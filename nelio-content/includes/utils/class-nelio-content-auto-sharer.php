<?php
/**
 * This file shares content automatically.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.3.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\key_by;

/**
 * This class implements all the functions used for sharing content automatically on social media.
 */
class Nelio_Content_Auto_Sharer {

	const SCHEDULE_WEEK      = 'nelio_content_social_automations_schedule_week';
	const RESET_MESSAGES     = 'nelio_content_social_automations_reset_social_messages';
	const MAX_SHARES_PER_DAY = 30;

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Auto_Sharer|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Auto_Sharer
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'enable_cron_tasks' ) );
	}

	/**
	 * Callback to enable cron tasks.
	 *
	 * @return void
	 */
	public function enable_cron_tasks() {
		add_action( self::SCHEDULE_WEEK, array( $this, 'schedule_week' ) );
		add_action( self::RESET_MESSAGES, array( $this, 'schedule_week' ) );
		$this->add_schedule_week_cron();
	}


	/**
	 * Callback to reset.
	 *
	 * @return void
	 */
	public function reset() {
		delete_transient( 'nc_automation_groups' );
		delete_option( 'nc_reshare_last_day' );
		wp_schedule_single_event( time(), self::RESET_MESSAGES, array( time() ) );
	}

	/**
	 * Callback to schedule week.
	 *
	 * @return void
	 */
	public function schedule_week() {

		$today       = gmdate( 'Y-m-d', time() );
		$weekdays    = array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' );
		$end_of_week = ( absint( get_option( 'start_of_week', 0 ) ) + 6 ) % 7;
		$end_of_week = $weekdays[ $end_of_week ];

		$last_day_to_schedule = gmdate( 'Y-m-d', strtotime( "next {$end_of_week}" ) );
		$last_scheduled_day   = max( $today, get_option( 'nc_reshare_last_day', $today ) );
		if ( $last_scheduled_day >= $last_day_to_schedule ) {
			return;
		}

		$days_to_schedule = $this->diff_days( $today, $last_day_to_schedule );
		$posts            = $this->get_posts_for_resharing( $days_to_schedule );
		if ( empty( $posts ) ) {
			return;
		}

		$posts_per_day = $this->array_split( $posts, $days_to_schedule );
		foreach ( $posts_per_day as $posts ) {
			$last_scheduled_day = strtotime( $last_scheduled_day . ' +1 day' );
			if ( false === $last_scheduled_day ) {
				break;
			}
			$last_scheduled_day = gmdate( 'Y-m-d', $last_scheduled_day );
			$this->schedule_day( $last_scheduled_day, $posts );
		}
		update_option( 'nc_reshare_last_day', $last_scheduled_day );
	}

	/**
	 * This function requests the cloud to generate all the messages for a given
	 * day, using the given list of posts.
	 *
	 * @param string          $day   Day to schedule.
	 * @param list<TAWS_Post> $posts List of posts used to "fill" the day.
	 *
	 * @return void
	 *
	 * @since  1.3.0
	 */
	public function schedule_day( $day, $posts ) {
		$posts = array_map( fn( $p ) => array_merge( $p, array( 'content' => '' ) ), $posts );
		if ( empty( $posts ) ) {
			return;
		}

		$body = wp_json_encode(
			array(
				'day'   => $day,
				'posts' => $posts,
			)
		);
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = sprintf(
			nelio_content_get_api_url( '/site/%s/social/auto', 'wp' ),
			nelio_content_get_site_id()
		);
		wp_remote_request( $url, $data );
	}

	/**
	 * Adds schedule week cron.
	 *
	 * @return void
	 */
	private function add_schedule_week_cron() {
		if ( wp_next_scheduled( self::SCHEDULE_WEEK ) ) {
			return;
		}

		$time     = sprintf(
			'%02d:%02d:00',
			wp_rand( 0, 4 ),
			wp_rand( 0, 59 )
		);
		$today    = gmdate( 'Y-m-d', time() ) . 'T' . $time;
		$tomorrow = strtotime( $today . ' +1 day' );
		if ( false === $tomorrow ) {
			return;
		}

		wp_schedule_event( $tomorrow, 'daily', self::SCHEDULE_WEEK );
	}

	/**
	 * Retrieves posts for resharing.
	 *
	 * @param int $num_of_days Number of days.
	 *
	 * @return list<TAWS_Post>
	 */
	private function get_posts_for_resharing( $num_of_days ) {
		/** @var wpdb $wpdb */
		global $wpdb;
		$queries = array(
			'top' => $this->get_top_posts_query(),
			'1mo' => $this->get_recent_posts_query( 1 ),
			'3mo' => $this->get_recent_posts_query( 3 ),
			'6mo' => $this->get_recent_posts_query( 6 ),
			'1yr' => $this->get_recent_posts_query( 12 ),
			'2yr' => $this->get_recent_posts_query( 24 ),
			'fbq' => $this->get_fallback_query(),
		);

		/** @var list<int> $post_ids */
		$post_ids        = array();
		$total_posts     = absint( $num_of_days * self::MAX_SHARES_PER_DAY );
		$posts_per_block = absint( ceil( $total_posts / count( $queries ) ) );
		foreach ( $queries as $key => $query ) {
			$query = $this->exclude_post_ids( $post_ids, $query );
			$count = 'fbq' !== $key ? $posts_per_block : $total_posts - count( $post_ids );
			$query = $this->limit_post_count( $count, $query );

			/** @var list<int> $post_ids */
			$post_ids = array_merge(
				$post_ids,
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				array_map( fn( $id ) => absint( $id ), $wpdb->get_col( $query ) )
			);
		}

		/**
		 * Filters the post IDs that will be reshared.
		 *
		 * @param list<int>  $post_ids List of post IDs.
		 * @param number $days     Number of days to schedule.
		 *
		 * @since 3.0.0
		 */
		$post_ids = apply_filters( 'nelio_content_posts_to_reshare', $post_ids, $num_of_days );

		if ( empty( $post_ids ) ) {
			return array();
		}

		if ( count( $post_ids ) < $num_of_days ) {
			$post_ids = array_fill( 0, $num_of_days, $post_ids );
			$post_ids = array_merge( ...$post_ids );
			$post_ids = array_slice( $post_ids, 0, $num_of_days );
		}
		shuffle( $post_ids );

		$post_helper = Nelio_Content_Post_Helper::instance();
		$posts       = array_map( fn( $id ) => $post_helper->post_to_aws_json( $id ), $post_ids );
		return array_values( array_filter( $posts ) );
	}

	/**
	 * Gets top posts query.
	 *
	 * @return string
	 */
	private function get_top_posts_query() {
		$query  = $this->get_basic_query();
		$joins  = array();
		$wheres = array();

		$key      = '_nc_engagement_total';
		$joins[]  = $this->left_meta_join( 'engtot', $key );
		$wheres[] = sprintf( '(engtot.meta_value >= %d)', $this->get_meta_value_threshold( $key ) );

		$settings = Nelio_Content_Settings::instance();
		$ga_data  = $settings->get( 'google_analytics_data' );
		$ga_view  = $ga_data['id'];
		if ( ! empty( $ga_view ) ) {
			$key      = "_nc_pageviews_total_{$ga_view}";
			$joins[]  = $this->left_meta_join( 'googanal', $key );
			$wheres[] = sprintf( '(googanal.meta_value >= %d)', $this->get_meta_value_threshold( $key ) );
		}

		$join  = implode( ' ', $joins );
		$where = 'AND (' . implode( ' OR ', $wheres ) . ')';

		$query = str_replace( '{{joins}}', $join, $query );
		$query = str_replace( '{{wheres}}', $where, $query );
		return $query;
	}

	/**
	 * Gets recent posts query.
	 *
	 * @param int $max_months Max months.
	 *
	 * @return string
	 */
	private function get_recent_posts_query( $max_months ) {
		$today = gmdate( 'Y-m-d', time() );
		$date  = strtotime( "{$today} - {$max_months} months" );
		$date  = false !== $date ? $date : absint( time() - ( MONTH_IN_SECONDS * $max_months ) );
		$date  = gmdate( 'Y-m-d', $date );
		$where = sprintf( 'AND \'%s\' <= p.post_date_gmt', esc_sql( $date ) );

		$query = $this->get_basic_query();
		$query = str_replace( '{{joins}}', '', $query );
		$query = str_replace( '{{wheres}}', $where, $query );
		return $query;
	}

	/**
	 * Gets fallback query.
	 *
	 * @return string
	 */
	private function get_fallback_query() {
		$query = $this->get_basic_query();
		$query = str_replace( '{{joins}}', '', $query );
		$query = str_replace( '{{wheres}}', '', $query );
		return $query;
	}

	/**
	 * Gets basic query.
	 *
	 * @return string
	 */
	private function get_basic_query() {
		/** @var string $query */
		static $query;
		if ( ! empty( $query ) ) {
			return $query;
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		$query = '' .
			"SELECT DISTINCT ID FROM {$wpdb->posts} p {{joins}}" .
			'WHERE p.post_status = \'publish\' AND p.ID NOT IN {{pids}} {{wheres}} ' .
			'ORDER BY RAND() ' .
			'LIMIT 0, {{post_count}}';
		$query = $this->add_post_type_filter( $query );
		$query = $this->add_automation_group_filter( $query );
		$query = $this->add_share_filter( $query );
		$query = $this->add_end_mode_filter( $query );

		return $query;
	}

	/**
	 * Adds post type filter.
	 *
	 * @param string $query Query.
	 *
	 * @return string
	 */
	private function add_post_type_filter( $query ) {
		$post_types = nelio_content_get_post_types( 'social' );
		$post_types = array_map(
			function ( $type ) {
				$type = esc_sql( sanitize_text_field( $type ) );
				return "'{$type}'";
			},
			$post_types
		);

		$where = 'AND p.post_type IN (' . implode( ',', $post_types ) . ')';

		return str_replace( '{{wheres}}', "{$where} {{wheres}}", $query );
	}

	/**
	 * Adds automation group filter.
	 *
	 * @param string $query Query.
	 *
	 * @return string
	 */
	private function add_automation_group_filter( $query ) {
		$groups = nelio_content_get_automation_groups();
		$groups = array_filter(
			$groups,
			function ( $g ) {
				/** @var TAutomation_Group $g */

				return (
					! empty( $g['priority'] ) &&
					array_reduce(
						$g['profileSettings'],
						function ( $carry, $ps ) {
							/** @var bool                                    $carry */
							/** @var TSimplified_Profile_Automation_Settings $ps    */

							if ( $carry ) {
								return $carry;
							}
							return ! empty( $ps['enabled'] ) && ! empty( $ps['reshare']['enabled'] );
						},
						false
					)
				);
			}
		);

		if ( empty( $groups ) ) {
			return str_replace( '{{wheres}}', 'AND FALSE', $query );
		}

		$groups = key_by( $groups, 'id' );
		if ( ! empty( $groups['universal'] ) ) {
			$publication = $groups['universal']['publication']['type'];
			if ( 'always' === $publication ) {
				return $query;
			}
		}

		$term_map   = $this->get_term_taxonomy_ids_from_groups( $groups );
		$tax_tables = array();
		if ( ! empty( $term_map ) ) {
			$taxs  = array_keys( $term_map );
			$names = array_map(
				function ( $i ) {
					++$i;
					return "tr{$i}";
				},
				array_keys( $taxs )
			);

			$tax_tables = array_combine( $taxs, $names );
		}

		$query  = $this->join_term_tables( $term_map, $tax_tables, $query );
		$today  = gmdate( 'Y-m-d', time() );
		$wheres = array_map(
			function ( $group ) use ( $today, &$term_map, &$tax_tables ) {
				$where = array();

				$post_type = $group['postType'] ?? '';
				if ( ! empty( $post_type ) ) {
					$where[] = sprintf( "p.post_type = '%s'", esc_sql( $post_type ) );
				}

				$taxonomies = $group['taxonomies'] ?? array();
				if ( ! empty( $taxonomies ) ) {
					$conds   = array_map(
						function ( $tax, $terms ) use ( &$term_map, &$tax_tables ) {
							/** @var string    $tax   */
							/** @var list<int> $terms */

							$terms = array_map(
								function ( $term ) use ( $tax, &$term_map ) {
									/** @var int $term */

									return $term_map[ $tax ][ $term ] ?? 0;
								},
								$terms
							);
							$terms = array_values( array_filter( $terms ) );
							if ( empty( $terms ) ) {
								return '';
							}
							$table = $tax_tables[ $tax ];
							return sprintf( "{$table}.term_taxonomy_id IN (%s)", implode( ',', $terms ) );
						},
						array_keys( $taxonomies ),
						array_values( $taxonomies )
					);
					$where[] = '(' . implode( ' AND ', array_filter( $conds ) ) . ')';
				}

				$authors = $group['authors'] ?? array();
				$authors = array_values( array_filter( array_map( fn( $id ) => absint( $id ), $authors ) ) );
				if ( ! empty( $authors ) ) {
					$where[] = 'p.post_author IN (' . implode( ', ', $authors ) . ')';
				}

				if ( 'max-age' === $group['publication']['type'] ) {
					$days    = $group['publication']['days'];
					$date    = strtotime( "{$today} - {$days} days" );
					$date    = false !== $date ? $date : absint( time() - ( DAY_IN_SECONDS * $days ) );
					$date    = gmdate( 'Y-m-d', $date );
					$where[] = sprintf( '\'%s\' <= p.post_date_gmt', esc_sql( $date ) );
				}

				return empty( $where ) ? '' : '(' . implode( ' AND ', $where ) . ')';
			},
			$groups
		);
		$where  = implode( ' OR ', array_filter( $wheres ) );
		return str_replace( '{{wheres}}', "AND ({$where}) {{wheres}}", $query );
	}

	/**
	 * Gets term taxonomy IDs from groups.
	 *
	 * @param array<TAutomation_Group> $groups Groups.
	 *
	 * @return array<string,array<int,int>>
	 */
	private function get_term_taxonomy_ids_from_groups( $groups ) {
		/** @var array<string,array<int,int>> $taxonomies */
		$taxonomies = array_reduce(
			$groups,
			function ( $result, $g ) {
				/** @var array<string,array<int,int>> $result */
				/** @var TAutomation_Group $g                 */

				$gt = $g['taxonomies'] ?? array();
				foreach ( $gt as $tax => $terms ) {
					$terms          = array_map( fn( $t ) => absint( $t ), $terms );
					$terms          = array_values( array_filter( $terms ) );
					$result[ $tax ] = isset( $result[ $tax ] ) ? $result[ $tax ] : array();
					foreach ( $terms as $term ) {
						$result[ $tax ][ $term ] = 0;
					}
				}
				return $result;
			},
			array()
		);

		$wheres = array();
		foreach ( $taxonomies as $tax => $terms ) {
			$wheres[] = sprintf(
				"(t.taxonomy = '%s' AND t.term_id IN (%s))",
				esc_sql( $tax ),
				implode( ',', array_merge( array( 0 ), array_keys( $terms ) ) )
			);
		}

		/** @var wpdb $wpdb */
		global $wpdb;
		$sql = "SELECT term_id AS old_id, term_taxonomy_id AS new_id, taxonomy FROM {$wpdb->term_taxonomy} t WHERE {{wheres}}";
		$sql = str_replace( '{{wheres}}', implode( ' OR ', $wheres ), $sql );

		/** @var list<array{taxonomy:string,old_id:int,new_id:int}> $mappings */
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$mappings = $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $mappings as $m ) {
			$taxonomies[ $m['taxonomy'] ][ absint( $m['old_id'] ) ] = absint( $m['new_id'] );
		}

		$taxonomies = array_map(
			fn ( $terms ) => array_values( array_filter( $terms ) ),
			$taxonomies
		);

		return array_filter( $taxonomies );
	}

	/**
	 * Joins term tables.
	 *
	 * @param array<string,array<int,int>> $tax_terms   Tax terms.
	 * @param array<string,string>         $join_tables Tables.
	 * @param string                       $query       Query.
	 *
	 * @return string
	 */
	private function join_term_tables( $tax_terms, $join_tables, $query ) {
		$joins = array();
		foreach ( $tax_terms as $tax => $terms ) {
			$joins[] = array(
				'terms' => $terms,
				'table' => isset( $join_tables[ $tax ] ) ? $join_tables[ $tax ] : false,
			);
		}

		/** @var string */
		return array_reduce(
			$joins,
			function ( $q, $j ) {
				/** @var string $q */
				/** @var array{terms:list<int>,table:string|false} $j */

				$terms = $j['terms'];
				$table = $j['table'];
				$join  = sprintf(
					'LEFT JOIN wp_term_relationships %1$s ON (p.ID = %1$s.object_id AND %1$s.term_taxonomy_id IN (%2$s))',
					$table,
					implode( ',', $terms )
				);
				return ! empty( $table ) ? str_replace( '{{joins}}', "{$join} {{joins}}", $q ) : $q;
			},
			$query
		);
	}

	/**
	 * Adds share filter.
	 *
	 * @param string $query Query.
	 *
	 * @return string
	 */
	private function add_share_filter( $query ) {
		$settings = Nelio_Content_Settings::instance();

		$auto_share = 'include-in-auto-share' === $settings->get( 'auto_share_default_mode' )
			? array(
				'key'  => '_nc_exclude_from_auto_share',
				'cond' => 'IS NULL',
			)
			: array(
				'key'  => '_nc_include_in_auto_share',
				'cond' => 'IS NOT NULL',
			);

		$join  = $this->left_meta_join( 'share_filter', $auto_share['key'] );
		$where = "AND share_filter.meta_key {$auto_share['cond']}";

		$query = str_replace( '{{joins}}', "{$join} {{joins}}", $query );
		$query = str_replace( '{{wheres}}', "{$where} {{wheres}}", $query );
		return $query;
	}

	/**
	 * Adds end mode filter.
	 *
	 * @param string $query Query.
	 *
	 * @return string
	 */
	private function add_end_mode_filter( $query ) {
		$end_modes = nelio_content_get_auto_share_end_modes();
		$end_modes = key_by( $end_modes, 'value' );

		$conditions = array_map( array( $this, 'end_mode_to_sql_condition' ), $end_modes );
		$join       = $this->left_meta_join( 'end_mode', '_nc_auto_share_end_mode' );
		$where      = 'AND (' . implode( ' OR ', $conditions ) . ')';

		$query = str_replace( '{{joins}}', "{$join} {{joins}}", $query );
		$query = str_replace( '{{wheres}}', "{$where} {{wheres}}", $query );
		return $query;
	}

	/**
	 * Creates end mode SQL condition.
	 *
	 * @param TAuto_Share_End_Mode $mode Mode.
	 *
	 * @return string
	 */
	private function end_mode_to_sql_condition( $mode ) {
		$mv = 'end_mode.meta_value';
		$pd = 'p.post_date_gmt';

		if ( 'never' === $mode['value'] ) {
			return sprintf( '(%1$s IS NULL) OR (%1$s = \'never\')', $mv );
		}

		$today = gmdate( 'Y-m-d', time() );
		$date  = strtotime( "{$today} - {$mode['months']} months" );
		$date  = false !== $date ? $date : absint( time() - ( MONTH_IN_SECONDS * $mode['months'] ) );
		$date  = gmdate( 'Y-m-d', $date );
		return sprintf(
			'(%1$s = \'%2$s\' AND  \'%3$s\' <= %4$s)',
			$mv,
			esc_sql( $mode['value'] ),
			esc_sql( $date ),
			$pd
		);
	}

	/**
	 * Excludes post IDs.
	 *
	 * @param list<int> $post_ids Post IDs.
	 * @param string    $query    Query.
	 *
	 * @return string
	 */
	private function exclude_post_ids( $post_ids, $query ) {
		$pids = '(' . implode( ',', array_merge( array( 0 ), $post_ids ) ) . ')';
		return str_replace( '{{pids}}', $pids, $query );
	}

	/**
	 * Limits post count.
	 *
	 * @param int    $count Count.
	 * @param string $query Query.
	 *
	 * @return string
	 */
	private function limit_post_count( $count, $query ) {
		return str_replace( '{{post_count}}', "$count", $query );
	}

	/**
	 * Gets meta value threshold.
	 *
	 * @param string $meta_name Meta name.
	 *
	 * @return int
	 */
	private function get_meta_value_threshold( $meta_name ) {

		$meta_value = 0;

		// Get number of pages.
		$args          = array(
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => $meta_name,
			'orderby'        => 'meta_value_num',
			'order'          => 'desc',
		);
		$query         = new WP_Query( $args );
		$num_top_posts = $query->max_num_pages;
		wp_reset_postdata();

		// Get the "threshold" post.
		$last_good_post = min( $num_top_posts, 250 );
		$args['paged']  = $last_good_post;
		$query          = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$query->the_post();
			$meta_value = absint( get_post_meta( absint( get_the_ID() ), $meta_name, true ) );
		}
		wp_reset_postdata();

		return 1 + $meta_value;
	}

	/**
	 * Splits array.
	 *
	 * @template T
	 *
	 * @param list<T> $items Items.
	 * @param int     $parts Optional. Default: 1.
	 *
	 * @return list<list<T>>
	 */
	private function array_split( $items, $parts = 1 ) {

		if ( 1 >= $parts ) {
			return array( $items );
		}

		$index  = 0;
		$result = array_fill( 0, $parts, array() );
		$max    = ceil( count( $items ) / $parts );
		foreach ( $items as $v ) {
			if ( count( $result[ $index ] ) >= $max ) {
				++$index;
			}
			array_push( $result[ $index ], $v );
		}

		return $result;
	}

	/**
	 * Creates a left meta join.
	 *
	 * @param string $alias    Alias.
	 * @param string $meta_key Meta key.
	 *
	 * @return string
	 */
	private function left_meta_join( $alias, $meta_key ) {
		/** @var wpdb $wpdb */
		global $wpdb;
		return '' .
			"LEFT JOIN {$wpdb->postmeta} {$alias} ON (" .
			"p.ID = {$alias}.post_id AND " .
			"{$alias}.meta_key = '{$meta_key}')";
	}

	/**
	 * Returns the diff in days.
	 *
	 * @param string $a One date.
	 * @param string $b Another date.
	 *
	 * @return int
	 */
	private function diff_days( $a, $b ) {
		$a = new DateTime( $a );
		$b = new DateTime( $b );
		return absint( $a->diff( $b )->format( '%a' ) );
	}
}

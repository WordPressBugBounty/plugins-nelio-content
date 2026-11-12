<?php
/** Bounded lifetime rankings from local cached aggregate analytics. */

use function Nelio_Content\Abilities\decode_cursor;
use function Nelio_Content\Abilities\local_post_page;
use function Nelio_Content\Abilities\summarize_post;

defined( 'ABSPATH' ) || exit;

/**
 * Reuses the existing raw analytics query without rendering posts or refreshing providers.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_List_Top_Performing_Posts_Ability extends Nelio_Content_Ability {

	/** @return void */
	public function register() {
		$properties                                  = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['metric']                        = array(
			'type'        => 'string',
			'enum'        => array( 'engagement', 'pageviews' ),
			'default'     => 'engagement',
			'description' => 'Saved total used to rank posts from highest to lowest. engagement combines available social interaction counts and approved WordPress comments. pageviews uses the currently configured Google Analytics property and requires that configuration. Defaults to engagement. These are accumulated counts, not activity within a selected period.',
		);
		$properties['author_id']                     = array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Optional WordPress author ID whose published posts should be considered.',
		);
		$properties['post_type']                     = array(
			'type'        => 'string',
			'minLength'   => 1,
			'maxLength'   => 64,
			'description' => 'Optional post type with analytics enabled. Omit to include all analytics-enabled types.',
		);
		$properties['published_after']               = array(
			'type'        => 'string',
			'format'      => 'date',
			'description' => 'Optional first inclusive publication day in the site timezone, YYYY-MM-DD. Filters when posts were published, not when visits or interactions occurred.',
		);
		$properties['published_before']              = array(
			'type'        => 'string',
			'format'      => 'date',
			'description' => 'Optional last inclusive publication day in the site timezone, YYYY-MM-DD. May span multiple years; it must not precede published_after. Does not restrict the period covered by the counts.',
		);
		$item                                        = $this->post_summary_schema();
		$item['properties']['permalink']             = array(
			'type'        => 'string',
			'description' => 'WordPress permalink of this published post.',
		);
		$item['properties']['metric_value']          = array(
			'type'        => 'integer',
			'minimum'     => 0,
			'description' => 'Stored count of zero or more used to rank this post for the selected metric. A stored zero differs from a missing count, which is excluded. Provider coverage and freshness vary.',
		);
		$item['properties']['last_analytics_update'] = array(
			'type'        => 'string',
			'description' => 'Last recorded general analytics update in UTC, or empty when unavailable. This is not a per-provider freshness guarantee and does not prove that every source was updated.',
		);
		$item['required']                            = array_merge( $item['required'], array( 'permalink', 'metric_value', 'last_analytics_update' ) );
		$output                                      = $this->page_schema( $item, 'One page of readable published posts ranked by a stored accumulated count. This is not a ranking of activity within a measurement period. Missing counts are excluded; different posts may have different provider coverage and freshness.' );
		$output['properties']['metric']              = $properties['metric'];
		$output['required'][]                        = 'metric';
		wp_register_ability(
			'nelio-content/list-top-performing-posts',
			array(
				'label'               => _x( 'List top-performing posts', 'command', 'nelio-content' ),
				'description'         => 'Rank readable published posts by cached accumulated engagement or pageviews. Available in Free and Premium. Uses only post types with analytics enabled and existing counts, without contacting providers or refreshing metrics. Engagement includes available social interaction counts and approved WordPress comments; pageviews require a configured Google Analytics property. Optional date filters select publication dates, not a period of visits or interactions. Missing counts are excluded rather than treated as zero. Results are ordered by count from highest to lowest, then by newest publication date and highest post ID. Read one page and follow next_cursor with unchanged filters and per_page even when a page is empty. Rankings may change between requests as posts or counts change.',
				'category'            => 'nelio-content-analytics',
				'input_schema'        => $this->object_schema( $properties, array(), 'Total metric used for ranking, optional publication-date, author and post-type filters, and results returned one page at a time with page size limits. No measurement-period filter is available.' ),
				'output_schema'       => $output,
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', 'You cannot access post analytics.' );
		}
		$cursor = decode_cursor( $input );
		if ( is_wp_error( $cursor ) ) {
			return $cursor;
		}
		if ( '' !== $cursor['page'] ) {
			return new WP_Error( 'nelio_content_invalid_cursor', 'Use the cursor value from the previous response to this analytics ranking.' );
		}
		$dates = $this->publication_filter( $input );
		if ( is_wp_error( $dates ) ) {
			return $dates;
		}
		$types = nelio_content_get_post_types( 'analytics' );
		if ( isset( $input['post_type'] ) ) {
			if ( ! in_array( $input['post_type'], $types, true ) ) {
				return new WP_Error( 'nelio_content_invalid_post_type', 'Analytics are not enabled for this post type.' );
			}
			$types = array( $input['post_type'] );
		}
		$metric = $input['metric'] ?? 'engagement';
		$helper = Nelio_Content_Analytics_Helper::instance();
		$key    = $helper->get_cached_ranking_key( $metric );
		if ( is_wp_error( $key ) ) {
			return $key;
		}
		$limit = $input['per_page'] ?? 50;
		$args  = array(
			'post_type'           => $types,
			'post_status'         => 'publish',
			'posts_per_page'      => $limit + 1,
			'offset'              => $cursor['offset'],
			'author'              => $input['author_id'] ?? 0,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Same cached aggregate counter used by the existing ranking.
			'meta_key'            => $key,
			'orderby'             => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
				'ID'             => 'DESC',
			),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => $dates,
		);
		/** @var wpdb $wpdb */
		global $wpdb;
		$query = $helper->query_cached_posts( $args );
		if ( $wpdb->last_error ) {
			return new WP_Error( 'nelio_content_analytics_unavailable', 'The cached analytics ranking could not be read.' );
		}
		$source = $query->posts ?? array();
		$items  = array();
		foreach ( array_slice( $source, 0, $limit ) as $post ) {
			if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || ! in_array( $post->post_type, $types, true ) || ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}
			$value = filter_var( get_post_meta( $post->ID, $key, true ), FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0 ) ) );
			if ( false === $value ) {
				continue;
			}
			$item                          = summarize_post( $post );
			$item['permalink']             = get_permalink( $post );
			$item['metric_value']          = $value;
			$last_update                   = filter_var( get_post_meta( $post->ID, '_nc_last_update', true ), FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
			$item['last_analytics_update'] = false !== $last_update ? gmdate( DATE_ATOM, $last_update ) : '';
			$items[]                       = $item;
		}
		$page = local_post_page( $items, $cursor['offset'] + $limit, count( $source ) > $limit );
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		$page['metric'] = $metric;
		return $page;
	}

	/**
	 * Publication bounds are inclusive site-local dates, independently optional.
	 *
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	private function publication_filter( $input ) {
		$filter = array(
			'column'    => 'post_date',
			'inclusive' => true,
		);
		foreach ( array(
			'published_after'  => 'after',
			'published_before' => 'before',
		) as $field => $bound ) {
			if ( ! isset( $input[ $field ] ) ) {
				continue;
			}
			$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $input[ $field ], wp_timezone() );
			if ( ! $date || $date->format( 'Y-m-d' ) !== $input[ $field ] ) {
				return new WP_Error( 'nelio_content_invalid_date', 'Use real publication dates in YYYY-MM-DD format.' );
			}
			$filter[ $bound ] = $input[ $field ] . ( 'after' === $bound ? ' 00:00:00' : ' 23:59:59' );
		}
		if ( isset( $input['published_after'], $input['published_before'] ) && $input['published_after'] > $input['published_before'] ) {
			return new WP_Error( 'nelio_content_invalid_date_range', 'The final publication date must not precede the first.' );
		}
		return count( $filter ) > 2 ? $filter : array();
	}
}

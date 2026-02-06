<?php
/**
 * This file contains REST endpoints to work with analytics.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Analytics_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Analytics_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Analytics_REST_Controller
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/top-posts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_top_posts' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'author'   => array(
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'from'     => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'to'       => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'postType' => array(
							'required' => false,
							'type'     => 'string',
						),
						'sortBy'   => array(
							'required' => false,
							'type'     => 'string',
						),
						'page'     => array(
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'perPage'  => array(
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/top-posts/export',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_top_posts_csv' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'author'   => array(
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'from'     => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'to'       => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'postType' => array(
							'required' => false,
							'type'     => 'string',
						),
						'sortBy'   => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/connect',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connect_google_analytics' ),
					'permission_callback' => array( $this, 'validate_analytics_connection' ),
					'args'                => array(
						'token'         => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_not_empty',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'refresh-token' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_not_empty',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'expiration'    => array(
							'required'          => true,
							'type'              => 'numeric',
							'sanitize_callback' => 'absint',
						),
						'nonce'         => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_not_empty',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/ga4-properties',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ga4_properties' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/post',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_ids_to_update' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'page'   => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'period' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/analytics/post/(?P<id>[\d]+)/update',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_post_analytics' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to validate analytics connection.
	 *
	 * @param WP_REST_Request<array<string,string>> $request Request.
	 *
	 * @return bool
	 */
	public function validate_analytics_connection( $request ) {
		$token         = $request['token'] ?? '';
		$refresh_token = $request['refresh-token'] ?? '';
		$nonce         = $request['nonce'] ?? '';
		$secret        = get_option( 'nc_api_secret', '' );
		return md5( "{$token}{$refresh_token}{$secret}" ) === $nonce;
	}

	/**
	 * Returns the list of top posts that match the search criteria.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_top_posts( $request ) {

		// Load some settings.
		$enabled_post_types = nelio_content_get_post_types( 'analytics' );

		// Get the author id.
		$author_id = absint( $request['author'] ?? 0 );

		// Get the time interval.
		$first_day = $request['from'] ?? false;
		$last_day  = $request['to'] ?? false;
		$last_day  = is_string( $last_day ) ? "{$last_day} 23:59:59" : $last_day;

		// Post type.
		$post_type  = $request['postType'] ?? false;
		$post_types = ! empty( $post_type ) ? array( $post_type ) : $enabled_post_types;

		// Sort by criterion.
		$ranking_field = $request['sortBy'] ?? false;
		$ranking_field = ! empty( $ranking_field ) ? $ranking_field : false;
		$ranking_field = 'pageviews' === $ranking_field ? '_nc_pageviews_total' : '_nc_engagement_total';

		// Pagination.
		$posts_per_page = absint( $request['perPage'] ?? 10 );
		$page           = absint( $request['page'] ?? 1 );

		$args = array(
			'paged'               => $page,
			'posts_per_page'      => $posts_per_page,
			'author'              => $author_id,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'            => $ranking_field,
			'orderby'             => 'meta_value_num post_date',
			'order'               => 'desc',
			'post_type'           => $post_types,
			'date_query'          => array(
				'after'     => $first_day,
				'before'    => $last_day,
				'inclusive' => true,
			),
			'ignore_sticky_posts' => true,
		);

		$analytics = Nelio_Content_Analytics_Helper::instance();
		$result    = $analytics->get_paginated_posts( $args );
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Returns the list of top posts that match the search criteria.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return WP_Error|void
	 */
	public function export_top_posts_csv( $request ) {

		// Load some settings.
		$enabled_post_types = nelio_content_get_post_types( 'analytics' );

		// Get the author id.
		$author_id = absint( $request['author'] ?? 0 );

		// Get the time interval.
		$first_day = $request['from'] ?? false;
		$last_day  = $request['to'] ?? false;
		$last_day  = is_string( $last_day ) ? "{$last_day} 23:59:59" : $last_day;

		// Post type.
		$post_type  = $request['postType'] ?? false;
		$post_types = ! empty( $post_type ) ? array( $post_type ) : $enabled_post_types;

		// Sort by criterion.
		$ranking_field = $request['sortBy'] ?? false;
		$ranking_field = ! empty( $ranking_field ) ? $ranking_field : false;
		$ranking_field = 'pageviews' === $ranking_field ? '_nc_pageviews_total' : '_nc_engagement_total';

		// Pagination.
		$posts_per_page = 200;
		$page           = 1;

		$filename = sprintf(
			'nelio-content-analytics-%s.csv',
			gmdate( 'Ymd-His' )
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$fh = fopen( 'php://output', 'w' );
		if ( ! $fh ) {
			return new WP_Error(
				'csv-open-error',
				_x( 'Could not open output stream for CSV.', 'text', 'nelio-content' )
			);
		}

		$base_columns = array(
			'post_id',
			'title',
			'permalink',
			'post_type',
			'status',
			'author',
			'author_name',
			'post_date',
		);

		$engagement_columns = array_map(
			function ( $metric ) {
				return 'engagement_' . $metric;
			},
			Nelio_Content_Analytics_Helper::$engagement_metrics
		);

		$pageviews_columns = array_map(
			function ( $metric ) {
				return 'pageviews_' . $metric;
			},
			Nelio_Content_Analytics_Helper::$pageviews_metrics
		);

		// CSV header row.
		fputcsv(
			$fh,
			array_merge( $base_columns, $engagement_columns, $pageviews_columns )
		);

		do {
			$args = array(
				'paged'               => $page,
				'posts_per_page'      => $posts_per_page,
				'author'              => $author_id,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'            => $ranking_field,
				'orderby'             => 'meta_value_num post_date',
				'order'               => 'desc',
				'post_type'           => $post_types,
				'date_query'          => array(
					'after'     => $first_day,
					'before'    => $last_day,
					'inclusive' => true,
				),
				'ignore_sticky_posts' => true,
			);

			$analytics = Nelio_Content_Analytics_Helper::instance();
			$paginated = $analytics->get_paginated_posts( $args, true );
			$posts     = $paginated['results'];
			$has_more  = ! empty( $paginated['pagination']['more'] );

			foreach ( $posts as $post ) {
				fputcsv(
					$fh,
					array_merge(
						array(
							$post['id'],
							$post['title'],
							$post['permalink'],
							$post['type'],
							$post['status'],
							$post['author'],
							$post['authorName'],
							$post['date'],
						),
						array_map(
							function ( $metric ) use ( $post ) {
								return $post['statistics']['engagement'][ $metric ] ?? 0;
							},
							Nelio_Content_Analytics_Helper::$engagement_metrics
						),
						array_map(
							function ( $metric ) use ( $post ) {
								return $post['statistics']['pageviews'][ $metric ] ?? 0;
							},
							Nelio_Content_Analytics_Helper::$pageviews_metrics
						)
					)
				);
			}

			flush();
			++$page;
		} while ( $has_more );

		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit();
	}

	/**
	 * Connects Google Analytics by saving its access and refresh tokens.
	 *
	 * It returns a simple HTML page that closes the screen.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return void
	 */
	public function connect_google_analytics( $request ) {
		$analytics = Nelio_Content_Analytics_Helper::instance();

		/** @var string */
		$refresh_token = $request['refresh-token'];
		update_option( 'nc_ga_refresh_token', $refresh_token );

		/** @var string */
		$token = $request['token'];
		/** @var int */
		$expiration = $request['expiration'];
		update_option( 'nc_ga_token', $token );
		update_option( 'nc_ga_token_expiration', $analytics->normalize_ts( $expiration ) );

		delete_option( 'nc_ga_token_error' );

		header( 'Content-Type: text/html; charset=UTF-8' );
		echo '<!DOCTYPE html>';
		echo '<html><head><script>window.close();</script></head></html>';
		die();
	}

	/**
	 * Gets GA4 properties.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_ga4_properties() {
		$analytics = Nelio_Content_Analytics_Helper::instance();
		$result    = $analytics->get_ga4_account_properties();
		return new WP_REST_Response( $result, 200 );
	}//end get_ga4_properties()

	/**
	 * Returns a list of IDs of posts that require updating.
	 *
	 * @param WP_REST_Request<array{page:int,period:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_post_ids_to_update( $request ) {

		$enabled_post_types = nelio_content_get_post_types( 'analytics' );

		$ppp = 10;
		/** @var int */
		$page = $request['page'];
		/** @var string */
		$period = $request['period'];

		$args = array(
			'paged'          => $page,
			'post_status'    => 'publish',
			'posts_per_page' => $ppp,
			'orderby'        => 'date',
			'order'          => 'desc',
			'post_type'      => $enabled_post_types,
		);

		if ( 'month' === $period || 'year' === $period ) {
			$args['date_query'] = array(
				array(
					'column' => 'post_date_gmt',
					'after'  => '1 ' . $period . ' ago',
				),
			);
		}

		$query  = new WP_Query( $args );
		$result = array(
			'ids'   => wp_list_pluck( $query->posts, 'ID' ),
			'more'  => $page < $query->max_num_pages,
			'total' => absint( $query->found_posts ),
			'ppp'   => $ppp,
		);
		wp_reset_postdata();

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Updates the analytics of all posts included in the current period.
	 *
	 * @param WP_REST_Request<array{id:int}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_post_analytics( $request ) {
		$analytics = Nelio_Content_Analytics_Helper::instance();
		$result    = $analytics->update_statistics( absint( $request['id'] ), 'now' );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 200 );
	}
}

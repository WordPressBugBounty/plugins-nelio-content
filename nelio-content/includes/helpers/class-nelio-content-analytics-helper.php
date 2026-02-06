<?php
/**
 * This file contains a class with some analytics-related helper functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/helpers
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements analytics-related helper functions.
 */
class Nelio_Content_Analytics_Helper {

	/**
	 * NOTE: remember to update “update_statistics” to actually pull new metrics from each social network.
	 *
	 * @var list<string>
	 */
	public static $engagement_metrics = array( 'total', 'twitter', 'facebook', 'band', 'bluesky', 'linkedin', 'mastodon', 'threads', 'tumblr', 'vk', 'pinterest', 'reddit' );

	/**
	 * @var list<string>
	 */
	public static $pageviews_metrics = array( 'total', 'twitter', 'facebook', 'band', 'bluesky', 'linkedin', 'mastodon', 'medium', 'pinterest', 'instagram', 'reddit', 'telegram', 'threads', 'discord', 'slack', 'tiktok', 'tumblr', 'vk' );

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Analytics_Helper|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Analytics_Helper
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
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );
		add_action( 'init', array( $this, 'maybe_enable_cron_tasks' ) );
		add_action( 'wp_update_comment_count', array( $this, 'update_comment_count' ), 10, 2 );
	}

	/**
	 * Enables or disables cron tasks on WordPress’ init based on current settings.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function maybe_enable_cron_tasks() {
		$use_analytics = ! empty( nelio_content_get_post_types( 'analytics' ) );
		if ( $use_analytics ) {
			$this->enable_analytics_cron_tasks();
		} else {
			$this->disable_analytics_cron_tasks();
		}
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array<string,array{interval:int,display:string}> $schedules List of schedules.
	 *
	 * @return array<string,array{interval:int,display:string}>
	 *
	 * @since  2.0.0
	 */
	public function add_cron_interval( $schedules ) {
		$schedules['nc_four_hours'] = array(
			'interval' => 4 * HOUR_IN_SECONDS,
			'display'  => esc_html_x( 'Every Four Hours (Nelio Content)', 'text', 'nelio-content' ),
		);
		return $schedules;
	}

	/**
	 * Normalizes timetamp into seconds.
	 *
	 * @param int|float $value Value.
	 *
	 * @return int
	 */
	public function normalize_ts( $value ) {
		return absint( $value > 9999999999 ? floor( $value / 1000 ) : $value );
	}

	/**
	 * Enables analytics cron tasks.
	 *
	 * @return void
	 */
	private function enable_analytics_cron_tasks() {

		$time = time();

		add_action( 'nelio_content_analytics_today_cron_hook', array( $this, 'update_today_posts' ) );
		if ( ! wp_next_scheduled( 'nelio_content_analytics_today_cron_hook' ) ) {
			wp_schedule_event( $time, 'nc_four_hours', 'nelio_content_analytics_today_cron_hook' );
		}

		add_action( 'nelio_content_analytics_month_cron_hook', array( $this, 'update_month_posts' ) );
		if ( ! wp_next_scheduled( 'nelio_content_analytics_month_cron_hook' ) ) {
			wp_schedule_event( $time + 3600, 'nc_four_hours', 'nelio_content_analytics_month_cron_hook' );
		}

		add_action( 'nelio_content_analytics_other_cron_hook', array( $this, 'update_other_posts' ) );
		if ( ! wp_next_scheduled( 'nelio_content_analytics_other_cron_hook' ) ) {
			wp_schedule_event( $time + 7200, 'nc_four_hours', 'nelio_content_analytics_other_cron_hook' );
		}
	}

	/**
	 * Disables analytics cron tasks.
	 *
	 * @return void
	 */
	private function disable_analytics_cron_tasks() {

		$timestamp = absint( wp_next_scheduled( 'nelio_content_analytics_today_cron_hook' ) );
		wp_unschedule_event( $timestamp, 'nelio_content_analytics_today_cron_hook' );

		$timestamp = absint( wp_next_scheduled( 'nelio_content_analytics_month_cron_hook' ) );
		wp_unschedule_event( $timestamp, 'nelio_content_analytics_month_cron_hook' );

		$timestamp = absint( wp_next_scheduled( 'nelio_content_analytics_other_cron_hook' ) );
		wp_unschedule_event( $timestamp, 'nelio_content_analytics_other_cron_hook' );

		$timestamp = absint( wp_next_scheduled( 'nelio_content_analytics_top_cron_hook' ) );
		wp_unschedule_event( $timestamp, 'nelio_content_analytics_top_cron_hook' );
	}

	/**
	 * Update analytics of all posts published today.
	 *
	 * @return void
	 *
	 * @since  1.2.0
	 */
	public function update_today_posts() {

		// Let's get the posts published today.
		$today = getdate();
		$args  = array(
			'posts_per_page' => -1,
			'date_query'     => array(
				array(
					'year'  => $today['year'],
					'month' => $today['mon'],
					'day'   => $today['mday'],
				),
			),
		);

		$posts_to_update = $this->get_posts_using_last_update( $args );
		foreach ( $posts_to_update as $post ) {
			$post_id = $post['id'];
			$this->update_statistics( $post_id );
		}
	}

	/**
	 * Update analytics of 10 random posts published this month.
	 *
	 * @return void
	 *
	 * @since  1.2.0
	 */
	public function update_month_posts() {

		// Let's get the posts to update.
		$args = array(
			'posts_per_page' => 10,
			'date_query'     => array(
				array(
					'column' => 'post_date_gmt',
					'after'  => '1 month ago',
				),
			),
		);

		$posts_to_update = $this->get_posts_using_last_update( $args );
		foreach ( $posts_to_update as $post ) {
			$post_id = $post['id'];
			$this->update_statistics( $post_id );
		}
	}

	/**
	 * Update analytics of 10 random posts published before this month.
	 *
	 * @return void
	 *
	 * @since  1.2.0
	 */
	public function update_other_posts() {

		// Let's get 10 posts before this month.
		$args = array(
			'posts_per_page' => 10,
			'date_query'     => array(
				array(
					'column' => 'post_date_gmt',
					'before' => '1 month ago',
				),
			),
		);

		$posts_to_update = $this->get_posts_using_last_update( $args );
		foreach ( $posts_to_update as $post ) {
			$post_id = $post['id'];
			$this->update_statistics( $post_id );
		}
	}

	/**
	 * Helper function that, given a certain post ID, updates its analytics.
	 *
	 * @param  integer $post_id     the post whose analytics has to be updated.
	 * @param  mixed   $update_now  if set to `now`, the post will be updated right away. Otherwise, it’ll depend on the last update.
	 *
	 * @return true|WP_Error
	 *
	 * @since  1.2.0
	 */
	public function update_statistics( $post_id, $update_now = false ) {
		// Convert to boolean.
		$update_now = 'now' === $update_now;

		// Safe guards.
		if ( empty( $post_id ) ) {
			return new WP_Error( 'nelio-content-missing-post-id', _x( 'Missing post ID', 'text', 'nelio-content' ) );
		}

		if ( ! $update_now && ! $this->needs_to_be_updated( $post_id ) ) {
			return true;
		}

		// Compute engagement.
		$social_analytics = $this->get_social_count( $post_id );
		$url              = get_permalink( $post_id );
		$url              = is_string( $url ) ? $url : '';
		$engagement       = array(
			'twitter'   => $social_analytics['twitter'],
			'facebook'  => $social_analytics['facebook'],
			'band'      => $social_analytics['band'],
			'bluesky'   => $social_analytics['bluesky'],
			'linkedin'  => $social_analytics['linkedin'],
			'mastodon'  => $social_analytics['mastodon'],
			'threads'   => $social_analytics['threads'],
			'tumblr'    => $social_analytics['tumblr'],
			'vk'        => $social_analytics['vk'],
			'pinterest' => $this->get_pinterest_count( $url ),
			'reddit'    => $this->get_reddit_count( $url ),
			'comments'  => intval( wp_count_comments( $post_id )->approved ),
		);
		$this->save_engagement( $post_id, $engagement );

		// Compute pageviews.
		$settings = Nelio_Content_Settings::instance();
		$ga_data  = $settings->get( 'google_analytics_data' );
		$ga4_prop = $ga_data['id'];
		if ( ! empty( $ga4_prop ) ) {
			$date = get_the_date( 'Y-m-d', $post_id );
			if ( is_string( $date ) ) {
				$pageviews = $this->get_ga_data( $ga4_prop, $post_id, $url, $date );
				if ( is_wp_error( $pageviews ) ) {
					return $pageviews;
				}
				$this->save_pageviews( $ga4_prop, $post_id, $pageviews );
			}
		}

		// Refresh last update.
		update_post_meta( $post_id, '_nc_last_update', time() );
		return true;
	}

	/**
	 * Helper function that, given a certain post ID, retrieves its analytics.
	 *
	 * @param  integer $post_id the post whose analytics has to be recovered.
	 * @param  bool    $raw     whether raw numbers (true) or human-friendly numbers (false) are to be returned.
	 *
	 * @return TPost_Stats the statistics of the given post.
	 *
	 * @since  1.2.0
	 */
	public function get_post_stats( $post_id, $raw = false ) {

		// LAST UPDATE.
		$last_update = get_post_meta( $post_id, '_nc_last_update', true );
		if ( empty( $last_update ) ) {
			$last_update = 0;
		}

		// ENGAGEMENT.
		$engagement = array();
		foreach ( self::$engagement_metrics as $metric ) {
			$value                 = get_post_meta( $post_id, $this->engagement_key( $metric ), true );
			$value                 = empty( $value ) ? 0 : absint( $value );
			$engagement[ $metric ] = $raw ? strval( $value ) : $this->human_number( $value );
		}
		$value                  = intval( wp_count_comments( $post_id )->approved );
		$engagement['comments'] = $raw ? strval( $value ) : $this->human_number( $value );

		// PAGEVIEWS.
		$settings  = Nelio_Content_Settings::instance();
		$ga_data   = $settings->get( 'google_analytics_data' );
		$ga4_prop  = $ga_data['id'];
		$pageviews = get_post_meta( $post_id, $this->pageviews_total_key( $ga4_prop ), true );
		$pageviews = array(
			'total' => $raw ? strval( absint( $pageviews ) ) : $this->human_number( absint( $pageviews ) ),
		);

		$social_data = get_post_meta( $post_id, $this->pageviews_social_data_key( $ga4_prop ), true );
		if ( is_array( $social_data ) ) {
			$social_data = array_filter( $social_data );
			foreach ( $social_data as $network => $value ) {
				$pageviews[ $network ] = $raw ? strval( absint( $value ) ) : $this->human_number( absint( $value ) );
			}
		}

		return array(
			'engagement' => $engagement,
			'pageviews'  => $pageviews,
		);
	}

	/**
	 * Get a set of posts from WordPress, embedded in an object for pagination.
	 *
	 * @param array<mixed> $params Parameters to filter the search.
	 * @param bool         $raw    Whether raw numbers (true) or human-friendly numbers (false) are to be returned in post statistics.
	 *
	 * @return array{results:list<TPost>,pagination:array{more:bool,pages:int}}
	 *
	 * @since  2.0.0
	 */
	public function get_paginated_posts( $params, $raw = false ) {

		// Load some settings.
		$settings           = Nelio_Content_Settings::instance();
		$enabled_post_types = nelio_content_get_post_types( 'analytics' );

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => $enabled_post_types,
		);

		$args = wp_parse_args( $params, $defaults );

		if ( isset( $args['meta_key'] ) &&
			'_nc_pageviews_total' === $args['meta_key'] ) {
			$ga_data = $settings->get( 'google_analytics_data' );
			$ga_view = $ga_data['id'];
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_key'] = $this->pageviews_total_key( $ga_view );
		}

		$query = new WP_Query( $args );

		$posts       = array();
		$post_helper = Nelio_Content_Post_Helper::instance();
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			if ( empty( $post_id ) ) {
				continue;
			}

			$aux = $post_helper->post_to_json( $post_id, $raw );
			if ( ! empty( $aux ) ) {
				array_push( $posts, $aux );
			}
		}

		wp_reset_postdata();

		// Build result object, ready for pagination.
		$page = isset( $params['paged'] ) ? $params['paged'] : 1;
		return array(
			'results'    => $posts,
			'pagination' => array(
				'more'  => $page < $query->max_num_pages,
				'pages' => $query->max_num_pages,
			),
		);
	}

	/**
	 * Returns a list of posts using last update.
	 *
	 * @param array<mixed> $params Params.
	 *
	 * @return list<TPost>
	 */
	private function get_posts_using_last_update( $params ) {

		$params = wp_parse_args( $params, array( 'posts_per_page' => 10 ) );

		$post_helper = Nelio_Content_Post_Helper::instance();

		// Load some settings.
		$enabled_post_types = nelio_content_get_post_types( 'analytics' );

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => $enabled_post_types,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query'  => array(
				array(
					'key'     => '_nc_last_update',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$args = wp_parse_args( $params, $defaults );

		$query = new WP_Query( $args );

		$posts = array();
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			if ( empty( $post_id ) ) {
				continue;
			}

			$aux = $post_helper->post_to_json( $post_id );
			if ( ! empty( $aux ) ) {
				array_push( $posts, $aux );
			}
		}

		wp_reset_postdata();

		$posts_to_find = isset( $params['posts_per_page'] ) && is_numeric( $params['posts_per_page'] ) ? intval( $params['posts_per_page'] ) : 0;
		if ( empty( $posts_to_find ) || count( $posts ) === $posts_to_find ) {
			return $posts;
		}

		if ( -1 !== $posts_to_find ) {
			$params['posts_per_page'] = $posts_to_find - count( $posts );
		}

		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => $enabled_post_types,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'    => '_nc_last_update',
			'orderby'     => 'meta_value_num',
			'order'       => 'ASC',
		);

		$args = wp_parse_args( $params, $defaults );

		$query = new WP_Query( $args );
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			if ( empty( $post_id ) ) {
				continue;
			}

			$aux = $post_helper->post_to_json( $post_id );
			if ( ! empty( $aux ) ) {
				array_push( $posts, $aux );
			}
		}

		wp_reset_postdata();

		return $posts;
	}

	/**
	 * Helper function that updates the total engagement value when a new comment
	 * occurs in WordPress or an old comment changes its status.
	 *
	 * @param int $post_id           Post whose engagement needs to be updated.
	 * @param int $new_comment_count New comment count.
	 *
	 * @return void
	 *
	 * @since  1.2.0
	 */
	public function update_comment_count( $post_id, $new_comment_count ) {

		$total = 0;
		foreach ( self::$engagement_metrics as $metric ) {
			if ( 'total' === $metric ) {
				continue;
			}
			$total += absint( get_post_meta( $post_id, $this->engagement_key( $metric ), true ) );
		}

		update_post_meta( $post_id, $this->engagement_key( 'total' ), $total + $new_comment_count );
	}

	/**
	 * Helper function that obtains the access token and refresh token in Google Analytics.
	 *
	 * @return string|false
	 */
	public function refresh_access_token() {

		$code = get_option( 'nc_ga_refresh_token', false );
		if ( empty( $code ) ) {
			return false;
		}

		$body = wp_json_encode( array( 'code' => $code ) );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nelio_content_get_api_url( '/connect/ga/refresh', 'wp' );
		$response = wp_remote_request( $url, $data );

		if ( is_wp_error( $response ) ) {
			update_option( 'nc_ga_token_error', true );
			return false;
		}

		/** @var object{token?:string,expiration?:int} */
		$json = json_decode( $response['body'] );
		if ( ! isset( $json->token ) || ! isset( $json->expiration ) ) {
			update_option( 'nc_ga_token_error', true );
			return false;
		}

		update_option( 'nc_ga_token', $json->token );
		update_option( 'nc_ga_token_expiration', $this->normalize_ts( $json->expiration ) );
		delete_option( 'nc_ga_token_error' );

		return $json->token;
	}

	/**
	 * Returns GA4 account properties.
	 *
	 * @return list<array{id:string,name:string}>
	 */
	public function get_ga4_account_properties() {
		$ga_token = get_option( 'nc_ga_token', false );
		if ( empty( $ga_token ) || $this->is_token_expired() ) {
			$ga_token = $this->refresh_access_token();
			if ( false === $ga_token ) {
				return array();
			}
		}

		$result = $this->list_all_ga4_properties( $ga_token );
		if ( is_wp_error( $result ) ) {
			return array();
		}
		return $result;
	}

	/**
	 * Whether analytics need to be updated.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	private function needs_to_be_updated( $post_id ) {

		$last_update = absint( get_post_meta( $post_id, '_nc_last_update', true ) );
		if ( empty( $last_update ) ) {
			return true;
		}

		$updated_last_hour = time() - $last_update < HOUR_IN_SECONDS;
		if ( $updated_last_hour ) {
			return false;
		}

		$publication_date = get_post_time( 'U', true, $post_id );
		if ( ! is_int( $publication_date ) ) {
			return false;
		}

		$published_last_week = time() - $publication_date < WEEK_IN_SECONDS;
		if ( $published_last_week ) {
			return true;
		}

		$updated_today = time() - $last_update <= DAY_IN_SECONDS;
		return ! $updated_today;
	}

	/**
	 * Saves engagement.
	 *
	 * @param int               $post_id    Post ID.
	 * @param array<string,int> $engagement Engagement.
	 *
	 * @return void
	 */
	private function save_engagement( $post_id, $engagement ) {

		$total = 0;
		foreach ( $engagement as $key => $value ) {
			$current_value = absint( get_post_meta( $post_id, $this->engagement_key( $key ), true ) );
			$updated_value = max( $current_value, $value );
			$total        += $updated_value;

			if ( 'comments' !== $key && $updated_value > 0 && $updated_value !== $current_value ) {
				update_post_meta( $post_id, $this->engagement_key( $key ), $value );
			}
		}

		update_post_meta( $post_id, $this->engagement_key( 'total' ), $total );
	}

	/**
	 * Saves page views.
	 *
	 * @param string            $ga4_prop  GA4 prop.
	 * @param int               $post_id   Post ID.
	 * @param array<string,int> $pageviews Pageviews.
	 *
	 * @return void
	 */
	private function save_pageviews( $ga4_prop, $post_id, $pageviews ) {
		if ( empty( $ga4_prop ) ) {
			return;
		}

		if ( empty( $pageviews ) || ! isset( $pageviews['total'] ) || $pageviews['total'] <= 0 ) {
			return;
		}

		update_post_meta( $post_id, $this->pageviews_total_key( $ga4_prop ), absint( $pageviews['total'] ) );

		// Process and update traffic from social networks.
		$current_values = get_post_meta( $post_id, $this->pageviews_social_data_key( $ga4_prop ), true );
		if ( ! is_array( $current_values ) ) {
			$current_values = array();
		}

		$pageviews = wp_parse_args( $pageviews, $current_values );
		unset( $pageviews['total'] );
		$pageviews = array_filter( $pageviews );
		if ( ! empty( $pageviews ) ) {
			update_post_meta( $post_id, $this->pageviews_social_data_key( $ga4_prop ), $pageviews );
		}
	}

	/**
	 * Converts number into human friendly number string.
	 *
	 * @param int $number    Number.
	 * @param int $precision Optional. Number of decimals. Default: `1`.
	 *
	 * @return string
	 */
	private function human_number( $number, $precision = 1 ) {
		$units = array( '', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y' );
		$step  = 1000;
		$i     = 0;
		while ( ( $number / $step ) > 0.9 ) {
			$number = $number / $step;
			++$i;
		}

		if ( floor( $number ) >= 100 ) {
			$number = number_format_i18n( $number, 0 );
		} elseif ( floor( $number ) * 10 !== floor( $number * 10 ) ) {
			$number = number_format_i18n( $number, $precision );
		} else {
			$number = number_format_i18n( $number, 0 );
		}

		return $number . $units[ $i ];
	}

	/**
	 * Gets social counts.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<string,int>
	 */
	private function get_social_count( $post_id ) {

		$previous_twitter_count  = absint( get_post_meta( $post_id, $this->engagement_key( 'twitter' ), true ) );
		$previous_facebook_count = absint( get_post_meta( $post_id, $this->engagement_key( 'facebook' ), true ) );
		$previous_band_count     = absint( get_post_meta( $post_id, $this->engagement_key( 'band' ), true ) );
		$previous_bluesky_count  = absint( get_post_meta( $post_id, $this->engagement_key( 'bluesky' ), true ) );
		$previous_linkedin_count = absint( get_post_meta( $post_id, $this->engagement_key( 'linkedin' ), true ) );
		$previous_mastodon_count = absint( get_post_meta( $post_id, $this->engagement_key( 'mastodon' ), true ) );
		$previous_threads_count  = absint( get_post_meta( $post_id, $this->engagement_key( 'threads' ), true ) );
		$previous_tumblr_count   = absint( get_post_meta( $post_id, $this->engagement_key( 'tumblr' ), true ) );
		$previous_vk_count       = absint( get_post_meta( $post_id, $this->engagement_key( 'vk' ), true ) );

		$count = array(
			'twitter'  => $previous_twitter_count,
			'facebook' => $previous_facebook_count,
			'band'     => $previous_band_count,
			'bluesky'  => $previous_bluesky_count,
			'linkedin' => $previous_linkedin_count,
			'mastodon' => $previous_mastodon_count,
			'threads'  => $previous_threads_count,
			'tumblr'   => $previous_tumblr_count,
			'vk'       => $previous_vk_count,
		);
		if ( ! nelio_content_is_subscribed() ) {
			return $count;
		}

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$path = sprintf(
			'/site/%s/post/%d/analytics',
			nelio_content_get_site_id(),
			$post_id
		);

		$response = wp_remote_request( nelio_content_get_api_url( $path, 'wp' ), $data );
		if ( is_wp_error( $response ) ) {
			return $count;
		}

		$body = json_decode( $response['body'], true );
		if ( ! is_array( $body ) ) {
			return $count;
		}

		if ( isset( $body['twitter'] ) ) {
			$count['twitter'] = absint( $body['twitter'] );
		}

		if ( isset( $body['facebook'] ) ) {
			$count['facebook'] = absint( $body['facebook'] );
		}

		if ( isset( $body['band'] ) ) {
			$count['band'] = absint( $body['band'] );
		}

		if ( isset( $body['bluesky'] ) ) {
			$count['bluesky'] = absint( $body['bluesky'] );
		}

		if ( isset( $body['linkedin'] ) ) {
			$count['linkedin'] = absint( $body['linkedin'] );
		}

		if ( isset( $body['mastodon'] ) ) {
			$count['mastodon'] = absint( $body['mastodon'] );
		}

		if ( isset( $body['threads'] ) ) {
			$count['threads'] = absint( $body['threads'] );
		}

		if ( isset( $body['tumblr'] ) ) {
			$count['tumblr'] = absint( $body['tumblr'] );
		}

		if ( isset( $body['vk'] ) ) {
			$count['vk'] = absint( $body['vk'] );
		}

		return $count;
	}

	/**
	 * Gets pinterest count.
	 *
	 * @param string $url URL.
	 *
	 * @return int
	 */
	private function get_pinterest_count( $url ) {
		if ( empty( $url ) ) {
			return 0;
		}

		// Retrieves data with HTTP GET method for current URL.
		$json_string = wp_remote_request(
			'https://api.pinterest.com/v1/urls/count.json?url=' . rawurlencode( $url ),
			array(
				'method'    => 'GET',
				'sslverify' => false, // Disable checking SSL certificates.
			)
		);

		if ( is_wp_error( $json_string ) ) {
			return 0;
		}

		// Retrives only body from previous HTTP GET request.
		$json_string = wp_remote_retrieve_body( $json_string );
		$json_string = preg_replace( '/^receiveCount\((.*)\)$/', "\\1", $json_string );
		$json_string = is_string( $json_string ) ? $json_string : '';

		// Convert body data to JSON format.
		$json = json_decode( $json_string, true );

		// Get count of Pinterest Shares for requested URL.
		if ( ! is_array( $json ) || ! isset( $json['count'] ) ) {
			return 0;
		}

		$value = absint( $json['count'] );
		return $value;
	}

	/**
	 * Gets reddit count.
	 *
	 * @param string $url URL.
	 *
	 * @return int
	 */
	private function get_reddit_count( $url ) {
		if ( empty( $url ) ) {
			return 0;
		}

		// Retrieves data with HTTP GET method for current URL.
		$json_string = wp_remote_request(
			'https://www.reddit.com/api/info.json?url=' . rawurlencode( $url ),
			array(
				'method'    => 'GET',
				'sslverify' => false, // Disable checking SSL certificates.
			)
		);

		if ( is_wp_error( $json_string ) ) {
			return 0;
		}

		// Retrives only body from previous HTTP GET request.
		$json_string = wp_remote_retrieve_body( $json_string );

		// Convert body data to JSON format.
		$json = json_decode( $json_string, true );

		// Get count of Reddit Shares for requested URL.
		if (
			! is_array( $json ) ||
				! isset( $json['data'] ) ||
				! is_array( $json['data'] ) ||
				! isset( $json['data']['children'] ) ||
				! is_array( $json['data']['children'] )
		) {
			return 0;
		}

		$value = 0;
		$items = $json['data']['children'];
		foreach ( $items as $item ) {

			if (
				! is_array( $item ) ||
				! isset( $item['data'] ) ||
				! is_array( $item['data'] ) ||
				! isset( $item['data']['score'] )
			) {
				continue;
			}

			$value += absint( $item['data']['score'] );

		}

		return $value;
	}

	/**
	 * Retrieves GA Data.
	 *
	 * @param string $ga4_prop   GA4 Prop.
	 * @param int    $post_id    Post ID.
	 * @param string $url        URL.
	 * @param string $start_date Start date.
	 *
	 * @return array<string,int>|WP_Error
	 */
	private function get_ga_data( $ga4_prop, $post_id, $url, $start_date ) {

		// Google Analytics 4 was released on 2015-08-14, so we cannot query data before that date.
		// See https://support.google.com/analytics/answer/6367342?hl=en.
		if ( $start_date <= '2015-08-13' ) {
			$start_date = '2015-08-14';
		}

		$total_pageviews = absint( get_post_meta( $post_id, $this->pageviews_total_key( $ga4_prop ), true ) );

		$path = preg_replace( '/^https?:\/\/[^\/]+/', '', $url );
		if ( ! $path ) {
			$path = '/';
		}

		/**
		 * Modifies the list of paths in which we can find a given post.
		 *
		 * @param array $paths   an array with one or more paths in which the post can be found.
		 * @param int   $post_id the ID of the post.
		 *
		 * @since 1.3.0
		 */
		$paths = apply_filters( 'nelio_content_analytics_post_paths', array( $path ), $post_id );

		$ga_token = get_option( 'nc_ga_token', false );
		if ( empty( $ga_token ) || $this->is_token_expired() ) {
			$ga_token = $this->refresh_access_token();
			if ( false === $ga_token ) {
				return array();
			}
		}

		$args = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => false,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $ga_token,
			),
			'body'      => sprintf(
				$this->remove_spaces(
					'{
						"dateRanges": [ {
							"startDate": %1$s,
							"endDate": "today"
						} ],
						"dimensions": [ {
							"name": "pagePathPlusQueryString"
						} ],
						"metrics": [ {
							"name": "screenPageViews"
						} ],
						"dimensionFilter": {
							"filter": {
								"fieldName": "pagePathPlusQueryString",
								"inListFilter": {
									"values": %2$s,
									"caseSensitive": false
								}
							}
						}
					}'
				),
				wp_json_encode( $start_date ),
				wp_json_encode( $paths )
			),
		);

		$json_string = wp_remote_post( "https://analyticsdata.googleapis.com/v1beta/properties/{$ga4_prop}:runReport", $args );
		if ( is_wp_error( $json_string ) ) {
			return new WP_Error( 'nelio-content-ga-error', _x( 'Error while retrieving data from Google Analytics.', 'user', 'nelio-content' ) );
		}

		$code = wp_remote_retrieve_response_code( $json_string );
		if ( 429 === $code ) {
			return array(
				'total' => $total_pageviews,
			);

		}

		$json = json_decode( $json_string['body'], true );
		if ( ! is_array( $json ) || isset( $json['error'] ) ) {
			return new WP_Error( 'nelio-content-ga-error', _x( 'Error while retrieving data from Google Analytics. Please refresh token.', 'user', 'nelio-content' ) . wp_json_encode( $json ) );
		}

		$rows            = isset( $json['rows'] ) && is_array( $json['rows'] ) ? $json['rows'] : array();
		$total_pageviews = 0;
		foreach ( $rows as $row ) {
			$values = is_array( $row ) && ! empty( $row['metricValues'] ) && is_array( $row['metricValues'] ) ? $row['metricValues'] : array();
			foreach ( $values as $value ) {
				$total_pageviews += is_array( $value ) && isset( $value['value'] ) ? absint( $value['value'] ) : 0;
			}
		}

		$result = array(
			'total' => $total_pageviews,
		);

		$args = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => false,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $ga_token,
			),
			'body'      => sprintf(
				$this->remove_spaces(
					'{
						"dateRanges": [ {
							"startDate": %1$s,
							"endDate": "today"
						} ],
						"dimensions": [
							{ "name": "sessionSource" },
							{ "name": "sessionDefaultChannelGroup" }
						],
						"metrics": [ {
							"name": "screenPageViews"
						} ],
						"dimensionFilter": {
							"andGroup": {
								"expressions": [
									{
										"filter": {
											"fieldName": "pagePath",
											"inListFilter": {
												"values": %2$s,
												"caseSensitive": false
											}
										}
									},
									{
										"filter": {
											"fieldName": "sessionDefaultChannelGroup",
											"stringFilter": {
												"matchType": "EXACT",
												"value": %3$s
											}
										}
									}
								]
							}
						},
						"orderBys": [
							{
								"metric": {
									"metricName": "screenPageViews"
								},
								"desc": true
							}
						],
						"limit": "1000"
					}'
				),
				wp_json_encode( $start_date ),
				wp_json_encode( $paths ),
				wp_json_encode( 'Organic Social' )
			),
		);

		$json_string = wp_remote_post( "https://analyticsdata.googleapis.com/v1beta/properties/{$ga4_prop}:runReport", $args );
		if ( is_wp_error( $json_string ) ) {
			return new WP_Error( 'nelio-content-ga-error', _x( 'Error while retrieving social-origin data from Google Analytics.', 'user', 'nelio-content' ) );
		}

		$code = wp_remote_retrieve_response_code( $json_string );
		if ( 429 === $code ) {
			return $result;
		}

		$json = json_decode( $json_string['body'], true );
		if ( ! is_array( $json ) || isset( $json['error'] ) ) {
			return new WP_Error( 'nelio-content-ga-error', _x( 'Error while retrieving social-origin data from Google Analytics.', 'user', 'nelio-content' ) . wp_json_encode( $json ) );
		}

		$rows = isset( $json['rows'] ) && is_array( $json['rows'] ) ? $json['rows'] : array();
		foreach ( $rows as $row ) {
			$dimensions = is_array( $row ) && ! empty( $row['dimensionValues'] ) && is_array( $row['dimensionValues'] ) ? $row['dimensionValues'] : array();
			$source     = is_array( $dimensions[0] ) && isset( $dimensions[0]['value'] ) && is_string( $dimensions[0]['value'] ) ? $dimensions[0]['value'] : '';
			$network    = $this->get_network_from_source( $source );
			if ( empty( $network ) ) {
				continue;
			}

			$values = is_array( $row ) && ! empty( $row['metricValues'] ) && is_array( $row['metricValues'] ) ? $row['metricValues'] : array();
			$views  = 0;
			foreach ( $values as $value ) {
				$views += is_array( $value ) && isset( $value['value'] ) ? absint( $value['value'] ) : 0;
			}

			if ( isset( $result[ $network ] ) ) {
				$result[ $network ] += $views;
			} else {
				$result[ $network ] = $views;
			}
		}

		return $result;
	}

	/**
	 * Returns network from source.
	 *
	 * @param string $source Source.
	 *
	 * @return string
	 */
	private function get_network_from_source( $source ) {

		$source = strtolower( $source );

		if ( 't.co' === $source || 'twitter.com' === $source || 'x.com' === $source ) {
			return 'twitter';
		}

		if ( false !== strpos( $source, 'facebook' ) || false !== strpos( $source, 'fb' ) ) {
			return 'facebook';
		}

		if ( false !== strpos( $source, 'band' ) ) {
			return 'band';
		}

		if ( false !== strpos( $source, 'bluesky' ) || false !== strpos( $source, 'bsky' ) ) {
			return 'bluesky';
		}

		if ( false !== strpos( $source, 'linkedin' ) || false !== strpos( $source, 'lnkd' ) ) {
			return 'linkedin';
		}

		if ( false !== strpos( $source, 'mastodon' ) || false !== strpos( $source, 'mstdn' ) ) {
			return 'mastodon';
		}

		if ( false !== strpos( $source, 'medium.com' ) ) {
			return 'medium';
		}

		if ( false !== strpos( $source, 'pinterest' ) || false !== strpos( $source, 'pinboard' ) ) {
			return 'pinterest';
		}

		if ( false !== strpos( $source, 'instagram' ) || false !== strpos( $source, 'instagr.am' ) ) {
			return 'instagram';
		}

		if ( false !== strpos( $source, 'reddit' ) ) {
			return 'reddit';
		}

		if ( false !== strpos( $source, 'telegram' ) || false !== strpos( $source, 't.me' ) ) {
			return 'telegram';
		}

		if ( false !== strpos( $source, 'threads' ) ) {
			return 'threads';
		}

		if ( false !== strpos( $source, 'discord' ) || false !== strpos( $source, 'dis.gd' ) ) {
			return 'discord';
		}

		if ( false !== strpos( $source, 'slack' ) || false !== strpos( $source, 'slack-redir' ) ) {
			return 'slack';
		}

		if ( false !== strpos( $source, 'tiktok' ) ) {
			return 'tiktok';
		}

		if ( false !== strpos( $source, 'tumblr' ) || false !== strpos( $source, 'tmblr' ) ) {
			return 'tumblr';
		}

		if ( false !== strpos( $source, 'vk' ) || false !== strpos( $source, 'vkontakte' ) ) {
			return 'vk';
		}

		return '';
	}

	/**
	 * List all GA4 properties (id + name) accessible by the given access token.
	 *
	 * @param string $access_token OAuth2 access token with Analytics Admin scope.
	 *
	 * @return list<array{id:string,name:string}>|WP_Error
	 */
	private function list_all_ga4_properties( $access_token ) {
		$accounts = $this->list_ga4_accounts( $access_token );
		if ( is_wp_error( $accounts ) ) {
			return $accounts;
		}

		$all_properties = array();

		foreach ( $accounts as $account ) {
			// The $account['name'] looks like "accounts/123456789".
			if ( ! is_array( $account ) || empty( $account['name'] ) || ! is_string( $account['name'] ) ) {
				continue;
			}

			$props = $this->list_ga4_properties_for_account( $account['name'], $access_token );
			if ( is_wp_error( $props ) ) {
				return $props;
			}

			$all_properties = array_merge( $all_properties, $props );
		}

		// Flatten + map to the ReturningProperty[] shape you had in TS.
		$mapped = array();
		foreach ( $all_properties as $property ) {
			if ( ! is_array( $property ) ) {
				continue;
			}

			if ( empty( $property['name'] ) || empty( $property['displayName'] ) ) {
				continue;
			}

			if ( ! is_string( $property['name'] ) || ! is_string( $property['displayName'] ) ) {
				continue;
			}

			// Transform "properties/123456789" into "123456789".
			$parts = explode( '/', $property['name'] );
			$id    = isset( $parts[1] ) ? $parts[1] : '';

			if ( $id ) {
				$mapped[] = array(
					'id'   => $id,
					'name' => $property['displayName'],
				);
			}
		}

		return $mapped;
	}//end list_all_ga4_properties()

	/**
	 * Fetch GA4 Accounts via Analytics Admin API.
	 *
	 * @param string $access_token OAuth2 access token.
	 *
	 * @return array<mixed>|WP_Error Array of accounts (each with 'name', ...), or WP_Error.
	 */
	private function list_ga4_accounts( $access_token ) {
		$url  = 'https://analyticsadmin.googleapis.com/v1beta/accounts';
		$args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		);
		$resp = wp_remote_get( $url, $args );

		if ( is_wp_error( $resp ) ) {
			return new WP_Error( 'data-not-found', 'Error listing accounts: request failed', array( 'status' => 404 ) );
		}

		$code = wp_remote_retrieve_response_code( $resp );
		if ( $code < 200 || $code >= 300 ) {
			$reason = wp_remote_retrieve_response_message( $resp );
			return new WP_Error( 'data-not-found', sprintf( 'Error listing accounts: %s', $reason ), array( 'status' => 404 ) );
		}

		$body = wp_remote_retrieve_body( $resp );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'data-not-found', 'Error listing accounts: invalid JSON response', array( 'status' => 404 ) );
		}

		return isset( $data['accounts'] ) && is_array( $data['accounts'] ) ? $data['accounts'] : array();
	}//end list_ga4_accounts()

	/**
	 * Fetch GA4 Properties for a given account (handles pagination).
	 *
	 * @param string $account_name The Admin API account resource name, e.g. "accounts/123456789".
	 * @param string $access_token OAuth2 access token.
	 *
	 * @return array<mixed>|WP_Error Array of property objects (Admin API shape), or WP_Error.
	 */
	private function list_ga4_properties_for_account( $account_name, $access_token ) {
		$base_url   = 'https://analyticsadmin.googleapis.com/v1beta/properties';
		$query_args = array(
			'filter' => 'parent:' . $account_name, // parent:accounts/123...
		);

		$all_properties = array();
		$page_token     = null;

		do {
			$args_for_request = $query_args;
			if ( ! empty( $page_token ) ) {
				$args_for_request['pageToken'] = $page_token;
			}

			$url = add_query_arg( $args_for_request, $base_url );

			$resp = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
					),
				)
			);

			if ( is_wp_error( $resp ) ) {
				return new WP_Error( 'data-not-found', 'Failed to fetch GA4 properties: request failed', array( 'status' => 404 ) );
			}

			$code = wp_remote_retrieve_response_code( $resp );
			if ( $code < 200 || $code >= 300 ) {
				$body = wp_remote_retrieve_body( $resp );
				return new WP_Error(
					'data-not-found',
					sprintf( 'Failed to fetch GA4 properties: %d %s', $code, $body ),
					array( 'status' => 404 )
				);
			}

			$body = wp_remote_retrieve_body( $resp );
			$data = json_decode( $body, true );

			if ( ! is_array( $data ) ) {
				return new WP_Error( 'data-not-found', 'Failed to fetch GA4 properties: invalid JSON response', array( 'status' => 404 ) );
			}

			if ( ! empty( $data['properties'] ) && is_array( $data['properties'] ) ) {
				$all_properties = array_merge( $all_properties, $data['properties'] );
			}

			$page_token = isset( $data['nextPageToken'] ) ? $data['nextPageToken'] : null;

		} while ( ! empty( $page_token ) );

		return $all_properties;
	}//end list_ga4_properties_for_account()

	/**
	 * Whether the token has expired.
	 *
	 * @return bool
	 */
	private function is_token_expired() {
		$expiration = get_option( 'nc_ga_token_expiration', 0 );
		return $expiration < ( time() + ( MINUTE_IN_SECONDS * 10 ) );
	}

	/**
	 * Returns engagement key.
	 *
	 * @param string $metric Metric.
	 *
	 * @return '_nc_engagement_{metric}'
	 */
	private function engagement_key( $metric ) {
		/** @var '_nc_engagement_{metric}' */
		return "_nc_engagement_{$metric}";
	}

	/**
	 * Returns engagement key.
	 *
	 * @param string $ga4_prop GA4 prop.
	 *
	 * @return '_nc_pageviews_social_data_{ga4_prop}'
	 */
	private function pageviews_social_data_key( $ga4_prop ) {
		/** @var '_nc_pageviews_social_data_{ga4_prop}' */
		return "_nc_pageviews_social_data_{$ga4_prop}";
	}

	/**
	 * Returns engagement key.
	 *
	 * @param string $ga4_prop GA4 prop.
	 *
	 * @return '_nc_pageviews_total_{ga4_prop}'
	 */
	private function pageviews_total_key( $ga4_prop ) {
		/** @var '_nc_pageviews_total_{ga4_prop}' */
		return "_nc_pageviews_total_{$ga4_prop}";
	}

	/**
	 * Removes all spaces from the given text.
	 *
	 * @param string $text Text.
	 *
	 * @return string
	 */
	private function remove_spaces( $text ) {
		$text = preg_replace( '/\s/', '', $text );
		return is_string( $text ) ? $text : '';
	}
}

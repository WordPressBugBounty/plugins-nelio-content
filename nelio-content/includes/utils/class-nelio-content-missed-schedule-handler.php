<?php
/**
 * This file contains a class that handles missed schedule posts.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      2.5.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements a missed schedule handler.
 */
class Nelio_Content_Missed_Schedule_Handler {

	const ACTION      = 'nelio_content_missed_schedule_handler';
	const NONCE       = 'nelio_content_missed_schedule_handler_nonce';
	const BATCH_LIMIT = 20;
	const FREQUENCY   = 900;
	const OPTION_NAME = 'nc_missed_schedule_handler_last_run';

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Missed_Schedule_Handler|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Missed_Schedule_Handler
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

		add_action( 'init', array( $this, 'add_hooks_if_handler_is_enabled' ) );
	}

	/**
	 * Hooks into WordPress if handler is enabled.
	 *
	 * @return void
	 */
	public function add_hooks_if_handler_is_enabled() {

		$settings = Nelio_Content_Settings::instance();
		if ( ! $settings->get( 'use_missed_schedule_handler' ) ) {
			return;
		}

		add_action( 'send_headers', array( $this, 'send_headers' ) );
		add_action( 'shutdown', array( $this, 'maybe_send_handler_run_request' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'maybe_handle_missed_posts' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'maybe_handle_missed_posts' ) );
	}

	/**
	 * Prevents caching of requests including the AJAX script.
	 *
	 * Includes the no-caching headers if the response will include the
	 * AJAX fallback script. This is to prevent excess calls to the
	 * admin-ajax.php action.
	 *
	 * @return void
	 */
	public function send_headers() {
		if ( ! $this->can_handler_be_run() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		nocache_headers();
	}

	/**
	 * Callback to enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( ! $this->can_handler_be_run() ) {
			return;
		}

		// Shutdown request is not needed.
		remove_action( 'shutdown', array( $this, 'maybe_send_handler_run_request' ) );

		// Null script for inline script to come afterward.
		wp_register_script(
			self::ACTION,
			'',
			array(),
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			null,
			true
		);

		$request = array(
			'url'  => add_query_arg( 'action', self::ACTION, admin_url( 'admin-ajax.php' ) ),
			'args' => array(
				'method' => 'POST',
				'body'   => self::NONCE . '=' . wp_create_nonce( self::ACTION ),
			),
		);

		$script = '
		(function( request ){
			if ( ! window.fetch ) {
				return;
			}
			request.args.body = new URLSearchParams( request.args.body );
			fetch( request.url, request.args );
		}( ' . wp_json_encode( $request ) . ' ));
		';

		wp_add_inline_script(
			self::ACTION,
			$script
		);

		wp_enqueue_script( self::ACTION );
	}

	/**
	 * Callback to send handler run request.
	 *
	 * @return void
	 */
	public function maybe_send_handler_run_request() {
		if ( ! $this->can_handler_be_run() ) {
			return;
		}

		// Do request.
		$request = array(
			'url'  => add_query_arg( 'action', self::ACTION, admin_url( 'admin-ajax.php' ) ),
			'args' => array(
				'timeout'   => 0.01,
				'blocking'  => false,
				/** This filter is documented in wp-includes/class-wp-http-streams.php */
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				'sslverify' => ! empty( apply_filters( 'https_local_ssl_verify', false ) ),
				'body'      => array(
					self::NONCE => wp_create_nonce( self::ACTION ),
				),
			),
		);

		wp_remote_post( $request['url'], $request['args'] );
	}

	/**
	 * Callback to handle missed posts.
	 *
	 * @return void
	 */
	public function maybe_handle_missed_posts() {
		$nonce = self::NONCE;
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce ] ?? '' ) ), self::ACTION ) ) {
			wp_send_json_error();
		}

		if ( ! $this->can_handler_be_run() ) {
			wp_send_json_success();
		}

		$this->publish_missed_posts();
		wp_send_json_success();
	}

	/**
	 * Whether helper can run.
	 *
	 * @return bool
	 */
	private function can_handler_be_run() {
		$last_run = absint( get_option( self::OPTION_NAME, 0 ) );
		return $last_run < ( time() - $this->get_handler_running_frequency() );
	}

	/**
	 * Gets handler running frequency.
	 *
	 * @return int
	 */
	private function get_handler_running_frequency() {
		/**
		 * Filters the running frequency of the missed schedule handler.
		 *
		 * Controls the frequency in seconds of each execution of the missed
		 * schedule handler.
		 *
		 * @param int  $frequency  Running frequency in seconds.
		 *
		 * @since 2.5.1
		 */
		return (int) apply_filters( 'nelio_content_missed_schedule_handler_run_frequency', self::FREQUENCY );
	}

	/**
	 * Publishes missed posts.
	 *
	 * @return void
	 */
	private function publish_missed_posts() {
		/** @var wpdb $wpdb */
		global $wpdb;

		update_option( self::OPTION_NAME, time() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$scheduled_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM %i WHERE post_date <= %s AND post_status = 'future' LIMIT %d",
				$wpdb->posts,
				current_time( 'mysql', 0 ),
				self::BATCH_LIMIT
			)
		);

		$scheduled_ids = array_map( fn( $id ) => absint( $id ), $scheduled_ids );
		if ( ! count( $scheduled_ids ) ) {
			return;
		}

		if ( count( $scheduled_ids ) === self::BATCH_LIMIT ) {
			update_option( self::OPTION_NAME, 0 );
		}

		array_walk( $scheduled_ids, 'wp_publish_post' );
	}
}

<?php
/**
 * This file contains REST endpoints to work with internal events.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      2.5.1
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Internal_Events_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.5.1
	 * @var    Nelio_Content_Internal_Events_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Internal_Events_REST_Controller
	 *
	 * @since  2.5.1
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
	 * @since  2.5.1
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
			'/internal-events',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_events' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);
	}

	/**
	 * Gets the ical body of the specified URL.
	 *
	 * @return WP_REST_Response
	 */
	public function get_events() {

		/**
		 * Filters the internal events to show in the calendar.
		 *
		 * @param list<TInternal_Event> $events The list of internal events to show in the calendar.
		 *                                      Each event has the following attributes:
		 *
		 * @since 2.5.1
		 */
		$events = apply_filters( 'nelio_content_internal_events', array() );

		return new WP_REST_Response( $events, 200 );
	}
}

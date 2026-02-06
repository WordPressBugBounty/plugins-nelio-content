<?php
/**
 * This file contains REST endpoints to work with external calendars.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.1.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_External_Calendar_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.1.0
	 * @var    Nelio_Content_External_Calendar_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_External_Calendar_REST_Controller
	 *
	 * @since  2.1.0
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
	 * @since  2.1.0
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
			'/external-calendars',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_external_calendars' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_external_calendar' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_external_calendar' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'url'  => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
						'name' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => flow( 'trim', 'nelio_content_is_not_empty' ),
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_external_calendar' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/external-calendar/events',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_events' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
			)
		);
	}

	/**
	 * Returns the external calendar list.
	 *
	 * @return WP_REST_Response
	 */
	public function get_external_calendars() {
		return new WP_REST_Response( get_option( 'nc_external_calendars', array() ), 200 );
	}

	/**
	 * Creates a new calendar.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_external_calendar( $request ) {

		/** @var string */
		$url      = $request['url'];
		$response = wp_remote_request( $url, array( 'method' => 'GET' ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing calendar.', 'text', 'nelio-content' )
			);
		}

		if ( ! $this->is_ics_content( $response ) ) {
			return new WP_Error(
				'no-ics-url',
				_x( 'Provided URL doesn’t contain an ICS calendar', 'text', 'nelio-content' )
			);
		}

		$calendar = array(
			'url'  => $url,
			'name' => $this->get_name( $url, $response ),
		);

		$this->save_external_calendar( $calendar );
		return new WP_REST_Response( $calendar, 200 );
	}

	/**
	 * Renames the given calendar.
	 *
	 * @param WP_REST_Request<array{url:string,name:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_external_calendar( $request ) {

		/** @var string */
		$url = $request['url'];
		/** @var string */
		$name = trim( $request['name'] ?? '' );

		$calendar = $this->get_external_calendar( $url );
		if ( empty( $calendar ) ) {
			return new WP_Error(
				'calendar-not-found',
				_x( 'Calendar not found.', 'text', 'nelio-content' )
			);
		}

		$calendar['name'] = $name;
		$this->save_external_calendar( $calendar );
		return new WP_REST_Response( $calendar, 200 );
	}

	/**
	 * Removes the given calendar.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function remove_external_calendar( $request ) {

		/** @var string */
		$url      = $request['url'];
		$calendar = $this->get_external_calendar( $url );
		if ( empty( $calendar ) ) {
			return new WP_REST_Response( true, 200 );
		}

		$calendars = get_option( 'nc_external_calendars', array() );
		/** @disregard P1006 — $calendars is an array */
		$calendars = array_filter(
			$calendars,
			function ( $calendar ) use ( $url ) {
				return $calendar['url'] !== $url;
			}
		);
		update_option( 'nc_external_calendars', array_values( $calendars ) );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Gets the ical body of the specified URL.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_events( $request ) {
		/** @var string */
		$url      = $request['url'];
		$response = wp_remote_request( $url, array( 'method' => 'GET' ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing calendar.', 'text', 'nelio-content' )
			);
		}

		if ( ! $this->is_ics_content( $response ) ) {
			return new WP_Error(
				'no-ics-url',
				_x( 'Provided URL doesn’t contain an ICS calendar', 'text', 'nelio-content' )
			);
		}

		return new WP_REST_Response( $response['body'], 200 );
	}

	/**
	 * Gets external calendar.
	 *
	 * @param string $url URL.
	 *
	 * @return TExternal_Calendar|false
	 */
	private function get_external_calendar( $url ) {
		$calendars = get_option( 'nc_external_calendars', array() );
		/** @disregard P1006 — $calendars is an array */
		foreach ( $calendars as $calendar ) {
			if ( $calendar['url'] === $url ) {
				return $calendar;
			}
		}
		return false;
	}

	/**
	 * Saves external calendar.
	 *
	 * @param TExternal_Calendar $calendar Calendar.
	 *
	 * @return void
	 */
	private function save_external_calendar( $calendar ) {
		$calendars = get_option( 'nc_external_calendars', array() );
		if ( ! $this->get_external_calendar( $calendar['url'] ) ) {
			/** @disregard P1006 — $calendars is an array */
			array_push( $calendars, $calendar );
		}

		foreach ( $calendars as $key => $existing_cal ) {
			if ( $existing_cal['url'] === $calendar['url'] ) {
				$calendars[ $key ] = $calendar;
			}
		}

		update_option( 'nc_external_calendars', $calendars );
	}

	/**
	 * Whether the response corresponds to an ICS calendar.
	 *
	 * @param array{body:string} $response Response.
	 *
	 * @return bool
	 */
	private function is_ics_content( $response ) {
		return 0 === strpos( $response['body'], 'BEGIN:VCALENDAR' );
	}

	/**
	 * Returns calendar’s name.
	 *
	 * @param string             $url      URL.
	 * @param array{body:string} $response Response.
	 *
	 * @return string
	 */
	private function get_name( $url, $response ) {
		$count = 30;
		$lines = array_map( 'trim', explode( "\n", $response['body'], $count + 1 ) );

		$count = min( $count, count( $lines ) );
		foreach ( $lines as $line ) {
			if ( 0 === strpos( $line, 'X-WR-CALNAME:' ) ) {
				return str_replace( 'X-WR-CALNAME:', '', $line );
			}
		}

		$url = preg_replace( '/^https?:\/\//', '', $url );
		$url = is_string( $url ) ? $url : '';
		$url = preg_replace( '/\/.*$/', '', $url );
		$url = is_string( $url ) ? $url : '';
		return $url;
	}
}

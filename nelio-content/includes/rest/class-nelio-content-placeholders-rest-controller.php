<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing Nelio Content custom fields and placeholders.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      2.5.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Placeholders_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.5.0
	 * @var    Nelio_Content_Placeholders_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Placeholders_REST_Controller
	 *
	 * @since  2.5.0
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
			'/custom-fields',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_fields' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/custom-placeholders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_custom_placeholders' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);
	}

	/**
	 * Retrieves information about the custom fields supported.
	 *
	 * @return WP_REST_Response
	 */
	public function get_custom_fields() {
		$post_helper   = Nelio_Content_Post_Helper::instance();
		$custom_fields = $post_helper->get_supported_custom_fields_in_templates();
		return new WP_REST_Response( $custom_fields, 200 );
	}

	/**
	 * Retrieves information about the custom placeholders supported.
	 *
	 * @return WP_REST_Response
	 */
	public function get_custom_placeholders() {
		$post_helper         = Nelio_Content_Post_Helper::instance();
		$custom_placeholders = $post_helper->get_supported_custom_placeholders_in_templates();
		return new WP_REST_Response( $custom_placeholders, 200 );
	}
}

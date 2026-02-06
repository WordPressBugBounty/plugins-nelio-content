<?php
/**
 * This file contains the class that defines REST API endpoints for
 * installing plugins in the background.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Plugin_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  3.6.0
	 * @var    Nelio_Content_Plugin_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Plugin_REST_Controller
	 *
	 * @since  3.6.0
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
	 * @since  3.6.0
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
			'/premium/install',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install_premium' ),
					'permission_callback' => array( $this, 'can_install_plugin' ),
				),
			)
		);
	}

	/**
	 * Callback to check if the current user can install and activate plugins.
	 *
	 * @return bool
	 */
	public function can_install_plugin() {
		return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
	}

	/**
	 * Callback to retrieve information about the site.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function install_premium() {
		nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
		nelio_content_require_wp_file( '/wp-admin/includes/admin.php' );
		nelio_content_require_wp_file( '/wp-admin/includes/plugin-install.php' );
		nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
		nelio_content_require_wp_file( '/wp-admin/includes/class-wp-upgrader.php' );
		nelio_content_require_wp_file( '/wp-admin/includes/class-plugin-upgrader.php' );

		$premium_slug = 'nelio-content-premium/nelio-content-premium.php';
		if ( is_plugin_active( $premium_slug ) ) {
			return new WP_REST_Response( 'OK', 200 );
		}

		$installed_plugins = get_plugins();
		if ( array_key_exists( $premium_slug, $installed_plugins ) ) {
			$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $premium_slug, '', false, false );
			if ( ! is_wp_error( $activated ) ) {
				return new WP_REST_Response( 'OK', 200 );
			} else {
				return $activated;
			}
		}

		$body = wp_json_encode(
			array(
				'sites'   => array( nelio_content_get_site_id() ),
				'version' => nelio_content()->plugin_version,
			)
		);
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

		$url      = nelio_content_get_api_url( '/premium/update', 'wp' );
		$response = wp_remote_request( $url, $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if (
			200 !== wp_remote_retrieve_response_code( $response )
			|| empty( wp_remote_retrieve_body( $response ) )
		) {
			return new WP_Error(
				'internal-error',
				_x( 'You do not have permission to install Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $data ) || ! is_object( $data ) || empty( $data->package ) || ! is_string( $data->package ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'You do not have permission to install Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $data->package );

		if ( ! $result || is_wp_error( $result ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error installing Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}

		$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $premium_slug, '', false, true );
		if ( is_wp_error( $activated ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error activating Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}

		return new WP_REST_Response( 'OK', 200 );
	}
}

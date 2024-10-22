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
	 * The single instance of this class.
	 *
	 * @since  3.6.0
	 * @access protected
	 * @var    Nelio_Content_Plugin_REST_Controller
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content_Plugin_REST_Controller the single instance of this class.
	 *
	 * @since  3.6.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  3.6.0
	 * @access public
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/premium/install',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'install_premium' ),
					'permission_callback' => 'nc_can_current_user_manage_plugin',
				),
			)
		);

	}//end register_routes()

	/**
	 * Retrieves information about the site.
	 *
	 * @return WP_REST_Response The response
	 */
	public function install_premium() {

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'You do not have permission to perform this action.', 'text', 'nelio-content' )
			);
		}//end if

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/admin.php';
		include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		include_once ABSPATH . '/wp-admin/includes/plugin.php';
		include_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';

		$premium_slug = 'nelio-content-premium/nelio-content-premium.php';
		if ( is_plugin_active( $premium_slug ) ) {
			return new WP_REST_Response( 'OK', 200 );
		}//end if

		$installed_plugins = get_plugins();
		if ( array_key_exists( $premium_slug, $installed_plugins ) || in_array( $premium_slug, $installed_plugins, true ) ) {
			$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $premium_slug, false, false, false );
			if ( ! is_wp_error( $activated ) ) {
				return new WP_REST_Response( 'OK', 200 );
			} else {
				return $activated;
			}//end if
		}//end if

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nelio_content_request_timeout', 30 ),
			'sslverify' => ! nc_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => wp_json_encode(
				array(
					'sites'   => array( nc_get_site_id() ),
					'version' => nelio_content()->plugin_version,
				)
			),
		);

		$url      = nc_get_api_url( '/premium/update', 'wp' );
		$response = wp_remote_request( $url, $data );

		if (
			is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response )
			|| empty( wp_remote_retrieve_body( $response ) )
		) {
			return false;
		}//end if

		$data = (object) json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $data->package ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'You do not have permission to install Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}//end if

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $data->package );

		if ( ! $result || is_wp_error( $result ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error installing Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}//end if

		$activated = activate_plugin( trailingslashit( WP_PLUGIN_DIR ) . $premium_slug, false, false, true );
		if ( is_wp_error( $activated ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error activating Nelio Content Premium.', 'text', 'nelio-content' )
			);
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end install_premium()

}//end class

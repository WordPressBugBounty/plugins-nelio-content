<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing task presets.
 *
 * @since 3.2.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_Content\Zod\Zod as Z;

use function Nelio_Content\Helpers\find;

class Nelio_Content_Task_Presets_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since 3.2.0
	 * @var   Nelio_Content_Task_Presets_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Task_Presets_REST_Controller
	 *
	 * @since 3.2.0
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;
		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 *
	 * @since 3.2.0
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
			'/task-presets',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_task_presets' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'presets' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_task_presets' ),
							'sanitize_callback' => array( $this, 'sanitize_task_presets' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to validate tasks presets.
	 *
	 * @param array<mixed>|string $presets Presets.
	 *
	 * @return true|WP_Error
	 */
	public function validate_task_presets( $presets ) {
		$schema = Z::array( Nelio_Content_Task_Preset::schema() );
		$result = $schema->safe_parse( $presets );
		return $result['success'] ? true : new WP_Error( 'parse-error', $result['error'] );
	}

	/**
	 * Callback to sanitize tasks presets.
	 *
	 * @param array<array<mixed>|string> $presets Presets.
	 *
	 * @return list<Nelio_Content_Task_Preset>
	 */
	public function sanitize_task_presets( $presets ) {
		/** @var list<Nelio_Content_Task_Preset> */
		return array_map( fn( $p ) => Nelio_Content_Task_Preset::parse( $p ), $presets );
	}

	/**
	 * Callback to update task presets.
	 *
	 * @param WP_REST_Request<array{presets:list<Nelio_Content_Task_Preset>}> $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_task_presets( $request ) {
		/** @var list<Nelio_Content_Task_Preset> $presets */
		$presets = $request->get_param( 'presets' );
		$presets = array_map( fn( $p ) => $p->save(), $presets );

		$error = find( $presets, 'is_wp_error' );
		if ( is_wp_error( $error ) ) {
			return $error;
		} else {
			/** @var list<Nelio_Content_Task_Preset> $presets */
			$presets = $presets;
		}

		$old = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => 'nc_task_preset',
				'post_status' => 'draft',
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'exclude'     => array_map( fn( $p ) => $p->ID, $presets ),
				'numberposts' => 50,
			)
		);
		array_map( 'wp_delete_post', $old );

		return new WP_REST_Response( array_map( fn( $p ) => $p->json(), $presets ), 200 );
	}
}

<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing task statuses.
 *
 * @since 4.0.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_Content\Zod\Schema;
use Nelio_Content\Zod\Zod as Z;

use function Nelio_Content\Helpers\key_by;

class Nelio_Content_Statuses_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since 4.0.0
	 * @var   Nelio_Content_Statuses_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Statuses_REST_Controller
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'init', array( $this, 'register_post_statuses' ), 9999 );
	}

	/**
	 * Registers post statuses.
	 *
	 * @return void
	 */
	public function register_post_statuses() {
		$statuses = nelio_content_get_statuses();
		foreach ( $statuses as $status ) {
			if ( ! empty( $status['core'] ) ) {
				continue;
			}

			if ( get_post_status_object( $status['slug'] ) ) {
				continue;
			}

			register_post_status(
				$status['slug'],
				array(
					'label'       => $status['name'],
					'protected'   => true,
					'label_count' => array(
						'singular' => sprintf(
							'%1$s <span class="count">(%2$s)</span>',
							$status['name'],
							'%s'
						),
						'plural'   => sprintf(
							'%1$s <span class="count">(%2$s)</span>',
							$status['name'],
							'%s'
						),
						'context'  => null,
						'domain'   => 'nelio-content',
					),
				)
			);
		}
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/statuses',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_statuses' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'statuses' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_statuses' ),
							'sanitize_callback' => array( $this, 'sanitize_statuses' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to validate post statuses.
	 *
	 * @param mixed $statuses Post statues.
	 *
	 * @return true|WP_Error
	 */
	public function validate_statuses( $statuses ) {
		$schema = Z::array( $this->schema() );
		$result = $schema->safe_parse( $statuses );
		return $result['success'] ? true : new WP_Error( 'parse-error', $result['error'] );
	}

	/**
	 * Callback to sanitize post statuses.
	 *
	 * @param mixed $statuses Post statues.
	 *
	 * @return list<TPost_Status>
	 */
	public function sanitize_statuses( $statuses ) {
		$statuses = is_array( $statuses ) ? $statuses : array();
		$statuses = array_map( fn( $p ) => $this->parse( $p ), $statuses );
		$statuses = array_filter( $statuses, fn( $status ) => ! is_wp_error( $status ) );
		$statuses = key_by( $statuses, 'slug' );
		$statuses = array_diff_key( $statuses, array_flip( array( 'nelio-content-unscheduled', 'trash' ) ) );
		$statuses = array_values( $statuses );
		return $statuses;
	}

	/**
	 * Callback to update statuses.
	 *
	 * @param WP_REST_Request<array{statuses:list<TPost_Status>}> $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_statuses( $request ) {
		/** @var list<TPost_Status> */
		$statuses = $request->get_param( 'statuses' );
		$statuses = array_map(
			function ( $status ) {
				return array(
					'slug'        => $status['slug'],
					'name'        => $status['name'],
					'icon'        => $status['icon'] ?? '',
					'description' => $status['description'] ?? '',
					'colors'      => array(
						'main'       => $status['colors']['main'] ?? '',
						'background' => $status['colors']['background'] ?? '',
					),
					'postTypes'   => $status['postTypes'],
					'roles'       => $status['roles'],
					'flags'       => $status['flags'] ?? array(),
				);
			},
			$statuses
		);

		update_option( 'nc_statuses', $statuses );

		return new WP_REST_Response( nelio_content_get_statuses(), 200 );
	}

	/**
	 * Gets the schema.
	 *
	 * @return Schema
	 */
	private function schema() {
		return Z::object(
			array(
				'slug'        => Z::union(
					array(
						Z::string()->trim()->max( 20 ),
						Z::literal( 'nelio-content-unscheduled' ),
					)
				),
				'name'        => Z::string()->trim()->min( 1 ),
				'icon'        => Z::string()->optional(),
				'description' => Z::string()->optional(),
				'core'        => Z::boolean()->optional(),
				'colors'      => Z::object(
					array(
						'main'       => Z::string()->optional(),
						'background' => Z::string()->optional(),
					)
				)->optional(),
				'postTypes'   => Z::union(
					array(
						Z::array( Z::string() ),
						Z::literal( 'all-types' ),
					)
				),
				'roles'       => Z::union(
					array(
						Z::array( Z::string() ),
						Z::literal( 'all-roles' ),
					)
				),
				'flags'       => Z::array(
					Z::enum(
						array(
							'disabled-in-editor',
							'hide-in-board',
							'no-drop-in-board',
						)
					)
				)->optional(),
			)
		);
	}

	/**
	 * Parses the given JSON.
	 *
	 * @param mixed $json JSON.
	 *
	 * @return TPost_Status|WP_Error
	 */
	private function parse( $json ) {
		$json = is_string( $json ) ? json_decode( $json, true ) : $json;
		$json = is_array( $json ) ? $json : array();

		$parsed = $this->schema()->safe_parse( $json );
		if ( false === $parsed['success'] ) {
			return new WP_Error( 'parsing-error', $parsed['error'] );
		}

		/** @var TPost_Status */
		return $parsed['data'];
	}
}

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

use function Nelio_Content\Helpers\find;

class Nelio_Content_Statuses_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since 4.0.0
	 * @var   Nelio_Content_Statuses_REST_Controller
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content_Statuses_REST_Controller the single instance of this class.
	 *
	 * @since 4.0.0
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;
		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since 4.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_action( 'init', array( $this, 'manage_post_statuses_capabilities' ) );
		add_filter( 'nelio_content_can_use_post_status', array( $this, 'filter_can_use_post_status' ), 10, 3 );
	}//end init()

	public function register_post_statuses() {
		$statuses = nelio_content_get_saved_statuses();
		foreach ( $statuses as $status ) {
			if ( ! empty( $status['core'] ) ) {
				continue;
			}//end if

			if ( get_post_status_object( $status['slug'] ) ) {
				continue;
			}//end if

			register_post_status(
				$status['slug'],
				array(
					'label'       => $status['name'],
					'protected'   => true,
					'label_count' => array(
						'singular' => sprintf(
							/* translators: %1$s: status name, %2$s: item count */
							__( '%1$s <span class="count">(%2$s)</span>', 'nelio-content' ),
							$status['name'],
							'%s'
						),
						'plural'   => sprintf(
							/* translators: %1$s: status name, %2$s: item count */
							__( '%1$s <span class="count">(%2$s)</span>', 'nelio-content' ),
							$status['name'],
							'%s'
						),
						'context'  => null,
						'domain'   => 'nelio-content',
					),
				)
			);
		}//end foreach
	}//end register_post_statuses()

	public function manage_post_statuses_capabilities() {
		$statuses = nelio_content_get_saved_statuses();
		$roles    = wp_roles();
		foreach ( $statuses as $status ) {
			if ( ! empty( $status['core'] ) ) {
				continue;
			}//end if

			foreach ( $roles->role_objects as $role_name => $role ) {
				$cap_name = 'status_change_' . str_replace( '-', '_', $status['slug'] );

				if ( empty( $status['roles'] ) || ! in_array( $role_name, $status['roles'], true ) ) {
					$role->remove_cap( $cap_name );
					continue;
				}//end if

				if ( ! $role->has_cap( $cap_name ) ) {
					$role->add_cap( $cap_name );
				}//end if
			}//end foreach
		}//end foreach
	}//end manage_post_statuses_capabilities()

	public function filter_can_use_post_status( $available, $status_slug, $post_type ) {
		$statuses = nelio_content_get_saved_statuses();
		$status   = find(
			$statuses,
			function ( $s ) use ( $status_slug ) {
				return $s['slug'] === $status_slug;
			}
		);

		if ( ! $status ) {
			return $available;
		}//end if

		if ( ! empty( $status['core'] ) ) {
			return $available;
		}//end if

		if ( ! empty( $status['postTypes'] ) && ! in_array( $post_type, $status['postTypes'], true ) ) {
			return false;
		}//end if

		if ( ! empty( $status['roles'] ) ) {
			$user = wp_get_current_user();
			if ( current_user_can( 'manage_options' ) ) {
				return true;
			}//end if

			$cap_name = 'status_change_' . str_replace( '-', '_', $status['slug'] );
			if (
				( ! empty( $status['roles'] ) && ! array_intersect( $user->roles, $status['roles'] ) ) ||
				! current_user_can( $cap_name ) ||
				( 'post' === $post_type && ! current_user_can( 'edit_posts' ) ) ||
				( 'page' === $post_type && ! current_user_can( 'edit_pages' ) )
			) {
				return false;
			}//end if
		}//end if

		return $available;
	}//end filter_can_use_post_status()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/statuses',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_statuses' ),
					'permission_callback' => 'nc_can_current_user_manage_plugin',
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
	}//end register_routes()

	public function validate_statuses( $statuses ) {
		$schema = Z::array( self::schema() );
		$result = $schema->safe_parse( $statuses );
		return $result['success'] ? true : new WP_Error( 'parse-error', $result['error'] );
	}//end validate_statuses()

	public function sanitize_statuses( $statuses ) {
		return array_map( fn( $p ) => self::parse( $p ), $statuses );
	}//end sanitize_statuses()

	public function update_statuses( $request ) {
		$statuses = $request->get_param( 'statuses' );

		$error = find( $statuses, 'is_wp_error' );
		if ( is_wp_error( $error ) ) {
			return $error;
		}//end if

		nelio_content_set_saved_statuses( $statuses );

		return new WP_REST_Response( $statuses, 200 );
	}//end update_statuses()

	public static function schema(): Schema {
		return Z::object(
			array(
				'slug'        => Z::string()->trim()->max( 20 ),
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
				'available'   => Z::boolean(),
				'postTypes'   => Z::array( Z::string() )->optional(),
				'roles'       => Z::array( Z::string() )->optional(),
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
	}//end schema()

	public static function parse( $json ) {
		$json = is_string( $json ) ? json_decode( $json, ARRAY_A ) : $json;
		$json = is_array( $json ) ? $json : array();

		$parsed = self::schema()->safe_parse( $json );
		if ( empty( $parsed['success'] ) ) {
			return new WP_Error( 'parsing-error', $parsed['error'] );
		}//end if

		return $parsed['data'];
	}//end parse()
}//end class

<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing post references.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Reference_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Reference_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Reference_REST_Controller
	 *
	 * @since  2.0.0
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
			'/reference/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_reference' ),
					'permission_callback' => array( $this, 'can_update_reference' ),
					'args'                => array(
						'id'      => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'title'   => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'author'  => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'email'   => array(
							'required'          => false,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_email',
						),
						'twitter' => array(
							'required'          => false,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_twitter_handle',
						),
						'date'    => array(
							'required'          => false,
							'type'              => 'datetime',
							'validate_callback' => 'nelio_content_is_datetime',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/reference/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_reference_by_url' ),
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
	 * Gets the requested reference.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function search_reference_by_url( $request ) {
		$url       = $request->get_param( 'url' );
		$reference = ! empty( $url ) ? nelio_content_create_reference( $url ) : false;
		return new WP_REST_Response( ! empty( $reference ) ? $reference->json_encode() : false, 200 );
	}

	/**
	 * Updates the reference.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_reference( $request ) {

		$reference_id = $this->reference_id( $request );
		$parents      = Nelio_Content_Reference_Permissions::editable_parents( $reference_id, true );
		if ( is_wp_error( $parents ) ) {
			return $parents;
		}
		$reference = nelio_content_get_reference( $reference_id );
		if ( ! $reference ) {
			return Nelio_Content_Reference_Permissions::denied();
		}
		$setters = array(
			'title'   => 'set_title',
			'author'  => 'set_author_name',
			'email'   => 'set_author_email',
			'twitter' => 'set_author_twitter',
			'date'    => 'set_publication_date',
		);
		foreach ( $setters as $field => $setter ) {
			if ( Nelio_Content_Reference_Permissions::editable_parents( $reference_id, true ) !== $parents ) {
				return $this->unconfirmed_update();
			}
			$value = $request->get_param( $field );
			$reference->$setter( ! empty( $value ) && is_string( $value ) ? $value : '' );
		}
		if ( Nelio_Content_Reference_Permissions::editable_parents( $reference_id, true ) !== $parents ) {
			return $this->unconfirmed_update();
		}
		return new WP_REST_Response( $reference->json_encode(), 200 );
	}

	/**
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 * @return bool
	 * @phpstan-impure
	 */
	public function can_update_reference( $request ) {
		return ! is_wp_error( Nelio_Content_Reference_Permissions::editable_parents( $this->reference_id( $request ), true ) );
	}

	/**
	 * The URL controls the reference identity, regardless of JSON parameters.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 * @return int
	 */
	private function reference_id( $request ) {
		$params = $request->get_url_params();
		return absint( $params['id'] ?? $request['id'] );
	}

	/** @return WP_Error */
	private function unconfirmed_update() {
		return new WP_Error( 'nelio_content_reference_update_unconfirmed', _x( 'The shared-reference update could not be confirmed. Some requested fields may have been saved. Inspect the reference and review access before retrying; no automatic retry was performed.', 'user', 'nelio-content' ) );
	}
}

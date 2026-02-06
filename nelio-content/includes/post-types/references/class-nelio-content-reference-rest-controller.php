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
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
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
	 * @param WP_REST_Request<TEditorial_Reference> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_reference( $request ) {

		$reference_id = absint( $request['id'] );

		$reference = nelio_content_get_reference( $reference_id );
		if ( ! $reference ) {
			return new WP_Error(
				'reference-not-found',
				_x( 'Reference not found', 'text', 'nelio-content' )
			);
		}

		$title = $request->get_param( 'title' );
		$title = ! empty( $title ) ? $title : '';
		$reference->set_title( $title );

		$author = $request->get_param( 'author' );
		$author = ! empty( $author ) ? $author : '';
		$reference->set_author_name( $author );

		$email = $request->get_param( 'email' );
		$email = ! empty( $email ) ? $email : '';
		$reference->set_author_email( $email );

		$twitter = $request->get_param( 'twitter' );
		$twitter = ! empty( $twitter ) ? $twitter : '';
		$reference->set_author_twitter( $twitter );

		$date = $request->get_param( 'date' );
		$date = ! empty( $date ) ? $date : '';
		$reference->set_publication_date( $date );

		return new WP_REST_Response( $reference->json_encode(), 200 );
	}
}

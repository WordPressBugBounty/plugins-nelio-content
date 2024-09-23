<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing reusable messages.
 *
 * @since 3.3.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_Content\Zod\Zod as Z;

class Nelio_Content_Reusable_Message_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since 3.3.0
	 * @var   Nelio_Content_Reusable_Message_REST_Controller
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content_Reusable_Message_REST_Controller the single instance of this class.
	 *
	 * @since 3.3.0
	 */
	public static function instance() {
		self::$instance = is_null( self::$instance ) ? new self() : self::$instance;
		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since 3.3.0
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
			'/reusable-message',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_reusable_message' ),
					'permission_callback' => 'nc_can_current_user_use_plugin',
					'args'                => array(
						'message' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'validate_reusable_message' ),
							'sanitize_callback' => array( $this, 'sanitize_reusable_message' ),
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_reusable_message' ),
					'permission_callback' => 'nc_can_current_user_use_plugin',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/reusable-messages/search',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'search_reusable_messages' ),
					'permission_callback' => 'nc_can_current_user_use_plugin',
					'args'                => array(
						'query'   => array(
							'required'          => true,
							'validate_callback' => fn( $v ) => is_string( $v ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'exclude' => array( // phpcs:ignore
							'required'          => true,
							'validate_callback' => fn( $v ) => is_array( $v ),
							'sanitize_callback' => fn( $a ) => array_values( array_filter( array_map( 'absint', $a ) ) ),
						),
					),
				),
			)
		);

	}//end register_routes()

	public function validate_reusable_message( $message ) {
		$schema = Nelio_Content_Reusable_Message::schema();
		$result = $schema->safe_parse( $message );
		return $result['success'] ? true : new WP_Error( 'parse-error', $result['error'] );
	}//end validate_reusable_message()

	public function sanitize_reusable_message( $message ) {
		return Nelio_Content_Reusable_Message::parse( $message );
	}//end sanitize_reusable_message()

	public function update_reusable_message( $request ) {
		$message = $request->get_param( 'message' );
		if ( is_wp_error( $message ) ) {
			return $message;
		}//end if

		$message->save();
		return new WP_REST_Response( $message->json(), 200 );
	}//end update_reusable_message()

	public function remove_reusable_message( $request ) {
		$message_id = $request->get_param( 'id' );
		if ( 'nc_reusable_social' !== get_post_type( $message_id ) ) {
			return new WP_Error(
				sprintf(
				/* translators: post ID */
					_x(
						'Item #%s is not a reusable social message.',
						'text',
						'nelio-content'
					),
					$message_id
				)
			);
		}//end if
		wp_delete_post( $message_id );
		return new WP_REST_Response( true, 200 );
	}//end remove_reusable_message()

	public function search_reusable_messages( $request ) {
		$query        = $request->get_param( 'query' );
		$excluded_ids = $request->get_param( 'exclude' );

		$search = $this->search( $query, $excluded_ids, 20 );

		$new_ids      = wp_list_pluck( $search['messages'], 'id' );
		$excluded_ids = array_merge( $excluded_ids, $new_ids );
		$extra        = $this->search( '', $excluded_ids, 1 );

		$response = array(
			'messages' => $search['messages'],
			'status'   => empty( $extra['messages'] ) && empty( $extra['more'] )
				? 'all-loaded'
				: ( $search['more'] ? 'more' : 'query-loaded' ),
		);

		return new WP_REST_Response( $response, 200 );
	}//end search_reusable_messages()

	private function search( $query, $excluded_ids, $count ) {
		$search_columns = fn() => array( 'post_excerpt' );

		add_filter( 'post_search_columns', $search_columns );
		$wpq = new WP_Query(
			array(
				'fields'         => 'ids',
				'post_type'      => 'nc_reusable_social',
				'posts_per_page' => $count,
				'post__not_in'   => $excluded_ids,
				's'              => $query,
				'post_status'    => 'draft',
			)
		);
		$ids = $wpq->get_posts();
		remove_filter( 'post_search_columns', $search_columns );

		$messages = array_map( fn( $id ) => new Nelio_Content_Reusable_Message( $id ), $ids );
		$messages = array_map( fn( $m ) => $m->json(), $messages );
		return array(
			'messages' => $messages,
			'more'     => 1 < $wpq->max_num_pages,
		);
	}//end search()

}//end class

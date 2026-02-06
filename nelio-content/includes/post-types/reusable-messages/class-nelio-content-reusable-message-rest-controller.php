<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing reusable messages.
 *
 * @since 3.3.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Reusable_Message_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since 3.3.0
	 * @var   Nelio_Content_Reusable_Message_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Reusable_Message_REST_Controller
	 *
	 * @since 3.3.0
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
	 * @since 3.3.0
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
			'/reusable-message',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_reusable_message' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
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
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
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
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'query'   => array(
							'required'          => true,
							'validate_callback' => fn( $v ) => is_string( $v ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
						'exclude' => array(
							'required'          => true,
							'validate_callback' => fn( $v ) => is_array( $v ),
							'sanitize_callback' => fn( $a ) => is_array( $a ) ? array_values( array_filter( array_map( 'absint', $a ) ) ) : array(),
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to validate reusable social message.
	 *
	 * @param array<mixed> $message Message.
	 *
	 * @return true|WP_Error
	 */
	public function validate_reusable_message( $message ) {
		$schema = Nelio_Content_Reusable_Message::schema();
		$result = $schema->safe_parse( $message );
		return $result['success'] ? true : new WP_Error( 'parse-error', $result['error'] );
	}

	/**
	 * Callback to sanitize reusable social message.
	 *
	 * @param array<mixed> $message Message.
	 *
	 * @return Nelio_Content_Reusable_Message
	 */
	public function sanitize_reusable_message( $message ) {
		$message = Nelio_Content_Reusable_Message::parse( $message );
		assert( ! is_wp_error( $message ), 'Message has been validated and is therefore parseable' );
		return $message;
	}

	/**
	 * Callback to update reusable social message.
	 *
	 * @param WP_REST_Request<array{message:Nelio_Content_Reusable_Message}> $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_reusable_message( $request ) {
		$message = $request->get_param( 'message' );
		assert( $message instanceof Nelio_Content_Reusable_Message );
		$message->save();
		return new WP_REST_Response( $message->json(), 200 );
	}

	/**
	 * Callback to remove reusable social message.
	 *
	 * @param WP_REST_Request<array{id:int}> $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_reusable_message( $request ) {
		$message_id = absint( $request->get_param( 'id' ) );
		if ( 'nc_reusable_social' !== get_post_type( $message_id ) ) {
			return new WP_Error(
				sprintf(
				/* translators: %s: Post ID. */
					_x(
						'Item #%s is not a reusable social message.',
						'text',
						'nelio-content'
					),
					$message_id
				)
			);
		}
		wp_delete_post( $message_id );
		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Callback to search reusable social messages.
	 *
	 * @param WP_REST_Request<array{query:string,exclude:list<int>}> $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function search_reusable_messages( $request ) {
		$query = $request->get_param( 'query' );
		$query = is_string( $query ) ? $query : '';

		$excluded_ids = $request->get_param( 'exclude' );
		$excluded_ids = is_array( $excluded_ids ) ? $excluded_ids : array();
		$excluded_ids = array_map( fn ( $id ) => absint( $id ), $excluded_ids );

		$search = $this->search( $query, $excluded_ids, 20 );

		$new_ids      = array_map( fn( $m ) => $m['id'], $search['messages'] );
		$excluded_ids = array_merge( $excluded_ids, $new_ids );
		$extra        = $this->search( '', $excluded_ids, 1 );

		$response = array(
			'messages' => $search['messages'],
			'status'   => empty( $extra['messages'] ) && empty( $extra['more'] )
				? 'all-loaded'
				: ( $search['more'] ? 'more' : 'query-loaded' ),
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Returns reusable messages matching the given query.
	 *
	 * @param string    $query        Query.
	 * @param list<int> $excluded_ids IDs to ignore during search.
	 * @param int       $count        Max number of messages to return.
	 *
	 * @return array{
	 *   messages: list<TReusable_Social_Message>,
	 *   more: bool
	 * }
	 */
	private function search( $query, $excluded_ids, $count ) {
		$search_columns = fn() => array( 'post_excerpt' );

		add_filter( 'post_search_columns', $search_columns );
		$wpq = new WP_Query(
			array(
				'fields'         => 'ids',
				'post_type'      => 'nc_reusable_social',
				'posts_per_page' => $count,
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
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
			'messages' => array_values( $messages ),
			'more'     => 1 < $wpq->max_num_pages,
		);
	}
}

<?php
/**
 * This file contains some generic REST functions we might need.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Generic_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Generic_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Generic_REST_Controller
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
			'/wizard/end',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'finish_wizard' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/social/reset',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reset_auto_social_messages' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/social/pause-publication',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'pause_publication' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'paused' => array(
							'required'          => true,
							'type'              => 'boolean',
							'validate_callback' => 'nelio_content_can_be_bool',
							'sanitize_callback' => 'nelio_content_bool',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/notifications/comment',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'run_new_comment_action' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'authorId' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'comment'  => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'date'     => array(
							'required'          => true,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_datetime',
						),
						'postId'   => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/notifications/task',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'run_update_task_action' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'assigneeId' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'assignerId' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'completed'  => array(
							'required'          => true,
							'type'              => 'boolean',
							'validate_callback' => 'nelio_content_can_be_bool',
							'sanitize_callback' => 'nelio_content_bool',
						),
						'dateDue'    => array(
							'required'          => true,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_datetime',
						),
						'isNewTask'  => array(
							'required'          => true,
							'type'              => 'boolean',
							'validate_callback' => 'nelio_content_can_be_bool',
							'sanitize_callback' => 'nelio_content_bool',
						),
						'postId'     => array(
							'required'          => false,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'task'       => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/settings/update-profiles',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_profiles' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'profiles' => array(
							'required'          => true,
							'type'              => 'boolean',
							'validate_callback' => 'nelio_content_can_be_bool',
							'sanitize_callback' => 'nelio_content_bool',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/plugin/clean',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'clean_plugin' ),
					'permission_callback' => array( $this, 'check_if_user_can_deactivate_plugin' ),
					'args'                => array(
						'_nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => flow( 'trim', 'nelio_content_is_not_empty' ),
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/plugin/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'deactivate_plugin' ),
					'permission_callback' => array( $this, 'check_if_user_can_deactivate_plugin' ),
					'args'                => array(
						'_nonce' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => flow( 'trim', 'nelio_content_is_not_empty' ),
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to check if user can deactivate plugin.
	 *
	 * @return bool
	 */
	public function check_if_user_can_deactivate_plugin() {
		return current_user_can( 'deactivate_plugin', nelio_content()->plugin_file );
	}

	/**
	 * Finishes the wizard.
	 *
	 * @return WP_REST_Response
	 */
	public function finish_wizard() {
		nelio_content()->finish_wizard();
		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Pauses or resumes social publication.
	 *
	 * @return WP_REST_Response
	 */
	public function reset_auto_social_messages() {
		$sharer = Nelio_Content_Auto_Sharer::instance();
		$sharer->reset();
		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Pauses or resumes social publication.
	 *
	 * @param WP_REST_Request<array{paused:bool}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function pause_publication( $request ) {

		$is_paused = ! empty( $request['paused'] );

		$body = array(
			'url'                        => home_url(),
			'timezone'                   => nelio_content_get_timezone(),
			'language'                   => nelio_content_get_language(),
			'isMessagePublicationPaused' => $is_paused,
		);

		if ( ! $is_paused ) {
			$body['isPluginInactive'] = false;
		}

		$body = wp_json_encode( $body );
		assert( ! empty( $body ) );

		// Note. Use error_logs for logging this function or you won't see anything.
		$data = array(
			'method'  => 'PUT',
			'timeout' => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'headers' => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'    => $body,
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$result   = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/** @var array{isMessagePublicationPaused:bool} $result */
		$is_paused = ! empty( $result['isMessagePublicationPaused'] );

		return new WP_REST_Response( $is_paused, 200 );
	}

	/**
	 * Runs an action so that post followers can be notified when a new comment has been added to a post.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_new_comment_action( $request ) {

		assert( is_string( $request['id'] ) );
		assert( is_int( $request['authorId'] ) );
		assert( is_string( $request['comment'] ) );
		assert( is_string( $request['date'] ) );
		assert( is_int( $request['postId'] ) );

		$comment = array(
			'id'      => $request['id'],
			'author'  => $request['authorId'],
			'comment' => $request['comment'],
			'date'    => $request['date'],
			'post'    => $request['postId'],
		);

		$key = 'nc_new_comment_notify_' . $request['id'];
		if ( get_transient( $key ) ) {
			return new WP_REST_Response( array( 'deduped' => true ), 200 );
		}
		set_transient( $key, 1, 1 * HOUR_IN_SECONDS );

		/**
		 * It runs when an editorial comment has been created.
		 *
		 * @param array{id:string,author:int,comment:string,date:string,post:int} $comment the comment.
		 * @param int                                                             $user    the user who created the comment.
		 *
		 * @since 2.0.0
		 */
		do_action( 'nelio_content_after_create_editorial_comment', $comment, get_current_user_id() );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Runs an action so that users related to a task can know it’s been created or updated.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function run_update_task_action( $request ) {

		assert( is_string( $request['id'] ) );
		assert( is_int( $request['assigneeId'] ) );
		assert( is_int( $request['assignerId'] ) );
		assert( is_string( $request['dateDue'] ) );
		assert( is_string( $request['task'] ) );
		assert( is_int( $request['postId'] ?? 0 ) );

		$task = array(
			'id'         => $request['id'],
			'assigneeId' => $request['assigneeId'],
			'assignerId' => $request['assignerId'],
			'completed'  => ! empty( $request['completed'] ),
			'dateDue'    => $request['dateDue'],
			'postId'     => $request['postId'] ?? 0,
			'task'       => $request['task'],
		);

		if ( ! empty( $request['isNewTask'] ) ) {

			$key = 'nc_new_task_notify_' . $request['id'];
			if ( get_transient( $key ) ) {
				return new WP_REST_Response( array( 'deduped' => true ), 200 );
			}
			set_transient( $key, 1, 1 * HOUR_IN_SECONDS );

			/**
			 * It runs when an editorial task has been created.
			 *
			 * @param array{assigneeId:int,assignerId:int,completed:bool,dateDue:string,postId:int,task:string} $task the task.
			 * @param int                                                                                       $user the user who created the task.
			 *
			 * @since 2.0.0
			 */
			do_action( 'nelio_content_after_create_editorial_task', $task, get_current_user_id() );
		} else {
			/**
			 * It runs when an editorial task has been updated.
			 *
			 * @param array{assigneeId:int,assignerId:int,completed:bool,dateDue:string,postId:int,task:string} $task the task.
			 * @param int                                                                                       $user the user who updated the task.
			 *
			 * @since 2.0.0
			 */
			do_action( 'nelio_content_after_update_editorial_task', $task, get_current_user_id() );
		}

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Updates the setting that track whether the site has any connected social profiles.
	 *
	 * @param WP_REST_Request<array{profiles:bool}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function update_profiles( $request ) {

		$has_profiles = ! empty( $request['profiles'] );
		update_option( 'nc_has_social_profiles', $has_profiles );
		return new WP_REST_Response( $has_profiles, 200 );
	}

	/**
	 * Deactivates the plugin. It tells our cloud to pause the calendar.
	 *
	 * @param WP_REST_Request<array{_nonce:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function deactivate_plugin( $request ) {

		/** @var string */
		$nonce = $request['_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'nelio_content_clean_plugin_data_' . get_current_user_id() ) ) {
			return new WP_Error( 'invalid-nonce' );
		}

		$body = wp_json_encode(
			array(
				'url'              => home_url(),
				'timezone'         => nelio_content_get_timezone(),
				'language'         => nelio_content_get_language(),
				'isPluginInactive' => true,
			)
		);
		assert( ! empty( $body ) );

		$data = array(
			'method'  => 'PUT',
			'timeout' => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'headers' => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'    => $body,
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Cleans the plugin. If a reason is provided, it tells our cloud what happened.
	 *
	 * @param WP_REST_Request<array{_nonce:string,reason?:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function clean_plugin( $request ) {

		/** @var string */
		$nonce = $request['_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'nelio_content_clean_plugin_data_' . get_current_user_id() ) ) {
			return new WP_Error( 'invalid-nonce' );
		}

		// 1. Clean cloud.
		/** @var string */
		$reason = ! empty( $request['reason'] ) ? $request['reason'] : 'none';
		$body   = wp_json_encode( array( 'reason' => $reason ) );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'body'      => $body,
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Clean database.
		/** @var wpdb */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM $wpdb->postmeta
			WHERE meta_key LIKE '_nc_%'"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM $wpdb->posts
			WHERE post_type IN (
				'nc_reference',
				'nc_reusable_social',
				'nc_future_action',
				'nc_task_preset'
			)"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"DELETE FROM $wpdb->options
			WHERE option_name LIKE 'nc_%' OR
			      option_name LIKE 'nelio_content_%' OR
			      option_name LIKE 'nelio-content_%'"
		);

		return new WP_REST_Response( true, 200 );
	}
}

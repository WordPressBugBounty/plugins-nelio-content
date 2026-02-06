<?php
/**
 * This file tracks the saving of a post regardless of the scope in which the saving occurs.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Post_Saving {

	const LATE_PRIORITY = 9999;

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Post_Saving|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Post_Saving
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
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_hooks_to_trigger_custom_save_post_action' ) );
		add_action( 'init', array( $this, 'maybe_add_hooks_to_notify_post_followers' ) );
		add_action( 'nelio_content_save_post', array( $this, 'add_default_post_followers' ), 1, 2 );
	}

	/**
	 * Callback to add hooks to trigger custom save post action.
	 *
	 * @return void
	 */
	public function add_hooks_to_trigger_custom_save_post_action() {
		$post_types = nelio_content_get_post_types( 'cloud' );

		$on_regular_post_save = function ( $post_id, $post, $update ) use ( $post_types ) {
			/** @var int     $post_id */
			/** @var WP_Post $post    */
			/** @var bool    $update  */

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return;
			}

			if ( ! in_array( $post->post_type, $post_types, true ) ) {
				return;
			}
			$this->trigger_save_post_action( $post_id, ! $update );
		};

		$on_rest_post_save = function ( $post, $request, $creating ) {
			/** @var WP_Post                $post     */
			/** @var WP_REST_Request<mixed> $request  */
			/** @var bool                   $creating */

			$this->trigger_save_post_action( $post->ID, $creating );
		};

		add_action( 'wp_insert_post', $on_regular_post_save, self::LATE_PRIORITY, 3 );

		foreach ( $post_types as $post_type ) {
			add_action( "rest_after_insert_{$post_type}", $on_rest_post_save, self::LATE_PRIORITY, 3 );
		}
	}

	/**
	 * Callback to add hooks to notify post followers when there’s a change.
	 *
	 * @return void
	 */
	public function maybe_add_hooks_to_notify_post_followers() {

		if ( empty( nelio_content_get_post_types( 'notifications' ) ) ) {
			return;
		}

		$old_post_values      = array();
		$save_old_post_values = function ( $post_id ) use ( &$old_post_values ) {
			/** @var int $post_id */

			$status = get_post_status( $post_id );
			if ( false === $status ) {
				return;
			}

			if ( isset( $old_post_values[ $post_id ] ) ) {
				return;
			}
			$post_helper                 = Nelio_Content_Post_Helper::instance();
			$old_post_values[ $post_id ] = array(
				'status'    => $status,
				'followers' => $post_helper->get_post_followers( $post_id ),
			);
		};

		$make_sure_we_save_old_post_values = function ( $new_status, $old_status, $post ) use ( &$old_post_values ) {
			/** @var string  $new_status */
			/** @var string  $old_status */
			/** @var WP_Post $post       */

			if ( isset( $old_post_values[ $post->ID ] ) ) {
				return;
			}
			$post_helper                  = Nelio_Content_Post_Helper::instance();
			$old_post_values[ $post->ID ] = array(
				'status'    => $old_status,
				'followers' => $post_helper->get_post_followers( $post->ID ),
			);
		};

		$notify_post_followers = function ( $post_id ) use ( &$old_post_values ) {
			/** @var int $post_id */

			$prev_values = array(
				'status'    => 'auto-draft',
				'followers' => array(),
			);

			if ( isset( $old_post_values[ $post_id ] ) ) {
				$prev_values = $old_post_values[ $post_id ];
			}

			$this->notify_post_followers( $post_id, $prev_values );
		};

		add_action( 'pre_post_update', $save_old_post_values, 1 );
		add_action( 'transition_post_status', $make_sure_we_save_old_post_values, 1, 3 );
		add_action( 'nelio_content_save_post', $notify_post_followers, self::LATE_PRIORITY );
	}

	/**
	 * Callback to add default post followers.
	 *
	 * @param int     $post_id  Post ID.
	 * @param boolean $creating Whether we’re creating a post or not.
	 *
	 * @return void
	 */
	public function add_default_post_followers( $post_id, $creating ) {

		$default = array();

		if ( $creating ) {
			/**
			 * Filters whether the post creator (i.e. the current user) should be added in the post followers list or not.
			 *
			 * This filter only runs when a post is being created.
			 *
			 * @param boolean $auto_subscribe whether the post creator should be a follower or not. Default: `true`.
			 *
			 * @since 2.0.0
			 */
			if ( apply_filters( 'nelio_content_notification_auto_subscribe_post_creator', true ) ) {
				$user      = wp_get_current_user();
				$default[] = $user->ID;
			}
		}

		/**
		 * Filters whether the post author should be added in the post followers list or not.
		 *
		 * @param boolean $auto_subscribe whether the post author should be a follower or not. Default: `true`.
		 *
		 * @since 1.4.2
		 */
		if ( apply_filters( 'nelio_content_notification_auto_subscribe_post_author', true ) ) {
			$post      = get_post( $post_id );
			$default[] = ! empty( $post ) ? absint( $post->post_author ) : 0;
		}

		$default = array_values( array_filter( $default ) );
		if ( empty( $default ) ) {
			return;
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		$followers   = $post_helper->get_post_followers( $post_id );

		$new_followers = array_merge( $followers, $default );
		if ( count( $new_followers ) === count( $followers ) ) {
			return;
		}

		$post_helper->save_post_followers( $post_id, $new_followers );
	}

	/**
	 * Callback to trigger Nelio Content’s save post action.
	 *
	 * @param int     $post_id  The post we’ve just saved.
	 * @param boolean $creating `true` when creating a post, `false` when updating.
	 *
	 * @return void
	 */
	public function trigger_save_post_action( $post_id, $creating ) {

		/**
		 * This action is triggered after a post is saved so that we can notify its followers.
		 *
		 * @param int     $post_id  The post we’ve just saved.
		 * @param boolean $creating `true` when creating a post, `false` when updating.
		 *
		 * @since 2.0.0
		 */
		do_action( 'nelio_content_save_post', $post_id, $creating );
	}

	/**
	 * Notifies post followers.
	 *
	 * @param int                                       $post_id Post ID.
	 * @param array{status:string, followers:list<int>} $prev_values Previous values.
	 *
	 * @return void
	 */
	private function notify_post_followers( $post_id, $prev_values ) {

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		$post_types = nelio_content_get_post_types( 'notifications' );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		$post_helper   = Nelio_Content_Post_Helper::instance();
		$followers     = $post_helper->get_post_followers( $post_id );
		$old_status    = $prev_values['status'];
		$old_followers = $prev_values['followers'];

		/**
		 * This action is triggered after a post is saved so that we can notify its followers.
		 *
		 * @param WP_Post   $post          The post.
		 * @param list<int> $followers     List with current post followers.
		 * @param string    $old_status    Previous post status (i.e. before the update).
		 * @param list<int> $old_followers List with previous post followers (i.e. before the update).
		 *
		 * @since 2.0.0
		 */
		do_action( 'nelio_content_notify_post_followers', $post, $followers, $old_status, $old_followers );
	}
}

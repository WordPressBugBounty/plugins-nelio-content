<?php
/**
 * This file contains a class with notifications-related functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      1.4.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements notifications-related functions.
 */
class Nelio_Content_Notifications {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Notifications|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Notifications
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

		add_action( 'init', array( $this, 'add_hooks_if_notifications_are_enabled' ) );
		add_action( 'delete_user', array( $this, 'delete_follower' ) );
	}

	/**
	 * Hooks into WordPress if notifications are enabled.
	 *
	 * @return void
	 */
	public function add_hooks_if_notifications_are_enabled() {

		// Post status change actions.
		if ( $this->should_followers_be_notified() ) {
			add_action( 'nelio_content_notify_post_followers', array( $this, 'maybe_notify_post_followers' ), 10, 4 );
		}

		// Editorial comments change actions.
		if ( $this->are_comment_notifications_enabled() ) {
			add_action( 'nelio_content_after_create_editorial_comment', array( $this, 'maybe_send_comment_creation_notification' ) );
		}

		// Editorial tasks change actions.
		if ( $this->are_task_notifications_enabled() ) {
			add_action( 'nelio_content_after_create_editorial_task', array( $this, 'maybe_send_task_creation_notification' ) );
			add_action( 'nelio_content_after_update_editorial_task', array( $this, 'maybe_send_task_update_notification' ) );
		}
	}

	/**
	 * Callback to notify post followers.
	 *
	 * @param int       $post_id       Post ID.
	 * @param list<int> $followers     Followers.
	 * @param string    $old_status    Old status.
	 * @param list<int> $old_followers Old followers.
	 *
	 * @return void
	 */
	public function maybe_notify_post_followers( $post_id, $followers, $old_status, $old_followers ) {

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		if ( ! $this->should_followers_be_notified( $post->post_type ) ) {
			return;
		}

		/**
		 * Filters the status that shouldn’t trigger a notification email.
		 *
		 * @param array  $statuses  Statuses that shouldn’t trigger a notification email. Default: [ `inherit`, `auto-draft` ].
		 * @param string $post_type Post type.
		 *
		 * @since 1.4.2
		 */
		$ignored_statuses = apply_filters( 'nelio_content_notification_ignored_statuses', array( 'inherit', 'auto-draft' ), $post->post_type );
		$ignored_statuses = array_merge( $ignored_statuses, array( $old_status ) );

		// If the post status changed, let’s notify all current followers.
		$is_valid_status  = ! in_array( $post->post_status, $ignored_statuses, true );
		$is_status_change = $post->post_status !== $old_status;
		if ( $is_valid_status && $is_status_change ) {
			$email = $this->get_post_status_change_email_data( $post, $old_status );
			$this->send_email( $email, $followers, $post );
			return;
		}

		// If it didn’t, but there are new followers, let’s them know.
		$new_followers = array_values( array_diff( $followers, $old_followers ) );
		if ( count( $new_followers ) ) {
			$email = $this->get_post_following_email_data( $post );
			$this->send_email( $email, $new_followers, $post );
			return;
		}
	}

	/**
	 * Callback to send comment creation notification.
	 *
	 * @param TEditorial_Comment $comment Comment.
	 *
	 * @return void
	 */
	public function maybe_send_comment_creation_notification( $comment ) {

		$post = get_post( $comment['postId'] );
		if ( empty( $post ) ) {
			return;
		}

		if ( ! $this->are_comment_notifications_enabled( $post->post_type ) ) {
			return;
		}

		/**
		 *  Kill switch for comment creation notification.
		 *
		 *  @param TEditorial_Comment|false $comment The comment.
		 *  @param WP_Post                  $post    The related post.
		 *
		 * @since 1.4.2
		 */
		if ( ! apply_filters( 'nelio_content_notification_editorial_comment', $comment, $post ) ) {
			return;
		}

		$helper     = Nelio_Content_Post_Helper::instance();
		$followers  = $this->should_followers_be_notified( $post->post_type )
			? $helper->get_post_followers( $comment['postId'] )
			: array();
		$recipients = array_values( array_unique( array_merge( $followers, array( $comment['authorId'] ) ) ) );

		$email = $this->get_comment_in_post_email_data( $post, $comment );
		$this->send_email( $email, $recipients, $comment );
	}

	/**
	 * Callback to send task creation notification.
	 *
	 * @param TEditorial_Task $task Task.
	 *
	 * @return void
	 */
	public function maybe_send_task_creation_notification( $task ) {

		$post = null;
		if ( ! empty( $task['postId'] ) ) {
			$post = get_post( $task['postId'] );
			if ( empty( $post ) ) {
				return;
			}
		}

		$post_type = ! empty( $post ) ? $post->post_type : null;
		if ( ! $this->are_task_notifications_enabled( $post_type ) ) {
			return;
		}

		/**
		 * Kill switch for task creation notification.
		 *
		 *  @param TEditorial_Task|false $task The task.
		 *  @param WP_Post|null          $post The related post (if any).
		 *
		 * @since 1.4.2
		 */
		if ( ! apply_filters( 'nelio_content_notification_editorial_task', $task, $post ) ) {
			return;
		}

		$helper     = Nelio_Content_Post_Helper::instance();
		$followers  = ! empty( $task['postId'] ) && $this->should_followers_be_notified( $post_type )
			? $helper->get_post_followers( $task['postId'] )
			: array();
		$recipients = array_values( array_unique( array_merge( $followers, array( $task['assignerId'], $task['assigneeId'] ) ) ) );

		$email = $this->get_task_creation_email_data( $task, $post );
		$this->send_email( $email, $recipients, $task );
	}

	/**
	 * Callback to send task update notification.
	 *
	 * @param TEditorial_Task $task Task.
	 *
	 * @return void
	 */
	public function maybe_send_task_update_notification( $task ) {

		$post = null;
		if ( ! empty( $task['postId'] ) ) {
			$post = get_post( $task['postId'] );
			if ( empty( $post ) ) {
				return;
			}
		}

		$post_type = ! empty( $post ) ? $post->post_type : null;
		if ( ! $this->are_task_notifications_enabled( $post_type ) ) {
			return;
		}

		/**
		 * Kill switch for task update notification.
		 *
		 *  @param TEditorial_Task|false $task The task.
		 *  @param WP_Post|null          $post The related post (if any).
		 *
		 * @since 1.4.2
		 */
		if ( ! apply_filters( 'nelio_content_notification_editorial_task', $task, $post ) ) {
			return;
		}

		$helper     = Nelio_Content_Post_Helper::instance();
		$followers  = ! empty( $task['postId'] ) && $this->should_followers_be_notified( $post_type )
			? $helper->get_post_followers( $task['postId'] )
			: array();
		$recipients = array_values( array_unique( array_merge( $followers, array( $task['assignerId'], $task['assigneeId'] ) ) ) );

		$email = $this->get_task_updated_email_data( $task, $post );
		$this->send_email( $email, $recipients, $task );
	}

	/**
	 * Callback to delete follower.
	 *
	 * @param int $id User ID.
	 *
	 * @return void
	 */
	public function delete_follower( $id ) {

		if ( ! $id ) {
			return;
		}

		/** @var wpdb $wpdb */
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'   => '_nc_following_users',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => $id,
			)
		);
	}

	/**
	 * Sends email.
	 *
	 * @param array{type:string,subject:string,message:string} $email      Email.
	 * @param list<int>                                        $recipients Recipients.
	 * @param WP_Post|TEditorial_Comment|TEditorial_Task       $item       Item.
	 *
	 * @return bool
	 */
	private function send_email( $email, $recipients, $item ) {

		$recipients = $this->get_email_addresses( $recipients );

		/**
		 * Filters the recipients of the email.
		 *
		 * @param list<string>                               $recipients emails of the recipients
		 * @param string                                     $type       type of email we’re about to send. Values can be: `status-change`, `new-post-follower`, `comment`, `task-creation`, or `task-completed`.
		 * @param WP_Post|TEditorial_Comment|TEditorial_Task $item       item that triggered the notification. Either a WordPress post, a task, or a comment.
		 *
		 * @since 2.0.0
		 */
		$recipients = apply_filters( 'nelio_content_notification_send_email_recipients', $recipients, $email['type'], $item );
		if ( empty( $recipients ) ) {
			return false;
		}

		/**
		 * Filters the subject of the email.
		 *
		 * @param string                                     $subject    the subject of the email.
		 * @param string                                     $type       type of email we’re about to send. Values can be: `status-change`, `new-post-follower`, `comment`, `task-creation`, or `task-completed`.
		 * @param WP_Post|TEditorial_Comment|TEditorial_Task $item       item that triggered the notification. Either a WordPress post, a task, or a comment.
		 *
		 * @since 1.4.2
		 */
		$subject = apply_filters( 'nelio_content_notification_send_email_subject', $email['subject'], $email['type'], $item );

		/**
		 * Filters the message of the email.
		 *
		 * @param string                                     $message    the message of the email.
		 * @param string                                     $type       type of email we’re about to send. Values can be: `status-change`, `new-post-follower`, `comment`, `task-creation`, or `task-completed`.
		 * @param WP_Post|TEditorial_Comment|TEditorial_Task $item       item that triggered the notification. Either a WordPress post, a task, or a comment.
		 *
		 * @since 1.4.2
		 */
		$message = apply_filters( 'nelio_content_notification_send_email_message', $email['message'], $email['type'], $item );

		/**
		 * Filters the headers of the email.
		 *
		 * @param string                                     $headers    the headers of the email.
		 * @param string                                     $type       type of email we’re about to send. Values can be: `status-change`, `new-post-follower`, `comment`, `task-creation`, or `task-completed`.
		 * @param WP_Post|TEditorial_Comment|TEditorial_Task $item       item that triggered the notification. Either a WordPress post, a task, or a comment.
		 *
		 * @since 1.4.2
		 */
		$message_headers = apply_filters( 'nelio_content_notification_send_email_message_headers', '', $email['type'], $item );

		return wp_mail( $recipients, $subject, $message, $message_headers );
	}

	/**
	 * Returns email addresses.
	 *
	 * @param list<int> $user_ids User IDs.
	 *
	 * @return list<string>
	 */
	private function get_email_addresses( $user_ids ) {

		if ( in_array( get_current_user_id(), $user_ids, true ) ) {
			/**
			 * Whether the current user should receive an email or not.
			 *
			 * @param boolean $receive_email whether the current user should receive an email or not. Default: `false`.
			 *
			 * @since      1.4.2
			 */
			if ( ! apply_filters( 'nelio_content_notification_email_current_user', false ) ) {
				$user_ids = array_values( array_diff( $user_ids, array( get_current_user_id() ) ) );
			}
		}

		$emails = array_map(
			function ( $user_id ) {
				if ( ! is_user_member_of_blog( $user_id ) ) {
					return false;
				}

				$info = get_userdata( $user_id );
				if ( empty( $info ) ) {
					return false;
				}

				return $info->user_email;
			},
			$user_ids
		);

		return array_values( array_unique( array_filter( $emails ) ) );
	}

	/**
	 * Gets post status change email data.
	 *
	 * @param WP_Post $post       Post.
	 * @param string  $old_status Status.
	 *
	 * @return array{type:'status-change',subject:string,message:string}
	 */
	private function get_post_status_change_email_data( $post, $old_status ) {

		$post_id     = $post->ID;
		$post_author = get_userdata( absint( $post->post_author ) );
		$post_status = $post->post_status;
		$post_type   = $this->get_post_type_label( $post );
		$post_title  = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );

		$blog_name = get_option( 'blogname' );

		$current_user = wp_get_current_user();
		if ( 0 !== $current_user->ID ) {
			/* translators: %1$s: User name. %2$s: User email. */
			$username_and_email = sprintf( _x( '%1$s (%2$s)', 'text', 'nelio-content' ), $current_user->display_name, $current_user->user_email );
		} else {
			$username_and_email = _x( 'WordPress Scheduler', 'text', 'nelio-content' );
		}

		$message = '';

		// Email subject and first line of body.
		// Set message subjects according to what action is being taken on the Post.
		if ( 'new' === $old_status || 'auto-draft' === $old_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] New %2$s Created: “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( 'A new %1$s (#%2$s “%3$s”) was created by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		} elseif ( 'trash' === $post_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Trashed: “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( '%1$s #%2$s “%3$s” was moved to the trash by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		} elseif ( 'trash' === $old_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Restored (from Trash): “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( '%1$s #%2$s “%3$s” was restored from trash by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		} elseif ( 'future' === $post_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Scheduled: “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. %5$s: Scheduled date . */
				_x( '%1$s #%2$s “%3$s” was scheduled by %4$s. It will be published on %5$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email,
				$this->get_scheduled_datetime( $post )
			) . "\r\n";

		} elseif ( 'publish' === $post_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Published: “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( '%1$s #%2$s “%3$s” was published by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		} elseif ( 'publish' === $old_status ) {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Unpublished: “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( '%1$s #%2$s “%3$s” was unpublished by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		} else {

			$subject = sprintf(
				/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
				_x( '[%1$s] %2$s Status Changed for “%3$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_type,
				$post_title
			);

			$message .= sprintf(
				/* translators: %1$s: Post type. %2$s: Post id. %3$s: Post title. %4$s: User name. */
				_x( 'Status was changed for %1$s #%2$s “%3$s” by %4$s', 'text', 'nelio-content' ),
				$post_type,
				$post_id,
				$post_title,
				$username_and_email
			) . "\r\n";

		}

		$message .= sprintf(
			/* translators: %1$s: Date. %2$s: Time. %3$s: Timezone. */
			_x( 'This action was taken on %1$s at %2$s %3$s', 'text', 'nelio-content' ),
			date_i18n( get_option( 'date_format' ) ),
			date_i18n( get_option( 'time_format' ) ),
			get_option( 'timezone_string' )
		) . "\r\n";

		// Email body.
		$friendly_old_status  = $this->get_post_status_label( $old_status );
		$friendly_post_status = $this->get_post_status_label( $post_status );

		$message .= "\r\n";

		$message .= sprintf(
			/* translators: %1$s: Old status. %2$s: New status. */
			_x( '%1$s => %2$s', 'text', 'nelio-content' ),
			$friendly_old_status,
			$friendly_post_status
		);
		$message .= "\r\n\r\n";

		$message .= "--------------------\r\n\r\n";

		/* translators: %s: Post type. */
		$message .= sprintf( _x( '== %s Details ==', 'title', 'nelio-content' ), $post_type ) . "\r\n";
		/* translators: %s: Post title. */
		$message .= sprintf( _x( 'Title: %s', 'text', 'nelio-content' ), $post_title ) . "\r\n";

		if ( ! empty( $post_author ) ) {

			$message .= sprintf(
				/* translators: %1$s: Author name. %2$s: Author email. */
				_x( 'Author: %1$s (%2$s)', 'text', 'nelio-content' ),
				$post_author->display_name,
				$post_author->user_email
			) . "\r\n";

		}

		$message .= $this->get_email_footer( $post );
		return array(
			'type'    => 'status-change',
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Gets post following email data.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return array{type:'new-post-follower',subject:string,message:string}
	 */
	private function get_post_following_email_data( $post ) {

		$post_type   = $this->get_post_type_label( $post );
		$post_title  = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );
		$post_author = get_userdata( absint( $post->post_author ) );

		$blog_name = get_option( 'blogname' );

		$subject = sprintf(
			/* translators: %1$s: Site name. %2$s: Post type. %3$s: Post title. */
			_x( '[%1$s] You’re now watching %2$s “%3$s”', 'text', 'nelio-content' ),
			$blog_name,
			$post_type,
			$post_title
		);

		$message = sprintf(
			/* translators: %1$s: Post type. %2$s: Post title. */
			_x( 'You’re now watching %1$s “%2$s”.', 'text', 'nelio-content' ),
			$post_type,
			$post_title
		) . "\r\n\r\n";

		$message .= sprintf(
			/* translators: %1$s: Date. %2$s: Time. %3$s: Timezone. */
			_x( 'This action was taken on %1$s at %2$s %3$s', 'text', 'nelio-content' ),
			date_i18n( get_option( 'date_format' ) ),
			date_i18n( get_option( 'time_format' ) ),
			get_option( 'timezone_string' )
		) . "\r\n\r\n";

		$message .= "--------------------\r\n\r\n";

		/* translators: %s: Post type. */
		$message .= sprintf( _x( '== %s Details ==', 'title', 'nelio-content' ), $post_type ) . "\r\n";
		/* translators: %s: Post title. */
		$message .= sprintf( _x( 'Title: %s', 'text', 'nelio-content' ), $post_title ) . "\r\n";

		if ( ! empty( $post_author ) ) {

			$message .= sprintf(
				/* translators: %1$s: Author name. %2$s: Author email. */
				_x( 'Author: %1$s (%2$s)', 'text', 'nelio-content' ),
				$post_author->display_name,
				$post_author->user_email
			) . "\r\n";

		}

		$message .= sprintf(
			/* translators: %s: Post status. */
			_x( 'Status: %s', 'text', 'nelio-content' ),
			$this->get_post_status_label( $post->post_status )
		) . "\r\n";

		$message .= $this->get_email_footer( $post );

		return array(
			'type'    => 'new-post-follower',
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Gets comment in post email data.
	 *
	 * @param WP_Post            $post Post.
	 * @param TEditorial_Comment $comment Comment.
	 *
	 * @return array{type:'comment',subject:string,message:string}
	 */
	private function get_comment_in_post_email_data( $post, $comment ) {

		$post_id    = $post->ID;
		$post_type  = $this->get_post_type_label( $post );
		$post_title = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );

		$current_user       = wp_get_current_user();
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;

		$blog_name = get_option( 'blogname' );

		$current_date = mysql2date( get_option( 'date_format' ), $comment['date'] );
		$current_time = mysql2date( get_option( 'time_format' ), $comment['date'] );

		$subject = sprintf(
			/* translators: %1$s: Blog name. %2$s: Post title. */
			_x( '[%1$s] New Editorial Comment: “%2$s”', 'text', 'nelio-content' ),
			$blog_name,
			$post_title
		);

		$message = sprintf(
			/* translators: %1$s: Post id. %2$s: Post title. %3$s: Post type. */
			_x( 'A new editorial comment was added to %3$s #%1$s “%2$s”', 'text', 'nelio-content' ),
			$post_id,
			$post_title,
			$post_type
		) . "\r\n\r\n";

		$message .= sprintf(
			/* translators: %1$s: Comment author. %2$s: Author email. %3$s: Date. %4$s: Time. */
			_x( '%1$s (%2$s) said on %3$s at %4$s:', 'text', 'nelio-content' ),
			$current_user_name,
			$current_user_email,
			$current_date,
			$current_time
		) . "\r\n";

		$message .= "\r\n" . $comment['comment'] . "\r\n";
		$message .= $this->get_email_footer( $post );

		return array(
			'type'    => 'comment',
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Gets task creation email data.
	 *
	 * @param TEditorial_Task $task Task.
	 * @param WP_Post|null    $post Post.
	 *
	 * @return array{type:'task-creation',subject:string,message:string}
	 */
	private function get_task_creation_email_data( $task, $post ) {

		$current_user       = wp_get_current_user();
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;

		$blog_name = get_option( 'blogname' );

		if ( $post ) {
			$post_id    = $post->ID;
			$post_type  = $this->get_post_type_label( $post );
			$post_title = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );

			$subject = sprintf(
				/* translators: %1$s: Blog name. %2$s: Post title. */
				_x( '[%1$s] New Editorial Task in “%2$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_title
			);

			$message = sprintf(
				/* translators: %1$s: Post id. %2$s: Post title. %3$s: Post type. */
				_x( 'A new editorial task was added to %3$s #%1$s “%2$s”.', 'text', 'nelio-content' ),
				$post_id,
				$post_title,
				$post_type
			) . "\r\n\r\n";

		} else {

			/* translators: %s: Blog name. */
			$subject = sprintf( _x( '[%s] New Editorial Task', 'text', 'nelio-content' ), $blog_name );
			$message = _x( 'A new editorial task was added.', 'text', 'nelio-content' ) . "\r\n\r\n";

		}

		$message .= sprintf(
			/* translators: %1$s: Task author. %2$s: Task author email. */
			_x( '%1$s (%2$s) created the following task:', 'text', 'nelio-content' ),
			$current_user_name,
			$current_user_email
		) . "\r\n\r\n";

		$message .= $this->get_task_information( $task );
		$message .= $this->get_email_footer( $post );

		return array(
			'type'    => 'task-creation',
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Gets task updated email data.
	 *
	 * @param TEditorial_Task $task Task.
	 * @param WP_Post|null    $post Post.
	 *
	 * @return array{type:'task-completed'|'task-updated',subject:string,message:string}
	 */
	private function get_task_updated_email_data( $task, $post ) {

		$current_user       = wp_get_current_user();
		$current_user_name  = $current_user->display_name;
		$current_user_email = $current_user->user_email;

		$blog_name = get_option( 'blogname' );

		if ( $post ) {
			$post_id    = $post->ID;
			$post_type  = $this->get_post_type_label( $post );
			$post_title = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );

			$subject = sprintf(
				! empty( $task['completed'] )
					/* translators: %1$s: Blog name. %2$s: Post title. */
					? _x( '[%1$s] Editorial Task Completed in “%2$s”', 'text', 'nelio-content' )
					/* translators: %1$s: Blog name. %2$s: Post title. */
					: _x( '[%1$s] Editorial Task Updated in “%2$s”', 'text', 'nelio-content' ),
				$blog_name,
				$post_title
			);

			$message = sprintf(
				! empty( $task['completed'] )
					/* translators: %1$s: Post id. %2$s: Post title. %3$s: Post type. */
					? _x( 'An editorial task was completed in %3$s #%1$s “%2$s”.', 'text', 'nelio-content' )
					/* translators: %1$s: Post id. %2$s: Post title. %3$s: Post type. */
					: _x( 'An editorial task was updated in %3$s #%1$s “%2$s”.', 'text', 'nelio-content' ),
				$post_id,
				$post_title,
				$post_type
			) . "\r\n\r\n";

		} else {

			$subject = sprintf(
				! empty( $task['completed'] )
					/* translators: %s: Blog name. */
					? _x( '[%s] Editorial Task Completed', 'text', 'nelio-content' )
					/* translators: %s: Blog name. */
					: _x( '[%s] Editorial Task Updated', 'text', 'nelio-content' ),
				$blog_name
			);
			$message = ! empty( $task['completed'] )
				? _x( 'An editorial task was completed.', 'text', 'nelio-content' ) . "\r\n\r\n"
				: _x( 'An editorial task was updated.', 'text', 'nelio-content' ) . "\r\n\r\n";

		}

		$message .= sprintf(
			! empty( $task['completed'] )
				/* translators: %1$s: Task author. %2$s: Task author email. */
				? _x( '%1$s (%2$s) completed the following task:', 'text', 'nelio-content' )
				/* translators: %1$s: Task author. %2$s: Task author email. */
				: _x( '%1$s (%2$s) updated the following task:', 'text', 'nelio-content' ),
			$current_user_name,
			$current_user_email
		) . "\r\n\r\n";

		$message .= $this->get_task_information( $task );
		$message .= $this->get_email_footer( $post );

		return array(
			'type'    => ! empty( $task['completed'] ) ? 'task-completed' : 'task-updated',
			'subject' => $subject,
			'message' => $message,
		);
	}

	/**
	 * Gets task information.
	 *
	 * @param TEditorial_Task $task Task.
	 *
	 * @return string
	 */
	private function get_task_information( $task ) {

		$assignee       = get_userdata( $task['assigneeId'] );
		$assignee_name  = _x( 'Unknown Assignee', 'text', 'nelio-content' );
		$assignee_email = '';
		if ( $assignee ) {
			$assignee_name  = $assignee->display_name;
			$assignee_email = $assignee->user_email;
		}

		$assigner       = get_userdata( $task['assignerId'] );
		$assigner_name  = _x( 'Unknown Assignee', 'text', 'nelio-content' );
		$assigner_email = '';
		if ( $assigner ) {
			$assigner_name  = $assigner->display_name;
			$assigner_email = $assigner->user_email;
		}

		/* translators: %s: Task description. */
		$info = ' - ' . sprintf( _x( 'Task: %s', 'text', 'nelio-content' ), $task['task'] ) . "\r\n";
		/* translators: %1$s: User name. %2$s: User email. */
		$info .= ' - ' . sprintf( _x( 'Assignee: %1$s (%2$s)', 'text', 'nelio-content' ), $assignee_name, $assignee_email ) . "\r\n";
		/* translators: %1$s: User name. %2$s: User email. */
		$info .= ' - ' . sprintf( _x( 'Assigner: %1$s (%2$s)', 'text', 'nelio-content' ), $assigner_name, $assigner_email ) . "\r\n";

		if ( $task['dateDue'] ) {
			$task_due_date = mysql2date( get_option( 'date_format' ), $task['dateDue'] );
			/* translators: %s: Date. */
			$info .= ' - ' . sprintf( _x( 'Due Date: %s', 'text', 'nelio-content' ), $task_due_date ) . "\r\n";
		}

		return $info;
	}

	/**
	 * Returns the email footer.
	 *
	 * @param WP_Post|null $post the post. Optional.
	 *
	 * @return string
	 */
	private function get_email_footer( $post = null ) {

		$blog_name = get_option( 'blogname' );
		$blog_url  = get_bloginfo( 'url' );
		$admin_url = admin_url( '/' );

		$footer = '';

		if ( $post ) {

			$post_type        = get_post_type_object( $post->post_type );
			$post_type_labels = ! empty( $post_type ) ? $post_type->labels : null;

			$post_title = ! empty( $post->post_title ) ? $post->post_title : _x( '(no title)', 'text', 'nelio-content' );
			$edit_link  = get_edit_post_link( $post->ID );
			$edit_link  = is_string( $edit_link ) ? $edit_link : '';
			$edit_link  = htmlspecialchars_decode( $edit_link );

			$edit_label = $post_type_labels->edit_item ?? _x( 'Edit', 'command', 'nelio-content' );
			$edit_label = is_string( $edit_label ) ? $edit_label : _x( 'Edit', 'command', 'nelio-content' );

			if ( 'publish' !== $post->post_status ) {
				$view_link = add_query_arg( array( 'preview' => 'true' ), wp_get_shortlink( $post->ID ) );
			} else {
				$view_link = get_permalink( $post->ID );
				$view_link = is_string( $view_link ) ? $view_link : '';
				$view_link = htmlspecialchars_decode( $view_link );
			}

			$view_label = $post_type_labels->view_item ?? _x( 'View', 'command', 'nelio-content' );
			$view_label = is_string( $view_label ) ? $view_label : _x( 'View', 'command', 'nelio-content' );

			$footer .= "\r\n";
			$footer .= _x( '== Actions ==', 'title', 'nelio-content' ) . "\r\n";
			$footer .= sprintf( '%1$s: %2$s', $edit_label, $edit_link ) . "\r\n";
			$footer .= sprintf( '%1$s: %2$s', $view_label, $view_link ) . "\r\n";

			$footer .= "\r\n--------------------\r\n";
			/* translators: %s: Post title. */
			$footer .= sprintf( _x( 'You are receiving this email because you are subscribed to “%s”.', 'user', 'nelio-content' ), $post_title );

		} else {

			$footer .= "\r\n--------------------\r\n";
			/* translators: %s: Blog URL. */
			$footer .= sprintf( _x( 'You are receiving this email because you are registered to %s.', 'user', 'nelio-content' ), $blog_url );

		}

		$footer .= "\r\n\r\n";
		$footer .= $blog_name . ' | ' . $blog_url . ' | ' . $admin_url . "\r\n";

		return $footer;
	}

	/**
	 * Returns post’s scheduled datetime.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_scheduled_datetime( $post ) {

		$scheduled_timestatmp = strtotime( $post->post_date );

		$date = date_i18n( get_option( 'date_format' ), $scheduled_timestatmp );
		$time = date_i18n( get_option( 'time_format' ), $scheduled_timestatmp );

		/* translators: %1$s: Post scheduled date. %2$s: Post scheduled time. */
		return sprintf( _x( '%1$s at %2$s', 'text', 'nelio-content' ), $date, $time );
	}

	/**
	 * Returns post type label.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_post_type_label( $post ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( empty( $post_type ) ) {
			return $post->post_type;
		}

		$labels = $post_type->labels;
		if ( empty( $labels->singular_name ) ) {
			return $post->post_type;
		}

		if ( ! is_string( $labels->singular_name ) ) {
			return $post->post_type;
		}

		return $labels->singular_name;
	}

	/**
	 * Returns post status label.
	 *
	 * @param string $status Status.
	 *
	 * @return string
	 */
	private function get_post_status_label( $status ) {
		$status_object = get_post_status_object( $status );
		return ! empty( $status_object ) && is_string( $status_object->label ) ? $status_object->label : $status;
	}

	/**
	 * Whether followers should be notified.
	 *
	 * If a post type is provided, it returns whether notifications are enabled for said post type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	private function should_followers_be_notified( $post_type = null ) {
		$post_types = nelio_content_get_post_types( 'notifications' );
		if ( empty( $post_types ) ) {
			return false;
		}
		return empty( $post_type ) || in_array( $post_type, $post_types, true );
	}

	/**
	 * Whether comment notifications are enabled.
	 *
	 * If a post type is provided, it returns whether they’re enabled for said post type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	private function are_comment_notifications_enabled( $post_type = null ) {
		$settings = Nelio_Content_Settings::instance();
		if ( empty( $settings->get( 'use_comment_notifications' ) ) ) {
			return false;
		}

		$post_types = nelio_content_get_post_types( 'comments' );
		if ( empty( $post_types ) ) {
			return false;
		}

		return empty( $post_type ) || in_array( $post_type, $post_types, true );
	}

	/**
	 * Whether task notifications are enabled.
	 *
	 * If a post type is provided, it returns whether they’re enabled for said post type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return bool
	 */
	private function are_task_notifications_enabled( $post_type = null ) {
		$settings = Nelio_Content_Settings::instance();
		if ( empty( $settings->get( 'use_task_notifications' ) ) ) {
			return false;
		}

		$post_types = nelio_content_get_post_types( 'tasks' );
		if ( empty( $post_types ) ) {
			return false;
		}

		return empty( $post_type ) || in_array( $post_type, $post_types, true );
	}
}

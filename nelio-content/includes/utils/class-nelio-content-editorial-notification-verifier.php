<?php
/** Verifies native notification requests against saved Cloud records. */
defined( 'ABSPATH' ) || exit;

class Nelio_Content_Editorial_Notification_Verifier {
	/**
	 * @param array<array-key,mixed> $input Untrusted notification attributes.
	 * @return array{id:string,authorId:int,comment:string,date:string,postId:int}|WP_Error
	 */
	public static function comment( $input ) {
		$id   = $input['id'] ?? null;
		$post = $input['postId'] ?? null;
		if ( ! is_string( $id ) || ! wp_is_uuid( $id ) || ! is_int( $post ) || ! self::can_comment( $post ) ) {
			return self::denied();
		}
		$items = self::read( '/post/' . $post . '/items', 'comments' );
		if ( is_wp_error( $items ) ) {
			return $items;
		}
		$record = self::find( $items['comments'], $id );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		if (
			! $record
			|| ! is_int( $record['authorId'] ?? null )
			|| get_current_user_id() !== $record['authorId']
			|| ( $record['postId'] ?? null ) !== $post
			|| ! is_string( $record['comment'] ?? null )
			|| ! is_string( $record['date'] ?? null )
			|| ! self::recent( $record['date'] )
		) {
			return self::denied();
		}
		foreach ( array( 'authorId', 'postId', 'comment', 'date' ) as $key ) {
			if ( ( $input[ $key ] ?? null ) !== $record[ $key ] ) {
				return self::denied();
			}
		}
		if ( ! self::can_comment( $post ) ) {
			return self::denied();
		}
		return array(
			'id'       => $id,
			'authorId' => $record['authorId'],
			'comment'  => $record['comment'],
			'date'     => $record['date'],
			'postId'   => $post,
		);
	}

	/**
	 * @param array<array-key,mixed> $input Untrusted notification attributes.
	 * @param bool                   $is_new Requested creation event.
	 * @return array{id:string,task:string,dateDue:string,assigneeId:int,assignerId:int,completed:bool,postId:int,timestamp:string}|WP_Error
	 */
	public static function task( $input, $is_new ) {
		$id   = $input['id'] ?? null;
		$post = $input['postId'] ?? 0;
		if (
			! self::can_use_plugin()
			|| ! is_string( $id )
			|| ! wp_is_uuid( $id )
			|| ! is_int( $post )
			|| $post < 0
			|| ( $post && ! self::can_read_post( $post ) )
			|| 'none' === Nelio_Content_Admin::instance()->get_task_editor_permission()
		) {
			return self::denied();
		}
		$record = self::find_task( $id, $post, $input['dateDue'] ?? '' );
		if ( is_wp_error( $record ) ) {
			return $record;
		}
		if ( ! $record || ! is_string( $record['timestamp'] ?? null ) || ! self::recent( $record['timestamp'] ) || ( $record['postId'] ?? 0 ) !== $post ) {
			return self::denied();
		}
		$task     = $record['task'] ?? null;
		$due      = $record['dateDue'] ?? '';
		$assignee = $record['assigneeId'] ?? null;
		$assigner = $record['assignerId'] ?? null;
		if (
			! is_string( $task )
			|| ! is_string( $due )
			|| ( $input['task'] ?? null ) !== $task
			|| ( $input['dateDue'] ?? '' ) !== $due
			|| ! is_int( $assignee )
			|| ! is_int( $assigner )
			|| $assignee < 1
			|| $assigner < 1
			|| ( $input['assigneeId'] ?? null ) !== $assignee
			|| ( $input['assignerId'] ?? null ) !== $assigner
		) {
			return self::denied();
		}
		foreach ( array( $assignee, $assigner ) as $user ) {
			if ( ! get_userdata( $user ) || ! is_user_member_of_blog( $user ) ) {
				return self::denied();
			}
		}
		if ( ! is_bool( $record['completed'] ?? null ) || ( $input['completed'] ?? null ) !== $record['completed'] || ! self::can_task( $record, $is_new ) ) {
			return self::denied();
		}
		return array(
			'id'         => $id,
			'task'       => $task,
			'dateDue'    => $due,
			'assigneeId' => $assignee,
			'assignerId' => $assigner,
			'completed'  => $record['completed'],
			'postId'     => $post,
			'timestamp'  => $record['timestamp'],
		);
	}

	/**
	 * @param int $id WordPress post.
	 * @return bool
	 * @phpstan-impure
	 */
	public static function can_comment( $id ) {
		$post = $id > 0 ? get_post( $id ) : null;
		return self::can_use_plugin() && $post && self::can_read_post( $id ) && current_user_can( 'edit_post', $id ) && in_array( $post->post_type, nelio_content_get_post_types( 'comments' ), true );
	}

	/**
	 * @param array<array-key,mixed> $task Saved task.
	 * @param bool                   $is_new Creation event.
	 * @return bool
	 * @phpstan-impure
	 */
	public static function can_task( $task, $is_new ) {
		$post       = $task['postId'] ?? 0;
		$permission = Nelio_Content_Admin::instance()->get_task_editor_permission();
		if ( ! self::can_use_plugin() || 'none' === $permission || ! is_int( $post ) || $post < 0 || ( $post && ! self::can_read_post( $post ) ) ) {
			return false;
		}
		if ( $is_new ) {
			return get_current_user_id() === ( $task['assignerId'] ?? null ) && empty( $task['completed'] ) && ( $post ? current_user_can( 'edit_post', $post ) : 'all' === $permission );
		}
		return 'all' === $permission || get_current_user_id() === ( $task['assigneeId'] ?? null ) || ( $post && current_user_can( 'edit_post', $post ) );
	}

	/**
	 * @param int $id Post.
	 * @return bool
	 */
	private static function can_read_post( $id ) {
		$post = get_post( $id );
		return $post && ! in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) && current_user_can( 'read_post', $id );
	}

	/** @return bool */
	private static function can_use_plugin() {
		return is_user_logged_in() && nelio_content_can_current_user_use_plugin();
	}

	/**
	 * Native callbacks confirm recent saves; an expired transient must not enable old replays.
	 *
	 * @param mixed $date Saved Cloud timestamp.
	 * @return bool
	 */
	private static function recent( $date ) {
		if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $date ) ) {
			return false;
		}
		$time = rest_parse_date( $date );
		return false !== $time && $time <= time() + MINUTE_IN_SECONDS && $time >= time() - 10 * MINUTE_IN_SECONDS;
	}

	/**
	 * @param string $id Task ID.
	 * @param int    $post Related post or zero.
	 * @param mixed  $due Calendar lookup hint; never treated as saved data.
	 * @return array<array-key,mixed>|null|WP_Error
	 */
	private static function find_task( $id, $post, $due ) {
		if ( $post ) {
			$items = self::read( '/post/' . $post . '/items', 'tasks' );
			return is_wp_error( $items ) ? $items : self::find( $items['tasks'], $id );
		}
		$time = is_string( $due ) && '' !== $due ? rest_parse_date( $due ) : false;
		if ( false === $time ) {
			return self::unverified();
		}
		$page = '';
		$seen = array();
		for ( $i = 0; $i < 5; ++$i ) {
			$query = array(
				'from' => gmdate( 'Y-m-d', $time - DAY_IN_SECONDS ),
				'to'   => gmdate( 'Y-m-d', $time + 2 * DAY_IN_SECONDS ),
			);
			if ( $page ) {
				$query['page'] = $page;
			}
			$items = self::read( add_query_arg( $query, '/calendar/tasks' ), 'tasks' );
			if ( is_wp_error( $items ) ) {
				return $items;
			}
			$record = self::find( $items['tasks'], $id );
			if ( $record ) {
				return $record;
			}
			$page = $items['page'] ?? '';
			if ( ! is_string( $page ) || isset( $seen[ $page ] ) ) {
				return self::unverified();
			}
			if ( '' === $page ) {
				return null;
			}
			$seen[ $page ] = true;
		}
		return self::unverified();
	}

	/**
	 * @param mixed  $items Saved collection.
	 * @param string $id Item ID.
	 * @return array<array-key,mixed>|null|WP_Error
	 */
	private static function find( $items, $id ) {
		if ( ! is_array( $items ) || count( $items ) > 1000 ) {
			return self::unverified();
		}
		$found = null;
		foreach ( $items as $item ) {
			if ( is_array( $item ) && ( $item['id'] ?? null ) === $id ) {
				if ( null !== $found ) {
					return self::unverified();
				}
				$found = $item;
			}
		}
		return $found;
	}

	/**
	 * Available on supported WordPress versions even without the Abilities API.
	 *
	 * @param string $path Site-relative Cloud path.
	 * @param string $collection Required list field.
	 * @return array<array-key,mixed>|WP_Error
	 */
	private static function read( $path, $collection ) {
		$site  = nelio_content_get_site_id();
		$token = nelio_content_generate_api_auth_token( 'skip-errors' );
		if ( ! self::can_use_plugin() || '' === $site || '' === $token || 'unauthorized' === $token ) {
			return self::unverified();
		}
		$response = wp_remote_get(
			nelio_content_get_api_url( '/site/' . rawurlencode( $site ) . $path, 'wp' ),
			array(
				'limit_response_size' => 2 * MB_IN_BYTES,
				'timeout'             => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
				'headers'             => array(
					'Authorization' => 'Bearer ' . $token,
					'accept'        => 'application/json',
				),
			)
		);
		if (
			is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) < 200
			|| wp_remote_retrieve_response_code( $response ) >= 300
			|| strlen( wp_remote_retrieve_body( $response ) ) > 2 * MB_IN_BYTES
		) {
			return self::unverified();
		}
		$object = json_decode( wp_remote_retrieve_body( $response ) );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );
		return $object instanceof stdClass && isset( $object->$collection ) && is_array( $object->$collection ) && is_array( $data ) ? $data : self::unverified();
	}

	/** @return WP_Error */
	public static function denied() {
		return new WP_Error( 'nelio_content_notification_forbidden', _x( 'You cannot notify this editorial item with the supplied data and current permissions.', 'text', 'nelio-content' ), array( 'status' => 403 ) );
	}

	/** @return WP_Error */
	private static function unverified() {
		return new WP_Error( 'nelio_content_notification_unverified', _x( 'The saved editorial item could not be verified. No notification was sent.', 'text', 'nelio-content' ), array( 'status' => 503 ) );
	}
}

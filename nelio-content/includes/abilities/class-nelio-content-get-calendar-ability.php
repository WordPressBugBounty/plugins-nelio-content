<?php
/**
 * Registration, authorization and execution of the get-calendar ability.
 */

use function Nelio_Content\Abilities\validate_date_range;
use function Nelio_Content\Abilities\decode_cursor;
use function Nelio_Content\Abilities\query_social_messages;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-calendar ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Calendar_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-calendar ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties              = $this->query_properties();
		$input_properties['item_type'] = array(
			'type'        => 'string',
			'enum'        => array( 'posts', 'social' ),
			'description' => 'Select one independently paginated calendar stream; defaults to posts.',
		);
		$input_properties['statuses']  = $this->array_schema(
			array(
				'type'        => 'string',
				'description' => 'One status from the selected calendar stream.',
				'enum'        => array_values( array_unique( array_merge( get_post_stati(), array( 'schedule', 'error' ) ) ) ),
			),
			'Include any of these statuses in the selected stream. Use WordPress post statuses for posts and draft, schedule, publish or error for social messages.'
		);
		$input_properties['authors']   = $this->array_schema(
			array(
				'type'        => 'integer',
				'description' => 'WordPress user ID of an author to include.',
				'minimum'     => 1,
			),
			'Include items associated with any of these WordPress author IDs. For social messages, this refers to the related post author.'
		);
		$item_schema                   = $this->object_schema(
			array(
				'id'           => array(
					'type'        => array( 'integer', 'string' ),
					'description' => 'WordPress post ID for a post item, or social message UUID for a social item. Interpret together with item_type.',
				),
				'item_type'    => array(
					'type'        => 'string',
					'enum'        => array( 'post', 'social' ),
					'description' => 'Item kind: post for WordPress posts or social for social messages for individual profiles.',
				),
				'title'        => array(
					'type'        => 'string',
					'description' => 'WordPress post title or the original social message text.',
				),
				'status'       => array(
					'type'        => 'string',
					'description' => 'Item status: a WordPress post status for post items or draft, schedule, publish or error for social items.',
				),
				'scheduled_at' => array(
					'type'        => 'string',
					'description' => 'Post publication timestamp with timezone offset, or the Cloud social publication timestamp. Empty when no social schedule is available.',
				),
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'Related WordPress post ID. For post items this equals id; for unrelated social messages it is 0.',
				),
				'author_id'    => array(
					'type'        => 'integer',
					'description' => 'WordPress author ID for a post item. Omitted from social calendar items.',
				),
				'profile_id'   => array(
					'type'        => 'string',
					'description' => 'Connected social profile ID for a social item. Omitted from WordPress post items.',
				),
			),
			array( 'id', 'item_type', 'title', 'status', 'scheduled_at' ),
			'One WordPress post or social message in the calendar. Interpret its ID and status according to item_type.'
		);

		wp_register_ability(
			'nelio-content/get-calendar',
			array(
				'label'               => _x( 'Get editorial calendar', 'command', 'nelio-content' ),
				'description'         => 'Retrieve one page of posts or social messages for inclusive site-local calendar days. Select item_type for each stream. Tasks are available through the Premium task ability. Dates include offsets; unscheduled posts are excluded.',
				'category'            => 'nelio-content-calendar',
				'input_schema'        => $this->object_schema( $input_properties, array( 'start_date', 'end_date' ), 'Inclusive site-local date range, calendar stream and optional filters. Posts and social messages are requested in separate pages; unscheduled posts are excluded.' ),
				'output_schema'       => $this->page_schema( $item_schema, 'One page of posts or social messages the current user can access with their IDs and publication dates. Each item_type is requested separately; the response does not give a combined total.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Checks whether the current user may execute this ability.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Calendar streams use independent pagination; never report partial aggregate counts.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		$valid = validate_date_range( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( 'social' === ( $input['item_type'] ?? 'posts' ) ) {
			$page = query_social_messages( $input, $this->message_schema() );
			if ( is_wp_error( $page ) ) {
				return $page;
			}
			$page['items'] = array_values( array_filter( $page['items'], fn( $message ) => empty( $input['statuses'] ) || in_array( $message['status'], $input['statuses'], true ) ) );
			$page['items'] = array_map(
				fn( $message ) => array(
					'id'           => $message['id'],
					'item_type'    => 'social',
					'title'        => $message['text'],
					'status'       => $message['status'],
					'scheduled_at' => $message['schedule'] ?? '',
					'post_id'      => $message['postId'] ?? 0,
					'profile_id'   => $message['profileId'],
				),
				$page['items']
			);
			return $page;
		}
		$cursor = decode_cursor( $input );
		if ( is_wp_error( $cursor ) ) {
			return $cursor;
		}
		$limit      = $input['per_page'] ?? 50;
		$query_args = array(
			'post_type'      => nelio_content_get_post_types( 'calendar' ),
			'post_status'    => $input['statuses'] ?? 'any',
			'posts_per_page' => $limit,
			'offset'         => $cursor['offset'],
			'orderby'        => array(
				'date' => 'ASC',
				'ID'   => 'ASC',
			),
			'date_query'     => array(
				'after'     => $input['start_date'] ?? '',
				'before'    => ( $input['end_date'] ?? '' ) . ' 23:59:59',
				'inclusive' => true,
			),
		);
		if ( ! empty( $input['authors'] ) ) {
			$query_args['author__in'] = $input['authors'];
		}
		if ( ! empty( $input['post_id'] ) ) {
			$query_args['p'] = $input['post_id'];
		}
		if ( empty( $query_args['post_type'] ) ) {
			return array(
				'items'       => array(),
				'has_more'    => false,
				'next_cursor' => '',
				'timezone'    => wp_timezone_string(),
			);
		}
		$query      = new WP_Query( $query_args );
		$items      = array();
		$controller = Nelio_Content_Post_REST_Controller::instance();
		foreach ( $query->posts ?? array() as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt || ( ! current_user_can( 'read_post', $post->ID ) && ! $controller->can_current_user_view_calendar_post( $post->ID ) ) ) {
				continue;
			}
			$date = get_post_datetime( $post );
			if ( ! $date ) {
				continue;
			}
			$items[] = array(
				'id'           => $post->ID,
				'item_type'    => 'post',
				'post_id'      => $post->ID,
				'title'        => wp_strip_all_tags( $post->post_title ),
				'status'       => $post->post_status,
				'scheduled_at' => $date->format( DATE_ATOM ),
				'author_id'    => (int) $post->post_author,
			);
		}
		$more    = $cursor['offset'] + $limit < $query->found_posts;
		$encoded = wp_json_encode(
			array(
				'page'   => '',
				'offset' => $cursor['offset'] + $limit,
			)
		);
		if ( false === $encoded ) {
			return new WP_Error( 'nelio_content_invalid_cursor', 'Unable to create the cursor value for the next page.' );
		}
		return array(
			'items'       => $items,
			'has_more'    => $more,
			'next_cursor' => $more ? base64_encode( $encoded ) : '',
			'timezone'    => wp_timezone_string(),
		);
	}
}

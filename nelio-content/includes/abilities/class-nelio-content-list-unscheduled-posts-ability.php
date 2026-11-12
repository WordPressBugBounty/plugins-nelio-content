<?php
/**
 * Lists posts without a publication date using the calendar's shared search.
 */

use function Nelio_Content\Abilities\decode_cursor;
use function Nelio_Content\Abilities\local_post_page;
use function Nelio_Content\Abilities\summarize_post;

defined( 'ABSPATH' ) || exit;

/**
 * Implements read-only discovery of unscheduled calendar content in both editions.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_List_Unscheduled_Posts_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the bounded unscheduled-post query.
	 *
	 * @return void
	 */
	public function register() {
		$properties              = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['query']     = array(
			'type'        => 'string',
			'maxLength'   => 200,
			'description' => 'Optional title fragment, WordPress post ID or local post URL. Exact matches must also have no publication date. Leading and trailing whitespace is ignored.',
		);
		$properties['post_type'] = array(
			'type'        => 'string',
			'minLength'   => 1,
			'maxLength'   => 64,
			'description' => 'Optional WordPress post type enabled in the editorial calendar. Omit to include all enabled calendar post types.',
		);
		wp_register_ability(
			'nelio-content/list-unscheduled-posts',
			array(
				'label'               => _x( 'List unscheduled posts', 'command', 'nelio-content' ),
				'description'         => 'Find readable WordPress posts without a publication date among the post types enabled in the editorial calendar. Available in Free and Premium. Unscheduled is determined by the absence of a publication date, not by draft status alone. Optionally search titles or supply an exact post ID or local URL. Exact matches must also be unscheduled. Results are returned one page at a time, ordered by saved post date, newest first, with exact matches first. Follow next_cursor even after an empty page while has_more is true. Does not change posts or generate social messages.',
				'category'            => 'nelio-content-calendar',
				'input_schema'        => $this->object_schema( $properties, array(), 'Optional search and calendar post type filters with results returned one page at a time with page size limits for posts without publication dates.' ),
				'output_schema'       => $this->page_schema( $this->post_summary_schema(), 'One page of readable unscheduled posts. publication_date is empty for every item. Continue with unchanged filters when next_cursor is present.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Requires authenticated plugin access before local queries.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return bool
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Reads one source page before applying per-post visibility.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', 'You cannot list unscheduled posts.' );
		}
		$cursor = decode_cursor( $input );
		if ( is_wp_error( $cursor ) ) {
			return $cursor;
		}
		if ( '' !== $cursor['page'] ) {
			return new WP_Error( 'nelio_content_invalid_cursor', 'Use the cursor value from the previous response to this post query.' );
		}
		$types = nelio_content_get_post_types( 'calendar' );
		if ( isset( $input['post_type'] ) ) {
			if ( ! in_array( $input['post_type'], $types, true ) ) {
				return new WP_Error( 'nelio_content_invalid_post_type', 'The requested post type is not enabled in the editorial calendar.' );
			}
			$types = array( $input['post_type'] );
		}
		$limit = $input['per_page'] ?? 50;
		$page  = Nelio_Content_Post_REST_Controller::instance()->query_unscheduled_posts( $input['query'] ?? '', $cursor['offset'], $limit, $types );
		$items = array();
		foreach ( $page['posts'] as $post ) {
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt && in_array( $post->post_type, $types, true ) && current_user_can( 'read_post', $post->ID ) ) {
				$items[] = summarize_post( $post );
			}
		}
		return local_post_page( $items, $cursor['offset'] + $limit, $page['has_more'] );
	}
}

<?php
/** Premium series-membership contract shared with Free discovery. */
defined( 'ABSPATH' ) || exit;

/** Reads bounded pages of accessible posts in a series. */
class Nelio_Content_List_Series_Posts_Ability extends Nelio_Content_Series_Ability {
	/** @return void */
	public function register() {
		$properties                        = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['series_id']           = $this->series_id_schema();
		$item                              = $this->post_summary_schema();
		$item['properties']['part_number'] = array(
			'type'        => array( 'integer', 'null' ),
			'minimum'     => 1,
			'description' => 'Saved positive decimal part number without leading zeros, or null when missing or invalid. This does not establish its position among all posts in the series.',
		);
		$item['required'][]                = 'part_number';
		wp_register_ability(
			'nelio-content/list-series-posts',
			array(
				'label'               => _x( 'List series posts', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium. List readable posts in one series from the configured taxonomy and enabled attached content types. Requires taxonomy assignment and content editing capability. Saved positive decimal part numbers without leading zeros sort first in ascending order, followed by posts with missing or invalid numbers. Publication date ascending and then post ID ascending break ties in both groups. Duplicate part metadata uses its first saved value. The response does not estimate part positions or return content, metadata or totals. Trash, auto-drafts, revisions and inaccessible posts are omitted. Each page checks at most 100 posts before checking whether the current user may read each post; follow next_cursor even after an empty page while has_more is true. Records may change between page requests. The search can skip at most 100000 records. Keep series_id and per_page unchanged. Reads do not change series ordering, render content or contact external services.',
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, array( 'series_id' ), 'One existing series and optional page size and continuation values. The configured taxonomy and enabled content types are resolved automatically.' ),
				'output_schema'       => $this->page_schema( $item, 'Accessible post summaries and saved part numbers in one page of records checked for this query. has_more means there are more posts to check, not that the user can access more posts.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-series-posts';
	}
}

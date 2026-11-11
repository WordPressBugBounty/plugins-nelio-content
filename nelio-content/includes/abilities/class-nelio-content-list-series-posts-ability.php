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
			'description' => _x( 'Saved positive decimal part number without leading zeros, or null when missing or invalid. This is not an inferred position among all series posts.', 'text', 'nelio-content' ),
		);
		$item['required'][]                = 'part_number';
		wp_register_ability(
			'nelio-content/list-series-posts',
			array(
				'label'               => _x( 'List series posts', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. List readable posts in one series from the configured taxonomy and enabled attached content types. Requires taxonomy assignment and content editing capability. Saved positive decimal part numbers without leading zeros sort first in ascending order, followed by posts with missing or invalid numbers. Publication date ascending and then post ID ascending break ties in both groups. Duplicate part metadata uses its first saved value. No inferred part positions, content, metadata or totals are returned. Trash, auto-drafts, revisions and inaccessible posts are omitted. Each page examines at most 100 source posts before per-post visibility checks; follow next_cursor even after an empty page while has_more is true. Pagination is not a snapshot and supports offsets up to 100000. Keep series_id and per_page unchanged. Reads do not change series ordering, render content or contact external services.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, array( 'series_id' ), _x( 'One existing series and optional bounded pagination. The configured taxonomy and enabled content types are resolved automatically.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->page_schema( $item, _x( 'Accessible post summaries and saved part numbers in a bounded source page. has_more describes remaining source records, not a visible-post total.', 'text', 'nelio-content' ) ),
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

<?php
/** Premium contract for reorder-series-posts. */
defined( 'ABSPATH' ) || exit;

/** Exposes an explicit series mutation in the shared catalog. */
class Nelio_Content_Reorder_Series_Posts_Ability extends Nelio_Content_Series_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties                   = array( 'series_id' => $this->series_id_schema() );
		$properties['post_ids']       = array(
			'type'        => 'array',
			'maxItems'    => 100,
			'uniqueItems' => true,
			'items'       => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
			'description' => 'Complete series member IDs in the desired order. Parts become 1..N. Empty only for an empty series.',
		);
		$properties['expected_parts'] = array(
			'type'        => 'array',
			'maxItems'    => 100,
			'items'       => $this->object_schema(
				array(
					'post_id'     => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'One current series member ID.',
					),
					'part_number' => $this->part_schema(),
				),
				array( 'post_id', 'part_number' ),
				'Saved part number returned by the last read for this member, or null when unset. Exactly one entry per member.'
			),
			'description' => 'Expected complete saved parts from current series-post reads. If the saved part numbers or membership have changed since they were read, reordering is rejected before anything is saved.',
		);
		$this->register_mutation( 'reorder-series-posts', 'Reorder series posts', 'Assign consecutive part numbers starting at 1 to every post in the series, at most 100 posts, with term-edit, taxonomy assignment and read/edit permission for every member. Supply expected_parts from current series-post reads to detect part numbers that changed since they were read, and post_ids in the desired order. IDs must be unique and match the full membership. If the list is incomplete, includes hidden posts, no longer matches the saved series or exceeds the size limit, the request fails before saving changes. An empty order is allowed only for an empty series. If the resulting part numbers are already saved, nothing is changed. Duplicate or malformed per-post part metadata is rejected.', $properties, array( 'series_id', 'post_ids', 'expected_parts' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/reorder-series-posts';
	}
}

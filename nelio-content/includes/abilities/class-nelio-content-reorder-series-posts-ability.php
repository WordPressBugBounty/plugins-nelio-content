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
			'description' => _x( 'Complete series member IDs in the desired order. Parts become 1..N. Empty only for an empty series.', 'text', 'nelio-content' ),
		);
		$properties['expected_parts'] = array(
			'type'        => 'array',
			'maxItems'    => 100,
			'items'       => $this->object_schema(
				array(
					'post_id'     => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => _x( 'One current series member ID.', 'text', 'nelio-content' ),
					),
					'part_number' => $this->part_schema(),
				),
				array( 'post_id', 'part_number' ),
				_x( 'Current saved part observed for this member, or null when unset. Exactly one entry per member.', 'text', 'nelio-content' )
			),
			'description' => _x( 'Expected complete saved parts from current series-post reads. Stale numbers or membership reject the reorder before writes.', 'text', 'nelio-content' ),
		);
		$this->register_mutation( 'reorder-series-posts', _x( 'Reorder series posts', 'command', 'nelio-content' ), _x( 'Set parts 1..N for an explicit complete series collection, at most 100 posts, with term-edit, taxonomy assignment and read/edit permission for every member. Supply expected_parts from current series-post reads to detect stale saved numbers, and post_ids in the desired order. IDs must be unique and match the full membership. Missing, hidden, stale or oversized collections fail before writes. An empty order is allowed only for an empty series. Equal resulting parts are a no-op. Duplicate or malformed per-post part metadata is rejected.', 'text', 'nelio-content' ), $properties, array( 'series_id', 'post_ids', 'expected_parts' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/reorder-series-posts';
	}
}

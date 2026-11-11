<?php
/** Premium contract for add-post-to-series. */
defined( 'ABSPATH' ) || exit;

/** Exposes an explicit series mutation in the shared catalog. */
class Nelio_Content_Add_Post_To_Series_Ability extends Nelio_Content_Series_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties                = array(
			'series_id' => $this->series_id_schema(),
			'post_id'   => $this->query_properties()['post_id'],
		);
		$properties['part_number'] = array(
			'type'        => 'integer',
			'minimum'     => 1,
			'maximum'     => 1000000,
			'description' => _x( 'Optional positive part number up to 1000000. Omit to preserve the saved part, including when membership already exists.', 'text', 'nelio-content' ),
		);
		$this->register_mutation( 'add-post-to-series', _x( 'Add post to series', 'command', 'nelio-content' ), _x( 'Add one readable/editable enabled post to this series with taxonomy assignment permission, preserving every other series. Optional part_number replaces this series’s saved part; omission preserves it. Repeating an existing membership with the same requested part is a no-op.', 'text', 'nelio-content' ), $properties, array( 'series_id', 'post_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/add-post-to-series';
	}
}

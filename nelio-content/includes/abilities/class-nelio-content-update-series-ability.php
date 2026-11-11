<?php
/** Premium contract for update-series. */
defined( 'ABSPATH' ) || exit;

/** Exposes an explicit series mutation in the shared catalog. */
class Nelio_Content_Update_Series_Ability extends Nelio_Content_Series_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties = array_merge( array( 'series_id' => $this->series_id_schema() ), $this->term_properties() );
		$this->register_mutation( 'update-series', _x( 'Update series', 'command', 'nelio-content' ), _x( 'Partially edit an existing series name, description or slug with its actual term-edit capability. Supply at least one field. Omitted fields and other term attributes are preserved; equal values are a no-op. Name/slug conflicts fail.', 'text', 'nelio-content' ), $properties, array( 'series_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/update-series';
	}
}

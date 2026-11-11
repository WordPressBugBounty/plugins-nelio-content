<?php
/** Premium contract for create-series. */
defined( 'ABSPATH' ) || exit;

/** Exposes an explicit series mutation in the shared catalog. */
class Nelio_Content_Create_Series_Ability extends Nelio_Content_Series_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties = $this->term_properties();
		$this->register_mutation( 'create-series', _x( 'Create series', 'command', 'nelio-content' ), _x( 'Create one series with a plain name and optional description/slug. Requires the taxonomy’s actual manage-terms capability. Existing names or slugs are conflicts, not a request to reuse or alter an existing term.', 'text', 'nelio-content' ), $properties, array( 'name' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-series';
	}
}

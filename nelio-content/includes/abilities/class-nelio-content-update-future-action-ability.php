<?php
/** Shared Premium contract for update-future-action. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit future-action mutation. */
class Nelio_Content_Update_Future_Action_Ability extends Nelio_Content_Future_Action_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties              = $this->mutation_properties();
		$properties['action_id'] = $this->action_id_schema();
		$this->register_mutation( 'update-future-action', _x( 'Update future action', 'command', 'nelio-content' ), _x( 'Partially update one pending action, preserving its ID, target and author. Supply at least one mutable field; omitted fields remain saved. Executed actions cannot be reactivated. Equal requests verify or repair the matching event without resetting a published-post relative anchor.', 'text', 'nelio-content' ), $properties, array( 'action_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/update-future-action'; }
}

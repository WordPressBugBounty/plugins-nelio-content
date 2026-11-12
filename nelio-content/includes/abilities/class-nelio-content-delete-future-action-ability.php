<?php
/** Shared Premium contract for delete-future-action. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit future-action mutation. */
class Nelio_Content_Delete_Future_Action_Ability extends Nelio_Content_Future_Action_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties              = array();
		$properties['action_id'] = $this->action_id_schema();
		$this->register_mutation( 'delete-future-action', 'Delete future action', 'Cancel all matching scheduled events and permanently delete only the action record with its deletion permission. Never deletes the target post or other actions. Missing/inconsistent records fail rather than reporting an unverified repeated deletion.', $properties, array( 'action_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/delete-future-action'; }
}

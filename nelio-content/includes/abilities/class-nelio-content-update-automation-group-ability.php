<?php
/** Shared Premium contract for update-automation-group. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit saved group change. */
class Nelio_Content_Update_Automation_Group_Ability extends Nelio_Content_Automation_Group_Mutation_Ability {
	/** @return void */
	public function register() {
		$this->register_mutation( 'update-automation-group', _x( 'Update automation group', 'command', 'nelio-content' ), _x( 'Partially update one saved automation group, preserving omitted settings. Universal permits weights, publication rules and profile/network configuration, but has no editable name or content selectors.', 'text', 'nelio-content' ), array( 'group_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/update-automation-group';
	}
}

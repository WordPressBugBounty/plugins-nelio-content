<?php
/** Shared Premium contract for delete-automation-group. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit saved group change. */
class Nelio_Content_Delete_Automation_Group_Ability extends Nelio_Content_Automation_Group_Mutation_Ability {
	/** @return void */
	public function register() {
		$this->register_mutation( 'delete-automation-group', _x( 'Delete automation group', 'command', 'nelio-content' ), _x( 'Delete one identified regular automation group while preserving every other returned group. The universal group cannot be deleted.', 'text', 'nelio-content' ), array( 'group_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/delete-automation-group';
	}
}

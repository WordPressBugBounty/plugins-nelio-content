<?php
/** Shared Premium contract for create-automation-group. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit saved group change. */
class Nelio_Content_Create_Automation_Group_Ability extends Nelio_Content_Automation_Group_Mutation_Ability {
	/** @return void */
	public function register() {
		$this->register_mutation( 'create-automation-group', _x( 'Create automation group', 'command', 'nelio-content' ), _x( 'Create one regular automation group with a new controlled identity. Name and content type are required; omitted settings use empty selectors/templates and an always publication rule.', 'text', 'nelio-content' ), array( 'name', 'post_type' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-automation-group';
	}
}

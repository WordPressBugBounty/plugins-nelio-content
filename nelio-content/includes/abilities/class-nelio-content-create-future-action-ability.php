<?php
/** Shared Premium contract for create-future-action. */
defined( 'ABSPATH' ) || exit;

/** Defines one explicit future-action mutation. */
class Nelio_Content_Create_Future_Action_Ability extends Nelio_Content_Future_Action_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties            = $this->mutation_properties();
		$properties['post_id'] = $this->query_properties()['post_id'];
		$this->register_mutation( 'create-future-action', 'Create future action', 'Create one pending future action for a readable/editable post with an explicit effect and date/time rules. Requires action-creation permission. Relative rules for an undated post may remain unscheduled until its date is set.', $properties, array( 'post_id', 'type', 'date_type', 'date_value', 'time_type', 'time_value' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-future-action'; }
}

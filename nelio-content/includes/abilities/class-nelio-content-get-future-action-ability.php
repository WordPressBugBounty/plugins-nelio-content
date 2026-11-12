<?php
/** Premium saved action lookup contract. */
defined( 'ABSPATH' ) || exit;

/** Reads one verified future action. */
class Nelio_Content_Get_Future_Action_Ability extends Nelio_Content_Future_Action_Ability {
	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/get-future-action',
			array(
				'label'               => _x( 'Get future action', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium. Inspect one saved future action, its authorized configuration, date rules, saved schedule, record date, recorded status and current next WP-Cron event. Requires read and edit access to the action and its existing target post, whose type must have future actions enabled. The action’s parent post must match the post identified in its saved data. Actions with no valid related post, invalid saved data or an unsupported format return an error without exposing their contents. Custom-field names and values are never included. Recorded execution does not prove success. Never executes, recalculates, repairs, saves or schedules actions, renders content or contacts external services.',
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( array( 'action_id' => $this->action_id_schema() ), array( 'action_id' ), 'One saved future-action record ID; its target is verified from stored relationships.' ),
				'output_schema'       => $this->action_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-future-action';
	}
}

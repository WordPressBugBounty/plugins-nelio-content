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
				'description'         => _x( 'Requires Nelio Content Premium. Inspect one saved future action, its authorized configuration, date rules, saved schedule, record date, recorded status and current next WP-Cron event. Requires read and edit access to the action and its existing target post, whose type must have future actions enabled. Parent and payload relationships must agree. Standalone, orphaned, corrupt and unsupported records fail without exposing their content. Custom-field keys and values are always redacted. Recorded execution does not prove success. Never executes, recalculates, repairs, saves or schedules actions, renders content or contacts external services.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( array( 'action_id' => $this->action_id_schema() ), array( 'action_id' ), _x( 'One saved future-action record ID; its target is verified from stored relationships.', 'text', 'nelio-content' ) ),
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

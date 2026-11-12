<?php
/** Contract for complete-editorial-task. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium complete task operation. */
class Nelio_Content_Complete_Editorial_Task_Ability extends Nelio_Content_Editorial_Task_Ability {

	/** @return void */
	public function register() {
		$properties              = $this->lookup_properties();
		$metadata                = $this->get_premium_ability_metadata( false );
		$metadata['annotations'] = array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		);
		wp_register_ability(
			'nelio-content/complete-editorial-task',
			array(
				'label'               => _x( 'Complete editorial task', 'command', 'nelio-content' ),
				'description'         => 'Mark one existing editorial task as completed without toggling or reopening it. Requires Nelio Content Premium and task access through assignment, unrestricted task permission or related-post editing. Supply post_id or the current due-date range to locate it on one page of results. If not found, no change is made; follow next_cursor from the error until exhausted. An already completed task returns unchanged without a write or duplicate notification. Normal completion notifications may be sent after a confirmed change. Inspect the task before retrying a failed request.',
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->lookup_input_schema( $properties ),
				'output_schema'       => $this->task_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $metadata,
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/complete-editorial-task';
	}
}

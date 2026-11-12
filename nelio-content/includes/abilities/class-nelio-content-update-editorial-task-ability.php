<?php
/** Contract for update-editorial-task. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium update task operation. */
class Nelio_Content_Update_Editorial_Task_Ability extends Nelio_Content_Editorial_Task_Ability {

	/** @return void */
	public function register() {
		$properties              = $this->lookup_properties();
		$properties              = array_merge( $properties, $this->changes_properties() );
		$metadata                = $this->get_premium_ability_metadata( false );
		$metadata['annotations'] = array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		);
		wp_register_ability(
			'nelio-content/update-editorial-task',
			array(
				'label'               => _x( 'Update editorial task', 'command', 'nelio-content' ),
				'description'         => 'Change the description, assignee, due-date rule or color of one existing editorial task. Requires Nelio Content Premium and access through assignment, unrestricted task permission or related-post editing. Supply post_id or the current due-date range to locate the task on one page of results. If not found, no change is made; follow next_cursor from the error until exhausted. Omitted fields, completion state and related post are preserved. Normal task update notifications may be sent after success. A failed request may already have changed the task; inspect it before retrying.',
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
		return 'nelio-content/update-editorial-task';
	}
}

<?php
/** Contract for create-editorial-task. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium create task operation. */
class Nelio_Content_Create_Editorial_Task_Ability extends Nelio_Content_Editorial_Task_Ability {

	/** @return void */
	public function register() {
		$properties            = $this->changes_properties();
		$properties['post_id'] = array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Optional related WordPress post that the current user can edit. Omit only when unrestricted task permission allows creating standalone tasks. The post association cannot be changed by update-editorial-task.',
		);
		wp_register_ability(
			'nelio-content/create-editorial-task',
			array(
				'label'               => _x( 'Create editorial task', 'command', 'nelio-content' ),
				'description'         => 'Create one pending editorial task. Requires Nelio Content Premium and permission to create tasks for the related post, or unrestricted task permission for standalone tasks. Defaults to the current user as assignee and the site timezone. Supports exact and relative due dates. Normal assignment notifications may be sent after confirmed creation. A failed request may already have created the task; inspect the calendar before retrying.',
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->object_schema( $properties, array( 'task', 'date_value' ), 'Task attributes and identification required for this editorial task operation.' ),
				'output_schema'       => $this->task_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-editorial-task';
	}
}

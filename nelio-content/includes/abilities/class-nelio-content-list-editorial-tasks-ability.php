<?php
/**
 * Shared contract and Premium dispatch for the list-editorial-tasks ability.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the ability in Free and delegates execution to the Premium plugin.
 */
class Nelio_Content_List_Editorial_Tasks_Ability extends Nelio_Content_Editorial_Task_Ability {

	/**
	 * Registers the list-editorial-tasks ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties                = $this->query_properties();
		$input_properties['assignee_id'] = array(
			'description' => 'Include tasks assigned to this WordPress user ID.',
			'type'        => 'integer',
			'minimum'     => 1,
		);
		$input_properties['status']      = array(
			'description' => 'Match pending tasks with completed false or completed tasks with completed true.',
			'type'        => 'string',
			'enum'        => array( 'pending', 'completed' ),
		);

		wp_register_ability(
			'nelio-content/list-editorial-tasks',
			array(
				'label'               => _x( 'List editorial tasks', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium. Retrieve one page of editorial tasks due in an inclusive site-local date range. Visibility respects task editor permission, assignment and related post access. Due dates retain their timezone field.',
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->object_schema( $input_properties, array( 'start_date', 'end_date' ), 'Inclusive site-local due-date range and optional post, assignee and completion filters for visible Premium editorial tasks.' ),
				'output_schema'       => $this->page_schema( $this->task_schema(), 'One page of tasks visible through task permissions, assignment or related post editing access. Task due dates retain their own timezone.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}

	/**
	 * Identifies the Premium handler required by this contract.
	 *
	 * @return string
	 */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-editorial-tasks';
	}
}

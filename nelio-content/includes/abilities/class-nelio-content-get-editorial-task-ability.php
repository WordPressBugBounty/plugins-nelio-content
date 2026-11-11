<?php
/** Contract for get-editorial-task. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium get task operation. */
class Nelio_Content_Get_Editorial_Task_Ability extends Nelio_Content_Editorial_Task_Ability {

	/** @return void */
	public function register() {
		$properties = $this->lookup_properties();
		wp_register_ability(
			'nelio-content/get-editorial-task',
			array(
				'label'               => _x( 'Get editorial task', 'command', 'nelio-content' ),
				'description'         => _x( 'Find one visible editorial task on a bounded page for one readable post or within an inclusive due-date range of at most 31 days. Requires Nelio Content Premium and task access. Follow next_cursor until found or has_more is false; an empty page is inconclusive while has_more is true. Use post_id to find related tasks without due dates; these do not appear in date searches.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->lookup_input_schema( $properties ),
				'output_schema'       => $this->page_schema( $this->task_schema(), _x( 'At most one matching visible task and continuation information for the remaining date range.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( true ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-editorial-task';
	}
}

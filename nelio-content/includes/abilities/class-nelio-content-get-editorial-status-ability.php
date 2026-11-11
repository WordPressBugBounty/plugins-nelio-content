<?php
/** Discoverable contract for a post's publication state and visible task counts. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium editorial summary without loading paid execution. */
class Nelio_Content_Get_Editorial_Status_Ability extends Nelio_Content_Editorial_Task_Ability {

	const MAX_TASKS = 1000;

	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/get-editorial-status',
			array(
				'label'               => _x( 'Get editorial status', 'command', 'nelio-content' ),
				'description'         => _x( 'Summarize one readable post’s saved publication state and visible editorial tasks. Requires Nelio Content Premium and task access. Counts completed and pending tasks, separating pending tasks into overdue, due today, upcoming and undated. Due dates are calendar days in each task’s timezone; a task due today is not overdue. Includes tasks without due dates and applies the same task permissions as task editing. Counts cover only tasks accessible to this user, not necessarily all tasks for the post. No pending visible tasks does not establish publication readiness or quality. Reads at most 1000 source tasks and a 2 MiB response; unverifiable or oversized collections return an error without partial counts. No content is rendered and no tasks or dates are changed.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->object_schema(
					array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Existing readable WordPress post whose saved publication state and accessible editorial tasks should be summarized. No due-date range is required.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id' ),
					_x( 'Identify one readable post. Task permissions further restrict the counts returned.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->summary_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( true ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-editorial-status';
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return isset( $input['post_id'] ) && $input['post_id'] > 0 && parent::check_operation_permissions( $input );
	}

	/** @return array<string,mixed> */
	private function summary_schema() {
		$counts       = array();
		$descriptions = array(
			'total'     => _x( 'All visible task records for this post. Equals pending plus completed.', 'text', 'nelio-content' ),
			'pending'   => _x( 'Visible tasks that are not completed. Equals overdue plus due_today plus upcoming plus undated.', 'text', 'nelio-content' ),
			'completed' => _x( 'Visible tasks explicitly marked completed, regardless of their due date.', 'text', 'nelio-content' ),
			'overdue'   => _x( 'Pending tasks whose due calendar day is earlier than today in the task’s timezone.', 'text', 'nelio-content' ),
			'due_today' => _x( 'Pending tasks due on today’s calendar day in the task’s timezone, regardless of the stored hour.', 'text', 'nelio-content' ),
			'upcoming'  => _x( 'Pending tasks due after today’s calendar day in the task’s timezone.', 'text', 'nelio-content' ),
			'undated'   => _x( 'Pending tasks with no recorded due date, including unresolved dates relative to an unscheduled post. Dates are not predicted.', 'text', 'nelio-content' ),
		);
		foreach ( $descriptions as $key => $description ) {
			$counts[ $key ] = array(
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => self::MAX_TASKS,
				'description' => $description,
			);
		}
		$properties = array(
			'post_id'          => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Readable WordPress post summarized.', 'text', 'nelio-content' ),
			),
			'post_status'      => array(
				'type'        => 'string',
				'description' => _x( 'Current saved WordPress status, including any custom editorial status. This does not establish that editorial work is complete.', 'text', 'nelio-content' ),
			),
			'publication_date' => array(
				'type'        => 'string',
				'description' => _x( 'Saved publication timestamp in UTC, or empty when no valid publication date is recorded. A stored date on a draft does not mean publication is scheduled; inspect post_status.', 'text', 'nelio-content' ),
			),
			'timezone'         => array(
				'type'        => 'string',
				'description' => _x( 'Current WordPress site timezone. Task due-day comparisons use each task’s own timezone.', 'text', 'nelio-content' ),
			),
			'observed_at'      => array(
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => _x( 'UTC time used to evaluate task due days. The saved post and its tasks are read separately, not as an atomic snapshot.', 'text', 'nelio-content' ),
			),
			'scope'            => array(
				'type'        => 'string',
				'enum'        => array( 'accessible_post_tasks' ),
				'description' => _x( 'Only tasks belonging to this post and accessible under the current user’s task permissions are counted. Hidden task identities and counts are omitted.', 'text', 'nelio-content' ),
			),
			'complete'         => array(
				'type'        => 'boolean',
				'enum'        => array( true ),
				'description' => _x( 'The complete bounded collection within the stated scope was validated. This is not a claim that every task for the post is accessible, or that the post is ready to publish. Partial summaries are not returned.', 'text', 'nelio-content' ),
			),
			'tasks'            => $this->object_schema( $counts, array_keys( $counts ), _x( 'Visible task counts. Only pending tasks are divided into due-day buckets; completed tasks are counted separately.', 'text', 'nelio-content' ) ),
		);
		return $this->object_schema( $properties, array_keys( $properties ), _x( 'Saved post publication state and complete counts of accessible tasks, without a publication-readiness verdict or hidden task details.', 'text', 'nelio-content' ) );
	}
}

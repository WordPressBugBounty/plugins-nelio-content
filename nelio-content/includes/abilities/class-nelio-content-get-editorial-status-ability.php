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
				'description'         => 'Summarize one readable post’s saved publication state and visible editorial tasks. Requires Nelio Content Premium and task access. Counts completed and pending tasks, separating pending tasks into overdue, due today, upcoming and undated. Due dates are calendar days in each task’s timezone; a task due today is not overdue. Includes tasks without due dates and applies the same task permissions as task editing. Counts cover only tasks accessible to this user, not necessarily all tasks for the post. No pending visible tasks does not establish publication readiness or quality. Reads at most 1000 task records and accepts a response of at most 2 MiB. If the data cannot be verified or exceeds either limit, it returns an error instead of partial counts. No content is rendered and no tasks or dates are changed.',
				'category'            => 'nelio-content-tasks',
				'input_schema'        => $this->object_schema(
					array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Existing readable WordPress post whose saved publication state and accessible editorial tasks should be summarized. No due-date range is required.',
						),
					),
					array( 'post_id' ),
					'Identify one readable post. Task permissions further restrict the counts returned.'
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
			'total'     => 'All visible task records for this post. Equals pending plus completed.',
			'pending'   => 'Visible tasks that are not completed. Equals overdue plus due_today plus upcoming plus undated.',
			'completed' => 'Visible tasks explicitly marked completed, regardless of their due date.',
			'overdue'   => 'Pending tasks whose due calendar day is earlier than today in the task’s timezone.',
			'due_today' => 'Pending tasks due on today’s calendar day in the task’s timezone, regardless of the stored hour.',
			'upcoming'  => 'Pending tasks due after today’s calendar day in the task’s timezone.',
			'undated'   => 'Pending tasks with no recorded due date, including unresolved dates relative to an unscheduled post. Dates are not predicted.',
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
				'description' => 'Readable WordPress post summarized.',
			),
			'post_status'      => array(
				'type'        => 'string',
				'description' => 'Current saved WordPress status, including any custom editorial status. This does not establish that editorial work is complete.',
			),
			'publication_date' => array(
				'type'        => 'string',
				'description' => 'Saved publication timestamp in UTC, or empty when no valid publication date is recorded. A stored date on a draft does not mean publication is scheduled; inspect post_status.',
			),
			'timezone'         => array(
				'type'        => 'string',
				'description' => 'Current WordPress site timezone. Task due-day comparisons use each task’s own timezone.',
			),
			'observed_at'      => array(
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => 'UTC time used to evaluate task due days. The saved post and its tasks are read separately and may reflect different moments in time.',
			),
			'scope'            => array(
				'type'        => 'string',
				'enum'        => array( 'accessible_post_tasks' ),
				'description' => 'Only tasks belonging to this post and accessible under the current user’s task permissions are counted. Hidden task identities and counts are omitted.',
			),
			'complete'         => array(
				'type'        => 'boolean',
				'enum'        => array( true ),
				'description' => 'All records covered by this response were checked within the supported size limits. This is not a claim that every task for the post is accessible, or that the post is ready to publish. Partial summaries are not returned.',
			),
			'tasks'            => $this->object_schema( $counts, array_keys( $counts ), 'Visible task counts. Only pending tasks are divided into due-day buckets; completed tasks are counted separately.' ),
		);
		return $this->object_schema( $properties, array_keys( $properties ), 'Saved post publication state and complete counts of accessible tasks, without a publication-readiness verdict or hidden task details.' );
	}
}

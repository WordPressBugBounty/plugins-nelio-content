<?php
/** Shared schemas and authorization for Premium editorial task contracts. */
defined( 'ABSPATH' ) || exit;

/**
 * Describes task operations without loading their paid execution.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Editorial_Task_Ability extends Nelio_Content_Premium_Ability {

	/**
	 * Keeps task editor restrictions ahead of Premium availability guidance.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return 'none' !== Nelio_Content_Admin::instance()->get_task_editor_permission();
	}

	/**
	 * Builds the safe editorial task response schema.
	 *
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function task_schema() {
		return $this->object_schema(
			array(
				'id'         => $this->uuid_schema(
					_x( 'Stable Nelio Content editorial task ID.', 'text', 'nelio-content' )
				),
				'task'       => array(
					'type'        => 'string',
					'description' => _x( 'Description of the editorial work to complete.', 'text', 'nelio-content' ),
				),
				'completed'  => array(
					'type'        => 'boolean',
					'description' => _x( 'True when the task is completed; false when it is still pending.', 'text', 'nelio-content' ),
				),
				'assigneeId' => array(
					'type'        => 'integer',
					'description' => _x( 'WordPress user ID assigned to complete the task.', 'text', 'nelio-content' ),
				),
				'assignerId' => array(
					'type'        => 'integer',
					'description' => _x( 'WordPress user ID that assigned the task, when recorded.', 'text', 'nelio-content' ),
				),
				'dateDue'    => array(
					'type'        => 'string',
					'description' => _x( 'Task due date. It may specify only a calendar day rather than an exact time, or be empty for a relative task whose post has no publication date. Interpret it using the task timezone.', 'text', 'nelio-content' ),
				),
				'timezone'   => array(
					'type'        => 'string',
					'description' => _x( 'Timezone associated with the task due date.', 'text', 'nelio-content' ),
				),
				'postId'     => array(
					'type'        => 'integer',
					'description' => _x( 'Related WordPress post ID, when the task belongs to a post.', 'text', 'nelio-content' ),
				),
				'dateType'   => array(
					'type'        => 'string',
					'description' => _x( 'Due-date rule, such as exact, predefined-offset, negative-days or positive-days.', 'text', 'nelio-content' ),
				),
				'dateValue'  => array(
					'type'        => 'string',
					'description' => _x( 'Value of the due-date rule, such as a calendar date or day offset. Interpret together with dateType.', 'text', 'nelio-content' ),
				),
				'color'      => array(
					'type'        => 'string',
					'description' => _x( 'Task color label used in the calendar, such as none, red, orange, yellow, green, cyan, blue or purple.', 'text', 'nelio-content' ),
				),
			),
			array( 'id', 'task', 'completed', 'assigneeId', 'dateDue' ),
			_x( 'One visible editorial task with its assignment, completion state, due date and optional related post.', 'text', 'nelio-content' )
		);
	}
	/**
	 * Identifies one task on a bounded calendar page.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function lookup_properties() {
		$properties                           = $this->query_properties();
		$properties['post_id']['description'] = _x( 'Locate the task among items belonging to this readable WordPress post, including tasks without a due date. Use either post_id or start_date and end_date, not both.', 'text', 'nelio-content' );
		$properties['task_id']                = $this->uuid_schema( _x( 'ID of the existing task to locate among tasks for the selected post or within the supplied due-date range. Follow pagination until found or exhausted.', 'text', 'nelio-content' ) );
		return $properties;
	}

	/**
	 * Requires exactly one bounded search scope: a post or a date range.
	 *
	 * @param array<string,array<string,mixed>> $properties Input properties.
	 * @return array<string,mixed>
	 */
	protected function lookup_input_schema( $properties ) {
		$schema          = $this->object_schema( $properties, array( 'task_id' ), _x( 'Task ID and exactly one search scope: post_id, or start_date and end_date. Keep the scope and per_page unchanged while following cursors.', 'text', 'nelio-content' ) );
		$schema['oneOf'] = array(
			array( 'required' => array( 'post_id' ) ),
			array( 'required' => array( 'start_date', 'end_date' ) ),
		);
		return $schema;
	}

	/**
	 * Allows only editable task attributes, excluding identity and post reassignment.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function changes_properties() {
		return array(
			'task'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 10000,
				'description' => _x( 'Plain-text description of the work to complete. Must contain non-whitespace text; HTML is unsupported.', 'text', 'nelio-content' ),
			),
			'assignee_id' => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Existing WordPress user in this site who should complete the task. Defaults to the current user when creating a task.', 'text', 'nelio-content' ),
			),
			'date_type'   => array(
				'type'        => 'string',
				'enum'        => array( 'exact', 'predefined-offset', 'negative-days', 'positive-days' ),
				'description' => _x( 'Due-date rule. exact uses a calendar date; predefined-offset uses 0, -1 or -7 days; negative-days and positive-days use a nonnegative number of days before or after the base date. Supply date_value together with date_type when updating. Relative dates use the unpublished post publication date, or today for standalone and published-post tasks. An unscheduled post can retain a relative rule without a due date.', 'text', 'nelio-content' ),
			),
			'date_value'  => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 10,
				'description' => _x( 'For exact, a real YYYY-MM-DD date in the task timezone. For predefined-offset, 0, -1 or -7. For positive-days or negative-days, an integer from 0 to 36500. Creation defaults date_type to exact. Updates must supply both date_type and date_value.', 'text', 'nelio-content' ),
			),
			'color'       => array(
				'type'        => 'string',
				'enum'        => array( 'none', 'red', 'orange', 'yellow', 'green', 'cyan', 'blue', 'purple' ),
				'description' => _x( 'Calendar color label. Defaults to none when creating a task.', 'text', 'nelio-content' ),
			),
		);
	}
}

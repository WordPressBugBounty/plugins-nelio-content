<?php
/** Shared discovery schemas for saved future-action reads. */
defined( 'ABSPATH' ) || exit;

/**
 * Defines safe action summaries without arbitrary custom-field values.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Future_Action_Ability extends Nelio_Content_Premium_Ability {
	/** @return array<string,mixed> */
	protected function action_id_schema() {
		return array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Existing future-action record ID obtained from action discovery, not a WordPress content ID.',
		);
	}
	/** @return array{type:string,description:string,properties:array<string,array<string,mixed>>,required:list<string>,additionalProperties:bool} */
	protected function action_schema() {
		$properties = array();
		foreach ( array(
			'id'      => 'Saved future-action record ID.',
			'post_id' => 'Verified readable and editable target post matching both saved relationships.',
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => $description,
			);
		}
		foreach ( array(
			'type'            => 'Saved action kind: status-change, delete, terms-change, fields-change, stick or unstick.',
			'recorded_status' => 'Saved record status: draft or nc_executed. nc_executed means execution was recorded before attempting the change; it does not prove success.',
			'date_type'       => 'Saved date rule: exact, positive-days or predefined-offset. The rule is not recalculated.',
			'date_value'      => 'Saved calendar date or a day offset of zero or more, interpreted according to date_type.',
			'time_type'       => 'Saved time rule: exact, positive-hours or predefined-offset.',
			'time_value'      => 'Saved HH:mm clock time or an hour offset of zero or more, interpreted according to time_type.',
			'timezone'        => 'Saved scheduling timezone, or the current site timezone when no explicit zone was stored.',
			'saved_schedule'  => 'Saved target timestamp with its explicit offset, or empty when unscheduled. It is not recomputed or checked for execution success.',
			'stored_date'     => 'Record’s saved WordPress date in the site timezone, or empty when unset. Date-range discovery uses this date, which may differ from saved_schedule and WP-Cron.',
			'cron_date'       => 'Next matching WP-Cron event timestamp in UTC, or empty when no event is found. Presence does not guarantee execution.',
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'string',
				'description' => $description,
			);
		}
		$properties['type']['enum']            = array( 'status-change', 'delete', 'terms-change', 'fields-change', 'stick', 'unstick' );
		$properties['recorded_status']['enum'] = array( 'draft', 'nc_executed' );
		$properties['cron_scheduled']          = array(
			'type'        => 'boolean',
			'description' => 'Whether WP-Cron currently reports an event for this action. This is independent of its saved date and recorded status.',
		);
		$properties['configuration']           = $this->object_schema(
			array(
				'operation'  => array(
					'type'        => 'string',
					'description' => 'Saved operation for terms/fields changes; empty for other action kinds.',
				),
				'new_status' => array(
					'type'        => 'string',
					'description' => 'Saved target WordPress status for status-change, otherwise empty.',
				),
				'taxonomies' => array(
					'type'        => 'array',
					'maxItems'    => 100,
					'items'       => $this->object_schema(
						array(
							'taxonomy' => array(
								'type'        => 'string',
								'description' => 'Configured target taxonomy attached to the post type and assignable by the current user.',
							),
							'term_ids' => array(
								'type'        => 'array',
								'maxItems'    => 100,
								'items'       => array(
									'type'    => 'integer',
									'minimum' => 1,
								),
								'description' => 'Saved positive term IDs. Saved term labels, slugs and arbitrary data are omitted.',
							),
						),
						array( 'taxonomy', 'term_ids' ),
						'Authorized taxonomy-change target.'
					),
					'description' => 'Only authorized taxonomy targets. Empty for other action types or targets the current user cannot access.',
				),
				'redacted'   => array(
					'type'        => 'boolean',
					'description' => 'True when some settings are hidden. Custom-field names and values are never included; taxonomy targets the current user cannot access are also left out.',
				),
			),
			array( 'operation', 'new_status', 'taxonomies', 'redacted' ),
			'Saved settings limited to fields this tool is allowed to return. Custom-field names and values, the full saved action data, credentials and term labels are not included.'
		);
		return $this->object_schema( $properties, array_keys( $properties ), 'Saved action details and its current scheduling state, after validation. These details do not establish whether the action ran successfully. This operation does not repair or reschedule the action.' );
	}
}

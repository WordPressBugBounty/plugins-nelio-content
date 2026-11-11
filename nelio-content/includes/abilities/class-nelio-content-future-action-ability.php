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
			'description' => _x( 'Existing future-action record ID obtained from action discovery, not a WordPress content ID.', 'text', 'nelio-content' ),
		);
	}
	/** @return array{type:string,description:string,properties:array<string,array<string,mixed>>,required:list<string>,additionalProperties:bool} */
	protected function action_schema() {
		$properties = array();
		foreach ( array(
			'id'      => _x( 'Saved future-action record ID.', 'text', 'nelio-content' ),
			'post_id' => _x( 'Verified readable and editable target post matching both saved relationships.', 'text', 'nelio-content' ),
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => $description,
			);
		}
		foreach ( array(
			'type'            => _x( 'Saved action kind: status-change, delete, terms-change, fields-change, stick or unstick.', 'text', 'nelio-content' ),
			'recorded_status' => _x( 'Saved record status: draft or nc_executed. nc_executed means execution was recorded before attempting the change; it does not prove success.', 'text', 'nelio-content' ),
			'date_type'       => _x( 'Saved date rule: exact, positive-days or predefined-offset. The rule is not recalculated.', 'text', 'nelio-content' ),
			'date_value'      => _x( 'Saved calendar date or nonnegative day offset, interpreted according to date_type.', 'text', 'nelio-content' ),
			'time_type'       => _x( 'Saved time rule: exact, positive-hours or predefined-offset.', 'text', 'nelio-content' ),
			'time_value'      => _x( 'Saved HH:mm clock time or nonnegative hour offset, interpreted according to time_type.', 'text', 'nelio-content' ),
			'timezone'        => _x( 'Saved scheduling timezone, or the current site timezone when no explicit zone was stored.', 'text', 'nelio-content' ),
			'saved_schedule'  => _x( 'Saved target timestamp with its explicit offset, or empty when unscheduled. It is not recomputed or checked for execution success.', 'text', 'nelio-content' ),
			'stored_date'     => _x( 'Record’s saved WordPress date in the site timezone, or empty when unset. Date-range discovery uses this date, which may differ from saved_schedule and WP-Cron.', 'text', 'nelio-content' ),
			'cron_date'       => _x( 'Next matching WP-Cron event timestamp in UTC, or empty when no event is found. Presence does not guarantee execution.', 'text', 'nelio-content' ),
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
			'description' => _x( 'Whether WP-Cron currently reports an event for this action. This is independent of its saved date and recorded status.', 'text', 'nelio-content' ),
		);
		$properties['configuration']           = $this->object_schema(
			array(
				'operation'  => array(
					'type'        => 'string',
					'description' => _x( 'Saved operation for terms/fields changes; empty for other action kinds.', 'text', 'nelio-content' ),
				),
				'new_status' => array(
					'type'        => 'string',
					'description' => _x( 'Saved target WordPress status for status-change, otherwise empty.', 'text', 'nelio-content' ),
				),
				'taxonomies' => array(
					'type'        => 'array',
					'maxItems'    => 100,
					'items'       => $this->object_schema(
						array(
							'taxonomy' => array(
								'type'        => 'string',
								'description' => _x( 'Configured target taxonomy attached to the post type and assignable by the current user.', 'text', 'nelio-content' ),
							),
							'term_ids' => array(
								'type'        => 'array',
								'maxItems'    => 100,
								'items'       => array(
									'type'    => 'integer',
									'minimum' => 1,
								),
								'description' => _x( 'Saved positive term IDs. Saved term labels, slugs and arbitrary data are omitted.', 'text', 'nelio-content' ),
							),
						),
						array( 'taxonomy', 'term_ids' ),
						_x( 'Authorized taxonomy-change target.', 'text', 'nelio-content' )
					),
					'description' => _x( 'Only authorized taxonomy targets. Empty for other action kinds or redacted targets.', 'text', 'nelio-content' ),
				),
				'redacted'   => array(
					'type'        => 'boolean',
					'description' => _x( 'True when configuration is withheld. All custom-field keys and values are always redacted; inaccessible taxonomy targets are also omitted.', 'text', 'nelio-content' ),
				),
			),
			array( 'operation', 'new_status', 'taxonomies', 'redacted' ),
			_x( 'Allowlisted saved configuration. No custom-field keys/values, raw payload, credentials or term labels are returned.', 'text', 'nelio-content' )
		);
		return $this->object_schema( $properties, array_keys( $properties ), _x( 'Validated saved action and current scheduling observations. No execution result is inferred and no action is repaired or rescheduled.', 'text', 'nelio-content' ) );
	}
}

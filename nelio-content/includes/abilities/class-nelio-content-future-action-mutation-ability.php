<?php
/** Shared Premium contracts for individual deferred post changes. */
defined( 'ABSPATH' ) || exit;

/** Defines explicit deferred-effect requests, never immediate execution. */
abstract class Nelio_Content_Future_Action_Mutation_Ability extends Nelio_Content_Future_Action_Ability {
	/** @return array<string,array<string,mixed>> */
	protected function mutation_properties() {
		return array(
			'type'           => array(
				'type'        => 'string',
				'enum'        => array( 'status-change', 'terms-change', 'fields-change', 'stick', 'unstick', 'delete' ),
				'description' => 'Deferred change kind. Changing kind requires the complete configuration for the new kind.',
			),
			'new_status'     => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
				'description' => 'Required target status for status-change. Publishing/private statuses require publication capability; no immediate status change is performed.',
			),
			'operation'      => array(
				'type'        => 'string',
				'enum'        => array( 'add-terms', 'remove-terms', 'replace-terms', 'add-fields', 'remove-fields', 'replace-fields' ),
				'description' => 'Required operation for taxonomy/custom-field changes. For custom fields, both add and replace create a missing value or update an existing value. Only fields holding one string value and allowed by the current user’s permissions are supported.',
			),
			'taxonomies'     => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema(
					array(
						'taxonomy' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Existing taxonomy attached to the target type, assignable by this user.',
						),
						'term_ids' => array(
							'type'        => 'array',
							'maxItems'    => 100,
							'uniqueItems' => true,
							'items'       => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'description' => 'Existing positive term IDs belonging to this taxonomy. Empty is allowed for an explicit replacement.',
						),
					),
					array( 'taxonomy', 'term_ids' ),
					'One explicit taxonomy target; duplicate taxonomies are rejected.'
				),
				'description' => 'Complete replacement of this action’s taxonomy configuration, not an immediate edit to the post.',
			),
			'fields'         => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema(
					array(
						'key'   => array(
							'type'        => 'string',
							'minLength'   => 1,
							'maxLength'   => 100,
							'description' => 'Registered, public, single string post metadata with REST exposure and current per-key edit/delete permission. Protected and unregistered keys are rejected.',
						),
						'value' => array(
							'type'        => 'string',
							'maxLength'   => 10000,
							'description' => 'Plain string checked and cleaned according to the registered REST schema. Required except for remove-fields. Saved field names and values are not included in responses.',
						),
					),
					array( 'key' ),
					'One authorized metadata target; duplicate keys are rejected.'
				),
				'description' => 'Complete replacement of this action’s custom-field configuration. No metadata is changed while scheduling.',
			),
			'date_type'      => array(
				'type'        => 'string',
				'enum'        => array( 'exact', 'positive-days', 'predefined-offset' ),
				'description' => 'Exact calendar date or a calendar-day offset of zero or more from the target’s publication date, or creation/edit time for published targets. Undated drafts keep relative actions unscheduled.',
			),
			'date_value'     => array(
				'type'        => 'string',
				'maxLength'   => 10,
				'description' => 'Real YYYY-MM-DD date for exact rules, otherwise decimal days from 0 through 36500.',
			),
			'time_type'      => array(
				'type'        => 'string',
				'enum'        => array( 'exact', 'positive-hours', 'predefined-offset' ),
				'description' => 'Exact local HH:mm time, or elapsed hours added after the date rule. On an exact date, hour offsets start at local midnight.',
			),
			'time_value'     => array(
				'type'        => 'string',
				'maxLength'   => 6,
				'description' => 'HH:mm clock time for exact rules, otherwise decimal hours from 0 through 876000. Scheduling has minute precision.',
			),
			'timezone'       => array(
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => 'Optional IANA timezone or explicit offset such as +02:00 for exact date/time rules. Empty resets to the site timezone. Relative rules use the site timezone. Nonexistent local times fail; ambiguous local times use the earlier instant unless an explicit offset is supplied.',
			),
			'confirm_delete' => array(
				'type'        => 'boolean',
				'description' => 'Must be true whenever creating or updating a deferred permanent post-deletion action. This confirms the target will be permanently deleted at its scheduled time. It does not execute deletion now.',
			),
		);
	}
	/**
	 * @param lowercase-string                  $name Ability suffix.
	 * @param string                            $label Label.
	 * @param string                            $description Description.
	 * @param array<string,array<string,mixed>> $properties Input properties.
	 * @param list<string>                      $required Required fields.
	 * @return void
	 */
	protected function register_mutation( $name, $label, $description, $properties, $required ) {
		$output                          = $this->action_schema();
		$output['properties']['changed'] = array(
			'type'        => 'boolean',
			'description' => 'Whether this confirmed request changed the action or repaired its event. If the saved fields already match and the scheduled event is correct, nothing is changed.',
		);
		$output['required'][]            = 'changed';
		if ( 'delete-future-action' === $name ) {
			$output = $this->object_schema(
				array(
					'action_id' => $this->action_id_schema(),
					'post_id'   => $this->query_properties()['post_id'],
					'deleted'   => array(
						'type'        => 'boolean',
						'enum'        => array( true ),
						'description' => 'The action record and all its matching events are absent. The target post was not deleted.',
					),
				),
				array( 'action_id', 'post_id', 'deleted' ),
				'Verified cancellation of this action only.'
			);
		}
		wp_register_ability(
			'nelio-content/' . $name,
			array(
				'label'               => $label,
				'description'         => $description . ' Requires Premium, current permission to access the action and its target post, a post type with future actions enabled, and permission to make the requested change. No immediate execution occurs. Resolved dates must be more than one minute in the future. Saving the action and scheduling its WP-Cron event happen in separate steps, and each step is checked. An error may leave the action saved without the event that should run it. Inspect before retrying; this operation does not retry automatically or guarantee that repeating it cannot create duplicates. Permissions are checked again when the scheduled action runs for actions created through these abilities.',
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( $properties, $required, 'One explicit deferred action change. Client-controlled identity, author and execution status are unsupported.' ),
				'output_schema'       => $output,
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}
}

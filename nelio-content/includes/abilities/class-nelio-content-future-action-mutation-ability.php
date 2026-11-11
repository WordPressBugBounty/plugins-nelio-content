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
				'description' => _x( 'Deferred change kind. Changing kind requires the complete configuration for the new kind.', 'text', 'nelio-content' ),
			),
			'new_status'     => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'pending', 'private', 'publish' ),
				'description' => _x( 'Required target status for status-change. Publishing/private statuses require publication capability; no immediate status change is performed.', 'text', 'nelio-content' ),
			),
			'operation'      => array(
				'type'        => 'string',
				'enum'        => array( 'add-terms', 'remove-terms', 'replace-terms', 'add-fields', 'remove-fields', 'replace-fields' ),
				'description' => _x( 'Required operation for taxonomy/custom-field changes. Field add/replace both upsert authorized single-value string metadata.', 'text', 'nelio-content' ),
			),
			'taxonomies'     => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema(
					array(
						'taxonomy' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => _x( 'Existing taxonomy attached to the target type, assignable by this user.', 'text', 'nelio-content' ),
						),
						'term_ids' => array(
							'type'        => 'array',
							'maxItems'    => 100,
							'uniqueItems' => true,
							'items'       => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'description' => _x( 'Existing positive term IDs belonging to this taxonomy. Empty is allowed for an explicit replacement.', 'text', 'nelio-content' ),
						),
					),
					array( 'taxonomy', 'term_ids' ),
					_x( 'One explicit taxonomy target; duplicate taxonomies are rejected.', 'text', 'nelio-content' )
				),
				'description' => _x( 'Complete replacement of this action’s taxonomy configuration, not an immediate edit to the post.', 'text', 'nelio-content' ),
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
							'description' => _x( 'Registered, public, single string post metadata with REST exposure and current per-key edit/delete permission. Protected and unregistered keys are rejected.', 'text', 'nelio-content' ),
						),
						'value' => array(
							'type'        => 'string',
							'maxLength'   => 10000,
							'description' => _x( 'Literal plain string, validated against the registered REST schema and sanitizer. Required except for remove-fields. Saved keys/values remain redacted from responses.', 'text', 'nelio-content' ),
						),
					),
					array( 'key' ),
					_x( 'One authorized metadata target; duplicate keys are rejected.', 'text', 'nelio-content' )
				),
				'description' => _x( 'Complete replacement of this action’s custom-field configuration. No metadata is changed while scheduling.', 'text', 'nelio-content' ),
			),
			'date_type'      => array(
				'type'        => 'string',
				'enum'        => array( 'exact', 'positive-days', 'predefined-offset' ),
				'description' => _x( 'Exact calendar date or nonnegative calendar-day offset from the target’s publication date, or creation/edit time for published targets. Undated drafts keep relative actions unscheduled.', 'text', 'nelio-content' ),
			),
			'date_value'     => array(
				'type'        => 'string',
				'maxLength'   => 10,
				'description' => _x( 'Real YYYY-MM-DD date for exact rules, otherwise decimal days from 0 through 36500.', 'text', 'nelio-content' ),
			),
			'time_type'      => array(
				'type'        => 'string',
				'enum'        => array( 'exact', 'positive-hours', 'predefined-offset' ),
				'description' => _x( 'Exact local HH:mm time, or elapsed hours added after the date rule. On an exact date, hour offsets start at local midnight.', 'text', 'nelio-content' ),
			),
			'time_value'     => array(
				'type'        => 'string',
				'maxLength'   => 6,
				'description' => _x( 'HH:mm clock time for exact rules, otherwise decimal hours from 0 through 876000. Scheduling has minute precision.', 'text', 'nelio-content' ),
			),
			'timezone'       => array(
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => _x( 'Optional IANA timezone or explicit offset such as +02:00 for exact date/time rules. Empty resets to the site timezone. Relative rules use the site timezone. Nonexistent local times fail; ambiguous local times use the earlier instant unless an explicit offset is supplied.', 'text', 'nelio-content' ),
			),
			'confirm_delete' => array(
				'type'        => 'boolean',
				'description' => _x( 'Must be true whenever creating or updating a deferred permanent post-deletion action. This confirms the target will be permanently deleted at its scheduled time. It does not execute deletion now.', 'text', 'nelio-content' ),
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
			'description' => _x( 'Whether this confirmed request changed the action or repaired its event. Equal fields with a verified event are a no-op.', 'text', 'nelio-content' ),
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
						'description' => _x( 'The action record and all its matching events are absent. The target post was not deleted.', 'text', 'nelio-content' ),
					),
				),
				array( 'action_id', 'post_id', 'deleted' ),
				_x( 'Verified cancellation of this action only.', 'text', 'nelio-content' )
			);
		}
		wp_register_ability(
			'nelio-content/' . $name,
			array(
				'label'               => $label,
				'description'         => $description . ' ' . _x( 'Requires Premium, current record/target permissions, enabled target types and effect-specific authorization. No immediate execution occurs. Resolved dates must be more than one minute in the future. Record persistence and Cron synchronization are separate verified stages: an error may leave a saved action without its intended event. Inspect before retrying; no automatic retry or duplicate-safety guarantee is provided. Authorization is checked again at deferred execution for actions created through these abilities.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( $properties, $required, _x( 'One explicit deferred action change. Client-controlled identity, author and execution status are unsupported.', 'text', 'nelio-content' ) ),
				'output_schema'       => $output,
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}
}

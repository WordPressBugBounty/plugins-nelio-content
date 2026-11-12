<?php
/**
 * Shared inputs, safe output contracts and authorization for automation discovery.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes automation configuration without exposing raw Cloud settings.
 *
 * @template TInput of TAbility_Input
 * @extends Nelio_Content_Premium_Ability<TInput>
 */
abstract class Nelio_Content_Automation_Ability extends Nelio_Content_Premium_Ability {

	/**
	 * Checks post editing access before Cloud reads or Premium guidance.
	 *
	 * @param TInput $input Validated ability input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return 'none' !== nelio_content_get_social_editor_permission() &&
			( empty( $input['post_id'] ) || ( current_user_can( 'edit_post', $input['post_id'] ) && in_array( get_post_type( $input['post_id'] ), nelio_content_get_post_types( 'social' ), true ) ) );
	}

	/**
	 * Limits discovery to one authorized profile and optional post context.
	 *
	 * @return array<string,mixed>
	 */
	protected function automation_query_schema() {
		$properties = $this->query_properties();
		unset( $properties['start_date'], $properties['end_date'] );
		$properties['post_id']['description'] = 'Optional editable WordPress post. Restricts group and template selection by post type, author and taxonomy; does not render placeholders or predict scheduling.';
		$properties['profile_id']             = $this->uuid_schema( 'One social profile accessible to the current user. Other profiles and their configuration are never included.' );
		return $this->object_schema( $properties, array( 'profile_id' ), 'Automation settings for one profile, optionally filtered for a post the current user can edit. Results are returned one page at a time, with limits on page size.' );
	}

	/**
	 * Projects the configured state of a group for a single profile.
	 *
	 * @return array<string,mixed>
	 */
	protected function automation_settings_schema() {
		$properties = $this->automation_record_properties();
		foreach ( array(
			'enabled'             => 'Whether automation is enabled for this profile in this group. A positive group weight and the relevant publication or reshare setting must also be enabled.',
			'publication_enabled' => 'Whether publication promotion is enabled for this profile in this group. Eligible templates and content are still required to generate messages.',
			'reshare_enabled'     => 'Whether republication is enabled for this profile in this group. Content must still meet the eligibility requirements for republication.',
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'boolean',
				'description' => $description,
			);
		}
		$properties['publication_rule'] = $this->publication_rule_schema();
		return $this->object_schema( $properties, array_keys( $properties ), 'Settings from one automation group for the requested profile. Group weights and enabled settings describe configuration, not a guarantee of publication.' );
	}

	/** @return array<string,mixed> */
	protected function publication_rule_schema() {
		return $this->object_schema(
			array(
				'type' => array(
					'type'        => 'string',
					'enum'        => array( 'always', 'max-age' ),
					'description' => 'Whether the group can apply regardless of post age or only within its configured age limit.',
				),
				'days' => array(
					'type'        => 'number',
					'minimum'     => 0,
					'description' => 'Maximum published post age in days when type is max-age.',
				),
			),
			array( 'type' ),
			'Group-level published post age rule used by Cloud when selecting automation groups.'
		);
	}

	/**
	 * Projects template identity, provenance and supported timing fields.
	 *
	 * @return array<string,mixed>
	 */
	protected function social_template_schema() {
		$properties                 = $this->automation_record_properties();
		$properties['template_id']  = $this->uuid_schema( 'Stable identifier of the configured template.' );
		$properties['kind']         = array(
			'type'        => 'string',
			'enum'        => array( 'publication', 'reshare' ),
			'description' => 'Whether this template belongs to publication promotion or ongoing republication.',
		);
		$properties['source']       = array(
			'type'        => 'string',
			'enum'        => array( 'profile', 'network' ),
			'description' => 'Whether the template is configured directly on the profile or inherited from its network in this group.',
		);
		$properties['text']         = array(
			'type'        => 'string',
			'description' => 'Configured template text, including literal placeholders. This is not rendered message content.',
		);
		$required                   = array_keys( $properties );
		$properties['target_name']  = array(
			'type'        => 'string',
			'description' => 'Configured publication destination, such as a Pinterest board, when specified.',
		);
		$properties['attachment']   = array(
			'type'        => 'string',
			'enum'        => array( 'none', 'image', 'video' ),
			'description' => 'Configured attachment preference. Actual media availability and network requirements are evaluated during generation.',
		);
		$properties['availability'] = $this->availability_schema();
		return $this->object_schema( $properties, $required, 'One enabled template with its source group, profile, promotion kind and optional timing configuration. Multiple groups can contribute templates.' );
	}

	/**
	 * Common provenance for a record without unrelated profile settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function automation_record_properties() {
		return array(
			'group_id'   => array(
				'type'        => 'string',
				'description' => 'Automation group UUID or universal for the universal group.',
			),
			'group_name' => array(
				'type'        => 'string',
				'description' => 'Configured group name, or an empty string for the universal group.',
			),
			'priority'   => array(
				'type'        => 'number',
				'description' => 'Configured group weight. Zero disables the group; positive weights allow several applicable groups to contribute.',
			),
			'profile_id' => $this->uuid_schema( 'Accessible profile to which this record applies.' ),
			'network'    => array(
				'type'        => 'string',
				'description' => 'Network of the requested accessible profile.',
			),
		);
	}

	/**
	 * Describes the native timing configuration without passing arbitrary settings through.
	 *
	 * @return array<string,mixed>
	 */
	protected function availability_schema() {
		$properties = array(
			'type' => array(
				'type'        => 'string',
				'enum'        => array( 'publication-day-offset', 'publication-day-period', 'after-publication', 'reshare' ),
				'description' => 'Timing rule relative to post publication or the weekly republication schedule.',
			),
			'time' => array(
				'type'        => 'string',
				'description' => 'Site-local HH:MM or named period: morning, noon, afternoon or night.',
			),
		);
		foreach ( array(
			'hoursAfterPublication' => 'Hours after post publication for a publication-day-offset rule.',
			'daysAfterPublication'  => 'Days after post publication for an after-publication rule.',
			'randomize'             => 'Configured scheduling variation preference. Exact publication times are determined when messages are generated.',
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'number',
				'minimum'     => 0,
				'description' => $description,
			);
		}
		$days = array();
		foreach ( array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ) as $day ) {
			$days[ $day ] = array(
				'type'        => 'boolean',
				'description' => 'Whether this weekday is selected for republication.',
			);
		}
		$properties['weekday']    = $this->object_schema( $days, array_keys( $days ), 'Selected weekdays for a reshare template.' );
		$properties['recurrency'] = $this->object_schema(
			array(
				'instanceCount'  => array(
					'type'        => 'number',
					'minimum'     => 0,
					'description' => 'Configured number of recurring instances.',
				),
				'intervalInDays' => array(
					'type'        => 'number',
					'minimum'     => 1,
					'description' => 'Days between recurring instances.',
				),
			),
			array( 'instanceCount', 'intervalInDays' ),
			'Recurrence requested by the configured template; generated series remain managed by Cloud.'
		);
		return $this->object_schema( $properties, array( 'type' ), 'Configured template timing. Eligibility and exact dates are determined by Cloud, not by this read-only ability.' );
	}
}

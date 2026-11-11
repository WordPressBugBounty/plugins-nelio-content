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
		$properties['post_id']['description'] = _x( 'Optional editable WordPress post. Restricts group and template selection by post type, author and taxonomy; does not render placeholders or predict scheduling.', 'text', 'nelio-content' );
		$properties['profile_id']             = $this->uuid_schema( _x( 'One social profile accessible to the current user. Other profiles and their configuration are never included.', 'text', 'nelio-content' ) );
		return $this->object_schema( $properties, array( 'profile_id' ), _x( 'Profile-scoped automation discovery with optional editable post context and bounded pagination.', 'text', 'nelio-content' ) );
	}

	/**
	 * Projects the configured state of a group for a single profile.
	 *
	 * @return array<string,mixed>
	 */
	protected function automation_settings_schema() {
		$properties = $this->automation_record_properties();
		foreach ( array(
			'enabled'             => _x( 'Whether automation is enabled for this profile in this group. A positive group weight and the relevant publication or reshare setting must also be enabled.', 'text', 'nelio-content' ),
			'publication_enabled' => _x( 'Whether publication promotion is enabled for this profile in this group. Eligible templates and content are still required to generate messages.', 'text', 'nelio-content' ),
			'reshare_enabled'     => _x( 'Whether republication is enabled for this profile in this group. Content must still meet the eligibility requirements for republication.', 'text', 'nelio-content' ),
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'boolean',
				'description' => $description,
			);
		}
		$properties['publication_rule'] = $this->publication_rule_schema();
		return $this->object_schema( $properties, array_keys( $properties ), _x( 'Settings from one automation group for the requested profile. Group weights and enabled settings describe configuration, not a guarantee of publication.', 'text', 'nelio-content' ) );
	}

	/** @return array<string,mixed> */
	protected function publication_rule_schema() {
		return $this->object_schema(
			array(
				'type' => array(
					'type'        => 'string',
					'enum'        => array( 'always', 'max-age' ),
					'description' => _x( 'Whether the group can apply regardless of post age or only within its configured age limit.', 'text', 'nelio-content' ),
				),
				'days' => array(
					'type'        => 'number',
					'minimum'     => 0,
					'description' => _x( 'Maximum published post age in days when type is max-age.', 'text', 'nelio-content' ),
				),
			),
			array( 'type' ),
			_x( 'Group-level published post age rule used by Cloud when selecting automation groups.', 'text', 'nelio-content' )
		);
	}

	/**
	 * Projects template identity, provenance and supported timing fields.
	 *
	 * @return array<string,mixed>
	 */
	protected function social_template_schema() {
		$properties                 = $this->automation_record_properties();
		$properties['template_id']  = $this->uuid_schema( _x( 'Stable identifier of the configured template.', 'text', 'nelio-content' ) );
		$properties['kind']         = array(
			'type'        => 'string',
			'enum'        => array( 'publication', 'reshare' ),
			'description' => _x( 'Whether this template belongs to publication promotion or ongoing republication.', 'text', 'nelio-content' ),
		);
		$properties['source']       = array(
			'type'        => 'string',
			'enum'        => array( 'profile', 'network' ),
			'description' => _x( 'Whether the template is configured directly on the profile or inherited from its network in this group.', 'text', 'nelio-content' ),
		);
		$properties['text']         = array(
			'type'        => 'string',
			'description' => _x( 'Configured template text, including literal placeholders. This is not rendered message content.', 'text', 'nelio-content' ),
		);
		$required                   = array_keys( $properties );
		$properties['target_name']  = array(
			'type'        => 'string',
			'description' => _x( 'Configured publication destination, such as a Pinterest board, when specified.', 'text', 'nelio-content' ),
		);
		$properties['attachment']   = array(
			'type'        => 'string',
			'enum'        => array( 'none', 'image', 'video' ),
			'description' => _x( 'Configured attachment preference. Actual media availability and network requirements are evaluated during generation.', 'text', 'nelio-content' ),
		);
		$properties['availability'] = $this->availability_schema();
		return $this->object_schema( $properties, $required, _x( 'One enabled template with its source group, profile, promotion kind and optional timing configuration. Multiple groups can contribute templates.', 'text', 'nelio-content' ) );
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
				'description' => _x( 'Automation group UUID or universal for the universal group.', 'text', 'nelio-content' ),
			),
			'group_name' => array(
				'type'        => 'string',
				'description' => _x( 'Configured group name, or an empty string for the universal group.', 'text', 'nelio-content' ),
			),
			'priority'   => array(
				'type'        => 'number',
				'description' => _x( 'Configured group weight. Zero disables the group; positive weights allow several applicable groups to contribute.', 'text', 'nelio-content' ),
			),
			'profile_id' => $this->uuid_schema( _x( 'Accessible profile to which this record applies.', 'text', 'nelio-content' ) ),
			'network'    => array(
				'type'        => 'string',
				'description' => _x( 'Network of the requested accessible profile.', 'text', 'nelio-content' ),
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
				'description' => _x( 'Timing rule relative to post publication or the weekly republication schedule.', 'text', 'nelio-content' ),
			),
			'time' => array(
				'type'        => 'string',
				'description' => _x( 'Site-local HH:MM or named period: morning, noon, afternoon or night.', 'text', 'nelio-content' ),
			),
		);
		foreach ( array(
			'hoursAfterPublication' => _x( 'Hours after post publication for a publication-day-offset rule.', 'text', 'nelio-content' ),
			'daysAfterPublication'  => _x( 'Days after post publication for an after-publication rule.', 'text', 'nelio-content' ),
			'randomize'             => _x( 'Configured scheduling variation preference. Exact publication times are determined when messages are generated.', 'text', 'nelio-content' ),
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
				'description' => _x( 'Whether this weekday is selected for republication.', 'text', 'nelio-content' ),
			);
		}
		$properties['weekday']    = $this->object_schema( $days, array_keys( $days ), _x( 'Selected weekdays for a reshare template.', 'text', 'nelio-content' ) );
		$properties['recurrency'] = $this->object_schema(
			array(
				'instanceCount'  => array(
					'type'        => 'number',
					'minimum'     => 0,
					'description' => _x( 'Configured number of recurring instances.', 'text', 'nelio-content' ),
				),
				'intervalInDays' => array(
					'type'        => 'number',
					'minimum'     => 1,
					'description' => _x( 'Days between recurring instances.', 'text', 'nelio-content' ),
				),
			),
			array( 'instanceCount', 'intervalInDays' ),
			_x( 'Recurrence requested by the configured template; generated series remain managed by Cloud.', 'text', 'nelio-content' )
		);
		return $this->object_schema( $properties, array( 'type' ), _x( 'Configured template timing. Eligibility and exact dates are determined by Cloud, not by this read-only ability.', 'text', 'nelio-content' ) );
	}
}

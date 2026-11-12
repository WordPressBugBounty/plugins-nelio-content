<?php
/** Shared schemas and management access for automation group discovery. */
defined( 'ABSPATH' ) || exit;

/**
 * Describes saved group configuration without predicting generated messages.
 *
 * @extends Nelio_Content_Automation_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Automation_Group_Ability extends Nelio_Content_Automation_Ability {
	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return nelio_content_can_current_user_manage_plugin() && parent::check_operation_permissions( $input );
	}

	/** @return array<string,mixed> */
	protected function group_id_schema() {
		return array(
			'type'        => 'string',
			'minLength'   => 1,
			'maxLength'   => 100,
			'description' => 'Saved automation group UUID, or universal for the universal group. Obtain it from list-automation-groups.',
		);
	}

	/** @return array<string,mixed> */
	protected function revision_schema() {
		return array(
			'type'        => 'string',
			'pattern'     => '^[a-f0-9]{64}$',
			'description' => 'Value identifying the group collection last read from Cloud. Pass it unchanged as expected_revision when saving changes. It helps detect changes made since that read, but cannot prevent another client from changing Cloud settings at the same time.',
		);
	}

	/**
	 * @param bool $detail Include profile-scoped configuration.
	 * @return array<string,mixed>
	 */
	protected function group_schema( $detail = false ) {
		$properties = array(
			'group_id'          => $this->group_id_schema(),
			'revision'          => $this->revision_schema(),
			'name'              => array(
				'type'        => 'string',
				'description' => 'Saved group name in plain text; empty for the universal group.',
			),
			'universal'         => array(
				'type'        => 'boolean',
				'description' => 'Whether this group applies across enabled content types without author or taxonomy selectors.',
			),
			'weight'            => array(
				'type'        => 'number',
				'minimum'     => 0,
				'maximum'     => 100,
				'description' => 'Saved relative weight. Zero disables the group. A higher weight does not exclude other matching groups; several groups can contribute messages.',
			),
			'enabled'           => array(
				'type'        => 'boolean',
				'description' => 'Whether the group has positive weight. Profile switches, usable templates, content eligibility and plan limits still apply.',
			),
			'post_type'         => array(
				'type'        => 'string',
				'description' => 'Enabled editable content type selected by a regular group; empty for the universal group.',
			),
			'author_ids'        => array(
				'type'        => 'array',
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'description' => 'Saved author selectors, without contact information. Empty means any author. A saved ID does not imply that the author still exists.',
			),
			'taxonomies'        => array(
				'type'        => 'array',
				'items'       => $this->object_schema(
					array(
						'taxonomy' => array(
							'type'        => 'string',
							'description' => 'Taxonomy attached to the selected content type and assignable by the current user.',
						),
						'term_ids' => array(
							'type'        => 'array',
							'items'       => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'description' => 'Saved terms in this taxonomy. A post must match at least one selected term in each taxonomy filter that has selected terms.',
						),
					),
					array( 'taxonomy', 'term_ids' ),
					'One authorized saved taxonomy criterion. Empty selections do not restrict content.'
				),
				'description' => 'Authorized taxonomy selectors. Different taxonomies are combined with AND; terms within each taxonomy use OR.',
			),
			'criteria_redacted' => array(
				'type'        => 'boolean',
				'description' => 'Whether unavailable or unauthorized taxonomy criteria were omitted. If some criteria are missing from the response, it cannot establish which posts match.',
			),
			'publication_rule'  => $this->publication_rule_schema(),
			'plan_group_limit'  => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Locally recorded subscription limit for the total group collection, including universal. This read does not truncate saved groups to the limit or guarantee Cloud eligibility.',
			),
		);
		if ( $detail ) {
			$properties['profiles']  = array(
				'type'        => 'array',
				'items'       => $this->automation_settings_schema(),
				'maxItems'    => 1000,
				'description' => 'Saved switches for connected profiles accessible to the current user, including disabled switches. Other profiles and their counts are excluded.',
			);
			$properties['templates'] = array(
				'type'        => 'array',
				'items'       => $this->social_template_schema(),
				'maxItems'    => 1000,
				'description' => 'Configured templates compatible with the group selectors, returned separately for each profile the current user can access, including disabled groups and switches. Each network template identifies the accessible profile to which it applies. Additional template selectors may still restrict use; use get-social-templates with a post for post-specific selection. No placeholders are rendered.',
			);
		}
		return $this->object_schema( $properties, array_keys( $properties ), 'Saved automation group configuration. Selectors, weight and templates do not guarantee message generation; content, network and subscription eligibility are evaluated when generating.' );
	}
}

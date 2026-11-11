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
			'description' => _x( 'Saved automation group UUID, or universal for the universal group. Obtain it from list-automation-groups.', 'text', 'nelio-content' ),
		);
	}

	/** @return array<string,mixed> */
	protected function revision_schema() {
		return array(
			'type'        => 'string',
			'pattern'     => '^[a-f0-9]{64}$',
			'description' => _x( 'Opaque revision of the Cloud-visible group collection. Pass it as expected_revision when writing. It detects observed stale configuration but does not provide an atomic remote lock.', 'text', 'nelio-content' ),
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
				'description' => _x( 'Saved group name in plain text; empty for the universal group.', 'text', 'nelio-content' ),
			),
			'universal'         => array(
				'type'        => 'boolean',
				'description' => _x( 'Whether this group applies across enabled content types without author or taxonomy selectors.', 'text', 'nelio-content' ),
			),
			'weight'            => array(
				'type'        => 'number',
				'minimum'     => 0,
				'maximum'     => 100,
				'description' => _x( 'Saved relative weight. Zero disables the group. Positive weights are not exclusive precedence: multiple applicable groups can contribute messages.', 'text', 'nelio-content' ),
			),
			'enabled'           => array(
				'type'        => 'boolean',
				'description' => _x( 'Whether the group has positive weight. Profile switches, usable templates, content eligibility and plan limits still apply.', 'text', 'nelio-content' ),
			),
			'post_type'         => array(
				'type'        => 'string',
				'description' => _x( 'Enabled editable content type selected by a regular group; empty for the universal group.', 'text', 'nelio-content' ),
			),
			'author_ids'        => array(
				'type'        => 'array',
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'description' => _x( 'Saved author selectors, without contact information. Empty means any author. A saved ID does not imply that the author still exists.', 'text', 'nelio-content' ),
			),
			'taxonomies'        => array(
				'type'        => 'array',
				'items'       => $this->object_schema(
					array(
						'taxonomy' => array(
							'type'        => 'string',
							'description' => _x( 'Taxonomy attached to the selected content type and assignable by the current user.', 'text', 'nelio-content' ),
						),
						'term_ids' => array(
							'type'        => 'array',
							'items'       => array(
								'type'    => 'integer',
								'minimum' => 1,
							),
							'description' => _x( 'Saved terms in this taxonomy. A post must match at least one selected term in each nonempty taxonomy selector.', 'text', 'nelio-content' ),
						),
					),
					array( 'taxonomy', 'term_ids' ),
					_x( 'One authorized saved taxonomy criterion. Empty selections do not restrict content.', 'text', 'nelio-content' )
				),
				'description' => _x( 'Authorized taxonomy selectors. Different taxonomies are combined with AND; terms within each taxonomy use OR.', 'text', 'nelio-content' ),
			),
			'criteria_redacted' => array(
				'type'        => 'boolean',
				'description' => _x( 'Whether unavailable or unauthorized taxonomy criteria were omitted. An incomplete projection cannot establish which posts match.', 'text', 'nelio-content' ),
			),
			'publication_rule'  => $this->publication_rule_schema(),
			'plan_group_limit'  => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Locally recorded subscription limit for the total group collection, including universal. This read does not truncate saved groups to the limit or guarantee Cloud eligibility.', 'text', 'nelio-content' ),
			),
		);
		if ( $detail ) {
			$properties['profiles']  = array(
				'type'        => 'array',
				'items'       => $this->automation_settings_schema(),
				'maxItems'    => 1000,
				'description' => _x( 'Saved switches for connected profiles accessible to the current user, including disabled switches. Other profiles and their counts are excluded.', 'text', 'nelio-content' ),
			);
			$properties['templates'] = array(
				'type'        => 'array',
				'items'       => $this->social_template_schema(),
				'maxItems'    => 1000,
				'description' => _x( 'Configured templates compatible with the group selectors, projected separately for each accessible profile, including disabled groups and switches. Network templates carry the accessible profile to which they apply. Additional template selectors may still restrict use; use get-social-templates with a post for post-specific selection. No placeholders are rendered.', 'text', 'nelio-content' ),
			);
		}
		return $this->object_schema( $properties, array_keys( $properties ), _x( 'Saved automation group configuration. Selectors, weight and templates do not guarantee message generation; content, network and subscription eligibility are evaluated when generating.', 'text', 'nelio-content' ) );
	}
}

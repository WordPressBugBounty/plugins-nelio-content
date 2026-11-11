<?php
/** Shared contracts for explicit individual changes to the saved automation collection. */
defined( 'ABSPATH' ) || exit;

/** Defines partial group mutations and their collection-wide save boundary. */
abstract class Nelio_Content_Automation_Group_Mutation_Ability extends Nelio_Content_Automation_Group_Ability {
	/** @return array<string,array<string,mixed>> */
	protected function mutation_properties() {
		$term      = $this->object_schema(
			array(
				'taxonomy' => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => _x( 'Attached, assignable taxonomy.', 'text', 'nelio-content' ),
				),
				'term_ids' => array(
					'type'        => 'array',
					'uniqueItems' => true,
					'maxItems'    => 100,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'description' => _x( 'Existing assignable terms; empty means no restriction for this taxonomy.', 'text', 'nelio-content' ),
				),
			),
			array( 'taxonomy', 'term_ids' ),
			_x( 'One complete taxonomy selector; duplicate taxonomies are rejected.', 'text', 'nelio-content' )
		);
		$template  = $this->object_schema(
			array(
				'template_id'  => $this->uuid_schema( _x( 'Optional ID of an existing template in this exact group, source and promotion kind. Omit for a new controlled identity.', 'text', 'nelio-content' ) ),
				'text'         => array(
					'type'        => 'string',
					'minLength'   => 1,
					'maxLength'   => 10000,
					'description' => _x( 'Literal template text, including supported placeholders. Text is saved without rendering or enforcing a rendered message length.', 'text', 'nelio-content' ),
				),
				'post_type'    => array(
					'type'        => 'string',
					'maxLength'   => 100,
					'description' => _x( 'Optional enabled editable type for a universal-group template. Regular-group templates inherit their group type.', 'text', 'nelio-content' ),
				),
				'author_id'    => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => _x( 'Optional existing author selector. Must be compatible with the group and editable content type.', 'text', 'nelio-content' ),
				),
				'taxonomies'   => array(
					'type'        => 'array',
					'maxItems'    => 100,
					'items'       => $term,
					'description' => _x( 'Optional single-term selectors; each taxonomy must have exactly one term, compatible with the group.', 'text', 'nelio-content' ),
				),
				'target_name'  => array(
					'type'        => 'string',
					'maxLength'   => 500,
					'description' => _x( 'Optional saved publication destination, such as a Pinterest board. Its current availability is evaluated when generating messages.', 'text', 'nelio-content' ),
				),
				'attachment'   => array(
					'type'        => 'string',
					'enum'        => array( 'none', 'image', 'video' ),
					'description' => _x( 'Optional media preference supported by the network. Actual media is chosen when generating messages.', 'text', 'nelio-content' ),
				),
				'availability' => $this->availability_schema(),
			),
			array( 'text' ),
			_x( 'One replacement template. Identity, creator, network, source profile and group are assigned by the service; credentials and arbitrary settings are unsupported.', 'text', 'nelio-content' )
		);
		$templates = array(
			'type'        => 'array',
			'maxItems'    => 100,
			'items'       => $template,
			'description' => _x( 'Complete replacement of this source’s templates for this promotion kind. Empty explicitly removes them; omitted preserves them.', 'text', 'nelio-content' ),
		);
		$profile   = array( 'profile_id' => $this->uuid_schema( _x( 'Connected accessible profile whose settings are being patched. Omitted profiles are preserved.', 'text', 'nelio-content' ) ) );
		foreach ( array( 'enabled', 'publication_enabled', 'reshare_enabled' ) as $key ) {
			$profile[ $key ] = array(
				'type'        => 'boolean',
				'description' => _x( 'Requested saved switch. Publication/reshare switches are normalized to false without usable profile or network templates.', 'text', 'nelio-content' ),
			);
		}
		$profile['publication_templates'] = $templates;
		$profile['reshare_templates']     = $templates;
		return array(
			'name'             => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 200,
				'description' => _x( 'Plain name for a regular group; unavailable for universal.', 'text', 'nelio-content' ),
			),
			'weight'           => array(
				'type'        => 'number',
				'minimum'     => 0,
				'maximum'     => 100,
				'description' => _x( 'Relative group weight, default 100 on creation. Zero disables; positive weights do not imply exclusive precedence.', 'text', 'nelio-content' ),
			),
			'post_type'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 100,
				'description' => _x( 'Enabled editable type for a regular group. Changing it normalizes its selectors and templates as in the editor. Unavailable for universal.', 'text', 'nelio-content' ),
			),
			'authors'          => array(
				'type'        => 'array',
				'uniqueItems' => true,
				'maxItems'    => 100,
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'description' => _x( 'Complete saved author selector. Empty selects all authors; authors must exist and have editing access to the selected type. Types without author support clear author selectors.', 'text', 'nelio-content' ),
			),
			'taxonomies'       => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $term,
				'description' => _x( 'Complete replacement of regular-group taxonomy selectors. Empty clears them; omitted preserves applicable selectors.', 'text', 'nelio-content' ),
			),
			'publication_rule' => $this->publication_rule_schema(),
			'profiles'         => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema( $profile, array( 'profile_id' ), _x( 'Partial settings for one profile. At least one switch or template list is required.', 'text', 'nelio-content' ) ),
				'description' => _x( 'Individual profile patches. Frequencies are not changed by this operation.', 'text', 'nelio-content' ),
			),
			'networks'         => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema(
					array(
						'network'               => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => _x( 'Connected network supporting network templates. Its templates may affect every associated profile.', 'text', 'nelio-content' ),
						),
						'publication_templates' => $templates,
						'reshare_templates'     => $templates,
					),
					array( 'network' ),
					_x( 'Partial network-template settings. At least one template list is required; omitted modes/networks are preserved.', 'text', 'nelio-content' )
				),
				'description' => _x( 'Network template patches without profile frequency updates.', 'text', 'nelio-content' ),
			),
		);
	}

	/**
	 * @param lowercase-string $name Name suffix.
	 * @param string           $label Label.
	 * @param string           $description Description.
	 * @param list<string>     $required Required inputs.
	 * @return void
	 */
	protected function register_mutation( $name, $label, $description, $required ) {
		$properties = 'delete-automation-group' === $name ? array() : $this->mutation_properties();
		if ( 'create-automation-group' !== $name ) {
			$properties['group_id'] = $this->group_id_schema();
		}
		$properties['expected_revision']        = $this->revision_schema();
		$properties['confirm_collection_write'] = array(
			'type'        => 'boolean',
			'enum'        => array( true ),
			'description' => _x( 'Confirm that saving this individual change rewrites the Cloud-visible group collection. Concurrent browser/other-site writes cannot be atomically prevented. Inspect after uncertain results; never retry creation automatically.', 'text', 'nelio-content' ),
		);
		wp_register_ability(
			'nelio-content/' . $name,
			array(
				'label'               => $label,
				'description'         => $description . ' ' . _x( 'Requires Premium, plugin management, full social editing and access to every connected profile and saved profile reference, including network-template effects. Supply a revision from a fresh group read and explicitly confirm the collection-wide save. Unrelated returned groups and omitted settings are preserved. A stale revision is rejected before submission; a local lock and rechecks do not provide atomic Cloud concurrency. Errors after submission may leave a saved or partial change. Inspect configuration before deciding what to do next. This does not generate messages or update profile frequencies.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array_merge( $required, array( 'expected_revision', 'confirm_collection_write' ) ), _x( 'One explicit group mutation with a required observed collection revision and save confirmation. No raw collection replacement is accepted.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->object_schema(
					array(
						'group_id' => $this->group_id_schema(),
						'changed'  => array(
							'type'        => 'boolean',
							'description' => _x( 'Whether the verified request changed saved configuration. Equal normalized updates are no-ops.', 'text', 'nelio-content' ),
						),
						'deleted'  => array(
							'type'        => 'boolean',
							'description' => _x( 'Whether the identified regular group is absent after verified deletion. No posts or messages were deleted.', 'text', 'nelio-content' ),
						),
						'revision' => $this->revision_schema(),
					),
					array( 'group_id', 'changed', 'deleted', 'revision' ),
					_x( 'Verified operation identity and observed collection revision, without raw settings or templates.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return parent::check_operation_permissions( $input ) && 'all' === nelio_content_get_social_editor_permission();
	}
}

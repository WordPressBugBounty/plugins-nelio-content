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
					'description' => 'Attached, assignable taxonomy.',
				),
				'term_ids' => array(
					'type'        => 'array',
					'uniqueItems' => true,
					'maxItems'    => 100,
					'items'       => array(
						'type'    => 'integer',
						'minimum' => 1,
					),
					'description' => 'Existing assignable terms; empty means no restriction for this taxonomy.',
				),
			),
			array( 'taxonomy', 'term_ids' ),
			'One complete taxonomy selector; duplicate taxonomies are rejected.'
		);
		$template  = $this->object_schema(
			array(
				'template_id'  => $this->uuid_schema( 'Optional ID of an existing template in this exact group, source and promotion kind. Omit for a new controlled identity.' ),
				'text'         => array(
					'type'        => 'string',
					'minLength'   => 1,
					'maxLength'   => 10000,
					'description' => 'Literal template text, including supported placeholders. Text is saved without rendering or enforcing a rendered message length.',
				),
				'post_type'    => array(
					'type'        => 'string',
					'maxLength'   => 100,
					'description' => 'Optional enabled editable type for a universal-group template. Regular-group templates inherit their group type.',
				),
				'author_id'    => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => 'Optional existing author selector. Must be compatible with the group and editable content type.',
				),
				'taxonomies'   => array(
					'type'        => 'array',
					'maxItems'    => 100,
					'items'       => $term,
					'description' => 'Optional single-term selectors; each taxonomy must have exactly one term, compatible with the group.',
				),
				'target_name'  => array(
					'type'        => 'string',
					'maxLength'   => 500,
					'description' => 'Optional saved publication destination, such as a Pinterest board. Its current availability is evaluated when generating messages.',
				),
				'attachment'   => array(
					'type'        => 'string',
					'enum'        => array( 'none', 'image', 'video' ),
					'description' => 'Optional media preference supported by the network. Actual media is chosen when generating messages.',
				),
				'availability' => $this->availability_schema(),
			),
			array( 'text' ),
			'One replacement template. Identity, creator, network, source profile and group are assigned by the service; credentials and arbitrary settings are unsupported.'
		);
		$templates = array(
			'type'        => 'array',
			'maxItems'    => 100,
			'items'       => $template,
			'description' => 'Complete replacement of this source’s templates for this promotion kind. Empty explicitly removes them; omitted preserves them.',
		);
		$profile   = array( 'profile_id' => $this->uuid_schema( 'Connected accessible profile whose settings are being patched. Omitted profiles are preserved.' ) );
		foreach ( array( 'enabled', 'publication_enabled', 'reshare_enabled' ) as $key ) {
			$profile[ $key ] = array(
				'type'        => 'boolean',
				'description' => 'Requested saved switch. Publication/reshare switches are normalized to false without usable profile or network templates.',
			);
		}
		$profile['publication_templates'] = $templates;
		$profile['reshare_templates']     = $templates;
		return array(
			'name'             => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 200,
				'description' => 'Plain name for a regular group; unavailable for universal.',
			),
			'weight'           => array(
				'type'        => 'number',
				'minimum'     => 0,
				'maximum'     => 100,
				'description' => 'Relative group weight, default 100 on creation. Zero disables the group. A higher weight does not exclude other matching groups.',
			),
			'post_type'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 100,
				'description' => 'Enabled editable type for a regular group. Changing it normalizes its selectors and templates as in the editor. Unavailable for universal.',
			),
			'authors'          => array(
				'type'        => 'array',
				'uniqueItems' => true,
				'maxItems'    => 100,
				'items'       => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'description' => 'Complete saved author selector. Empty selects all authors; authors must exist and have editing access to the selected type. Types without author support clear author selectors.',
			),
			'taxonomies'       => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $term,
				'description' => 'Complete replacement of regular-group taxonomy selectors. Empty clears them; omitted preserves applicable selectors.',
			),
			'publication_rule' => $this->publication_rule_schema(),
			'profiles'         => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema( $profile, array( 'profile_id' ), 'Partial settings for one profile. At least one switch or template list is required.' ),
				'description' => 'Individual profile patches. Frequencies are not changed by this operation.',
			),
			'networks'         => array(
				'type'        => 'array',
				'maxItems'    => 100,
				'items'       => $this->object_schema(
					array(
						'network'               => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Connected network supporting network templates. Its templates may affect every associated profile.',
						),
						'publication_templates' => $templates,
						'reshare_templates'     => $templates,
					),
					array( 'network' ),
					'Partial network-template settings. At least one template list is required; omitted modes/networks are preserved.'
				),
				'description' => 'Network template patches without profile frequency updates.',
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
			'description' => 'Confirm that saving this individual change rewrites the group collection returned by Cloud. Another browser or site may save changes at the same time; this operation cannot prevent that. Inspect after uncertain results; never retry creation automatically.',
		);
		wp_register_ability(
			'nelio-content/' . $name,
			array(
				'label'               => $label,
				'description'         => $description . ' Requires Premium, plugin management, full social editing and access to every connected profile and saved profile reference, including network-template effects. Supply a revision from a fresh group read and explicitly confirm the save of the entire group collection. Unrelated returned groups and omitted settings are preserved. If the supplied revision no longer matches the collection, the request is rejected before saving. A local lock prevents overlapping changes through these tools. Rechecks may detect other changes, but cannot prevent another client from changing Cloud settings at the same time. Errors after submission may leave a saved or partial change. Inspect configuration before deciding what to do next. This does not generate messages or update profile frequencies.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array_merge( $required, array( 'expected_revision', 'confirm_collection_write' ) ), 'One group change, with the revision returned by a recent read of the collection and explicit confirmation to save it. Replacing the entire collection directly is not supported.' ),
				'output_schema'       => $this->object_schema(
					array(
						'group_id' => $this->group_id_schema(),
						'changed'  => array(
							'type'        => 'boolean',
							'description' => 'Whether the verified request changed saved configuration. If the settings already match after applying the normal setting rules, no changes are saved.',
						),
						'deleted'  => array(
							'type'        => 'boolean',
							'description' => 'Whether the identified regular group is absent after verified deletion. No posts or messages were deleted.',
						),
						'revision' => $this->revision_schema(),
					),
					array( 'group_id', 'changed', 'deleted', 'revision' ),
					'Identifiers for the confirmed change and the collection revision returned after it, without full settings or templates.'
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

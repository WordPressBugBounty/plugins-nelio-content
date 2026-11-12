<?php
/** Discoverable Premium contract for list-automation-groups. */
defined( 'ABSPATH' ) || exit;

/** Describes saved automation groups for plugin managers. */
class Nelio_Content_List_Automation_Groups_Ability extends Nelio_Content_Automation_Group_Ability {
	/** @return void */
	public function register() {
		$properties              = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['query']     = array(
			'type'        => 'string',
			'maxLength'   => 200,
			'description' => 'Optional case-insensitive substring of the plain group name; surrounding whitespace is trimmed.',
		);
		$properties['post_type'] = array(
			'type'        => 'string',
			'maxLength'   => 100,
			'description' => 'Optional enabled editable content type. Includes its regular groups and the universal group.',
		);
		$properties['enabled']   = array(
			'type'        => 'boolean',
			'description' => 'Optional group-weight filter: true selects positive weights; false selects zero-weight groups. Does not filter profile switches.',
		);
		wp_register_ability(
			'nelio-content/list-automation-groups',
			array(
				'label'               => _x( 'List automation groups', 'command', 'nelio-content' ),
				'description'         => 'Find saved automation groups, including universal and disabled groups, by name, content type or group weight. Returns saved selection criteria and the local plan limit. Requires Nelio Content Premium, plugin management and social editing permission. Regular groups for disabled or noneditable content types are unavailable. Taxonomy filters the current user cannot access are not included. A higher weight does not exclude other matching groups. Saved settings do not guarantee that messages will be generated. Reads configuration without modifying groups, templates, messages or scheduling.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array(), 'Explicit saved group lookup; no content generation or configuration changes.' ),
				'output_schema'       => $this->page_schema( $this->group_schema(), 'A page of saved group summaries, universal first and regular groups sorted by ID. Keep filters and page size when following next_cursor. No profile identities or counts are included.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-automation-groups';
	}
}

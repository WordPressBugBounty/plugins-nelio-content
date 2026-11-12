<?php
/** Discoverable Premium contract for get-automation-group. */
defined( 'ABSPATH' ) || exit;

/** Describes saved automation groups for plugin managers. */
class Nelio_Content_Get_Automation_Group_Ability extends Nelio_Content_Automation_Group_Ability {
	/** @return void */
	public function register() {
		$properties = array( 'group_id' => $this->group_id_schema() );
		wp_register_ability(
			'nelio-content/get-automation-group',
			array(
				'label'               => _x( 'Get automation group', 'command', 'nelio-content' ),
				'description'         => 'Inspect one saved automation group, its selection criteria, and configured switches and templates for accessible connected profiles, including disabled settings. Profile-restricted templates and unrelated credentials are excluded. Requires Nelio Content Premium, plugin management and social editing permission. Regular groups for disabled or noneditable content types are unavailable. Taxonomy filters the current user cannot access are not included. A higher weight does not exclude other matching groups. Saved settings do not guarantee that messages will be generated. Reads configuration without modifying groups, templates, messages or scheduling.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'group_id' ), 'Explicit saved group lookup; no content generation or configuration changes.' ),
				'output_schema'       => $this->group_schema( true ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-automation-group';
	}
}

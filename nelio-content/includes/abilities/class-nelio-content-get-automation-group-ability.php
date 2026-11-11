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
				'description'         => _x( 'Inspect one saved automation group, its selection criteria, and configured switches and templates for accessible connected profiles, including disabled settings. Profile-restricted templates and unrelated credentials are excluded. Requires Nelio Content Premium, plugin management and social editing permission. Regular groups for disabled or noneditable content types are unavailable. Taxonomy criteria are redacted when inaccessible. Weights are not exclusive precedence, and saved configuration does not guarantee message generation. Reads configuration without modifying groups, templates, messages or scheduling.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'group_id' ), _x( 'Explicit saved group lookup; no content generation or configuration changes.', 'text', 'nelio-content' ) ),
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

<?php
/**
 * Shared contract and Premium dispatch for the get-automation-settings ability.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes profile-scoped automation discovery in the shared catalog.
 *
 * @extends Nelio_Content_Automation_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Automation_Settings_Ability extends Nelio_Content_Automation_Ability {

	/**
	 * Registers the get-automation-settings ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-content/get-automation-settings',
			array(
				'label'               => _x( 'Get automation settings', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. Inspect publication and reshare automation settings for one accessible profile, optionally restricted to groups matching an editable post. Returns enabled settings and group weights; these do not guarantee that messages will be generated. Credentials and settings for other profiles are excluded.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->automation_query_schema(),
				'output_schema'       => $this->page_schema( $this->automation_settings_schema(), _x( 'One bounded page of profile-scoped automation records. Keep the same filters when following next_cursor. Configuration does not guarantee message generation.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}

	/**
	 * Identifies the paid implementation.
	 *
	 * @return string
	 */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-automation-settings';
	}
}

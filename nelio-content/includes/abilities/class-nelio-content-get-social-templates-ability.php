<?php
/**
 * Shared contract and Premium dispatch for the get-social-templates ability.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes profile-scoped automation discovery in the shared catalog.
 *
 * @extends Nelio_Content_Automation_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Social_Templates_Ability extends Nelio_Content_Automation_Ability {

	/**
	 * Registers the get-social-templates ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-content/get-social-templates',
			array(
				'label'               => _x( 'Get social templates', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. List configured publication and reshare templates enabled for one accessible profile, optionally matching an editable post. Network templates are included only for that profile. Group weights are not exclusive precedence: several groups can contribute. Placeholders are returned literally; Cloud still decides time, attachment and content eligibility during generation.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->automation_query_schema(),
				'output_schema'       => $this->page_schema( $this->social_template_schema(), _x( 'One bounded page of profile-scoped automation records. Keep the same filters when following next_cursor. Configuration does not guarantee message generation.', 'text', 'nelio-content' ) ),
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
		return 'nelio-content/get-social-templates';
	}
}

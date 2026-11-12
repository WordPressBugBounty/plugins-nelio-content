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
				'description'         => 'Requires Nelio Content Premium. List configured publication and reshare templates enabled for one accessible profile, optionally matching an editable post. Network templates are included only for that profile. A higher group weight does not exclude other matching groups; several groups can provide templates. Placeholders are returned literally; Cloud still decides time, attachment and content eligibility during generation.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->automation_query_schema(),
				'output_schema'       => $this->page_schema( $this->social_template_schema(), 'One page of automation settings for a single profile. Keep the same filters when following next_cursor. Configuration does not guarantee message generation.' ),
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

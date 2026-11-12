<?php
/**
 * Registration, authorization and execution of the get-social-profile-status ability.
 */

use function Nelio_Content\Abilities\query_social_profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-social-profile-status ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Social_Profile_Status_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-social-profile-status ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_schema = $this->profile_query_schema();

		wp_register_ability(
			'nelio-content/get-social-profile-status',
			array(
				'label'               => _x( 'Get social profile status', 'command', 'nelio-content' ),
				'description'         => 'Inspect accessible profiles for valid or renew status. Renew means reauthentication is needed. Does not reconnect profiles or send notifications.',
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $input_schema,
				'output_schema'       => $this->page_schema( $this->profile_schema(), 'A page of accessible profiles and their reported valid or renew connection state. No reconnection is performed.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Checks whether the current user may execute this ability.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Returns the requested page of accessible profile details.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		return query_social_profiles( $input, $this->profile_schema() );
	}
}

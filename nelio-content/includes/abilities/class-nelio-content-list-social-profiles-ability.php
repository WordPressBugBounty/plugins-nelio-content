<?php
/**
 * Registration, authorization and execution of the list-social-profiles ability.
 */

use function Nelio_Content\Abilities\query_social_profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the list-social-profiles ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_List_Social_Profiles_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the list-social-profiles ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_schema = $this->profile_query_schema();

		wp_register_ability(
			'nelio-content/list-social-profiles',
			array(
				'label'               => _x( 'List social profiles', 'command', 'nelio-content' ),
				'description'         => _x( 'List accessible connected profiles, their existing labels and available saved publication/reshare frequencies shared across automation groups. All requested labels must match. Status is valid or renew; no credentials or connection changes are returned.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $input_schema,
				'output_schema'       => $this->page_schema( $this->profile_schema(), _x( 'A page of accessible profiles with existing labels and safe account details. No credentials are returned.', 'text', 'nelio-content' ) ),
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
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot execute this ability.', 'user', 'nelio-content' ) );
		}

		return query_social_profiles( $input, $this->profile_schema() );
	}
}

<?php
/** Lists accessible publishing targets using the same Cloud endpoint as the editor. */

use function Nelio_Content\Abilities\get_social_profile_targets;
use function Nelio_Content\Abilities\paginate_items;

defined( 'ABSPATH' ) || exit;

/** @extends Nelio_Content_Ability<TAbility_Input> */
class Nelio_Content_List_Social_Profile_Targets_Ability extends Nelio_Content_Ability {

	/**
	 * Registers bounded target discovery without exposing connection settings.
	 *
	 * @return void
	 */
	public function register() {
		$query  = $this->query_properties();
		$target = $this->object_schema(
			array(
				'name'        => array(
					'type'        => 'string',
					'description' => _x( 'Exact publishing target name to use as profile_options.target_name, such as a Pinterest board identifier.', 'text', 'nelio-content' ),
				),
				'displayName' => array(
					'type'        => 'string',
					'description' => _x( 'Human-readable board or destination name reported by Nelio Cloud.', 'text', 'nelio-content' ),
				),
			),
			array( 'name', 'displayName' ),
			_x( 'One publishing destination within an accessible social profile.', 'text', 'nelio-content' )
		);
		wp_register_ability(
			'nelio-content/list-social-profile-targets',
			array(
				'label'               => _x( 'List social profile targets', 'command', 'nelio-content' ),
				'description'         => _x( 'List accessible destinations for a connected profile, such as Pinterest boards, before creating a message. Requires access to the profile. Returns destination names only, without credentials or connection changes.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $this->object_schema(
					array(
						'profile_id' => $this->uuid_schema( _x( 'Accessible connected social profile whose destinations should be listed.', 'text', 'nelio-content' ) ),
						'cursor'     => $query['cursor'],
						'per_page'   => $query['per_page'],
					),
					array( 'profile_id' ),
					_x( 'One accessible profile and optional bounded pagination over its publishing destinations.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->page_schema( $target, _x( 'A page of profile destination names suitable for use in per-profile publishing options.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Checks plugin access before target lookup.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return bool
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Returns a safe bounded page after checking actual profile visibility.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot access social profile targets.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
		}
		$targets = get_social_profile_targets( $input['profile_id'] ?? '' );
		return is_wp_error( $targets ) ? $targets : paginate_items( $targets, $input );
	}
}

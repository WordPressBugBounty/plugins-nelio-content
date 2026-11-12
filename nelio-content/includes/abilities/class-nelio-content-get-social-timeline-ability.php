<?php
/**
 * Registration, authorization and execution of the get-social-timeline ability.
 */

use function Nelio_Content\Abilities\query_social_messages;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-social-timeline ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Social_Timeline_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-social-timeline ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties = $this->message_query_properties();
		$required_fields  = array( 'start_date', 'end_date' );

		wp_register_ability(
			'nelio-content/get-social-timeline',
			array(
				'label'               => _x( 'Get social timeline', 'command', 'nelio-content' ),
				'description'         => 'Retrieve scheduled messages for individual profiles in a date range. This returns existing messages one page at a time; it does not generate a timeline. It excludes draft messages awaiting publication of their related post, even when they have planned dates. These drafts become scheduled automatically after post publication reaches Nelio Cloud. Use list-social-messages or get-post-social-status to inspect them; an empty scheduled timeline alone does not justify regeneration.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( array_diff_key( $input_properties, array( 'status' => true ) ), $required_fields, 'Date range and optional filters for one page of scheduled social message instances.' ),
				'output_schema'       => $this->page_schema( $this->message_schema(), 'One page containing only messages with status schedule. Drafts awaiting publication of a related post are excluded and activate automatically after post publication reaches Nelio Cloud.' ),
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
	 * Returns the scheduled timeline using the shared query.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		$input['status'] = 'schedule';
		return query_social_messages( $input, $this->message_schema() );
	}
}

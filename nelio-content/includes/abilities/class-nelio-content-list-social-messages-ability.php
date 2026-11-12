<?php
/**
 * Registration, authorization and execution of the list-social-messages ability.
 */

use function Nelio_Content\Abilities\query_social_messages;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the list-social-messages ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_List_Social_Messages_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the list-social-messages ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties = $this->message_query_properties();
		$required_fields  = array( 'start_date', 'end_date' );

		wp_register_ability(
			'nelio-content/list-social-messages',
			array(
				'label'               => _x( 'List social messages', 'command', 'nelio-content' ),
				'description'         => 'Retrieve one page of messages for individual profiles in an inclusive site-local date range. Follow next_cursor even when filtering leaves an empty page. Drafts linked to unpublished posts can already have planned dates and are scheduled automatically after post publication reaches Nelio Cloud. This is expected and does not require rescheduling. Undated drafts may not appear in date queries.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $input_properties, $required_fields, 'Date range and optional filters for one page of accessible social message instances.' ),
				'output_schema'       => $this->page_schema( $this->message_schema(), 'One page of messages for individual profiles matching the query and current user permissions.' ),
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
	 * Returns one filtered page of accessible social messages.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		return query_social_messages( $input, $this->message_schema() );
	}
}

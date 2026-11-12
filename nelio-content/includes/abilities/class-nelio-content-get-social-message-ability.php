<?php
/**
 * Registration, authorization and execution of the get-social-message ability.
 */

use function Nelio_Content\Abilities\query_social_messages;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-social-message ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Social_Message_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-social-message ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties               = $this->message_query_properties();
		$required_fields                = array( 'start_date', 'end_date' );
		$input_properties['message_id'] = $this->uuid_schema(
			'ID of the message for a single social profile to locate within the supplied date range.'
		);

		wp_register_ability(
			'nelio-content/get-social-message',
			array(
				'label'               => _x( 'Get social message', 'command', 'nelio-content' ),
				'description'         => 'Find an accessible message on one calendar page. Supply its date range and follow next_cursor until found or has_more is false. An empty page does not imply the message does not exist. A draft with planned dates linked to an unpublished post is expected: scheduling activates automatically after post publication reaches Nelio Cloud.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $input_properties, array_merge( $required_fields, array( 'message_id' ) ), 'Message ID, date range and optional filters for locating one message on a calendar page. Request the next pages until the message is found or no pages remain.' ),
				'output_schema'       => $this->page_schema( $this->message_schema(), 'A page containing the requested accessible message when found, or no items. An empty page is inconclusive while has_more is true.' ),
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

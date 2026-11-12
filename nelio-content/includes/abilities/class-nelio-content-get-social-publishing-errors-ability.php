<?php
/**
 * Registration, authorization and execution of the get-social-publishing-errors ability.
 */

use function Nelio_Content\Abilities\query_social_messages;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-social-publishing-errors ability.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Social_Publishing_Errors_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-social-publishing-errors ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_properties = $this->message_query_properties();
		$required_fields  = array( 'start_date', 'end_date' );

		wp_register_ability(
			'nelio-content/get-social-publishing-errors',
			array(
				'label'               => _x( 'Get social publishing errors', 'command', 'nelio-content' ),
				'description'         => 'List failed messages for individual profiles in a date range. Raw provider error text is excluded because it can contain credentials. Failure timestamps and retry eligibility are not available from this contract.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( array_diff_key( $input_properties, array( 'status' => true ) ), $required_fields, 'Date range and optional filters for one page of failed social message instances.' ),
				'output_schema'       => $this->page_schema( $this->message_schema(), 'One page of messages with status error. Unfiltered provider error details, failure timestamps and retry eligibility are not returned.' ),
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
	 * Returns failed publications using the shared query.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		$input['status'] = 'error';
		return query_social_messages( $input, $this->message_schema() );
	}
}

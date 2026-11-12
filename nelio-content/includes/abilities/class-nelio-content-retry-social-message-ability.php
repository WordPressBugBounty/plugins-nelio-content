<?php
/**
 * Retries a failed message through the existing immediate-publication operation.
 */

use function Nelio_Content\Abilities\request_cloud;

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Retry_Social_Message_Ability extends Nelio_Content_Social_Message_Mutation_Ability {

	/**
	 * Registers a retry with an explicit immediate-publication input.
	 *
	 * @return void
	 */
	public function register() {
		$properties                        = $this->mutation_properties();
		$properties['confirm_publication'] = array(
			'type'        => 'boolean',
			'enum'        => array( true ),
			'description' => 'Must be true to request an immediate publication attempt. This does not merely reschedule the message. Obtain the user\'s intent to publish before calling.',
		);
		wp_register_ability(
			'nelio-content/retry-social-message',
			array(
				'label'               => _x( 'Retry publishing a social message', 'command', 'nelio-content' ),
				'description'         => 'Request immediate publication of one failed, non-recurring message. Available in Free and Premium with social editing, profile and related-post permissions. Requires status error, a connected profile and confirm_publication true. Existing content and network settings are preserved by Cloud. This may publish immediately. Repeating the request may cause another publication attempt. Never automatically retry after a timeout or assume that accepting the request proves publication.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'message_id', 'start_date', 'end_date', 'confirm_publication' ), 'Locate the failed message on one page of calendar results and explicitly request an immediate publication attempt.' ),
				'output_schema'       => $this->message_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->mutation_metadata(),
			)
		);
	}

	/**
	 * Keeps Cloud's returned state rather than fabricating publication success.
	 *
	 * @param TAbility_Mutation_Input $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$message = $this->get_editable_message( $input, true );
		if ( is_wp_error( $message ) ) {
			return $message;
		}
		if ( 'error' !== ( $message['status'] ?? '' ) || true !== ( $input['confirm_publication'] ?? false ) ) {
			return new WP_Error( 'nelio_content_invalid_message_state', 'Retry requires a failed message and an explicit immediate-publication request.' );
		}
		$result = request_cloud( '/social/' . rawurlencode( $input['message_id'] ) . '/retry', 'PUT' );
		return is_wp_error( $result ) ? $result : $this->confirmed_message( $result, $message );
	}
}

<?php
/**
 * Deletes one unpublished message from Nelio Content.
 */

use function Nelio_Content\Abilities\request_cloud;

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Delete_Social_Message_Ability extends Nelio_Content_Social_Message_Mutation_Ability {

	/**
	 * Registers explicit single-message deletion.
	 *
	 * @return void
	 */
	public function register() {
		$properties          = $this->mutation_properties();
		$properties['scope'] = array(
			'type'        => 'string',
			'enum'        => array( 'single' ),
			'description' => 'Use single to delete exactly the identified non-recurring message. Deleting a recurrence series or following occurrences is unsupported.',
		);
		wp_register_ability(
			'nelio-content/delete-social-message',
			array(
				'label'               => _x( 'Delete a social message', 'command', 'nelio-content' ),
				'description'         => 'Delete one unpublished, non-recurring social message from Nelio Content. Available in Free and Premium with social editing, profile and related-post permissions. Published, sent and Free preview messages are rejected. This does not delete an already published post from a social network. No following-occurrence or series deletion. After an ambiguous failure inspect the calendar before retrying.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'message_id', 'start_date', 'end_date', 'scope' ), 'Locate one existing message on one page of calendar results and explicitly select single-message deletion.' ),
				'output_schema'       => $this->object_schema(
					array(
						'message_id' => $this->uuid_schema( 'ID of the message whose deletion Nelio Cloud acknowledged.' ),
						'deleted'    => array(
							'type'        => 'boolean',
							'description' => 'True when deletion of the specified non-recurring message was confirmed.',
						),
					),
					array( 'message_id', 'deleted' ),
					'Acknowledgement of one deletion in Nelio Content. No social network publication is deleted.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->mutation_metadata(),
			)
		);
	}

	/**
	 * Uses the existing single-message DELETE endpoint after object authorization.
	 *
	 * @param TAbility_Mutation_Input $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$message = $this->get_editable_message( $input );
		if ( is_wp_error( $message ) ) {
			return $message;
		}
		if ( 'single' !== ( $input['scope'] ?? '' ) ) {
			return new WP_Error( 'nelio_content_invalid_scope', 'Select single-message deletion explicitly.' );
		}
		$result = request_cloud( '/social/' . rawurlencode( $input['message_id'] ), 'DELETE' );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( array() !== $result ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', 'Cloud returned unexpected affected messages. Inspect the calendar before retrying deletion.' );
		}
		return array(
			'message_id' => $input['message_id'],
			'deleted'    => true,
		);
	}
}

<?php
/**
 * Shared contract and Premium dispatch for the create-social-message ability.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the ability in Free and delegates execution to the Premium plugin.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Create_Input>
 */
class Nelio_Content_Create_Social_Message_Ability extends Nelio_Content_Premium_Ability {

	/**
	 * Registers the create-social-message ability.
	 *
	 * @return void
	 */
	public function register() {
		$input_schema = $this->object_schema(
			$this->scheduled_message_properties(),
			array( 'content', 'profile_ids', 'scheduled_at', 'publication_mode' ),
			'Message text with optional post placeholders, target profiles and an explicit future publication time. Requires an active Premium subscription and social editing permissions.'
		);

		wp_register_ability(
			'nelio-content/create-social-message',
			array(
				'label'               => _x( 'Schedule social messages', 'command', 'nelio-content' ),
				'description'         => 'Create one future social message per selected profile on any supported Nelio Content network through Nelio Cloud. Requires Nelio Content Premium. Applies the text, media and publishing requirements of each network. Instagram, Pinterest and TikTok require media_id; Pinterest requires target_name; TikTok requires explicit settings; Medium and Blogger require post_id. Each valid profile is submitted separately. An invalid profile does not prevent creation for the others. Always inspect both items and errors, even when the tool succeeds. Invalid request structure, permissions, dates or attachments abort before writing. Unknown Cloud outcomes may already have created a message; inspect the calendar using the returned message_id before retrying. Buffer and Hootsuite connections are supported; Buffer uses the requested time instead of its managed queue. Message text and webhook headers/bodies support all Nelio Content post placeholders when post_id is supplied. Expanded text is checked against each network limit. No explicit draft creation mode, recurrence, multiple attachments or immediate publication support. Messages linked to an unpublished post can appear as drafts with planned dates; they are scheduled automatically when the post is published and its publication update reaches Nelio Cloud. Do not recreate or reschedule them solely because of this expected draft status. Related posts must be published or scheduled. Repeating the request can create another message. If a request times out and its result is unclear, inspect the calendar before trying again.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $input_schema,
				'output_schema'       => $this->object_schema(
					array(
						'items'  => array(
							'type'        => 'array',
							'items'       => $this->message_schema(),
							'maxItems'    => 10,
							'description' => 'Confirmed messages for successful profiles only. Messages linked to an unpublished post can later appear as drafts until publication of the post activates scheduling. This can be empty. Compare with errors to account for every requested profile.',
						),
						'errors' => array(
							'type'        => 'array',
							'items'       => $this->creation_error_schema(),
							'maxItems'    => 10,
							'description' => 'One failure for each profile without confirmed creation. not_created means no creation was submitted; unknown means Cloud may already have created message_id. Never automatically retry unknown outcomes.',
						),
					),
					array( 'items', 'errors' ),
					'Results contain exactly one confirmed item or error for each requested profile. Messages created for one profile are kept even if creation fails for another. A successful response can contain only errors; it does not imply that all messages were created.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/**
	 * Describes a safe per-profile failure and whether a write could have happened.
	 *
	 * @return array<string,mixed>
	 */
	private function creation_error_schema() {
		$properties = array(
			'profile_id' => $this->uuid_schema( 'Requested profile for which creation was not confirmed.' ),
			'code'       => array(
				'type'        => 'string',
				'description' => 'Stable local validation or Cloud execution error code. Raw provider errors are excluded.',
			),
			'message'    => array(
				'type'        => 'string',
				'description' => 'Human-readable reason and recovery guidance for this profile, without provider credentials or raw failure bodies.',
			),
			'outcome'    => array(
				'type'        => 'string',
				'enum'        => array( 'not_created', 'unknown' ),
				'description' => 'not_created means no creation was submitted for this profile. unknown means a request may have created the message; inspect the calendar before retrying.',
			),
			'message_id' => $this->uuid_schema( 'Generated message ID submitted for an unknown outcome. Use it with the profile and publication range to inspect the calendar before considering a retry.' ),
			'network'    => array(
				'type'        => 'string',
				'description' => 'Network associated with an accessible profile when included in local validation details. Omitted for inaccessible profiles.',
			),
			'rule'       => array(
				'type'        => 'string',
				'description' => 'Stable media validation rule, such as file_too_large, when a specific constraint failed.',
			),
		);
		foreach ( array(
			'limit'         => 'The network character limit exceeded by this text, using that network’s counting rules.',
			'actual_length' => 'The measured length of text that exceeds the limit, using the network’s character and link counting rules.',
		) as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => $description,
			);
		}
		return $this->object_schema( $properties, array( 'profile_id', 'code', 'message', 'outcome' ), 'Failure of one selected profile. The response distinguishes a request rejected before submission from a Cloud request whose result could not be confirmed, so the agent can avoid creating duplicates when retrying.' );
	}

	/**
	 * Identifies the Premium handler required by this contract.
	 *
	 * @return string
	 */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-social-message';
	}

	/**
	 * Preserves social editing and related post permissions before offering Premium.
	 *
	 * @param TAbility_Create_Input $input Validated ability input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		$permission = nelio_content_get_social_editor_permission();
		if ( 'none' === $permission ) {
			return false;
		}
		if ( ! empty( $input['post_id'] ) ) {
			return current_user_can( 'edit_post', $input['post_id'] ) && in_array( get_post_type( $input['post_id'] ), nelio_content_get_post_types( 'social' ), true );
		}
		return 'all' === $permission;
	}
}

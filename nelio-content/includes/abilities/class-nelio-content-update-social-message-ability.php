<?php
/**
 * Updates one existing simple social message through the editor's Cloud contract.
 */

use function Nelio_Content\Abilities\prepare_scheduled_social_message;
use function Nelio_Content\Abilities\request_cloud;

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Update_Social_Message_Ability extends Nelio_Content_Social_Message_Mutation_Ability {

	/**
	 * Registers the update contract in both editions.
	 *
	 * @return void
	 */
	public function register() {
		$scheduled                     = $this->scheduled_message_properties();
		$properties                    = $this->mutation_properties();
		$properties['content']         = $scheduled['content'];
		$properties['scheduled_at']    = $scheduled['scheduled_at'];
		$properties['profile_options'] = $scheduled['profile_options'];
		$properties['media_id']        = array(
			'type'        => 'integer',
			'minimum'     => 0,
			'description' => _x( 'Omit to preserve the existing single WordPress attachment, use a positive attachment ID to replace it, or use 0 to remove it. Networks such as Instagram, Pinterest and TikTok still require supported media. External or multiple attachments cannot be preserved by this ability.', 'text', 'nelio-content' ),
		);
		wp_register_ability(
			'nelio-content/update-social-message',
			array(
				'label'               => _x( 'Update a social message', 'command', 'nelio-content' ),
				'description'         => _x( 'Replace the text and publication time of one unpublished manual message on any supported Nelio Content network. Available in Free and Premium with social editing, profile and related-post permissions. The profile and related post remain fixed. Supports exact dates, network-specific options and one WordPress image or video; omitted media is preserved. Text and webhook headers/bodies support all Nelio Content post placeholders using the existing related post. Expanded text is checked against the network limit. Recurrences, automatic messages and multiple attachments are unsupported. Pinterest keeps its existing board; TikTok and webhook options are preserved when omitted. Medium and Blogger reuse the related post content. Messages linked to an unpublished post can remain or become drafts with planned dates; they are scheduled automatically when post publication reaches Nelio Cloud. This expected state alone does not require an update. This changes the existing message, never creates another. After an ambiguous failure inspect the calendar before retrying.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'message_id', 'start_date', 'end_date', 'content', 'scheduled_at' ), _x( 'Identify the current message by its calendar range and page, then provide replacement text with optional post placeholders and a future publication time. The date range locates the existing message, not its new publication time.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->message_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->mutation_metadata(),
			)
		);
	}

	/**
	 * Validates all replacements before sending the existing message's PUT request.
	 *
	 * @param TAbility_Mutation_Input $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$message = $this->get_editable_message( $input );
		if ( is_wp_error( $message ) ) {
			return $message;
		}
		if ( 'manual' !== ( $message['source'] ?? '' ) || 'exact' !== ( $message['dateType'] ?? '' ) || 'exact' !== ( $message['timeType'] ?? '' ) ||
			! in_array( $message['type'] ?? '', array( 'text', 'image', 'video', 'webhook' ), true ) || ! empty( $message['auto'] ) ) {
			return new WP_Error( 'nelio_content_unsupported_message', _x( 'Only manual, exact-date text, image, video or webhook messages can be updated. Use the social message editor for other messages.', 'user', 'nelio-content' ) );
		}
		$profile_id = $message['profileId'];
		if ( ! is_string( $profile_id ) ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unexpected Cloud message profile.', 'user', 'nelio-content' ) );
		}
		$options = ! empty( $input['profile_options'] ) ? $input['profile_options'] : array( array( 'profile_id' => $profile_id ) );
		foreach ( $options as &$option ) {
			if ( $option['profile_id'] !== $profile_id ) {
				continue;
			}
			$target = $message['targetName'] ?? 'default';
			if ( ! is_string( $target ) || ( isset( $option['target_name'] ) && $option['target_name'] !== $target ) ) {
				return new WP_Error( 'nelio_content_invalid_target', _x( 'Updating a message cannot change its publishing target.', 'user', 'nelio-content' ) );
			}
			$option['target_name'] = $target;
			foreach ( array(
				'tiktok_settings'  => 'tiktokSettings',
				'webhook_settings' => 'webhookSettings',
			) as $key => $field ) {
				if ( ! isset( $option[ $key ] ) && is_array( $message[ $field ] ?? null ) ) {
					$option[ $key ] = $message[ $field ];
				}
			}
			if ( ! isset( $option['plurk_qualifier'] ) && is_string( $message['plurkQualifier'] ?? null ) ) {
				$option['plurk_qualifier'] = $message['plurkQualifier'];
			}
			if ( 'medium' === $message['network'] && ! isset( $option['tags'] ) && is_array( $message['tags'] ?? null ) ) {
				$option['tags'] = array_values( array_filter( $message['tags'], 'is_string' ) );
			}
		}
		unset( $option );
		$replacement = array(
			'content'          => $input['content'] ?? '',
			'scheduled_at'     => $input['scheduled_at'] ?? '',
			'profile_ids'      => array( $profile_id ),
			'publication_mode' => 'scheduled',
			'profile_options'  => $options,
		);
		if ( ! empty( $message['postId'] ) && is_int( $message['postId'] ) ) {
			$replacement['post_id'] = $message['postId'];
		}
		$media_id      = $input['media_id'] ?? null;
		$preserved_alt = array();
		if ( null === $media_id ) {
			foreach ( array( 'image', 'video' ) as $type ) {
				if ( empty( $message[ $type ] ) ) {
					continue;
				}
				$id = $message[ $type . 'Id' ] ?? null;
				if ( ! is_string( $message[ $type ] ) || ! is_int( $id ) || $id < 1 || null !== $media_id ) {
					return new WP_Error( 'nelio_content_unsupported_media', _x( 'The existing media cannot be preserved. Provide media_id to explicitly replace or remove it.', 'user', 'nelio-content' ) );
				}
				$media_id = $id;
				if ( is_string( $message[ $type . 'AltText' ] ?? null ) ) {
					$preserved_alt[ $type . 'AltText' ] = $message[ $type . 'AltText' ];
				}
			}
		}
		if ( $media_id ) {
			$replacement['media_id'] = $media_id;
		}
		$body = prepare_scheduled_social_message( $replacement );
		if ( is_wp_error( $body ) ) {
			return $body;
		}
		$slot = $body['slots'][0] ?? null;
		// prepare_scheduled_social_message builds the same slots used by creation.
		if ( null === $slot ) {
			return new WP_Error( 'nelio_content_invalid_input', _x( 'Unable to prepare the message update.', 'user', 'nelio-content' ) );
		}
		unset( $body['slots'] );
		$body   = array_merge( $body, $slot, $preserved_alt, array( 'id' => $input['message_id'] ) );
		$result = request_cloud( '/social/' . rawurlencode( $input['message_id'] ), 'PUT', $body );
		return is_wp_error( $result ) ? $result : $this->confirmed_message( $result, $message );
	}
}

<?php
/**
 * Shared authorization and bounded lookup for mutations of existing social messages.
 */

use function Nelio_Content\Abilities\get_cloud_calendar_page;
use function Nelio_Content\Abilities\get_accessible_social_profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the actual message before trusting any of its profile or post attributes.
 *
 * @extends Nelio_Content_Ability<TAbility_Mutation_Input>
 */
abstract class Nelio_Content_Social_Message_Mutation_Ability extends Nelio_Content_Ability {

	/**
	 * Checks general access before reading Cloud data, including direct PHP execution.
	 *
	 * @param TAbility_Mutation_Input $input Validated ability input.
	 * @return bool
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input ) && 'none' !== nelio_content_get_social_editor_permission();
	}

	/**
	 * Inputs identify one message in one bounded calendar page.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function mutation_properties() {
		$properties = $this->query_properties();
		unset( $properties['post_id'] );
		$properties['message_id'] = $this->uuid_schema( _x( 'ID of the existing per-profile message to change. The message must be present in the supplied calendar range and page.', 'text', 'nelio-content' ) );
		return $properties;
	}

	/**
	 * Marks mutations as destructive and non-idempotent because Cloud has no revision contract.
	 *
	 * @return array<string,mixed>
	 */
	protected function mutation_metadata() {
		$metadata                = $this->get_ability_metadata( false );
		$metadata['annotations'] = array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		);
		return $metadata;
	}

	/**
	 * Finds the raw message and applies profile, post, state and recurrence checks.
	 * No mutation is sent on a missing, inaccessible or unsupported record.
	 *
	 * @param TAbility_Mutation_Input $input Validated ability input.
	 * @param bool                    $require_connected_profile Whether publication requires a connected profile.
	 * @return array<array-key,mixed>|WP_Error
	 */
	protected function get_editable_message( $input, $require_connected_profile = false ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot edit social messages.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
		}
		$page = get_cloud_calendar_page( 'messages', $input );
		if ( is_wp_error( $page ) ) {
			return $page;
		}
		$profiles = get_accessible_social_profiles();
		if ( is_wp_error( $profiles ) ) {
			return $profiles;
		}
		$profiles = array_column( $profiles, null, 'id' );
		foreach ( $page['items'] as $message ) {
			if ( ( $message['id'] ?? '' ) !== $input['message_id'] ) {
				continue;
			}
			$profile_id = $message['profileId'] ?? null;
			$profile    = is_string( $profile_id ) ? ( $profiles[ $profile_id ] ?? null ) : null;
			$post_id    = $message['postId'] ?? 0;
			if ( ! $profile || ! is_int( $post_id ) ||
				( $post_id && ( ! get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) ) ||
				( ! $post_id && 'all' !== nelio_content_get_social_editor_permission() ) ) {
				return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot edit this social message.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
			}
			if ( ( $message['network'] ?? null ) !== $profile['network'] ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The message does not match its connected social profile.', 'user', 'nelio-content' ) );
			}
			if ( $require_connected_profile && 'valid' !== $profile['status'] ) {
				return new WP_Error( 'nelio_content_invalid_profile', _x( 'Reconnect the social profile before retrying publication.', 'user', 'nelio-content' ) );
			}
			if ( ! in_array( $message['status'] ?? '', array( 'draft', 'schedule', 'error' ), true ) || ! empty( $message['sent'] ) || ! empty( $message['isFreePreview'] ) ) {
				return new WP_Error( 'nelio_content_invalid_message_state', _x( 'Published, sent and Free preview messages cannot be changed through this ability.', 'user', 'nelio-content' ) );
			}
			if ( ! empty( $message['recurrenceGroup'] ) || ! empty( $message['recurrenceSettings'] ) ) {
				return new WP_Error( 'nelio_content_unsupported_recurrence', _x( 'Recurring messages are unsupported. Manage the series in the Nelio Content calendar.', 'user', 'nelio-content' ) );
			}
			return $message;
		}
		return new WP_Error(
			'nelio_content_message_not_found',
			$page['has_more']
				/* translators: %s: Opaque cursor for the next calendar page. */
				? sprintf( _x( 'The message was not found on this calendar page. No change was made. Repeat with the same range and per_page, using this next_cursor: %s', 'user', 'nelio-content' ), $page['next_cursor'] )
				: _x( 'The message was not found in the remaining calendar records for this range. No change was made.', 'user', 'nelio-content' ),
			array(
				'has_more'    => $page['has_more'],
				'next_cursor' => $page['next_cursor'],
			)
		);
	}

	/**
	 * Verifies identity before exposing the safe mutation response.
	 *
	 * @param array<array-key,mixed> $result Cloud response.
	 * @param array<array-key,mixed> $original Authorized original message.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function confirmed_message( $result, $original ) {
		if ( ( $result['id'] ?? null ) !== $original['id'] || ( $result['profileId'] ?? null ) !== $original['profileId'] ||
			( $result['postId'] ?? 0 ) !== ( $original['postId'] ?? 0 ) || ( $result['network'] ?? null ) !== $original['network'] ||
			is_wp_error( rest_validate_value_from_schema( $result, array_merge( $this->message_schema(), array( 'additionalProperties' => true ) ) ) ) ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unable to confirm the changed message. Inspect the calendar before retrying; the operation may already have completed.', 'user', 'nelio-content' ) );
		}
		return \Nelio_Content\Abilities\project_fields( $result, $this->message_schema() );
	}
}

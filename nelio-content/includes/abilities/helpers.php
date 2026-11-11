<?php
/**
 * Cloud access, date validation, pagination, output projection and ability metadata.
 */

namespace Nelio_Content\Abilities;

use WP_Error;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use function Nelio_Content\Networks\get_network_requirements;
use function Nelio_Content\Networks\get_weighted_text_length;
use function Nelio_Content\Networks\get_media_rule_error;
use function Nelio_Content\Networks\network_requires_media;

defined( 'ABSPATH' ) || exit;

/**
 * Executes a site-scoped Cloud operation using the existing per-user authentication.
 *
 * @param string                      $path Path.
 * @param string                      $method Method.
 * @param array<array-key,mixed>|null $body Body.
 * @param list<string>                $expected_lists Expected JSON array fields; an empty field name requires an array at the response root.
 * @return array<array-key,mixed>|WP_Error
 */
function request_cloud( $path, $method = 'GET', $body = null, $expected_lists = array() ) {
	if ( ! is_user_logged_in() || ! nelio_content_can_current_user_use_plugin() ) {
		return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot use Nelio Content.', 'user', 'nelio-content' ) );
	}
	$site_id = nelio_content_get_site_id();
	if ( empty( $site_id ) ) {
		return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Nelio Content is not configured.', 'user', 'nelio-content' ) );
	}
	$token = nelio_content_generate_api_auth_token( 'skip-errors' );
	if ( empty( $token ) || 'unauthorized' === $token ) {
		return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unable to authenticate with Nelio Cloud.', 'user', 'nelio-content' ) );
	}
	$request_args = array(
		'method'              => $method,
		'limit_response_size' => 2 * MB_IN_BYTES,
		'timeout'             => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
		'headers'             => array(
			'Authorization' => 'Bearer ' . $token,
			'accept'        => 'application/json',
			'content-type'  => 'application/json',
		),
	);
	if ( null !== $body ) {
		$encoded = wp_json_encode( $body );
		if ( false === $encoded ) {
			return new WP_Error( 'nelio_content_invalid_input', _x( 'Unable to encode the Cloud request.', 'user', 'nelio-content' ) );
		}
		$request_args['body'] = $encoded;
	}
	$response = wp_remote_request( nelio_content_get_api_url( '/site/' . rawurlencode( $site_id ) . $path, 'wp' ), $request_args );
	if ( ! is_wp_error( $response ) && strlen( wp_remote_retrieve_body( $response ) ) > 2 * MB_IN_BYTES ) {
		return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The Cloud response exceeds the supported size limit.', 'user', 'nelio-content' ) );
	}
	$result = nelio_content_extract_response_body( $response );
	if ( ! is_wp_error( $response ) ) {
		$decoded = json_decode( wp_remote_retrieve_body( $response ) );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Nelio Cloud returned an invalid response.', 'user', 'nelio-content' ) );
		}
		foreach ( $expected_lists as $field ) {
			$collection = '' === $field ? $decoded : ( $decoded instanceof \stdClass ? ( $decoded->{$field} ?? null ) : null );
			if ( ! is_array( $collection ) ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The complete Cloud collection response could not be verified.', 'user', 'nelio-content' ) );
			}
		}
	}
	if ( is_wp_error( $result ) || ! is_array( $result ) ) {
		// Do not forward upstream bodies, headers, or arbitrary error descriptions.
		return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Nelio Cloud could not complete the operation.', 'user', 'nelio-content' ) );
	}
	return $result;
}

/**
 * Mirrors canCurrentUserUseProfile in the existing data store.
 *
 * @param array<array-key,mixed> $profile Profile.
 * @return bool
 */
function can_use_profile( $profile ) {
	$permissions = $profile['permissions'] ?? 'all-roles';
	return 'all-roles' === $permissions ||
		get_current_user_id() === ( $profile['creatorId'] ?? 0 ) ||
		( is_array( $permissions ) && in_array( nelio_content_get_current_user_role(), $permissions, true ) );
}

/**
 * Gets authorized profile fields for internal use, excluding credential-bearing settings.
 *
 * @param bool   $include_publishing_settings Include internal settings only while preparing Cloud writes.
 * @param bool   $require_all Require access to every profile for site-wide native automation effects.
 * @param bool   $strict_response Validate the entire profile response before producing aggregate summaries.
 * @param string $shortener_profile_id Preserve private shortener settings only for this profile during a write; never expose them.
 * @return list<TAbility_Profile>|WP_Error
 */
function get_accessible_social_profiles( $include_publishing_settings = false, $require_all = false, $strict_response = false, $shortener_profile_id = '' ) {
	$profiles = request_cloud( '/profiles', 'GET', null, $strict_response ? array( '' ) : array() );
	if ( is_wp_error( $profiles ) ) {
		return $profiles;
	}
	if ( $strict_response ) {
		if ( array_values( $profiles ) !== $profiles ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The complete profile response could not be verified.', 'user', 'nelio-content' ) );
		}
		$seen_profiles = array();
		foreach ( $profiles as $profile ) {
			if ( ! is_array( $profile ) || ! is_string( $profile['id'] ?? null ) || ! wp_is_uuid( $profile['id'] ) ||
				( array_key_exists( 'creatorId', $profile ) && ! is_int( $profile['creatorId'] ) ) ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The complete profile response could not be verified.', 'user', 'nelio-content' ) );
			}
			if ( isset( $seen_profiles[ $profile['id'] ] ) ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The complete profile response could not be verified.', 'user', 'nelio-content' ) );
			}
			$seen_profiles[ $profile['id'] ] = true;
			$permissions                     = array_key_exists( 'permissions', $profile ) ? $profile['permissions'] : 'all-roles';
			if ( ! in_array( $permissions, array( 'all-roles', 'creator-only' ), true ) && ( ! is_array( $permissions ) || count( array_filter( $permissions, 'is_string' ) ) !== count( $permissions ) ) ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The complete profile permissions could not be verified.', 'user', 'nelio-content' ) );
			}
		}
	}
	$result = array();
	foreach ( $profiles as $profile ) {
		if ( $require_all && ( ! is_array( $profile ) || ! can_use_profile( $profile ) ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'This site-wide automation operation requires access to every connected social profile.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
		}
		if ( ! is_array( $profile ) || ! can_use_profile( $profile ) ) {
			continue;
		}
		if ( ! isset( $profile['id'], $profile['displayName'], $profile['network'], $profile['username'], $profile['status'] ) ||
			! is_string( $profile['id'] ) || ! is_string( $profile['displayName'] ) || ! is_string( $profile['network'] ) || ! is_string( $profile['username'] ) || ! is_string( $profile['status'] ) ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unexpected Cloud profile response.', 'user', 'nelio-content' ) );
		}
		$safe = array(
			'id'          => $profile['id'],
			'displayName' => $profile['displayName'],
			'network'     => $profile['network'],
			'username'    => $profile['username'],
			'status'      => $profile['status'],
			'labels'      => is_array( $profile['labels'] ?? null ) ? array_values( array_filter( $profile['labels'], 'is_string' ) ) : array(),
			'alias'       => is_string( $profile['alias'] ?? null ) ? $profile['alias'] : '',
			'permissions' => is_array( $profile['permissions'] ?? null ) ? array_values( array_filter( $profile['permissions'], 'is_string' ) ) : 'all-roles',
			'creatorId'   => is_int( $profile['creatorId'] ?? null ) ? $profile['creatorId'] : 0,
			'isBuffer'    => ! empty( $profile['isBuffer'] ),
			'isHootsuite' => ! empty( $profile['isHootsuite'] ),
		);
		foreach ( array(
			'publicationFrequency' => 30,
			'reshareFrequency'     => 10,
		) as $field => $maximum ) {
			if ( isset( $profile[ $field ] ) && is_int( $profile[ $field ] ) && $profile[ $field ] >= 0 && $profile[ $field ] <= $maximum ) {
				$safe[ $field ] = $profile[ $field ];
			}
		}
		if ( $profile['id'] === $shortener_profile_id && array_key_exists( 'linkShortenerSettings', $profile ) ) {
			if ( ! is_array( $profile['linkShortenerSettings'] ) ) {
				return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'The saved profile settings could not be safely preserved.', 'user', 'nelio-content' ) );
			}
			$safe['linkShortenerSettings'] = $profile['linkShortenerSettings'];
		}
		if ( $include_publishing_settings && is_array( $profile['webhookSettings'] ?? null ) ) {
			$safe['webhookSettings'] = $profile['webhookSettings'];
		}
		$result[] = $safe;
	}
	return $result;
}

/**
 * Returns one upstream page, without following every Cloud cursor.
 *
 * @param string         $kind Kind.
 * @param TAbility_Input $input Validated ability input.
 * @return TAbility_Page|WP_Error
 */
function get_cloud_calendar_page( $kind, $input ) {
	$valid = validate_date_range( $input );
	if ( is_wp_error( $valid ) ) {
		return $valid;
	}
	$cursor = decode_cursor( $input );
	if ( is_wp_error( $cursor ) ) {
		return $cursor;
	}
	$query = array(
		'from' => $input['start_date'] ?? '',
		'to'   => $input['end_date'] ?? '',
	);
	if ( $cursor['page'] ) {
		$query['page'] = $cursor['page'];
	}
	$response = request_cloud( add_query_arg( $query, '/calendar/' . $kind ) );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( ! isset( $response[ $kind ] ) || ! is_array( $response[ $kind ] ) || ( isset( $response['page'] ) && ! is_string( $response['page'] ) ) ) {
		return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unexpected Cloud calendar response.', 'user', 'nelio-content' ) );
	}
	return paginate_items( array_values( array_filter( $response[ $kind ], 'is_array' ) ), $input, $response['page'] ?? '' );
}

/**
 * Validates real calendar days, ordering, and query size.
 *
 * @param TAbility_Input $input Validated ability input.
 * @return true|WP_Error
 */
function validate_date_range( $input ) {
	foreach ( array( 'start_date', 'end_date' ) as $key ) {
		$value = $input[ $key ] ?? '';
		$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
			return new WP_Error( 'nelio_content_invalid_date', _x( 'Use real calendar dates in YYYY-MM-DD format.', 'user', 'nelio-content' ) );
		}
	}
	$start = new DateTimeImmutable( $input['start_date'] ?? '', wp_timezone() );
	$end   = new DateTimeImmutable( $input['end_date'] ?? '', wp_timezone() );
	if ( $end < $start || $start->diff( $end )->days > 31 ) {
		return new WP_Error( 'nelio_content_invalid_date_range', _x( 'The date range must be ordered and span at most 31 days.', 'user', 'nelio-content' ) );
	}
	return true;
}

/**
 * Decodes a bounded pagination cursor. It can only influence an upstream page and an array offset.
 *
 * @param TAbility_Input $input Validated ability input.
 * @return array{page:string,offset:int}|WP_Error
 */
function decode_cursor( $input ) {
	if ( empty( $input['cursor'] ) ) {
		return array(
			'page'   => '',
			'offset' => 0,
		);
	}
	$decoded = base64_decode( $input['cursor'], true );
	$cursor  = false === $decoded ? null : json_decode( $decoded, true );
	if ( ! is_array( $cursor ) || ! isset( $cursor['page'], $cursor['offset'] ) || ! is_string( $cursor['page'] ) || ! is_int( $cursor['offset'] ) || $cursor['offset'] < 0 || $cursor['offset'] > 100000 ) {
		return new WP_Error( 'nelio_content_invalid_cursor', _x( 'Invalid pagination cursor.', 'user', 'nelio-content' ) );
	}
	return $cursor;
}

/**
 * Slices before filtering so restricted records never cause unbounded scans.
 *
 * @template T
 *
 * @param list<T>        $items Items.
 * @param TAbility_Input $input Validated ability input.
 * @param string         $next_page Next page.
 * @return array{items:list<T>,has_more:bool,next_cursor:string,timezone:string}|WP_Error
 */
function paginate_items( $items, $input, $next_page = '' ) {
	$cursor = decode_cursor( $input );
	if ( is_wp_error( $cursor ) ) {
		return $cursor;
	}
	$limit   = $input['per_page'] ?? 50;
	$offset  = $cursor['offset'];
	$next    = $offset + $limit < count( $items )
		? array(
			'page'   => $cursor['page'],
			'offset' => $offset + $limit,
		)
		: array(
			'page'   => $next_page,
			'offset' => 0,
		);
	$more    = $offset + $limit < count( $items ) || '' !== $next_page;
	$encoded = wp_json_encode( $next );
	if ( false === $encoded ) {
		return new WP_Error( 'nelio_content_invalid_cursor', _x( 'Unable to encode the pagination cursor.', 'user', 'nelio-content' ) );
	}
	return array(
		'items'       => array_slice( $items, $offset, $limit ),
		'has_more'    => $more,
		'next_cursor' => $more ? base64_encode( $encoded ) : '',
		'timezone'    => wp_timezone_string(),
	);
}

/**
 * Selects only declared output fields.
 *
 * @param array<array-key,mixed>                $item Item.
 * @param array{properties:array<string,mixed>} $schema Schema.
 * @return array<string,mixed>
 */
function project_fields( $item, $schema ) {
	$result = array();
	foreach ( $schema['properties'] as $key => $property ) {
		if ( array_key_exists( $key, $item ) ) {
			$result[ $key ] = $item[ $key ];
		}
	}
	return $result;
}



/**
 * Returns one filtered Cloud page, preserving native message instances.
 *
 * @param TAbility_Input                        $input Validated ability input.
 * @param array{properties:array<string,mixed>} $schema Safe output projection.
 * @return TAbility_Page|WP_Error
 */
function query_social_messages( $input, $schema ) {
	$page = get_cloud_calendar_page( 'messages', $input );
	if ( is_wp_error( $page ) ) {
		return $page;
	}
	$profiles = get_accessible_social_profiles();
	if ( is_wp_error( $profiles ) ) {
		return $profiles;
	}
	$profile_ids   = array_column( $profiles, 'id' );
	$page['items'] = array_values(
		array_filter(
			$page['items'],
			function ( $message ) use ( $input, $profile_ids ) {
				// Visibility checks apply before any optional search filters.
				if ( ! in_array( $message['profileId'] ?? '', $profile_ids, true ) ) {
					return false;
				}
				if ( ! empty( $message['postId'] ) && ! current_user_can( 'read_post', $message['postId'] ) ) {
					return false;
				}

				if ( ! empty( $input['post_id'] ) && ( $message['postId'] ?? 0 ) !== $input['post_id'] ) {
					return false;
				}
				if ( ! empty( $input['authors'] ) && ! in_array( $message['postAuthor'] ?? 0, $input['authors'], true ) ) {
					return false;
				}
				if ( ! empty( $input['profile_ids'] ) && ! in_array( $message['profileId'], $input['profile_ids'], true ) ) {
					return false;
				}
				if ( ! empty( $input['status'] ) && ( $message['status'] ?? '' ) !== $input['status'] ) {
					return false;
				}
				return empty( $input['message_id'] ) || ( $message['id'] ?? '' ) === $input['message_id'];
			}
		)
	);
	$page['items'] = array_map( fn( $message ) => project_fields( $message, $schema ), $page['items'] );
	return $page;
}

/**
 * Returns filtered, safe profiles.
 *
 * @param TAbility_Input                        $input Validated ability input.
 * @param array{properties:array<string,mixed>} $schema Safe output projection.
 * @return TAbility_Page|WP_Error
 */
function query_social_profiles( $input, $schema ) {
	$items = get_accessible_social_profiles();
	if ( is_wp_error( $items ) ) {
		return $items;
	}
	$items = array_values(
		array_filter(
			$items,
			function ( $profile ) use ( $input ) {
				return ( empty( $input['network'] ) || $profile['network'] === $input['network'] ) &&
				( empty( $input['profile_id'] ) || $profile['id'] === $input['profile_id'] ) &&
				( empty( $input['status'] ) || $profile['status'] === $input['status'] ) &&
				! array_diff( $input['labels'] ?? array(), $profile['labels'] );
			}
		)
	);
	$page  = paginate_items( $items, $input );
	if ( is_wp_error( $page ) ) {
		return $page;
	}
	$page['items'] = array_map( fn( $profile ) => project_fields( $profile, $schema ), $page['items'] );
	return $page;
}

/**
 * Resolves permitted attachment metadata without downloading arbitrary URLs.
 *
 * @param int $attachment_id WordPress media library attachment ID.
 * @return TAbility_Media|WP_Error
 */
function get_message_media( $attachment_id ) {
	$attachment = get_post( $attachment_id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'Select an existing image or video from the WordPress media library.', 'user', 'nelio-content' ) );
	}
	if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'read_post', $attachment_id ) ) {
		return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot use this media attachment.', 'user', 'nelio-content' ) );
	}
	$mime = get_post_mime_type( $attachment );
	$type = is_string( $mime ) && 0 === strpos( $mime, 'image/' ) ? 'image' : 'video';
	if ( ! is_string( $mime ) || ( 0 !== strpos( $mime, 'image/' ) && 0 !== strpos( $mime, 'video/' ) ) ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'The attachment must be an image or video.', 'user', 'nelio-content' ) );
	}
	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url || ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'The attachment must have an HTTP or HTTPS URL accessible to Nelio Cloud.', 'user', 'nelio-content' ) );
	}
	$metadata = wp_get_attachment_metadata( $attachment_id );
	$metadata = is_array( $metadata ) ? $metadata : array();
	$file     = get_attached_file( $attachment_id );
	$size     = $file && is_file( $file ) ? filesize( $file ) : ( $metadata['filesize'] ?? 0 );
	if ( ! is_numeric( $size ) || $size <= 0 ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'The attachment file size is unavailable. Regenerate its metadata before scheduling.', 'user', 'nelio-content' ) );
	}
	if ( 'image' === $type && ( empty( $metadata['width'] ) || empty( $metadata['height'] ) ) ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'Image dimensions are unavailable. Regenerate the attachment metadata before scheduling.', 'user', 'nelio-content' ) );
	}
	$media = array(
		'id'              => $attachment_id,
		'url'             => $url,
		'type'            => $type,
		'mime'            => $mime,
		'filesizeInBytes' => (int) $size,
	);
	if ( isset( $metadata['length'] ) && is_numeric( $metadata['length'] ) ) {
		$media['duration'] = (float) $metadata['length'];
	}
	if ( 'image' === $type ) {
		$media['width']   = $metadata['width'];
		$media['height']  = $metadata['height'];
		$alt_text         = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$media['altText'] = $alt_text;
	}
	return $media;
}

/**
 * Validates a simple scheduled message using the editor's network and media rules.
 *
 * @param TAbility_Create_Input $input Message text, profiles, media and date.
 * @return array{slots:list<array<string,mixed>>, ...<string,mixed>}|WP_Error
 */
function prepare_scheduled_social_message( $input ) {
	$batch = prepare_social_message_batch( $input );
	if ( is_wp_error( $batch ) ) {
		return $batch;
	}
	if ( $batch['errors'] ) {
		$error = $batch['errors'][0];
		return new WP_Error( $error['code'], $error['message'], $error );
	}
	return $batch['body'];
}

/**
 * Validates common inputs and prepares independent per-profile creation outcomes.
 *
 * @param TAbility_Create_Input $input Message text, profiles, media and date.
 * @return array{body:array{slots:list<array<string,mixed>>, ...<string,mixed>},errors:list<TAbility_Creation_Error>}|WP_Error
 */
function prepare_social_message_batch( $input ) {
	$text = trim( $input['content'] );
	if ( preg_match( '/[<>]/u', $text ) ) {
		return new WP_Error( 'nelio_content_invalid_content', _x( 'Provide plain message text without HTML.', 'user', 'nelio-content' ) );
	}
	try {
		$date   = new DateTimeImmutable( $input['scheduled_at'] );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) {
			return new WP_Error( 'nelio_content_invalid_date', _x( 'Invalid publication date.', 'user', 'nelio-content' ) );
		}
		// The existing editor and Cloud contract schedule with minute precision.
		$date = $date->setTime( (int) $date->format( 'H' ), (int) $date->format( 'i' ), 0 );
	} catch ( Exception $e ) {
		return new WP_Error( 'nelio_content_invalid_date', _x( 'Invalid publication date.', 'user', 'nelio-content' ) );
	}
	if ( $date->getTimestamp() < time() + 5 * MINUTE_IN_SECONDS ) {
		return new WP_Error( 'nelio_content_invalid_date', _x( 'Schedule publication at least five minutes in the future.', 'user', 'nelio-content' ) );
	}
	$post = ! empty( $input['post_id'] ) ? get_post( $input['post_id'] ) : null;
	if ( $post && ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
		return new WP_Error( 'nelio_content_invalid_post', _x( 'Related posts must be published or scheduled.', 'user', 'nelio-content' ) );
	}
	if ( ! $post ) {
		$computed = \Nelio_Content_Social_Message_Text::compute( $text, false, 0 );
		if ( is_wp_error( $computed ) ) {
			return $computed;
		}
	}
	$length = get_weighted_text_length( $text );
	if ( is_wp_error( $length ) ) {
		return $length;
	}
	$requirements = get_network_requirements();
	if ( is_wp_error( $requirements ) ) {
		return $requirements;
	}
	$media = isset( $input['media_id'] ) ? get_message_media( $input['media_id'] ) : null;
	if ( is_wp_error( $media ) ) {
		return $media;
	}
	$profiles = get_accessible_social_profiles( true );
	if ( is_wp_error( $profiles ) ) {
		return $profiles;
	}
	$options = array();
	foreach ( $input['profile_options'] ?? array() as $option ) {
		if ( isset( $options[ $option['profile_id'] ] ) || ! in_array( $option['profile_id'], $input['profile_ids'], true ) ) {
			return new WP_Error( 'nelio_content_invalid_network_options', _x( 'Provide at most one options object for each selected profile, without unselected profile IDs.', 'user', 'nelio-content' ) );
		}
		$options[ $option['profile_id'] ] = $option;
	}
	$post_data = $post ? \Nelio_Content_Post_Helper::instance()->post_to_json( $post ) : false;
	$profiles  = array_column( $profiles, null, 'id' );
	$slots     = array();
	$errors    = array();
	foreach ( $input['profile_ids'] as $profile_id ) {
		$profile = $profiles[ $profile_id ] ?? null;
		if ( ! $profile || 'valid' !== $profile['status'] || empty( $requirements[ $profile['network'] ]['abilitySupported'] ) ) {
			$error    = new WP_Error( 'nelio_content_invalid_profile', _x( 'Select an accessible, connected profile from a supported Nelio Content network.', 'user', 'nelio-content' ) );
			$errors[] = social_message_creation_error( $profile_id, $error );
			continue;
		}
		$slot = prepare_social_message_slot( $profile, $requirements[ $profile['network'] ], $options[ $profile_id ] ?? array( 'profile_id' => $profile_id ), $post_data, $media, $text );
		if ( is_wp_error( $slot ) ) {
			$errors[] = social_message_creation_error( $profile_id, $slot );
			continue;
		}
		$slots[] = $slot;
	}
	$local = $date->setTimezone( wp_timezone() );
	$body  = array(
		'baseDatetime' => 'none',
		'dateType'     => 'exact',
		'dateValue'    => $local->format( 'Y-m-d' ),
		'timeType'     => 'exact',
		'timeValue'    => $local->format( 'H:i' ),
		'schedule'     => $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( DATE_ATOM ),
		'timezone'     => nelio_content_get_timezone(),
		'source'       => 'manual',
		'text'         => $text,
		'textComputed' => $text,
		'slots'        => $slots,
	);
	if ( $media ) {
		$body[ $media['type'] ]        = $media['url'];
		$body[ $media['type'] . 'Id' ] = $media['id'];
		if ( 'image' === $media['type'] ) {
			$body['imageAltText'] = $media['altText'] ?? '';
		}
	}
	if ( $post ) {
		$body['postId']     = $post->ID;
		$body['postType']   = $post->post_type;
		$body['postAuthor'] = (int) $post->post_author;
	}
	return array(
		'body'   => $body,
		'errors' => $errors,
	);
}

/**
 * Prepares one authorized target using the shared network rules, without writing to Cloud.
 *
 * @param TAbility_Profile         $profile Accessible connected profile.
 * @param TNetwork_Requirements    $rules Bundled network rules.
 * @param TAbility_Profile_Options $options Options for this profile.
 * @param TPost|false              $post_data Authorized related post projection.
 * @param TAbility_Media|null      $media Resolved attachment.
 * @param string                   $text Original text, optionally with placeholders.
 * @return array<string,mixed>|WP_Error
 */
function prepare_social_message_slot( $profile, $rules, $options, $post_data, $media, $text ) {
	if ( $media && ! in_array( $media['type'], $rules['supports'], true ) ) {
		return new WP_Error( 'nelio_content_invalid_media', _x( 'A selected social network does not support this media format. Use separate requests for incompatible networks.', 'user', 'nelio-content' ) );
	}
	$slot_options = \Nelio_Content_Social_Message_Options::prepare( $profile, $options, $post_data );
	if ( is_wp_error( $slot_options ) ) {
		return $slot_options;
	}
	if ( $post_data && is_string( $slot_options['permalink'] ?? null ) ) {
		$post_data['permalink'] = $slot_options['permalink'];
	}
	$computed = \Nelio_Content_Social_Message_Text::compute( $text, $post_data, $rules['limit'], true );
	if ( is_wp_error( $computed ) ) {
		return $computed;
	}
	$resolved = $post_data ? str_replace( '{permalink}', $post_data['permalink'], $computed ) : $computed;
	$length   = get_weighted_text_length( $resolved );
	if ( is_wp_error( $length ) ) {
		return $length;
	}
	if ( '' === $resolved && in_array( 'text', $rules['supports'], true ) && 'tiktok' !== $profile['network'] ) {
		return new WP_Error( 'nelio_content_invalid_content', _x( 'This social network requires non-empty message text after expanding placeholders.', 'user', 'nelio-content' ) );
	}
	$details = array(
		'profile_id' => $profile['id'],
		'network'    => $profile['network'],
	);
	if ( $length > $rules['limit'] ) {
		return new WP_Error(
			'nelio_content_content_too_long',
			_x( 'The message exceeds the text limit for a selected social network. Shorten it or schedule separate messages.', 'user', 'nelio-content' ),
			array_merge(
				$details,
				array(
					'limit'         => $rules['limit'],
					'actual_length' => $length,
				)
			)
		);
	}
	if ( ! $media && network_requires_media( $rules ) ) {
		return new WP_Error( 'nelio_content_media_required', _x( 'A selected social network requires an image or video. Provide a media_id from the WordPress media library.', 'user', 'nelio-content' ), $details );
	}
	if ( $media ) {
		$media_rules = $rules['media'][ $media['type'] ] ?? null;
		$error       = $media_rules ? get_media_rule_error( $media_rules, $media ) : 'unsupported_media';
		if ( '' !== $error ) {
			return new WP_Error( 'nelio_content_invalid_media', _x( 'The attachment does not meet the requirements of a selected social network. Check get-social-network-requirements for its media constraints.', 'user', 'nelio-content' ), array_merge( $details, array( 'rule' => $error ) ) );
		}
	}
	return array_merge(
		array(
			'id'           => wp_generate_uuid4(),
			'profileId'    => $profile['id'],
			'network'      => $profile['network'],
			'targetName'   => 'default',
			'textComputed' => $computed,
			'type'         => 'webhook' === $profile['network'] ? 'webhook' : ( $media ? $media['type'] : 'text' ),
		),
		$slot_options
	);
}

/**
 * Retrieves only safe target identifiers after checking profile visibility.
 *
 * @param string $profile_id Connected profile ID.
 * @return list<array{name:string,displayName:string}>|WP_Error
 */
function get_social_profile_targets( $profile_id ) {
	$profiles = get_accessible_social_profiles();
	if ( is_wp_error( $profiles ) ) {
		return $profiles;
	}
	$profiles = array_column( $profiles, null, 'id' );
	if ( ! isset( $profiles[ $profile_id ] ) ) {
		return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot access this social profile.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
	}
	if ( 'pinterest' !== $profiles[ $profile_id ]['network'] ) {
		return array();
	}
	$targets = request_cloud( '/profile/' . rawurlencode( $profile_id ) . '/targets' );
	if ( is_wp_error( $targets ) ) {
		return $targets;
	}
	$result = array();
	foreach ( $targets as $target ) {
		if ( ! is_array( $target ) || ! is_string( $target['name'] ?? null ) || ! is_string( $target['displayName'] ?? null ) ) {
			return new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unexpected Cloud profile target response.', 'user', 'nelio-content' ) );
		}
		$result[] = array(
			'name'        => $target['name'],
			'displayName' => $target['displayName'],
		);
	}
	return $result;
}

/**
 * Projects a local creation failure to stable, safe fields for the public response.
 *
 * @param string                  $profile_id Requested profile ID.
 * @param WP_Error                $error Sanitized validation or execution error.
 * @param 'not_created'|'unknown' $outcome Whether a creation could have reached Cloud.
 * @param string                  $message_id Attempted message ID, only when the outcome is unknown.
 * @return TAbility_Creation_Error
 */
function social_message_creation_error( $profile_id, $error, $outcome = 'not_created', $message_id = '' ) {
	$result = array(
		'profile_id' => $profile_id,
		'code'       => (string) $error->get_error_code(),
		'message'    => $error->get_error_message(),
		'outcome'    => $outcome,
	);
	if ( 'unknown' === $outcome && '' !== $message_id ) {
		$result['message_id'] = $message_id;
	}
	$details = $error->get_error_data();
	if ( is_array( $details ) ) {
		foreach ( array( 'limit', 'actual_length' ) as $key ) {
			if ( isset( $details[ $key ] ) && is_int( $details[ $key ] ) ) {
				$result[ $key ] = $details[ $key ];
			}
		}
		foreach ( array( 'rule', 'network' ) as $key ) {
			if ( isset( $details[ $key ] ) && is_string( $details[ $key ] ) ) {
				$result[ $key ] = $details[ $key ];
			}
		}
	}
	return $result;
}

/**
 * Projects saved post identity and publication information without rendering content.
 *
 * @param \WP_Post $post Post.
 * @return array<string,mixed>
 */
function summarize_post( $post ) {
	$date = '0000-00-00 00:00:00' === $post->post_date_gmt ? false : get_post_datetime( $post );
	return array(
		'id'               => $post->ID,
		'title'            => wp_strip_all_tags( $post->post_title ),
		'post_type'        => $post->post_type,
		'status'           => $post->post_status,
		'author_id'        => (int) $post->post_author,
		'publication_date' => $date ? $date->format( DATE_ATOM ) : '',
	);
}

/**
 * Encodes a bounded local source offset using the existing list cursor contract.
 *
 * @param list<array<string,mixed>> $items Visible summaries from this source page.
 * @param int                       $next_offset Next source offset.
 * @param bool                      $has_more Whether additional source records remain.
 * @return TAbility_Page|WP_Error
 */
function local_post_page( $items, $next_offset, $has_more ) {
	$encoded = wp_json_encode(
		array(
			'page'   => '',
			'offset' => $next_offset,
		)
	);
	if ( false === $encoded ) {
		return new WP_Error( 'nelio_content_invalid_cursor', _x( 'Unable to encode the pagination cursor.', 'user', 'nelio-content' ) );
	}
	return array(
		'items'       => $items,
		'has_more'    => $has_more,
		'next_cursor' => $has_more ? base64_encode( $encoded ) : '',
		'timezone'    => wp_timezone_string(),
	);
}

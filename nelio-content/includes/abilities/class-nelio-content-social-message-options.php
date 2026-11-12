<?php
/**
 * Describes and validates the editor's per-profile publishing options.
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Social_Message_Options {

	/**
	 * Closed schema shared by creation, updates and stored-option validation.
	 *
	 * @return array{properties:array<string,array<string,mixed>>, ...<string,mixed>}
	 */
	public static function schema() {
		$pair   = self::object_schema(
			array(
				'key'   => array(
					'type'        => 'string',
					'maxLength'   => 1000,
					'description' => 'Header or form field name, supporting post placeholders.',
				),
				'value' => array(
					'type'        => 'string',
					'maxLength'   => 10000,
					'description' => 'Header or form field value, supporting post placeholders. Values are never returned in ability responses.',
				),
			),
			array( 'key', 'value' ),
			'One webhook header or form field.'
		);
		$tiktok = array(
			'privacyLevel' => array(
				'type'        => 'string',
				'enum'        => array( 'PUBLIC_TO_EVERYONE', 'MUTUAL_FOLLOW_FRIENDS', 'FOLLOWER_OF_CREATOR', 'SELF_ONLY' ),
				'description' => 'Explicit TikTok audience chosen for this publication. Provider account restrictions still apply.',
			),
		);
		foreach ( array(
			'disableDuet'     => 'Disable duets for this TikTok publication.',
			'disableStitch'   => 'Disable stitching for this TikTok publication.',
			'disableComment'  => 'Disable comments for this TikTok publication.',
			'discloseContent' => 'Declare whether this TikTok publication promotes a brand, product or service.',
			'isBrandContent'  => 'The TikTok content promotes a third-party brand. Requires disclosure and cannot use SELF_ONLY.',
			'isBrandOrganic'  => 'The TikTok content promotes your own brand. Requires content disclosure.',
		) as $key => $description ) {
			$tiktok[ $key ] = array(
				'type'        => 'boolean',
				'description' => $description,
			);
		}
		return self::object_schema(
			array(
				'profile_id'       => array(
					'type'        => 'string',
					'format'      => 'uuid',
					'description' => 'ID of one selected profile to which these options apply. Supply at most one options object per profile.',
				),
				'target_name'      => array(
					'type'        => 'string',
					'minLength'   => 1,
					'maxLength'   => 500,
					'description' => 'Exact target name returned by list-social-profile-targets. Required for Pinterest boards. Updates preserve the existing target and cannot move a message to another target.',
				),
				'tiktok_settings'  => self::object_schema( $tiktok, array_keys( $tiktok ), 'Explicit TikTok audience, interaction and commercial disclosure settings. Required when creating a TikTok message; updates preserve existing settings when omitted.' ),
				'plurk_qualifier'  => array(
					'type'        => 'string',
					'enum'        => array( 'plays', 'buys', 'sells', 'loves', 'likes', 'shares', 'hates', 'wants', 'wishes', 'needs', 'has', 'will', 'hopes', 'asks', 'wonders', 'feels', 'thinks', 'draws', 'is', 'says', 'eats', 'writes', 'whispers' ),
					'description' => 'Optional Plurk qualifier. Omit to use the provider default, or preserve it when updating.',
				),
				'tags'             => array(
					'type'        => 'array',
					'maxItems'    => 5,
					'uniqueItems' => true,
					'items'       => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 100,
						'description' => 'One Medium tag for the republished article.',
					),
					'description' => 'Up to five Medium tags. Blogger uses the related post tags automatically.',
				),
				'webhook_settings' => self::object_schema(
					array(
						'url'         => array(
							'type'        => 'string',
							'format'      => 'uri',
							'maxLength'   => 4096,
							'description' => 'Fully qualified HTTP or HTTPS destination URL for the scheduled webhook request. Only used for a webhook profile.',
						),
						'method'      => array(
							'type'        => 'string',
							'enum'        => array( 'POST', 'GET', 'HEAD', 'PATCH', 'PUT', 'DELETE' ),
							'description' => 'HTTP method executed by Nelio Cloud at the scheduled time.',
						),
						'headers'     => array(
							'type'        => 'array',
							'maxItems'    => 50,
							'items'       => $pair,
							'description' => 'Webhook request headers supporting post placeholders. Credentials are accepted as input but never exposed in output.',
						),
						'bodyType'    => array(
							'type'        => 'string',
							'enum'        => array( 'none', 'json', 'form' ),
							'description' => 'Webhook body encoding. JSON requires a valid JSON string; form requires a list of key/value fields; none omits the body.',
						),
						'bodyContent' => array(
							'type'        => array( 'string', 'array' ),
							'maxLength'   => 100000,
							'maxItems'    => 100,
							'items'       => $pair,
							'description' => 'JSON string or form fields matching bodyType, supporting post placeholders. Expanded JSON must remain valid JSON.',
						),
					),
					array( 'url', 'method', 'headers', 'bodyType' ),
					'Optional per-message webhook configuration. Headers and bodies support post placeholders with a related post; URL and method must be literal. Omit to use the profile settings or preserve the message settings on update.'
				),
			),
			array( 'profile_id' ),
			'Publishing options for one selected social profile. Only supply options applicable to that profile network.'
		);
	}

	/**
	 * Builds the editor's per-profile slot attributes after checking network-specific inputs.
	 *
	 * @param TAbility_Profile         $profile Accessible selected profile, with internal settings.
	 * @param TAbility_Profile_Options $options Explicit per-profile options.
	 * @param TPost|false              $post Authorized related post, using the existing post helper projection.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function prepare( $profile, $options, $post ) {
		if ( is_wp_error( rest_validate_value_from_schema( $options, self::schema() ) ) ) {
			return new WP_Error( 'nelio_content_invalid_network_options', 'The publishing options do not match the selected network option schema.' );
		}
		$network = $profile['network'];
		foreach ( array(
			'tiktok_settings'  => 'tiktok',
			'plurk_qualifier'  => 'plurk',
			'tags'             => 'medium',
			'webhook_settings' => 'webhook',
		) as $option => $owner ) {
			if ( isset( $options[ $option ] ) && $owner !== $network ) {
				return new WP_Error( 'nelio_content_invalid_network_options', 'Only provide publishing options applicable to the selected profile network.' );
			}
		}
		$slot = array();
		if ( 'pinterest' === $network ) {
			$targets = \Nelio_Content\Abilities\get_social_profile_targets( $profile['id'] );
			if ( is_wp_error( $targets ) ) {
				return $targets;
			}
			$selected = $options['target_name'] ?? '';
			$targets  = array_column( $targets, null, 'name' );
			if ( '' === $selected || ! isset( $targets[ $selected ] ) ) {
				return new WP_Error( 'nelio_content_invalid_target', 'Select an existing Pinterest board with target_name from list-social-profile-targets.' );
			}
			$slot['targetName']        = $selected;
			$slot['targetDisplayName'] = $targets[ $selected ]['displayName'];
		} elseif ( isset( $options['target_name'] ) && 'default' !== $options['target_name'] ) {
			return new WP_Error( 'nelio_content_invalid_target', 'This network uses the connected profile as its target. Do not supply a different target_name.' );
		}
		if ( 'tiktok' === $network ) {
			$settings = $options['tiktok_settings'] ?? null;
			$error    = self::validate( 'tiktok_settings', $settings );
			if ( $error ) {
				return $error;
			}
			$slot['tiktokSettings'] = $settings;
		}
		if ( 'plurk' === $network && isset( $options['plurk_qualifier'] ) ) {
			$error = self::validate( 'plurk_qualifier', $options['plurk_qualifier'] );
			if ( $error ) {
				return $error;
			}
			$slot['plurkQualifier'] = $options['plurk_qualifier'];
		}
		if ( in_array( $network, array( 'medium', 'blogger' ), true ) ) {
			if ( ! $post ) {
				return new WP_Error( 'nelio_content_post_required', 'Medium and Blogger require an editable published or scheduled related WordPress post.' );
			}
			$slot['title']   = $post['title'];
			$slot['content'] = $post['content'];
			$slot['tags']    = 'medium' === $network ? ( $options['tags'] ?? array() ) : array_column( $post['taxonomies']['post_tag'] ?? array(), 'name' );
		}
		if ( $post ) {
			$args = array();
			foreach ( $post['permalinkQueryArgs'] as $arg ) {
				$args[ $arg[0] ] = $arg[1];
			}
			$slot['permalink'] = add_query_arg( $args, $post['permalinks'][ $network ] ?? $post['permalink'] );
		}
		if ( $profile['isBuffer'] ) {
			// Like the editor's scheduled mode, honor the requested time instead of Buffer's queue.
			$slot['bufferSettings'] = array( 'isManagedInBuffer' => false );
		}
		if ( 'webhook' === $network ) {
			$settings = $options['webhook_settings'] ?? $profile['webhookSettings'] ?? null;
			$error    = self::validate( 'webhook_settings', $settings );
			if ( $error || ! is_array( $settings ) ) {
				return $error ?? new WP_Error( 'nelio_content_invalid_network_options', 'Provide valid webhook settings.' );
			}
			$computed = self::compute_webhook_settings( $settings, $post );
			if ( is_wp_error( $computed ) ) {
				return $computed;
			}
			$error = self::validate( 'webhook_settings', $computed );
			if ( $error ) {
				return $error;
			}
			$slot['webhookSettings']         = $settings;
			$slot['webhookSettingsComputed'] = $computed;
		}
		return $slot;
	}

	/**
	 * Validates a stored or supplied option without exposing its potentially sensitive values.
	 *
	 * @param string $name Option name.
	 * @param mixed  $value Option value.
	 * @return WP_Error|null
	 */
	public static function validate( $name, $value ) {
		$schema = self::schema()['properties'][ $name ] ?? null;
		if ( ! $schema || is_wp_error( rest_validate_value_from_schema( $value, $schema ) ) ) {
			return new WP_Error( 'nelio_content_invalid_network_options', 'The publishing options do not match the selected network option schema.' );
		}
		if ( 'tiktok_settings' === $name && is_array( $value ) ) {
			if ( ( true === $value['discloseContent'] && empty( $value['isBrandContent'] ) && empty( $value['isBrandOrganic'] ) ) ||
				( empty( $value['discloseContent'] ) && ( ! empty( $value['isBrandContent'] ) || ! empty( $value['isBrandOrganic'] ) ) ) ||
				( ! empty( $value['isBrandContent'] ) && 'SELF_ONLY' === $value['privacyLevel'] ) ) {
				return new WP_Error( 'nelio_content_invalid_network_options', 'TikTok commercial disclosure must identify the promoted brand and use a compatible audience.' );
			}
		}
		if ( 'webhook_settings' === $name && is_array( $value ) ) {
			$url = $value['url'] ?? '';
			if ( ! is_string( $url ) || ! in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) || ! wp_parse_url( $url, PHP_URL_HOST ) ) {
				return new WP_Error( 'nelio_content_invalid_network_options', 'Provide a fully qualified HTTP or HTTPS webhook URL.' );
			}
			$body       = $value['bodyContent'] ?? null;
			$valid_body = true;
			if ( 'json' === $value['bodyType'] ) {
				$valid_body = is_string( $body );
				if ( $valid_body ) {
					json_decode( $body );
					$valid_body = JSON_ERROR_NONE === json_last_error();
				}
			}
			if ( 'form' === $value['bodyType'] ) {
				$valid_body = is_array( $body );
			}
			if ( ! $valid_body ) {
				return new WP_Error( 'nelio_content_invalid_network_options', 'The webhook body must match its selected encoding and contain valid JSON or form fields.' );
			}
		}
		return null;
	}

	/**
	 * Expands the same webhook headers and body fields as the social editor.
	 *
	 * @param array<array-key,mixed> $settings Validated original settings.
	 * @param TPost|false            $post Authorized related post.
	 * @return array<array-key,mixed>|WP_Error
	 */
	private static function compute_webhook_settings( $settings, $post ) {
		$computed = $settings;
		foreach ( array( 'headers', 'bodyContent' ) as $property ) {
			if ( 'bodyContent' === $property && 'none' === $settings['bodyType'] ) {
				continue;
			}
			$value = $settings[ $property ] ?? array();
			if ( is_string( $value ) ) {
				$value = Nelio_Content_Social_Message_Text::compute( $value, $post, 1000 );
				if ( is_wp_error( $value ) ) {
					return $value;
				}
				$computed[ $property ] = $value;
				continue;
			}
			if ( ! is_array( $value ) ) {
				return new WP_Error( 'nelio_content_invalid_network_options', 'Provide valid webhook header or form fields.' );
			}
			$computed[ $property ] = array();
			foreach ( $value as $pair ) {
				if ( ! is_array( $pair ) || ! is_string( $pair['key'] ?? null ) || ! is_string( $pair['value'] ?? null ) ) {
					return new WP_Error( 'nelio_content_invalid_network_options', 'Provide valid webhook header or form fields.' );
				}
				if ( '' === $pair['key'] ) {
					continue;
				}
				$fields = array(
					'key'   => $pair['key'],
					'value' => $pair['value'],
				);
				foreach ( $fields as $field => $text ) {
					$result = Nelio_Content_Social_Message_Text::compute( $text, $post, 1000 );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
					$pair[ $field ] = $result;
				}
				$computed[ $property ][] = $pair;
			}
		}
		return $computed;
	}

	/**
	 * Builds a described closed object.
	 *
	 * @param array<string,array<string,mixed>> $properties Field schemas.
	 * @param list<string>                      $required_fields Required field names.
	 * @param string                            $description Object meaning.
	 * @return array{properties:array<string,array<string,mixed>>, ...<string,mixed>}
	 */
	private static function object_schema( $properties, $required_fields, $description ) {
		return array(
			'type'                 => 'object',
			'properties'           => $properties,
			'required'             => $required_fields,
			'additionalProperties' => false,
			'description'          => $description,
		);
	}
}

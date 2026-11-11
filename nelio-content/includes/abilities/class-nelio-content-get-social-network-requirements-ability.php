<?php
/**
 * Registration, authorization and execution of the get-social-network-requirements ability.
 */

use function Nelio_Content\Networks\get_network_requirements;
use function Nelio_Content\Networks\network_requires_media;
use function Nelio_Content\Networks\is_text_validation_available;

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-social-network-requirements ability.
 *
 * @extends Nelio_Content_Ability<array{network?:string}>
 */
class Nelio_Content_Get_Social_Network_Requirements_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-social-network-requirements ability.
	 *
	 * @return void
	 */
	public function register() {
		$item_schema = $this->object_schema(
			array(
				'network'            => array(
					'type'        => 'string',
					'description' => _x( 'Machine-readable network name used by connected social profiles.', 'text', 'nelio-content' ),
				),
				'text_limit'         => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'description' => _x( 'Maximum weighted text length for this network. Unicode characters and URLs can have different weights, so this can differ from the literal character count.', 'text', 'nelio-content' ),
				),
				'formats'            => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => _x( 'One supported message format or publishing feature for this network.', 'text', 'nelio-content' ),
					),
					_x( 'Supported formats and publishing features for this network. Check creation_supported and the create-social-message inputs to see which formats and options can be scheduled through this integration.', 'text', 'nelio-content' )
				),
				'requires_media'     => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether this network requires an image or video instead of a text-only message. When creation_supported is true, provide one image or video using media_id.', 'text', 'nelio-content' ),
				),
				'creation_supported' => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether this installation can create messages for this network with text and single-attachment validation. Requires Premium and available text validation. User permissions, subscription and profile connectivity are checked when creating a message.', 'text', 'nelio-content' ),
				),
				'update_supported'   => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether this installation can validate and update existing manual messages on this network. Available in Free and Premium; user, profile and message restrictions still apply.', 'text', 'nelio-content' ),
				),
				'required_options'   => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => _x( 'One required input path or network publishing setting. Webhook settings may already be configured on the profile.', 'text', 'nelio-content' ),
					),
					_x( 'Network-specific publishing requirements beyond literal text and time. Supply per-profile options for Pinterest, TikTok and webhooks, or a related post for Medium and Blogger.', 'text', 'nelio-content' )
				),
				'image'              => $this->media_rules_schema(),
				'video'              => $this->media_rules_schema(),
			),
			array( 'network', 'text_limit', 'formats', 'requires_media', 'creation_supported' ),
			_x( 'Publishing requirements for one network, including supported formats, text limits, attachment constraints and required publishing options.', 'text', 'nelio-content' )
		);
		wp_register_ability(
			'nelio-content/get-social-network-requirements',
			array(
				'label'               => _x( 'Get social network requirements', 'command', 'nelio-content' ),
				'description'         => _x( 'Discover network text limits, supported message formats and single-attachment requirements before scheduling. These are configured requirements; a social network may still reject a message at publication time.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $this->object_schema(
					array(
						'network' => array(
							'type'        => 'string',
							'description' => _x( 'Optional exact machine-readable network name, such as instagram. Omit to list all networks.', 'text', 'nelio-content' ),
						),
					),
					array(),
					_x( 'Optional network filter for retrieving publishing requirements.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'items'                     => $this->array_schema( $item_schema, _x( 'Publishing requirements for every matching network, returned together in one response.', 'text', 'nelio-content' ) ),
						'text_validation_available' => array(
							'type'        => 'boolean',
							'description' => _x( 'Whether this installation can validate weighted text length before scheduling or updating messages. If false, ask the site administrator to enable text validation.', 'text', 'nelio-content' ),
						),
					),
					array( 'items', 'text_validation_available' ),
					_x( 'Network publishing requirements and whether this installation can validate weighted text length. Connected profiles and credentials are not inspected.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Checks whether the current user may execute this ability.
	 *
	 * @param array{network?:string} $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Lists network limits without contacting Nelio Cloud.
	 *
	 * @param array{network?:string} $input Optional network name.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot execute this ability.', 'user', 'nelio-content' ) );
		}

		$definitions = get_network_requirements();
		if ( is_wp_error( $definitions ) ) {
			return $definitions;
		}
		if ( isset( $input['network'] ) && ! isset( $definitions[ $input['network'] ] ) ) {
			return new WP_Error( 'nelio_content_invalid_network', _x( 'Unknown social network. Omit network to list supported network names.', 'user', 'nelio-content' ) );
		}
		$items = array();
		foreach ( $definitions as $network => $rules ) {
			if ( isset( $input['network'] ) && $network !== $input['network'] ) {
				continue;
			}
			$item                     = array(
				'network'            => $network,
				'text_limit'         => $rules['limit'],
				'formats'            => $rules['supports'],
				'requires_media'     => network_requires_media( $rules ),
				'creation_supported' => $rules['abilitySupported'] && is_text_validation_available() && ( new Nelio_Content_Create_Social_Message_Ability() )->is_available(),
			);
			$item['update_supported'] = is_text_validation_available();
			$item['required_options'] = array();
			if ( network_requires_media( $rules ) ) {
				$item['required_options'][] = 'media_id';
			}
			$required_options = array(
				'pinterest' => 'profile_options.target_name',
				'tiktok'    => 'profile_options.tiktok_settings',
				'webhook'   => 'profile_options.webhook_settings',
				'medium'    => 'post_id',
				'blogger'   => 'post_id',
			);
			if ( isset( $required_options[ $network ] ) ) {
				$item['required_options'][] = $required_options[ $network ];
			}
			if ( isset( $rules['media'] ) ) {
				$item = array_merge( $item, $rules['media'] );
			}
			$items[] = $item;
		}
		return array(
			'items'                     => $items,
			'text_validation_available' => is_text_validation_available(),
		);
	}

	/**
	 * Describes the shared image or video metadata constraints.
	 *
	 * @return array<string,mixed>
	 */
	private function media_rules_schema() {
		$properties                     = array(
			'mimeTypes' => $this->array_schema(
				array(
					'type'        => 'string',
					'description' => _x( 'An accepted MIME type, such as image/jpeg, or a family token image or video accepting all MIME types in that family.', 'text', 'nelio-content' ),
				),
				_x( 'Accepted MIME types for this media format.', 'text', 'nelio-content' )
			),
		);
		$descriptions                   = array(
			'minSizeKiB'  => _x( 'Minimum file size in KiB, checked after dividing the byte count by 1024 and rounding to the nearest whole KiB.', 'text', 'nelio-content' ),
			'maxSizeMiB'  => _x( 'Maximum file size in MiB, checked after rounding the byte count to the nearest whole KiB and then to the nearest whole MiB.', 'text', 'nelio-content' ),
			'minDuration' => _x( 'Minimum video duration in seconds. Attachment duration metadata is required when this is present.', 'text', 'nelio-content' ),
			'maxDuration' => _x( 'Maximum video duration in seconds. Attachment duration metadata is required when this is present.', 'text', 'nelio-content' ),
		);
		$descriptions                   = array_merge(
			$descriptions,
			array(
				'minWidth'        => _x( 'Minimum image width in pixels.', 'text', 'nelio-content' ),
				'minHeight'       => _x( 'Minimum image height in pixels.', 'text', 'nelio-content' ),
				'maxHeight'       => _x( 'Maximum image height in pixels.', 'text', 'nelio-content' ),
				'maxDimensionSum' => _x( 'Maximum sum of image width and height in pixels.', 'text', 'nelio-content' ),
			)
		);
		$properties['maxSizeMiBByMime'] = array(
			'type'                 => 'object',
			'additionalProperties' => array(
				'type'        => 'number',
				'minimum'     => 0,
				'description' => _x( 'Maximum rounded file size in MiB for the MIME type used as this key.', 'text', 'nelio-content' ),
			),
			'description'          => _x( 'MIME-specific file size limits that override maxSizeMiB, such as the larger GIF allowance on X.', 'text', 'nelio-content' ),
		);
		foreach ( $descriptions as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'number',
				'minimum'     => 0,
				'description' => $description,
			);
		}
		return $this->object_schema( $properties, array( 'mimeTypes' ), _x( 'Attachment requirements checked when scheduling or updating a message. An omitted bound means this integration does not check that constraint; the social network may still apply additional requirements.', 'text', 'nelio-content' ) );
	}
}

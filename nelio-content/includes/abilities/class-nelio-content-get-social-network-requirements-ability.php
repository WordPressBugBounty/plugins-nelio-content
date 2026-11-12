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
					'description' => 'Machine-readable network name used by connected social profiles.',
				),
				'text_limit'         => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'description' => 'Maximum text length allowed by this network. The network may count some Unicode characters and URLs differently, so this can differ from a simple character count.',
				),
				'formats'            => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => 'One supported message format or publishing feature for this network.',
					),
					'Supported formats and publishing features for this network. Check creation_supported and the create-social-message inputs to see which formats and options can be scheduled through this integration.'
				),
				'requires_media'     => array(
					'type'        => 'boolean',
					'description' => 'Whether this network requires an image or video instead of a text-only message. When creation_supported is true, provide one image or video using media_id.',
				),
				'creation_supported' => array(
					'type'        => 'boolean',
					'description' => 'Whether this installation can create messages for this network with text and single-attachment validation. Requires Premium and available text validation. User permissions, subscription and profile connectivity are checked when creating a message.',
				),
				'update_supported'   => array(
					'type'        => 'boolean',
					'description' => 'Whether this installation can validate and update existing manual messages on this network. Available in Free and Premium; user, profile and message restrictions still apply.',
				),
				'required_options'   => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => 'One required input path or network publishing setting. Webhook settings may already be configured on the profile.',
					),
					'Network-specific publishing requirements beyond literal text and time. Supply per-profile options for Pinterest, TikTok and webhooks, or a related post for Medium and Blogger.'
				),
				'image'              => $this->media_rules_schema(),
				'video'              => $this->media_rules_schema(),
			),
			array( 'network', 'text_limit', 'formats', 'requires_media', 'creation_supported' ),
			'Publishing requirements for one network, including supported formats, text limits, attachment constraints and required publishing options.'
		);
		wp_register_ability(
			'nelio-content/get-social-network-requirements',
			array(
				'label'               => _x( 'Get social network requirements', 'command', 'nelio-content' ),
				'description'         => 'Discover network text limits, supported message formats and single-attachment requirements before scheduling. These are configured requirements; a social network may still reject a message at publication time.',
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $this->object_schema(
					array(
						'network' => array(
							'type'        => 'string',
							'description' => 'Optional exact machine-readable network name, such as instagram. Omit to list all networks.',
						),
					),
					array(),
					'Optional network filter for retrieving publishing requirements.'
				),
				'output_schema'       => $this->object_schema(
					array(
						'items'                     => $this->array_schema( $item_schema, 'Publishing requirements for every matching network, returned together in one response.' ),
						'text_validation_available' => array(
							'type'        => 'boolean',
							'description' => 'Whether this installation can check text length using the network’s character and link counting rules before scheduling or updating messages. If false, ask the site administrator to enable text validation.',
						),
					),
					array( 'items', 'text_validation_available' ),
					'Network publishing requirements and whether this installation can check text length using the network’s character and link counting rules. Connected profiles and credentials are not inspected.'
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
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}

		$definitions = get_network_requirements();
		if ( is_wp_error( $definitions ) ) {
			return $definitions;
		}
		if ( isset( $input['network'] ) && ! isset( $definitions[ $input['network'] ] ) ) {
			return new WP_Error( 'nelio_content_invalid_network', 'Unknown social network. Omit network to list supported network names.' );
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
					'description' => 'An accepted MIME type, such as image/jpeg, or a family token image or video accepting all MIME types in that family.',
				),
				'Accepted MIME types for this media format.'
			),
		);
		$descriptions                   = array(
			'minSizeKiB'  => 'Minimum file size in KiB, checked after dividing the byte count by 1024 and rounding to the nearest whole KiB.',
			'maxSizeMiB'  => 'Maximum file size in MiB, checked after rounding the byte count to the nearest whole KiB and then to the nearest whole MiB.',
			'minDuration' => 'Minimum video duration in seconds. Attachment duration metadata is required when this is present.',
			'maxDuration' => 'Maximum video duration in seconds. Attachment duration metadata is required when this is present.',
		);
		$descriptions                   = array_merge(
			$descriptions,
			array(
				'minWidth'        => 'Minimum image width in pixels.',
				'minHeight'       => 'Minimum image height in pixels.',
				'maxHeight'       => 'Maximum image height in pixels.',
				'maxDimensionSum' => 'Maximum sum of image width and height in pixels.',
			)
		);
		$properties['maxSizeMiBByMime'] = array(
			'type'                 => 'object',
			'additionalProperties' => array(
				'type'        => 'number',
				'minimum'     => 0,
				'description' => 'Maximum rounded file size in MiB for the MIME type used as this key.',
			),
			'description'          => 'MIME-specific file size limits that override maxSizeMiB, such as the larger GIF allowance on X.',
		);
		foreach ( $descriptions as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'number',
				'minimum'     => 0,
				'description' => $description,
			);
		}
		return $this->object_schema( $properties, array( 'mimeTypes' ), 'Attachment requirements checked when scheduling or updating a message. An omitted bound means this integration does not check that constraint; the social network may still apply additional requirements.' );
	}
}

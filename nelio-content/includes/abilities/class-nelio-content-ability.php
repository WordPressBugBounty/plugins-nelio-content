<?php
/**
 * Base contract and shared schema helpers for Nelio Content abilities.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the registration, authorization and execution contract for each ability.
 *
 * @template TInput of array<array-key,mixed>
 */
abstract class Nelio_Content_Ability {

	/**
	 * Registers this ability with WordPress Core.
	 *
	 * @return void
	 */
	abstract public function register();

	/**
	 * Checks access to this ability for its validated input.
	 *
	 * @param TInput $input Validated ability input.
	 * @return bool|WP_Error
	 */
	abstract public function permissions( $input );

	/**
	 * Executes this ability after checking its permissions.
	 *
	 * @param TInput $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	abstract public function execute( $input );

	/**
	 * Builds a closed, described object schema.
	 *
	 * @param array<string,array<string,mixed>> $properties Schemas indexed by property name.
	 * @param list<string>                      $required_fields Required property names.
	 * @param string                            $description Meaning of this object.
	 * @return array{type:string,description:string,properties:array<string,array<string,mixed>>,required:list<string>,additionalProperties:bool}
	 */
	protected function object_schema( $properties, $required_fields, $description ) {
		return array(
			'type'                 => 'object',
			'description'          => $description,
			'properties'           => $properties,
			'required'             => $required_fields,
			'additionalProperties' => false,
		);
	}

	/**
	 * Bounded list query inputs. Dates are inclusive site-local calendar days.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function query_properties() {
		return array(
			'start_date' => array(
				'type'        => 'string',
				'format'      => 'date',
				'description' => _x( 'First inclusive calendar day in the site timezone (YYYY-MM-DD).', 'text', 'nelio-content' ),
			),
			'end_date'   => array(
				'type'        => 'string',
				'format'      => 'date',
				'description' => _x( 'Last inclusive calendar day; at most 31 days after start_date.', 'text', 'nelio-content' ),
			),
			'cursor'     => array(
				'type'        => 'string',
				'maxLength'   => 4096,
				'description' => _x( 'Opaque next_cursor from the preceding response. Keep the same filters and per_page while following a cursor.', 'text', 'nelio-content' ),
			),
			'per_page'   => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 100,
				'default'     => 50,
				'description' => _x( 'Maximum source records examined per page, from 1 to 100; defaults to 50. Permission checks and filters can leave fewer returned items.', 'text', 'nelio-content' ),
			),
			'post_id'    => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Restrict results to this WordPress post ID or its related items. The current user must be allowed to read the post.', 'text', 'nelio-content' ),
			),
		);
	}

	/**
	 * Describes a small post summary without rendering or exposing its content.
	 *
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function post_summary_schema() {
		$properties = array(
			'id'               => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'WordPress post ID. Use it when requesting post details, analytics or social promotion.', 'text', 'nelio-content' ),
			),
			'title'            => array(
				'type'        => 'string',
				'description' => _x( 'Saved post title as plain text. It can be empty for an untitled post.', 'text', 'nelio-content' ),
			),
			'post_type'        => array(
				'type'        => 'string',
				'description' => _x( 'WordPress post type, such as post or page.', 'text', 'nelio-content' ),
			),
			'status'           => array(
				'type'        => 'string',
				'description' => _x( 'Current WordPress post status, such as draft, pending or publish. Interpret publication_date separately.', 'text', 'nelio-content' ),
			),
			'author_id'        => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => _x( 'WordPress author ID associated with this post.', 'text', 'nelio-content' ),
			),
			'publication_date' => array(
				'type'        => 'string',
				'description' => _x( 'Publication date and time with a timezone offset, or an empty string when the post has no publication date.', 'text', 'nelio-content' ),
			),
		);
		return $this->object_schema( $properties, array_keys( $properties ), _x( 'A visible WordPress post summary. Post content, custom fields and automation credentials are excluded.', 'text', 'nelio-content' ) );
	}

	/**
	 * Describes a native profile, message or task identifier.
	 *
	 * @param string $description Meaning of this identifier.
	 * @return array<string,mixed>
	 */
	protected function uuid_schema( $description ) {
		return array(
			'type'        => 'string',
			'format'      => 'uuid',
			'description' => $description,
		);
	}

	/**
	 * Builds a bounded array schema with unique items.
	 *
	 * @param array<string,mixed> $item_schema Schema of each array item.
	 * @param string              $description Meaning of this collection.
	 * @return array<string,mixed>
	 */
	protected function array_schema( $item_schema, $description ) {
		return array(
			'type'        => 'array',
			'description' => $description,
			'items'       => $item_schema,
			'maxItems'    => 100,
			'uniqueItems' => true,
		);
	}

	/**
	 * Response for a single bounded page; total counts are deliberately not invented.
	 *
	 * @param array<string,mixed> $item_schema Schema of each array item.
	 * @param string              $description Meaning and coverage of this page.
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function page_schema( $item_schema, $description ) {
		return $this->object_schema(
			array(
				'items'       => array(
					'type'        => 'array',
					'items'       => $item_schema,
					'maxItems'    => 100,
					'description' => _x( 'Accessible matching records on this page. The array can be empty even when has_more is true.', 'text', 'nelio-content' ),
				),
				'has_more'    => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether additional source records remain to examine. Continue with next_cursor even when this page contains no matching items.', 'text', 'nelio-content' ),
				),
				'next_cursor' => array(
					'type'        => 'string',
					'description' => _x( 'Opaque cursor for the next request with the same filters and per_page. Empty when no further source records remain.', 'text', 'nelio-content' ),
				),
				'timezone'    => array(
					'type'        => 'string',
					'description' => _x( 'Configured WordPress site timezone name or UTC offset used to interpret the query dates. Item timestamps may retain explicit offsets.', 'text', 'nelio-content' ),
				),
			),
			array( 'items', 'has_more', 'next_cursor', 'timezone' ),
			$description
		);
	}

	/**
	 * Whitelisted profile fields; settings may contain credentials and are excluded.
	 *
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function profile_schema() {
		return $this->object_schema(
			array(
				'id'                   => $this->uuid_schema( _x( 'Stable Nelio Content profile ID. Pass this value to message profile filters or profile_ids when scheduling messages.', 'text', 'nelio-content' ) ),
				'displayName'          => array(
					'type'        => 'string',
					'description' => _x( 'Human-readable name of the connected social account or page.', 'text', 'nelio-content' ),
				),
				'network'              => array(
					'type'        => 'string',
					'description' => _x( 'Machine-readable social network name, such as linkedin or facebook.', 'text', 'nelio-content' ),
				),
				'username'             => array(
					'type'        => 'string',
					'description' => _x( 'Account username reported by the social network; it may be empty when unavailable.', 'text', 'nelio-content' ),
				),
				'alias'                => array(
					'type'        => 'string',
					'description' => _x( 'Custom profile alias configured in Nelio Content; empty when none is configured.', 'text', 'nelio-content' ),
				),
				'labels'               => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => _x( 'One existing label assigned to this social profile.', 'text', 'nelio-content' ),
					),
					_x( 'Existing Nelio Content labels associated with the profile, such as company or marketing.', 'text', 'nelio-content' )
				),
				'publicationFrequency' => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'maximum'     => 30,
					'description' => _x( 'Saved maximum promotion messages per post publication, shared across this profile’s automation groups. Zero disables this automation. Omitted means unavailable or invalid; it does not mean zero.', 'text', 'nelio-content' ),
				),
				'reshareFrequency'     => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'maximum'     => 10,
					'description' => _x( 'Saved maximum daily reshare messages, shared across this profile’s automation groups. Zero disables this automation. Omitted means unavailable or invalid. Group switches and usable templates are still required.', 'text', 'nelio-content' ),
				),
				'status'               => array(
					'type'        => 'string',
					'enum'        => array( 'valid', 'renew' ),
					'description' => _x( 'Connection state reported by Nelio Cloud: valid or renew. Renew indicates that reauthentication is needed.', 'text', 'nelio-content' ),
				),
			),
			array( 'id', 'displayName', 'network', 'username', 'status' ),
			_x( 'An accessible connected social profile with safe account details and connection status. Credentials and provider settings are excluded.', 'text', 'nelio-content' )
		);
	}

	/**
	 * Native per-profile social message, without webhook settings or raw error responses.
	 *
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function message_schema() {
		$properties   = array(
			'id'        => $this->uuid_schema( _x( 'Stable ID of this per-profile message instance. Use it as message_id when retrieving a message.', 'text', 'nelio-content' ) ),
			'profileId' => $this->uuid_schema( _x( 'ID of the single connected social profile associated with this message.', 'text', 'nelio-content' ) ),
			'postId'    => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Related WordPress post ID, when the message promotes a post.', 'text', 'nelio-content' ),
			),
			'status'    => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'schedule', 'publish', 'error' ),
				'description' => _x( 'Publication state: draft, schedule, publish or error. A draft linked to an unpublished post can already have planned publication dates. It is scheduled automatically when the post is published and Nelio Cloud receives the publication update. This expected draft state alone is not a failure and does not require manual rescheduling or regeneration.', 'text', 'nelio-content' ),
			),
			'sent'      => array(
				'type'        => 'boolean',
				'description' => _x( 'Optional sent flag reported by Nelio Cloud. An absent flag does not establish whether the message was sent.', 'text', 'nelio-content' ),
			),
		);
		$descriptions = array(
			'network'           => _x( 'Machine-readable social network name for this message instance.', 'text', 'nelio-content' ),
			'text'              => _x( 'Original message text, which may retain template placeholders for existing messages.', 'text', 'nelio-content' ),
			'textComputed'      => _x( 'Computed message text reported by Nelio Cloud after applying available formatting and placeholders.', 'text', 'nelio-content' ),
			'schedule'          => _x( 'Planned publication date and time reported by Nelio Cloud, when available. A date can be present while status is draft because the related post is unpublished; publication of the post activates scheduling after its Cloud update. Undated or relative messages may omit this value or return it empty. Interpret together with status and the related post status.', 'text', 'nelio-content' ),
			'dateType'          => _x( 'Date rule: exact, predefined-offset, negative-days, or positive-days. Interpret dateValue using this rule.', 'text', 'nelio-content' ),
			'dateValue'         => _x( 'Value of the date rule, such as an exact calendar date, an offset identifier, or a number of days. Interpret together with dateType.', 'text', 'nelio-content' ),
			'timeType'          => _x( 'Time rule: exact, predefined-offset, positive-hours, or time-interval. Interpret timeValue using this rule.', 'text', 'nelio-content' ),
			'timeValue'         => _x( 'Value of the time rule, such as an exact time, an hour offset, or a named interval. Interpret together with timeType.', 'text', 'nelio-content' ),
			'type'              => _x( 'Message format: text, image, auto-image, video, multi-media, or webhook. Single-media attachment fields are included when available; webhook settings are excluded.', 'text', 'nelio-content' ),
			'source'            => _x( 'Creation source: manual, user-highlight, auto-extracted-sentence, reshare-template, or publication-template.', 'text', 'nelio-content' ),
			'targetName'        => _x( 'Publishing destination name, such as the Pinterest board. Updates preserve this target.', 'text', 'nelio-content' ),
			'targetDisplayName' => _x( 'Human-readable destination name when supplied by Cloud.', 'text', 'nelio-content' ),
			'networkId'         => _x( 'Publication identifier assigned by the social network, when available.', 'text', 'nelio-content' ),
			'networkLink'       => _x( 'Link to the published message on the social network, when available.', 'text', 'nelio-content' ),
		);
		foreach ( $descriptions as $key => $description ) {
			$properties[ $key ] = array(
				'type'        => 'string',
				'description' => $description,
			);
		}
		foreach ( array( 'image', 'video' ) as $type ) {
			$properties[ $type ]        = array(
				'type'        => array( 'string', 'array' ),
				'items'       => array(
					'type'        => 'string',
					'description' => _x( 'One media URL from an existing message containing multiple attachments.', 'text', 'nelio-content' ),
				),
				'description' => _x( 'Image or video URL reported by Nelio Cloud, or a list of URLs for an existing message with multiple attachments.', 'text', 'nelio-content' ),
			);
			$properties[ $type . 'Id' ] = array(
				'type'        => array( 'integer', 'array', 'null' ),
				'items'       => array(
					'type'        => array( 'integer', 'null' ),
					'description' => _x( 'One WordPress attachment ID, or null for an external media item.', 'text', 'nelio-content' ),
				),
				'description' => _x( 'WordPress attachment ID when available, or a list for an existing message with multiple attachments. External media may have no attachment ID.', 'text', 'nelio-content' ),
			);
		}
		$options_schema               = Nelio_Content_Social_Message_Options::schema()['properties'];
		$properties['tiktokSettings'] = $options_schema['tiktok_settings'];
		$properties['plurkQualifier'] = $options_schema['plurk_qualifier'];
		$properties['tags']           = $this->array_schema(
			array(
				'type'        => 'string',
				'description' => _x( 'One article tag reported by Cloud for a Medium or Blogger message.', 'text', 'nelio-content' ),
			),
			_x( 'Tags associated with the republished article. Medium accepts at most five tags; Blogger can use the related post tags.', 'text', 'nelio-content' )
		);
		return $this->object_schema(
			$properties,
			array( 'id', 'profileId', 'network', 'text', 'status' ),
			_x( 'One social message for one profile, including its text, publication state and available scheduling or publication details.', 'text', 'nelio-content' )
		);
	}

	/**
	 * Shared input schema for profile discovery and connection status.
	 *
	 * @return array<string,mixed>
	 */
	protected function profile_query_schema() {
		return $this->object_schema(
			array(
				'network'    => array(
					'type'        => 'string',
					'description' => _x( 'Match this machine-readable social network name, such as linkedin or facebook.', 'text', 'nelio-content' ),
				),
				'profile_id' => $this->uuid_schema(
					_x( 'Restrict results to this accessible Nelio Content social profile ID.', 'text', 'nelio-content' )
				),
				'labels'     => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => _x( 'One existing label to match.', 'text', 'nelio-content' ),
					),
					_x( 'Match profiles containing every requested label. Labels refer to the existing Nelio Content profile labels.', 'text', 'nelio-content' )
				),
				'status'     => array(
					'type'        => 'string',
					'enum'        => array( 'valid', 'renew' ),
					'description' => _x( 'Match the reported connection state: valid or renew. Renew indicates that reauthentication is needed.', 'text', 'nelio-content' ),
				),
				'cursor'     => array(
					'type'        => 'string',
					'maxLength'   => 4096,
					'description' => _x( 'Opaque next_cursor from the preceding profile response. Keep the same filters and per_page.', 'text', 'nelio-content' ),
				),
				'per_page'   => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'description' => _x( 'Maximum matching profiles returned per page, from 1 to 100; defaults to 50.', 'text', 'nelio-content' ),
				),
			),
			array(),
			_x( 'Optional filters for connected profiles visible to the current user. Omitted filters include all accessible profiles.', 'text', 'nelio-content' )
		);
	}

	/**
	 * Common date, profile and status filters for social message reads.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function message_query_properties() {
		$input_properties                = $this->query_properties();
		$input_properties['profile_ids'] = $this->array_schema(
			$this->uuid_schema(
				_x( 'ID of one accessible connected social profile to match.', 'text', 'nelio-content' )
			),
			_x( 'Include messages belonging to any of these accessible profile IDs. Omit or pass an empty array to include all accessible profiles.', 'text', 'nelio-content' )
		);
		$input_properties['status']      = array(
			'description' => _x( 'Match the stored publication state: draft, schedule, publish or error. Messages awaiting publication of their related post can be drafts with planned dates and become scheduled automatically after the post publication reaches Cloud. Date queries may exclude undated drafts.', 'text', 'nelio-content' ),
			'type'        => 'string',
			'enum'        => array( 'draft', 'schedule', 'publish', 'error' ),
		);
		return $input_properties;
	}

	/**
	 * Shared schemas for scheduled message creation and editing.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function scheduled_message_properties() {
		return array(
			'content'          => array(
				'type'        => 'string',
				'minLength'   => 0,
				'description' => _x( 'Plain message text supporting {permalink}, {title}, {author}, {excerpt}, {categories}, {tags}, {taxonomy:slug}, {field:key} and {custom:key}. Placeholders require a related post and use its configured taxonomy, custom-field and custom-placeholder values. Expanded text must fit each network\'s weighted limit; taxonomy hashtags are limited to available space. HTML is unsupported. Empty captions are permitted for TikTok, Medium, Blogger and webhooks. Discover limits with get-social-network-requirements.', 'text', 'nelio-content' ),
			),
			'profile_options'  => array(
				'type'        => 'array',
				'maxItems'    => 10,
				'items'       => Nelio_Content_Social_Message_Options::schema(),
				'description' => _x( 'Optional publishing options for selected profiles: Pinterest board target, TikTok settings, Plurk qualifier, Medium tags or webhook configuration. Each profile may appear once.', 'text', 'nelio-content' ),
			),
			'profile_ids'      => array(
				'type'        => 'array',
				'items'       => $this->uuid_schema( _x( 'ID of one accessible, connected profile from any supported Nelio Content network, including Buffer and Hootsuite connections.', 'text', 'nelio-content' ) ),
				'minItems'    => 1,
				'maxItems'    => 10,
				'uniqueItems' => true,
				'description' => _x( 'One to ten unique profile IDs to schedule for. Best-effort creation attempts one message per profile and reports failures separately; valid profiles are still processed.', 'text', 'nelio-content' ),
			),
			'media_id'         => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Optional WordPress media library attachment ID for one image or video shared by all selected profiles. Required by media-only networks such as Instagram, Pinterest and TikTok. Requires upload and attachment read permissions, complete metadata and a URL accessible to Nelio Cloud. The file must satisfy every selected network\'s media constraints.', 'text', 'nelio-content' ),
			),
			'post_id'          => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Related WordPress post ID, required when using placeholders. The post must be published or scheduled, enabled for social promotion and editable by the current user.', 'text', 'nelio-content' ),
			),
			'scheduled_at'     => array(
				'type'        => 'string',
				'format'      => 'date-time',
				'pattern'     => '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:Z|[+-]\\d{2}:\\d{2})$',
				'description' => _x( 'RFC3339 timestamp with timezone offset, at least five minutes in the future. Converted to the site timezone with minute precision.', 'text', 'nelio-content' ),
			),
			'publication_mode' => array(
				'type'        => 'string',
				'enum'        => array( 'scheduled' ),
				'description' => _x( 'Use scheduled to request future publication. Explicit draft creation and immediate publication modes are unsupported. Messages linked to an unpublished post can subsequently appear as drafts until the post is published and its Cloud update activates scheduling.', 'text', 'nelio-content' ),
			),
		);
	}

	/**
	 * Supplies common external exposure and execution annotations.
	 *
	 * @param bool $is_readonly Whether execution only reads data.
	 * @return array<string,mixed>
	 */
	protected function get_ability_metadata( $is_readonly = true ) {
		return array(
			'public'       => true,
			'show_in_rest' => true,
			'mcp'          => array(
				'public' => true,
				'type'   => 'tool',
			),
			'annotations'  => array(
				'readonly'    => $is_readonly,
				'destructive' => false,
				'idempotent'  => $is_readonly,
			),
		);
	}

	/**
	 * Shared authenticated permission gate. Object visibility is checked before returning data.
	 *
	 * @param TAbility_Input $input Validated ability input.
	 * @return bool
	 */
	protected function can_use_plugin( $input = array() ) {
		if ( ! is_user_logged_in() || ! nelio_content()->is_ready() || ! nelio_content_can_current_user_use_plugin() ) {
			return false;
		}
		return empty( $input['post_id'] ) || ( get_post( $input['post_id'] ) && current_user_can( 'read_post', $input['post_id'] ) );
	}
}

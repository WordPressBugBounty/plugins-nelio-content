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
				'description' => 'First inclusive calendar day in the site timezone (YYYY-MM-DD).',
			),
			'end_date'   => array(
				'type'        => 'string',
				'format'      => 'date',
				'description' => 'Last inclusive calendar day; at most 31 days after start_date.',
			),
			'cursor'     => array(
				'type'        => 'string',
				'maxLength'   => 4096,
				'description' => 'The next_cursor value returned by the previous response. Pass it unchanged and keep the same filters and per_page.',
			),
			'per_page'   => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 100,
				'default'     => 50,
				'description' => 'Maximum records checked per page before applying permissions and filters, from 1 to 100; defaults to 50. Permission checks and filters can leave fewer returned items.',
			),
			'post_id'    => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Restrict results to this WordPress post ID or its related items. The current user must be allowed to read the post.',
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
				'description' => 'WordPress post ID. Use it when requesting post details, analytics or social promotion.',
			),
			'title'            => array(
				'type'        => 'string',
				'description' => 'Saved post title as plain text. It can be empty for an untitled post.',
			),
			'post_type'        => array(
				'type'        => 'string',
				'description' => 'WordPress post type, such as post or page.',
			),
			'status'           => array(
				'type'        => 'string',
				'description' => 'Current WordPress post status, such as draft, pending or publish. Interpret publication_date separately.',
			),
			'author_id'        => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => 'WordPress author ID associated with this post.',
			),
			'publication_date' => array(
				'type'        => 'string',
				'description' => 'Publication date and time with a timezone offset, or an empty string when the post has no publication date.',
			),
		);
		return $this->object_schema( $properties, array_keys( $properties ), 'A visible WordPress post summary. Post content, custom fields and automation credentials are excluded.' );
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
					'description' => 'Accessible matching records on this page. The array can be empty even when has_more is true.',
				),
				'has_more'    => array(
					'type'        => 'boolean',
					'description' => 'Whether more records remain to check. Continue with next_cursor even when this page contains no matching items.',
				),
				'next_cursor' => array(
					'type'        => 'string',
					'description' => 'Value to pass as cursor when requesting the next page with the same filters and per_page. Use it unchanged. Empty when there are no more records to check.',
				),
				'timezone'    => array(
					'type'        => 'string',
					'description' => 'Configured WordPress site timezone name or UTC offset used to interpret the query dates. Item timestamps may retain explicit offsets.',
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
				'id'                   => $this->uuid_schema( 'Stable Nelio Content profile ID. Pass this value to message profile filters or profile_ids when scheduling messages.' ),
				'displayName'          => array(
					'type'        => 'string',
					'description' => 'Human-readable name of the connected social account or page.',
				),
				'network'              => array(
					'type'        => 'string',
					'description' => 'Machine-readable social network name, such as linkedin or facebook.',
				),
				'username'             => array(
					'type'        => 'string',
					'description' => 'Account username reported by the social network; it may be empty when unavailable.',
				),
				'alias'                => array(
					'type'        => 'string',
					'description' => 'Custom profile alias configured in Nelio Content; empty when none is configured.',
				),
				'labels'               => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => 'One existing label assigned to this social profile.',
					),
					'Existing Nelio Content labels associated with the profile, such as company or marketing.'
				),
				'publicationFrequency' => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'maximum'     => 30,
					'description' => 'Saved maximum promotion messages per post publication, shared across this profile’s automation groups. Zero disables this automation. Omitted means unavailable or invalid; it does not mean zero.',
				),
				'reshareFrequency'     => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'maximum'     => 10,
					'description' => 'Saved maximum daily reshare messages, shared across this profile’s automation groups. Zero disables this automation. Omitted means unavailable or invalid. Group switches and usable templates are still required.',
				),
				'status'               => array(
					'type'        => 'string',
					'enum'        => array( 'valid', 'renew' ),
					'description' => 'Connection state reported by Nelio Cloud: valid or renew. Renew indicates that reauthentication is needed.',
				),
			),
			array( 'id', 'displayName', 'network', 'username', 'status' ),
			'An accessible connected social profile with safe account details and connection status. Credentials and provider settings are excluded.'
		);
	}

	/**
	 * Native per-profile social message, without webhook settings or raw error responses.
	 *
	 * @return array{properties:array<string,array<string,mixed>>,type:string,description:string,required:list<string>,additionalProperties:bool}
	 */
	protected function message_schema() {
		$properties   = array(
			'id'        => $this->uuid_schema( 'ID of this message for one social profile. Use it as message_id when retrieving a message.' ),
			'profileId' => $this->uuid_schema( 'ID of the single connected social profile associated with this message.' ),
			'postId'    => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Related WordPress post ID, when the message promotes a post.',
			),
			'status'    => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'schedule', 'publish', 'error' ),
				'description' => 'Publication state: draft, schedule, publish or error. A draft linked to an unpublished post can already have planned publication dates. It is scheduled automatically when the post is published and Nelio Cloud receives the publication update. This expected draft state alone is not a failure and does not require manual rescheduling or regeneration.',
			),
			'sent'      => array(
				'type'        => 'boolean',
				'description' => 'Optional sent flag reported by Nelio Cloud. An absent flag does not establish whether the message was sent.',
			),
		);
		$descriptions = array(
			'network'           => 'Machine-readable social network name for this message instance.',
			'text'              => 'Original message text, which may retain template placeholders for existing messages.',
			'textComputed'      => 'Computed message text reported by Nelio Cloud after applying available formatting and placeholders.',
			'schedule'          => 'Planned publication date and time reported by Nelio Cloud, when available. A date can be present while status is draft because the related post is unpublished; publication of the post activates scheduling after its Cloud update. Undated or relative messages may omit this value or return it empty. Interpret together with status and the related post status.',
			'dateType'          => 'Date rule: exact, predefined-offset, negative-days, or positive-days. Interpret dateValue using this rule.',
			'dateValue'         => 'Value of the date rule, such as an exact calendar date, an offset identifier, or a number of days. Interpret together with dateType.',
			'timeType'          => 'Time rule: exact, predefined-offset, positive-hours, or time-interval. Interpret timeValue using this rule.',
			'timeValue'         => 'Value of the time rule, such as an exact time, an hour offset, or a named interval. Interpret together with timeType.',
			'type'              => 'Message format: text, image, auto-image, video, multi-media, or webhook. Single-media attachment fields are included when available; webhook settings are excluded.',
			'source'            => 'Creation source: manual, user-highlight, auto-extracted-sentence, reshare-template, or publication-template.',
			'targetName'        => 'Publishing destination name, such as the Pinterest board. Updates preserve this target.',
			'targetDisplayName' => 'Human-readable destination name when supplied by Cloud.',
			'networkId'         => 'Publication identifier assigned by the social network, when available.',
			'networkLink'       => 'Link to the published message on the social network, when available.',
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
					'description' => 'One media URL from an existing message containing multiple attachments.',
				),
				'description' => 'Image or video URL reported by Nelio Cloud, or a list of URLs for an existing message with multiple attachments.',
			);
			$properties[ $type . 'Id' ] = array(
				'type'        => array( 'integer', 'array', 'null' ),
				'items'       => array(
					'type'        => array( 'integer', 'null' ),
					'description' => 'One WordPress attachment ID, or null for an external media item.',
				),
				'description' => 'WordPress attachment ID when available, or a list for an existing message with multiple attachments. External media may have no attachment ID.',
			);
		}
		$options_schema               = Nelio_Content_Social_Message_Options::schema()['properties'];
		$properties['tiktokSettings'] = $options_schema['tiktok_settings'];
		$properties['plurkQualifier'] = $options_schema['plurk_qualifier'];
		$properties['tags']           = $this->array_schema(
			array(
				'type'        => 'string',
				'description' => 'One article tag reported by Cloud for a Medium or Blogger message.',
			),
			'Tags associated with the republished article. Medium accepts at most five tags; Blogger can use the related post tags.'
		);
		return $this->object_schema(
			$properties,
			array( 'id', 'profileId', 'network', 'text', 'status' ),
			'One social message for one profile, including its text, publication state and available scheduling or publication details.'
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
					'description' => 'Match this machine-readable social network name, such as linkedin or facebook.',
				),
				'profile_id' => $this->uuid_schema(
					'Restrict results to this accessible Nelio Content social profile ID.'
				),
				'labels'     => $this->array_schema(
					array(
						'type'        => 'string',
						'description' => 'One existing label to match.',
					),
					'Match profiles containing every requested label. Labels refer to the existing Nelio Content profile labels.'
				),
				'status'     => array(
					'type'        => 'string',
					'enum'        => array( 'valid', 'renew' ),
					'description' => 'Match the reported connection state: valid or renew. Renew indicates that reauthentication is needed.',
				),
				'cursor'     => array(
					'type'        => 'string',
					'maxLength'   => 4096,
					'description' => 'The next_cursor value returned by the previous profile response. Pass it unchanged and keep the same filters and per_page.',
				),
				'per_page'   => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 100,
					'description' => 'Maximum matching profiles returned per page, from 1 to 100; defaults to 50.',
				),
			),
			array(),
			'Optional filters for connected profiles visible to the current user. Omitted filters include all accessible profiles.'
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
				'ID of one accessible connected social profile to match.'
			),
			'Include messages belonging to any of these accessible profile IDs. Omit or pass an empty array to include all accessible profiles.'
		);
		$input_properties['status']      = array(
			'description' => 'Match the stored publication state: draft, schedule, publish or error. Messages awaiting publication of their related post can be drafts with planned dates and become scheduled automatically after the post publication reaches Cloud. Date queries may exclude undated drafts.',
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
				'description' => 'Plain message text supporting {permalink}, {title}, {author}, {excerpt}, {categories}, {tags}, {taxonomy:slug}, {field:key} and {custom:key}. Placeholders require a related post and use its configured taxonomy, custom-field and custom-placeholder values. After replacing placeholders, the text must fit each network\'s character limit. Networks may count some characters and links differently. Taxonomy hashtags are limited to the remaining space. HTML is unsupported. Empty captions are permitted for TikTok, Medium, Blogger and webhooks. Discover limits with get-social-network-requirements.',
			),
			'profile_options'  => array(
				'type'        => 'array',
				'maxItems'    => 10,
				'items'       => Nelio_Content_Social_Message_Options::schema(),
				'description' => 'Optional publishing options for selected profiles: Pinterest board target, TikTok settings, Plurk qualifier, Medium tags or webhook configuration. Each profile may appear once.',
			),
			'profile_ids'      => array(
				'type'        => 'array',
				'items'       => $this->uuid_schema( 'ID of one accessible, connected profile from any supported Nelio Content network, including Buffer and Hootsuite connections.' ),
				'minItems'    => 1,
				'maxItems'    => 10,
				'uniqueItems' => true,
				'description' => 'One to ten unique profile IDs to schedule for. Creation is attempted separately for each profile. A failure for one profile does not stop the others, and each failure is reported separately.',
			),
			'media_id'         => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Optional WordPress media library attachment ID for one image or video shared by all selected profiles. Required by media-only networks such as Instagram, Pinterest and TikTok. Requires upload and attachment read permissions, complete metadata and a URL accessible to Nelio Cloud. The file must satisfy every selected network\'s media constraints.',
			),
			'post_id'          => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Related WordPress post ID, required when using placeholders. The post must be published or scheduled, enabled for social promotion and editable by the current user.',
			),
			'scheduled_at'     => array(
				'type'        => 'string',
				'format'      => 'date-time',
				'pattern'     => '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:Z|[+-]\\d{2}:\\d{2})$',
				'description' => 'RFC3339 timestamp with timezone offset, at least five minutes in the future. Converted to the site timezone with minute precision.',
			),
			'publication_mode' => array(
				'type'        => 'string',
				'enum'        => array( 'scheduled' ),
				'description' => 'Use scheduled to request future publication. Explicit draft creation and immediate publication modes are unsupported. Messages linked to an unpublished post can subsequently appear as drafts until the post is published and its Cloud update activates scheduling.',
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

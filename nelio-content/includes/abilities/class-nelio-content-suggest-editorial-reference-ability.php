<?php
/** Single-reference suggestions using the native reference storage functions. */
defined( 'ABSPATH' ) || exit;

/** Reuses or creates a reference and suggests it without replacing other references. */
class Nelio_Content_Suggest_Editorial_Reference_Ability extends Nelio_Content_Editorial_Reference_Ability {

	/** @return void */
	public function register() {
		$properties = array(
			'post_id' => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Readable and editable WordPress post with editorial references enabled. Only its suggestion for this URL is changed.',
			),
			'url'     => array(
				'type'        => 'string',
				'format'      => 'uri',
				'minLength'   => 1,
				'maxLength'   => 2048,
				'description' => 'HTTP(S) URL to suggest, at most 2048 bytes after trimming surrounding whitespace. Embedded username/password credentials, HTML, unescaped whitespace and other protocols are unsupported. Internal content must be readable and viewable.',
			),
		);
		wp_register_ability(
			'nelio-content/suggest-editorial-reference',
			array(
				'label'               => _x( 'Suggest editorial reference', 'command', 'nelio-content' ),
				'description'         => 'Suggest one HTTP(S) URL for an editable post, in Free or Premium. Reuses a saved external reference for that exact URL or resolves readable, viewable internal content; otherwise creates a pending external reference. Creation requires permission to create references. Adds only this suggestion, preserving other references and any included relationship. Re-suggesting a discarded reference removes its discarded relationship. Records the current user and time when suggestion metadata is absent, preserving valid existing attribution. An already suggested, non-discarded reference with valid attribution is returned unchanged. No page download, link check, metadata enrichment or post-content edit occurs; internal content and shared bibliographic fields are not modified. The reference is saved in separate steps, so some changes may remain if a later step fails. Requests made at the same time may create duplicate reference records. After an unconfirmed result, inspect the post’s references before retrying; no automatic retry is performed.',
				'category'            => 'nelio-content-references',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id', 'url' ), 'One editable post and one reference URL. Attribution and reference identity are resolved by the site, not supplied by the caller.' ),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Post whose suggestion was verified.',
						),
						'reference_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Resolved external reference ID or internal WordPress content ID. Reuse it when inspecting this post’s references.',
						),
						'url'          => array(
							'type'        => 'string',
							'format'      => 'uri',
							'maxLength'   => 2048,
							'description' => 'Requested URL after trimming surrounding whitespace. This does not prove the URL is reachable.',
						),
						'is_external'  => array(
							'type'        => 'boolean',
							'description' => 'Whether this uses an external reference record rather than internal WordPress content.',
						),
						'suggested'    => array(
							'type'        => 'boolean',
							'enum'        => array( true ),
							'description' => 'True only after verifying the suggestion, absence of a discarded relationship and valid saved attribution.',
						),
						'changed'      => array(
							'type'        => 'boolean',
							'description' => 'True when a suggestion, discard state or absent attribution needed updating. False means the existing valid suggestion required no write. This is a summary of verified changes, not a complete record of every save step.',
						),
					),
					array( 'post_id', 'reference_id', 'url', 'is_external', 'suggested', 'changed' ),
					'Verified suggestion identity and result, without author contact details, raw metadata or associations with other posts.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata( false ),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return $this->denied();
		}
		$url   = trim( $input['url'] ?? '' );
		$parts = wp_parse_url( $url );
		if ( '' === $url || strlen( $url ) > 2048 || esc_url_raw( $url, array( 'http', 'https' ) ) !== $url || preg_match( '/\s/u', $url ) || ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return new WP_Error( 'nelio_content_invalid_reference_url', 'Provide an HTTP(S) URL of at most 2048 bytes without embedded credentials or unescaped whitespace.' );
		}
		$reference = nelio_content_get_reference_post_by_url( $url );
		if ( is_wp_error( $reference ) ) {
			return $this->unconfirmed( $input );
		}
		if ( ! $this->permissions( $input ) || ( $reference && ! $this->can_read_reference( $reference->ID ) ) ) {
			return $this->denied();
		}
		if ( ! $reference ) {
			$type       = get_post_type_object( 'nc_reference' );
			$capability = $type ? $type->cap->create_posts : null;
			if ( ! is_string( $capability ) || ! current_user_can( $capability ) ) {
				return $this->denied();
			}
			$reference = nelio_content_insert_reference_post( $url );
			if ( is_wp_error( $reference ) ) {
				$data = $reference->get_error_data();
				$id   = is_array( $data ) && is_int( $data['reference_id'] ?? null ) ? $data['reference_id'] : 0;
				return $this->unconfirmed( $input, $id );
			}
		}
		$id       = $reference->ID;
		$post_id  = $input['post_id'] ?? 0;
		$external = 'nc_reference' === $reference->post_type;
		if ( ! $this->permissions( $input ) || ! $this->can_read_reference( $id ) || ( $external && get_metadata( 'post', $id, '_nc_url', true ) !== $url ) ) {
			return $this->unconfirmed( $input, $id );
		}
		$meta_key = '_nc_suggested_reference_' . $id . '_meta';
		$had_meta = metadata_exists( 'post', $post_id, $meta_key );
		$before   = get_metadata( 'post', $post_id, $meta_key, true );
		if ( $had_meta && ! $this->valid_attribution( $before ) ) {
			return $this->unconfirmed( $input, $id );
		}
		$changed = ! $this->has_relation( $post_id, '_nc_suggested_reference', $id ) || $this->has_relation( $post_id, '_nc_discarded_reference', $id ) || ! $had_meta;
		$advisor = get_current_user_id();
		if ( ! $this->permissions( $input ) || ! $this->can_read_reference( $id ) ) {
			return $this->unconfirmed( $input, $id );
		}
		if ( $changed ) {
			nelio_content_suggest_post_reference( $post_id, $id, $advisor );
		}
		$after = get_metadata( 'post', $post_id, $meta_key, true );
		if ( ! $this->permissions( $input ) || ! $this->can_read_reference( $id ) ||
			! $this->has_relation( $post_id, '_nc_suggested_reference', $id ) || $this->has_relation( $post_id, '_nc_discarded_reference', $id ) ||
			! $this->valid_attribution( $after ) || ( $had_meta ? $before !== $after : $after['advisor'] !== $advisor ) ) {
			return $this->unconfirmed( $input, $id );
		}
		return array(
			'post_id'      => $post_id,
			'reference_id' => $id,
			'url'          => $url,
			'is_external'  => $external,
			'suggested'    => true,
			'changed'      => $changed,
		);
	}

	/**
	 * @param mixed $meta Stored suggestion attribution.
	 * @return bool
	 * @phpstan-assert-if-true array{advisor:int,date:int} $meta
	 */
	private function valid_attribution( $meta ) {
		return is_array( $meta ) && is_int( $meta['advisor'] ?? null ) && $meta['advisor'] >= 0 && is_int( $meta['date'] ?? null ) && $meta['date'] > 0;
	}

	/** @return WP_Error */
	private function denied() {
		return new WP_Error( 'nelio_content_permission_denied', 'You cannot suggest this reference for the selected post.' );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @param int            $id Reference, if known.
	 * @return WP_Error
	 */
	private function unconfirmed( $input, $id = 0 ) {
		if ( $id > 0 && $this->permissions( $input ) && $this->can_read_reference( $id ) ) {
			return new WP_Error(
				'nelio_content_reference_suggestion_unconfirmed',
				sprintf( 'The suggestion for reference %d could not be confirmed. Inspect this post’s references before retrying.', $id ),
				array(
					'post_id'      => $input['post_id'] ?? 0,
					'reference_id' => $id,
					'outcome'      => 'unconfirmed',
				)
			);
		}
		return new WP_Error( 'nelio_content_reference_suggestion_unconfirmed', 'The suggestion could not be confirmed. Inspect this post’s references, or ask an editor to do so, before retrying.' );
	}
}

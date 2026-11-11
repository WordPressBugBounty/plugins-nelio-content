<?php
/** Single-post suggestion discards that preserve included references and content. */
defined( 'ABSPATH' ) || exit;

/** Discards a known suggestion without deleting its shared reference record. */
class Nelio_Content_Discard_Editorial_Reference_Ability extends Nelio_Content_Editorial_Reference_Ability {

	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/discard-editorial-reference',
			array(
				'label'               => _x( 'Discard editorial reference', 'command', 'nelio-content' ),
				'description'         => _x( 'Discard one editorial suggestion from a readable and editable post, in Free or Premium. The post type must have editorial references enabled, and the reference must be readable and already suggested or discarded for this post. Obtain its ID by listing this post’s references. Marks the suggestion discarded, removes its suggested relationship and clears its suggestion author/date metadata. Preserves any included relationship and all links in the post content, the shared reference record and its bibliography, and relationships with other posts. An included-only reference or an unrelated ID is rejected without changes. A clean existing discard returns unchanged; stale suggestion metadata can be cleared. No URL lookup, reference creation, download, enrichment or content edit occurs. Changes are not transactional; after an unconfirmed result inspect this post’s references before retrying. No automatic retry is performed.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-references',
				'input_schema'        => $this->object_schema(
					array(
						'post_id'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Readable and editable WordPress post with editorial references enabled. Only this post’s suggestion is discarded.', 'text', 'nelio-content' ),
						),
						'reference_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Readable reference ID from this post’s suggested or discarded references. Internal references use their WordPress content ID. Included-only or unrelated references are unsupported.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id', 'reference_id' ),
					_x( 'Identify one post and an existing reference suggestion. No URL, bibliography or content changes are accepted.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Post whose discard state was verified.', 'text', 'nelio-content' ),
						),
						'reference_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Existing reference whose suggestion was discarded. The reference record itself remains available.', 'text', 'nelio-content' ),
						),
						'discarded'    => array(
							'type'        => 'boolean',
							'enum'        => array( true ),
							'description' => _x( 'Verified discarded relationship for this post.', 'text', 'nelio-content' ),
						),
						'suggested'    => array(
							'type'        => 'boolean',
							'enum'        => array( false ),
							'description' => _x( 'False only after verifying the suggested relationship and its suggestion attribution are absent.', 'text', 'nelio-content' ),
						),
						'included'     => array(
							'type'        => 'boolean',
							'description' => _x( 'Verified stored included relationship, preserved from before the operation. True means the reference remains recorded as included even though its suggestion is discarded. Content links are not freshly scanned.', 'text', 'nelio-content' ),
						),
						'changed'      => array(
							'type'        => 'boolean',
							'description' => _x( 'True when discard state, suggestion state or suggestion attribution needed updating. False means the existing discard was already complete and required no write. Not a transactional change log.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id', 'reference_id', 'discarded', 'suggested', 'included', 'changed' ),
					_x( 'Verified suggestion discard and preserved included relationship. Raw content, attribution, bibliography and associations with other posts are excluded.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata( false ),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 * @phpstan-impure
	 */
	public function permissions( $input ) {
		return parent::permissions( $input ) && isset( $input['reference_id'] ) && $input['reference_id'] > 0 && $this->can_read_reference( $input['reference_id'] );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot discard this reference suggestion for the selected post.', 'user', 'nelio-content' ) );
		}
		$post_id   = $input['post_id'] ?? 0;
		$id        = $input['reference_id'] ?? 0;
		$suggested = $this->has_relation( $post_id, '_nc_suggested_reference', $id );
		$discarded = $this->has_relation( $post_id, '_nc_discarded_reference', $id );
		if ( ! $suggested && ! $discarded ) {
			return new WP_Error( 'nelio_content_reference_not_suggested', _x( 'Choose a suggested or already discarded reference from this post. Included-only references and references from other posts are not changed.', 'user', 'nelio-content' ) );
		}
		$included = $this->has_relation( $post_id, '_nc_included_reference', $id );
		$meta_key = '_nc_suggested_reference_' . $id . '_meta';
		// Without a suggestion, the membership check above requires an existing discard.
		$changed = $suggested || metadata_exists( 'post', $post_id, $meta_key );
		if ( ! $this->permissions( $input ) ) {
			return $this->unconfirmed( $input );
		}
		if ( $changed ) {
			nelio_content_discard_post_reference( $post_id, $id, true );
		}
		if ( ! $this->permissions( $input ) ||
			! $this->has_relation( $post_id, '_nc_discarded_reference', $id ) || $this->has_relation( $post_id, '_nc_suggested_reference', $id ) ||
			metadata_exists( 'post', $post_id, $meta_key ) || $included !== $this->has_relation( $post_id, '_nc_included_reference', $id ) ) {
			return $this->unconfirmed( $input );
		}
		return array(
			'post_id'      => $post_id,
			'reference_id' => $id,
			'discarded'    => true,
			'suggested'    => false,
			'included'     => $included,
			'changed'      => $changed,
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return WP_Error
	 */
	private function unconfirmed( $input ) {
		if ( $this->permissions( $input ) ) {
			return new WP_Error(
				'nelio_content_reference_discard_unconfirmed',
				/* translators: %d: Reference ID to inspect. */
				sprintf( _x( 'The discard for reference %d could not be confirmed. Inspect this post’s references before retrying.', 'user', 'nelio-content' ), $input['reference_id'] ?? 0 ),
				array(
					'post_id'      => $input['post_id'] ?? 0,
					'reference_id' => $input['reference_id'] ?? 0,
					'outcome'      => 'unconfirmed',
				)
			);
		}
		return new WP_Error( 'nelio_content_reference_discard_unconfirmed', _x( 'The reference discard could not be confirmed. Ask an editor to inspect this post’s references before retrying.', 'user', 'nelio-content' ) );
	}
}

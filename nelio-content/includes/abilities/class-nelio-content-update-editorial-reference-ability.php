<?php
/** Partial shared bibliography edits with access checks across all associated posts. */
defined( 'ABSPATH' ) || exit;

/** Updates only requested external bibliographic fields, without returning contact data. */
class Nelio_Content_Update_Editorial_Reference_Ability extends Nelio_Content_Editorial_Reference_Ability {

	const META_FIELDS     = array(
		'url'              => '_nc_url',
		'author'           => '_nc_author_name',
		'email'            => '_nc_author_email',
		'twitter'          => '_nc_author_twitter',
		'publication_date' => '_nc_publication_date',
	);
	const EDITABLE_FIELDS = array( 'title', 'author', 'email', 'twitter', 'publication_date' );

	/** @return void */
	public function register() {
		$properties = array(
			'post_id'          => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Readable and editable reference-enabled post already associated with this reference. Editing affects the shared bibliography for every associated post, not only this context.',
			),
			'reference_id'     => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Existing external reference ID obtained from this post’s references. Internal WordPress content cannot be edited through this operation.',
			),
			'title'            => array(
				'type'        => 'string',
				'maxLength'   => 500,
				'description' => 'Optional plain bibliographic title, at most 500 Unicode characters. Omit to preserve it; an explicit empty string clears it. Surrounding whitespace is trimmed.',
			),
			'author'           => array(
				'type'        => 'string',
				'maxLength'   => 200,
				'description' => 'Optional plain author name, at most 200 Unicode characters. Omit to preserve it; an empty string clears it. Surrounding whitespace is trimmed.',
			),
			'email'            => array(
				'type'        => 'string',
				'maxLength'   => 254,
				'description' => 'Optional valid author email address, or empty to clear it. Omit to preserve it. Stored contact data is never returned, even when another field is edited.',
			),
			'twitter'          => array(
				'type'        => 'string',
				'maxLength'   => 100,
				'description' => 'Optional author X/Twitter handle starting with @, without spaces or another @, or empty to clear it. Omit to preserve it. At most 100 Unicode characters; no network validation occurs.',
			),
			'publication_date' => array(
				'type'        => 'string',
				'maxLength'   => 10,
				'description' => 'Optional real publication calendar date in YYYY-MM-DD form, or empty to clear it. Omit to preserve it. This is the source’s publication date, not a post schedule or suggestion date.',
			),
		);
		wp_register_ability(
			'nelio-content/update-editorial-reference',
			array(
				'label'               => _x( 'Update editorial reference', 'command', 'nelio-content' ),
				'description'         => 'Partially edit an external reference’s shared bibliographic data, in Free or Premium. Requires plugin access, a readable/editable reference-enabled context post, reference read/edit access and read/edit permission for every post associated through included, suggested or discarded relationships. Administrators do not bypass these checks. At most 100 associated posts can be verified; missing associations, posts the user cannot access, more than 100 associations or data that cannot be verified cause the request to fail before saving. Supply at least one title, author, email, X/Twitter handle or publication date; omitted fields are preserved, explicit empty strings clear them. The URL and internal WordPress content cannot be changed through this operation. Changes affect every use of this shared reference. Contact values and other-post identities are never returned. Uses cached bibliographic completeness rules without downloading the URL; broken/check states remain unchanged. If the supplied values already match, nothing is changed and the status is not refreshed. Association and stored-data changes detected during execution stop further writes. Changes are saved in separate steps, and other requests may change the reference at the same time. If the result cannot be confirmed, inspect the reference before retrying. No automatic retry occurs.',
				'category'            => 'nelio-content-references',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id', 'reference_id' ), 'One existing external reference, a known editable context post and at least one explicit bibliographic change. Omitted attributes are preserved.' ),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'        => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Authorized context post used to identify the shared reference. Changes also apply to other posts using it.',
						),
						'reference_id'   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'External reference whose requested fields and final status were verified.',
						),
						'changed'        => array(
							'type'        => 'boolean',
							'description' => 'True when supplied fields differed and their final values were verified. False means no changes were needed, so nothing was saved and the status was not refreshed. This summarizes verified changes; it does not list every save step.',
						),
						'updated_fields' => array(
							'type'        => 'array',
							'maxItems'    => 5,
							'uniqueItems' => true,
							'items'       => array(
								'type'        => 'string',
								'enum'        => self::EDITABLE_FIELDS,
								'description' => 'Name of one supplied field that required a verified update.',
							),
							'description' => 'Names of changed bibliographic fields, without their values. Empty when no changes were needed. Omitted fields are not included.',
						),
						'status'         => array(
							'type'        => 'string',
							'enum'        => array( 'pending', 'improvable', 'complete', 'broken', 'check' ),
							'description' => 'Verified cached bibliographic status. Completeness uses the native saved-field rules; this is not a live link-health check. When no changes are needed, the status is left unchanged.',
						),
					),
					array( 'post_id', 'reference_id', 'changed', 'updated_fields', 'status' ),
					'Verified shared-reference update identity, field names and cached status. Contact values, raw metadata and related-post identities/counts are excluded.'
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
		return ! is_wp_error( $this->context( $input ) );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$parents = $this->context( $input );
		if ( is_wp_error( $parents ) ) {
			return $parents;
		}
		$changes = $this->changes( $input );
		if ( is_wp_error( $changes ) ) {
			return $changes;
		}
		$id    = $input['reference_id'] ?? 0;
		$state = $this->state( $id );
		if ( is_wp_error( $state ) ) {
			return $this->unconfirmed();
		}
		$updated = array();
		foreach ( $changes as $field => $value ) {
			if ( $state[ $field ] === $value ) {
				continue;
			}
			if ( ! $this->matches( $input, $parents, $state ) ) {
				return $this->unconfirmed();
			}
			if ( 'title' === $field ) {
				$result = wp_update_post(
					wp_slash(
						array(
							'ID'         => $id,
							'post_title' => $value,
						)
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					return $this->unconfirmed();
				}
			} else {
				update_post_meta( $id, self::META_FIELDS[ $field ], wp_slash( $value ) );
			}
			$state[ $field ] = $value;
			$updated[]       = $field;
			if ( ! $this->matches( $input, $parents, $state ) ) {
				return $this->unconfirmed();
			}
		}
		if ( $updated ) {
			$values = array_values( array_intersect_key( $state, array_flip( array( 'title', 'url', 'author', 'email', 'twitter', 'publication_date' ) ) ) );
			$status = Nelio_Content_Reference::get_bibliographic_status( $state['status'], $values );
			if ( $status !== $state['status'] ) {
				$result = wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => $status,
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					return $this->unconfirmed();
				}
				$state['status'] = $status;
			}
		}
		if ( ! $this->matches( $input, $parents, $state ) ) {
			return $this->unconfirmed();
		}
		return array(
			'post_id'        => $input['post_id'] ?? 0,
			'reference_id'   => $id,
			'changed'        => ! empty( $updated ),
			'updated_fields' => $updated,
			'status'         => substr( $state['status'], 3 ),
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return list<int>|WP_Error
	 * @phpstan-impure
	 */
	private function context( $input ) {
		$id = $input['reference_id'] ?? 0;
		if ( ! parent::permissions( $input ) || $id < 1 || 'nc_reference' !== get_post_type( $id ) || ! $this->can_read_reference( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			return $this->denied();
		}
		$parents = Nelio_Content_Reference_Permissions::editable_parents( $id );
		return ! is_wp_error( $parents ) && in_array( $input['post_id'] ?? 0, $parents, true ) ? $parents : $this->denied();
	}

	/**
	 * @param int $id Reference.
	 * @return array<string,string>|WP_Error
	 * @phpstan-impure
	 */
	private function state( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'nc_reference' !== $post->post_type || ! in_array( $post->post_status, array( 'nc_pending', 'nc_improvable', 'nc_complete', 'nc_broken', 'nc_check' ), true ) ) {
			return $this->unconfirmed();
		}
		$state = array(
			'title'  => $post->post_title,
			'status' => $post->post_status,
		);
		foreach ( self::META_FIELDS as $field => $key ) {
			$value = get_metadata( 'post', $id, $key, true );
			if ( ! is_string( $value ) ) {
				return $this->unconfirmed();
			}
			$state[ $field ] = $value;
		}
		return $state;
	}

	/**
	 * @param TAbility_Input       $input Input.
	 * @param list<int>            $parents Authorized association snapshot.
	 * @param array<string,string> $state Expected bibliography snapshot.
	 * @return bool
	 * @phpstan-impure
	 */
	private function matches( $input, $parents, $state ) {
		return $parents === $this->context( $input ) && $state === $this->state( $input['reference_id'] ?? 0 );
	}

	/**
	 * @param array<string,mixed> $input Input.
	 * @return array<string,string>|WP_Error
	 */
	private function changes( $input ) {
		$result = array();
		$limits = array(
			'title'            => 500,
			'author'           => 200,
			'email'            => 254,
			'twitter'          => 100,
			'publication_date' => 10,
		);
		foreach ( self::EDITABLE_FIELDS as $field ) {
			if ( ! array_key_exists( $field, $input ) ) {
				continue;
			}
			$value = $input[ $field ];
			if ( ! is_string( $value ) ) {
				return $this->invalid_input();
			}
			$value = trim( $value );
			if ( mb_strlen( $value ) > $limits[ $field ] || wp_strip_all_tags( $value ) !== $value ||
				( 'email' === $field && '' !== $value && ! is_email( $value ) ) ||
				( 'twitter' === $field && '' !== $value && ! nelio_content_is_twitter_handle( $value ) ) ||
				( 'publication_date' === $field && '' !== $value && ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) ) ) {
				return $this->invalid_input();
			}
			$result[ $field ] = $value;
		}
		return $result ? $result : $this->invalid_input();
	}

	/** @return WP_Error */
	private function invalid_input() {
		return new WP_Error( 'nelio_content_invalid_reference_update', 'Supply at least one supported bibliographic field with a valid plain-text value, email, handle or calendar date. Omit fields to preserve them; use an empty string to clear them.' );
	}

	/** @return WP_Error */
	private function denied() {
		return new WP_Error( 'nelio_content_permission_denied', 'This shared reference cannot be updated in the selected context. It must be an editable external reference associated with this post, with read and edit access to every associated post within the supported limits.' );
	}

	/** @return WP_Error */
	private function unconfirmed() {
		return new WP_Error( 'nelio_content_reference_update_unconfirmed', 'The shared-reference update could not be confirmed. Some requested fields may have been saved. Inspect the reference and review access before retrying; no automatic retry was performed.' );
	}
}

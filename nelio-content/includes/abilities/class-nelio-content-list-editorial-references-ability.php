<?php
/** Bounded, read-only queries of stored editorial reference associations. */

use function Nelio_Content\Abilities\decode_cursor;

defined( 'ABSPATH' ) || exit;

/**
 * Lists references without invoking the reference search/create or enrichment flows.
 *
 * Uses shared editorial reference permissions.
 */
class Nelio_Content_List_Editorial_References_Ability extends Nelio_Content_Editorial_Reference_Ability {

	const META_KEYS = array(
		'included'  => '_nc_included_reference',
		'suggested' => '_nc_suggested_reference',
		'discarded' => '_nc_discarded_reference',
	);

	/** @return void */
	public function register() {
		$properties                           = array_intersect_key( $this->query_properties(), array_flip( array( 'post_id', 'cursor', 'per_page' ) ) );
		$properties['post_id']['description'] = 'Existing readable and editable WordPress post with editorial references enabled. Editing access is required because suggested and discarded references are internal editorial information.';
		$properties['relation']               = array(
			'type'        => 'string',
			'enum'        => array( 'all', 'included', 'suggested', 'discarded' ),
			'default'     => 'all',
			'description' => 'Optional stored relationship to select; defaults to all. Each reference is returned once with all its recorded relationships to this post, even when filtering by one relationship. Included and suggested can coexist.',
		);
		wp_register_ability(
			'nelio-content/list-editorial-references',
			array(
				'label'               => _x( 'List editorial references', 'command', 'nelio-content' ),
				'description'         => 'List stored editorial references for one readable and editable post, in Free or Premium. The post type must have editorial references enabled. Select included, suggested, discarded or all relationships; these are separate from the reference’s cached bibliographic status. A reference can be included and suggested simultaneously. Reads only saved information without creating references, downloading URLs, checking link health, rendering or changing content. Internal references require read access to viewable local content. Missing, inaccessible or invalid records are omitted without exposing their identities or totals. Pages examine up to 100 distinct associated reference IDs in ascending order before checking which records the user may read; follow next_cursor even after an empty page while has_more is true. Keep post_id, relation and per_page unchanged. Records may change between page requests. The search can skip at most 100000 records. Author emails, suggestion-advisor details and arbitrary metadata are excluded.',
				'category'            => 'nelio-content-references',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id' ), 'One editable post, an optional relationship filter and results returned one page at a time with page size limits. Cursors belong to this user and unchanged query.' ),
				'output_schema'       => $this->page_schema( $this->reference_schema(), 'One page of accessible stored references, with distinct identities and all their relationships to the selected post. No total or complete collection claim is made. Continue when has_more is true.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return TAbility_Page|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return $this->permission_error();
		}
		$post_id  = $input['post_id'] ?? 0;
		$relation = $input['relation'] ?? 'all';
		$limit    = $input['per_page'] ?? 50;
		if ( ! in_array( $relation, array( 'all', 'included', 'suggested', 'discarded' ), true ) || $limit < 1 || $limit > 100 ) {
			return new WP_Error( 'nelio_content_invalid_input', 'Choose a supported reference relationship and a page size between 1 and 100.' );
		}
		$cursor = decode_cursor( $input );
		if ( is_wp_error( $cursor ) ) {
			return $cursor;
		}
		$scope = 'editorial-references:' . hash( 'sha256', $post_id . ':' . $relation . ':' . $limit . ':' . get_current_user_id() );
		if ( ! empty( $input['cursor'] ) && $scope !== $cursor['page'] ) {
			return new WP_Error( 'nelio_content_invalid_cursor', 'Use a reference cursor returned for this user, post, relationship and page size.' );
		}
		$ids = $this->query_ids( $post_id, $relation, $cursor['offset'], $limit + 1 );
		if ( is_wp_error( $ids ) ) {
			return $ids;
		}
		$has_more    = count( $ids ) > $limit;
		$ids         = array_slice( $ids, 0, $limit );
		$next_offset = $cursor['offset'] + $limit;
		if ( $has_more && $next_offset > 100000 ) {
			return new WP_Error( 'nelio_content_reference_limit_exceeded', 'This reference query exceeds the supported limit for requesting further pages. The full list of references cannot be determined.' );
		}
		$relations = $this->query_relations( $post_id, $ids );
		if ( is_wp_error( $relations ) ) {
			return $relations;
		}
		$items = array();
		foreach ( $ids as $id ) {
			$recorded = $relations[ $id ] ?? array();
			// Associations can change between reads. Do not project a detached reference.
			if ( ! $recorded || ( 'all' !== $relation && ! in_array( $relation, $recorded, true ) ) ) {
				continue;
			}
			$item = $this->reference( $id, $recorded );
			if ( $item ) {
				$items[] = $item;
			}
		}
		if ( ! $this->permissions( $input ) ) {
			return $this->permission_error();
		}
		$encoded = wp_json_encode(
			array(
				'page'   => $scope,
				'offset' => $next_offset,
			)
		);
		if ( false === $encoded ) {
			return $this->response_error();
		}
		return array(
			'items'       => $items,
			'has_more'    => $has_more,
			'next_cursor' => $has_more ? base64_encode( $encoded ) : '',
			'timezone'    => wp_timezone_string(),
		);
	}

	/**
	 * Selects a bounded page of distinct stored IDs, without loading all post metadata.
	 *
	 * @param int    $post_id Parent.
	 * @param string $relation Filter.
	 * @param int    $offset Source offset.
	 * @param int    $limit Source limit, including a continuation probe.
	 * @return list<int>|WP_Error
	 */
	private function query_ids( $post_id, $relation, $offset, $limit ) {
		global $wpdb;
		if ( ! $wpdb instanceof wpdb ) {
			return $this->response_error();
		}
		$previous = $wpdb->suppress_errors();
		try {
			// Native associations are positive decimal IDs. Invalid stored values cannot identify references.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT CAST(meta_value AS UNSIGNED) AS reference_id FROM %i
					WHERE post_id = %d
					AND meta_key IN (%s, %s, %s) AND (%s = 'all' OR meta_key = %s)
					AND meta_value REGEXP '^[1-9][0-9]*$'
					AND CAST(meta_value AS UNSIGNED) <= %d
					ORDER BY reference_id ASC
					LIMIT %d OFFSET %d",
					$wpdb->postmeta,
					$post_id,
					self::META_KEYS['included'],
					self::META_KEYS['suggested'],
					self::META_KEYS['discarded'],
					$relation,
					self::META_KEYS[ $relation ] ?? '',
					PHP_INT_MAX,
					$limit,
					$offset
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( '' !== $wpdb->last_error ) {
				return $this->response_error();
			}
			$result = array();
			foreach ( $ids as $value ) {
				$id = $this->stored_id( $value );
				if ( false === $id ) {
					return $this->response_error();
				}
				$result[] = $id;
			}
			return $result;
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/**
	 * Reads all relationship flags for this page only; duplicates remain one flag.
	 *
	 * @param int       $post_id Parent.
	 * @param list<int> $ids Selected IDs.
	 * @return array<int,list<string>>|WP_Error
	 */
	private function query_relations( $post_id, $ids ) {
		if ( ! $ids ) {
			return array();
		}
		global $wpdb;
		if ( ! $wpdb instanceof wpdb ) {
			return $this->response_error();
		}
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
		$previous     = $wpdb->suppress_errors();
		try {
			// Only fixed keys and integer ID placeholders are used in the prepared query.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The bounded ID list contributes one additional %d placeholder and argument per ID.
				$wpdb->prepare(
					"SELECT DISTINCT CAST(meta_value AS UNSIGNED) AS reference_id, meta_key FROM %i
					WHERE post_id = %d
					AND meta_key IN (%s, %s, %s) AND meta_value REGEXP '^[1-9][0-9]*$'
					AND CAST(meta_value AS UNSIGNED) IN ($placeholders)",
					$wpdb->postmeta,
					$post_id,
					self::META_KEYS['included'],
					self::META_KEYS['suggested'],
					self::META_KEYS['discarded'],
					...$ids
				),
				ARRAY_A
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( '' !== $wpdb->last_error || null === $rows ) {
				return $this->response_error();
			}
			$result = array();
			foreach ( $rows as $row ) {
				if ( ! is_string( $row['meta_key'] ?? null ) ) {
					return $this->response_error();
				}
				$id = $this->stored_id( $row['reference_id'] ?? null );
				if ( false === $id || ! in_array( $id, $ids, true ) ) {
					return $this->response_error();
				}
				$key = array_search( $row['meta_key'], self::META_KEYS, true );
				if ( false !== $key ) {
					$result[ $id ][] = $key;
				}
			}
			return $result;
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/**
	 * Projects bibliographic fields without author contact data or reference creation.
	 *
	 * @param int          $id Reference ID.
	 * @param list<string> $relations Stored relationship names.
	 * @return array<string,mixed>|null
	 */
	private function reference( $id, $relations ) {
		$post = get_post( $id );
		if ( ! $post || ! $this->can_read_reference( $id ) || in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			return null;
		}
		$external = 'nc_reference' === $post->post_type;
		if ( ! $external && ! is_post_type_viewable( $post->post_type ) ) {
			return null;
		}
		$url    = $external ? get_metadata( 'post', $id, '_nc_url', true ) : get_permalink( $post );
		$author = $external ? get_metadata( 'post', $id, '_nc_author_name', true ) : get_the_author_meta( 'display_name', (int) $post->post_author );
		$date   = $external ? get_metadata( 'post', $id, '_nc_publication_date', true ) : substr( $post->post_date, 0, 10 );
		if ( ! $external && '0000-00-00' === $date ) {
			$date = '';
		}
		if ( ! is_string( $url ) || ! is_string( $author ) || ! is_string( $date ) || strlen( $url ) > 2048 || esc_url_raw( $url, array( 'http', 'https' ) ) !== $url ) {
			return null;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return null;
		}
		if ( '' !== $date && ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/D', $date, $matches ) || ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) ) {
			return null;
		}
		// Mirror the native status mapping without loading author contact data.
		$status = $external ? str_replace( 'nc_', '', $post->post_status ) : 'complete';
		if ( ! in_array( $status, array( 'pending', 'improvable', 'complete', 'broken', 'check' ), true ) ) {
			$status = 'pending';
		}
		if ( ! $this->can_read_reference( $id ) ) {
			return null;
		}
		return array(
			'reference_id'     => $id,
			'is_external'      => $external,
			'relations'        => array_values( array_intersect( array_keys( self::META_KEYS ), $relations ) ),
			'status'           => $status,
			'title'            => wp_html_excerpt( $post->post_title, 500, '' ),
			'url'              => $url,
			'author'           => wp_html_excerpt( $author, 200, '' ),
			'publication_date' => $date,
		);
	}

	/**
	 * @param mixed $value Stored identifier from a database result.
	 * @return int|false
	 */
	private function stored_id( $value ) {
		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return false;
		}
		return filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
	}

	/** @return WP_Error */
	private function permission_error() {
		return new WP_Error( 'nelio_content_permission_denied', 'You cannot inspect editorial references for this post.' );
	}

	/** @return WP_Error */
	private function response_error() {
		return new WP_Error( 'nelio_content_references_unavailable', 'Editorial references could not be retrieved. The full list of references cannot be determined.' );
	}

	/** @return array<string,mixed> */
	private function reference_schema() {
		$properties = array(
			'reference_id'     => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Stable reference ID. For an internal reference, this is the readable local content ID.',
			),
			'is_external'      => array(
				'type'        => 'boolean',
				'description' => 'True for a stored external reference; false for readable, viewable local WordPress content.',
			),
			'relations'        => array(
				'type'        => 'array',
				'minItems'    => 1,
				'maxItems'    => 3,
				'uniqueItems' => true,
				'items'       => array(
					'type'        => 'string',
					'enum'        => array_keys( self::META_KEYS ),
					'description' => 'One saved relationship: included in the post, suggested for it, or discarded from its suggestions.',
				),
				'description' => 'All recorded relationships to the selected post, in included/suggested/discarded order. These do not alter or freshly verify links in the post content.',
			),
			'status'           => array(
				'type'        => 'string',
				'enum'        => array( 'pending', 'improvable', 'complete', 'broken', 'check' ),
				'description' => 'Cached bibliographic status, separate from the post relationship. Internal references use complete. This is not a live URL health check or guarantee of metadata completeness.',
			),
			'title'            => array(
				'type'        => 'string',
				'maxLength'   => 500,
				'description' => 'Plain saved title, shortened to 500 Unicode characters; may be empty when bibliographic data is missing.',
			),
			'url'              => array(
				'type'        => 'string',
				'format'      => 'uri',
				'maxLength'   => 2048,
				'description' => 'Saved external HTTP(S) URL or current internal permalink, without embedded username/password credentials. No request is made to this URL.',
			),
			'author'           => array(
				'type'        => 'string',
				'maxLength'   => 200,
				'description' => 'Plain saved author name, or local author display name, shortened to 200 Unicode characters. May be empty; no email address is returned.',
			),
			'publication_date' => array(
				'type'        => 'string',
				'description' => 'Saved publication calendar date in YYYY-MM-DD form, or empty when absent. It is not the date the reference was suggested.',
			),
		);
		return $this->object_schema( $properties, array_keys( $properties ), 'One accessible reference and its stored relationships to the selected post. Other posts using the same reference and private contact data are excluded.' );
	}
}

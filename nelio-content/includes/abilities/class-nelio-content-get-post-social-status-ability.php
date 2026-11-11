<?php
/** Complete bounded summaries of a post's currently visible social promotion. */

use function Nelio_Content\Abilities\get_accessible_social_profiles;
use function Nelio_Content\Abilities\request_cloud;

defined( 'ABSPATH' ) || exit;

/**
 * Counts records, not publishing guarantees or future recurrence instances.
 *
 * @extends Nelio_Content_Ability<array{post_id:int}>
 */
class Nelio_Content_Get_Post_Social_Status_Ability extends Nelio_Content_Ability {

	const MAX_MESSAGES = 1000;
	const MAX_PROFILES = 100;

	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/get-post-social-status',
			array(
				'label'               => _x( 'Get post social status', 'command', 'nelio-content' ),
				'description'         => _x( 'Summarize one readable post’s social promotion in Free or Premium without changing messages. Counts only non-muted records for current profiles accessible to this user. Draft, scheduled, published and failed states are counted, with Free previews in separate counters. Drafts with planned dates linked to an unpublished post are expected and become scheduled automatically when post publication reaches Nelio Cloud. They are counted as drafts, not future scheduled messages; this alone does not mean promotion is missing or require regeneration. Previews never count as effective future scheduling. Includes profile/network breakdowns and the next future scheduled date in UTC. Scope excludes removed profiles and inaccessible profiles; it is not a complete historical or site-wide view. Future recurrence instances are not predicted. Scheduled records do not guarantee successful publication. Limits are 1000 source messages, 100 accessible profiles and 2 MiB per response; failures, oversized replies and unverifiable collections produce an error rather than partial totals.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema(
					array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Existing readable WordPress post whose currently visible social messages should be summarized. No publication-date range is needed, so undated drafts can be included.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id' ),
					_x( 'Identify one readable post. All counts are restricted to profiles accessible to the current user.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->output_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * @param array{post_id:int} $input Input.
	 * @return bool
	 * @phpstan-impure
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input ) && null !== get_post( $input['post_id'] );
	}

	/**
	 * @param array{post_id:int} $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot inspect social promotion for this post.', 'user', 'nelio-content' ) );
		}
		$response = request_cloud( '/post/' . $input['post_id'] . '/items', 'GET', null, array( 'social' ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! is_array( $response['social'] ?? null ) || array_values( $response['social'] ) !== $response['social'] || count( $response['social'] ) > self::MAX_MESSAGES || ! empty( $response['page'] ) || ! empty( $response['next_cursor'] ) || ! empty( $response['has_more'] ) || ! empty( $response['truncated'] ) || ! empty( $response['pagination'] ) ) {
			return $this->unavailable();
		}
		// Resolve profile permissions after reading the post collection, as late as possible.
		$profiles = get_accessible_social_profiles( false, false, true );
		if ( is_wp_error( $profiles ) ) {
			return $profiles;
		}
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'Post access is no longer available.', 'user', 'nelio-content' ) );
		}
		if ( count( $profiles ) > self::MAX_PROFILES ) {
			return $this->unavailable();
		}
		$profile_map = array();
		$by_profile  = array();
		$by_network  = array();
		foreach ( $profiles as $profile ) {
			if ( isset( $profile_map[ $profile['id'] ] ) || ! preg_match( '/^[a-z][a-z0-9_-]{0,63}$/D', $profile['network'] ) ) {
				return $this->unavailable();
			}
			$profile_map[ $profile['id'] ]     = $profile;
			$by_profile[ $profile['id'] ]      = $this->empty_summary();
			$by_network[ $profile['network'] ] = $this->empty_summary();
		}
		$summary = $this->empty_summary();
		$seen    = array();
		$now     = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		foreach ( $response['social'] as $message ) {
			if ( ! is_array( $message ) || ( $message['postId'] ?? null ) !== $input['post_id'] || ! is_string( $message['profileId'] ?? null ) || ! wp_is_uuid( $message['profileId'] ) ) {
				return $this->unavailable();
			}
			$profile_id = $message['profileId'];
			if ( ! isset( $profile_map[ $profile_id ] ) || 'muted' === ( $message['status'] ?? null ) ) {
				continue;
			}
			$status = $message['status'] ?? null;
			$id     = $message['id'] ?? null;
			if ( ! is_string( $id ) || ! wp_is_uuid( $id ) || isset( $seen[ $id ] ) || ! in_array( $status, array( 'draft', 'schedule', 'publish', 'error' ), true ) ||
				( $message['network'] ?? null ) !== $profile_map[ $profile_id ]['network'] ||
				( array_key_exists( 'isFreePreview', $message ) && ! is_bool( $message['isFreePreview'] ) ) ||
				( array_key_exists( 'auto', $message ) && ! is_string( $message['auto'] ) ) ||
				( array_key_exists( 'source', $message ) && ! is_string( $message['source'] ) ) ) {
				return $this->unavailable();
			}
			$seen[ $id ] = true;
			$preview     = $message['isFreePreview'] ?? false;
			$future_at   = '';
			if ( 'schedule' === $status && ! $preview ) {
				$date = $this->scheduled_date( $message['schedule'] ?? null );
				if ( is_wp_error( $date ) ) {
					return $date;
				}
				if ( $date > $now ) {
					$future_at = $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( DATE_ATOM );
				}
			}
			$origin = $this->origin( $message );
			$this->accumulate( $summary, $status, $preview, $origin, $future_at );
			$this->accumulate( $by_profile[ $profile_id ], $status, $preview, $origin, $future_at );
			$this->accumulate( $by_network[ $profile_map[ $profile_id ]['network'] ], $status, $preview, $origin, $future_at );
		}
		$profile_rows = array();
		foreach ( $profile_map as $id => $profile ) {
			$profile_rows[] = array_merge(
				array(
					'profile_id'   => $id,
					'display_name' => wp_html_excerpt( $profile['displayName'], 200, '' ),
					'network'      => $profile['network'],
				),
				$by_profile[ $id ]
			);
		}
		$network_rows = array();
		foreach ( $by_network as $network => $counts ) {
			$network_rows[] = array_merge( array( 'network' => $network ), $counts );
		}
		return array_merge(
			array(
				'post_id'                       => $input['post_id'],
				'scope'                         => 'accessible_current_profiles_non_muted',
				'complete'                      => true,
				'profiles_considered'           => count( $profile_map ),
				'observed_at'                   => $now->format( DATE_ATOM ),
				'has_future_scheduled_messages' => $summary['future_scheduled_count'] > 0,
				'by_profile'                    => $profile_rows,
				'by_network'                    => $network_rows,
			),
			$summary
		);
	}

	/** @return TAbility_Social_Status_Summary */
	private function empty_summary() {
		$counts = array(
			'total'          => 0,
			'draft'          => 0,
			'schedule'       => 0,
			'publish'        => 0,
			'error'          => 0,
			'manual'         => 0,
			'automatic'      => 0,
			'unknown_origin' => 0,
		);
		return array(
			'counts'                 => $counts,
			'previews'               => $counts,
			'future_scheduled_count' => 0,
			'next_scheduled_at'      => '',
		);
	}

	/**
	 * @param TAbility_Social_Status_Summary        $summary Accumulator.
	 * @param 'draft'|'schedule'|'publish'|'error'  $status State.
	 * @param bool                                  $preview Whether excluded from effective scheduling.
	 * @param 'manual'|'automatic'|'unknown_origin' $origin Observed origin.
	 * @param string                                $future_at Normalized UTC future date, or empty.
	 * @return void
	 */
	private function accumulate( &$summary, $status, $preview, $origin, $future_at ) {
		$bucket = $preview ? 'previews' : 'counts';
		++$summary[ $bucket ]['total'];
		++$summary[ $bucket ][ $status ];
		++$summary[ $bucket ][ $origin ];
		if ( '' !== $future_at ) {
			++$summary['future_scheduled_count'];
			if ( '' === $summary['next_scheduled_at'] || $future_at < $summary['next_scheduled_at'] ) {
				$summary['next_scheduled_at'] = $future_at;
			}
		}
	}

	/**
	 * @param array<array-key,mixed> $message Message.
	 * @return 'manual'|'automatic'|'unknown_origin'
	 */
	private function origin( $message ) {
		if ( in_array( $message['auto'] ?? null, array( 'publication', 'timeline', 'reshare', 'recurrence' ), true ) ) {
			return 'automatic';
		}
		if ( ! empty( $message['auto'] ) ) {
			return 'unknown_origin';
		}
		if ( 'manual' === ( $message['source'] ?? null ) ) {
			return 'manual';
		}
		return in_array( $message['source'] ?? null, array( 'publication-template', 'reshare-template', 'user-highlight', 'auto-extracted-sentence', 'custom-sentence' ), true ) ? 'automatic' : 'unknown_origin';
	}

	/**
	 * @param mixed $value Scheduled timestamp.
	 * @return DateTimeImmutable|WP_Error
	 */
	private function scheduled_date( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value ) ) {
			return $this->unavailable();
		}
		try {
			$date   = new DateTimeImmutable( $value );
			$errors = DateTimeImmutable::getLastErrors();
			return false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ? $this->unavailable() : $date;
		} catch ( Exception $e ) {
			return $this->unavailable();
		}
	}

	/** @return WP_Error */
	private function unavailable() {
		return new WP_Error( 'nelio_content_social_summary_unavailable', _x( 'A complete social summary could not be verified within the supported limits. No partial counts were returned.', 'user', 'nelio-content' ) );
	}

	/** @return array<string,mixed> */
	private function output_schema() {
		$properties = array();
		foreach ( array(
			'total'          => _x( 'Total records in this bucket. Published means reported as published, not a newly verified provider publication.', 'text', 'nelio-content' ),
			'draft'          => _x( 'Records with draft status, including undated drafts and messages with planned dates awaiting publication of a related post. The latter become scheduled automatically after post publication reaches Nelio Cloud.', 'text', 'nelio-content' ),
			'schedule'       => _x( 'Records with schedule status, which may have past dates. This is not a publication guarantee.', 'text', 'nelio-content' ),
			'publish'        => _x( 'Records reported with publish status within the visible current collection, not an exhaustive historical total.', 'text', 'nelio-content' ),
			'error'          => _x( 'Records with error status. Raw provider failure details are excluded.', 'text', 'nelio-content' ),
			'manual'         => _x( 'Records explicitly identified as manual without an automatic-generation marker.', 'text', 'nelio-content' ),
			'automatic'      => _x( 'Records identified by a known automatic-generation marker or automatic source.', 'text', 'nelio-content' ),
			'unknown_origin' => _x( 'Records whose origin cannot be reliably classified as manual or automatic.', 'text', 'nelio-content' ),
		) as $name => $description ) {
			$properties[ $name ] = array(
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => self::MAX_MESSAGES,
				'description' => $description,
			);
		}
		$counts               = $this->object_schema( $properties, array_keys( $properties ), _x( 'Record counts by state and origin. State counters sum to total; origin counters separately sum to total.', 'text', 'nelio-content' ) );
		$summary              = array(
			'counts'                 => array_replace( $counts, array( 'description' => _x( 'Visible non-preview, non-muted message records. Counts do not include Free previews.', 'text', 'nelio-content' ) ) ),
			'previews'               => array_replace( $counts, array( 'description' => _x( 'Free preview records counted separately. These do not establish effective future promotion.', 'text', 'nelio-content' ) ) ),
			'future_scheduled_count' => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => self::MAX_MESSAGES,
				'description' => _x( 'Non-preview schedule records with valid dates later than observed_at. This counts stored schedule records, not drafts awaiting post publication, future recurrence expansion or provider acceptance.', 'text', 'nelio-content' ),
			),
			'next_scheduled_at'      => array(
				'type'        => 'string',
				'description' => _x( 'Earliest future non-preview scheduled date in UTC, or empty when none is present within this scope.', 'text', 'nelio-content' ),
			),
		);
		$profile              = $this->object_schema(
			array_merge(
				array(
					'profile_id'   => $this->uuid_schema( _x( 'Current social profile accessible to the current user.', 'text', 'nelio-content' ) ),
					'display_name' => array(
						'type'        => 'string',
						'maxLength'   => 200,
						'description' => _x( 'Plain display name of this accessible profile, shortened to at most 200 Unicode characters. Use profile_id as the stable identity.', 'text', 'nelio-content' ),
					),
					'network'      => array(
						'type'        => 'string',
						'description' => _x( 'Network belonging to this accessible profile. A current profile does not prove current provider authentication.', 'text', 'nelio-content' ),
					),
				),
				$summary
			),
			array_merge( array( 'profile_id', 'display_name', 'network' ), array_keys( $summary ) ),
			_x( 'Counts for one accessible current profile, including zero counts where no matching message records exist.', 'text', 'nelio-content' )
		);
		$network              = $this->object_schema(
			array_merge(
				array(
					'network' => array(
						'type'        => 'string',
						'description' => _x( 'Network represented by the accessible current profiles in this group.', 'text', 'nelio-content' ),
					),
				),
				$summary
			),
			array_merge( array( 'network' ), array_keys( $summary ) ),
			_x( 'Counts aggregated only across accessible current profiles of this network.', 'text', 'nelio-content' )
		);
		$profiles             = $this->array_schema( $profile, _x( 'All accessible current profiles considered, with no inaccessible profile identities or counts.', 'text', 'nelio-content' ) );
		$profiles['maxItems'] = self::MAX_PROFILES;
		$networks             = $this->array_schema( $network, _x( 'Network breakdown of the same scoped records, with previews separated.', 'text', 'nelio-content' ) );
		$networks['maxItems'] = self::MAX_PROFILES;
		$root                 = array(
			'post_id'                       => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => _x( 'Readable WordPress post summarized.', 'text', 'nelio-content' ),
			),
			'scope'                         => array(
				'type'        => 'string',
				'enum'        => array( 'accessible_current_profiles_non_muted' ),
				'description' => _x( 'Coverage is limited to non-muted records belonging to current profiles accessible to this user. Removed and inaccessible profiles are outside scope.', 'text', 'nelio-content' ),
			),
			'complete'                      => array(
				'type'        => 'boolean',
				'enum'        => array( true ),
				'description' => _x( 'True only for the fully validated bounded collection within the stated scope. It does not mean complete site-wide promotion or historical coverage. Partial summaries are not returned.', 'text', 'nelio-content' ),
			),
			'profiles_considered'           => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'maximum'     => self::MAX_PROFILES,
				'description' => _x( 'Number of accessible current profiles considered. Zero means this user has no current profiles in scope, not that no profiles exist on the site.', 'text', 'nelio-content' ),
			),
			'observed_at'                   => array(
				'type'        => 'string',
				'format'      => 'date-time',
				'description' => _x( 'UTC time used to distinguish future schedules. Post and profile reads are not an atomic snapshot.', 'text', 'nelio-content' ),
			),
			'has_future_scheduled_messages' => array(
				'type'        => 'boolean',
				'description' => _x( 'Whether at least one visible non-preview record has schedule status and a future date. False can be expected for an unpublished post whose messages are drafts awaiting automatic scheduling at publication; it does not alone mean promotion is missing. Results are limited to this scope, and true does not guarantee publication.', 'text', 'nelio-content' ),
			),
			'by_profile'                    => $profiles,
			'by_network'                    => $networks,
		);
		$root                 = array_merge( $root, $summary );
		return $this->object_schema( $root, array_keys( $root ), _x( 'Complete bounded summary of currently visible social promotion for one post. Previews are separate, and no site-wide or exhaustive historical conclusion is implied.', 'text', 'nelio-content' ) );
	}
}

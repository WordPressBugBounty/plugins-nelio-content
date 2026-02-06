<?php
/**
 * This file contains a class with some post-related helper functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements post-related helper functions.
 */
class Nelio_Content_Post_Helper {

	/**
	 * This instance
	 *
	 * @var Nelio_Content_Post_Helper|null
	 */
	protected static $instance;

	/**
	 * List of networks.
	 *
	 * @var list<string>
	 */
	private static $networks = array( 'band', 'blogger', 'bluesky', 'facebook', 'instagram', 'linkedin', 'mastodon', 'medium', 'ok', 'pinterest', 'plurk', 'twitter', 'tumblr', 'telegram', 'tiktok', 'threads', 'gmb', 'reddit', 'vk' );

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Post_Helper
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * This function returns the suggested and external references of the post.
	 *
	 * @param integer|WP_Post              $post_id The post whose reference we want or its ID.
	 * @param 'all'|'included'|'suggested' $type    Optional. Type of references to pull. Default: `all`.
	 *
	 * @return list<TEditorial_Reference> an array with two lists: _suggested_ and _included_ references.
	 *
	 * @since  1.3.4
	 */
	public function get_references( $post_id, $type = 'all' ) {

		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}

		if ( empty( $post_id ) ) {
			return array();
		}

		$included_ids  = nelio_content_get_post_reference( $post_id, 'included' );
		$suggested_ids = nelio_content_get_post_reference( $post_id, 'suggested' );
		if ( 'included' === $type ) {
			$reference_ids = $included_ids;
		} elseif ( 'suggested' === $type ) {
			$reference_ids = $suggested_ids;
		} else {
			$reference_ids = array_values( array_unique( array_merge( $included_ids, $suggested_ids ) ) );
		}

		$references = array_map(
			function ( $ref_id ) use ( $post_id ) {
				$reference = new Nelio_Content_Reference( $ref_id );
				$meta      = nelio_content_get_suggested_reference_meta( $post_id, $ref_id );
				if ( ! empty( $meta ) ) {
					$reference->mark_as_suggested( $meta['advisor'], $meta['date'] );
				}
				return $reference->json_encode();
			},
			$reference_ids
		);

		$references = array_filter(
			$references,
			function ( $ref ) {
				return ! empty( $ref['url'] );
			}
		);

		return array_values( $references );
	}

	/**
	 * This function returns a list with the domains that shouldn't be considered
	 * as references.
	 *
	 * @return list<string>
	 *
	 * @since  1.3.4
	 */
	public function get_non_reference_domains() {

		/**
		 * List of domain names that shouldn't be considered as external references.
		 *
		 * @param list<string> $domains list of domain names that shouldn't be considered as
		 *                              external references. It accepts the star (*) char as
		 *                              a wildcard.
		 *
		 * @since 1.3.4
		 */
		return apply_filters(
			'nelio_content_non_reference_domains',
			array(
				'bing.*',
				'*.bing.*',
				'flickr.com',
				'giphy.com',
				'google.*',
				'*.google.*',
				'linkedin.com',
				'unsplash.com',
				'twitter.com',
				'facebook.com',
			)
		);
	}

	/**
	 * Modifies the metas so that we know whether the post can be auto shared or not.
	 *
	 * @param int     $post_id Post ID.
	 * @param boolean $enabled whether the post can be auto shared or not.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function enable_auto_share( $post_id, $enabled ) {

		if ( $enabled ) {
			delete_post_meta( $post_id, '_nc_exclude_from_auto_share' );
			update_post_meta( $post_id, '_nc_include_in_auto_share', true );
		} else {
			delete_post_meta( $post_id, '_nc_include_in_auto_share' );
			update_post_meta( $post_id, '_nc_exclude_from_auto_share', true );
		}
	}

	/**
	 * Returns whether the post can be auto shared or not.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 *
	 * @since  2.0.0
	 */
	public function is_auto_share_enabled( $post_id ) {

		$explicitly_included = ! empty( get_post_meta( $post_id, '_nc_include_in_auto_share', true ) );
		if ( $explicitly_included ) {
			return true;
		}

		$explicitly_excluded = ! empty( get_post_meta( $post_id, '_nc_exclude_from_auto_share', true ) );
		if ( $explicitly_excluded ) {
			return false;
		}

		$settings = Nelio_Content_Settings::instance();
		return 'include-in-auto-share' === $settings->get( 'auto_share_default_mode' );
	}

	/**
	 * Returns the auto share end mode.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return TAuto_Share_End_Mode_Id the auto share end mode.
	 *
	 * @since  2.2.8
	 */
	public function get_auto_share_end_mode( $post_id ) {
		$end_mode  = get_post_meta( $post_id, '_nc_auto_share_end_mode', true );
		$end_modes = array_map( fn( $m ) => $m['value'], nelio_content_get_auto_share_end_modes() );
		if ( ! in_array( $end_mode, $end_modes, true ) ) {
			$end_mode = 'never';
		}
		return $end_mode;
	}

	/**
	 * Retrieves the query args that should be added in the `{permalink}`
	 * placeholder when sharing this post on social media.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return list<TRegular_Query_Arg>
	 *
	 * @since 3.3.0
	 */
	public function get_permalink_query_args( $post_id ) {
		$args = get_post_meta( $post_id, '_nc_permalink_query_args', true );
		return is_array( $args ) ? $args : array();
	}

	/**
	 * Updates the query args that should be added in the `{permalink}`
	 * placeholder when sharing this post on social media.
	 *
	 * @param int                      $post_id Post ID.
	 * @param list<TRegular_Query_Arg> $args    List of query arg pairs (name and value).
	 *
	 * @return void
	 *
	 * @since 3.3.0
	 */
	public function update_permalink_query_args( $post_id, $args ) {
		$args = array_map( fn( $arg ) => array( trim( $arg[0] ), trim( $arg[1] ) ), $args );
		$args = array_values( array_filter( $args, fn( $arg ) => ! empty( $arg[0] ) ) );

		if ( empty( $args ) ) {
			delete_post_meta( $post_id, '_nc_permalink_query_args' );
		} else {
			update_post_meta( $post_id, '_nc_permalink_query_args', $args );
		}
	}

	/**
	 * Updates the auto share end mode.
	 *
	 * @param int                     $post_id  Post ID.
	 * @param TAuto_Share_End_Mode_Id $end_mode New end mode.
	 *
	 * @return void
	 *
	 * @since  2.2.8
	 */
	public function update_auto_share_end_mode( $post_id, $end_mode ) {
		if ( 'never' === $end_mode ) {
			delete_post_meta( $post_id, '_nc_auto_share_end_mode' );
		} else {
			update_post_meta( $post_id, '_nc_auto_share_end_mode', $end_mode );
		}
	}

	/**
	 * Returns the date in which auto share is going to end (if any), `never` if it will never end, or `unknown` otherwise.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string the date in which the auto share ends/ended.
	 *
	 * @since  2.2.8
	 */
	public function get_auto_share_end_date( $post_id ) {
		if ( ! in_array( get_post_status( $post_id ), array( 'publish', 'future' ), true ) ) {
			return 'unknown';
		}

		$end_mode = $this->get_auto_share_end_mode( $post_id );
		if ( 'never' === $end_mode ) {
			return 'never';
		}

		$end_mode = str_replace( '-', ' ', (string) $end_mode );
		$pub_date = get_the_date( 'Y-m-d', $post_id );
		if ( empty( $pub_date ) || ! is_string( $pub_date ) ) {
			return 'unknown';
		}

		$time = strtotime( "{$pub_date} + {$end_mode}" );
		if ( empty( $time ) ) {
			return 'unknown';
		}

		return gmdate( 'Y-m-d', $time );
	}

	/**
	 * Sets users to follow specified post.
	 *
	 * @param int          $post_id     ID of the post.
	 * @param list<string> $suggestions URLs of suggested references.
	 * @param list<string> $included    URLs of included references.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function update_post_references( $post_id, $suggestions, $included ) {

		// 1. SUGGESTED REFERENCES
		$suggestions     = array_map( fn( $url ) => nelio_content_create_reference( $url ), $suggestions );
		$suggestions     = array_map( fn( $s ) => false !== $s ? $s->ID : 0, $suggestions );
		$suggestions     = array_values( array_filter( $suggestions ) );
		$old_suggestions = nelio_content_get_post_reference( $post_id, 'suggested' );

		$new_suggestions = array_diff( $suggestions, $old_suggestions );
		foreach ( $new_suggestions as $ref_id ) {
			nelio_content_suggest_post_reference( $post_id, $ref_id, get_current_user_id() );
		}

		$invalid_suggestions = array_diff( $old_suggestions, $suggestions );
		foreach ( $invalid_suggestions as $ref_id ) {
			nelio_content_discard_post_reference( $post_id, $ref_id );
		}

		// 2. INCLUDED REFERENCES
		$included     = array_map( fn( $url ) => nelio_content_create_reference( $url ), $included );
		$included     = array_map( fn( $s ) => false !== $s ? $s->ID : 0, $included );
		$included     = array_values( array_filter( $included ) );
		$old_included = nelio_content_get_post_reference( $post_id, 'included' );

		$new_included = array_diff( $included, $old_included );
		foreach ( $new_included as $ref_id ) {
			nelio_content_add_post_reference( $post_id, $ref_id );
		}

		$invalid_included = array_diff( $old_included, $included );
		foreach ( $invalid_included as $ref_id ) {
			nelio_content_delete_post_reference( $post_id, $ref_id );
		}
	}

	/**
	 * Updates network image IDs.
	 *
	 * @param int               $post_id                 Post ID.
	 * @param array<string,int> $network_image_ids Network image IDs.
	 *
	 * @return void
	 */
	public function update_network_image_ids( $post_id, $network_image_ids ) {
		if ( empty( $network_image_ids ) ) {
			delete_post_meta( $post_id, '_nc_network_image_ids' );
		} else {
			update_post_meta( $post_id, '_nc_network_image_ids', $network_image_ids );
		}
	}

	/**
	 * Updates series.
	 *
	 * @param int                            $post_id Post ID.
	 * @param list<TSeries>                  $series  Series.
	 * @param 'keep-parts'|'dont-keep-parts' $mode    Optional. Whether to keep parts or not. Default: `dont-keep-parts`.
	 *
	 * @return void
	 */
	public function update_series( $post_id, $series, $mode = 'dont-keep-parts' ) {
		$settings      = Nelio_Content_Settings::instance();
		$taxonomy_slug = $settings->get( 'series_taxonomy_slug' );

		$old_series_terms          = $this->get_series( $post_id );
		$old_series_term_ids       = array_map( fn( $t ) => $t['id'], $old_series_terms );
		$series_term_ids           = array_map( fn( $t ) => $t['id'], $series );
		$series_term_ids_to_delete = array_diff( $old_series_term_ids, $series_term_ids );

		foreach ( $series_term_ids_to_delete as $series_term_id ) {
			delete_post_meta( $post_id, "_nc_series_{$series_term_id}_part" );
		}

		wp_set_object_terms( $post_id, $series_term_ids, $taxonomy_slug );
		foreach ( $series as $series_item ) {
			if ( isset( $series_item['part'] ) ) {
				update_post_meta( $post_id, "_nc_series_{$series_item['id']}_part", $series_item['part'] );
			} elseif ( 'dont-keep-parts' === $mode ) {
					delete_post_meta( $post_id, "_nc_series_{$series_item['id']}_part" );
			}
		}
	}

	/**
	 * Sets users to follow specified post.
	 *
	 * @param int       $post_id ID of the post.
	 * @param list<int> $users   User IDs that follow the post.
	 *
	 * @return boolean
	 *
	 * @since  1.4.2
	 */
	public function save_post_followers( $post_id, $users ) {
		$users = array_values( array_filter( array_unique( array_map( 'absint', $users ) ) ) );
		return nelio_content_update_post_meta_array( $post_id, '_nc_following_users', $users );
	}

	/**
	 * This function creates a ncselect2-ready object with (a) the current post
	 * in the loop or (b) the post specified in `$post_id`.
	 *
	 * @param WP_Post|integer $post The post we want to stringify (or its ID).
	 * @param bool            $raw  Whether raw numbers (true) or human-friendly numbers (false) are to be returned in post statistics.
	 *
	 * @return TPost|false a ncselect2-ready object with (a) the current post in the
	 *               loop or (b) the post specified in `$post_id`.
	 *
	 * @since  1.0.0
	 */
	public function post_to_json( $post, $raw = false ) {

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return false;
		}

		nelio_content_require_wp_file( '/wp-admin/includes/post.php' );
		$analytics          = Nelio_Content_Analytics_Helper::instance();
		$post_status_object = get_post_status_object( $post->post_status );
		$content            = $this->get_the_content( $post );
		$images             = $this->get_images( $content );
		$result             = array(
			'id'                   => $post->ID,
			'author'               => absint( $post->post_author ),
			'authorName'           => $this->get_the_author( $post ),
			'canBeRewritten'       => $this->can_be_rewritten( $post ),
			'content'              => $content,
			'customFields'         => $this->get_custom_fields( $post->ID, $post->post_type ),
			'customPlaceholders'   => $this->get_custom_placeholders( $post->ID, $post->post_type ),
			'date'                 => $this->get_post_time( $post ),
			'editLink'             => $this->get_edit_post_link( $post ),
			'excerpt'              => $this->get_the_excerpt( $post ),
			'followers'            => $this->get_post_followers( $post ),
			'imageAltText'         => $this->get_post_thumbnail_alt_text( $post ),
			'imageAltTexts'        => array_map( fn( $i ) => $i['alt'], $images ),
			'imageId'              => $this->get_post_thumbnail_id( $post ),
			'imageIds'             => array_map( fn( $i ) => $i['id'], $images ),
			'imageSrc'             => $this->get_post_thumbnail( $post, false ),
			'images'               => array_map( fn( $i ) => $i['url'], $images ),
			'isRewrite'            => $this->is_rewrite( $post ),
			'isSticky'             => is_sticky( $post->ID ),
			'networkImageAltTexts' => $this->get_network_image_alt_texts( $post ),
			'networkImageIds'      => $this->get_network_image_ids( $post->ID ),
			'networkImages'        => $this->get_network_images( $post ),
			'permalink'            => $this->get_permalink( $post ),
			'permalinkQueryArgs'   => $this->get_permalink_query_args( $post->ID ),
			'permalinkTemplate'    => get_sample_permalink( $post->ID, $this->get_the_title( $post ), '' )[0],
			'permalinks'           => $this->get_network_permalinks( $post ),
			'rewriteUrl'           => $this->get_rewrite_url( $post ),
			'series'               => $this->get_series( $post->ID ),
			'statistics'           => $analytics->get_post_stats( $post->ID, $raw ),
			'status'               => $post->post_status,
			'statusName'           => ! empty( $post_status_object->label ) && is_string( $post_status_object->label ) ? $post_status_object->label : $post->post_status,
			'taxonomies'           => $this->get_taxonomies( $post ),
			'thumbnailSrc'         => $this->get_featured_thumb( $post ),
			'title'                => $this->get_the_title( $post ),
			'type'                 => $post->post_type,
			'typeName'             => $this->get_post_type_name( $post ),
			'viewLink'             => get_permalink( $post ),
		);

		return $result;
	}

	/**
	 * This function creates an AWS-ready post object.
	 *
	 * @param integer $post_id The ID of the post we want to stringify.
	 *
	 * @return TAWS_Post|false an AWS-ready post object.
	 *
	 * @since  1.4.5
	 */
	public function post_to_aws_json( $post_id ) {

		$result = $this->post_to_json( $post_id );
		if ( empty( $result ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return false;
		}

		unset( $result['followers'] );
		unset( $result['statistics'] );

		$result = array_merge(
			$result,
			array(
				'autoShareEndMode'   => $this->get_auto_share_end_mode( $post->ID ),
				'automationSources'  => $this->get_automation_sources( $post->ID ),
				'content'            => $this->get_the_content( $post ),
				'date'               => ! empty( $result['date'] ) ? $result['date'] : 'none',
				'featuredImage'      => $this->get_post_thumbnail( $post, 'none' ),
				'highlights'         => $this->get_post_highlights( $post->ID ),
				'isAutoShareEnabled' => $this->is_auto_share_enabled( $post->ID ),
				'references'         => $this->get_external_references( $post ),
				'series'             => $this->get_series( $post->ID ),
				'timezone'           => nelio_content_get_timezone(),
			)
		);

		return $result;
	}

	/**
	 * This function returns whether the given post has changed since the last update or not.
	 *
	 * @param integer $post_id the post ID.
	 *
	 * @return boolean whether the post has changed since the last update or not.
	 *
	 * @since  3.0.0
	 */
	public function has_relevant_changes( $post_id ) {

		$new_hash = $this->get_post_hash( $post_id );
		if ( empty( $new_hash ) ) {
			return false;
		}

		$new_full_hash  = $new_hash['full_hash'];
		$new_force_hash = $new_hash['force_synch_hash'];

		$old_hash       = get_post_meta( $post_id, '_nc_sync_hash', true );
		$old_full_hash  = isset( $old_hash['full_hash'] ) ? $old_hash['full_hash'] : '';
		$old_force_hash = isset( $old_hash['force_synch_hash'] ) ? $old_hash['force_synch_hash'] : '';

		if ( $new_force_hash !== $old_force_hash ) {
			return true;
		}

		if ( $new_full_hash === $old_full_hash ) {
			return false;
		}

		$recent = get_transient( "nc_recent_sync_{$post_id}" );
		return empty( $recent );
	}

	/**
	 * This function adds a custom meta so that we know that the post, as is right now, has been synched with AWS.
	 *
	 * @param integer $post_id the post ID.
	 *
	 * @return void
	 *
	 * @since  1.6.8
	 */
	public function mark_post_as_synched( $post_id ) {

		$hash = $this->get_post_hash( $post_id );
		if ( $hash ) {
			update_post_meta( $post_id, '_nc_sync_hash', $hash );
			set_transient( "nc_recent_sync_{$post_id}", true, 15 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Returns the list of post followers for the given post
	 *
	 * @param int|WP_Post $post_id the ID of the post whose followers we want.
	 *
	 * @return list<int> the list of post followers for the given post
	 *
	 * @since  2.0.0
	 */
	public function get_post_followers( $post_id ) {

		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}

		if ( empty( $post_id ) ) {
			return array();
		}

		$follower_ids = get_post_meta( $post_id, '_nc_following_users', false );
		if ( ! is_array( $follower_ids ) ) {
			$follower_ids = array();
		}

		return array_values( array_unique( array_map( fn( $id ) => absint( $id ), $follower_ids ) ) );
	}

	/**
	 * Returns the automation sources.
	 *
	 * @param int|WP_Post $post_id The ID of the post.
	 *
	 * @return TAutomation_Sources the automation sources.
	 *
	 * @since  2.2.6
	 */
	public function get_automation_sources( $post_id ) {

		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}

		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			return array(
				'useCustomSentences' => false,
				'customSentences'    => array(),
			);
		}

		$sources = get_post_meta( $post_id, '_nc_automation_sources', true );
		return $this->fix_automation_sources( $sources, $post_type );
	}

	/**
	 * Updates the automation sources meta.
	 *
	 * @param int|WP_Post         $post_id Post ID.
	 * @param TAutomation_Sources $sources New automation sources value.
	 *
	 * @return void
	 *
	 * @since  2.2.6
	 */
	public function update_automation_sources( $post_id, $sources ) {
		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}

		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			$sources = array(
				'useCustomSentences' => false,
				'customSentences'    => array(),
			);
		} else {
			$sources = $this->fix_automation_sources( $sources, $post_type );
		}

		update_post_meta( $post_id, '_nc_automation_sources', $sources );
	}

	/**
	 * Returns the post highlights.
	 *
	 * @param int|WP_Post $post_id The ID of the post.
	 *
	 * @return list<THighlight> Post higlights.
	 *
	 * @since  2.2.6
	 */
	public function get_post_highlights( $post_id ) {
		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}
		$highlights = get_post_meta( $post_id, '_nc_post_highlights', true );
		return is_array( $highlights ) ? $highlights : array();
	}

	/**
	 * Updates the post highlights.
	 *
	 * @param int|WP_Post      $post_id    Post ID.
	 * @param list<THighlight> $highlights New post highlights.
	 *
	 * @return void
	 *
	 * @since  2.2.6
	 */
	public function update_post_highlights( $post_id, $highlights ) {
		if ( $post_id instanceof WP_Post ) {
			$post_id = $post_id->ID;
		}
		update_post_meta( $post_id, '_nc_post_highlights', $highlights );
	}

	/**
	 * Returns supported custom fields in templates.
	 *
	 * @return array<string,list<array{key:string,name:string}>>
	 */
	public function get_supported_custom_fields_in_templates() {
		$types  = nelio_content_get_post_types( 'social' );
		$fields = array_map( fn( $type ) => $this->get_supported_custom_fields( $type ), $types );
		return array_combine( $types, $fields );
	}

	/**
	 * Returns supported custom placeholders in templates.
	 *
	 * @return array<string,list<array{key:string,name:string}>>
	 */
	public function get_supported_custom_placeholders_in_templates() {
		$types        = nelio_content_get_post_types( 'social' );
		$placeholders = array_map( array( $this, 'get_supported_custom_placeholders' ), $types );
		$placeholders = array_map( fn( $type ) => $this->get_supported_custom_placeholders( $type ), $types );
		return array_combine( $types, $placeholders );
	}

	/**
	 * Returns post hash.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array{full_hash:string,force_synch_hash:string}|false
	 */
	private function get_post_hash( $post_id ) {

		$post = $this->post_to_aws_json( $post_id );
		if ( ! $post ) {
			return false;
		}

		$relevant_attributes = array(
			'date'    => $post['date'],
			'excerpt' => $post['excerpt'],
			'status'  => $post['status'],
			'title'   => $post['title'],
		);

		$post['date'] = substr( $post['date'], 0, strlen( 'YYYY-MM-DDThh:mm' ) );
		unset( $post['content'] );

		$post = array_map(
			function ( $value ) {
				if ( is_array( $value ) ) {
					sort( $value );
				}
				return $value;
			},
			(array) $post
		);

		$encoded_post  = wp_json_encode( $post );
		$encoded_post  = ! empty( $encoded_post ) ? $encoded_post : '';
		$encoded_attrs = wp_json_encode( $relevant_attributes );
		$encoded_attrs = ! empty( $encoded_attrs ) ? $encoded_attrs : '';

		return array(
			'full_hash'        => md5( $encoded_post ),
			'force_synch_hash' => md5( $encoded_attrs ),
		);
	}

	/**
	 * Returns the author name.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_the_author( $post ) {

		$name = get_the_author_meta( 'display_name', absint( $post->post_author ) );

		/**
		 * Filters the post’s author name.
		 *
		 * @param string  $name Name of the author.
		 * @param WP_Post $post Post object.
		 *
		 * @since 2.2.4
		 */
		return apply_filters( 'nelio_content_post_author_name', $name, $post );
	}

	/**
	 * Returns the post’s thumbnail.
	 *
	 * @template TDefault of false|'none'
	 *
	 * @param WP_Post  $post          Post.
	 * @param TDefault $default_value Default value.
	 *
	 * @return string|TDefault
	 */
	private function get_post_thumbnail( $post, $default_value ) {

		$featured_image = wp_get_attachment_url( $this->get_post_thumbnail_id( $post ) );

		$use_efi = ! empty( nelio_content_get_post_types( 'efi' ) );
		if ( $use_efi && empty( $featured_image ) ) {
			$settings        = Nelio_Content_Settings::instance();
			$efi_helper      = Nelio_Content_External_Featured_Image_Helper::instance();
			$featured_image  = $efi_helper->get_external_featured_image( $post->ID );
			$auto_feat_image = $settings->get( 'auto_feat_image' );
			if ( empty( $featured_image ) && 'disabled' !== $auto_feat_image ) {
				$featured_image = $efi_helper->get_auto_featured_image( $post->ID, $auto_feat_image );
			}
		}

		return ! empty( $featured_image ) ? $featured_image : $default_value;
	}

	/**
	 * Returns featured thumbnail.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_featured_thumb( $post ) {

		$default_value  = Nelio_Content()->plugin_url . '/assets/dist/images/default-featured-image-thumbnail.png';
		$featured_image = wp_get_attachment_thumb_url( $this->get_post_thumbnail_id( $post ) );

		$use_efi = ! empty( nelio_content_get_post_types( 'efi' ) );
		if ( $use_efi && empty( $featured_image ) ) {
			$settings        = Nelio_Content_Settings::instance();
			$efi_helper      = Nelio_Content_External_Featured_Image_Helper::instance();
			$featured_image  = $efi_helper->get_external_featured_image( $post->ID );
			$auto_feat_image = $settings->get( 'auto_feat_image' );
			if ( empty( $featured_image ) && 'disabled' !== $auto_feat_image ) {
				$featured_image = $efi_helper->get_auto_featured_image( $post->ID, $auto_feat_image );
			}
		}

		return ! empty( $featured_image ) ? $featured_image : $default_value;
	}

	/**
	 * Returns post’s thumbnail ID.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return int
	 */
	private function get_post_thumbnail_id( $post ) {

		$post_thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true );
		if ( empty( $post_thumbnail_id ) ) {
			$post_thumbnail_id = 0;
		}

		return absint( $post_thumbnail_id );
	}

	/**
	 * Returns post’s thumbnail alt text.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_post_thumbnail_alt_text( $post ) {

		$post_thumbnail_id = absint( get_post_meta( $post->ID, '_thumbnail_id', true ) );
		if ( empty( $post_thumbnail_id ) ) {
			return '';
		}

		$image_alt = trim( wp_strip_all_tags( get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true ) ) );
		if ( empty( $image_alt ) ) {
			$image_alt = '';
		}

		return $image_alt;
	}

	/**
	 * Returns post type name.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_post_type_name( $post ) {

		$post_type_name = _x( 'Post', 'text (default post type name)', 'nelio-content' );
		$post_type      = get_post_type_object( $post->post_type );
		if ( ! empty( $post_type->labels->singular_name ) && is_string( $post_type->labels->singular_name ) ) {
			$post_type_name = $post_type->labels->singular_name;
		}

		return $post_type_name;
	}

	/**
	 * Returns the title.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_the_title( $post ) {

		/**
		 * Modifies the title of the post.
		 *
		 * @param string $title   the title.
		 * @param int    $post_id the ID of the post.
		 *
		 * @since 1.0.0
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$title = apply_filters( 'nelio_content_post_title', apply_filters( 'the_title', $post->post_title, $post->ID ), $post->ID );

		return html_entity_decode( wp_strip_all_tags( $title ), ENT_HTML5 );
	}

	/**
	 * Returns the excerpt.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_the_excerpt( $post ) {

		if ( ! empty( $post->post_excerpt ) ) {
			$excerpt = $post->post_excerpt;
		} else {
			$excerpt = '';
		}

		/**
		 * Modifies the excerpt of the post.
		 *
		 * @param string $excerpt the excerpt.
		 * @param int    $post_id the ID of the post.
		 *
		 * @since 1.0.0
		 */
		$excerpt = apply_filters( 'nelio_content_post_excerpt', $excerpt, $post->ID );

		return html_entity_decode( wp_strip_all_tags( $excerpt ), ENT_HTML5 );
	}

	/**
	 * Returns post time.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string|false
	 */
	private function get_post_time( $post ) {

		$date = ' ' . $post->post_date_gmt;
		if ( strpos( $date, '0000-00-00' ) ) {
			return false;
		}

		$timezone = date_default_timezone_get();
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( 'UTC' );
		$date = get_post_time( 'c', true, $post );
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set
		date_default_timezone_set( $timezone );

		if ( empty( $date ) || ! is_string( $date ) ) {
			return false;
		}

		$has_seconds = strlen( $date ) >= 19;
		if ( $has_seconds ) {
			$date[17] = '0';
			$date[18] = '0';
		}

		return $date;
	}

	/**
	 * Returns post’s edit link.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_edit_post_link( $post ) {

		$link = get_edit_post_link( $post->ID, 'default' );
		if ( empty( $link ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Returns the post’s permalink.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_permalink( $post ) {

		$permalink = get_permalink( $post );
		if ( 'publish' !== $post->post_status ) {
			$aux              = clone $post;
			$aux->post_status = 'publish';
			if ( empty( $aux->post_name ) ) {
				$aux->post_name = sanitize_title( $aux->post_title, "{$aux->post_type}-{$aux->ID}" );
			}
			$aux->post_name = wp_unique_post_slug( $aux->post_name, $aux->ID, 'publish', $aux->post_type, $aux->post_parent );
			$permalink      = get_permalink( $aux );
		}

		/**
		 * Filters the permalink that will be used when sharing the post on social media.
		 *
		 * @param string $permalink the post permalink.
		 * @param int    $post_id   the post ID.
		 *
		 * @since 1.3.6
		 */
		$permalink = apply_filters( 'nelio_content_post_permalink', $permalink, $post->ID );

		return $permalink;
	}

	/**
	 * Whether the given URL is an external URL or not.
	 *
	 * @param string       $url URL.
	 * @param list<string> $non_ref_domains PHP patterns to match domains that can’t be considered a reference.
	 *
	 * @return bool
	 */
	private function is_external_reference( $url, $non_ref_domains ) {

		// Internal URLs are not external.
		if ( 0 === strpos( $url, get_home_url() ) ) {
			return false;
		}

		// Discard any URL that is an external reference.
		foreach ( $non_ref_domains as $pattern ) {
			if ( preg_match( $pattern, $url ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the post content.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_the_content( $post ) {

		$aux = Nelio_Content_Public::instance();
		remove_filter( 'the_content', array( $aux, 'remove_share_blocks' ), 99 );

		/**
		 * Fires before processing post content.
		 *
		 * @since 3.6.0
		 */
		do_action( 'nelio_content_before_the_content' );

		$content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		/** @var string */
		$content = $content;

		/**
		 * Filters the content used in Nelio Content for a given post.
		 *
		 * This is specially useful to ACF users who don’t use the
		 * `the_content` filter to render the content of their posts.
		 *
		 * @param string  $content   the post content.
		 * @param integer $post_id   the post ID.
		 *
		 * @since 2.3.1
		 */
		$content = apply_filters( 'nelio_content_the_content', $content, $post->ID );

		/**
		 * Fires after processing post content.
		 *
		 * @since 3.6.0
		 */
		do_action( 'nelio_content_after_the_content' );

		return $content;
	}

	/**
	 * Returns the images included in the post.
	 *
	 * @param string $content Post content.
	 *
	 * @return list<array{url:string,id:int,alt:string}>
	 */
	private function get_images( $content ) {

		preg_match_all( '/<img[^>]+>/i', $content, $matches );

		$result = array();
		foreach ( $matches[0] as $img ) {
			$url = $this->get_url_from_image_tag( $img );
			if ( $url ) {
				array_push(
					$result,
					array(
						'url' => $url,
						'id'  => $this->get_id_from_image_tag( $img ),
						'alt' => $this->get_alt_text_from_image_tag( $img ),
					)
				);
			}
		}

		shuffle( $result );
		return $result;
	}

	/**
	 * Returns network image ids.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<string,int>
	 */
	public function get_network_image_ids( $post_id ) {
		$ids = get_post_meta( $post_id, '_nc_network_image_ids', true );
		if ( ! is_array( $ids ) ) {
			$ids = array();
		}
		return $ids;
	}

	/**
	 * Returns post series.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return list<TSeries>
	 */
	public function get_series( $post_id ) {
		$settings      = Nelio_Content_Settings::instance();
		$taxonomy_slug = $settings->get( 'series_taxonomy_slug' );
		$series        = wp_get_object_terms( $post_id, $taxonomy_slug );
		if ( empty( $series ) || is_wp_error( $series ) ) {
			return array();
		}

		/** @var list<object{term_id:int}> */
		$series = array_values( $series );
		return array_map(
			function ( $series_item ) use ( $post_id ) {
				/** @var '_nc_series_{term_id}_part' */
				$meta_key = "_nc_series_{$series_item->term_id}_part";
				$part     = absint( get_post_meta( $post_id, $meta_key, true ) );
				$result   = array(
					'id' => $series_item->term_id,
				);
				if ( ! empty( $part ) ) {
					$result['part'] = $part;
				}
				return $result;
			},
			$series
		);
	}

	/**
	 * Returns image URL from an image tag.
	 *
	 * @param string $img Image tag.
	 *
	 * @return string|false
	 */
	private function get_url_from_image_tag( $img ) {
		/**
		 * HTML attributes that might contain the actual URL in an image tag.
		 *
		 * @param list<string> $attributes list of attributes. Default: `[ 'src', 'data-src' ]`.
		 *
		 * @since 2.0.0
		 */
		$attributes = apply_filters( 'nelio_content_url_attributes_in_image_tag', array( 'src', 'data-src' ) );

		$attributes = implode( '|', $attributes );
		preg_match_all( '/(' . $attributes . ')=("[^"]*"|\'[^\']*\')/i', $img, $aux );

		if ( count( $aux ) <= 2 ) {
			return false;
		}

		$urls = array_map(
			function ( $url ) {
				return substr( $url, 1, strlen( $url ) - 2 );
			},
			$aux[2]
		);

		foreach ( $urls as $url ) {
			if ( preg_match( '/^https?:\/\//', $url ) ) {
				return $url;
			}
		}

		return false;
	}

	/**
	 * Returns image ID from an image tag.
	 *
	 * @param string $img Image tag.
	 *
	 * @return int
	 */
	private function get_id_from_image_tag( $img ) {
		preg_match( '/wp-image-(\d+)/', $img, $matches );
		return isset( $matches[1] ) ? (int) $matches[1] : 0;
	}

	/**
	 * Returns alt text from an image tag.
	 *
	 * @param string $img Image tag.
	 *
	 * @return string
	 */
	private function get_alt_text_from_image_tag( $img ) {
		preg_match( '/alt=(["\'])(.*?)\1/', $img, $matches );
		return isset( $matches[2] ) ? html_entity_decode( $matches[2], ENT_QUOTES ) : '';
	}

	/**
	 * Whether the post is a rewrite.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return bool
	 */
	private function is_rewrite( $post ) {
		/**
		 * Filters if a given post is a rewrite of another post.
		 *
		 * @param boolean $is_rewrite Whether the given post is a rewrite.
		 * @param WP_Post $post       The post.
		 */
		return apply_filters( 'nelio_content_is_post_a_rewrite', false, $post );
	}

	/**
	 * Whether post can be rewritten and republished.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return bool
	 */
	private function can_be_rewritten( $post ) {
		/**
		 * Filters if a given post can be rewritten and republished.
		 *
		 * @param boolean $can_be_rewritten Whether the given post can be rewritten.
		 * @param WP_Post $post             The post.
		 */
		return apply_filters( 'nelio_content_can_post_be_rewritten', false, $post );
	}

	/**
	 * Returns rewrite URL.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string|null
	 */
	private function get_rewrite_url( $post ) {
		/**
		 * Filters the rewrite URL of a given post.
		 *
		 * @param string|null $rewrite_url The rewrite URL of a given post.
		 * @param WP_Post     $post        The post.
		 */
		return apply_filters( 'nelio_content_rewrite_url', null, $post );
	}

	/**
	 * Returns custom fields.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return array<string,TCustom_Field>
	 */
	private function get_custom_fields( $post_id, $post_type ) {
		$metas = $this->get_supported_custom_fields( $post_type );
		$metas = array_map(
			function ( $meta ) use ( $post_id ) {
				$value = get_post_meta( $post_id, $meta['key'], true );
				return array(
					'key'   => $meta['key'],
					'name'  => $meta['name'],
					'value' => is_scalar( $value ) ? "$value" : '',
				);
			},
			$metas
		);
		return array_combine( array_map( fn( $m ) => $m['key'], $metas ), $metas );
	}

	/**
	 * Returns custom placeholders.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return array<string,TCustom_Placeholder>
	 */
	private function get_custom_placeholders( $post_id, $post_type ) {

		$placeholders = $this->get_supported_custom_placeholders( $post_type );
		$placeholders = array_map(
			function ( $placeholder ) use ( $post_id, $post_type ) {
				return array(
					'key'   => $placeholder['key'],
					'name'  => $placeholder['name'],
					'value' => call_user_func( $placeholder['callback'], $post_id, $post_type ),
				);
			},
			$placeholders
		);

		return array_combine( array_map( fn( $p ) => $p['key'], $placeholders ), $placeholders );
	}

	/**
	 * Returns external references.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return list<TAWS_Editorial_Reference>
	 */
	private function get_external_references( $post ) {

		$references = $this->get_references( $post, 'all' );

		$non_ref_domains = $this->get_non_reference_domains();
		$count           = count( $non_ref_domains );
		for ( $i = 0; $i < $count; ++$i ) {
			$pattern               = $non_ref_domains[ $i ];
			$pattern               = str_replace( '.', '\\.', $pattern );
			$pattern               = preg_replace( '/\*$/', '[^\/]+', $pattern );
			$pattern               = is_string( $pattern ) ? $pattern : '';
			$pattern               = str_replace( '*', '[^\/]*', $pattern );
			$pattern               = '/^[^:]+:\/\/[^\/]*' . $pattern . '/';
			$non_ref_domains[ $i ] = $pattern;
		}
		$non_ref_domains = array_values( $non_ref_domains );

		$external_references = array_values(
			array_filter(
				$references,
				fn( $r ) => $this->is_external_reference( $r['url'], $non_ref_domains )
			)
		);

		return array_map(
			function ( $reference ) {
				return array(
					'url'     => $reference['url'],
					'author'  => $reference['author'],
					'title'   => $reference['title'],
					'twitter' => $reference['twitter'],
				);
			},
			$external_references
		);
	}

	/**
	 * Fixes automation sources.
	 *
	 * @param mixed  $sources Automation sources.
	 * @param string $type    Post type.
	 *
	 * @return TAutomation_Sources
	 */
	private function fix_automation_sources( $sources, $type ) {
		if ( ! is_array( $sources ) ) {
			$sources = array();
		}

		$defaults = array(
			'useCustomSentences' => false,
			'customSentences'    => array(),
		);

		/**
		 * Filters default automation sources values.
		 *
		 * @param TAutomation_Sources $defaults Default automation sources.
		 * @param string              $type     Post type.
		 *
		 * @since 2.2.6
		 */
		$defaults = apply_filters( 'nelio_content_default_automation_sources', $defaults, $type );

		$use_custom_sentences = isset( $sources['useCustomSentences'] )
			? ! empty( $sources['useCustomSentences'] )
			: ! empty( $defaults['useCustomSentences'] );
		if ( empty( $use_custom_sentences ) ) {
			return array(
				'useCustomSentences' => false,
				'customSentences'    => array(),
			);
		}

		$sentences = isset( $sources['customSentences'] )
			? $sources['customSentences']
			: $defaults['customSentences'];

		if ( is_string( $sentences ) ) {
			$sentences = explode( "\n", str_replace( "\r\n", "\n", $sentences ) );
		}

		if ( ! is_array( $sentences ) ) {
			$sentences = array();
		}

		$sentences = array_map( fn( $s ) => is_string( $s ) ? trim( $s ) : '', $sentences );
		$sentences = array_values( array_filter( $sentences ) );

		return array(
			'useCustomSentences' => true,
			'customSentences'    => $sentences,
		);
	}

	/**
	 * Returns network images.
	 *
	 * @param WP_Post|int $post Post.
	 *
	 * @return array<string,string>
	 */
	public function get_network_images( $post ) {
		$post_id           = is_int( $post ) ? $post : $post->ID;
		$network_image_ids = $this->get_network_image_ids( $post_id );
		$images            = array_map(
			function ( $network ) use ( $post_id, $network_image_ids ) {
				$image = ! empty( $network_image_ids[ $network ] ) ? wp_get_attachment_url( $network_image_ids[ $network ] ) : false;
				/**
				 * Sets the exact image that should be used when sharing the post on a certain network.
				 *
				 * Notice that not all messages that Nelio Content generates will contain an image.
				 * This filter only overwrites the shared image on those messages that contain one.
				 *
				 * @param false|string $image   The image that should be used. Default: `false` (i.e. “none”).
				 * @param int          $post_id The post that’s about to be shared.
				 *
				 * @since 1.4.5
				 */
				return apply_filters( "nelio_content_{$network}_featured_image", $image, $post_id );
			},
			self::$networks
		);
		return array_filter( array_combine( self::$networks, $images ) );
	}

	/**
	 * Returns image alt texts.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return array<string,string>
	 */
	private function get_network_image_alt_texts( $post ) {
		$post_id           = $post->ID;
		$network_image_ids = $this->get_network_image_ids( $post_id );
		$image_alt_texts   = array_map(
			function ( $network ) use ( $post_id, $network_image_ids ) {
				$image_alt = ! empty( $network_image_ids[ $network ] ) ? trim( wp_strip_all_tags( get_post_meta( $network_image_ids[ $network ], '_wp_attachment_image_alt', true ) ) ) : '';

				/**
				 * Sets the exact image alt text that should be used when sharing the post on a certain network.
				 *
				 * Notice that not all messages that Nelio Content generates will contain an image.
				 * This filter only overwrites the shared image alt text on those messages that contain one.
				 *
				 * @param string $image_alt The image that should be used. Default: `""` (i.e. “none”).
				 * @param int    $post_id   The post that’s about to be shared.
				 *
				 * @since 3.9.5
				 */
				return apply_filters( "nelio_content_{$network}_featured_image_alt_text", $image_alt, $post_id );
			},
			self::$networks
		);
		return array_filter( array_combine( self::$networks, $image_alt_texts ) );
	}

	/**
	 * Returns network permalinks.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return array<string,string>
	 */
	private function get_network_permalinks( $post ) {
		$post_id    = $post->ID;
		$default    = $this->get_permalink( $post );
		$permalinks = array_map(
			function ( $network ) use ( $default, $post_id ) {
				$permalink = $default;

				/**
				 * Filters the permalink used in a certain network.
				 *
				 * @param string $permalink The permalink to use in the given network.
				 * @param int    $post_id   The post that’s about to be shared.
				 *
				 * @since 2.3.2
				 */
				$permalink = apply_filters( "nelio_content_post_permalink_on_{$network}", $permalink, $post_id );

				/**
				 * Filters the permalink used in a certain network.
				 *
				 * @param string $permalink The permalink to use in the given network.
				 * @param string $network   The network in which the post will be shared.
				 * @param int    $post_id   The post that’s about to be shared.
				 *
				 * @since 2.3.2
				 */
				$permalink = apply_filters( 'nelio_content_post_permalink_on_network', $permalink, $network, $post_id );

				return $permalink === $default ? false : $permalink;
			},
			self::$networks
		);
		return array_filter( array_combine( self::$networks, $permalinks ) );
	}

	/**
	 * Returns all taxonomies of the given post.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return array<string,list<TTerm>>
	 */
	private function get_taxonomies( $post ) {
		$taxonomies = array_map( fn( $t ) => get_taxonomy( $t ), get_post_taxonomies( $post ) );
		$taxonomies = array_map(
			function ( $tax ) {
				return ! empty( $tax ) && $tax->public && $tax->show_in_rest ? $tax->name : false;
			},
			$taxonomies
		);
		/** @var list<string> */
		$taxonomies = array_values( array_filter( $taxonomies ) );

		$post_id   = $post->ID;
		$all_terms = array_map(
			function ( $tax ) use ( $post_id ) {
				/** @var WP_Error|list<object{term_id:int,name:string,slug:string}> */
				$terms = wp_get_post_terms( $post_id, $tax );
				if ( is_wp_error( $terms ) ) {
					return array();
				}
				return array_map(
					function ( $term ) {
						return array(
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						);
					},
					$terms
				);
			},
			$taxonomies
		);

		return array_combine( $taxonomies, $all_terms );
	}

	/**
	 * Returns list of supported custom fields.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return list<TCustom_Field_Definition>
	 */
	private function get_supported_custom_fields( $post_type ) {
		/**
		 * List of supported custom fields of a post.
		 *
		 * @param list<TCustom_Field_Definition> $metas List of post meta objects that can be used as placeholders in the content of social messages.
		 *                                              Each item in the array contains key and name.
		 * @param string                         $type  Post type.
		 *
		 * @since 2.5.0
		 */
		return apply_filters(
			'nelio_content_supported_post_metas',
			array(),
			$post_type
		);
	}

	/**
	 * Returns list of supported placeholders.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return list<TCustom_Placeholder_Definition>
	 */
	private function get_supported_custom_placeholders( $post_type ) {
		/**
		 * List of supported custom placeholders of a post.
		 *
		 * @param list<TCustom_Placeholder_Definition> $placeholders List of custom objects that can be used as placeholders in the content of social messages.
		 *                                                           Each item in the array contains key, name, and a callback function to get the value.
		 * @param string                               $type         Post type.
		 *
		 * @since 2.5.0
		 */
		return apply_filters(
			'nelio_content_custom_placeholders',
			array(),
			$post_type
		);
	}
}

<?php
/**
 * This file contains the reusable message class.
 *
 * @since 3.2.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_Content\Zod\Schema;
use Nelio_Content\Zod\Zod as Z;

class Nelio_Content_Reusable_Message {

	/**
	 * The reusable message (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Attribures in this reusable message.
	 *
	 * @var TReusable_Social_Message
	 */
	private $attrs;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|Nelio_Content_Reusable_Message|WP_Post $preset Optional. The
	 *                 identifier of a reusable message in the database, or a WP_Post
	 *                 instance that contains said preset. If no value is
	 *                 given, a reusable message object that has no counterpart in
	 *                 the database will be created.
	 *
	 * @since 3.2.0
	 */
	public function __construct( $preset = 0 ) {
		$preset = $preset instanceof Nelio_Content_Reusable_Message ? $preset->ID : $preset;
		$preset = $preset instanceof WP_Post ? $preset->ID : $preset;
		$preset = absint( $preset );
		$preset = get_post( $preset );
		$preset = $preset instanceof WP_Post ? $preset : false;

		if ( ! empty( $preset ) ) {
			$this->ID    = $preset->ID;
			$this->attrs = $this->defaults( $preset->ID );

			$content     = json_decode( base64_decode( $preset->post_content ) );
			$parsed_data = self::schema()->safe_parse( $content );
			if ( true === $parsed_data['success'] ) {
				/** @var TReusable_Social_Message $parsed_data */
				$parsed_data = $parsed_data['data'];
				$this->attrs = $parsed_data;
			}
		}
	}

	/**
	 * Parses the given JSON and converts it into an instance of this class.
	 *
	 * @param string|array<mixed> $json Reusable message as JSON.
	 *
	 * @return Nelio_Content_Reusable_Message|WP_Error an instance of this class or error.
	 *
	 * @since 3.2.0
	 */
	public static function parse( $json ) {
		$json = is_string( $json ) ? json_decode( $json, true ) : $json;
		$json = is_array( $json ) ? $json : array();

		$parsed = self::schema()->safe_parse( $json );
		if ( false === $parsed['success'] ) {
			return new WP_Error( 'parsing-error', $parsed['error'] );
		}

		/** @var TReusable_Social_Message */
		$parsed = $parsed['data'];
		if ( ! empty( $parsed['id'] ) && 'nc_reusable_social' !== get_post_type( absint( $parsed['id'] ) ) ) {
			return new WP_Error( 'invalid-id', sprintf( 'Post %d is not a Reusable Message', $parsed['id'] ) );
		}

		$result        = new self();
		$result->ID    = $parsed['id'] < 0 ? 0 : $parsed['id'];
		$result->attrs = $parsed;
		return $result;
	}

	/**
	 * Saves the reusable message to the database.
	 *
	 * @return Nelio_Content_Reusable_Message|WP_Error the reusable message ID or an error if something went wrong.
	 *
	 * @since 3.2.0
	 */
	public function save() {
		$body = wp_json_encode( $this->attrs );
		assert( ! empty( $body ) );

		$args = array(
			'post_content' => base64_encode( $body ),
			'post_excerpt' => $this->attrs['textComputed'] ?? '',
			'post_type'    => 'nc_reusable_social',
			'post_status'  => 'draft',
		);

		$result = empty( $this->ID )
			? wp_insert_post( $args, true )
			: wp_update_post( array_merge( $args, array( 'ID' => $this->ID ) ), true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->ID = $result;
		return $this;
	}

	/**
	 * Converts this class into JSON.
	 *
	 * @return TReusable_Social_Message
	 *
	 * @since 3.2.0
	 */
	public function json() {
		return array_merge(
			$this->attrs,
			array( 'id' => $this->ID )
		);
	}

	public static function schema(): Schema {
		/** @var Nelio_Content\Zod\Schema|null $schema */
		static $schema;
		if ( empty( $schema ) ) {
			$schema = Z::object(
				array(
					'id'           => Z::number()->optional(),
					'image'        => Z::string()->optional(),
					'imageId'      => Z::number()->optional(),
					'network'      => Z::enum(
						array(
							'band',
							'blogger',
							'bluesky',
							'facebook',
							'gmb',
							'instagram',
							'linkedin',
							'mastodon',
							'ok',
							'pinterest',
							'plurk',
							'reddit',
							'telegram',
							'tiktok',
							'tumblr',
							'twitter',
							'discord',
							'medium',
							'slack',
							'threads',
							'vk',
						)
					),
					'postAuthor'   => Z::number()->optional(),
					'postId'       => Z::number()->optional(),
					'postType'     => Z::string()->optional(),
					'profileId'    => Z::string(),
					'targetName'   => Z::string()->optional(),
					'text'         => Z::string(),
					'textComputed' => Z::string(),
					'timeType'     => Z::enum(
						array(
							'predefined-offset',
							'positive-hours',
							'time-interval',
							'exact',
						)
					),
					'timeValue'    => Z::string(),
					'type'         => Z::enum( array( 'text', 'image', 'auto-image', 'video' ) ),
					'video'        => Z::string()->optional(),
					'videoId'      => Z::number()->optional(),
				)
			);
		}
		return $schema;
	}

	/**
	 * Returns default values.
	 *
	 * @param int $id ID.
	 *
	 * @return TReusable_Social_Message
	 */
	private function defaults( $id ) {
		return array(
			'id'           => $id,
			'network'      => 'twitter',
			'profileId'    => '',
			'text'         => '',
			'textComputed' => '',
			'timeType'     => 'time-interval',
			'timeValue'    => 'morning',
			'type'         => 'text',
		);
	}
}

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
	 * @var array
	 */
	private $attrs = array();

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
		$preset = is_numeric( $preset ) ? absint( $preset ) : 0;
		$preset = get_post( $preset );
		$preset = $preset instanceof WP_Post ? $preset : false;

		if ( ! empty( $preset ) ) {
			$this->ID    = $preset->ID;
			$content     = json_decode( base64_decode( $preset->post_content ) );
			$attrs       = self::schema()->safe_parse( $content );
			$this->attrs = ! empty( $attrs['success'] ) ? $attrs['data'] : $this->defaults( $preset->ID );
		}//end if
	}//end __construct()

	/**
	 * Parses the given JSON and converts it into an instance of this class.
	 *
	 * @param string|array $json Reusable message as JSON.
	 *
	 * @return Nelio_Content_Reusable_Message|WP_Error an instance of this class or error.
	 *
	 * @since 3.2.0
	 */
	public static function parse( $json ) {
		$json = is_string( $json ) ? json_decode( $json, ARRAY_A ) : $json;
		$json = is_array( $json ) ? $json : array();

		$parsed = self::schema()->safe_parse( $json );
		if ( empty( $parsed['success'] ) ) {
			return new WP_Error( 'parsing-error', $parsed['error'] );
		}//end if

		$parsed = $parsed['data'];
		if ( 0 < $parsed['id'] && 'nc_reusable_social' !== get_post_type( $parsed['id'] ) ) {
			return new WP_Error( 'invalid-id', sprintf( 'Post %d is not a Reusable Message', $parsed['id'] ) );
		}//end if

		$result        = new self();
		$result->ID    = $parsed['id'] < 0 ? 0 : $parsed['id'];
		$result->attrs = $parsed;
		return $result;
	}//end parse()

	/**
	 * Saves the reusable message to the database.
	 *
	 * @return Nelio_Content_Reusable_Message|WP_Error the reusable message ID or an error if something went wrong.
	 *
	 * @since 3.2.0
	 */
	public function save() {
		$args = array(
			'post_content' => base64_encode( wp_json_encode( $this->attrs ) ),
			'post_excerpt' => $this->attrs['textComputed'],
			'post_type'    => 'nc_reusable_social',
			'post_status'  => 'draft',
		);

		$result = empty( $this->ID )
			? wp_insert_post( $args )
			: wp_update_post( array_merge( $args, array( 'ID' => $this->ID ) ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}//end if

		$this->ID = $result;
		return $this;
	}//end save()

	/**
	 * Converts this class into JSON.
	 *
	 * @return array this class into JSON.
	 *
	 * @since 3.2.0
	 */
	public function json() {
		return array_merge(
			$this->attrs,
			array( 'id' => $this->ID )
		);
	}//end json()

	private static $schema = null; // phpcs:ignore
	public static function schema(): Schema {
		if ( empty( self::$schema ) ) {
			self::$schema = Z::object(
				array(
					'id'           => Z::number()->optional(),
					'image'        => Z::string()->optional(),
					'imageId'      => Z::number()->optional(),
					'network'      => Z::enum(
						array(
							'bluesky',
							'facebook',
							'gmb',
							'instagram',
							'linkedin',
							'mastodon',
							'pinterest',
							'reddit',
							'telegram',
							'tiktok',
							'tumblr',
							'twitter',
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
		}//end if
		return self::$schema;
	}//end schema()

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
	}//end defaults()
}//end class

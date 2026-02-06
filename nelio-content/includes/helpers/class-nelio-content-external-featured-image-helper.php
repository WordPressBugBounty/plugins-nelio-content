<?php
/**
 * This file contains a helper class for working with (external) featured images.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.1.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements some helper functions for working with featured images.
 */
class Nelio_Content_External_Featured_Image_Helper {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_External_Featured_Image_Helper|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_External_Featured_Image_Helper
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * This function sets the external featured image of a certain post.
	 *
	 * @param int    $post_id The ID of the post.
	 * @param string $url     The URL of an external featured image.
	 * @param string $alt     Alternative text for the image.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function set_nelio_featured_image( $post_id, $url, $alt ) {

		if ( empty( $url ) ) {
			delete_post_meta( $post_id, '_nelioefi_url' );
			delete_post_meta( $post_id, '_nelioefi_alt' );
			return;
		}

		update_post_meta( $post_id, '_nelioefi_url', $url );
		if ( empty( $alt ) ) {
			delete_post_meta( $post_id, '_nelioefi_alt' );
		} else {
			update_post_meta( $post_id, '_nelioefi_alt', $alt );
		}
	}

	/**
	 * This function returns the URL of a Nelio Featured Image.
	 *
	 * Nelio Featured Images are either the user-set NelioEFI URL or, if featured
	 * image autosetting is enabled, the URL of one image included in the post.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return false|string
	 *
	 * @since  1.1.1
	 */
	public function get_nelio_featured_image( $post_id ) {

		$url = $this->get_external_featured_image( $post_id );
		if ( $url ) {
			return $url;
		}

		$settings        = Nelio_Content_Settings::instance();
		$auto_feat_image = $settings->get( 'auto_feat_image' );
		if ( 'disabled' === $auto_feat_image ) {
			return false;
		}

		return $this->get_auto_featured_image( $post_id, $auto_feat_image );
	}

	/**
	 * This function returns the value of the post meta `_nelioefi_url` if any.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return false|string
	 *
	 * @since  1.1.1
	 */
	public function get_external_featured_image( $post_id ) {

		// Use the external featured image (if any).
		$efi_url = get_post_meta( $post_id, '_nelioefi_url', true );
		if ( empty( $efi_url ) ) {
			return false;
		}

		return $efi_url;
	}

	/**
	 * This function returns the alt value of the external featured image.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return false|string
	 *                        or false if there isn't any.
	 *
	 * @since  1.1.1
	 */
	public function get_external_featured_alt( $post_id ) {

		if ( ! $this->get_external_featured_image( $post_id ) ) {
			return false;
		}

		return get_post_meta( $post_id, '_nelioefi_alt', true );
	}

	/**
	 * This function returns the URL of one image included in the post.
	 *
	 * @param int                  $post_id  The ID of the post.
	 * @param 'first'|'last'|'any' $position Optional. The image to return.  Default: `first`.
	 *
	 * @return false|string
	 *
	 * @since  1.1.1
	 */
	public function get_auto_featured_image( $post_id, $position = 'first' ) {

		$images = get_post_meta( $post_id, '_nc_auto_efi', true );

		if ( ! is_array( $images ) || ! isset( $images[ $position ] ) ) {
			$images = $this->extract_featured_images_for_autoset( $post_id );
		}

		if ( ! $images ) {
			return false;
		}

		if ( isset( $images[ $position ] ) ) {
			return $images[ $position ];
		} else {
			return false;
		}
	}

	/**
	 * This function analyzes a post and extracts the included images, so that
	 * they can be used as featured images.
	 *
	 * @param int $post_id The ID of the post.
	 *
	 * @return array{}|array{first:string,last:string,any:string}
	 *
	 * @since  1.1.1
	 */
	public function extract_featured_images_for_autoset( $post_id ) {

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			delete_post_meta( $post_id, '_nc_auto_efi' );
			return array();
		}

		$matches = array();
		preg_match_all(
			'/<img[^>]*src=("[^"]*"|\'[^\']*\')/i',
			$post->post_content,
			$matches
		);

		if ( empty( $matches[1] ) ) {
			delete_post_meta( $post_id, '_nc_auto_efi' );
			return array();
		}

		$matches = $matches[1];
		foreach ( $matches as $key => $value ) {
			$matches[ $key ] = preg_replace( '/^.(.*).$/', '$1', $value );
		}

		$matches = array_values( array_filter( $matches, fn( $m ) => is_string( $m ) ) );
		$last    = count( $matches ) - 1;
		$result  = array(
			'first' => ! empty( $matches[0] ) ? $matches[0] : '',
			'any'   => ! empty( $matches[ $last ] ) ? $matches[ $last ] : '',
			'last'  => ! empty( $matches[ $last ] ) ? $matches[ $last ] : '',
		);

		if ( count( $matches ) > 2 ) {

			unset( $matches[0] );
			unset( $matches[ count( $matches ) ] );

			$old_images = get_post_meta( $post_id, '_nc_auto_efi', true );
			if ( ! is_array( $old_images ) ) {
				$old_images = array();
			}

			if ( isset( $old_images['any'] ) && in_array( $old_images['any'], $matches, true ) ) {
				$result['any'] = $old_images['any'];
			} else {
				$result['any'] = $matches[ wp_rand( 1, count( $matches ) ) ];
			}
		}

		update_post_meta( $post_id, '_nc_auto_efi', $result );
		return $result;
	}
}

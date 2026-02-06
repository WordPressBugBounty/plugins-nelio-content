<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/public
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/public
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */
class Nelio_Content_Public {

	/**
	 * This instance.
	 *
	 * @since  1.3.4
	 * @var    Nelio_Content_Public|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Public
	 *
	 * @since  1.3.4
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	public function init() {
		add_filter( 'the_content', array( $this, 'remove_share_blocks' ), 99 );
	}

	/**
	 * Strips all ncshare tags from the content.
	 *
	 * @param string $content The original content.
	 *
	 * @return string
	 *
	 * @since  1.3.4
	 */
	public function remove_share_blocks( $content ) {
		$clean = preg_replace( '/<.?ncshare[^>]*>/', '', $content );
		return is_string( $clean ) ? $clean : $content;
	}
}

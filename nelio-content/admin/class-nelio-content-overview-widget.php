<?php
/**
 * Adds overview widget to WordPress’ dashboard.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin
 * @since      6.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * An overview widget in the Dashboard.
 */
class Nelio_Content_Overview_Widget {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Overview_Widget|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Overview_Widget
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
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'add_overview_widget' ) );
	}

	/**
	 * Callback to add overview widget.
	 *
	 * @return void
	 */
	public function add_overview_widget() {
		if ( nelio_content()->is_ready() ) {
			require nelio_content()->plugin_path . '/admin/views/nelio-content-overview-widget.php';
		}
	}
}

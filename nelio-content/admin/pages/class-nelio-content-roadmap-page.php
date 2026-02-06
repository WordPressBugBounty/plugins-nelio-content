<?php
/**
 * This file contains the class for registering the plugin's roadmap page.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.0.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the plugin's roadmap page.
 */
class Nelio_Content_Roadmap_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {
		parent::__construct(
			'nelio-content',
			'nelio-content-roadmap',
			_x( 'Roadmap', 'text', 'nelio-content' ),
			nelio_content_can_current_user_use_plugin()
		);
	}

	// @Implements
	public function enqueue_assets() {
		$help_url = 'https://trello.com/b/xzRPgkP2';
		printf(
			'<meta http-equiv="refresh" content="0; url=%s" />',
			esc_url( $help_url )
		);
	}

	// @Overwrites
	public function display() {
		// Nothing to be done.
	}
}

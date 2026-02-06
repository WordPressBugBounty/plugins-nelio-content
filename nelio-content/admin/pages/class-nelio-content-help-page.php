<?php
/**
 * This file contains the class that registers the help menu item in Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the help menu item in Nelio Content.
 */
class Nelio_Content_Help_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content-help',
			_x( 'Help', 'text', 'nelio-content' ),
			nelio_content_can_current_user_use_plugin()
		);
	}

	// @Implements
	public function enqueue_assets() {
		$help_url = add_query_arg(
			array(
				'utm_source'   => 'nelio-content',
				'utm_medium'   => 'plugin',
				'utm_campaign' => 'support',
				'utm_content'  => 'overview-help',
			),
			'https://neliosoftware.com/content/help/'
		);
		printf(
			'<meta http-equiv="refresh" content="0; url=%s" />',
			esc_url( $help_url )
		);
	}

	// @Implements
	public function display() {
		// Nothing to be done.
	}
}

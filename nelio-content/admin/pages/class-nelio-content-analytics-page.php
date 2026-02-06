<?php
/**
 * This file contains the class for rendering the analytics page.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that renders the analytics page.
 */
class Nelio_Content_Analytics_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content-analytics',
			_x( 'Analytics', 'text', 'nelio-content' ),
			nelio_content_can_current_user_use_plugin()
		);
	}

	// @Overrides
	protected function add_page_specific_hooks() {
		remove_all_filters( 'admin_notices' );
	}

	// @Overrides
	public function init() {

		$use_analytics = ! empty( nelio_content_get_post_types( 'analytics' ) );
		if ( ! $use_analytics ) {
			return;
		}

		parent::init();
	}

	// @Implements
	public function enqueue_assets() {

		wp_enqueue_style(
			'nelio-content-analytics-page',
			nelio_content()->plugin_url . '/assets/dist/css/analytics-page.css',
			array( 'nelio-content-components' ),
			nelio_content_get_script_version( 'analytics-page' )
		);
		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-analytics-page', 'analytics-page', true );

		wp_add_inline_script(
			'nelio-content-analytics-page',
			'NelioContent.initPage( "nelio-content-analytics-page" );'
		);
	}
}

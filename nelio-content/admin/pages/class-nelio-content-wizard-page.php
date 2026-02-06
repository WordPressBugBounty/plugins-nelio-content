<?php
/**
 * This file adds the wizard page to help users set up the calendar and starts the render process.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      4.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that adds the wizard page.
 */
class Nelio_Content_Wizard_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content',
			_x( 'Wizard', 'text', 'nelio-content' ),
			nelio_content_can_current_user_manage_account()
		);
	}

	// @Overrides
	protected function add_page_specific_hooks() {
		remove_all_filters( 'admin_notices' );
	}

	// @Implements
	public function enqueue_assets() {

		wp_enqueue_style(
			'nelio-content-wizard-page',
			nelio_content()->plugin_url . '/assets/dist/css/wizard-page.css',
			array( 'nelio-content-components', 'nelio-content-social-profiles-manager' ),
			nelio_content_get_script_version( 'wizard-page' )
		);
		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-wizard-page', 'wizard-page', true );
	}
}

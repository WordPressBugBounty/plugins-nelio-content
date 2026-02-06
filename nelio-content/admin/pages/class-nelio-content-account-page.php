<?php
/**
 * This file adds the account page and starts the render process.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that adds the account page.
 */
class Nelio_Content_Account_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content-account',
			_x( 'Account', 'text', 'nelio-content' ),
			nelio_content_can_current_user_manage_account()
		);
	}

	// @Overrides
	protected function add_page_specific_hooks() {
		remove_all_filters( 'admin_notices' );
	}

	// @Implements
	public function enqueue_assets() {

		$script   = 'NelioContent.initPage( "nelio-content-account-page", %s );';
		$settings = array(
			'isSubscribed' => nelio_content_is_subscribed(),
			'siteId'       => nelio_content_get_site_id(),
		);

		wp_enqueue_style(
			'nelio-content-account-page',
			nelio_content()->plugin_url . '/assets/dist/css/account-page.css',
			array( 'nelio-content-components' ),
			nelio_content_get_script_version( 'account-page' )
		);
		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-account-page', 'account-page', true );

		wp_add_inline_script(
			'nelio-content-account-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}
}

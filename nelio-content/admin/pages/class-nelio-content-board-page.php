<?php
/**
 * This file contains the class for rendering the mock board page.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that renders the board page.
 */
class Nelio_Content_Board_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content-board',
			_x( 'Content Board', 'menu', 'nelio-content' ),
			nc_can_current_user_use_plugin()
		);

	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function init() {

		$use_board = ! empty( nelio_content_get_post_types( 'content-board' ) );
		if ( ! $use_board ) {
			return;
		}//end if

		parent::init();

	}//end init()

	// @Overrides
	// phpcs:ignore
	protected function add_page_specific_hooks() {

		remove_all_filters( 'admin_notices' );

		add_filter( 'admin_footer_text', '__return_empty_string', 99 );
		add_filter( 'update_footer', '__return_empty_string', 99 );

	}//end add_page_specific_hooks()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {
		$script   = 'NelioContent.initPage( "nelio-content-board-page", %s );';
		$settings = array( 'page' => 'content-board' );

		wp_enqueue_style(
			'nelio-content-fake-premium-page',
			nelio_content()->plugin_url . '/assets/dist/css/fake-premium-page.css',
			array( 'nelio-content-components' ),
			nc_get_script_version( 'fake-premium-page' )
		);
		nc_enqueue_script_with_auto_deps( 'nelio-content-fake-premium-page', 'fake-premium-page', true );

		wp_add_inline_script(
			'nelio-content-fake-premium-page',
			sprintf(
				$script,
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);
	}//end enqueue_assets()
}//end class

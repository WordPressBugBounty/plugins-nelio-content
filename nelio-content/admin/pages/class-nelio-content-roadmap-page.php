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
			nc_can_current_user_use_plugin()
		);

	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function add_page() {

		parent::add_page();

		global $submenu;
		if ( isset( $submenu['nelio-content'] ) ) {
			$count = count( $submenu['nelio-content'] );
			for ( $i = 0; $i < $count; ++$i ) {
				if ( 'nelio-content-roadmap' === $submenu['nelio-content'][ $i ][2] ) {
					$submenu['nelio-content'][ $i ][2] = 'https://trello.com/b/xzRPgkP2'; // phpcs:ignore
					break;
				}//end if
			}//end for
		}//end if

	}//end add_page()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {
		// Nothing to be done.
	}//end enqueue_assets()

	// @Overwrites
	// phpcs:ignore
	public function display() {
		// Nothing to be done.
	}//end display()

}//end class

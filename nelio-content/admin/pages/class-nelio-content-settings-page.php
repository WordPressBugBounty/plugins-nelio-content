<?php
/**
 * This file contains the class for registering the plugin's settings page.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the plugin's settings page.
 */
class Nelio_Content_Settings_Page extends Nelio_Content_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-content',
			'nelio-content-settings',
			_x( 'Settings', 'text', 'nelio-content' ),
			nc_can_current_user_manage_plugin()
		);

	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function add_page_specific_hooks() {
		remove_all_filters( 'admin_notices' );
	}//end add_page_specific_hooks()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( 'nelio-content_page_nelio-content-settings' !== $screen->id ) {
			return;
		}//end if

		$settings = Nelio_Content_Settings::instance();
		wp_enqueue_script( $settings->get_generic_script_name() );
		wp_enqueue_style( $settings->get_generic_script_name() );
		wp_enqueue_style(
			'nelio-content-settings-page',
			nelio_content()->plugin_url . '/assets/dist/css/settings-page.css',
			array( 'nelio-content-components' ),
			nc_get_script_version( 'settings-page' )
		);
		nc_enqueue_script_with_auto_deps( 'nelio-content-settings-page', 'settings-page', true );

		$subpage = $this->get_current_subpage();
		$script  = $this->get_custom_subpage_script( $subpage );
		if ( $script ) {
			$this->enqueue_supage_assets( $script, $subpage );
		}//end if
	}//end enqueue_assets()

	// @Overwrites
	// phpcs:ignore
	public function display() {

		echo '<div class="wrap">';

		printf(
			'<h1 class="wp-heading-inline">%s</h1><span id="nelio-content-settings-title"></span>',
			esc_html_x( 'Nelio Content - Settings', 'text', 'nelio-content' )
		);

		settings_errors();

		echo '<form id="nelio-content-settings-form" method="post" action="options.php">';

		$settings = Nelio_Content_Settings::instance();
		settings_fields( $settings->get_option_group() );
		do_settings_sections( $settings->get_settings_page_name() );

		if ( $this->is_submit_button_enabled() ) {
			echo '<div id="nelio-content-settings-submit-button">';
			submit_button();
			echo '</div>';
		}//end if

		echo '</form>';

		echo '</div>';
	}//end display()

	private function enqueue_supage_assets( $script, $subpage ) {

		$handle    = "nelio-content-{$script}";
		$target_id = "nelio-settings__{$subpage}__subpage-content";

		wp_enqueue_style(
			$handle,
			nelio_content()->plugin_url . '/assets/dist/css/' . $script . '.css',
			array( 'nelio-content-components' ),
			nelio_content()->plugin_version
		);

		nc_enqueue_script_with_auto_deps( $handle, $script, true );
		wp_add_inline_script(
			$handle,
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode( $target_id )
			)
		);

	}//end enqueue_supage_assets()

	private function get_current_subpage() {
		$subpage = 'social--profiles';
		if ( isset( $_GET['subpage'] ) ) { // phpcs:ignore
			$subpage = sanitize_text_field( wp_unslash( $_GET['subpage'] ) ); // phpcs:ignore
		}//end if
		return $subpage;
	}//end get_current_subpage()

	private function get_custom_subpage_script( $subpage ) {
		$scripts = array(
			'social--profiles'    => 'social-profile-settings',
			'social--automations' => 'automations-settings',
			'tools--task-presets' => 'task-presets-settings',
		);
		return isset( $scripts[ $subpage ] ) ? $scripts[ $subpage ] : false;
	}//end get_custom_subpage_script()

	private function is_submit_button_enabled() {
		$subpage = $this->get_current_subpage();
		return empty( $this->get_custom_subpage_script( $subpage ) );
	}//end is_submit_button_enabled()

}//end class

<?php
/**
 * This file customizes the plugin list page added by WordPress.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class contains several methods to customize the plugin list page added
 * by WordPress and, in particular, the actions associated with Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.6
 */
class Nelio_Content_Plugin_List_Page {

	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'plugin_action_links_' . nelio_content()->plugin_file, array( $this, 'customize_plugin_actions' ) );
		add_action( 'admin_init', array( $this, 'maybe_show_premium_notice' ) );

	}//end init()

	public function customize_plugin_actions( $actions ) {

		if ( ! nc_get_site_id() ) {
			return $actions;
		}//end if

		if ( ! nc_is_subscribed() ) {

			$subscribe_url = add_query_arg(
				array(
					'utm_source'   => 'nelio-content',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'free',
					'utm_content'  => 'upgrade-to-premium',
				),
				'https://neliosoftware.com/content/pricing/',
			);

			$actions['subscribe'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $subscribe_url ),
				esc_html_x( 'Upgrade to Premium', 'command', 'nelio-content' )
			);

		}//end if

		if ( current_user_can( 'deactivate_plugin', nelio_content()->plugin_file ) && isset( $actions['deactivate'] ) ) {
			$actions['deactivate'] = sprintf(
				'<span class="nelio-content-deactivate-link"></span><noscript>%s</noscript>',
				$actions['deactivate']
			);
		}//end if

		return $actions;

	}//end customize_plugin_actions()

	public function maybe_show_premium_notice() {
		if ( ! nc_is_subscribed() ) {
			return;
		}//end if

		$premium_slug = 'nelio-content-premium/nelio-content-premium.php';
		if ( is_plugin_active( $premium_slug ) ) {
			return;
		}//end if

		$installed_plugins   = get_plugins();
		$is_plugin_installed = array_key_exists( $premium_slug, $installed_plugins ) || in_array( $premium_slug, $installed_plugins, true );
		$html_message        = $is_plugin_installed ?
			_x( 'You are subscribed to Nelio Content. Please activate <strong>Nelio Content Premium</strong> to benefit from all its features.', 'user', 'nelio-content' ) :
			_x( 'You are subscribed to Nelio Content. Please install <strong>Nelio Content Premium</strong> to benefit from all its features.', 'user', 'nelio-content' );

		add_action(
			'admin_notices',
			function() use ( $html_message ) {
				global $pagenow;
				if ( 'plugins.php' !== $pagenow ) {
					return;
				}//end if

				printf(
					'<div class="notice notice-warning"><p>%s</p><div class="nelio-content-install-premium-action"></div></div>',
					$html_message // phpcs:ignore
				);
			}
		);
	}//end maybe_show_premium_notice()

	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'plugins' !== $screen->id ) {
			return;
		}//end if

		$settings = array(
			'isPremiumActive' => is_plugin_active( 'nelio-content-premium/nelio-content-premium.php' ),
			'isSubscribed'    => nc_is_subscribed(),
			'cleanNonce'      => wp_create_nonce( 'nelio_content_clean_plugin_data_' . get_current_user_id() ),
			'deactivationUrl' => $this->get_deactivation_url(),
		);

		wp_enqueue_style(
			'nelio-content-plugin-list-page',
			nelio_content()->plugin_url . '/assets/dist/css/plugin-list-page.css',
			array( 'nelio-content-components' ),
			nc_get_script_version( 'plugin-list-page' )
		);
		nc_enqueue_script_with_auto_deps( 'nelio-content-plugin-list-page', 'plugin-list-page', true );

		wp_add_inline_script(
			'nelio-content-plugin-list-page',
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);

	}//end enqueue_assets()

	private function get_deactivation_url() {

		global $status, $page, $s;
		return add_query_arg(
			array(
				'action'        => 'deactivate',
				'plugin'        => nelio_content()->plugin_file,
				'plugin_status' => $status,
				'paged'         => $page,
				's'             => $s,
				'_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . nelio_content()->plugin_file ),
			),
			admin_url( 'plugins.php' )
		);

	}//end get_deactivation_url()

}//end class

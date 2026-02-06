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

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . nelio_content()->plugin_file, array( $this, 'customize_plugin_actions' ) );
		add_action( 'admin_init', array( $this, 'maybe_show_premium_notice' ) );
	}

	/**
	 * Callback to customize the plugin action links.
	 *
	 * @param array<string,string> $actions List of actions.
	 *
	 * @return array<string,string>
	 */
	public function customize_plugin_actions( $actions ) {

		if ( ! nelio_content_get_site_id() ) {
			return $actions;
		}

		if ( ! nelio_content_is_subscribed() ) {

			$subscribe_url = add_query_arg(
				array(
					'utm_source'   => 'nelio-content',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'free',
					'utm_content'  => 'upgrade-to-premium',
				),
				'https://neliosoftware.com/content/pricing/'
			);

			$actions['subscribe'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $subscribe_url ),
				esc_html_x( 'Upgrade to Premium', 'command', 'nelio-content' )
			);

		}

		if ( current_user_can( 'deactivate_plugin', nelio_content()->plugin_file ) && isset( $actions['deactivate'] ) ) {
			$actions['deactivate'] = sprintf(
				'<span class="nelio-content-deactivate-link"></span><noscript>%s</noscript>',
				$actions['deactivate']
			);
		}

		return $actions;
	}

	/**
	 * Callback to show premium notice.
	 *
	 * @return void
	 */
	public function maybe_show_premium_notice() {
		if ( ! nelio_content_is_subscribed() ) {
			return;
		}

		$premium_slug = 'nelio-content-premium/nelio-content-premium.php';
		if ( is_plugin_active( $premium_slug ) ) {
			return;
		}

		$installed_plugins   = get_plugins();
		$is_plugin_installed = array_key_exists( $premium_slug, $installed_plugins );
		$html_message        = $is_plugin_installed ?
			_x( 'You are subscribed to Nelio Content. Please activate <strong>Nelio Content Premium</strong> to benefit from all its features.', 'user', 'nelio-content' ) :
			_x( 'You are subscribed to Nelio Content. Please install <strong>Nelio Content Premium</strong> to benefit from all its features.', 'user', 'nelio-content' );

		add_action(
			'admin_notices',
			function () use ( $html_message ) {
				global $pagenow;
				if ( 'plugins.php' !== $pagenow ) {
					return;
				}

				printf(
					'<div class="notice notice-warning"><p>%s</p><div class="nelio-content-install-premium-action"></div></div>',
					wp_kses(
						$html_message,
						array( 'strong' => array() )
					)
				);
			}
		);
	}

	/**
	 * Callback to enqueue this page’s assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'plugins' !== $screen->id ) {
			return;
		}

		$settings = array(
			'isPremiumActive' => is_plugin_active( 'nelio-content-premium/nelio-content-premium.php' ),
			'isSubscribed'    => nelio_content_is_subscribed(),
			'cleanNonce'      => wp_create_nonce( 'nelio_content_clean_plugin_data_' . get_current_user_id() ),
			'deactivationUrl' => $this->get_deactivation_url(),
		);

		wp_enqueue_style(
			'nelio-content-plugin-list-page',
			nelio_content()->plugin_url . '/assets/dist/css/plugin-list-page.css',
			array( 'nelio-content-components' ),
			nelio_content_get_script_version( 'plugin-list-page' )
		);
		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-plugin-list-page', 'plugin-list-page', true );

		wp_add_inline_script(
			'nelio-content-plugin-list-page',
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode( $settings )
			)
		);
	}

	/**
	 * Returns the deactivation URL.
	 *
	 * @return string
	 */
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
	}
}

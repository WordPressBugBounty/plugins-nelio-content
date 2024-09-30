<?php
/**
 * Compatibility with Elementor Page Builder.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/compat
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

add_action(
	'admin_init',
	function() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}//end if

		$settings   = Nelio_Content_Settings::instance();
		$post_types = $settings->get( 'calendar_post_types', array() );
		$post_types = is_array( $post_types ) ? $post_types : array();
		if ( empty( $post_types ) ) {
			return;
		}//end if

		if ( ! isset( $_REQUEST['post'] ) && ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'elementor' ) ) { // phpcs:ignore
			return;
		}//end if

		$post_id = absint( $_REQUEST['post'] ); // phpcs:ignore
		if ( ! in_array( get_post_type( $post_id ), $post_types, true ) ) {
			return;
		}//end if

		add_action(
			'elementor/editor/footer',
			function() {
				$icon = file_get_contents( nelio_content()->plugin_path . '/assets/dist/images/logo.svg' );
				$icon = str_replace( 'fill="inherit"', 'fill="currentColor"', $icon );
				$icon = str_replace( 'width="20"', 'width="15"', $icon );
				$icon = str_replace( 'height="20"', 'width="15"', $icon );
				?>
				<button id="elementor-panel-footer-nelio-content" class="elementor-panel-footer-tool tooltip-target" data-tooltip="<?php echo esc_attr_x( 'Nelio Content', 'text', 'nelio-content' ); ?>" original-title="">
					<span id="elementor-panel-footer-nelio-content-label">
					<?php
						echo $icon; // phpcs:ignore
					?>
					</span>
					<span class="elementor-screen-only"><?php echo esc_html_x( 'Nelio Content', 'text', 'nelio-content' ); ?></span>
				</button>

				<aside id="elementor-nelio-content" aria-labelledby="elementor-nelio-content__header__title" style="display: none;">
					<div id="elementor-nelio-content__inner">
						<div id="elementor-nelio-content__header" class="ui-draggable-handle">
							<span id="elementor-nelio-content__header__icon">
							<?php
								echo $icon; // phpcs:ignore
							?>
								</span>
							<h2 id="elementor-nelio-content__header__title"><?php echo esc_html_x( 'Nelio Content', 'text', 'nelio-content' ); ?></h2>
							<button id="elementor-nelio-content__close">
								<i class="eicon-close" aria-hidden="true"></i>
								<span class="elementor-screen-only"><?php echo esc_html_x( 'Close', 'command', 'nelio-content' ); ?></span>
							</button>
						</div>
						<div id="elementor-nelio-content__elements"></div>
						<div id="elementor-nelio-content__footer">
							<div id="elementor-nelio-content__footer__resize-bar">
								<i class="eicon-ellipsis-h" aria-hidden="true"></i>
								<span class="elementor-screen-only"><?php echo esc_html_x( 'Resize', 'command', 'nelio-content' ); ?></span>
							</div>
						</div>
					</div>
				</aside>
				<?php
			},
			100
		);

		$aux = Nelio_Content_Admin::instance();
		add_action(
			'elementor/editor/before_enqueue_scripts',
			array( $aux, 'register_assets' )
		);
		add_action(
			'elementor/editor/before_enqueue_scripts',
			function() {
				$url   = nelio_content()->plugin_url;
				$files = array( 'post-quick-editor', 'social-message-editor', 'task-editor', 'social-timeline' );
				foreach ( $files as $file ) {
					wp_enqueue_style(
						"nelio-content-{$file}",
						"{$url}/assets/dist/css/{$file}.css",
						array( 'nelio-content-components' ),
						nc_get_script_version( $file )
					);
				}//end foreach
				wp_enqueue_media();
			}
		);

		$aux = new Nelio_Content_Edit_Post_Page();
		add_action(
			'elementor/editor/before_enqueue_scripts',
			array( $aux, 'register_assets' )
		);

		add_action(
			'elementor/editor/after_enqueue_scripts',
			function() {
				wp_enqueue_style( 'nelio-content-edit-post' );
				wp_enqueue_style(
					'nelio-content-elementor-editor',
					nelio_content()->plugin_url . '/assets/dist/css/elementor-editor.css',
					array( 'nelio-content-components' ),
					nc_get_script_version( 'elementor-editor' )
				);
				nc_enqueue_script_with_auto_deps( 'nelio-content-elementor-editor', 'elementor-editor', true );

				$aux = new Nelio_Content_Edit_Post_Page();
				$aux->enqueue_edit_post_style();
				wp_add_inline_script(
					'nelio-content-elementor-editor',
					sprintf(
						'NelioContent.initPage( %s );',
						wp_json_encode( $aux->get_init_args() )
					)
				);
			}
		);
	}
);

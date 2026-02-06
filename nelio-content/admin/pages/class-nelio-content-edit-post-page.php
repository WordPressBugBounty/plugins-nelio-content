<?php
/**
 * This file customizes the post edit screen.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers required UI elements to customize post edit screen.
 */
class Nelio_Content_Edit_Post_Page {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_assets' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_classic_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'maybe_enqueue_ncshare_highlight' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'maybe_enqueue_gutenberg_assets' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_add_mce_translations' ) );
		add_filter( 'mce_external_plugins', array( $this, 'maybe_add_mce_plugin' ) );
		add_filter( 'mce_buttons', array( $this, 'add_mce_buttons' ) );
		add_filter( 'tiny_mce_before_init', array( $this, 'add_mce_tags' ) );
	}

	/**
	 * Callback to register editor assets.
	 *
	 * @return void
	 */
	public function register_assets() {

		wp_register_style(
			'nelio-content-edit-post',
			nelio_content()->plugin_url . '/assets/dist/css/edit-post.css',
			array( 'nelio-content-components' ),
			nelio_content_get_script_version( 'edit-post' )
		);

		nelio_content_register_script_with_auto_deps( 'nelio-content-edit-post', 'edit-post', true );
		nelio_content_register_script_with_auto_deps( 'nelio-content-gutenberg-editor', 'gutenberg-editor', true );
		nelio_content_register_script_with_auto_deps( 'nelio-content-classic-editor', 'classic-editor', true );
	}

	/**
	 * Callback to enqueue gutenberg editor assets.
	 *
	 * @return void
	 */
	public function maybe_enqueue_gutenberg_assets() {

		if ( ! $this->is_managed_post_type() ) {
			return;
		}

		$this->enqueue_edit_post_style();

		if ( file_exists( nelio_content()->plugin_path . '/assets/dist/css/gutenberg-editor.css' ) ) {
			wp_enqueue_style(
				'nelio-content-gutenberg-editor',
				nelio_content()->plugin_url . '/assets/dist/css/gutenberg-editor.css',
				array( 'nelio-content-edit-post' ),
				nelio_content_get_script_version( 'gutenberg-editor' )
			);
		}

		wp_enqueue_script( 'nelio-content-gutenberg-editor' );
		wp_add_inline_script(
			'nelio-content-gutenberg-editor',
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode( $this->get_init_args() )
			)
		);
	}

	/**
	 * Callback to enqueue classic editor assets.
	 *
	 * @return void
	 */
	public function maybe_enqueue_classic_editor_assets() {

		if ( ! $this->is_classic_editor() ) {
			return;
		}

		if ( ! $this->is_managed_post_type() ) {
			return;
		}

		$this->enqueue_edit_post_style();

		wp_enqueue_script( 'nelio-content-classic-editor' );
		wp_add_inline_script(
			'nelio-content-classic-editor',
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode( $this->get_init_args() )
			)
		);
	}

	/**
	 * Callback to enqueue translations.
	 *
	 * @return void
	 */
	public function maybe_add_mce_translations() {
		if ( ! $this->is_managed_post_type() ) {
			return;
		}

		$translations = array(
			'pluginUrl'       => 'https://neliosoftware.com/content/',
			'description'     => _x( 'Social Automations by Nelio Content', 'text', 'nelio-content' ),
			'createAction'    => _x( 'Create Social Message', 'command', 'nelio-content' ),
			'highlightAction' => _x( 'Highlight for Auto Sharing', 'command', 'nelio-content' ),
			'removeAction'    => _x( 'Remove Highlight', 'command', 'nelio-content' ),
		);

		wp_add_inline_script(
			'wp-tinymce-root',
			sprintf(
				'NelioContentTinyMCEi18n = %s;',
				wp_json_encode( $translations )
			)
		);
	}

	/**
	 * Callback to add our tinymce plugin.
	 *
	 * @param array<string,string> $plugins Plugins.
	 *
	 * @return array<string,string>
	 */
	public function maybe_add_mce_plugin( $plugins ) {
		if ( ! $this->is_managed_post_type() ) {
			return $plugins;
		}

		$asset   = include nelio_content()->plugin_path . '/assets/dist/js/tinymce-actions.asset.php';
		$asset   = is_array( $asset ) ? $asset : array();
		$version = ! empty( $asset['version'] ) && is_string( $asset['version'] ) ? $asset['version'] : nelio_content()->plugin_version;

		$plugins['nelio_content'] = add_query_arg(
			'version',
			$version,
			nelio_content()->plugin_url . '/assets/dist/js/tinymce-actions.js'
		);

		return $plugins;
	}

	/**
	 * Callback to add new buttons.
	 *
	 * @param list<string> $buttons Buttons.
	 *
	 * @return list<string>
	 */
	public function add_mce_buttons( $buttons ) {
		$buttons[] = 'nelio_content';
		return $buttons;
	}

	/**
	 * Callback to add MCE tags.
	 *
	 * @param array<string,string> $options Options.
	 *
	 * @return array<string,string>
	 */
	public function add_mce_tags( $options ) {
		$append = function ( $arr, $key, $value, $sep = ',' ) {
			/** @var array<string,string> $arr   */
			/** @var string               $key   */
			/** @var string               $value */
			/** @var string               $sep   */

			if ( ! isset( $arr[ $key ] ) || empty( $arr[ $key ] ) ) {
				$arr[ $key ] = '';
			} else {
				$arr[ $key ] .= $sep;
			}
			$arr[ $key ] .= $value;
			return $arr;
		};

		$options = $append( $options, 'custom_elements', '~ncshare' );
		$options = $append( $options, 'extended_valid_elements', 'ncshare[class]' );
		$options = $append( $options, 'content_style', 'ncshare { background: #ffffaa; }', ' ' );
		$options = $append( $options, 'content_style', 'ncshare.nc-has-caret { background: #ffee00; }', ' ' );
		$options = $append( $options, 'content_style', 'ncshare.nc-has-caret ncshare { background: transparent }', ' ' );

		return $options;
	}

	/**
	 * Whether we’re on the classic editor.
	 *
	 * @return bool
	 */
	private function is_classic_editor() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( empty( $screen ) || $screen->is_block_editor() ) {
			return false;
		}

		$post_type = $screen->id;
		return in_array( $post_type, nelio_content_get_post_types( 'editor' ), true );
	}

	/**
	 * Whether our plugin should work with this post type or not.
	 *
	 * @return bool
	 */
	private function is_managed_post_type() {
		$post_type = get_post_type( $this->get_current_post_id() );
		return in_array( $post_type, nelio_content_get_post_types( 'editor' ), true );
	}

	/**
	 * Callback to enqueue styles.
	 *
	 * @return void
	 */
	public function enqueue_edit_post_style() {
		wp_enqueue_style( 'nelio-content-edit-post' );

		// TinyMCE.
		wp_add_inline_style(
			'nelio-content-edit-post',
			sprintf(
				'.mce-toolbar .mce-btn .mce-i-nelio-content-icon:before{background:none;background-image:url(%s);background-size:1em 1em;content:"";display:block;font-size:20px;height:1em;opacity:0.67;width:1em;}',
				nelio_content()->plugin_url . '/assets/dist/images/logo.svg'
			)
		);
	}

	/**
	 * Callback to enqueue ncshare highlight styles.
	 *
	 * @return void
	 */
	public function maybe_enqueue_ncshare_highlight() {
		if ( ! is_admin() ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_style( 'nelio-content-ncshare-highlight', false );
		wp_enqueue_style( 'nelio-content-ncshare-highlight' );
		wp_add_inline_style(
			'nelio-content-ncshare-highlight',
			'.rich-text ncshare { background: #ffa } .rich-text:focus ncshare[data-rich-text-format-boundary] { background: #fe0 }'
		);
	}

	/**
	 * Gets initial query args.
	 *
	 * @return TPost_Editor_Args
	 */
	public function get_init_args() {
		$post_id     = $this->get_current_post_id();
		$settings    = Nelio_Content_Settings::instance();
		$post_helper = Nelio_Content_Post_Helper::instance();

		return array(
			'attributes' => array(
				'externalFeatImage' => $this->get_external_featured_image( $post_id ),
				'followers'         => $post_helper->get_post_followers( $post_id ),
				'references'        => $post_helper->get_references( $post_id, 'all' ),
			),
			'postId'     => $post_id,
			'settings'   => array(
				'nonce'                  => wp_create_nonce( "nelio_content_save_post_{$post_id}" ),
				'qualityAnalysis'        => array(
					'canImageBeAutoSet' => 'disabled' !== $settings->get( 'auto_feat_image' ),
					'isFullyIntegrated' => $this->is_quality_analysis_fully_integrated(),
					'isYoastIntegrated' => $this->is_yoast_integrated(),
					'supportsFeatImage' => current_theme_supports( 'post-thumbnails' ),
				),
				'autoShareEndModes'      => nelio_content_get_auto_share_end_modes(),
				/** This filter is documented in includes/utils/class-nelio-content-post-saving.php */
				'shouldAuthorBeFollower' => ! empty( apply_filters( 'nelio_content_notification_auto_subscribe_post_author', true ) ),
			),
		);
	}

	/**
	 * Returns current post ID.
	 *
	 * @return int
	 */
	private function get_current_post_id() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return absint( $_GET['post'] );
		}

		/** @var WP_Post|null $post */
		global $post;
		if ( ! empty( $post ) ) {
			return absint( $post->ID );
		}

		return 0;
	}

	/**
	 * Whether quality analysis is integrated in publish meta box (classic editor) or uses its own meta.
	 *
	 * @return bool
	 */
	private function is_quality_analysis_fully_integrated() {
		/**
		 * Returns whether the quality analysis should be fully integrated with WordPress or not,
		 * using default sidebars and metaboxes.
		 *
		 * If it isn’t, Nelio Content will only use its own areas to display QA.
		 *
		 * @param boolean $is_visible whether the quality analysis is fully integrated with WP.
		 *                            Default: `true`.
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'nelio_content_is_quality_analysis_fully_integrated', true );
	}

	/**
	 * Whether Yoast analysis is integrated into our own analysis.
	 *
	 * @return bool
	 */
	private function is_yoast_integrated() {
		if (
			! is_plugin_active( 'wordpress-seo/wp-seo.php' ) &&
			! is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' )
		) {
			return false;
		}

		/**
		 * Whether Yoast should be integrated with Nelio Content’s quality analysis or not.
		 *
		 * @param boolean $integrated Default: true.
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'nelio_content_is_yoast_integrated_in_quality_analysis', true );
	}

	/**
	 * Gets external featured image.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array{url:string, alt:string}
	 */
	private function get_external_featured_image( $post_id ) {
		return array(
			'url' => get_post_meta( $post_id, '_nelioefi_url', true ),
			'alt' => get_post_meta( $post_id, '_nelioefi_alt', true ),
		);
	}
}

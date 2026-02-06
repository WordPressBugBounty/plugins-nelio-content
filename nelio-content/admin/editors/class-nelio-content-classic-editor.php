<?php
/**
 * This file adds a few hooks to work with the classic editor.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/editors
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers specific hooks to work with the classic editor.
 */
class Nelio_Content_Classic_Editor {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Classic_Editor|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Classic_Editor
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_post_analysis_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_classic_meta_boxes_values' ), 10, 2 );

		add_filter( 'redirect_post_location', array( $this, 'maybe_add_query_arg_for_timeline_auto_generation' ), 99 );
	}

	/**
	 * Callback to add meta boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {

		$settings = Nelio_Content_Settings::instance();
		if ( ! $this->is_quality_analysis_fully_integrated() ) {
			$this->add_meta_box( 'quality-analysis', _x( 'Quality Analysis', 'text', 'nelio-content' ), $settings->get( 'quality_check_post_types' ) );
		}

		$this->add_meta_box( 'social-media', _x( 'Social Media', 'text', 'nelio-content' ), $settings->get( 'social_post_types' ) );
		$this->add_meta_box( 'editorial-comments', _x( 'Editorial Comments', 'text', 'nelio-content' ), $settings->get( 'comment_post_types' ) );
		$this->add_meta_box( 'editorial-tasks', _x( 'Editorial Tasks', 'text', 'nelio-content' ), $settings->get( 'task_post_types' ) );
		$this->add_meta_box( 'links', _x( 'References', 'text', 'nelio-content' ), $settings->get( 'reference_post_types' ) );
		$this->add_meta_box( 'notifications', _x( 'Notifications', 'text', 'nelio-content' ), $settings->get( 'notification_post_types' ) );
		$this->add_meta_box( 'featured-image', _x( 'External Featured Image', 'text', 'nelio-content' ), $settings->get( 'efi_post_types' ) );
	}

	/**
	 * Callback to add post analysis meta box.
	 *
	 * @return void
	 */
	public function add_post_analysis_meta_box() {
		$types = nelio_content_get_post_types( 'quality-checks' );
		if ( ! in_array( get_post_type(), $types, true ) ) {
			return;
		}

		if ( ! $this->is_quality_analysis_fully_integrated() ) {
			return;
		}

		echo '<div id="nelio-content-quality-analysis"><div class="inside" style="padding: 0 1em 1em;"></div></div>';
	}

	/**
	 * Callback to save classic meta boxes values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post.
	 *
	 * @return void
	 */
	public function save_classic_meta_boxes_values( $post_id, $post ) {
		// If it's a revision or an autosave, do nothing.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['nelio-content-edit-post-nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nelio-content-edit-post-nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, "nelio_content_save_post_{$post_id}" ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['nelio-content-classic-values'] ) ) {
			return;
		}

		$values = sanitize_text_field( wp_unslash( $_REQUEST['nelio-content-classic-values'] ) );
		$values = json_decode( $values, true );
		$values = is_array( $values ) ? $values : null;
		Nelio_Content_Gutenberg::instance()->save( $values, $post );
	}

	/**
	 * Callback to add query arg for timeline auto generation on redirect post location.
	 *
	 * @param string $location URL.
	 *
	 * @return string
	 */
	public function maybe_add_query_arg_for_timeline_auto_generation( $location ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['_nc_auto_messages'] ) ) {
			$location = add_query_arg( 'nc-auto-messages', 'true', $location );
		}

		return $location;
	}

	/**
	 * Helper function to add meta box.
	 *
	 * @param string              $id        ID.
	 * @param string              $title     Title.
	 * @param string|list<string> $post_types Post type.
	 *
	 * @return void
	 */
	private function add_meta_box( $id, $title, $post_types ) {
		$extra      = array( '__back_compat_meta_box' => 'social-media' !== $id );
		$location   = 'social-media' === $id ? 'normal' : 'side';
		$post_types = is_array( $post_types ) ? $post_types : array( $post_types );
		foreach ( $post_types as $post_type ) {
			add_meta_box( "nelio-content-{$id}", $title, array( $this, 'render_loader' ), $post_type, $location, 'default', $extra );
		}
	}

	/**
	 * Callback function to render a loader.
	 *
	 * @return void
	 */
	public function render_loader() {
		printf(
			'<div class="nelio-content-loading-animation nelio-content-loading-animation--is-small"><span class="spinner is-active" style="margin-top:0;margin-bottom:0"></span><div class="nelio-content-loading-animation__text nelio-content-loading-animation__text--is-small">%s</div></div>',
			esc_html_x( 'Loading…', 'text', 'nelio-content' )
		);
	}

	/**
	 * Whether quality analysis is fully integrated.
	 *
	 * @return bool
	 */
	private function is_quality_analysis_fully_integrated() {
		/** This filter is documented in admin/pages/class-nelio-content-edit-post-page.php */
		return ! empty( apply_filters( 'nelio_content_is_quality_analysis_fully_integrated', true ) );
	}
}

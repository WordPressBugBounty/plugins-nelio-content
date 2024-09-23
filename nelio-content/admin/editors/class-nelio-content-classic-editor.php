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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'add_post_analysis_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_classic_meta_boxes_values' ), 10, 2 );

		add_action( 'redirect_post_location', array( $this, 'maybe_add_query_arg_for_timeline_auto_generation' ), 99 );

	}//end init()

	public function add_meta_boxes() {

		$meta_boxes = array(
			'quality-analysis'   => array(
				'setting' => 'quality_check_post_types',
				'title'   => _x( 'Quality Analysis', 'text', 'nelio-content' ),
			),
			'social-media'       => array(
				'setting' => 'social_post_types',
				'title'   => _x( 'Social Media', 'text', 'nelio-content' ),
			),
			'editorial-comments' => array(
				'setting' => 'comment_post_types',
				'title'   => _x( 'Editorial Comments', 'text', 'nelio-content' ),
			),
			'editorial-tasks'    => array(
				'setting' => 'task_post_types',
				'title'   => _x( 'Editorial Tasks', 'text', 'nelio-content' ),
			),
			'links'              => array(
				'setting' => 'reference_post_types',
				'title'   => _x( 'References', 'text', 'nelio-content' ),
			),
			'notifications'      => array(
				'setting' => 'notification_post_types',
				'title'   => _x( 'Notifications', 'text', 'nelio-content' ),
			),
			'featured-image'     => array(
				'setting' => 'efi_post_types',
				'title'   => _x( 'External Featured Image', 'text', 'nelio-content' ),
			),
		);

		if ( $this->is_quality_analysis_fully_integrated() ) {
			unset( $meta_boxes['quality-analysis'] );
		}//end if

		$settings = Nelio_Content_Settings::instance();
		foreach ( $meta_boxes as $id => $meta_box ) {
			foreach ( $settings->get( $meta_box['setting'] ) as $post_type ) {
				$this->add_meta_box( $id, $meta_box['title'], $post_type );
			}//end foreach
		}//end foreach

	}//end add_meta_boxes()

	public function add_post_analysis_meta_box() {
		$types = nelio_content_get_post_types( 'quality-checks' );
		if ( ! in_array( get_post_type(), $types, true ) ) {
			return;
		}//end if

		if ( ! $this->is_quality_analysis_fully_integrated() ) {
			return;
		}//end if

		echo '<div id="nelio-content-quality-analysis"><div class="inside" style="padding: 0 1em 1em;"></div></div>';
	}//end add_post_analysis_meta_box()

	public function save_classic_meta_boxes_values( $post_id, $post ) {
		// If it's a revision or an autosave, do nothing.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}//end if

		if ( ! isset( $_REQUEST['nelio-content-edit-post-nonce'] ) ) { // phpcs:ignore
			return;
		}//end if

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nelio-content-edit-post-nonce'] ) ); // phpcs:ignore
		if ( ! wp_verify_nonce( $nonce, "nelio_content_save_post_{$post_id}" ) ) {
			return;
		}//end if

		if ( ! isset( $_REQUEST['nelio-content-classic-values'] ) ) { // phpcs:ignore
			return;
		}//end if

		$values = sanitize_text_field( wp_unslash( $_REQUEST['nelio-content-classic-values'] ) ); // phpcs:ignore
		$values = json_decode( $values, ARRAY_A ); // phpcs:ignore
		Nelio_Content_Gutenberg::instance()->save( $values, $post );
	}//end save_classic_meta_boxes_values()

	public function maybe_add_query_arg_for_timeline_auto_generation( $location ) {

		if ( isset( $_REQUEST['_nc_auto_messages'] ) ) { // phpcs:ignore
			$location = add_query_arg( 'nc-auto-messages', 'true', $location );
		}//end if

		return $location;

	}//end maybe_add_query_arg_for_timeline_auto_generation()

	private function add_meta_box( $id, $title, $post_type ) {
		$extra    = array( '__back_compat_meta_box' => 'social-media' !== $id );
		$location = 'social-media' === $id ? 'normal' : 'side';
		add_meta_box( "nelio-content-{$id}", $title, array( $this, 'render_loader' ), $post_type, $location, 'default', $extra );
	}//end add_meta_box()

	public function render_loader() {
		printf(
			'<div class="nelio-content-loading-animation nelio-content-loading-animation--is-small"><span class="spinner is-active" style="margin-top:0;margin-bottom:0"></span><div class="nelio-content-loading-animation__text nelio-content-loading-animation__text--is-small">%s</div></div>',
			esc_html_x( 'Loading…', 'text', 'nelio-content' )
		);
	}//end render_loader()

	private function is_quality_analysis_fully_integrated() {
		/** This filter is documented in admin/pages/class-nelio-content-edit-post-page.php */
		return apply_filters( 'nelio_content_is_quality_analysis_fully_integrated', true );
	}//end is_quality_analysis_fully_integrated()

}//end class

<?php
/**
 * This file adds a few hooks to work with Gutenberg.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/editors
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers specific hooks to work with the Gutenberg.
 */
class Nelio_Content_Gutenberg {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Gutenberg|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Gutenberg
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
		add_action( 'rest_api_init', array( $this, 'register_custom_metas' ) );
	}

	/**
	 * Callback to register “nelio_content” custom meta.
	 *
	 * @return void
	 */
	public function register_custom_metas() {

		$post_types = nelio_content_get_post_types( 'editor' );
		if ( empty( $post_types ) ) {
			return;
		}

		register_rest_field(
			$post_types,
			'nelio_content',
			array(
				'get_callback'    => array( $this, 'get_values' ),
				'update_callback' => array( $this, 'save' ),
			)
		);
	}

	/**
	 * Callback to retrieve values.
	 *
	 * @param array{id:int} $data Data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_values( $data ) {
		return $this->load_values( $data['id'] );
	}

	/**
	 * Callback to save new values.
	 *
	 * @param array<mixed>|null $values Values.
	 * @param WP_Post           $post   Post.
	 *
	 * @return TNelio_Content_Custom_Meta
	 */
	public function save( $values, $post ) {
		$values = $this->parse_values( $values, $post->ID );

		$efi_helper = Nelio_Content_External_Featured_Image_Helper::instance();
		$efi_helper->set_nelio_featured_image( $post->ID, $values['efiUrl'], $values['efiAlt'] );

		$post_helper = Nelio_Content_Post_Helper::instance();
		$post_helper->save_post_followers( $post->ID, $values['followers'] );
		$post_helper->update_post_references( $post->ID, $values['suggestedReferences'], array() );
		$post_helper->enable_auto_share( $post->ID, $values['isAutoShareEnabled'] );
		$post_helper->update_auto_share_end_mode( $post->ID, $values['autoShareEndMode'] );
		$post_helper->update_automation_sources( $post->ID, $values['automationSources'] );
		$post_helper->update_post_highlights( $post->ID, $values['highlights'] );
		$post_helper->update_permalink_query_args( $post->ID, $values['permalinkQueryArgs'] );
		$post_helper->update_network_image_ids( $post->ID, $values['networkImageIds'] );
		$post_helper->update_series( $post->ID, $values['series'] );

		return $values;
	}

	/**
	 * Helper function to load post’s custom meta.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return TNelio_Content_Custom_Meta
	 */
	private function load_values( $post_id ) {
		$post_helper = Nelio_Content_Post_Helper::instance();

		$suggested = array_map(
			fn( $r ) => $r['url'],
			$post_helper->get_references( $post_id, 'suggested' )
		);

		$efi_url = get_post_meta( $post_id, '_nelioefi_url', true );
		$efi_alt = get_post_meta( $post_id, '_nelioefi_alt', true );
		return array(
			'autoShareEndMode'    => $post_helper->get_auto_share_end_mode( $post_id ),
			'automationSources'   => $post_helper->get_automation_sources( $post_id ),
			'efiAlt'              => $efi_alt,
			'efiUrl'              => $efi_url,
			'followers'           => $post_helper->get_post_followers( $post_id ),
			'highlights'          => $post_helper->get_post_highlights( $post_id ),
			'isAutoShareEnabled'  => $post_helper->is_auto_share_enabled( $post_id ),
			'networkImageIds'     => $post_helper->get_network_image_ids( $post_id ),
			'permalinkQueryArgs'  => $post_helper->get_permalink_query_args( $post_id ),
			'series'              => $post_helper->get_series( $post_id ),
			'suggestedReferences' => $suggested,
		);
	}

	/**
	 * Helper function to parse values.
	 *
	 * @param array<mixed>|null $values  Values.
	 * @param int               $post_id Post ID.
	 *
	 * @return TNelio_Content_Custom_Meta
	 */
	private function parse_values( $values, $post_id ) {
		if ( ! is_array( $values ) ) {
			$values = array();
		}

		$defaults = $this->load_values( $post_id );
		/** @var TNelio_Content_Custom_Meta */
		return wp_parse_args( $values, $defaults );
	}
}

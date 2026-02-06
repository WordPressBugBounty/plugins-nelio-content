<?php
/**
 * This file contains a class for registering the Reusable Message post type.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/extensions
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class registers the Reusable Message post type.
 */
class Nelio_Content_Reusable_Message_Post_Type_Register {

	/**
	 * This instance.
	 *
	 * @since  3.6.0
	 * @var    Nelio_Content_Reusable_Message_Post_Type_Register|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Reusable_Message_Post_Type_Register
	 *
	 * @since  3.6.0
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

		add_action( 'init', array( $this, 'register_post_type' ), 5 );

		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );
	}

	/**
	 * Callback to register post type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		if ( post_type_exists( 'nc_reusable_message' ) ) {
			return;
		}

		register_post_type(
			'nc_reusable_message',
			/**
			 * Filters the args of the nc_reusable_message post type.
			 *
			 * @since 3.6.0
			 *
			 * @param array $args The arguments, as defined in WordPress function register_post_type.
			 *
			 * @phpstan-ignore argument.type
			 */
			apply_filters(
				'nelio_content_register_reusable_message_post_type',
				array(
					'labels'          => array(
						'name'               => _x( 'Reusable Messages', 'text', 'nelio-content' ),
						'singular_name'      => _x( 'Reusable Message', 'text', 'nelio-content' ),
						'menu_name'          => _x( 'Reusable Messages', 'text', 'nelio-content' ),
						'all_items'          => _x( 'Reusable Messages', 'text', 'nelio-content' ),
						'add_new'            => _x( 'Add Reusable Message', 'command', 'nelio-content' ),
						'add_new_item'       => _x( 'Add Reusable Message', 'command', 'nelio-content' ),
						'edit_item'          => _x( 'Edit Reusable Message', 'command', 'nelio-content' ),
						'new_item'           => _x( 'New Reusable Message', 'text', 'nelio-content' ),
						'search_items'       => _x( 'Search Reusable Messages', 'command', 'nelio-content' ),
						'not_found'          => _x( 'No reusable messages found', 'text', 'nelio-content' ),
						'not_found_in_trash' => _x( 'No reusable messages found in trash', 'text', 'nelio-content' ),
					),
					'can_export'      => true,
					'capability_type' => 'nc_reusable_message',
					'hierarchical'    => false,
					'map_meta_cap'    => true,
					'public'          => false,
					'query_var'       => false,
					'rewrite'         => false,
					'show_in_menu'    => 'nelio-content',
					'show_ui'         => false,
					'supports'        => array( 'title', 'author' ),
				)
			)
		);
	}

	/**
	 * Callback to set user capabilities.
	 *
	 * @param array<string,bool> $capabilities All capabilities.
	 * @param list<string>       $caps         Caps.
	 * @param array<mixed>       $args         Args.
	 * @param WP_User            $user         User.
	 *
	 * @return array<string,bool>
	 */
	public function set_user_capabilities( $capabilities, $caps, $args, $user ) {

		$capabilities = array_filter(
			$capabilities,
			function ( $cap ) {
				return false === strpos( $cap, 'nc_reusable_message' );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( get_current_user_id() !== $user->ID ) {
			return $capabilities;
		}

		if ( ! did_action( 'init' ) || doing_action( 'init' ) ) {
			return $capabilities;
		}

		remove_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10 );
		if ( nelio_content_can_current_user_use_plugin() ) {
			$reference_capabilities = array(
				'create_nc_reusable_messages',
				'delete_nc_reusable_message',
				'delete_nc_reusable_messages',
				'delete_others_nc_reusable_messages',
				'delete_private_nc_reusable_messages',
				'delete_published_nc_reusable_messages',
				'edit_nc_reusable_message',
				'edit_nc_reusable_messages',
				'edit_others_nc_reusable_message',
				'edit_others_nc_reusable_messages',
				'edit_private_nc_reusable_messages',
				'edit_published_nc_reusable_messages',
				'publish_nc_reusable_messages',
				'read_nc_reusable_message',
				'read_private_nc_reusable_messages',
			);
			foreach ( $reference_capabilities as $cap ) {
				$capabilities[ $cap ] = true;
			}
		}
		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );

		return $capabilities;
	}
}

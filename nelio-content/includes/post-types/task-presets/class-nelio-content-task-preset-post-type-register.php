<?php
/**
 * This file contains a class for registering the Task Preset post type.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/extensions
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * This class registers the Task Preset post type.
 */
class Nelio_Content_Task_Preset_Post_Type_Register {

	/**
	 * The single instance of this class.
	 *
	 * @since  3.6.0
	 * @access protected
	 * @var    Nelio_Content
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content the single instance of this class.
	 *
	 * @since  3.6.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'init', array( $this, 'register_post_type' ), 5 );

		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );

	}//end init()

	public function register_post_type() {

		if ( post_type_exists( 'nc_task_preset' ) ) {
			return;
		}//end if

		/**
		 * This action fires right before registering the "Task Preset" post
		 * type.
		 *
		 * @since 3.6.0
		 */
		do_action( 'nelio_content_register_post_types' );

		register_post_type(
			'nc_task_preset',
			/**
			 * Filters the args of the nc_task_preset post type.
			 *
			 * @since 3.6.0
			 *
			 * @param array $args The arguments, as defined in WordPress function register_post_type.
			 */
			apply_filters(
				'nelio_content_register_task_preset_post_type',
				array(
					'labels'          => array(
						'name'               => _x( 'Task Presets', 'text', 'nelio-content' ),
						'singular_name'      => _x( 'Task Preset', 'text', 'nelio-content' ),
						'menu_name'          => _x( 'Task Presets', 'text', 'nelio-content' ),
						'all_items'          => _x( 'Task Presets', 'text', 'nelio-content' ),
						'add_new'            => _x( 'Add Task Preset', 'command', 'nelio-content' ),
						'add_new_item'       => _x( 'Add Task Preset', 'command', 'nelio-content' ),
						'edit_item'          => _x( 'Edit Task Preset', 'command', 'nelio-content' ),
						'new_item'           => _x( 'New Task Preset', 'text', 'nelio-content' ),
						'search_items'       => _x( 'Search Task Presets', 'command', 'nelio-content' ),
						'not_found'          => _x( 'No tasks presets found', 'text', 'nelio-content' ),
						'not_found_in_trash' => _x( 'No tasks presets found in trash', 'text', 'nelio-content' ),
					),
					'can_export'      => true,
					'capability_type' => 'nc_task_preset',
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

	}//end register_post_type()

	public function set_user_capabilities( $capabilities, $_, $__, $user ) {

		$capabilities = array_filter(
			$capabilities,
			function( $cap ) {
				return false === strpos( $cap, 'nc_task_preset' );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( get_current_user_id() !== $user->ID ) {
			return $capabilities;
		}//end if

		remove_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );
		if ( nc_can_current_user_use_plugin() ) {
			$reference_capabilities = array(
				'create_nc_task_presets',
				'delete_nc_task_preset',
				'delete_nc_task_presets',
				'delete_others_nc_task_presets',
				'delete_private_nc_task_presets',
				'delete_published_nc_task_presets',
				'edit_nc_task_preset',
				'edit_nc_task_presets',
				'edit_others_nc_task_preset',
				'edit_others_nc_task_presets',
				'edit_private_nc_task_presets',
				'edit_published_nc_task_presets',
				'publish_nc_task_presets',
				'read_nc_task_preset',
				'read_private_nc_task_presets',
			);
			foreach ( $reference_capabilities as $cap ) {
				$capabilities[ $cap ] = true;
			}//end foreach
		}//end if
		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );

		return $capabilities;
	}//end set_user_capabilities()

}//end class

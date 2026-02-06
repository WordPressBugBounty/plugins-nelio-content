<?php
/**
 * This file contains a class for registering the Reference post type.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/extensions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class registers the Reference post type and its statuses.
 */
class Nelio_Content_Reference_Post_Type_Register {

	/**
	 * This instance.
	 *
	 * @since  1.0.0
	 * @var    Nelio_Content_Reference_Post_Type_Register|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Reference_Post_Type_Register
	 *
	 * @since  1.0.0
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
		add_action( 'init', array( $this, 'register_post_statuses' ), 9 );

		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );
	}

	/**
	 * Callback to register post type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		if ( post_type_exists( 'nc_reference' ) ) {
			return;
		}

		register_post_type(
			'nc_reference',
			/**
			 * Filters the args of the nc_reference post type.
			 *
			 * @since 1.0.0
			 *
			 * @param array $args The arguments, as defined in WordPress function register_post_type.
			 *
			 * @phpstan-ignore argument.type
			 */
			apply_filters(
				'nelio_content_register_reference_post_type',
				array(
					'labels'          => array(
						'name'               => _x( 'External References', 'text', 'nelio-content' ),
						'singular_name'      => _x( 'Reference', 'text', 'nelio-content' ),
						'menu_name'          => _x( 'References', 'text', 'nelio-content' ),
						'all_items'          => _x( 'References', 'text', 'nelio-content' ),
						'add_new'            => _x( 'Add Reference', 'command', 'nelio-content' ),
						'add_new_item'       => _x( 'Add Reference', 'command', 'nelio-content' ),
						'edit_item'          => _x( 'Edit Reference', 'command', 'nelio-content' ),
						'new_item'           => _x( 'New Reference', 'text', 'nelio-content' ),
						'search_items'       => _x( 'Search References', 'command', 'nelio-content' ),
						'not_found'          => _x( 'No references found', 'text', 'nelio-content' ),
						'not_found_in_trash' => _x( 'No references found in trash', 'text', 'nelio-content' ),
					),
					'can_export'      => true,
					'capability_type' => 'nc_reference',
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
	 * Callback to register post statuses.
	 *
	 * @return void
	 */
	public function register_post_statuses() {

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Pending', 'text (reference)', 'nelio-content' ),
			/* translators: %s: Number. */
			'label_count' => _nx_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'text (reference)', 'nelio-content' ),
		);
		register_post_status( 'nc_pending', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Improvable', 'text (reference)', 'nelio-content' ),
			/* translators: %s: Number. */
			'label_count' => _nx_noop( 'Improvable <span class="count">(%s)</span>', 'Improvable <span class="count">(%s)</span>', 'text (reference)', 'nelio-content' ),
		);
		register_post_status( 'nc_improvable', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Complete', 'text (reference)', 'nelio-content' ),
			/* translators: %s: Number. */
			'label_count' => _nx_noop( 'Complete <span class="count">(%s)</span>', 'Complete <span class="count">(%s)</span>', 'text (reference)', 'nelio-content' ),
		);
		register_post_status( 'nc_complete', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Broken', 'text (reference)', 'nelio-content' ),
			/* translators: %s: Number. */
			'label_count' => _nx_noop( 'Broken <span class="count">(%s)</span>', 'Broken <span class="count">(%s)</span>', 'text (reference)', 'nelio-content' ),
		);
		register_post_status( 'nc_broken', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Check Required', 'text (reference)', 'nelio-content' ),
			/* translators: %s: Number. */
			'label_count' => _nx_noop( 'Check Required <span class="count">(%s)</span>', 'Check Required <span class="count">(%s)</span>', 'text (reference)', 'nelio-content' ),
		);
		register_post_status( 'nc_check', $args );
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
				return false === strpos( $cap, 'nc_reference' );
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
				'create_nc_references',
				'delete_nc_reference',
				'delete_nc_references',
				'delete_others_nc_references',
				'delete_private_nc_references',
				'delete_published_nc_references',
				'edit_nc_reference',
				'edit_nc_references',
				'edit_others_nc_reference',
				'edit_others_nc_references',
				'edit_private_nc_references',
				'edit_published_nc_references',
				'publish_nc_references',
				'read_nc_reference',
				'read_private_nc_references',
			);
			foreach ( $reference_capabilities as $cap ) {
				$capabilities[ $cap ] = true;
			}
		}
		add_filter( 'user_has_cap', array( $this, 'set_user_capabilities' ), 10, 4 );

		return $capabilities;
	}
}

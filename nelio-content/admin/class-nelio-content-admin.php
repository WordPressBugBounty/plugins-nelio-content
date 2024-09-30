<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

use function Nelio_Content\Helpers\not;
use function Nelio_Content\Helpers\key_by;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_Content_Admin {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'init', array( $this, 'init_pages' ), 9999 );

		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_calendar_in_admin_bar' ), 99 );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_editor_dialog_styles' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_media_scripts' ), 99 );
		add_filter( 'option_page_capability_nelio-content_group', array( $this, 'get_settings_capability' ) );

		add_action( 'admin_head', array( $this, 'add_workaround_styles' ) );

	}//end init()

	public function create_menu() {

		$capability =
			nc_can_current_user_use_plugin()
				? 'read'
				: 'invalid-capability';

		add_menu_page(
			'Nelio Content',
			'Nelio Content',
			$capability,
			'nelio-content',
			null,
			$this->get_plugin_icon(),
			25
		);

		$settings   = Nelio_Content_Settings::instance();
		$post_types = $settings->get( 'calendar_post_types', array() );

		foreach ( $post_types as $post_type ) {
			if ( 'post' === $post_type ) {
				add_posts_page(
					_x( 'Calendar', 'text', 'nelio-content' ),
					_x( 'Calendar', 'text', 'nelio-content' ),
					$capability,
					'nelio-content'
				);
			} else {
				add_submenu_page(
					'edit.php?post_type=' . $post_type,
					_x( 'Calendar', 'text', 'nelio-content' ),
					_x( 'Calendar', 'text', 'nelio-content' ),
					$capability,
					'nelio-content',
					null,
				);
			}//end if
		}//end foreach

	}//end create_menu()

	public function add_calendar_in_admin_bar() {

		global $wp_admin_bar;
		$original_blog_id = get_current_blog_id();

		foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {

			if ( is_multisite() ) {
				switch_to_blog( $blog->userblog_id );
			}//end if

			if ( ! nc_get_site_id() || ! nc_can_current_user_use_plugin() ) {
				continue;
			}//end if

			$wp_admin_bar->add_node(
				array(
					'parent' => is_multisite()
						? 'blog-' . get_current_blog_id()
						: 'site-name',
					'id'     => 'nelio-content-calendar-blog-' . get_current_blog_id(),
					'title'  => _x( 'Calendar', 'text (menu)', 'nelio-content' ),
					'href'   => admin_url( 'admin.php?page=nelio-content' ),
				)
			);

		}//end foreach

		if ( is_multisite() ) {
			switch_to_blog( $original_blog_id );
		}//end if

	}//end add_calendar_in_admin_bar()


	public function init_pages() {

		if ( ! nelio_content()->is_ready() ) {
			$page = new Nelio_Content_Welcome_Page();
			$page->init();
			return;
		}//end if

		$page = new Nelio_Content_Calendar_Page();
		$page->init();

		$page = new Nelio_Content_Board_Page();
		$page->init();

		$page = new Nelio_Content_Post_List_Page();
		$page->init();

		$page = new Nelio_Content_Edit_Post_Page();
		$page->init();

		$page = new Nelio_Content_Feeds_Page();
		$page->init();

		$page = new Nelio_Content_Analytics_Page();
		$page->init();

		$page = new Nelio_Content_Account_Page();
		$page->init();

		$page = new Nelio_Content_Settings_Page();
		$page->init();

		$page = new Nelio_Content_Roadmap_Page();
		$page->init();

		$page = new Nelio_Content_Help_Page();
		$page->init();

		$page = new Nelio_Content_Plugin_List_Page();
		$page->init();

	}//end init_pages()

	public function register_assets() {

		$url = nelio_content()->plugin_url;

		$scripts = array(
			'nelio-content-calendar',
			'nelio-content-components',
			'nelio-content-data',
			'nelio-content-date',
			'nelio-content-networks',
			'nelio-content-post-quick-editor',
			'nelio-content-premium-hooks-for-pages',
			'nelio-content-social-message-editor',
			'nelio-content-social-timeline',
			'nelio-content-task-editor',
			'nelio-content-utils',
		);

		foreach ( $scripts as $script ) {
			$file_without_ext = preg_replace( '/^nelio-content-/', '', $script );
			nc_register_script_with_auto_deps( $script, $file_without_ext, true );
		}//end foreach

		wp_register_style(
			'nelio-content-components',
			$url . '/assets/dist/css/components.css',
			array( 'wp-admin', 'wp-components' ),
			nc_get_script_version( 'components' )
		);

		$settings    = Nelio_Content_Settings::instance();
		$post_helper = Nelio_Content_Post_Helper::instance();

		$plugin_settings = array(
			'activePromos'            => nc_get_active_promos(),
			'apiRoot'                 => nc_get_api_url( '', 'browser' ),
			'areAutoTutorialsEnabled' => $settings->get( 'are_auto_tutorials_enabled' ),
			'authenticationToken'     => nc_generate_api_auth_token(),
			'isGAConnected'           => $this->is_ga_connected(),
			'limits'                  => nc_get_site_limits(),
			'nonReferenceDomains'     => $post_helper->get_non_reference_domains(),
			'pluginUrl'               => untrailingslashit( nelio_content()->plugin_url ),
			'premiumStatus'           => $this->get_premium_status(),
			'subscriptionPlan'        => nc_get_subscription() ? nc_get_subscription() : 'none',
		);

		$site_settings = array(
			'activePlugins'      => $this->get_active_plugins(),
			'adminUrl'           => admin_url(),
			'firstDayOfWeek'     => $this->get_first_day_of_week(),
			'homeUrl'            => home_url(),
			'id'                 => nc_get_site_id(),
			'isMultiAuthor'      => $this->is_multi_author(),
			'language'           => nc_get_language(),
			'now'                => gmdate( 'c' ),
			'postTypes'          => $this->get_post_types(),
			'postTypesByContext' => $this->get_post_types_by_context(),
			'restUrl'            => untrailingslashit( get_rest_url() ),
			'timezone'           => nc_get_timezone(),
		);

		$user_settings = array(
			'id'                             => get_current_user_id(),
			'pluginPermission'               => nc_can_current_user_manage_plugin() ? 'manage' : 'use',
			'postTypeCapabilities'           => $this->get_post_type_capabilities(),
			'socialEditorPermission'         => nelio_content_get_social_editor_permission(),
			'taskEditorPermission'           => $this->get_task_editor_permission(),
			'premiumEditorPermissionsByType' => $this->get_premium_editor_permissions_by_type(),
		);

		$script = <<<EOS
( function() {
	ncdata = wp.data.select( "nelio-content/data" );
	ncdata.getSocialProfiles();
	ncdata = wp.data.dispatch( "nelio-content/data" );
	ncdata.initPluginSettings( %1\$s );
	ncdata.initSiteSettings( %2\$s );
	ncdata.initUserSettings( %3\$s );
	ncdata.markSocialPublicationAsPaused( !! NelioContent?.utils?.getValue( "isSocialPublicationPaused" ) );
	ncdata.resetTaskPresets( %4\$s );
	ncdata.receiveFeeds( %5\$s );
	setInterval( function() {
		wp.data.dispatch( "nelio-content/data" ).setUtcNow( new Date().toISOString() );
	}, 30 * 60000 );
	ncdata = wp.data.select( "nelio-content/data" );
	ncdata.getAutomationGroups();
} )()
EOS;

		wp_add_inline_script(
			'nelio-content-data',
			sprintf(
				$script,
				wp_json_encode( $plugin_settings ),
				wp_json_encode( $site_settings ),
				wp_json_encode( $user_settings ),
				wp_json_encode( $this->get_task_presets() ),
				wp_json_encode( get_option( 'nc_feeds', array() ) ),
			)
		);

	}//end register_assets()

	public function maybe_enqueue_media_scripts() {

		if ( wp_script_is( 'nelio-content-components' ) ) {
			wp_enqueue_media();
		}//end if

	}//end maybe_enqueue_media_scripts()

	public function maybe_enqueue_editor_dialog_styles() {

		$url   = nelio_content()->plugin_url;
		$files = array( 'post-quick-editor', 'social-message-editor', 'task-editor', 'social-timeline' );
		foreach ( $files as $file ) {
			if ( wp_script_is( "nelio-content-{$file}", 'queue' ) ) {
				wp_enqueue_style(
					"nelio-content-{$file}",
					"{$url}/assets/dist/css/{$file}.css",
					array( 'nelio-content-components' ),
					nc_get_script_version( $file )
				);
			}//end if
		}//end foreach

	}//end maybe_enqueue_editor_dialog_styles()

	public function get_settings_capability() {
		return nc_can_current_user_manage_plugin() ? 'read' : 'invalid-capability';
	}//end get_settings_capability()

	public function add_workaround_styles() {
		echo '<style>';
		echo '[class*=nelio-content] button.components-button.has-icon .dashicon { margin: 0; padding: 0; }';
		echo 'button.components-button.has-icon[aria-label*="Nelio Content"] .dashicon { margin: 0; padding: 0; }';
		echo '</style>';
	}//end add_workaround_styles()

	private function get_plugin_icon() {

		$svg_icon_file = nelio_content()->plugin_path . '/assets/dist/images/logo.svg';
		if ( ! file_exists( $svg_icon_file ) ) {
			return false;
		}//end if

		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_icon_file ) ); // phpcs:ignore

	}//end get_plugin_icon()

	private function get_active_plugins() {
		$clean_extension = function( $plugin ) {
			return substr( $plugin, 0, -4 );
		};

		$plugins = array_keys( get_plugins() );
		$actives = array_map( 'is_plugin_active', $plugins );
		$plugins = array_combine( $plugins, $actives );
		$plugins = array_keys( array_filter( $plugins ) );
		$plugins = array_map( $clean_extension, $plugins );

		return $plugins;
	}//end get_active_plugins()

	private function is_ga_connected() {
		$settings = Nelio_Content_Settings::instance();
		$ga_view  = $settings->get( 'ga4_property_id' );
		return ! empty( $ga_view );
	}//end is_ga_connected()

	private function get_post_types_by_context() {
		$contexts   = array(
			'analytics',
			'calendar',
			'comments',
			'content-board',
			'efi',
			'future-actions',
			'notifications',
			'quality-checks',
			'references',
			'social',
			'tasks',
		);
		$post_types = array_map( 'nelio_content_get_post_types', $contexts );
		return array_combine( $contexts, $post_types );
	}//end get_post_types_by_context()

	private function get_post_types() {
		$post_types = $this->get_post_types_by_context();
		$post_types = array_values( array_unique( \Nelio_Content\Helpers\flatten( $post_types ) ) );
		$post_types = array_values( array_filter( array_map( // phpcs:ignore
			function ( $name ) {

				$type = get_post_type_object( $name );
				if ( ! $type || is_wp_error( $type ) ) {
					return false;
				}//end if

				return array(
					'name'     => $type->name,
					'labels'   => array(
						'all'      => $type->labels->all_items,
						'edit'     => $type->labels->edit_item,
						'new'      => $type->labels->add_new_item,
						'plural'   => $type->labels->name,
						'singular' => $type->labels->singular_name,
					),
					'statuses' => nelio_content_get_post_statuses( $type->name ),
					'supports' => array(
						'author'        => post_type_supports( $type->name, 'author' ),
						'title'         => post_type_supports( $type->name, 'title' ),
						'custom-fields' => post_type_supports( $type->name, 'custom-fields' ),
						'editor'        => post_type_supports( $type->name, 'editor' ),
						'excerpt'       => post_type_supports( $type->name, 'excerpt' ),
						'post-formats'  => post_type_supports( $type->name, 'post-formats' ),
						'thumbnail'     => post_type_supports( $type->name, 'thumbnail' ),
					),
				);

			},
			$post_types
		) ) ); // phpcs:ignore

		usort(
			$post_types,
			function ( $a, $b ) {
				if ( $a['labels']['singular'] < $b['labels']['singular'] ) {
					return -1;
				}//end if
				if ( $a['labels']['singular'] > $b['labels']['singular'] ) {
					return 1;
				}//end if
				return 0;
			}
		);

		return key_by( $post_types, 'name' );

	}//end get_post_types()

	private function get_first_day_of_week() {
		/**
		 * Filters the first day of the week in the calendar view.
		 *
		 * @param number $first_day First day of week, from 0 to 6 (both included). 0 is Sunday.
		 *
		 * @since 2.0.21
		 */
		return absint( apply_filters( 'nelio_content_first_day_of_week_in_calendar', absint( get_option( 'start_of_week' ) ) ) ) % 7;
	}//end get_first_day_of_week()

	private function is_multi_author() {

		/**
		 * Short-circuits the check to determine if site is multi author or not.
		 *
		 * @param boolean $is_multi_author whether site has multiple authors or not. Default: `null`.
		 *
		 * @since 2.3.4
		 */
		$short_circuit = apply_filters( 'nelio_content_is_multi_author', null );
		if ( null !== $short_circuit ) {
			return $short_circuit;
		}//end if

		$args = array(
			'capability' => array( 'edit_posts' ),
			'number'     => 2,
		);

		$remove_users_sorting = function( $results, $wp_user_query ) {
			$wp_user_query->query_orderby = '';
			return $results;
		};

		add_filter( 'users_pre_query', $remove_users_sorting, 10, 2 );
		$authors = get_users( $args );
		remove_filter( 'users_pre_query', $remove_users_sorting, 10, 2 );

		return 1 < count( $authors );

	}//end is_multi_author()

	private function get_post_type_capabilities() {
		$post_types = wp_list_pluck( $this->get_post_types(), 'name' );

		if ( nc_can_current_user_manage_plugin() ) {
			$capabilities = array();
			foreach ( $post_types as $name ) {
				$capabilities[ $name ] = array(
					'read',
					'edit-own',
					'edit-others',
					'create',
					'publish',
					'delete-own',
					'delete-others',
				);
			}//end foreach
			return $capabilities;
		}//end if

		$capabilities = array();
		foreach ( $post_types as $name ) {
			$type = get_post_type_object( $name );
			if ( empty( $type ) || is_wp_error( $type ) ) {
				continue;
			}//end if

			$capabilities[ $name ] = array_values( array_filter( // phpcs:ignore
				array(
					current_user_can( $type->cap->read ) ? 'read' : false,
					current_user_can( $type->cap->edit_posts ) ? 'edit-own' : false,
					current_user_can( $type->cap->edit_others_posts ) ? 'edit-others' : false,
					current_user_can( $type->cap->create_posts ) ? 'create' : false,
					current_user_can( $type->cap->publish_posts ) ? 'publish' : false,
					current_user_can( $type->cap->delete_posts ) ? 'delete-own' : false,
					current_user_can( $type->cap->delete_others_posts ) ? 'delete-others' : false,
				)
			) ); // phpcs:ignore
		}//end foreach

		return $capabilities;
	}//end get_post_type_capabilities()

	private function get_task_editor_permission() {
		$permission = 'none';
		if ( nc_can_current_user_use_plugin() ) {
			$permission = 'post-type';
		}//end if
		if ( nc_can_current_user_manage_plugin() ) {
			$permission = 'all';
		}//end if

		/**
		 * Filters the required permission for the user to be able to edit tasks.
		 *
		 * Possible values are:
		 *
		 * - `all`: the user can edit any task
		 * - `post-type`: the user can edit tasks related to a post type they can edit or tasks assigned to them
		 * - `none`: the user can’t edit any tasks
		 *
		 * @param string $permission the required permisison. Possibe values are:
		 * @param int    $user_id    current user id
		 *
		 * @since 2.0.0
		 */
		$new_permission = apply_filters( 'nelio_content_task_editor_permission', $permission, get_current_user_id() );

		if ( in_array( $new_permission, array( 'all', 'post-type', 'none' ), true ) ) {
			$permission = $new_permission;
		}//end if

		return $permission;
	}//end get_task_editor_permission()

	private function get_premium_editor_permissions_by_type() {
		/**
		 * Filters premium editor permissions by type.
		 *
		 * @param array $permissions editor permissions by type.
		 *
		 * @since 3.6.0
		 */
		return apply_filters( 'nelio_content_premium_editor_permissions_by_type', array() );
	}//end get_premium_editor_permissions_by_type()

	private function get_task_presets() {
		$posts = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => 'nc_task_preset',
				'post_status' => 'draft',
				'numberposts' => Nelio_Content_Task_Preset::MAX_PRESETS, // phpcs:ignore
			)
		);
		$posts = array_map( fn( $p ) => new Nelio_Content_Task_Preset( $p ), $posts );
		$posts = array_values( array_filter( $posts, not( 'is_wp_error' ) ) );
		$posts = array_map( fn( $p ) => $p->json(), $posts );
		usort( $posts, fn( $a, $b ) => $a['id'] - $b['id'] );
		return $posts;
	}//end get_task_presets()

	private function get_premium_status() {
		$premium_slug         = 'nelio-content-premium/nelio-content-premium.php';
		$installed_plugins    = get_plugins();
		$is_premium_installed = array_key_exists( $premium_slug, $installed_plugins ) || in_array( $premium_slug, $installed_plugins, true );
		$status               = $is_premium_installed ? 'inactive' : 'uninstalled';

		/**
		 * Filters premium status.
		 *
		 * Possible statuses: `uninstalled`, `inactive`, `unsubscribed`, `invalid-version`, or `ready`.
		 *
		 * @param string status Status of the premium plugin.
		 *
		 * @since 3.6.0
		 */
		return apply_filters( 'nelio_content_premium_status', $status );
	}//end get_premium_status()

}//end class

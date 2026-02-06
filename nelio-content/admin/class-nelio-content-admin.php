<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\not;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_Content_Admin {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Admin|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Admin
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

		add_action( 'init', array( $this, 'init_pages' ), 9999 );

		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_calendar_in_admin_bar' ), 99 );

		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_editor_dialog_styles' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_media_scripts' ), 99 );
		add_filter( 'option_page_capability_nelio-content_group', array( $this, 'get_settings_capability' ) );
	}

	/**
	 * Callback to create main menu.
	 *
	 * @return void
	 */
	public function create_menu() {

		$capability =
			nelio_content_can_current_user_use_plugin()
				? 'read'
				: 'invalid-capability';

		add_menu_page(
			'Nelio Content',
			'Nelio Content',
			$capability,
			'nelio-content',
			'__return_false',
			$this->get_plugin_icon(),
			25
		);

		$settings   = Nelio_Content_Settings::instance();
		$post_types = $settings->get( 'calendar_post_types' );

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
					'__return_false'
				);
			}
		}
	}

	/**
	 * Callback to add calendar option in admin bar.
	 *
	 * @return void
	 */
	public function add_calendar_in_admin_bar() {

		/** @var WP_Admin_Bar $wp_admin_bar */
		global $wp_admin_bar;
		$original_blog_id = get_current_blog_id();

		/** @var null|object{blogs?: list<object{userblog_id?:int}>} $user */
		$user  = ! empty( $wp_admin_bar->user ) ? $wp_admin_bar->user : null;
		$blogs = ! empty( $user ) && ! empty( $user->blogs ) ? $user->blogs : array();
		foreach ( $blogs as $blog ) {
			if ( empty( $blog->userblog_id ) ) {
				continue;
			}

			if ( is_multisite() ) {
				switch_to_blog( $blog->userblog_id );
			}

			if ( ! nelio_content_get_site_id() || ! nelio_content_can_current_user_use_plugin() ) {
				continue;
			}

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

		}

		if ( is_multisite() ) {
			switch_to_blog( $original_blog_id );
		}
	}


	/**
	 * Callback to init pages.
	 *
	 * @return void
	 */
	public function init_pages() {

		if ( ! nelio_content()->is_ready() ) {
			$page = new Nelio_Content_Welcome_Page();
			$page->init();
			return;
		}

		if ( nelio_content()->is_wizard_requested() ) {
			$page = new Nelio_Content_Wizard_Page();
			$page->init();
			return;
		}

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
	}

	/**
	 * Callback to register assets.
	 *
	 * @return void
	 */
	public function register_assets() {

		$url = nelio_content()->plugin_url;

		$scripts = array(
			'nelio-content-calendar',
			'nelio-content-components',
			'nelio-content-constants',
			'nelio-content-data',
			'nelio-content-date',
			'nelio-content-networks',
			'nelio-content-post-quick-editor',
			'nelio-content-premium-hooks-for-pages',
			'nelio-content-social-message-editor',
			'nelio-content-social-profiles-manager',
			'nelio-content-social-timeline',
			'nelio-content-task-editor',
			'nelio-content-utils',
		);

		foreach ( $scripts as $script ) {
			$file_without_ext = preg_replace( '/^nelio-content-/', '', $script );
			$file_without_ext = is_string( $file_without_ext ) ? $file_without_ext : '';
			nelio_content_register_script_with_auto_deps( $script, $file_without_ext, true );
		}

		wp_register_style(
			'nelio-content-components',
			$url . '/assets/dist/css/components.css',
			array( 'wp-admin', 'wp-components' ),
			nelio_content_get_script_version( 'components' )
		);

		wp_register_style(
			'nelio-content-social-profiles-manager',
			$url . '/assets/dist/css/social-profiles-manager.css',
			array( 'wp-admin', 'wp-components', 'nelio-content-components' ),
			nelio_content_get_script_version( 'social-profiles-manager' )
		);

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_style( 'nelio-content-colored-post', false );
		wp_add_inline_style( 'nelio-content-colored-post', $this->get_post_status_colors_style() );

		$settings    = Nelio_Content_Settings::instance();
		$post_helper = Nelio_Content_Post_Helper::instance();

		$plugin_settings = array(
			'activePromos'            => nelio_content_get_active_promos(),
			'apiRoot'                 => nelio_content_get_api_url( '', 'browser' ),
			'areAutoTutorialsEnabled' => $settings->get( 'are_auto_tutorials_enabled' ),
			'authenticationToken'     => nelio_content_generate_api_auth_token(),
			'isGAConnected'           => $this->is_ga_connected(),
			'limits'                  => nelio_content_get_site_limits(),
			'nonReferenceDomains'     => $post_helper->get_non_reference_domains(),
			'pluginUrl'               => untrailingslashit( nelio_content()->plugin_url ),
			'premiumStatus'           => $this->get_premium_status(),
			'subscriptionPlan'        => nelio_content_get_subscription() ? nelio_content_get_subscription() : 'none',
			'seriesTaxonomySlug'      => $settings->get( 'series_taxonomy_slug' ),
		);

		$site_settings = array(
			'activePlugins'      => $this->get_active_plugins(),
			'adminUrl'           => admin_url(),
			'firstDayOfWeek'     => $this->get_first_day_of_week(),
			'homeUrl'            => home_url(),
			'id'                 => nelio_content_get_site_id(),
			'isMultiAuthor'      => $this->is_multi_author(),
			'isStaging'          => nelio_content_is_staging(),
			'language'           => nelio_content_get_language(),
			'now'                => gmdate( 'c' ),
			'postTypes'          => $this->get_post_types(),
			'postTypesByContext' => $this->get_post_types_by_context(),
			'restUrl'            => untrailingslashit( get_rest_url() ),
			'roles'              => $this->get_roles(),
			'timezone'           => nelio_content_get_timezone(),
		);

		$user_settings = array(
			'id'                             => get_current_user_id(),
			'role'                           => nelio_content_get_current_user_role(),
			'pluginPermission'               => nelio_content_can_current_user_manage_plugin() ? 'manage' : 'use',
			'postTypeCapabilities'           => $this->get_post_type_capabilities(),
			'socialEditorPermission'         => nelio_content_get_social_editor_permission(),
			'taskEditorPermission'           => $this->get_task_editor_permission(),
			'premiumEditorPermissionsByType' => $this->get_premium_editor_permissions_by_type(),
		);

		$script = '
		( function() {
			ncdata = wp.data.select( "nelio-content/data" );
			ncdata.getSocialProfiles();
			ncdata = wp.data.dispatch( "nelio-content/data" );
			ncdata.initPluginSettings( %1$s );
			ncdata.initSiteSettings( %2$s );
			ncdata.initUserSettings( %3$s );
			ncdata.markSocialPublicationAsPaused( !! NelioContent?.utils?.getValue( "isSocialPublicationPaused" ) );
			ncdata.resetStatuses( %4$s );
			ncdata.resetTaskPresets( %5$s );
			ncdata.receiveFeeds( %6$s );
			setInterval( function() {
				wp.data.dispatch( "nelio-content/data" ).setUtcNow( new Date().toISOString() );
			}, 30 * 60000 );
			ncdata = wp.data.select( "nelio-content/data" );
			ncdata.getAutomationGroups();
		} )();';

		wp_add_inline_script(
			'nelio-content-data',
			sprintf(
				$script,
				wp_json_encode( $plugin_settings ),
				wp_json_encode( $site_settings ),
				wp_json_encode( $user_settings ),
				wp_json_encode( nelio_content_get_statuses() ),
				wp_json_encode( $this->get_task_presets() ),
				wp_json_encode( get_option( 'nc_feeds', array() ) )
			)
		);
	}

	/**
	 * Callback to enqueue media scripts.
	 *
	 * @return void
	 */
	public function maybe_enqueue_media_scripts() {

		if ( wp_script_is( 'nelio-content-components' ) ) {
			wp_enqueue_media();
		}
	}

	/**
	 * Callback to enqueue editor dialog styles.
	 *
	 * @return void
	 */
	public function maybe_enqueue_editor_dialog_styles() {

		$url   = nelio_content()->plugin_url;
		$files = array( 'post-quick-editor', 'social-message-editor', 'task-editor', 'social-timeline' );
		foreach ( $files as $file ) {
			if ( wp_script_is( "nelio-content-{$file}", 'queue' ) ) {
				wp_enqueue_style(
					"nelio-content-{$file}",
					"{$url}/assets/dist/css/{$file}.css",
					array( 'nelio-content-components' ),
					nelio_content_get_script_version( $file )
				);
			}
		}
	}

	/**
	 * Gets settings capability.
	 *
	 * @return 'read'|'nelio-content-invalid-capability'
	 */
	public function get_settings_capability() {
		return nelio_content_can_current_user_manage_plugin() ? 'read' : 'nelio-content-invalid-capability';
	}

	/**
	 * Returns the plugin’s icon.
	 *
	 * @return string
	 */
	private function get_plugin_icon() {

		$svg_icon_file = nelio_content()->plugin_path . '/assets/dist/images/logo.svg';
		if ( ! file_exists( $svg_icon_file ) ) {
			return 'admin-generic';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$icon = file_get_contents( $svg_icon_file );
		if ( empty( $icon ) ) {
			return 'admin-generic';
		}

		return 'data:image/svg+xml;base64,' . base64_encode( $icon );
	}

	/**
	 * Gets list of active plugins.
	 *
	 * @return list<string>
	 */
	private function get_active_plugins() {
		$plugins = array_keys( get_plugins() );
		$actives = array_map( 'is_plugin_active', $plugins );
		$plugins = array_combine( $plugins, $actives );
		$plugins = array_keys( array_filter( $plugins ) );
		$plugins = array_map( fn( $p ) => substr( $p, 0, -4 ), $plugins );
		return $plugins;
	}

	/**
	 * Whether GA is connected.
	 *
	 * @return bool
	 */
	private function is_ga_connected() {
		$settings = Nelio_Content_Settings::instance();
		$ga_data  = $settings->get( 'google_analytics_data' );
		return ! empty( $ga_data['id'] );
	}

	/**
	 * Gets post types by context.
	 *
	 * @return array<TPost_Type_Context,list<string>>
	 */
	private function get_post_types_by_context() {
		return array(
			'analytics'      => nelio_content_get_post_types( 'analytics' ),
			'calendar'       => nelio_content_get_post_types( 'calendar' ),
			'comments'       => nelio_content_get_post_types( 'comments' ),
			'content-board'  => nelio_content_get_post_types( 'content-board' ),
			'efi'            => nelio_content_get_post_types( 'efi' ),
			'future-actions' => nelio_content_get_post_types( 'future-actions' ),
			'notifications'  => nelio_content_get_post_types( 'notifications' ),
			'quality-checks' => nelio_content_get_post_types( 'quality-checks' ),
			'references'     => nelio_content_get_post_types( 'references' ),
			'series'         => nelio_content_get_post_types( 'series' ),
			'social'         => nelio_content_get_post_types( 'social' ),
			'tasks'          => nelio_content_get_post_types( 'tasks' ),
			'wp'             => nelio_content_get_post_types( 'wp' ),
		);
	}

	/**
	 * Gets post types.
	 *
	 * @return array<string,TPost_Type>
	 */
	private function get_post_types() {
		$post_types = $this->get_post_types_by_context();
		$post_types = array_values( array_unique( \Nelio_Content\Helpers\flatten( $post_types ) ) );
		$post_types = array_map( fn( $name ) => get_post_type_object( $name ), $post_types );
		/** @var list<\WP_Post_Type> $post_types */
		$post_types = array_values( array_filter( $post_types ) );
		$post_types = array_map(
			function ( $type ) {
				return array(
					'name'     => $type->name,
					'labels'   => array(
						'all'      => is_string( $type->labels->all_items ) ? $type->labels->all_items : '',
						'edit'     => is_string( $type->labels->edit_item ) ? $type->labels->edit_item : '',
						'new'      => is_string( $type->labels->add_new_item ) ? $type->labels->add_new_item : '',
						'plural'   => is_string( $type->labels->name ) ? $type->labels->name : '',
						'singular' => is_string( $type->labels->singular_name ) ? $type->labels->singular_name : '',
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
		);

		usort(
			$post_types,
			function ( $a, $b ) {
				if ( $a['labels']['singular'] < $b['labels']['singular'] ) {
					return -1;
				}
				if ( $a['labels']['singular'] > $b['labels']['singular'] ) {
					return 1;
				}
				return 0;
			}
		);

		return array_combine( array_map( fn( $t ) => $t['name'], $post_types ), $post_types );
	}

	/**
	 * Gets roles.
	 *
	 * @return array<string,TRole>
	 */
	private function get_roles() {
		$roles = wp_roles()->roles;
		$roles = array_map(
			function ( $role, $key ) {
				/** @var array{name:string,capabilities:array<mixed>} $role */
				/** @var string                                       $key  */

				/** @var TRole */
				return array(
					'id'      => $key,
					'name'    => translate_user_role( $role['name'] ),
					'isAdmin' => (
						in_array( 'administrator', array_keys( array_filter( $role['capabilities'] ) ), true ) ||
						in_array( 'manage_options', array_keys( array_filter( $role['capabilities'] ) ), true )
					),
				);
			},
			$roles,
			array_keys( $roles )
		);
		asort( $roles );

		$roles = array_values( $roles );
		return array_combine( array_map( fn( $r ) => $r['id'], $roles ), $roles );
	}

	/**
	 * Gets first day of the week.
	 *
	 * @return int
	 */
	private function get_first_day_of_week() {
		$start_of_week = absint( get_option( 'start_of_week' ) ) % 7;

		/**
		 * Filters the first day of the week in the calendar view.
		 *
		 * @param int $start_of_week First day of week, from 0 to 6 (both included). 0 is Sunday.
		 *
		 * @since 2.0.21
		 */
		$start_of_week = apply_filters( 'nelio_content_first_day_of_week_in_calendar', $start_of_week );
		return absint( $start_of_week ) % 7;
	}

	/**
	 * Whether the site has multiple authors.
	 *
	 * @return bool
	 */
	private function is_multi_author() {

		/**
		 * Short-circuits the check to determine if site is multi author or not.
		 *
		 * @param boolean|null $is_multi_author whether site has multiple authors or not. Default: `null`.
		 *
		 * @since 2.3.4
		 */
		$short_circuit = apply_filters( 'nelio_content_is_multi_author', null );
		if ( null !== $short_circuit ) {
			return $short_circuit;
		}

		$args = array(
			'capability' => array( 'edit_posts' ),
			'number'     => 2,
		);

		$remove_users_sorting = function ( $results, $wp_user_query ) {
			/** @var mixed         $results       */
			/** @var WP_User_Query $wp_user_query */

			$wp_user_query->query_orderby = '';
			return $results;
		};

		add_filter( 'users_pre_query', $remove_users_sorting, 10, 2 );
		$authors = get_users( $args );
		remove_filter( 'users_pre_query', $remove_users_sorting, 10 );

		return 1 < count( $authors );
	}

	/**
	 * Gets post type capabilities.
	 *
	 * @return array<string,list<TPost_Capability>>
	 */
	private function get_post_type_capabilities() {
		$post_types = array_keys( $this->get_post_types() );

		if ( nelio_content_can_current_user_manage_plugin() ) {
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
			}
			return $capabilities;
		}

		$capabilities = array();
		foreach ( $post_types as $name ) {
			$type = get_post_type_object( $name );
			if ( empty( $type ) ) {
				continue;
			}

			$caps = array(
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->read ) ? 'read' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->edit_posts ) ? 'edit-own' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->edit_others_posts ) ? 'edit-others' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->create_posts ) ? 'create' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->publish_posts ) ? 'publish' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->delete_posts ) ? 'delete-own' : false,
				/** @phpstan-ignore-next-line argument.type */
				current_user_can( $type->cap->delete_others_posts ) ? 'delete-others' : false,
			);

			$capabilities[ $name ] = array_values( array_filter( $caps ) );
		}

		return $capabilities;
	}

	/**
	 * Gets task editor permission.
	 *
	 * @return 'none'|'post-type'|'all'
	 */
	private function get_task_editor_permission() {
		$permission = 'none';
		if ( nelio_content_can_current_user_use_plugin() ) {
			$permission = 'post-type';
		}
		if ( nelio_content_can_current_user_manage_plugin() ) {
			$permission = 'all';
		}

		/**
		 * Filters the required permission for the user to be able to edit tasks.
		 *
		 * Possible values are:
		 *
		 * - `all`: the user can edit any task
		 * - `post-type`: the user can edit tasks related to a post type they can edit or tasks assigned to them
		 * - `none`: the user can’t edit any tasks
		 *
		 * @param 'none'|'post-type'|'all' $permission the required permisison. Possibe values are:
		 * @param int                      $user_id    current user id
		 *
		 * @since 2.0.0
		 */
		$new_permission = apply_filters( 'nelio_content_task_editor_permission', $permission, get_current_user_id() );

		if ( in_array( $new_permission, array( 'all', 'post-type', 'none' ), true ) ) {
			$permission = $new_permission;
		}

		return $permission;
	}

	/**
	 * Gets premium editor permissions by type.
	 *
	 * @return array<string,'none'|'post-type'|'all'>
	 */
	private function get_premium_editor_permissions_by_type() {
		/**
		 * Filters premium editor permissions by type.
		 *
		 * @param array<string,'none'|'post-type'|'all'> $permissions editor permissions by type.
		 *
		 * @since 3.6.0
		 */
		return apply_filters( 'nelio_content_premium_editor_permissions_by_type', array() );
	}

	/**
	 * Gets task presets.
	 *
	 * @return list<TTask_Preset>
	 */
	private function get_task_presets() {
		$posts = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => 'nc_task_preset',
				'post_status' => 'draft',
				'numberposts' => Nelio_Content_Task_Preset::MAX_PRESETS,
			)
		);
		$posts = array_map( fn( $p ) => new Nelio_Content_Task_Preset( $p ), $posts );
		$posts = array_filter( $posts, not( 'is_wp_error' ) );
		$posts = array_values( array_map( fn( $p ) => $p->json(), $posts ) );
		usort( $posts, fn( $a, $b ) => $a['id'] - $b['id'] );
		return $posts;
	}

	/**
	 * Returns CSS style to colorize post statuses.
	 *
	 * @return string
	 */
	private function get_post_status_colors_style() {
		$post_types = $this->get_post_types_by_context();
		$post_types = array_values( array_unique( \Nelio_Content\Helpers\flatten( $post_types ) ) );

		$statuses_by_type = array_combine(
			$post_types,
			array_map( fn( $t ) => nelio_content_get_post_statuses( $t ), $post_types )
		);

		$default_statuses = array_values( $statuses_by_type )[0];
		if ( empty( $default_statuses ) ) {
			return '';
		}

		$default_statuses = array_combine(
			array_map( fn( $ds ) => $ds['slug'], $default_statuses ),
			$default_statuses
		);

		$statuses_by_type = array_map(
			fn ( $statuses ) => array_filter(
				$statuses,
				fn( $status ) => (
					( $status['colors']['main'] ?? '' ) !== ( $default_statuses[ $status['slug'] ]['colors']['main'] ?? '' ) ||
					( $status['colors']['background'] ?? '' ) !== ( $default_statuses[ $status['slug'] ]['colors']['background'] ?? '' )
				)
			),
			$statuses_by_type
		);

		$statuses_by_type = array_merge( array( '' => $default_statuses ), $statuses_by_type );

		$result = '';
		foreach ( $statuses_by_type as $type => $statuses ) {
			$type_selector = empty( $type )
				? '.nelio-content-colored-post'
				: ".nelio-content-colored-post[data-post-type=\"{$type}\"]";
			foreach ( $statuses as $status ) {
				$result .= sprintf(
					'%1$s{background-color:%2$s;border-top-color:%3$s}',
					"{$type_selector}[data-status=\"{$status['slug']}\"]",
					$status['colors']['background'] ?? '',
					$status['colors']['main'] ?? ''
				);
			}
		}

		return $result;
	}

	/**
	 * Gets premium status.
	 *
	 * @return TPremium_Status
	 */
	private function get_premium_status() {
		$premium_slug         = 'nelio-content-premium/nelio-content-premium.php';
		$installed_plugins    = get_plugins();
		$is_premium_installed = array_key_exists( $premium_slug, $installed_plugins );
		$status               = $is_premium_installed ? 'inactive' : 'uninstalled';
		$status               = nelio_content_is_subscribed() ? $status : 'unsubscribed';

		/**
		 * Filters premium status.
		 *
		 * @param TPremium_Status $status Status of the premium plugin.
		 *
		 * @since 3.6.0
		 */
		return apply_filters( 'nelio_content_premium_status', $status );
	}
}

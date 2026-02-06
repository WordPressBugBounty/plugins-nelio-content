<?php
/**
 * The file that includes installation-related functions and actions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class configures WordPress and installs some capabilities.
 */
class Nelio_Content_Install {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Install|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Install
	 *
	 * @since  2.0.0
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
	 *
	 * @since  1.0.0
	 */
	public function init() {

		$main_file = nelio_content()->plugin_path . '/nelio-content.php';
		register_activation_hook( $main_file, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'allow_ncshare_tags' ) );
		add_action( 'admin_init', array( $this, 'maybe_update' ), 5 );

		add_action( 'nelio_content_installed', array( $this, 'notify_to_cloud' ) );

		add_action( 'nelio_content_installed', array( $this, 'update_to_nc2' ), 10, 2 );
		add_action( 'nelio_content_updated', array( $this, 'update_to_nc2' ), 10, 2 );

		add_action( 'nelio_content_installed', array( $this, 'update_to_nc3_6' ), 10, 2 );
		add_action( 'nelio_content_updated', array( $this, 'update_to_nc3_6' ), 10, 2 );
	}

	/**
	 * Callback to add the ncshare tag to the list of valid tags in post content.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function allow_ncshare_tags() {
		/** @var array<string,array<string,true>> */
		global $allowedposttags;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$allowedposttags['ncshare'] = array( 'class' => true );
	}

	/**
	 * Callback to check the currently-installed version and, if it's old, installs the new one.
	 *
	 * @return void
	 *
	 * @since  2.0.0
	 */
	public function maybe_update() {

		$last_version = get_option( 'nc_version' );
		$this_version = nelio_content()->plugin_version;
		if ( defined( 'IFRAME_REQUEST' ) || ( $last_version === $this_version ) ) {
			return;
		}

		$this->install();

		/**
		 * Fires once the plugin has been updated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'nelio_content_updated', $this_version, $last_version );
	}

	/**
	 * Callback to install Nelio Content.
	 *
	 * This function registers new post types, adds a few capabilities, and more.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function install() {

		if ( defined( 'NELIO_CONTENT_INSTALLING' ) ) {
			return;
		}
		define( 'NELIO_CONTENT_INSTALLING', true );

		// Installation actions here.
		$this->set_proper_permissions();

		// Update version.
		$this_version = nelio_content()->plugin_version;
		$last_version = get_option( 'nc_version', '0.0.0' );
		update_option( 'nc_version', $this_version );

		// Check if the user has social profiles.
		update_option( 'nc_has_social_profiles', $this->has_social_profiles() );

		/**
		 * Fires once the plugin has been installed.
		 *
		 * @since 1.0.0
		 */
		do_action( 'nelio_content_installed', $this_version, $last_version );
	}

	/**
	 * Whether we have social profiles or not.
	 *
	 * @return bool
	 */
	private function has_social_profiles() {

		if ( ! nelio_content_get_site_id() ) {
			return false;
		}

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/profiles', 'wp' );
		$response = wp_remote_request( $url, $data );
		$profiles = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $profiles ) ) {
			return false;
		}

		/** @var list<TSocial_Profile> $profiles */
		return count( $profiles ) > 0;
	}

	/**
	 * Sets proper permissions.
	 *
	 * @return void
	 */
	private function set_proper_permissions() {

		$contributor_caps = array(
			'read_nc_reference',
			'read_private_nc_references',
		);

		$author_caps = array_merge(
			$contributor_caps,
			array(
				'edit_nc_references',
				'edit_nc_reference',
				'edit_published_nc_references',
			)
		);

		$editor_caps = array_merge(
			$author_caps,
			array(
				'edit_others_nc_references',
				'publish_nc_references',
				'edit_private_nc_references',
				'edit_others_nc_reference',
				'create_nc_references',
				'delete_nc_reference',
				'delete_nc_references',
				'delete_others_nc_references',
				'delete_private_nc_references',
				'delete_published_nc_references',
			)
		);

		// Set new roles.
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( $editor_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		if ( is_multisite() ) {
			$super_admins = get_super_admins();
			foreach ( $super_admins as $username ) {
				$user = get_user_by( 'login', $username );
				if ( $user ) {
					foreach ( $editor_caps as $cap ) {
						$user->add_cap( $cap );
					}
				}
			}
		}

		$role = get_role( 'editor' );
		if ( $role ) {
			foreach ( $editor_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		$role = get_role( 'author' );
		if ( $role ) {
			foreach ( $author_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		$role = get_role( 'contributor' );
		if ( $role ) {
			foreach ( $contributor_caps as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Callback to notify cloud.
	 *
	 * @return void
	 */
	public function notify_to_cloud() {
		if ( ! nelio_content_get_site_id() ) {
			return;
		}

		$body = wp_json_encode(
			array(
				'url'              => home_url(),
				'timezone'         => nelio_content_get_timezone(),
				'language'         => nelio_content_get_language(),
				'isPluginInactive' => false,
			)
		);
		assert( ! empty( $body ) );

		$data = array(
			'method'  => 'PUT',
			'timeout' => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'headers' => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'    => $body,
		);

		$url = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		wp_remote_request( $url, $data );
	}

	/** @var bool */
	private $did_migrate_to_nc2 = false;

	/**
	 * Updates to Nelio Content 3.6.
	 *
	 * @param string $current_version Current version.
	 * @param string $prev_version    Previous version.
	 *
	 * @return void
	 */
	public function update_to_nc2( $current_version, $prev_version ) {
		if ( $this->did_migrate_to_nc2 ) {
			return;
		}
		$this->did_migrate_to_nc2 = true;

		if (
			! version_compare( $prev_version, '2.0', '<' ) ||
			empty( nelio_content_get_site_id() )
		) {
			return;
		}

		$this->migrate_post_statuses();
		$this->update_auto_sharing_fields();
	}//end update_to_nc2()

	/** @var bool */
	private $did_migrate_to_nc3_6 = false;

	/**
	 * Updates to Nelio Content 3.6.
	 *
	 * @param string $current_version Current version.
	 * @param string $prev_version    Previous version.
	 *
	 * @return void
	 */
	public function update_to_nc3_6( $current_version, $prev_version ) {
		if ( $this->did_migrate_to_nc3_6 ) {
			return;
		}
		$this->did_migrate_to_nc3_6 = true;

		if (
			! version_compare( $prev_version, '3.6', '<' ) ||
			empty( nelio_content_get_site_id() )
		) {
			return;
		}

		$options             = $this->get_settings();
		$calendar_post_types = empty( $options['calendar_post_types'] ) ? array( 'post' ) : $options['calendar_post_types'];
		$options             = wp_parse_args(
			$options,
			array(
				'analytics_post_types'      => empty( $options['use_analytics'] ) ? array() : $calendar_post_types,
				'calendar_post_types'       => $calendar_post_types,
				'comment_post_types'        => $calendar_post_types,
				'content_board_post_types'  => $calendar_post_types,
				'editorial_references'      => $calendar_post_types,
				'efi_post_types'            => empty( $options['use_external_featured_image'] ) ? array() : $calendar_post_types,
				'notification_post_types'   => empty( $options['use_notifications'] ) ? array() : $calendar_post_types,
				'quality_check_types'       => $calendar_post_types,
				'social_post_types'         => $calendar_post_types,
				'task_post_types'           => $calendar_post_types,
				'use_comment_notifications' => ! empty( $options['use_notifications'] ),
				'use_feeds'                 => ! empty( get_option( 'nc_feeds', array() ) ),
				'use_task_notifications'    => ! empty( $options['use_notifications'] ),
			)
		);
		unset( $options['use_analytics'] );
		unset( $options['use_external_featured_image'] );
		unset( $options['use_notifications'] );
		update_option( 'nelio-content_settings', $options );
	}//end update_to_nc3_6()

	/**
	 * Migrates post statuses.
	 *
	 * @return void
	 */
	private function migrate_post_statuses() {
		/** @var wpdb $wpdb */
		global $wpdb;

		$settings = $this->get_settings();

		/** @var non-empty-list<string> */
		$types = ! empty( $settings['calendar_post_types'] ) ? $settings['calendar_post_types'] : array( 'post' );

		$query = sprintf(
			"UPDATE %s SET post_status = 'draft' WHERE post_type IN (%s) AND post_status IN (%s)",
			$wpdb->posts,
			$this->escape_array( $types ),
			$this->escape_array( array( 'idea', 'assigned', 'in-progress' ) )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( $query );
	}

	/**
	 * Updates auto sharing fields.
	 *
	 * @return void
	 */
	private function update_auto_sharing_fields() {
		/** @var wpdb $wpdb */
		global $wpdb;

		$query = "UPDATE {$wpdb->postmeta} SET meta_key = '_nc_exclude_from_auto_share' WHERE meta_key = '_nc_exclude_from_reshare'";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $query );

		$query = "UPDATE {$wpdb->postmeta} SET meta_key = '_nc_include_in_auto_share' WHERE meta_key = '_nc_include_in_reshare'";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $query );

		$settings = $this->get_settings();
		if ( isset( $settings['auto_reshare_default_mode'] ) && is_string( $settings['auto_reshare_default_mode'] ) ) {
			$settings['auto_share_default_mode'] = str_replace( 'reshare', 'auto-share', $settings['auto_reshare_default_mode'] );
			unset( $settings['auto_reshare_default_mode'] );
			update_option( 'nelio-content_settings', $settings );
		}
	}

	/**
	 * Escapes array.
	 *
	 * @param non-empty-list<string> $arr Array.
	 *
	 * @return string
	 */
	private function escape_array( $arr ) {
		$values = array_map( fn( $v ) => esc_sql( $v ), $arr );
		return "'" . implode( "', '", $values ) . "'";
	}

	/**
	 * Gets settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		/** @var array<string,mixed> */
		return get_option( 'nelio-content_settings', array() );
	}
}

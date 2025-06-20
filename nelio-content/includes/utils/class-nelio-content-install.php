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

	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content_Install the single instance of this class.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hook in tabs.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function init() {

		$main_file = nelio_content()->plugin_path . '/nelio-content.php';
		register_activation_hook( $main_file, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'allow_ncshare_tags' ) );
		add_action( 'admin_init', array( $this, 'maybe_update' ), 5 );

		add_action( 'nelio_content_installed', array( $this, 'notify_to_cloud' ), 10, 2 );

		add_action( 'nelio_content_installed', array( $this, 'update_to_nc2' ), 10, 2 );
		add_action( 'nelio_content_updated', array( $this, 'update_to_nc2' ), 10, 2 );

		add_action( 'nelio_content_installed', array( $this, 'update_to_nc3_6' ), 10, 2 );
		add_action( 'nelio_content_updated', array( $this, 'update_to_nc3_6' ), 10, 2 );
	}//end init()

	/**
	 * Adds the ncshare tag to the list of valid tags in post content.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function allow_ncshare_tags() {

		global $allowedposttags;
		$allowedposttags['ncshare'] = array( 'class' => true ); // phpcs:ignore
	}//end allow_ncshare_tags()

	/**
	 * Checks the currently-installed version and, if it's old, installs the new one.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public function maybe_update() {

		$last_version = get_option( 'nc_version' );
		$this_version = nelio_content()->plugin_version;
		if ( defined( 'IFRAME_REQUEST' ) || ( $last_version === $this_version ) ) {
			return;
		}//end if

		$this->install();

		/**
		 * Fires once the plugin has been updated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'nelio_content_updated', $this_version, $last_version );
	}//end maybe_update()

	/**
	 * Install Nelio Content.
	 *
	 * This function registers new post types, adds a few capabilities, and more.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function install() {

		if ( defined( 'NELIO_CONTENT_INSTALLING' ) ) {
			return;
		}//end if
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
	}//end install()

	private function has_social_profiles() {

		if ( ! nc_get_site_id() ) {
			return false;
		}//end if

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nelio_content_request_timeout', 30 ),
			'sslverify' => ! nc_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nc_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nc_get_api_url( '/site/' . nc_get_site_id() . '/profiles', 'wp' );
		$response = wp_remote_request( $url, $data );

		if ( is_wp_error( $response ) ) {
			return false;
		}//end if

		$profiles = array();
		if ( isset( $response['body'] ) ) {
			$profiles = json_decode( $response['body'] );
		}//end if

		return count( $profiles ) > 0;
	}//end has_social_profiles()

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
			}//end foreach
		}//end if

		if ( is_multisite() ) {
			$super_admins = get_super_admins();
			foreach ( $super_admins as $username ) {
				$user = get_user_by( 'login', $username );
				if ( $user ) {
					foreach ( $editor_caps as $cap ) {
						$user->add_cap( $cap );
					}//end foreach
				}//end if
			}//end foreach
		}//end if

		$role = get_role( 'editor' );
		if ( $role ) {
			foreach ( $editor_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

		$role = get_role( 'author' );
		if ( $role ) {
			foreach ( $author_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if

		$role = get_role( 'contributor' );
		if ( $role ) {
			foreach ( $contributor_caps as $cap ) {
				$role->add_cap( $cap );
			}//end foreach
		}//end if
	}//end set_proper_permissions()

	public function notify_to_cloud() {
		if ( ! nc_get_site_id() ) {
			return false;
		}//end if

		$data = array(
			'method'  => 'PUT',
			'timeout' => apply_filters( 'nelio_content_request_timeout', 30 ),
			'headers' => array(
				'Authorization' => 'Bearer ' . nc_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'url'              => home_url(),
					'timezone'         => nc_get_timezone(),
					'language'         => nc_get_language(),
					'isPluginInactive' => false,
				)
			),
		);

		$url = nc_get_api_url( '/site/' . nc_get_site_id(), 'wp' );
		wp_remote_request( $url, $data );
	}//end notify_to_cloud()

	// phpcs:ignore
	private $did_migrate_to_nc2 = false;
	public function update_to_nc2( $_, $prev_version ) {
		if ( $this->did_migrate_to_nc2 ) {
			return;
		}//end if
		$this->did_migrate_to_nc2 = true;

		if (
			! version_compare( $prev_version, '2.0', '<' ) ||
			empty( nc_get_site_id() )
		) {
			return;
		}//end if

		$this->migrate_post_statuses();
		$this->update_auto_sharing_fields();
	}//end update_to_nc2()

	// phpcs:ignore
	private $did_migrate_to_nc3_6 = false;
	public function update_to_nc3_6( $_, $prev_version ) {
		if ( $this->did_migrate_to_nc3_6 ) {
			return;
		}//end if
		$this->did_migrate_to_nc3_6 = true;

		if (
			! version_compare( $prev_version, '3.6', '<' ) ||
			empty( nc_get_site_id() )
		) {
			return;
		}//end if

		$options             = get_option( 'nelio-content_settings' );
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

	private function migrate_post_statuses() {

		global $wpdb;
		$settings = Nelio_Content_Settings::instance();
		$settings = get_option( $settings->get_name(), array() );
		$types    = isset( $settings['calendar_post_types'] ) && ! empty( $settings['calendar_post_types'] )
				? $settings['calendar_post_types']
				: array( 'post' );

		$escape_array = function ( $arr ) {
			$values = array_map( 'esc_sql', $arr );
			return "'" . implode( "', '", $values ) . "'";
		};

		$query = sprintf(
			"UPDATE %s SET post_status = 'draft' WHERE post_type IN (%s) AND post_status IN (%s)",
			$wpdb->posts,
			$escape_array( $types ),
			$escape_array( array( 'idea', 'assigned', 'in-progress' ) )
		);

		$wpdb->query( $query ); // phpcs:ignore
	}//end migrate_post_statuses()

	private function update_auto_sharing_fields() {

		global $wpdb;
		$query = "UPDATE {$wpdb->postmeta} SET meta_key = '_nc_exclude_from_auto_share' WHERE meta_key = '_nc_exclude_from_reshare'";
		$wpdb->query( $query ); // phpcs:ignore

		$query = "UPDATE {$wpdb->postmeta} SET meta_key = '_nc_include_in_auto_share' WHERE meta_key = '_nc_include_in_reshare'";
		$wpdb->query( $query ); // phpcs:ignore

		$settings = get_option( 'nelio-content_settings', false );
		if ( ! empty( $settings ) && isset( $settings['auto_reshare_default_mode'] ) ) {
			$settings['auto_share_default_mode'] = str_replace( 'reshare', 'auto-share', $settings['auto_reshare_default_mode'] );
			unset( $settings['auto_reshare_default_mode'] );
			update_option( 'nelio-content_settings', $settings );
		}//end if
	}//end update_auto_sharing_fields()
}//end class

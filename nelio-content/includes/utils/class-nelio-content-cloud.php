<?php
/**
 * This file contains some functions to sync WordPress with Nelio’s cloud.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class implements some functions to sync WordPress with Nelio’s cloud.
 */
class Nelio_Content_Cloud {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Cloud|null
	 */
	protected static $instance;

	/**
	 * Whether site options are updated or not.
	 *
	 * @var bool
	 */
	private $are_site_options_updated = false;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Cloud
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

		add_action( 'admin_init', array( $this, 'add_hooks_for_updating_site_in_cloud' ) );

		add_action( 'nelio_content_save_post', array( $this, 'maybe_sync_post' ) );
		add_action( 'nelio_content_update_post_in_cloud', array( $this, 'maybe_sync_post' ) );
		add_action( 'init', array( $this, 'add_hooks_for_updating_post_in_cloud_on_publish' ) );
		add_action( 'init', array( $this, 'maybe_add_profile_status_checker' ) );
	}

	/**
	 * Callback to add hooks for updating site in cloud.
	 *
	 * @return void
	 */
	public function add_hooks_for_updating_site_in_cloud() {

		add_filter( 'pre_update_option_gmt_offset', array( $this, 'on_site_option_updated' ), 10, 2 );
		add_filter( 'pre_update_option_timezone_string', array( $this, 'on_site_option_updated' ), 10, 2 );
		add_filter( 'pre_update_option_WPLANG', array( $this, 'on_site_option_updated' ), 10, 2 );
		add_filter( 'pre_update_option_home', array( $this, 'on_site_option_updated' ), 10, 2 );

		add_action( 'shutdown', array( $this, 'maybe_sync_site' ) );
	}

	/**
	 * Callback to add hooks for updating post on cloud on publish.
	 *
	 * @return void
	 */
	public function add_hooks_for_updating_post_in_cloud_on_publish() {
		$post_types = nelio_content_get_post_types( 'cloud' );
		foreach ( $post_types as $post_type ) {
			add_action( "publish_{$post_type}", array( $this, 'maybe_sync_post' ) );
		}
	}

	/**
	 * Callback to sync post with AWS.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_sync_post( $post_id ) {
		if ( nelio_content_is_staging() ) {
			return;
		}

		// If it's a revision or an autosave, do nothing.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// If it’s an auto-draft, do nothing.
		if ( 'auto-draft' === get_post_status( $post_id ) ) {
			return;
		}

		// If we don't have social profiles, do nothing.
		if ( ! get_option( 'nc_has_social_profiles' ) ) {
			return;
		}

		// If post type is not controlled by the plugin, do nothing.
		$post_types = nelio_content_get_post_types( 'cloud' );
		if ( ! in_array( get_post_type( $post_id ), $post_types, true ) ) {
			return;
		}

		// If the post hasn’t changed since last time...
		$post_helper = Nelio_Content_Post_Helper::instance();
		if ( ! $post_helper->has_relevant_changes( $post_id ) ) {
			return;
		}

		// Otherwise, synch the plugin.
		$attempts = get_post_meta( $post_id, '_nc_cloud_sync_attempts', true );
		if ( empty( $attempts ) ) {
			$attempts = 0;
		}
		++$attempts;

		$post = $post_helper->post_to_aws_json( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		$synched = $this->sync_post( $post_id, $post );
		if ( ! $synched && 3 >= $attempts ) {
			update_post_meta( $post_id, '_nc_cloud_sync_attempts', $attempts );
			wp_schedule_single_event( time() + 30, 'nelio_content_update_post_in_cloud', array( $post_id ) );
		} else {
			delete_post_meta( $post_id, '_nc_cloud_sync_attempts' );
			$post_helper->mark_post_as_synched( $post_id );
		}
	}

	/**
	 * Callback to mark when the site has been updated and requires resync.
	 *
	 * @param mixed $new_value New value.
	 * @param mixed $old_value Old value.
	 *
	 * @return mixed
	 */
	public function on_site_option_updated( $new_value, $old_value ) {
		if ( $new_value !== $old_value ) {
			$this->are_site_options_updated = true;
		}
		return $new_value;
	}

	/**
	 * Callback to sync site with AWS.
	 *
	 * @return void
	 */
	public function maybe_sync_site() {

		if ( ! $this->are_site_options_updated ) {
			return;
		}

		$body = wp_json_encode(
			array(
				'url'      => home_url(),
				'timezone' => nelio_content_get_timezone(),
				'language' => nelio_content_get_language(),
			)
		);
		assert( ! empty( $body ) );

		// NOTE. Use error_logs for logging this function or you won't see anything.
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

	/**
	 * Callback to add profile status checker.
	 *
	 * @return void
	 */
	public function maybe_add_profile_status_checker() {

		$event = 'nelio_content_check_profile_status';

		/**
		 * Whether Nelio Content should warn users when there are profiles that need to be reauthenticated.
		 *
		 * @param boolean $warn
		 *
		 * @since 2.0.7
		 */
		if ( ! apply_filters( 'nelio_content_warn_when_profile_reauth_is_required', true ) ) {
			$schedule = absint( wp_next_scheduled( $event ) );
			wp_unschedule_event( $schedule, $event );
			return;
		}

		add_action( $event, array( $this, 'check_profile_status' ) );

		$actual_recurrence   = wp_get_schedule( $event );
		$expected_recurrence = nelio_content_is_subscribed() ? 'daily' : 'weekly';
		if ( $actual_recurrence !== $expected_recurrence ) {
			$schedule = absint( wp_next_scheduled( $event ) );
			wp_unschedule_event( $schedule, $event );
			wp_schedule_event( time() + DAY_IN_SECONDS, $expected_recurrence, $event );
		}
	}

	/**
	 * Callback to check profile status.
	 *
	 * @return void
	 */
	public function check_profile_status() {

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

		$url = sprintf(
			nelio_content_get_api_url( '/site/%s/profiles/renew', 'wp' ),
			nelio_content_get_site_id()
		);

		$response = wp_remote_request( $url, $data );
		$profiles = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return;
		}

		/** @var list<TSocial_Profile> $profiles */
		$profiles = $profiles;

		$users  = array_map( fn( $p ) => $p['creatorId'], $profiles );
		$users  = array_values( array_unique( $users ) );
		$emails = array_map(
			function ( $user_id ) {
				/** @var int $user_id */

				$info = get_userdata( $user_id );
				if ( ! is_user_member_of_blog( $user_id ) || empty( $info ) ) {
					return false;
				}
				return $info->user_email;
			},
			$users
		);
		$emails = array_values( array_unique( array_filter( $emails ) ) );

		if ( empty( $emails ) ) {
			return;
		}

		$subject = sprintf(
			/* translators: %s: Blogname. */
			_x( '[%s] Action Required: Re-Authenticate Social Profiles', 'text', 'nelio-content' ),
			get_option( 'blogname' )
		);

		$message = sprintf(
			/* translators: %1$s: Website name. %2$s: Website URL . */
			_x( 'One or more social profiles in %1$s need to be re-authenticated. Please go to Nelio Content’s Settings (%2$s) and re-authenticate them.', 'user', 'nelio-content' ),
			get_option( 'blogname' ),
			admin_url( 'admin.php?page=nelio-content-settings&subpage=social--profiles' )
		);

		wp_mail( $emails, $subject, $message );
	}

	/**
	 * Syncs post with our cloud.
	 *
	 * @param int       $post_id Post ID.
	 * @param TAWS_Post $post Post.
	 *
	 * @return bool
	 */
	private function sync_post( $post_id, $post ) {
		if ( nelio_content_is_staging() ) {
			return false;
		}

		$body = wp_json_encode( $post );
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'PUT',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = sprintf(
			nelio_content_get_api_url( '/site/%s/post/%s', 'wp' ),
			nelio_content_get_site_id(),
			$post_id
		);

		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		return ! is_wp_error( $response );
	}
}

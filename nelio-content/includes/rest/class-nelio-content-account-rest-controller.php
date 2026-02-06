<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing a Nelio Content account.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Account_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Account_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Account_REST_Controller
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
	 * @since  2.0.0
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/site',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_data' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/site/free',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_free_site' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
					'args'                => array(
						'isWizardRequested' => array(
							'required'          => false,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/site/use-license',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'use_license_in_site' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
					'args'                => array(
						'license' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_valid_license',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/site/remove-license',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'remove_license_from_site' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
					'args'                => array(
						'siteId' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/subscription/upgrade',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'upgrade_subscription' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
					'args'                => array(
						'product' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/subscription',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_subscription' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/subscription/uncancel',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'uncancel_subscription' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/subscription/sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sites_using_subscription' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/subscription/invoices',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_invoices_from_subscription' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_account',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/authentication-token',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_authentication_token' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
				),
			)
		);
	}

	/**
	 * Retrieves information about the site.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_site_data() {

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

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update subscription information with response.
		/** @var TAWS_Site */
		$site_info = $response;
		$account   = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Creates a new free site in AWS and updates the info in WordPress.
	 *
	 * @param WP_REST_Request<array{isWizardRequested:boolean}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_free_site( $request ) {

		if ( nelio_content_get_site_id() ) {
			return new WP_Error(
				'site-already-exists',
				_x( 'Site already exists.', 'text', 'nelio-content' )
			);
		}

		$body = wp_json_encode(
			array(
				'url'      => home_url(),
				'timezone' => nelio_content_get_timezone(),
				'language' => nelio_content_get_language(),
			)
		);
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => $body,
		);

		$url      = nelio_content_get_api_url( '/site', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update site ID and subscription information.
		/** @var TAWS_Site */
		$site_info = $response;
		if ( ! isset( $site_info['id'] ) ) {
			return new WP_Error(
				'unable-to-process-response',
				_x( 'Response from Nelio Content’s API couldn’t be processed.', 'text', 'nelio-content' )
			);
		}

		update_option( 'nc_site_id', $site_info['id'] );
		update_option( 'nc_api_secret', $site_info['secret'] );
		if ( ! empty( $request['isWizardRequested'] ) ) {
			update_option( 'nc_wizard_requested', true );
		}

		// Update subscription information with response.
		$account = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );

		$this->notify_site_created();

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Connects a site with a subscription.
	 *
	 * @param WP_REST_Request<array{license:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function use_license_in_site( $request ) {

		$parameters = $request->get_json_params();
		/** @var string */
		$license = $parameters['license'];

		if ( nelio_content_get_site_id() ) {

			$body = wp_json_encode( array( 'license' => $license ) );
			assert( ! empty( $body ) );

			$data = array(
				'method'    => 'POST',
				'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
				'sslverify' => ! nelio_content_does_api_use_proxy(),
				'headers'   => array(
					'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
					'accept'        => 'application/json',
					'content-type'  => 'application/json',
				),
				'body'      => $body,
			);

			$url = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription', 'wp' );

		} else {

			$body = wp_json_encode(
				array(
					'url'      => home_url(),
					'timezone' => nelio_content_get_timezone(),
					'language' => nelio_content_get_language(),
					'license'  => $license,
				)
			);
			assert( ! empty( $body ) );

			$data = array(
				'method'    => 'POST',
				'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
				'sslverify' => ! nelio_content_does_api_use_proxy(),
				'headers'   => array(
					'accept'       => 'application/json',
					'content-type' => 'application/json',
				),
				'body'      => $body,
			);

			$url = nelio_content_get_api_url( '/site/subscription', 'wp' );

		}

		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update site ID and subscription information.
		/** @var TAWS_Site */
		$site_info = $response;
		if ( ! isset( $site_info['id'] ) ) {
			return new WP_Error(
				'unable-to-process-response',
				_x( 'Response from Nelio Content’s API couldn’t be processed.', 'text', 'nelio-content' )
			);
		}

		$account = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );

		// If this is a new site, let's also save the ID and the secret.
		if ( ! nelio_content_get_site_id() ) {
			update_option( 'nc_site_id', $site_info['id'] );
			update_option( 'nc_api_secret', $site_info['secret'] );
			$this->notify_site_created();
		}

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Removes the license from this site (if any).
	 *
	 * @param WP_REST_Request<array{siteId:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_license_from_site( $request ) {

		assert( is_string( $request['siteId'] ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nelio_content_get_api_url( '/site/' . $request['siteId'] . '/subscription/free', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Upgrades the subscription to a yearly subscription.
	 *
	 * @param WP_REST_Request<array{product:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function upgrade_subscription( $request ) {

		assert( is_string( $request['product'] ) );
		$body = wp_json_encode( array( 'product' => $request['product'] ) );
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

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update site ID and subscription information.
		/** @var TAWS_Site */
		$site_info = $response;
		if ( ! isset( $site_info['id'] ) ) {
			return new WP_Error(
				'unable-to-process-response',
				_x( 'Response from Nelio Content’s API couldn’t be processed.', 'text', 'nelio-content' )
			);
		}

		$account = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Cancels a subscription.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_subscription() {

		if ( ! nelio_content_get_site_id() ) {
			return new WP_Error(
				'no-site-id',
				_x( 'Subscription cannot be canceled, because there’s no account available.', 'text', 'nelio-content' )
			);
		}

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update site ID and subscription information.
		/** @var TAWS_Site */
		$site_info = $response;
		if ( ! isset( $site_info['id'] ) ) {
			return new WP_Error(
				'unable-to-process-response',
				_x( 'Response from Nelio Content’s API couldn’t be processed.', 'text', 'nelio-content' )
			);
		}

		$account = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );
		update_option( 'nc_site_id', $site_info['id'] );

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Un-cancels a subscription.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function uncancel_subscription() {

		if ( ! nelio_content_get_site_id() ) {
			return new WP_Error(
				'no-site-id',
				_x( 'Subscription cannot be reactivated, because there’s no account available.', 'text', 'nelio-content' )
			);
		}

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
			'sslverify' => ! nelio_content_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription/uncancel', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Update site ID and subscription information.
		/** @var TAWS_Site */
		$site_info = $response;
		if ( ! isset( $site_info['id'] ) ) {
			return new WP_Error(
				'unable-to-process-response',
				_x( 'Response from Nelio Content’s API couldn’t be processed.', 'text', 'nelio-content' )
			);
		}

		$account = $this->create_account_object( $site_info );
		nelio_content_update_subscription( $account['plan'], $account['limits'] );
		update_option( 'nc_site_id', $site_info['id'] );

		return new WP_REST_Response( $account, 200 );
	}

	/**
	 * Obtains all sites connected to a subscription.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_sites_using_subscription() {

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

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription/sites', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Extract the current site.
		/** @var list<TAWS_Clean_Site> */
		$sites   = $response;
		$site_id = nelio_content_get_site_id();
		$key     = array_search( $site_id, array_column( $sites, 'id' ), true );
		assert( is_int( $key ), 'This site must be in the sites list' );

		$this_site = ! empty( $sites[ $key ] ) ? $sites[ $key ] : array();
		array_splice( $sites, $key, 1 );

		// Map other sites to the appropriate object form.
		$sites = array_map(
			function ( $site ) {
				return array(
					'id'            => $site['id'],
					'url'           => $site['url'],
					'isCurrentSite' => false,
				);
			},
			$sites
		);

		// Fix this site.
		$this_site = array(
			'id'            => nelio_content_get_site_id(),
			'url'           => isset( $this_site['url'] ) ? $this_site['url'] : home_url(),
			'actualUrl'     => home_url(),
			'isCurrentSite' => true,
		);

		// Merge them all and return.
		array_unshift( $sites, $this_site );
		return new WP_REST_Response( $sites, 200 );
	}


	/**
	 * Obtains the invoices of a subscription.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_invoices_from_subscription() {

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

		$url      = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/subscription/invoices', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Regenerate the invoices result and send it to the JS.
		/** @var list<TAWS_Invoice> */
		$invoices = $response;
		$invoices = array_map(
			function ( $invoice ) {
				$time                  = strtotime( $invoice['chargeDate'] );
				$invoice['chargeDate'] = ! empty( $time )
					? gmdate( get_option( 'date_format' ), $time )
					: $invoice['chargeDate'];
				return $invoice;
			},
			$invoices
		);

		return new WP_REST_Response( $invoices, 200 );
	}

	/**
	 * Obtains the subscription products of Nelio Content.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_products() {

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

		$url      = nelio_content_get_api_url( '/fastspring/products', 'wp' );
		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Regenerate the products result and send it to the JS.
		/** @var list<TAWS_Product> */
		$products = $response;
		$products = array_map(
			function ( $product ) {
				$from = $product['upgradeableFrom'];
				if ( ! is_array( $from ) ) {
					$from = empty( $from ) ? array() : array( $from );
				}
				return array(
					'id'              => $product['product'],
					'plan'            => nelio_content_get_plan( $product['product'] ),
					'period'          => $product['pricing']['interval'],
					'displayName'     => $product['display'],
					'price'           => $product['pricing']['price'],
					'description'     => $product['description']['full'],
					'attributes'      => $product['attributes'],
					'upgradeableFrom' => $from,
				);
			},
			$products
		);

		return new WP_REST_Response( $products, 200 );
	}

	/**
	 * Gets an authentication token for the current user.
	 *
	 * @return WP_REST_Response
	 */
	public function get_authentication_token() {
		return new WP_REST_Response( nelio_content_generate_api_auth_token(), 200 );
	}

	/**
	 * This helper function creates an account object.
	 *
	 * @param TAWS_Site $site The data about the site.
	 *
	 * @return TAccount an account object.
	 *
	 * @since  2.0.0
	 */
	private function create_account_object( $site ) {

		$limits = array(
			'maxAutomationGroups'   => 1,
			'maxProfiles'           => $site['maxProfiles'] ?? -1,
			'maxProfilesPerNetwork' => $site['maxProfilesPerNetwork'] ?? 1,
		);

		if ( empty( $site['subscription']['id'] ) ) {
			return array(
				'siteId' => nelio_content_get_site_id(),
				'plan'   => 'free',
				'limits' => $limits,
			);
		}

		$sites_allowed = absint( $site['subscription']['sites'] );

		$groups_allowed                = absint( $site['subscription']['maxAutomationGroups'] ?? 0 );
		$groups_allowed                = empty( $groups_allowed ) ? 1 : $groups_allowed;
		$limits['maxAutomationGroups'] = $groups_allowed;

		$photo = get_avatar_url( $site['subscription']['account']['email'], array( 'default' => 'mysteryman' ) );
		$photo = ! empty( $photo ) ? $photo : '';

		return array(
			'creationDate'        => $site['creation'],
			'currency'            => $site['subscription']['currency'] ?? 'USD',
			'deactivationDate'    => $site['subscription']['deactivationDate'] ?? '',
			'email'               => $site['subscription']['account']['email'],
			'endDate'             => $site['subscription']['endDate'] ?? '',
			'firstname'           => $site['subscription']['account']['firstname'] ?? '',
			'isAgency'            => ! empty( $site['subscription']['isAgency'] ),
			'lastname'            => $site['subscription']['account']['lastname'] ?? '',
			'license'             => $site['subscription']['license'],
			'limits'              => $limits,
			'mode'                => $site['subscription']['mode'],
			'nextChargeDate'      => $site['subscription']['nextChargeDate'] ?? '',
			'nextChargeTotal'     => $site['subscription']['nextChargeTotal'] ?? $site['subscription']['nextChargeTotalDisplay'] ?? '',
			'period'              => $site['subscription']['intervalUnit'],
			'photo'               => $photo,
			'plan'                => nelio_content_get_plan( $site['subscription']['product'] ),
			'productId'           => $site['subscription']['product'],
			'state'               => $site['subscription']['state'],
			'sitesAllowed'        => ! empty( $sites_allowed ) ? $sites_allowed : 1,
			'siteId'              => nelio_content_get_site_id(),
			'subscription'        => $site['subscription']['id'],
			'urlToManagePayments' => nelio_content_get_api_url( '/fastspring/' . $site['subscription']['id'] . '/url', 'browser' ),
		);
	}

	/**
	 * Triggers “nelio_content_site_created” action.
	 *
	 * @return void
	 */
	private function notify_site_created() {

		/**
		 * Fires once the site has been registered in Nelio’s cloud.
		 *
		 * When fired, the site has a valid site ID and an API secret.
		 *
		 * @since 2.0.0
		 */
		do_action( 'nelio_content_site_created' );
	}
}

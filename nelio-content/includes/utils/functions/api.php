<?php
/**
 * This file contains several helper functions that deal with the AWS API.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to use Nelio’s proxy instead of accessing AWS directly or not.
 *
 * @return boolean whether to use Nelio’s proxy instead of accessing AWS directly or not.
 *
 * @since 4.0.8
 */
function nelio_content_does_api_use_proxy() {

	/**
	 * Whether the plugin should use Nelio’s proxy instead of accessing AWS directly.
	 *
	 * @param boolean $uses_proxy use Nelio’s proxy instead of accessing AWS directly. Default: `false`.
	 *
	 * @since 2.0.0
	 */
	return apply_filters( 'nelio_content_use_nelio_proxy', false );
}

/**
 * Returns the API url for the specified method.
 *
 * @param string $method  The metho we want to use.
 * @param string $context Either 'wp' or 'browser', depending on the location
 *                        in which the resulting URL has to be used.
 *                        Only wp calls might use the proxy URL.
 *
 * @return string the API url for the specified method.
 *
 * @since 4.0.8
 */
function nelio_content_get_api_url( $method, $context ) {

	if ( 'browser' === $context ) {
		return 'https://api.neliocontent.com/v2' . $method;
	}

	if ( nelio_content_does_api_use_proxy() ) {
		return 'https://neliosoftware.com/proxy/content-api/v2' . $method;
	} else {
		return 'https://api.neliocontent.com/v2' . $method;
	}
}

/**
 * Returns a new token for accessing the API.
 *
 * @param string $mode Either 'regular' or 'skip-errors'. If the latter is used, the function
 *                     won't generate any HTML errors.
 *
 * @return string a new token for accessing the API.
 *
 * @since 4.0.8
 */
function nelio_content_generate_api_auth_token( $mode = 'regular' ) {

	/** @var string */
	static $token;

	if ( ! nelio_content_get_site_id() ) {
		return '';
	}

	// If we already have a token, return it.
	if ( ! empty( $token ) ) {
		return $token;
	}

	// If we don't, let's see if there's a transient.
	$transient_name     = 'nc_api_token_' . get_current_user_id();
	$token              = get_transient( $transient_name );
	$transient_exp_date = get_option( '_transient_timeout_' . $transient_name );

	if ( ! empty( $transient_exp_date ) && ! empty( $token ) && is_string( $token ) ) {
		return $token;
	}

	// If we don't have a token, let's get a new one.
	$uid    = get_current_user_id();
	$role   = nelio_content_get_current_user_role();
	$secret = nelio_content_get_api_secret();

	$token = '';

	$body = wp_json_encode(
		array(
			'id'   => "$uid",
			'role' => $role,
			'auth' => md5( $uid . $role . $secret ),
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

	// Iterate to obtain the token, or else things will go wrong.
	$url = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id() . '/key', 'wp' );
	for ( $i = 0; $i < 3; ++$i ) {

		$response = wp_remote_request( $url, $data );
		$response = nelio_content_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			sleep( 3 );
			continue;
		}

		/** @var array{token:string, activePromos?:list<string>}|null $response */
		if ( empty( $response ) ) {
			sleep( 3 );
			continue;
		}

		// Save the new token.
		$token = $response['token'];

		// Save active promos.
		if ( isset( $response['activePromos'] ) ) {
			set_transient( 'nc_active_promos', $response['activePromos'], 5 * DAY_IN_SECONDS );
		}

		if ( ! empty( $token ) ) {
			break;
		}

		sleep( 3 );

	}

	if ( ! empty( $token ) ) {
		set_transient( $transient_name, $token, 150 * MINUTE_IN_SECONDS );
	}

	// Send error if we couldn't get an API key.
	if ( 'skip-errors' !== $mode ) {

		if ( empty( $token ) ) {

			if ( wp_doing_ajax() ) {
				header( 'HTTP/1.1 500 Internal Server Error' );
				wp_send_json( _x( 'There was an error while accessing Nelio Content’s API.', 'error', 'nelio-content' ) );
			} else {
				return '';
			}
		}
	}

	return $token;
}


/**
 * Returns the error message associated to the given code.
 *
 * @param string       $code          API error code.
 * @param string|false $default_value Optional. Default error message.
 *
 * @return string|false
 *
 * @since 4.0.8
 */
function nelio_content_get_error_message( $code, $default_value = false ) {

	switch ( $code ) {

		case 'LicenseNotFound':
			return _x( 'Invalid license code.', 'error', 'nelio-content' );

		default:
			return $default_value;

	}
}

/**
 * This function converts a remote request response into either a WP_Error
 * object (if something failed) or whatever the original response had in its body.
 *
 * @param array<string,mixed>|WP_Error $response the response of a `wp_remote_*` call.
 *
 * @return mixed|WP_Error
 *
 * @since 4.0.8
 */
function nelio_content_extract_response_body( $response ) {
	// If we couldn't open the page, let's return an empty result object.
	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'server-error',
			_x( 'Unable to access Nelio Content’s API.', 'text', 'nelio-content' )
		);
	}

	// Extract body and response.
	$body = is_string( $response['body'] ) ? $response['body'] : '{}';
	$body = json_decode( $body, true );
	$body = ! empty( $body ) ? $body : array();

	// Check if the API returned an error code and error message.
	if ( is_array( $body ) && isset( $body['errorType'] ) && isset( $body['errorMessage'] ) ) {
		$error_type    = is_string( $body['errorType'] ) && ! empty( $body['errorType'] ) ? $body['errorType'] : 'unknown-error';
		$error_message = is_string( $body['errorMessage'] ) && ! empty( $body['errorMessage'] ) ? $body['errorMessage'] : false;
		$error_message = nelio_content_get_error_message( $error_type, $error_message );
		$error_message = ! empty( $error_message ) ? $error_message : _x( 'There was an error while accessing Nelio Content’s API.', 'error', 'nelio-content' );
		return new WP_Error( $error_type, $error_message );
	}

	// If we timed out, let the user know.
	$message = is_array( $body ) ? ( $body['message'] ?? '' ) : '';
	if ( 'Endpoint request timed out' === $message ) {
		return new WP_Error( 'nelio-api-timeout', _x( 'Nelio’s API timed out', 'text', 'nelio-content' ) );
	}

	// If the error is not an Unauthorized request, let's forward it to the user.
	$response = $response['response'];
	$response = is_array( $response ) ? $response : array();

	$code    = isset( $response['code'] ) ? absint( $response['code'] ) : 0;
	$message = isset( $response['message'] ) && is_string( $response['message'] ) ? $response['message'] : '';
	$summary = "{$code} {$message}";
	if ( false === preg_match( '/^HTTP\/1.1 [0-9][0-9][0-9]( [A-Z][a-z]+)+$/', 'HTTP/1.1 ' . $summary ) ) {
		$summary = '500 Internal Server Error';
	}

	if ( 200 !== $code ) {
		return new WP_Error(
			'server-error',
			sprintf(
			/* translators: %s: The placeholder is a string explaining the error returned by the API. */
				_x( 'There was an error while accessing Nelio Content’s API: %s.', 'error', 'nelio-content' ),
				$summary
			)
		);
	}

	return $body;
}

/**
 * Returns the API secret.
 *
 * @return string the API secret.
 *
 * @since 4.0.8
 */
function nelio_content_get_api_secret() {
	return get_option( 'nc_api_secret', '' );
}

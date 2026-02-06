<?php

defined( 'ABSPATH' ) || exit;

/**
 * Notification emails.
 */
class Nelio_Content_Link_Shortener_Setting extends Nelio_Content_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'link_shortener', 'LinkShortenerSetting' );
	}

	// @Implements
	public function sanitize( $input ) {

		if ( ! isset( $input[ $this->name ] ) ) {
			return $input;
		}

		$value = $input[ $this->name ];
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );
		$value = is_array( $value ) ? $value : false;
		$body  = wp_json_encode( array( 'linkShortenerSettings' => $value ) );
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

		$url = nelio_content_get_api_url( '/site/' . nelio_content_get_site_id(), 'wp' );
		wp_remote_request( $url, $data );

		unset( $input[ $this->name ] );
		return $input;
	}
}

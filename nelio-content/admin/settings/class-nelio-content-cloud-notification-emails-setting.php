<?php

defined( 'ABSPATH' ) || exit;

/**
 * Notification emails.
 */
class Nelio_Content_Cloud_Notification_Emails_Setting extends Nelio_Content_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'cloud_notification_emails', 'CloudNotificationEmailsSetting' );
	}

	// @Implements
	public function sanitize( $input ) {

		if ( ! isset( $input[ $this->name ] ) ) {
			return $input;
		}

		$value = $input[ $this->name ];
		$value = trim( sanitize_text_field( $value ) );
		$value = explode( ',', $value );
		$value = array_filter( array_map( 'trim', $value ) );

		$body = array(
			'notificationEmails' => $value,
		);
		$body = wp_json_encode( $body );
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

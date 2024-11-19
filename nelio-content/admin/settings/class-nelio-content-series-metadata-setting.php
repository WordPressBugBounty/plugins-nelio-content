<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * Notification emails.
 */
class Nelio_Content_Series_Metadata_Setting extends Nelio_Content_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'series_metadata', 'SeriesMetadataSetting' );
	}//end __construct()

	// @Implements
	// phpcs:ignore
	public function sanitize( $input ) {

		$value = isset( $input[ $this->name ] ) ? json_decode( $input[ $this->name ], ARRAY_A ) : $this->default_value;
		$value = is_array( $value ) ? wp_parse_args( $value, $this->default_value ) : $this->default_value;

		$input[ $this->name ] = $value;
		return $input;

	}//end sanitize()

}//end class

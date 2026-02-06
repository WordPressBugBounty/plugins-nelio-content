<?php
/**
 * This file contains the setting for connecting Google Analytics with
 * Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents the setting for connecting Google Analytics with
 * Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      1.2.0
 */
class Nelio_Content_Google_Analytics_Setting extends Nelio_Content_Abstract_React_Setting {

	public function __construct() {
		parent::__construct( 'google_analytics_data', 'GoogleAnalyticsSetting' );
	}

	// @Overrides
	protected function get_field_attributes() {
		$settings = Nelio_Content_Settings::instance();
		$value    = $settings->get( 'google_analytics_data' );
		return $value;
	}

	// @Implements
	public function sanitize( $input ) {

		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = is_string( $value ) ? $value : '';
		$value = sanitize_text_field( $value );
		$value = json_decode( $value, true );
		$value = is_array( $value ) ? $value : array();
		$value = wp_parse_args(
			$value,
			array(
				'id'   => '',
				'name' => '',
			)
		);

		$input[ $this->name ] = $value;
		return $input;
	}
}

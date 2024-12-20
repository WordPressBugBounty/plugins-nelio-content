<?php
/**
 * This file contains the setting for selecting which post types can be managed
 * using Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * This class represents a the setting for selecting which post types can be
 * managed using Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      3.6.0
 */
class Nelio_Content_Post_Type_Setting extends Nelio_Content_Abstract_React_Setting {

	private $props;
	public function __construct( $props ) {
		parent::__construct( $props['name'], 'PostTypeSetting' );
		$this->props = wp_parse_args(
			$props,
			array(
				'isMandatory' => false,
				'help'        => '',
			)
		);
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	protected function get_field_attributes() {
		return array_merge(
			$this->props,
			array( 'postTypes' => $this->get_post_types() )
		);
	}//end get_field_attributes()

	// @Implements
	// phpcs:ignore
	public function sanitize( $input ) {

		$types = wp_list_pluck( $this->get_post_types(), 'value' );

		$value = isset( $input[ $this->name ] ) ? $input[ $this->name ] : '';
		$value = is_array( $value ) ? $value : sanitize_text_field( $input[ $this->name ] );
		$value = is_array( $value ) ? $value : explode( ',', $value );
		$value = array_values( array_intersect( $value, $types ) );
		if ( empty( $value ) && $this->props['isMandatory'] ) {
			$value = ! in_array( 'post', $types, true ) && ! empty( $types ) ? array( $types[0] ) : array( 'post' );
		}//end if

		$input[ $this->name ] = $value;
		return $input;
	}//end sanitize()

	private function get_post_types() {

		$default_types = array( 'post', 'page' );
		$other_types   = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			)
		);

		$types = array_values( array_unique( array_merge( $default_types, $other_types ) ) );
		$types = array_map(
			function ( $name ) {
				$type = get_post_type_object( $name );
				return array(
					'value' => $type->name,
					'label' => $type->labels->singular_name,
				);
			},
			$types
		);

		/**
		 * Filters the list of post types that can be selected in the settings screen by a user to be compatible with our plugin.
		 *
		 * Each post type is an array with two keys: `value`, which is the post type’s slug, and `label`, which is a user-friendly, translatable name for the given post type.
		 *
		 * @param array  $types list of post types that may be used with Nelio Content.
		 * @param string $name  setting name.
		 *
		 * @since 2.2.3
		 */
		return apply_filters( 'nelio_content_available_post_types_setting', $types, $this->name );
	}//end get_post_types()
}//end class

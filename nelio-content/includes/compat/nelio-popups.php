<?php
/**
 * Compatibility with Nelio Popups.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/compat
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

add_filter(
	'nelio_content_get_post_types',
	function ( $post_types ) {
		return array_filter(
			$post_types,
			function ( $type ) {
				return ! in_array( $type, array( 'nelio_popup' ), true );
			}
		);
	}
);

<?php
/**
 * Compatibility with Flamingo.
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
	'nelio_content_hidden_post_statuses',
	function ( $hidden_statuses ) {
		return array_merge( $hidden_statuses, array( 'flamingo-spam' ) );
	}
);

<?php
namespace Nelio_Content\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to remove popup post type.
 *
 * @param list<string> $post_types Post types.
 *
 * @return list<string>
 */
function remove_popup_type( $post_types ) {
	$post_types = array_filter( $post_types, fn( $t ) => 'nelio_popup' !== $t );
	return array_values( $post_types );
}
add_filter( 'nelio_content_get_post_types', __NAMESPACE__ . '\remove_popup_type' );

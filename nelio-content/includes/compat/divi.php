<?php
namespace Nelio_Content\Compat\Divi;

defined( 'ABSPATH' ) || exit;

/**
 * Adds new action.
 *
 * @param list<string> $actions Actions.
 *
 * @return list<string>
 */
function divi_enable_shortcodes_in_ajax( $actions ) {
	array_push( $actions, 'nelio_content_get_post_for_auto_sharing' );
	return $actions;
}
add_filter( 'et_builder_load_actions', __NAMESPACE__ . '\divi_enable_shortcodes_in_ajax' );

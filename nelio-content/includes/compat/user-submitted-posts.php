<?php
/**
 * Compatibility with User Submitted Posts
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/compat
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.4.0
 */

namespace Nelio_Content\Compat\User_Submitted_Posts;

defined( 'ABSPATH' ) || exit;

/**
 * Callback on before insert.
 *
 * @return void
 */
function on_before_insert() {
	if ( ! is_sync_post_enabled() ) {
		return;
	}
	remove_sync_hooks();
	add_action( 'usp_new_post', __NAMESPACE__ . '\on_new_post' );
}
add_action( 'usp_insert_before', __NAMESPACE__ . '\on_before_insert' );

/**
 * Callback on new post.
 *
 * @param array{id:int} $post Post.
 *
 * @return void
 */
function on_new_post( $post ) {
	add_sync_hooks();
	/**
	* This filter is documented in includes/utils/class-nelio-content-post-saving.php
	*/
	do_action( 'nelio_content_save_post', $post['id'], true );
}

/**
 * Removes sync hooks.
 *
 * @return void
 */
function remove_sync_hooks() {
	$hooks = get_sync_post_hooks();
	foreach ( $hooks as $hook ) {
		remove_action( $hook[0], $hook[1] );
	}
}

/**
 * Adds sync hooks.
 *
 * @return void
 */
function add_sync_hooks() {
	$hooks = get_sync_post_hooks();
	foreach ( $hooks as $hook ) {
		add_action( $hook[0], $hook[1] );
	}
}

/**
 * Whether syncing is enabled.
 *
 * @return bool
 */
function is_sync_post_enabled() {
	$cloud    = \Nelio_Content_Cloud::instance();
	$callback = array( $cloud, 'maybe_sync_post' );
	return ! empty( has_action( 'nelio_content_save_post', $callback ) );
}

/**
 * Returns sync post hooks.
 *
 * @return list<array{string,callable}>
 */
function get_sync_post_hooks() {
	$post_types = nelio_content_get_post_types( 'cloud' );

	$hooks   = array_map( fn( $post_type ) =>"publish_{$post_type}", $post_types );
	$hooks[] = 'nelio_content_save_post';

	$cloud    = \Nelio_Content_Cloud::instance();
	$callback = function () use ( &$cloud ) {
		return array( $cloud, 'maybe_sync_post' );
	};

	/** @var list<array{string,callable}> */
	return array_map(
		null,
		$hooks,
		array_map( $callback, $hooks )
	);
}

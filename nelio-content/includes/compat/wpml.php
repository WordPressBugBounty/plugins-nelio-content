<?php
namespace Nelio_Content\Compat\WPML;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WordPress.
 *
 * @return void
 */
function maybe_add_filters() {
	if ( ! function_exists( 'icl_object_id' ) ) {
		return;
	}
	add_filter( 'nelio_content_post_permalink', __NAMESPACE__ . '\use_actual_post_link', 1, 2 );
}
add_action( 'init', __NAMESPACE__ . '\maybe_add_filters' );

/**
 * Callback to use actual post link.
 *
 * @param string $permalink Permalink.
 * @param int    $post_id   Post ID.
 *
 * @return string
 */
function use_actual_post_link( $permalink, $post_id ) {

	/** @var array{auto_adjust_ids:bool} $sitepress_settings */
	global $sitepress_settings;
	$old_value = $sitepress_settings['auto_adjust_ids'];
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$sitepress_settings['auto_adjust_ids'] = false;

	$post = get_post( $post_id );
	if ( ! $post ) {
		return $permalink;
	}

	remove_filter( 'nelio_content_post_permalink', __NAMESPACE__ . '\use_actual_post_link', 1 );
	$permalink = get_permalink( $post );
	if ( 'publish' !== $post->post_status ) {
		$aux              = clone $post;
		$aux->post_status = 'publish';
		if ( empty( $aux->post_name ) ) {
			$aux->post_name = sanitize_title( $aux->post_title );
		}
		$aux->post_name = wp_unique_post_slug( $aux->post_name, $aux->ID, 'publish', $aux->post_type, $aux->post_parent );
		$permalink      = get_permalink( $aux );
	}
	add_filter( 'nelio_content_post_permalink', __NAMESPACE__ . '\use_actual_post_link', 1, 2 );

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	$sitepress_settings['auto_adjust_ids'] = $old_value;

	return $permalink;
}

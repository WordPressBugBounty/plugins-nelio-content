<?php
namespace Nelio_Content\Compat\Speed_Optimizer;

defined( 'ABSPATH' ) || exit;

add_action(
	'nelio_content_before_the_content',
	function () {
		add_filter( 'sgo_lazy_load_exclude_urls', __NAMESPACE__ . '\exclude_current_url' );
	}
);

add_action(
	'nelio_content_after_the_content',
	function () {
		remove_filter( 'sgo_lazy_load_exclude_urls', __NAMESPACE__ . '\exclude_current_url' );
	}
);

/**
 * Callback to exclude lazy loading from current url when retrieving post to
 * share on social media.
 *
 * @param array<string|int,string> $urls Urls.
 *
 * @return array<string|int,string>
 *
 * @since 4.0.6
 */
function exclude_current_url( $urls ) {
	$scheme  = is_ssl() ? 'https://' : 'http://';
	$host    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	$path = null;
	if ( $host && $request ) {
		$full_url = $scheme . $host . $request;
		$path     = wp_parse_url( $full_url, PHP_URL_PATH );
	}

	if ( $path ) {
		$urls[] = $path;
	}
	return $urls;
}

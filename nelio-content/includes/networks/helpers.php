<?php
/**
 * Network policy shared with the social editor, and server-side text validation.
 */

namespace Nelio_Content\Networks;

use Twitter\Text\Parser;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the bundled declarative network definitions used by PHP and JavaScript.
 *
 * @return array<string,TNetwork_Requirements>|WP_Error
 */
function get_network_requirements() {
	$file = __DIR__ . '/requirements.json';
	// This is bundled application data, not a user-supplied path or response.
	$contents = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $contents ) {
		return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Network requirements are unavailable.', 'text', 'nelio-content' ) );
	}
	/** @var array<string,TNetwork_Requirements>|null $decoded Bundled definitions checked by the cross-language tests. */
	$decoded = json_decode( $contents, true );
	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Network requirements are unavailable.', 'text', 'nelio-content' ) );
	}
	return $decoded;
}

/**
 * Whether the editor requires a media format in addition to a text caption.
 *
 * @param TNetwork_Requirements $rules Bundled network requirements.
 * @return bool
 */
function network_requires_media( $rules ) {
	foreach ( $rules['requires'] as $group ) {
		if ( array_intersect( $group, array( 'image', 'video', 'multi-image', 'multi-media' ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether PHP can reproduce the editor's weighted Unicode text validation.
 *
 * @return bool
 */
function is_text_validation_available() {
	return extension_loaded( 'intl' ) && extension_loaded( 'mbstring' );
}

/**
 * Counts text using the editor's URL replacement and Twitter Text v3 weighting.
 *
 * @param string $text Literal message text.
 * @return int|WP_Error
 */
function get_weighted_text_length( $text ) {
	if ( ! is_text_validation_available() ) {
		return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Server-side message validation requires the PHP intl and mbstring extensions.', 'text', 'nelio-content' ) );
	}
	// Match getMessageLength in packages/networks/text.ts, including Unicode whitespace.
	// ECMAScript whitespace excludes U+0085 and includes U+FEFF, unlike PCRE \s.
	$space = '\x{0009}-\x{000D}\x{0020}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}';
	$text  = preg_replace( '/([' . $space . '])https?:\/\/[^' . $space . ']+/u', '$1aaaabaaaabaaaabaaaabaaaab', ' ' . $text . ' ' );
	if ( null === $text ) {
		return new WP_Error( 'nelio_content_invalid_content', _x( 'Message content must be valid UTF-8 text.', 'text', 'nelio-content' ) );
	}
	$text = preg_replace( '/^[' . $space . ']+|[' . $space . ']+$/u', '', $text );
	if ( null === $text ) {
		return new WP_Error( 'nelio_content_invalid_content', _x( 'Message content must be valid UTF-8 text.', 'text', 'nelio-content' ) );
	}
	// The PHP parser normalizes CRLF; the JavaScript parser counts both code points.
	$line_feed_adjustment = substr_count( $text, "\r\n" );
	return Parser::create()->parseTweet( $text )->weightedLength + $line_feed_adjustment;
}

/**
 * Checks one image or video against the editor's shared metadata constraints.
 *
 * @param TNetwork_Media_Rules                                                          $rules Network rules for this media type.
 * @param array{mime:string,filesizeInBytes:int,duration?:float,width?:int,height?:int} $media Attachment metadata.
 * @return string Empty when valid, otherwise a stable validation code.
 */
function get_media_rule_error( $rules, $media ) {
	if ( ! in_array( $media['mime'], $rules['mimeTypes'], true ) && ! in_array( strtok( $media['mime'], '/' ), $rules['mimeTypes'], true ) ) {
		return 'unsupported_mime';
	}
	$size_kib = round( $media['filesizeInBytes'] / 1024 );
	$size_mib = round( $size_kib / 1024 );
	if ( isset( $rules['minSizeKiB'] ) && $size_kib < $rules['minSizeKiB'] ) {
		return 'file_too_small';
	}
	$max_size = $rules['maxSizeMiBByMime'][ $media['mime'] ] ?? $rules['maxSizeMiB'] ?? null;
	if ( null !== $max_size && $size_mib > $max_size ) {
		return 'file_too_large';
	}
	if ( ( isset( $rules['minDuration'] ) || isset( $rules['maxDuration'] ) ) && ! isset( $media['duration'] ) ) {
		return 'duration_unavailable';
	}
	if ( isset( $rules['minDuration'], $media['duration'] ) && $media['duration'] < $rules['minDuration'] ) {
		return 'video_too_short';
	}
	if ( isset( $rules['maxDuration'], $media['duration'] ) && $media['duration'] > $rules['maxDuration'] ) {
		return 'video_too_long';
	}
	if ( ( isset( $rules['minWidth'] ) && ! isset( $media['width'] ) ) ||
		( ( isset( $rules['minHeight'] ) || isset( $rules['maxHeight'] ) ) && ! isset( $media['height'] ) ) ||
		( isset( $rules['maxDimensionSum'] ) && ( ! isset( $media['width'] ) || ! isset( $media['height'] ) ) ) ) {
		return 'dimensions_unavailable';
	}
	if ( ( isset( $rules['minWidth'], $media['width'] ) && $media['width'] < $rules['minWidth'] ) ||
		( isset( $rules['minHeight'], $media['height'] ) && $media['height'] < $rules['minHeight'] ) ) {
		return 'dimensions_too_small';
	}
	if ( ( isset( $rules['maxHeight'], $media['height'] ) && $media['height'] > $rules['maxHeight'] ) ||
		( isset( $rules['maxDimensionSum'], $media['width'], $media['height'] ) && $media['width'] + $media['height'] > $rules['maxDimensionSum'] ) ) {
		return 'dimensions_too_large';
	}
	return '';
}

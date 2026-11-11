<?php
/**
 * Resolves social message placeholders using the editor's post projection and rules.
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Social_Message_Text {

	/**
	 * Computes a caption, retaining its permalink token for Cloud when requested.
	 *
	 * @param string      $text Original template.
	 * @param TPost|false $post Authorized post projection, with the destination permalink.
	 * @param int         $limit Network's weighted text limit.
	 * @param bool        $keep_permalink Whether Cloud should resolve the permalink later.
	 * @return string|WP_Error
	 */
	public static function compute( $text, $post, $limit, $keep_permalink = false ) {
		if ( ! $post ) {
			if ( preg_match( '/\{(?:permalink|title|author|excerpt|categories|tags|(?:taxonomy|field|custom):[^}]*)\}/u', $text ) ) {
				return new WP_Error( 'nelio_content_post_required', _x( 'Placeholders require an editable published or scheduled related post.', 'user', 'nelio-content' ) );
			}
			return self::clean( $text );
		}
		// Keep the replacement order used by computeText in packages/utils/derived-attributes.ts.
		foreach ( array(
			'permalink'  => $keep_permalink ? '{permalink}' : $post['permalink'],
			'title'      => $post['title'],
			'author'     => $post['authorName'],
			'excerpt'    => $post['excerpt'],
			'categories' => '{taxonomy:category} {taxonomy:product_cat}',
			'tags'       => '{taxonomy:post_tag} {taxonomy:product_tag}',
		) as $key => $value ) {
			$text = str_replace( '{' . $key . '}', $value, $text );
		}
		foreach ( array(
			'field'  => 'customFields',
			'custom' => 'customPlaceholders',
		) as $prefix => $property ) {
			preg_match_all( '/\{' . $prefix . ':([^}]*)\}/u', $text, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$text = str_replace( $match[0], self::trim( $post[ $property ][ $match[1] ]['value'] ?? '' ), $text );
			}
		}
		$terms = array();
		foreach ( $post['taxonomies'] as $taxonomy => $items ) {
			$terms[ $taxonomy ] = array_map( fn( $term ) => self::hashtag( $term['name'] ), $items );
		}
		$max_count = max( array_merge( array( 0 ), array_map( 'count', $terms ) ) );
		$result    = self::replace_taxonomies( $text, $terms, 0 );
		for ( $count = 1; $count <= $max_count; ++$count ) {
			$candidate = self::replace_taxonomies( $text, $terms, $count );
			// Validate against the actual destination URL even when Cloud receives a token.
			$length = \Nelio_Content\Networks\get_weighted_text_length( str_replace( '{permalink}', $post['permalink'], $candidate ) );
			if ( is_wp_error( $length ) ) {
				return $length;
			}
			if ( $length > $limit ) {
				break;
			}
			$result = $candidate;
		}
		return self::clean( $result );
	}

	/**
	 * Adds the same number of terms from each taxonomy, as in the editor.
	 *
	 * @param string                     $text Template.
	 * @param array<string,list<string>> $terms Hashtags by taxonomy.
	 * @param int                        $count Maximum terms per taxonomy.
	 * @return string
	 */
	private static function replace_taxonomies( $text, $terms, $count ) {
		foreach ( $terms as $taxonomy => $hashtags ) {
			$text = str_replace( '{taxonomy:' . $taxonomy . '}', implode( ' ', array_slice( $hashtags, 0, $count ) ), $text );
		}
		return $text;
	}

	/**
	 * Mirrors hashtagfy's PascalCase and the editor's single-word lowercase rule.
	 *
	 * @param string $name Term name.
	 * @return string
	 */
	private static function hashtag( $name ) {
		$name = preg_replace( '/(\p{Ll})(\p{Lu})/u', '$1 $2', $name ) ?? $name;
		$name = preg_replace( '/(\p{Lu})(\p{Lu}\p{Ll})/u', '$1 $2', $name ) ?? $name;
		$name = self::trim( preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $name ) ?? $name );
		$name = mb_strtolower( $name, 'UTF-8' );
		$name = preg_replace( '/ (?=[0-9])/u', '_', $name ) ?? $name;
		$name = preg_replace_callback( '/ (.)/u', fn( $m ) => mb_strtoupper( $m[1], 'UTF-8' ), $name ) ?? $name;
		$name = mb_strtoupper( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $name, 1, null, 'UTF-8' );
		preg_match_all( '/\p{Lu}/u', $name, $capitals );
		if ( count( $capitals[0] ) <= 1 ) {
			$name = mb_strtolower( mb_substr( $name, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $name, 1, null, 'UTF-8' );
		}
		return '#' . $name;
	}

	/**
	 * Removes unavailable recognized tokens and collapses spaces, preserving newlines.
	 *
	 * @param string $text Computed text.
	 * @return string
	 */
	private static function clean( $text ) {
		$text = preg_replace( '/\{(?:title|author|excerpt|categories|tags|(?:taxonomy|field|custom):[^}]*)\}/u', '', $text ) ?? $text;
		return self::trim( preg_replace( '/  +/', ' ', $text ) ?? $text );
	}

	/**
	 * Matches ECMAScript and lodash trim, including nonbreaking spaces and BOM.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function trim( $text ) {
		return preg_replace( '/^[\x{0009}-\x{000D}\x{0020}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+|[\x{0009}-\x{000D}\x{0020}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+$/u', '', $text ) ?? $text;
	}
}

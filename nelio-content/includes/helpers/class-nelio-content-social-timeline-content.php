<?php
/**
 * Server equivalent of the editor's processHtml automation input preparation.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM properties.

/** Extracts rendered content blocks and contiguous highlighted runs without a browser. */
class Nelio_Content_Social_Timeline_Content {

	const BLOCKS = array( 'p', 'h1', 'h2', 'h3', 'h4', 'li' );

	/**
	 * Prepares the same clean, highlights and top fields sent by the post editor.
	 *
	 * @param string $html Saved rendered post HTML.
	 * @return array{clean:string,highlights:list<THighlight>,top:list<string>}|WP_Error
	 */
	public function extract( $html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Timeline generation requires the PHP DOM extension.', 'text', 'nelio-content' ) );
		}
		if ( strlen( $html ) > MB_IN_BYTES ) {
			return new WP_Error( 'nelio_content_invalid_post', _x( 'Rendered post content exceeds the timeline generation limit of 1 MiB.', 'text', 'nelio-content' ) );
		}
		$dom      = new DOMDocument( '1.0', 'UTF-8' );
		$previous = libxml_use_internal_errors( true );
		try {
			$loaded = $dom->loadHTML( '<?xml encoding="UTF-8"><html><body><div>' . $html . '</div></body></html>', LIBXML_NONET );
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
		}
		$body = $dom->getElementsByTagName( 'body' )->item( 0 );
		if ( ! $loaded || ! $body ) {
			return new WP_Error( 'nelio_content_invalid_post', _x( 'Unable to parse saved post content.', 'text', 'nelio-content' ) );
		}
		$this->unwrap_nested_blocks( $body );
		$clean      = array();
		$highlights = array();
		foreach ( $body->getElementsByTagName( '*' ) as $block ) {
			if ( ! in_array( $block->tagName, self::BLOCKS, true ) ) {
				continue;
			}
			$clean[] = $this->text_content( $block );
			$run     = array();
			$this->collect_highlights( $block, false, '', $run, $highlights );
			$this->flush_highlight( $run, $highlights );
		}
		$top = array();
		foreach ( array( 'h1', 'h2', 'h3', 'strong' ) as $tag ) {
			foreach ( $body->getElementsByTagName( $tag ) as $node ) {
				$text = $this->text_content( $node );
				if ( ! in_array( $text, $top, true ) ) {
					$top[] = $text;
				}
			}
		}
		return array(
			'clean'      => implode( "\n\n", $clean ),
			'highlights' => $highlights,
			'top'        => $top,
		);
	}

	/**
	 * Unwraps nested blocks and nested highlights in document order like the editor.
	 *
	 * @param DOMElement $body Root.
	 * @return void
	 */
	private function unwrap_nested_blocks( $body ) {
		do {
			$nested = null;
			foreach ( $body->getElementsByTagName( '*' ) as $node ) {
				$parent = $node->parentNode;
				while ( $parent && $parent !== $body ) {
					if ( $parent instanceof DOMElement && ( ( in_array( $node->tagName, self::BLOCKS, true ) && in_array( $parent->tagName, self::BLOCKS, true ) ) || ( 'ncshare' === $node->tagName && 'ncshare' === $parent->tagName ) ) ) {
						$nested = $node;
						break 2;
					}
					$parent = $parent->parentNode;
				}
			}
			if ( $nested && $nested->parentNode ) {
				$parent = $nested->parentNode;
				while ( $nested->firstChild ) {
					$parent->insertBefore( $nested->firstChild, $nested );
				}
				$parent->removeChild( $nested );
			}
		} while ( $nested );
	}

	/**
	 * Preserves link boundaries and merges adjacent highlighted formatting runs.
	 *
	 * @param DOMNode                              $node Node.
	 * @param bool                                 $highlighted Whether an ancestor marks a highlight.
	 * @param string                               $href Current link.
	 * @param list<array{href:string,text:string}> $run Current highlight segments.
	 * @param list<THighlight>                     $highlights Completed highlights.
	 * @return void
	 */
	private function collect_highlights( $node, $highlighted, $href, &$run, &$highlights ) {
		if ( $node instanceof DOMElement ) {
			$highlighted = $highlighted || 'ncshare' === $node->tagName;
			$href        = 'a' === $node->tagName ? $node->getAttribute( 'href' ) : $href;
			if ( 'br' === $node->tagName ) {
				$this->append_text( "\n", $highlighted, $href, $run, $highlights );
				return;
			}
		}
		if ( $node instanceof DOMText ) {
			$this->append_text( $node->data, $highlighted, $href, $run, $highlights );
			return;
		}
		foreach ( $node->childNodes as $child ) {
			$this->collect_highlights( $child, $highlighted, $href, $run, $highlights );
		}
	}

	/**
	 * Accumulates a highlighted segment or closes the current run.
	 *
	 * @param string                               $text Text.
	 * @param bool                                 $highlighted Whether it is highlighted.
	 * @param string                               $href Current link.
	 * @param list<array{href:string,text:string}> $run Current segments.
	 * @param list<THighlight>                     $highlights Completed highlights.
	 * @return void
	 */
	private function append_text( $text, $highlighted, $href, &$run, &$highlights ) {
		if ( '' === $text ) {
			return;
		}
		if ( ! $highlighted ) {
			$this->flush_highlight( $run, $highlights );
			return;
		}
		$last = count( $run ) - 1;
		if ( $last >= 0 && $run[ $last ]['href'] === $href ) {
			$run[ $last ]['text'] .= $text;
		} else {
			$run[] = array(
				'href' => $href,
				'text' => $text,
			);
		}
	}

	/**
	 * Serializes a run with links only, matching the editor's literal HTML/text pair.
	 *
	 * @param list<array{href:string,text:string}> $run Current segments.
	 * @param list<THighlight>                     $highlights Completed highlights.
	 * @return void
	 */
	private function flush_highlight( &$run, &$highlights ) {
		$html = '';
		foreach ( $run as $segment ) {
			$text  = htmlspecialchars( $segment['text'], ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' );
			$text  = str_replace( array( "\n", '&gt;' ), array( '<br>', '>' ), $text );
			$html .= '' === $segment['href'] ? $text : '<a href="' . htmlspecialchars( $segment['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) . '">' . $text . '</a>';
		}
		if ( '' !== $html ) {
			$highlight = array(
				'html' => $html,
				'text' => preg_replace( '/<[^>]*>/', '', $html ) ?? '',
			);
			if ( ! in_array( $highlight, $highlights, true ) ) {
				$highlights[] = $highlight;
			}
		}
		$run = array();
	}

	/**
	 * Matches JavaScript whitespace normalization, including non-breaking spaces.
	 *
	 * @param DOMNode $node Node.
	 * @return string
	 */
	private function text_content( $node ) {
		return preg_replace( '/[\x{0009}-\x{000d}\x{0020}\x{00a0}\x{1680}\x{2000}-\x{200a}\x{2028}\x{2029}\x{202f}\x{205f}\x{3000}\x{feff}]+/u', ' ', $node->textContent ?? '' ) ?? '';
	}
}

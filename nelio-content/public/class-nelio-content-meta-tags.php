<?php
/**
 * Adds Open Graph and Twitter meta tags.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/public
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.1.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds Open Graph and Twitter meta tags.
 */
class Nelio_Content_Meta_Tags {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Meta_Tags|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Meta_Tags
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'maybe_add_meta_tags' ) );
	}

	/**
	 * Callback to add meta tags.
	 *
	 * @return void
	 */
	public function maybe_add_meta_tags() {
		$settings = Nelio_Content_Settings::instance();
		if ( ! $settings->get( 'are_meta_tags_active' ) ) {
			return;
		}

		// See https://developers.facebook.com/docs/sharing/webmasters#markup link.
		$image = $this->get_og_image();

		$open_graph = array(
			'og:locale'       => get_locale(),
			'og:type'         => $this->get_og_type(),
			'og:title'        => $this->get_og_title(),
			'og:description'  => $this->get_og_desc(),
			'og:url'          => $this->get_og_url(),
			'og:site_name'    => get_bloginfo( 'name' ),
			'og:image'        => $image ? $image['url'] : false,
			'og:image:width'  => $image ? $image['width'] : false,
			'og:image:height' => $image ? $image['height'] : false,
		);

		// See https://developer.x.com/en/docs/x-for-websites/cards/overview/markup link.
		$twitter = array(
			'twitter:card'    => 'summary_large_image',
			'twitter:creator' => false,
			'twitter:site'    => false,
			'twitter:image'   => $image ? $image['url'] : false,
		);

		$metas = array_merge( $open_graph, $twitter );
		foreach ( $metas as $key => $value ) {
			/**
			 * Filters the given meta tag. If `false`, the tag won't be printed.
			 *
			 * @param string|int|false $value value of the meta tag.
			 * @param string       $key   the tag we're filtering.
			 *
			 * @since 2.1.2
			 */
			$metas[ $key ] = apply_filters( 'nelio_content_meta_tag', $value, $key );

			/**
			 * Filters the given meta tag. If `false`, the tag won't be printed.
			 *
			 * @param string|int|false $value value of the meta tag.
			 *
			 * @since 2.1.2
			 */
			$metas[ $key ] = apply_filters( "nelio_content_{$key}_meta_tag", $value );
			if ( false === $metas[ $key ] ) {
				unset( $metas[ $key ] );
				continue;
			}

			$metas[ $key ] = wp_strip_all_tags( (string) $metas[ $key ] );
		}

		echo "\n\n\t<!-- Nelio Content -->";
		foreach ( $metas as $key => $value ) {
			$attr = 0 === strpos( $key, 'twitter' ) ? 'name' : 'property';
			printf(
				"\n\t<meta %s=\"%s\" content=\"%s\" />",
				esc_attr( $attr ),
				esc_attr( $key ),
				esc_attr( "$value" )
			);
		}
		echo "\n\t<!-- /Nelio Content -->\n\n";
	}

	/**
	 * Returns OG image or `false` if none is found.
	 *
	 * @return array{url:string,width:int,height:int}|false
	 */
	private function get_og_image() {
		if ( ! is_singular() ) {
			return false;
		}

		$thumb_id = absint( get_post_thumbnail_id() );
		$thumb    = wp_get_attachment_image_src( $thumb_id, 'full' );
		if ( empty( $thumb ) ) {
			return false;
		}

		return array(
			'url'    => $thumb[0],
			'width'  => $thumb[1],
			'height' => $thumb[2],
		);
	}

	/**
	 * Returns OG type.
	 *
	 * @return 'website'|'profile'|'article'
	 */
	private function get_og_type() {
		if ( is_front_page() ) {
			return 'website';
		} elseif ( is_author() ) {
			return 'profile';
		} else {
			return 'article';
		}
	}

	/**
	 * Returns OG title.
	 *
	 * @return string
	 */
	private function get_og_title() {
		return get_the_title();
	}

	/**
	 * Returns OG description.
	 *
	 * @return string
	 */
	private function get_og_desc() {
		$more = function () {
			return '…';
		};
		add_filter( 'excerpt_more', $more );
		$excerpt = is_singular() ? get_the_excerpt() : '';
		remove_filter( 'excerpt_more', $more );
		return $excerpt;
	}

	/**
	 * Returns OG URL.
	 *
	 * @return string
	 */
	private function get_og_url() {
		/** @var WP $wp */
		global $wp;
		return trailingslashit( home_url( add_query_arg( array(), $wp->request ) ) );
	}
}

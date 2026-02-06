<?php
/**
 * This file contains the class that defines REST API endpoints for
 * retrieving meta information of shared links.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_Content_Shared_Link_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Shared_Link_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Shared_Link_REST_Controller
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelio_content()->rest_namespace,
			'/shared-link',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_shared_link_meta_data' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
			)
		);
	}

	/**
	 * Gets meta data from the given URL.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_shared_link_meta_data( $request ) {

		/** @var string */
		$url = $request->get_param( 'url' );

		// If the URL is empty, return.
		if ( empty( $url ) ) {
			return new WP_Error(
				'empty-url',
				_x( 'URL is empty.', 'text', 'nelio-content' )
			);
		}

		// Let's obtain the contents of the URL.
		$aux = $this->get_page_content( $url );

		// If we couldn't load the page content, return.
		if ( empty( $aux ) ) {
			return new WP_Error(
				'internal-error',
				sprintf(
					/* translators: %s: URL. */
					_x( 'Unable to load URL “%s”.', 'text', 'nelio-content' ),
					$url
				)
			);
		}

		$page = $aux['content'];
		$page = preg_replace( '/\n/', '', $page );

		// If the response code is an error, return.
		if ( in_array( absint( $aux['responseCode'] ), array( 403, 404, 500 ), true ) ) {
			return new WP_Error(
				'internal-error',
				sprintf(
					/* translators: %s: URL. */
					_x( 'Unable to load URL “%s”.', 'text', 'nelio-content' ),
					$url
				)
			);
		}

		// If we couldn't load the page content, return.
		if ( empty( $page ) ) {
			return new WP_Error(
				'internal-error',
				sprintf(
					/* translators: %s: URL. */
					_x( 'Unable to load URL “%s”.', 'text', 'nelio-content' ),
					$url
				)
			);
		}

		$meta_tags = $this->extract_metadata( $page, $url );

		// Let's populate the results object.
		$result = array(
			'responseCode' => '' . absint( $aux['responseCode'] ),
			'author'       => $meta_tags['author'] ?? $meta_tags['nelio-content:author'] ?? '',
			'date'         => $meta_tags['article:published_time'] ?? '',
			'domain'       => '',
			'email'        => '',
			'excerpt'      => $meta_tags['og:description'] ?? $meta_tags['description'] ?? $meta_tags['twitter:description'] ?? '',
			'image'        => $meta_tags['og:image'] ?? '',
			'permalink'    => $meta_tags['og:url'] ?? $meta_tags['nelio-content:url'],
			'title'        => $meta_tags['og:title'] ?? $meta_tags['nelio-content:title'] ?? $meta_tags['twitter:title'] ?? '',
			'twitter'      => $meta_tags['twitter:creator'] ?? '',
		);

		// Fix Domain.
		$result['domain'] = preg_replace( '/^https?:\/\/([^\/]+).*$/', '$1', $result['permalink'] );
		$result['domain'] = is_string( $result['domain'] ) ? $result['domain'] : '';

		// Fix Twitter.
		$result['twitter'] = preg_replace( '/@|https?:\/\/twitter.com\/?/', '', $result['twitter'] );
		$result['twitter'] = is_string( $result['twitter'] ) ? $result['twitter'] : '';
		if ( mb_strlen( $result['twitter'] ) ) {
			$result['twitter'] = '@' . $result['twitter'];
		}

		// Fix Author.
		if ( empty( $result['author'] ) && ! empty( $result['twitter'] ) ) {
			$result['author'] = $this->get_author_from_twitter( $result['twitter'] );
		}

		// Strip all HTML tags.
		foreach ( $result as $key => $value ) {
			$result[ $key ] = wp_strip_all_tags( $value );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Gets pages content.
	 *
	 * @param string $url URL.
	 *
	 * @return array{responseCode:int,content:string}|false
	 */
	private function get_page_content( $url ) {

		$result = array(
			'responseCode' => 0,
			'content'      => '',
		);

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36',
			),
		);

		add_filter( 'https_ssl_verify', '__return_false' );
		$response = @wp_safe_remote_request( $url, $args ); // phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
		remove_filter( 'https_ssl_verify', '__return_false' );

		// If we couldn't open the page, let's return an empty result object.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// If the response code is an error, return it.
		$result['responseCode'] = absint( $response['response']['code'] );
		if ( in_array( $result['responseCode'], array( 403, 404, 500 ), true ) ) {
			return $result;
		}

		$page = $response['body'];

		// Fix the page encoding (if necessary).
		if ( isset( $response['headers']['content-type'] ) ) {

			$content_type = $response['headers']['content-type'];

			if ( preg_match( '/charset=([a-zA-Z0-9-]+)/i', $content_type, $matches ) ) {

				$charset = $matches[1];
				if ( stripos( $charset, 'utf' ) !== 0 ) {
					$page = mb_convert_encoding( $page, 'UTF-8', $charset );
				}
			}
		}

		$result['content'] = is_string( $page ) ? $page : '';
		return $result;
	}

	/**
	 * Extracts meta data from URL.
	 *
	 * @param string $page HTML content.
	 * @param string $url  URL.
	 *
	 * @return array<string,string>
	 */
	private function extract_metadata( $page, $url ) {

		// First, we add the URL of the request.
		$meta_tags                      = array();
		$meta_tags['nelio-content:url'] = $url;

		// Then, we obtain the title tag.
		if ( preg_match( '/<title>([^<]*)<\/title>/i', $page, $matches ) ) {
			$meta_tags['nelio-content:title'] = wp_strip_all_tags( $matches[1] );
		}

		// Next, we try to discover who the author is.
		$meta_tags['nelio-content:author'] = $this->get_author( $page );

		// Finally, we look for all meta tags. First, property/name and content.
		if ( preg_match_all( '/<meta\s+(property|name)="([^"]*)"\s+content="([^"]*)"[^>]*>/i', $page, $matches ) ) {

			$count = count( $matches[0] );
			for ( $i = 0; $i < $count; ++$i ) {
				$key               = strtolower( $matches[2][ $i ] );
				$meta_tags[ $key ] = $matches[3][ $i ];
			}
		}

		// Then, content and property/name.
		if ( preg_match_all( '/<meta\s+content="([^"]*)"\s+(property|name)="([^"]*)"[^>]*>/i', $page, $matches ) ) {

			$count = count( $matches[0] );
			for ( $i = 0; $i < $count; ++$i ) {
				$key               = strtolower( $matches[3][ $i ] );
				$meta_tags[ $key ] = $matches[1][ $i ];
			}
		}

		return $meta_tags;
	}

	/**
	 * Extracts the author from the page.
	 *
	 * @param string $page HTML content.
	 *
	 * @return string
	 */
	private function get_author( $page ) {

		// First of all, we look for the `vcard author` name.
		if ( preg_match( '/(\bvcard\b[^"]+\bauthor\b|\bauthor\b[^"]+\bvcard\b).{0,200}\bfn\b(.{30,200})/i', $page, $matches ) ) {
			if ( preg_match( '/>([^<]{3,40})</i', $matches[2], $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}
		}

		// Then, we try to look for a schema.org or data-vocabulary.org author name.
		if ( preg_match( '/https?:\/\/(data-vocabulary|schema).org\/Person.{0,200}\bname\b(.{3,200})/i', $page, $matches ) ) {

			$match = $matches[2];
			if ( preg_match( '/>([^<]{3,40})</i', $match, $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}

			if ( preg_match( '/content="([^"]{3,40})"/', $match, $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}
		}

		// Next, we try to discover the author using WordPress' default class name.
		if ( preg_match( '/\bauthor-name\b(.{3,200})/i', $page, $matches ) ) {
			if ( preg_match( '/>([^<]{3,40})</i', $matches[1], $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}
		}

		// Next, we look for the "attributionNameClick" property.
		if ( preg_match( '/\battributionNameClick\b(.{3,150})/i', $page, $matches ) ) {
			if ( preg_match( '/>([^<]{3,40})</i', $matches[1], $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}
		}

		// Finally, we try to discover the author looking at a "rel author" link.
		if ( preg_match( '/<a.{0,200}rel=.author.(.{3,200})/i', $page, $matches ) ) {
			if ( preg_match( '/>([^<]{3,40})</i', $matches[1], $matches ) ) {
				$author = trim( $matches[1] );
				if ( ! empty( $author ) ) {
					return $author;
				}
			}
		}

		// If everything failed, let's return the empty string.
		return '';
	}

	/**
	 * Gets author from Twitter’s username.
	 *
	 * @param string $username Twitter’s username.
	 *
	 * @return string
	 */
	private function get_author_from_twitter( $username ) {

		// Result variable.
		$author = '';

		// Get $username's twitter profile page.
		$username = str_replace( '@', '', $username );
		$aux      = $this->get_page_content( 'https://twitter.com/' . $username );

		// If we couldn't load the page content, return.
		if ( empty( $aux ) ) {
			return $author;
		}

		// If the response code is an error, return.
		if ( in_array( $aux['responseCode'], array( 403, 404, 500 ), true ) ) {
			return $author;
		}

		// If we were able to load the page, let's loook for the author's name in
		// there.
		$page = $aux['content'];

		if ( preg_match( '/data-screen-name="' . $username . '".+data-name="([^"]+)"/i', $page, $matches ) ) {
			$author = trim( $matches[1] );
		}

		if ( empty( $author ) ) {
			if ( preg_match( '/<title>([^<]*)<\/title>/i', $page, $matches ) ) {
				$author = trim( wp_strip_all_tags( $matches[1] ) );
			}
		}

		return $author;
	}
}

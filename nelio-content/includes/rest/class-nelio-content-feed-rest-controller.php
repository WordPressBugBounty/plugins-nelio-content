<?php
/**
 * This file contains REST endpoints to work with feeds.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Feed_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Feed_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Feed_REST_Controller
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
			'/feeds',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_feeds' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_feed' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_feed' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'id'      => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
						'name'    => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => flow( 'trim', 'nelio_content_is_not_empty' ),
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'twitter' => array(
							'required'          => false,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_twitter_handle',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_feed' ),
					'permission_callback' => 'nelio_content_can_current_user_manage_plugin',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => 'nelio_content_is_url',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/feeds/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_feed_items' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => flow( 'trim', 'nelio_content_is_not_empty' ),
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Returns the feed list.
	 *
	 * @return WP_REST_Response
	 */
	public function get_feeds() {
		return new WP_REST_Response( get_option( 'nc_feeds', array() ), 200 );
	}

	/**
	 * Creates a new feed.
	 *
	 * @param WP_REST_Request<array{url:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_feed( $request ) {

		if ( ! defined( 'WPINC' ) || ! is_string( WPINC ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing feeds.', 'text', 'nelio-content' )
			);
		}

		nelio_content_require_wp_file( WPINC . '/feed.php' );

		/** @var string */
		$feed_url = $request['url'];
		$rss      = fetch_feed( $feed_url );

		$subscribe_url = ! is_wp_error( $rss ) ? $rss->subscribe_url() : null;
		if ( is_wp_error( $rss ) || empty( $subscribe_url ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing feeds.', 'text', 'nelio-content' )
			);
		}

		$feed = array(
			'id'      => $subscribe_url,
			'name'    => $rss->get_title() ?? $subscribe_url,
			'url'     => $rss->get_permalink() ?? $subscribe_url,
			'feed'    => $subscribe_url,
			'icon'    => $rss->get_image_url() ?? '',
			'twitter' => '',
		);

		$this->save_new_feed( $feed );
		return new WP_REST_Response( $feed, 200 );
	}

	/**
	 * Renames the given feed.
	 *
	 * @param WP_REST_Request<array{id:string,name:string,twitter?:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_feed( $request ) {

		/** @var string */
		$feed_id = $request['id'];
		/** @var string */
		$name = trim( $request['name'] ?? '' );
		/** @var string */
		$twitter = trim( $request['twitter'] ?? '' );

		$feed = $this->get_feed( $feed_id );
		if ( empty( $feed ) ) {
			return new WP_Error(
				'feed-not-found',
				_x( 'Feed not found.', 'text', 'nelio-content' )
			);
		}

		$feeds = get_option( 'nc_feeds', array() );
		/** @disregard P1006 — $feeds is an array */
		foreach ( $feeds as &$feed ) {
			if ( $feed['id'] === $feed_id ) {
				$feed['name']    = $name;
				$feed['twitter'] = $twitter;
			}
		}
		update_option( 'nc_feeds', $feeds );

		return new WP_REST_Response( $this->get_feed( $feed_id ), 200 );
	}

	/**
	 * Removes the given feed.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function remove_feed( $request ) {

		/** @var string */
		$feed_id = $request['id'];
		$feed    = $this->get_feed( $feed_id );
		if ( empty( $feed ) ) {
			return new WP_REST_Response( true, 200 );
		}

		$feeds = get_option( 'nc_feeds', array() );
		/** @disregard P1006 — $feeds is an array */
		$feeds = array_filter(
			$feeds,
			function ( $feed ) use ( $feed_id ) {
				return $feed['id'] !== $feed_id;
			}
		);
		update_option( 'nc_feeds', array_values( $feeds ) );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Returns all the items in the given feed.
	 *
	 * @param WP_REST_Request<array{id:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_feed_items( $request ) {

		/** @var string */
		$feed_id = $request['id'];
		$feed    = $this->get_feed( $feed_id );
		if ( empty( $feed ) ) {
			return new WP_Error(
				'feed-not-found',
				_x( 'Feed not found.', 'text', 'nelio-content' )
			);
		}

		if ( ! defined( 'WPINC' ) || ! is_string( WPINC ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing feeds.', 'text', 'nelio-content' )
			);
		}
		nelio_content_require_wp_file( WPINC . '/feed.php' );

		$rss = fetch_feed( $feed['feed'] );
		if ( is_wp_error( $rss ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Error while processing feeds.', 'text', 'nelio-content' )
			);
		}

		/** @var list<SimplePie\Item>|null $rss_items */
		$rss_items = $rss->get_items( 0, 10 );
		$result    = array_map(
			function ( $item ) use ( $feed_id ) {
				return array(
					'id'        => $item->get_permalink(),
					'authors'   => $this->prepare_authors( $item->get_authors() ),
					'excerpt'   => wp_strip_all_tags( $item->get_description() ?? '' ),
					'date'      => $item->get_date( 'c' ),
					'feedId'    => $feed_id,
					'permalink' => $item->get_permalink(),
					'title'     => $item->get_title(),
				);
			},
			$rss_items ?? array()
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Returns the list of author names.
	 *
	 * @param array<SimplePie\Author>|null $authors Authors.
	 *
	 * @return list<string>
	 */
	private function prepare_authors( $authors ) {
		if ( empty( $authors ) ) {
			return array();
		}
		$names = array_map( fn ( $a ) => $a->get_name(), $authors );
		return array_values( array_filter( $names ) );
	}

	/**
	 * Adds the feed to the list of saved feeds.
	 *
	 * @param TFeed $feed Feed.
	 *
	 * @return void
	 */
	private function save_new_feed( $feed ) {
		$old_feed = $this->get_feed( $feed['id'] );
		if ( ! empty( $old_feed ) ) {
			return;
		}

		$feeds = get_option( 'nc_feeds', array() );
		/** @disregard P1006 — $feeds is an array */
		array_push( $feeds, $feed );
		update_option( 'nc_feeds', $feeds );
	}

	/**
	 * Gets the requested feed.
	 *
	 * @param string $id Feed ID.
	 *
	 * @return TFeed|false
	 */
	private function get_feed( $id ) {
		$feeds = get_option( 'nc_feeds', array() );
		/** @disregard P1006 — $feeds is an array */
		foreach ( $feeds as $feed ) {
			if ( $feed['id'] === $id ) {
				return $feed;
			}
		}
		return false;
	}
}

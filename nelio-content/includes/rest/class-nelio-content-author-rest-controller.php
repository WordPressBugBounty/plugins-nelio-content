<?php
/**
 * This file contains the class that defines REST API endpoints for
 * retrieving authors.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Author_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Author_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Author_REST_Controller
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
			'/author/batch',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_authors' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'ids' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => array( $this, 'sanitize_ids' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/author/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_authors' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'query'    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => flow( 'sanitize_text_field', 'trim' ),
						),
						'page'     => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Retrieves the specified authors.
	 *
	 * @param WP_REST_Request<array{ids:list<int>}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_authors( $request ) {

		/** @var list<int> */
		$author_ids = $request->get_param( 'ids' );

		$authors = array_map(
			fn( $author_id ) => $this->json( get_userdata( $author_id ) ),
			$author_ids
		);
		$authors = array_values( array_filter( $authors ) );

		return new WP_REST_Response( $authors, 200 );
	}

	/**
	 * Search authors.
	 *
	 * @param WP_REST_Request<array<mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function search_authors( $request ) {

		/** @var string */
		$query = $request->get_param( 'query' );
		/** @var int */
		$per_page = $request->get_param( 'per_page' );
		/** @var int */
		$page = $request->get_param( 'page' );

		// Search query.
		$args = array(
			/**
			 * Filters the authors capabilities.
			 *
			 * @param list<string> $capabilities Array of capabilities
			 *
			 * @since 3.7.2
			 */
			'capability' => apply_filters( 'nelio_content_author_capabilities', array( 'edit_posts' ) ),
			'number'     => $per_page,
			'order'      => 'ASC',
			'orderby'    => 'display_name',
			'paged'      => $page,
			'search'     => "*{$query}*",
		);

		if ( empty( $query ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$args['exclude'] = $this->get_priority_authors();
		}

		$wp_user_query = new WP_User_Query( $args );
		/** @var list<object{data:WP_User}> */
		$authors = $wp_user_query->get_results();
		$authors = array_map( fn( $user ) => $this->json( $user->data ), $authors );
		$authors = array_values( array_filter( $authors ) );

		if ( empty( $query ) ) {
			if ( 1 === $page ) {
				$authors = $this->add_priority_authors( $authors );
			}
		}

		// Build result object, ready for pagination.
		$max_num_pages = ceil( $wp_user_query->get_total() / $per_page );
		$result        = array(
			'results'    => $authors,
			'pagination' => array(
				'more'  => $page < $max_num_pages,
				'pages' => $max_num_pages,
			),
		);

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Adds priority authors.
	 *
	 * @param list<TAuthor> $authors Authors.
	 *
	 * @return list<TAuthor>
	 */
	private function add_priority_authors( $authors ) {

		$priority_authors = array_map(
			function ( $user_id ) {
				return $this->json( get_userdata( $user_id ) );
			},
			$this->get_priority_authors()
		);

		return array_merge(
			array_filter( $priority_authors, fn( $a ) => ! empty( $a['id'] ) ),
			$authors
		);
	}

	/**
	 * Returns the list of priority authors.
	 *
	 * @return list<int>
	 */
	private function get_priority_authors() {
		/**
		 * Returns a list of author IDs.
		 *
		 * These authors will be the first results returned by an empty search.
		 *
		 * @param list<int> $author_ids list of author IDs.
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'nelio_content_get_priority_authors', array() );
	}

	/**
	 * Sanitizes a comma-separated list of IDs.
	 *
	 * @param mixed $ids Comma-separated list of IDs.
	 *
	 * @return list<int>
	 */
	public function sanitize_ids( $ids ) {

		$ids = is_string( $ids ) ? explode( ',', $ids ) : array();
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );
		$ids = array_unique( $ids );

		return array_values( $ids );
	}

	/**
	 * Private function to convert user to json.
	 *
	 * @param WP_User|false $author Author.
	 *
	 * @return TAuthor|false
	 */
	private function json( $author ) {

		if ( empty( $author ) ) {
			return false;
		}

		$author_id = absint( $author->ID );

		$photo = get_avatar_url(
			$author->user_email,
			array(
				'size'    => 60,
				'default' => 'blank',
			)
		);
		$photo = is_string( $photo ) ? $photo : '';
		return array(
			'id'      => $author_id,
			'isAdmin' => user_can( $author_id, 'manage_options' ),
			'email'   => $this->mask_email( $author->user_email ),
			'name'    => $author->display_name,
			'photo'   => $photo,
		);
	}

	/**
	 * Helper function to mask email.
	 *
	 * @param string $email Email.
	 *
	 * @return string
	 */
	private function mask_email( $email ) {

		$domain    = strrchr( $email, '@' );
		$domain    = is_string( $domain ) ? $domain : '';
		$extension = strrchr( $domain, '.' );
		$extension = is_string( $extension ) ? $extension : '';
		$mailname  = str_replace( $domain, '', $email );

		$domain = str_replace( $extension, '', $domain );

		$domain    = substr( $domain, 1 );
		$extension = substr( $extension, 1 );

		if ( strlen( $mailname ) < 5 ) {
			$mailname = '***';
		} else {
			$mailname = substr( $mailname, 0, 3 ) . '***';
		}

		if ( strlen( $domain ) < 5 ) {
			$domain = '***';
		} else {
			$domain = substr( $domain, 0, 3 ) . '***';
		}

		return "$mailname@$domain$extension";
	}
}

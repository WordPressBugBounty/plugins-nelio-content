<?php
/**
 * This file contains the class that defines REST API endpoints for
 * working with posts managed by Nelio Content.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_Content\Helpers\flow;

class Nelio_Content_Post_REST_Controller extends WP_REST_Controller {

	/**
	 * This instance.
	 *
	 * @since  2.0.0
	 * @var    Nelio_Content_Post_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Post_REST_Controller
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
			'/calendar/posts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_posts_in_date_range' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'from' => array(
							'required'          => true,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'to'   => array(
							'required'          => true,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'type' => array(
							'required'          => true,
							'type'              => 'date',
							'sanitize_callback' => flow(
								'sanitize_text_field',
								'trim',
								fn( $v ) => explode( ',', is_string( $v ) ? $v : '' )
							),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_posts' ),
					'permission_callback' => 'nelio_content_can_current_user_use_plugin',
					'args'                => array(
						'per_page' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'page'     => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'type'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => flow(
								'sanitize_text_field',
								'trim',
								fn( $v ) => explode( ',', is_string( $v ) ? $v : '' )
							),
						),
						'status'   => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => flow(
								'sanitize_text_field',
								'trim',
								fn( $v ) => empty( $v ) ? 'publish' : $v,
								fn( $v ) => explode( ',', is_string( $v ) ? $v : '' )
							),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_create_post' ),
					'args'                => array(
						'authorId'   => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'title'      => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_not_empty',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'dateValue'  => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'timeValue'  => array(
							'required'          => false,
							'type'              => 'time',
							'validate_callback' => 'nelio_content_is_time',
						),
						'type'       => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => array( $this, 'is_valid_post_type' ),
						),
						'status'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'taxonomies' => array(
							'required'          => false,
							'type'              => 'record<taxonomy name, term with id[]>',
							'sanitize_callback' => array( $this, 'sanitize_taxonomies' ),
						),
						'references' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => nelio_content_is_array( 'nelio_content_is_url' ),
						),
						'series'     => array(
							'required'          => false,
							'type'              => 'array<record<id, part>>',
							'sanitize_callback' => array( $this, 'sanitize_series' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_view_post' ),
					'args'                => array(
						'id'  => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'aws' => array(
							'required'          => false,
							'type'              => 'flag (true iff present)',
							'sanitize_callback' => '__return_true',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_edit_post' ),
					'args'                => array(
						'id'         => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'authorId'   => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'title'      => array(
							'required'          => true,
							'type'              => 'string',
							'validate_callback' => 'nelio_content_is_not_empty',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'dateValue'  => array(
							'required'          => false,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'timeValue'  => array(
							'required'          => false,
							'type'              => 'time',
							'validate_callback' => 'nelio_content_is_time',
						),
						'status'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'taxonomies' => array(
							'required'          => false,
							'type'              => 'record<taxonomy name, term with id[]>',
							'sanitize_callback' => array( $this, 'sanitize_taxonomies' ),
						),
						'references' => array(
							'required'          => true,
							'type'              => 'URL',
							'validate_callback' => nelio_content_is_array( 'nelio_content_is_url' ),
						),
						'series'     => array(
							'required'          => false,
							'type'              => 'array<record<id, part>>',
							'sanitize_callback' => array( $this, 'sanitize_series' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/items',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_post_items' ),
					'permission_callback' => array( $this, 'check_if_user_can_edit_post' ),
					'args'                => array(
						'id'     => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'values' => array(
							'required' => false,
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/references',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_references' ),
					'permission_callback' => array( $this, 'check_if_user_can_view_post' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/reschedule',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reschedule_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_edit_post' ),
					'args'                => array(
						'id'          => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'day'         => array(
							'required'          => true,
							'type'              => 'date',
							'validate_callback' => 'nelio_content_is_date',
						),
						'hour'        => array(
							'required'          => false,
							'type'              => 'time',
							'validate_callback' => 'nelio_content_is_time',
						),
						'defaultHour' => array(
							'required'          => true,
							'type'              => 'time',
							'validate_callback' => 'nelio_content_is_time',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/unschedule',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'unschedule_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_edit_post' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/trash',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'trash_post' ),
					'permission_callback' => array( $this, 'check_if_user_can_trash_post' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			nelio_content()->rest_namespace,
			'/post/(?P<id>[\d]+)/status',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_post_status' ),
					'permission_callback' => array( $this, 'check_if_user_can_edit_post' ),
					'args'                => array(
						'id'        => array(
							'required'          => true,
							'type'              => 'number',
							'validate_callback' => 'nelio_content_can_be_natural_number',
							'sanitize_callback' => 'absint',
						),
						'status'    => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'dateValue' => array(
							'required'          => false,
							'type'              => 'datetime',
							'validate_callback' => 'nelio_content_is_datetime',
						),
					),
				),
			)
		);
	}

	/**
	 * Callback to check if user can create a post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return bool
	 */
	public function check_if_user_can_create_post( $request ) {

		if ( nelio_content_can_current_user_manage_plugin() ) {
			return true;
		}

		$post_type  = $request['type'] ?? '';
		$post_type  = is_string( $post_type ) ? $post_type : '';
		$post_type  = get_post_type_object( $post_type );
		$capability = ! empty( $post_type ) ? $post_type->cap->create_posts : null;
		return is_string( $capability ) && current_user_can( $capability );
	}

	/**
	 * Callback to check if user can view a post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return bool
	 */
	public function check_if_user_can_view_post( $request ) {

		if ( nelio_content_can_current_user_manage_plugin() ) {
			return true;
		}

		$post_id = absint( $request['id'] );
		return current_user_can( 'read_post', $post_id );
	}

	/**
	 * Callback to check if post type is valid.
	 *
	 * @param string $type Post type.
	 *
	 * @return bool
	 */
	public function is_valid_post_type( $type ) {
		if ( empty( $type ) ) {
			return false;
		}

		$post_types = array_merge(
			nelio_content_get_post_types( 'calendar' ),
			nelio_content_get_post_types( 'content-board' )
		);
		if ( ! in_array( $type, $post_types, true ) ) {
			return false;
		}

		$post_type = get_post_type_object( $type );
		return ! empty( $post_type );
	}

	/**
	 * Callback to check if user can edit post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return bool
	 */
	public function check_if_user_can_edit_post( $request ) {

		$post_id   = absint( $request['id'] );
		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			return false;
		}

		if ( ! $this->is_valid_post_type( $post_type ) ) {
			return false;
		}

		if ( nelio_content_can_current_user_manage_plugin() ) {
			return true;
		}

		$post_type = get_post_type_object( $post_type );
		if ( empty( $post_type ) ) {
			return false;
		}

		$capability = in_array( get_post_status( $post_id ), array( 'publish', 'future' ), true )
			? $post_type->cap->edit_published_posts
			: $post_type->cap->edit_posts;
		return is_string( $capability ) && current_user_can( $capability, $post_id );
	}

	/**
	 * Callback to check if user can trash post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return bool
	 */
	public function check_if_user_can_trash_post( $request ) {

		$editable = $this->check_if_user_can_edit_post( $request );
		if ( empty( $editable ) ) {
			return false;
		}

		if ( nelio_content_can_current_user_manage_plugin() ) {
			return true;
		}

		$post_id   = absint( $request['id'] );
		$post_type = get_post_type( $post_id );
		$post_type = ! empty( $post_type ) ? $post_type : '';
		$post_type = get_post_type_object( $post_type );
		if ( empty( $post_type ) ) {
			return false;
		}

		$capability = in_array( get_post_status( $post_id ), array( 'publish', 'future' ), true )
			? $post_type->cap->delete_published_posts
			: $post_type->cap->delete_posts;
		return is_string( $capability ) && current_user_can( $capability, $post_id );
	}

	/**
	 * Callback to sanitize taxonomies.
	 *
	 * @param array<string,list<array{id?:int}>> $taxonomies Taxonomies with terms.
	 *
	 * @return array<string,list<int>>
	 */
	public function sanitize_taxonomies( $taxonomies ) {
		return array_map(
			function ( $values ) {
				$values = array_map( fn( $term ) => absint( $term['id'] ?? 0 ), $values );
				return array_values( array_filter( $values ) );
			},
			$taxonomies
		);
	}

	/**
	 * Callback to sanitize series.
	 *
	 * @param list<TSeries> $series Series.
	 *
	 * @return list<TSeries>
	 */
	public function sanitize_series( $series ) {
		return array_map(
			function ( $series_item ) {
				$id   = absint( $series_item['id'] );
				$part = absint( $series_item['part'] ?? 0 );
				return $part
					? array(
						'id'   => $id,
						'part' => $part,
					)
					: array(
						'id' => $id,
					);
			},
			$series
		);
	}

	/**
	 * Returns the requested post.
	 *
	 * @param WP_REST_Request<array{id:int,aws?:bool}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post( $request ) {

		$post = get_post( absint( $request['id'] ) );
		if ( empty( $post ) ) {
			return new WP_Error(
				'post-not-found',
				_x( 'Post not found.', 'text', 'nelio-content' )
			);
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		$json        = ! empty( $request['aws'] )
			? $post_helper->post_to_aws_json( $post->ID )
			: $post_helper->post_to_json( $post->ID );
		if ( empty( $json ) ) {
			return new WP_Error(
				'stringify-error',
				_x( 'Unable to stringify post', 'text', 'nelio-content' )
			);
		}

		return new WP_REST_Response( $json, 200 );
	}

	/**
	 * Gets the post references.
	 *
	 * @param WP_REST_Request<array{id:int}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_post_references( $request ) {
		$post_id     = absint( $request['id'] );
		$post_helper = Nelio_Content_Post_Helper::instance();
		return new WP_REST_Response(
			$post_helper->get_references( $post_id, 'suggested' ),
			200
		);
	}

	/**
	 * Gets all posts in given date period.
	 *
	 * @param WP_REST_Request<array{from:string,to:string,type:list<string>}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_posts_in_date_range( $request ) {

		/** @var WP_Post|null */
		global $post;
		/** @var string */
		$from = $request->get_param( 'from' );
		/** @var string */
		$to = $request->get_param( 'to' );
		/** @var list<string> */
		$post_types = $request->get_param( 'type' );

		$args = array(
			'date_query'     => array(
				'after'     => $from,
				'before'    => $to,
				'inclusive' => true,
			),
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'desc',
			'post_type'      => $post_types,
			'post_status'    => 'any',
		);

		$query       = new WP_Query( $args );
		$post_helper = Nelio_Content_Post_Helper::instance();

		$result = array();
		while ( $query->have_posts() ) {
			$query->the_post();

			if ( empty( $post ) || '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				continue;
			}

			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}

			$aux = $post_helper->post_to_json( $post );
			if ( ! empty( $aux ) ) {
				array_push( $result, $aux );
			}
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Creates a new post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_post( $request ) {

		/** @var int */
		$author_id = absint( $request->get_param( 'authorId' ) );
		/** @var string */
		$title = $request->get_param( 'title' );
		/** @var string|false */
		$date = $request->get_param( 'dateValue' );
		$date = ! empty( $date ) ? $date : false;
		/** @var string|false */
		$time = $request->get_param( 'timeValue' );
		$time = ! empty( $time ) ? $time : false;
		/** @var string */
		$status = $request->get_param( 'status' );
		/** @var array<string,list<int>>|null */
		$taxonomies = $request->get_param( 'taxonomies' );
		$taxonomies = ! empty( $taxonomies ) ? $taxonomies : array();
		/** @var list<string> */
		$references = $request->get_param( 'references' );
		/** @var list<TSeries>|null */
		$series = $request->get_param( 'series' );
		$series = ! empty( $series ) ? $series : array();
		/** @var string */
		$type = $request->get_param( 'type' );

		/**
		 * Modifies the title that will be used in the given post.
		 *
		 * This filter is called right before the post is saved in the database.
		 *
		 * @param string $title the new post title.
		 *
		 * @since 1.0.0
		 */
		$title = trim( apply_filters( 'nelio_content_calendar_create_post_title', $title ) );
		if ( empty( $title ) ) {
			$title = _x( 'No Title', 'text', 'nelio-content' );
		}

		// Create new post.
		$post_data = array(
			'post_title'  => $title,
			'post_author' => $author_id,
			'post_type'   => $type,
			'post_status' => $status,
		);
		$datetime  = $date && $time ? strtotime( "$date $time:00" ) : false;
		if ( $date && $time && $datetime ) {
			$post_data['post_date']     = "$date $time:00";
			$post_data['post_date_gmt'] = get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $datetime ) );
		} else {
			$post_data['post_date_gmt'] = '0000-00-00 00:00:00';
		}

		$post_id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $post_id ) ) {
			return new WP_Error(
				'internal-error',
				_x( 'Post could not be created.', 'text', 'nelio-content' )
			);
		}

		// NOTE. Make sure post_modified and post_modified_gmt are properly set by triggering an update.
		$post_data['ID'] = $post_id;
		wp_update_post( $post_data );

		$this->trigger_save_post_action( $post_id, true );

		foreach ( $taxonomies as $tax => $term_ids ) {
			wp_set_post_terms( $post_id, $term_ids, $tax );
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		$post_helper->update_post_references( $post_id, $references, array() );
		$post_helper->update_series( $post_id, $series );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'internal-error',
				_x( 'Post was successfully created, but could not be retrieved.', 'text', 'nelio-content' )
			);
		}

		$response = array(
			'post'       => $post_helper->post_to_json( $post ),
			'references' => $post_helper->get_references( $post_id, 'suggested' ),
		);
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Updates a post.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_post( $request ) {

		/** @var int */
		$post_id = absint( $request['id'] );
		/** @var int */
		$author_id = absint( $request->get_param( 'authorId' ) );
		/** @var string */
		$title = $request->get_param( 'title' );
		/** @var string|false */
		$date = $request->get_param( 'dateValue' );
		$date = ! empty( $date ) ? $date : false;
		/** @var string|false */
		$time = $request->get_param( 'timeValue' );
		$time = ! empty( $time ) ? $time : false;
		/** @var string */
		$status = $request->get_param( 'status' );
		/** @var array<string,list<int>>|null */
		$taxonomies = $request->get_param( 'taxonomies' );
		$taxonomies = ! empty( $taxonomies ) ? $taxonomies : array();
		/** @var list<string> */
		$references = $request->get_param( 'references' );
		/** @var list<TSeries>|null */
		$series = $request->get_param( 'series' );
		$series = ! empty( $series ) ? $series : array();
		/** @var string */
		$type = $request->get_param( 'type' );
		/** @var bool */
		$sticky = ! empty( $request->get_param( 'isSticky' ) );

		/**
		 * Modifies the title that will be used in the given post.
		 *
		 * This filter is called right before the post is updated and saved in the database.
		 *
		 * @param string $title   the new post title.
		 * @param int    $post_id the ID of the post we're updating.
		 *
		 * @since 1.0.0
		 */
		$title = trim( apply_filters( 'nelio_content_calendar_update_post_title', $title, $post_id ) );
		if ( empty( $title ) ) {
			$title = _x( 'No Title', 'text', 'nelio-content' );
		}

		$post = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post_data = array(
			'ID'          => $post_id,
			'post_title'  => $title,
			'post_author' => $author_id,
			'post_status' => $status,
			'edit_date'   => true,
		);
		$datetime  = $date && $time ? strtotime( "$date $time:00" ) : false;
		if ( $date && $time && $datetime ) {
			$post_data['post_date']     = "$date $time:00";
			$post_data['post_date_gmt'] = get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $datetime ) );
		} else {
			$post_data['post_date']     = '0000-00-00 00:00:00';
			$post_data['post_date_gmt'] = '0000-00-00 00:00:00';
		}

		$aux = wp_update_post( $post_data, true );
		if ( is_wp_error( $aux ) ) {
			return new WP_Error(
				'post-not-updated',
				sprintf(
					/* translators: %s: Post ID. */
					_x( 'Post %s could not be updated.', 'text', 'nelio-content' ),
					$post_id
				)
			);
		}

		foreach ( $taxonomies as $tax => $term_ids ) {
			wp_set_post_terms( $post_id, $term_ids, $tax );
		}

		if ( 'post' === $type ) {
			if ( $sticky ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		$post_helper->update_post_references( $post_id, $references, array() );
		$post_helper->update_series( $post_id, $series );

		$this->trigger_save_post_action( $post_id, false );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'internal-error',
				_x( 'Post was successfully updated, but could not be retrieved.', 'text', 'nelio-content' )
			);
		}

		$response = array(
			'post'       => $post_helper->post_to_json( $post ),
			'references' => $post_helper->get_references( $post_id, 'suggested' ),
		);
		return new WP_REST_Response( $response, 200 );
	}



	/**
	 * Updates post items.
	 *
	 * @param WP_REST_Request<array{id:int,values:array<mixed>|null}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_post_items( $request ) {

		$post_id = absint( $request['id'] );
		$values  = $request->get_param( 'values' );

		$post = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		Nelio_Content_Gutenberg::instance()->save( $values, $post );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Updates a post status.
	 *
	 * @param WP_REST_Request<array{id:int,status:string,dateValue:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_post_status( $request ) {

		$post_id = absint( $request['id'] );
		/** @var string */
		$status = $request->get_param( 'status' );
		/** @var string */
		$utc_date = $request->get_param( 'dateValue' );

		$post = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( 'publish' === $status ) {
			$post_type  = get_post_type( $post_id );
			$post_type  = ! empty( $post_type ) ? $post_type : '';
			$post_type  = get_post_type_object( $post_type );
			$capability = ! empty( $post_type ) && is_string( $post_type->cap->publish_posts ) ? $post_type->cap->publish_posts : null;
			if ( empty( $capability ) || ! current_user_can( $capability, $post_id ) ) {
				return new WP_Error( _x( 'You’re not allowed to publish this post.', 'text', 'nelio-content' ) );
			}
		}

		$post_data = array(
			'ID'          => $post_id,
			'post_status' => $status,
		);

		if ( 'publish' === $status ) {
			$utc_date = current_datetime()->format( 'c' );
		}

		if ( ! empty( $utc_date ) ) {
			$post_data['post_date']     = get_date_from_gmt( $utc_date );
			$post_data['post_date_gmt'] = $utc_date;
			$post_data['edit_date']     = true;
		}

		$aux = wp_update_post( $post_data, true );
		if ( is_wp_error( $aux ) ) {
			return new WP_Error(
				'post-not-updated',
				sprintf(
					/* translators: %s: Post ID. */
					_x( 'Post %s could not be updated.', 'text', 'nelio-content' ),
					$post_id
				)
			);
		}

		$this->trigger_save_post_action( $post_id, false );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'internal-error',
				_x( 'Post status was successfully updated, but could not be retrieved.', 'text', 'nelio-content' )
			);
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		return new WP_REST_Response( $post_helper->post_to_json( $post ), 200 );
	}

	/**
	 * Search posts.
	 *
	 * @param  WP_REST_Request<array{query:string,per_page?:int,page?:int,status:list<string>,type:list<string>}> $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function search_posts( $request ) {

		/** @var string */
		$query = $request->get_param( 'query' );
		/** @var int */
		$per_page = $request->get_param( 'per_page' );
		$per_page = ! empty( $per_page ) ? $per_page : 10;
		/** @var int */
		$page = $request->get_param( 'page' );
		$page = ! empty( $page ) ? $page : 1;
		/** @var list<string> */
		$status = $request->get_param( 'status' );
		/** @var list<string> */
		$post_types = $request->get_param( 'type' );

		$args = array(
			'per_page'   => $per_page,
			'page'       => $page,
			'status'     => $status,
			'post_types' => $post_types,
		);

		$data = $this->search_wp_posts( $query, $args );
		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Reschedules the post to the given date.
	 *
	 * @param WP_REST_Request<array{id:int,day:string,hour:string,defaultHour:string}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function reschedule_post( $request ) {

		$post_id = absint( $request['id'] );
		$post    = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		/** @var string */
		$day = $request->get_param( 'day' );
		/** @var string */
		$time = $request->get_param( 'hour' );

		if ( empty( $time ) ) {
			/** @var string */
			$time = $request->get_param( 'defaultHour' );
			if ( '0000-00-00 00:00:00' !== $post->post_date_gmt ) {
				$pd = strtotime( $post->post_date );
				if ( ! empty( $pd ) ) {
					$time = gmdate( 'H:i:s', $pd );
				}
			}
		}

		$gmt_time = strtotime( $day . ' ' . $time );
		if ( empty( $gmt_time ) ) {
			return new WP_Error( 'gmt-error', _x( 'Unable to compute GMT time', 'text', 'nelio-content' ) );
		}

		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_date'     => $day . ' ' . $time,
				'post_date_gmt' => get_gmt_from_date( gmdate( 'Y-m-d H:i:s', $gmt_time ) ),
				'edit_date'     => true,
			)
		);
		$this->trigger_save_post_action( $post_id, false );

		$post        = get_post( $post_id );
		$post_helper = Nelio_Content_Post_Helper::instance();
		$json        = ! empty( $post ) ? $post_helper->post_to_json( $post ) : false;
		if ( ! $json ) {
			return new WP_Error(
				'stringify-error',
				_x( 'Unable to stringify post', 'text', 'nelio-content' )
			);
		}

		return new WP_REST_Response( $json, 200 );
	}

	/**
	 * Unschedules the post.
	 *
	 * @param WP_REST_Request<array{id:int}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function unschedule_post( $request ) {

		$post_id = absint( $request['id'] );
		$post    = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_date'     => '0000-00-00 00:00:00',
				'post_date_gmt' => '0000-00-00 00:00:00',
				'edit_date'     => true,
				'post_status'   => 'draft',
			)
		);
		$this->trigger_save_post_action( $post_id, false );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'internal-error',
				_x( 'Post was successfully unscheduled, but could not be retrieved.', 'text', 'nelio-content' )
			);
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		return new WP_REST_Response( $post_helper->post_to_json( $post ), 200 );
	}

	/**
	 * Trashes the post.
	 *
	 * @param WP_REST_Request<array{id:int}> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function trash_post( $request ) {

		$post_id = absint( $request['id'] );
		$post    = $this->maybe_get_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$result = wp_trash_post( $post_id );
		if ( empty( $result ) ) {
			return new WP_Error(
				'trash-post-failed',
				_x( 'Something went wrong when trashing the post.', 'text', 'nelio-content' )
			);
		}
		$this->trigger_save_post_action( $post_id, false );

		return new WP_REST_Response( true, 200 );
	}

	/**
	 * Triggers save post action.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $creating Creating.
	 *
	 * @return void
	 */
	private function trigger_save_post_action( $post_id, $creating ) {
		/**
		 * This filter is documented in includes/utils/class-nelio-content-post-saving.php
		 */
		do_action( 'nelio_content_save_post', $post_id, $creating );
	}

	/**
	 * Searches posts.
	 *
	 * @param string                                                                   $query Query.
	 * @param array{page:int,per_page:int,status:list<string>,post_types:list<string>} $args  Arguments.
	 *
	 * @return (
	 *   array{
	 *     results: list<TPost>,
	 *     pagination: array{
	 *       more: bool,
	 *       pages: int
	 *     }
	 *   }
	 * )
	 */
	private function search_wp_posts( $query, $args ) {

		/** @var WP_Post|null */
		global $post;

		/** @var wpdb */
		global $wpdb;

		$wpdb->set_sql_mode( array( 'ALLOW_INVALID_DATES' ) );
		$page       = $args['page'];
		$per_page   = $args['per_page'];
		$status     = $args['status'];
		$post_types = $args['post_types'];

		$posts = array();
		if ( 1 === $page ) {
			$posts = $this->search_wp_post_by_id_or_url( $query, $post_types );
		}

		$args = array(
			'post_title__like'    => $query,
			'post_type'           => $post_types,
			'order'               => 'desc',
			'orderby'             => 'date',
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'ignore_sticky_posts' => true,
		);

		$args['post_status'] = $status;
		if ( count( $status ) === 1 && 'nc_unscheduled' === $status[0] ) {
			$args['post_status'] = 'any';
			$args['date_query']  = array(
				'column'    => 'post_date_gmt',
				'before'    => '0000-00-00',
				'inclusive' => true,
			);
		}

		add_filter( 'posts_where', array( $this, 'add_title_filter_to_wp_query' ), 10, 2 );
		$wp_query = new WP_Query( $args );
		remove_filter( 'posts_where', array( $this, 'add_title_filter_to_wp_query' ), 10 );

		$post_helper = Nelio_Content_Post_Helper::instance();
		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			if ( empty( $post ) || absint( $query ) === $post->ID ) {
				continue;
			}

			if ( ! current_user_can( 'read_post', $post->ID ) ) {
				continue;
			}

			$json = $post_helper->post_to_json( $post );
			if ( $json ) {
				array_push( $posts, $json );
			}
		}
		wp_reset_postdata();

		$data = array(
			'results'    => $posts,
			'pagination' => array(
				'more'  => $page < $wp_query->max_num_pages,
				'pages' => $wp_query->max_num_pages,
			),
		);

		return $data;
	}

	/**
	 * Search posts by ID or URL.
	 *
	 * @param string|int   $id_or_url  ID or URL.
	 * @param list<string> $post_types Post types.
	 *
	 * @return list<TPost>
	 */
	private function search_wp_post_by_id_or_url( $id_or_url, $post_types ) {

		if ( ! absint( $id_or_url ) && ! filter_var( $id_or_url, FILTER_VALIDATE_URL ) ) {
			return array();
		}

		$post_id = absint( $id_or_url );
		if ( ! $post_id ) {
			$post_id = nelio_content_url_to_postid( "{$id_or_url}" );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return array();
		}

		if ( 'trash' === $post->post_status ) {
			return array();
		}

		if ( ! current_user_can( 'read_post', $post->ID ) ) {
			return array();
		}

		$post_helper = Nelio_Content_Post_Helper::instance();
		$json        = $post_helper->post_to_json( $post );
		if ( ! $json ) {
			return array();
		}

		return array( $json );
	}

	/**
	 * A filter to search posts based on their title.
	 *
	 * This function modifies the posts query so that we can search posts based
	 * on a term that should appear in their titles.
	 *
	 * @param string   $where    The where clause, as it's originally defined.
	 * @param WP_Query $wp_query The $wp_query object that contains the params
	 *                           used to build the where clause.
	 *
	 * @return string
	 *
	 * @since  1.0.0
	 */
	public function add_title_filter_to_wp_query( $where, $wp_query ) {

		/** @var wpdb */
		global $wpdb;

		/** @var string|null */
		$search_term = $wp_query->get( 'post_title__like' );
		if ( $search_term ) {
			$search_term = esc_sql( $wpdb->esc_like( $search_term ) );
			$search_term = ' \'%' . $search_term . '%\'';
			$where       = $where . ' AND ' . $wpdb->posts . '.post_title LIKE ' . $search_term;
		}

		return $where;
	}

	/**
	 * Gets post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return WP_Error|WP_Post
	 */
	private function maybe_get_post( $post_id ) {

		if ( empty( $post_id ) ) {
			return new WP_Error(
				'missing-post-id',
				_x( 'Post ID is missing.', 'text', 'nelio-content' )
			);
		}

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return new WP_Error(
				'post-not-found',
				sprintf(
					/* translators: %s: Post ID. */
					_x( 'Post %s not found.', 'text', 'nelio-content' ),
					$post_id
				)
			);
		}

		return $post;
	}
}

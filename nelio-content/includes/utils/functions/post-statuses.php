<?php

defined( 'ABSPATH' ) || exit;

/**
 * Returns the post statuses available for this post type.
 *
 * @param string $post_type Post type name.
 *
 * @return list<TPost_Status>
 *
 * @since 4.0.8
 */
function nelio_content_get_post_statuses( $post_type ) {
	$statuses = array_values(
		array_filter(
			nelio_content_get_statuses(),
			fn( $status ) => 'all-types' === $status['postTypes'] || in_array( $post_type, $status['postTypes'], true )
		)
	);

	$type_object = get_post_type_object( $post_type );
	$user        = wp_get_current_user();
	return array_map(
		function ( $status ) use ( $post_type, &$type_object, &$user ) {
			$available = false;
			if ( 'private' === $status['slug'] ) {
				$available = current_user_can( 'administrator' ); //phpcs:ignore WordPress.WP.Capabilities.RoleFound
			} elseif ( empty( $type_object ) ) {
				$available = false;
			} elseif ( current_user_can( 'manage_options' ) ) {
				$available = true;
			} elseif ( 'all-roles' === $status['roles'] || array_intersect( $user->roles, $status['roles'] ) ) {
				$available = true;
			} elseif ( in_array( $status['slug'], array( 'publish', 'future' ), true ) ) {
				$available = is_string( $type_object->cap->publish_posts ) && current_user_can( $type_object->cap->publish_posts );
			} else {
				$available = false;
			}

			/**
			 * Filters whether current user can set this status or not.
			 *
			 * @param boolean      $available   Whether current user can set this status or not.
			 * @param string       $status_slug Status slug.
			 * @param string       $post_type   Post type.
			 * @param TPost_Status $status      Status.
			 *
			 * @since 2.3.5
			 */
			$available = apply_filters( 'nelio_content_can_use_post_status', $available, $status['slug'], $post_type, $status );

			$status['available'] = ! empty( $available );
			return $status;
		},
		$statuses
	);
}

/**
 * Returns all post statuses.
 *
 * @return list<TPost_Status>
 *
 * @since 4.0.0
 */
function nelio_content_get_statuses() {
	$core_statuses = array(
		array(
			'slug'      => 'trash',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Trash' ),
			'icon'      => 'trash',
			'colors'    => array(
				'main'       => '#c44',
				'background' => '#fee',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => 'all-roles',
		),
		array(
			'slug'      => 'draft',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Draft' ),
			'icon'      => 'drafts',
			'colors'    => array(
				'main'       => '#c44',
				'background' => '#fee',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => 'all-roles',
		),
		array(
			'slug'      => 'pending',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Pending' ),
			'icon'      => 'pending',
			'colors'    => array(
				'main'       => '#f9d510',
				'background' => '#fffdf1',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => 'all-roles',
		),
		array(
			'slug'      => 'future',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Scheduled' ),
			'icon'      => 'scheduled',
			'colors'    => array(
				'main'       => '#447d37',
				'background' => '#e5f0e7',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => array(),
		),
		array(
			'slug'      => 'publish',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Published' ),
			'icon'      => 'published',
			'colors'    => array(
				'main'       => '#447d37',
				'background' => '#e5f0e7',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => array(),
		),
		array(
			'slug'      => 'private',
			// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'name'      => __( 'Private' ),
			'icon'      => 'notAllowed',
			'colors'    => array(
				'main'       => '#447d37',
				'background' => '#e5f0e7',
			),
			'core'      => true,
			'postTypes' => 'all-types',
			'roles'     => array(),
		),
	);

	$statuses   = array();
	$post_types = nelio_content_get_post_types( 'wp' );
	foreach ( $post_types as $post_type ) {
		$post_type_statuses = $core_statuses;

		/**
		 * Filters whether the given post type supports its content being “unscheduled” or not.
		 *
		 * @param boolean $supported  whether unscheduled content is supported or not. Default: `true`.
		 * @param string  $post_type  post type.
		 *
		 * @since 3.6.0
		 */
		if ( apply_filters( 'nelio_content_can_post_type_be_unscheduled', true, $post_type ) ) {
			$unscheduled        = array(
				'slug'      => 'nelio-content-unscheduled',
				'name'      => _x( 'Unscheduled', 'text', 'nelio-content' ),
				'colors'    => array(
					'main'       => '#c44',
					'background' => '#fee',
				),
				'postTypes' => 'all-types',
				'roles'     => 'all-roles',
			);
			$post_type_statuses = array_merge( array( $unscheduled ), $post_type_statuses );
		}

		/**
		 * Filters the available post statuses for a given post type.
		 *
		 * Each status must contain a `slug`, a `name`, an optional `icon`
		 * dashicon, and a `colors` array with two values:
		 * `main` and `background`.
		 *
		 * Since 3.6.0, statuses may also contain a `flags` property. It’s
		 * an array that can accept the following string values:
		 *
		 * - `disabled-in-editor`: post status can’t be selected in Nelio’s quick post editor.
		 * - `hide-in-board`: post status is not visible in Nelio’s content board.
		 * - `no-drop-in-board`: content can’t be dropped into this status in Nelio’s content board.
		 *
		 * @param list<TPost_Status> $statues   List of post statuses.
		 * @param string             $post_type Post type.
		 *
		 * @since 2.2.2
		 */
		$post_type_statuses = apply_filters( 'nelio_content_post_statuses', $post_type_statuses, $post_type );

		// NOTE. Making sure that mandatory attributes are indeed present.
		/** @var list<TPost_Status> */
		$post_type_statuses = array_map(
			fn ( $s ) => wp_parse_args(
				$s,
				array(
					'postTypes' => array( $post_type ),
					'roles'     => 'all-roles',
				)
			),
			$post_type_statuses
		);

		foreach ( $post_type_statuses as $post_type_status ) {
			$slug = $post_type_status['slug'];
			if ( empty( $statuses[ $slug ] ) ) {
				$statuses[ $slug ] = $post_type_status;
			}

			$existing          = $statuses[ $slug ];
			$statuses[ $slug ] = array_merge(
				$existing,
				array(
					'postTypes' => (
						'all-types' === $existing['postTypes'] || 'all-types' === $post_type_status['postTypes']
							? 'all-types'
							: array_values( array_unique( array_merge( $existing['postTypes'], $post_type_status['postTypes'] ) ) )
					),
				)
			);
		}
	}
	$statuses = array_map( fn( $status ) => array_merge( $status, array( 'isCustomizationLimited' => true ) ), $statuses );

	/** @var list<TPost_Status> $custom_statuses */
	$custom_statuses = get_option( 'nc_statuses', array() );
	// NOTE. Making sure that mandatory attributes are indeed present.
	/** @var list<TPost_Status> $custom_statuses */
	$custom_statuses = array_map(
		fn ( $s ) => wp_parse_args(
			$s,
			array(
				'postTypes' => 'all-types',
				'roles'     => 'all-roles',
			)
		),
		$custom_statuses
	);

	foreach ( $custom_statuses as $custom_status ) {
		$slug = $custom_status['slug'];
		if ( empty( $statuses[ $slug ] ) ) {
			$statuses[ $slug ] = $custom_status;
		}

		$existing          = $statuses[ $slug ];
		$statuses[ $slug ] = array_merge(
			$existing,
			array(
				'colors' => $custom_status['colors'] ?? $existing['colors'] ?? array(),
				'icon'   => $custom_status['icon'] ?? $existing['icon'] ?? '',
				'roles'  => (
					'all-roles' === $custom_status['roles'] || 'all-roles' === $existing['roles']
						? 'all-roles'
						: array_values( array_unique( array_merge( $custom_status['roles'], $existing['roles'] ) ) )
				),
			)
		);
	}

	/** @var array<string,false> */
	$ordered = array_combine(
		array_map( fn( $s ) => $s['slug'], $custom_statuses ),
		array_map( fn() => false, $custom_statuses )
	);
	/** @var array<string,TPost_Status> */
	$statuses = $statuses;
	$statuses = array_merge( $ordered, $statuses );
	return array_values( array_filter( $statuses ) );
}

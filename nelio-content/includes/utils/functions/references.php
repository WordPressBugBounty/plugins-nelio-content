<?php

defined( 'ABSPATH' ) || exit;

/**
 * Returns the reference whose ID is the given ID.
 *
 * @param integer $id The ID of the reference.
 *
 * @return Nelio_Content_Reference|false The reference with the given ID or `false` if such a reference does not exist.
 *
 * @since 4.0.8
 */
function nelio_content_get_reference( $id ) {

	$post = get_post( $id );
	if ( $post ) {
		return new Nelio_Content_Reference( $post );
	} else {
		return false;
	}
}

/**
 * Returns the reference whose URL is the given URL.
 *
 * @param string $url The URL of the reference we want to retrieve.
 *
 * @return Nelio_Content_Reference|false The reference with the given URL or
 *               false if such a reference does not exist.
 *
 * @since 4.0.8
 */
function nelio_content_get_reference_by_url( $url ) {

	/** @var WP_Post $post */
	global $post;
	$result = false;

	// Look for an existing reference with the given URL.
	$args  = array(
		'post_type'   => 'nc_reference',
		'post_parent' => 0,
		'post_status' => 'any',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_key'    => '_nc_url',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		'meta_value'  => $url,
	);
	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();
			$result = new Nelio_Content_Reference( $post );
			break;
		}
	}

	wp_reset_postdata();

	// If we didn't find the reference, let's check if it's an internal link.
	if ( ! $result ) {

		$reference = nelio_content_url_to_postid( $url );
		if ( $reference ) {
			$result = new Nelio_Content_Reference( $reference );
			$result->set_url( $url );
		}
	}

	return $result;
}

/**
 * Creates a new reference with the given URL.
 *
 * If a reference with the given URL already exists, that reference will be returned.
 *
 * @param string $url The URL of the (possibly) new reference.
 *
 * @return Nelio_Content_Reference|false The new reference (or an
 *              existing one, if there already existed one reference
 *              with the given URL). If the reference didn't exist and
 *              couldn't be created, `false` is returned.
 *
 * @since 4.0.8
 */
function nelio_content_create_reference( $url ) {

	$reference = nelio_content_get_reference_by_url( $url );

	if ( empty( $reference ) ) {

		$reference = wp_insert_post(
			array(
				'post_title'  => '',
				'post_type'   => 'nc_reference',
				'post_status' => 'nc_pending',
			)
		);

		if ( $reference ) {
			// We add the URL using the meta directly, or else the status would be
			// changed from "pending" to "improvable", because all Reference setters
			// may update its status.
			update_post_meta( $reference, '_nc_url', $url );
			$reference = new Nelio_Content_Reference( $reference );
		} else {
			$reference = false;
		}
	}

	return $reference;
}

/**
 * Returns a list of all the references related to a given post.
 *
 * @param integer|WP_Post                    $post_id The post whose references will be returned.
 * @param 'included'|'suggested'|'discarded' $status  Optional. It specifies which references have to be returned. Default: `included`.
 *
 * @return list<int> a list of all the references related to the given post.
 *
 * @since 4.0.8
 */
function nelio_content_get_post_reference( $post_id, $status = 'included' ) {

	// Making sure we're using the post's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	switch ( $status ) {

		case 'discarded':
			return get_post_meta( $post_id, '_nc_discarded_reference', false );

		case 'suggested':
			return get_post_meta( $post_id, '_nc_suggested_reference', false );

		case 'included':
		default:
			return get_post_meta( $post_id, '_nc_included_reference', false );

	}
}

/**
 * Adds a reference to the given post, which means the reference appears in post's content.
 *
 * @param integer|WP_Post $post_id      The post in which a certain reference has to be added.
 * @param integer|WP_Post $reference_id The reference to be added.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_add_post_reference( $post_id, $reference_id ) {

	// Making sure we're using the post's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	// If the reference has been added to the post, it can't be "discarded".
	delete_post_meta( $post_id, '_nc_discarded_reference', $reference_id );
	nelio_content_add_post_meta_once( $post_id, '_nc_included_reference', $reference_id );
}

/**
 * Removes a reference from the list of "included references" of a certain post.
 *
 * @param integer|WP_Post $post_id      The post from which a certain reference has to be deleted.
 * @param integer|WP_Post $reference_id The reference to be deleted.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_delete_post_reference( $post_id, $reference_id ) {

	// Making sure we're using the post's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	delete_post_meta( $post_id, '_nc_included_reference', $reference_id );
	nelio_content_remove_unused_reference( $reference_id );
}

/**
 * Adds a reference as a suggestion of our post.
 *
 * @param integer|WP_Post $post_id      The post in which a certain reference has been suggested.
 * @param integer|WP_Post $reference_id The suggested reference.
 * @param integer         $advisor      The user ID who's suggesting this reference.
 *                                      If the advisor is Nelio Content itself, the ID is 0.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_suggest_post_reference( $post_id, $reference_id, $advisor ) {

	// Making sure we're using the post's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	// After (re)suggesting a reference, it can't be "discarded". It can, however,
	// be also included, so there's no need to update the "included" meta.
	delete_post_meta( $post_id, '_nc_discarded_reference', $reference_id );
	nelio_content_add_post_meta_once( $post_id, '_nc_suggested_reference', $reference_id );

	$meta = array(
		'advisor' => $advisor,
		'date'    => time(),
	);
	add_post_meta( $post_id, '_nc_suggested_reference_' . $reference_id . '_meta', $meta, true );
}

/**
 * Removes a reference from the list of suggested references in a post.
 *
 * @param integer|WP_Post $post_id      The post from which a certain suggested reference has been discarded.
 * @param integer|WP_Post $reference_id The discarded reference.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_discard_post_reference( $post_id, $reference_id ) {

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	// Making sure we're using the post's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	// Add the reference in the discarded list.
	nelio_content_add_post_meta_once( $post_id, '_nc_discarded_reference', $reference_id );

	// And remove it from any other list.
	nelio_content_delete_post_reference( $post_id, $reference_id );
	delete_post_meta( $post_id, '_nc_suggested_reference', $reference_id );
	delete_post_meta( $post_id, '_nc_suggested_reference_' . $reference_id . '_meta' );
}

/**
 * Returns meta information about a suggested reference, such as who suggested
 * it in a certain post.
 *
 * @param integer|WP_Post $post_id      The post for which the reference was suggested.
 * @param integer|WP_Post $reference_id The reference from which we want to obtain its meta information.
 *
 * @return TSuggested_Reference_Meta|false
 *
 * @since 4.0.8
 */
function nelio_content_get_suggested_reference_meta( $post_id, $reference_id ) {
	// Making sure we're using the posts's ID.
	if ( $post_id instanceof WP_Post ) {
		$post_id = $post_id->ID;
	}

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	/** @var TSuggested_Reference_Meta|'' $result */
	$result = get_post_meta( $post_id, '_nc_suggested_reference_' . $reference_id . '_meta', true );
	return ! empty( $result ) ? $result : false;
}

/**
 * Removes a reference from the database, iff it's not used by any post.
 *
 * @param integer|WP_Post $reference_id The reference to be deleted.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_remove_unused_reference( $reference_id ) {

	/** @var wpdb $wpdb */
	global $wpdb;

	// Making sure we're using the reference's ID.
	if ( $reference_id instanceof WP_Post ) {
		$reference_id = $reference_id->ID;
	}

	$reference = get_post( $reference_id );
	if ( empty( $reference ) || 'nc_reference' !== $reference->post_type ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$posts_with_the_ref = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE meta_value = %s AND meta_key IN ( %s, %s, %s )',
			$wpdb->postmeta,
			$reference_id,
			'_nc_included_reference',
			'_nc_suggested_reference',
			'_nc_discarded_reference'
		)
	);

	if ( $posts_with_the_ref ) {
		return;
	}

	wp_delete_post( $reference_id );
}

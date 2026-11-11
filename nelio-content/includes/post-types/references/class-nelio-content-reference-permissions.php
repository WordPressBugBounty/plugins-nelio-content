<?php
/** Shared bibliography authorization for native REST and abilities. */
defined( 'ABSPATH' ) || exit;

class Nelio_Content_Reference_Permissions {
	const MAX_PARENTS = 100;

	/**
	 * A shared write requires access to every stored association, not just one post.
	 * Unassociated references can be edited by their creator before the post is saved.
	 *
	 * @param int  $id Reference.
	 * @param bool $allow_unassociated Whether the native editor can edit a new, owned reference.
	 * @return list<int>|WP_Error Authorized association snapshot.
	 * @phpstan-impure
	 */
	public static function editable_parents( $id, $allow_unassociated = false ) {
		$reference = $id > 0 ? get_post( $id ) : null;
		$types     = nelio_content_get_post_types( 'references' );
		if ( ! is_user_logged_in() || ! nelio_content_can_current_user_use_plugin() || ! $reference || 'nc_reference' !== $reference->post_type
			|| ! in_array( $reference->post_status, array( 'nc_pending', 'nc_improvable', 'nc_complete', 'nc_broken', 'nc_check' ), true )
			|| ! current_user_can( 'read_post', $id ) || ! current_user_can( 'edit_post', $id ) || ! $types ) {
			return self::denied();
		}
		global $wpdb;
		if ( ! $wpdb instanceof wpdb ) {
			return self::denied();
		}
		$previous = $wpdb->suppress_errors();
		try {
			// Include discarded references too: their bibliography is still shared.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$values = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT post_id FROM %i WHERE meta_key IN (%s, %s, %s) AND meta_value = %s ORDER BY post_id ASC LIMIT %d', $wpdb->postmeta, '_nc_included_reference', '_nc_suggested_reference', '_nc_discarded_reference', (string) $id, self::MAX_PARENTS + 1 ) );
			if ( '' !== $wpdb->last_error || count( $values ) > self::MAX_PARENTS ) {
				return self::denied();
			}
			$parents     = array();
			$has_context = false;
			foreach ( $values as $value ) {
				$parent_id = is_string( $value ) ? filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ) : false;
				$post      = false !== $parent_id ? get_post( $parent_id ) : null;
				if ( ! $post || ! current_user_can( 'read_post', $post->ID ) || ! current_user_can( 'edit_post', $post->ID ) ) {
					return self::denied();
				}
				$parents[]   = $post->ID;
				$has_context = $has_context || ( in_array( $post->post_type, $types, true ) && ! in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) );
			}
			if ( $parents ) {
				return $has_context ? $parents : self::denied();
			}
			return $allow_unassociated && get_current_user_id() === (int) $reference->post_author ? array() : self::denied();
		} finally {
			$wpdb->suppress_errors( $previous );
		}
	}

	/** @return WP_Error */
	public static function denied() {
		return new WP_Error( 'nelio_content_reference_forbidden', _x( 'You cannot edit this shared reference with your current permissions.', 'user', 'nelio-content' ), array( 'status' => 403 ) );
	}
}

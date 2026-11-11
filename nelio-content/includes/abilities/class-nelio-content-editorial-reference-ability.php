<?php
/** Shared authorization and relationship queries for editorial reference abilities. */
defined( 'ABSPATH' ) || exit;

/**
 * Protects internal editorial information in both editions.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Editorial_Reference_Ability extends Nelio_Content_Ability {

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 * @phpstan-impure
	 */
	public function permissions( $input ) {
		$post = isset( $input['post_id'] ) ? get_post( $input['post_id'] ) : null;
		return $post && $this->can_use_plugin( $input ) && current_user_can( 'edit_post', $post->ID ) &&
			! in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) &&
			in_array( $post->post_type, nelio_content_get_post_types( 'references' ), true );
	}

	/**
	 * @param int $id Reference identifier.
	 * @return bool
	 * @phpstan-impure
	 */
	protected function can_read_reference( $id ) {
		$post = get_post( $id );
		return $post && current_user_can( 'read_post', $id ) &&
			! in_array( $post->post_status, array( 'trash', 'auto-draft' ), true ) &&
			( 'nc_reference' === $post->post_type || is_post_type_viewable( $post->post_type ) );
	}

	/**
	 * @param int    $post_id Post.
	 * @param string $key Relationship metadata key.
	 * @param int    $id Reference.
	 * @return bool
	 * @phpstan-impure
	 */
	protected function has_relation( $post_id, $key, $id ) {
		$values = get_metadata( 'post', $post_id, $key, false );
		if ( ! is_array( $values ) ) {
			return false;
		}
		foreach ( $values as $value ) {
			if ( ( is_string( $value ) || is_int( $value ) ) && (string) $id === (string) $value ) {
				return true;
			}
		}
		return false;
	}
}

<?php
/** Discoverable contract for creating a draft copy of existing content. */
defined( 'ABSPATH' ) || exit;

/**
 * Describes duplication while Premium supplies the implementation.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
class Nelio_Content_Duplicate_Content_Ability extends Nelio_Content_Premium_Ability {

	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/duplicate-content',
			array(
				'label'               => _x( 'Duplicate content', 'command', 'nelio-content' ),
				'description'         => _x( 'Create one new draft copy of an editable WordPress post or page, or another content type enabled for duplication. Requires Nelio Content Premium, read and edit access to the original, and permission to create that content type. The copy belongs to the current user and remains a draft even when the original is published or scheduled. Uses the site’s duplication options for the title, content, excerpt, date, taxonomies, metadata and featured image. Media references can be shared with the original; files, social messages, editorial tasks and future actions are not cloned. Attachments, revisions, trashed or internal drafts and rewrite copies are unsupported. The result confirms the saved copy’s identity, type, status and author, not an atomic copy of every metadata field. Each call creates a new copy; inspect any reported copy or the content list before retrying an uncertain result.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-calendar',
				'input_schema'        => $this->object_schema(
					array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Existing readable and editable WordPress content to copy. Its type must support duplication and creation by the current user. The original remains unchanged.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id' ),
					_x( 'Identify one original to duplicate as a new draft. Copy options are controlled by the site rather than supplied by the caller.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'source_post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'ID of the original content used to create this copy.', 'text', 'nelio-content' ),
						),
						'post_id'        => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'ID of the newly created draft, distinct from the original. Inspect this copy before repeating the operation.', 'text', 'nelio-content' ),
						),
						'post_type'      => array(
							'type'        => 'string',
							'description' => _x( 'Saved content type of the copy, matching the original.', 'text', 'nelio-content' ),
						),
						'status'         => array(
							'type'        => 'string',
							'enum'        => array( 'draft' ),
							'description' => _x( 'Verified draft state. A copied date does not mean the copy is scheduled for publication.', 'text', 'nelio-content' ),
						),
						'author_id'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'Saved author of the copy, verified to be the current user.', 'text', 'nelio-content' ),
						),
						'edit_url'       => array(
							'type'        => 'string',
							'format'      => 'uri',
							'description' => _x( 'WordPress editor URL for the confirmed draft copy.', 'text', 'nelio-content' ),
						),
					),
					array( 'source_post_id', 'post_id', 'post_type', 'status', 'author_id', 'edit_url' ),
					_x( 'Verified identity, draft state and current-user ownership of one new copy. Raw content and metadata are excluded. Taxonomy and metadata copying is not transactional.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/duplicate-content';
	}

	/**
	 * Checks source access and creation capability before any subscription guidance.
	 *
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		$post = get_post( $input['post_id'] ?? 0 );
		if ( ! isset( $input['post_id'] ) || ! $post || ! current_user_can( 'edit_post', $post->ID ) ||
			in_array( $post->post_type, array( 'attachment', 'revision' ), true ) ||
			in_array( $post->post_status, array( 'trash', 'auto-draft', 'inherit' ), true ) ||
			! in_array( $post->post_type, nelio_content_get_post_types( 'duplicate' ), true ) ) {
			return false;
		}
		$type       = get_post_type_object( $post->post_type );
		$capability = $type ? $type->cap->create_posts : null;
		return is_string( $capability ) && current_user_can( $capability );
	}
}

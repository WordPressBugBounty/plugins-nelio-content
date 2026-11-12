<?php
/** Contract for create-editorial-comment. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium create editorial comment operation. */
class Nelio_Content_Create_Editorial_Comment_Ability extends Nelio_Content_Editorial_Comment_Ability {

	/** @return void */
	public function register() {
		$properties = $this->comment_properties();
		unset( $properties['cursor'], $properties['per_page'] );
		$properties['comment'] = array(
			'type'        => 'string',
			'minLength'   => 1,
			'maxLength'   => 10000,
			'description' => 'Editorial comment containing plain text that is not blank, up to 10000 Unicode characters. Leading and trailing whitespace is removed. HTML is unsupported; line breaks are preserved.',
		);
		wp_register_ability(
			'nelio-content/create-editorial-comment',
			array(
				'label'               => _x( 'Create editorial comment', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium and permission to edit the selected saved post, with editorial comments enabled for its post type. Add one internal editorial comment as the authenticated current user. Leading and trailing whitespace is removed; text is plain, with HTML unsupported. Normal author and follower notifications may be sent according to site settings. Does not create a public WordPress reader comment or change followers. A failed request may already have saved the comment; list the discussion before retrying. No automatic retry is performed.',
				'category'            => 'nelio-content-comments',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id', 'comment' ), 'Saved post identity and attributes for accessing its internal editorial discussion.' ),
				'output_schema'       => $this->comment_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/create-editorial-comment';
	}
}

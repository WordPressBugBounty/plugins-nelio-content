<?php
/** Contract for list-editorial-comments. */
defined( 'ABSPATH' ) || exit;

/** Describes the Premium list editorial comment operation. */
class Nelio_Content_List_Editorial_Comments_Ability extends Nelio_Content_Editorial_Comment_Ability {

	/** @return void */
	public function register() {
		$properties = $this->comment_properties();
		wp_register_ability(
			'nelio-content/list-editorial-comments',
			array(
				'label'               => _x( 'List editorial comments', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium and permission to edit the selected post, with editorial comments enabled for its post type. Read one bounded page of internal editorial discussion, separate from public WordPress reader comments. Following a post alone does not grant access. Results retain oldest-first discussion order. Keep post_id and per_page unchanged when following next_cursor. Does not change comments, followers or notification settings.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-comments',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id' ), _x( 'Saved post identity and attributes for accessing its internal editorial discussion.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->page_schema( $this->comment_schema(), _x( 'A bounded page of the selected post discussion, plus continuation information and the site timezone. Post content, tasks, social messages and follower identities are excluded.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( true ),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-editorial-comments';
	}
}

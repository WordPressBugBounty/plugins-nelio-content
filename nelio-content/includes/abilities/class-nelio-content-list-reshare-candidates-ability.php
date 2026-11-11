<?php
/**
 * Discoverable Premium contract for inspecting reshare eligibility without scheduling.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Describes post candidates separately from generation and its final selections.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
class Nelio_Content_List_Reshare_Candidates_Ability extends Nelio_Content_Premium_Ability {

	/**
	 * Registers the bounded candidate query.
	 *
	 * @return void
	 */
	public function register() {
		$properties               = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['profile_id'] = $this->uuid_schema( _x( 'Optional accessible social profile whose enabled reshare groups should determine eligibility. Omit to use all profiles accessible to the current user.', 'text', 'nelio-content' ) );
		wp_register_ability(
			'nelio-content/list-reshare-candidates',
			array(
				'label'               => _x( 'List reshare candidates', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium and social editing permission. List readable published posts that match the configured republication rules for accessible social profiles: supported post types, enabled automation groups, author and taxonomy restrictions, post age and republication opt-in or expiry settings. Optionally restrict eligibility to one accessible profile. Returns a bounded page in ascending post ID order. Eligibility does not guarantee publication: templates, attachments, publishing limits and final scheduling choices can still prevent a message. Does not generate messages, reserve publishing slots or change the calendar. Follow next_cursor even when a page is empty while has_more is true.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array(), _x( 'Optional accessible profile filter and bounded pagination for published posts eligible for republication.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->page_schema( $this->post_summary_schema(), _x( 'Readable published posts matching republication rules for the selected accessible profiles. This is a candidate page, not a promise of generated messages or a complete list unless has_more is false.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}

	/**
	 * Identifies the paid implementation.
	 *
	 * @return string
	 */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-reshare-candidates';
	}

	/**
	 * Checks social editing before subscription guidance or Cloud reads.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return 'none' !== nelio_content_get_social_editor_permission();
	}
}

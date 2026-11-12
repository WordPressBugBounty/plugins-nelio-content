<?php
/**
 * Authorization shared by the two native automation generation operations.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Requires management access because native Cloud automation also adjusts site-wide quotas.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Generate_Timeline_Ability extends Nelio_Content_Premium_Ability {

	/**
	 * Checks management, social editing and any supplied post before Premium guidance.
	 *
	 * @param TAbility_Input $input Validated input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return nelio_content_can_current_user_manage_plugin() && 'all' === nelio_content_get_social_editor_permission() &&
			( empty( $input['post_id'] ) || ( current_user_can( 'edit_post', $input['post_id'] ) && in_array( get_post_type( $input['post_id'] ), nelio_content_get_post_types( 'social' ), true ) ) );
	}

	/**
	 * Marks regeneration as destructive and non-idempotent.
	 *
	 * @return array<string,mixed>
	 */
	protected function generation_metadata() {
		$metadata                = $this->get_premium_ability_metadata( false );
		$metadata['annotations'] = array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		);
		return $metadata;
	}

	/**
	 * Makes the native site-wide effects explicit in the caller's input.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function generation_properties() {
		return array(
			'confirm_generation' => array(
				'type'        => 'boolean',
				'enum'        => array( true ),
				'description' => 'Confirm automatic message generation, which can create or replace messages and pause or resume reshare messages across the site to respect publishing limits. Requires plugin management, full social editing and access to every connected profile.',
			),
		);
	}
}

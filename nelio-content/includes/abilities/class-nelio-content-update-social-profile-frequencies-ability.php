<?php
/** Discoverable Premium contract for shared per-profile automation frequencies. */
defined( 'ABSPATH' ) || exit;

/**
 * Updates a profile's limits across every automation group that uses it.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
class Nelio_Content_Update_Social_Profile_Frequencies_Ability extends Nelio_Content_Premium_Ability {
	/** @return void */
	public function register() {
		$properties = array(
			'profile_id'                 => $this->uuid_schema( 'One connected social profile accessible to the current user. Its frequencies are shared by all groups using it. Webhook profiles have no numeric frequency control.' ),
			'publication_frequency'      => $this->frequency_schema( 20, 'New maximum promotion messages per post publication, from 0 through 20. Zero disables publication automation for the profile; omitted preserves the current value.' ),
			'reshare_frequency'          => $this->frequency_schema( 10, 'New maximum daily reshare messages, from 0 through 10. Zero disables reshare automation for the profile; omitted preserves the current value. This limit is shared across groups.' ),
			'expected_frequencies'       => $this->object_schema(
				array(
					'publication' => $this->frequency_schema( 30, 'Current publicationFrequency from list-social-profiles, including any saved value above the editor’s input limit.' ),
					'reshare'     => $this->frequency_schema( 10, 'Current reshareFrequency from list-social-profiles.' ),
				),
				array( 'publication', 'reshare' ),
				'Both values returned by the last profile read. They are checked before submission to detect intervening changes, but Cloud may still change them between the check and the save.'
			),
			'confirm_shared_frequencies' => array(
				'type'        => 'boolean',
				'enum'        => array( true ),
				'description' => 'Confirm that these limits apply to this profile in every automation group, rather than only one group. Normal automation and quota processing use the new limits.',
			),
		);
		wp_register_ability(
			'nelio-content/update-social-profile-frequencies',
			array(
				'label'               => _x( 'Update social profile frequencies', 'command', 'nelio-content' ),
				'description'         => 'Requires Premium, plugin management, full social editing and access to the selected profile, including for administrators. Set one or both shared automation frequencies using current values from list-social-profiles and explicit confirmation that the limits apply across all groups using the profile. Unrelated profiles and group settings are not submitted. Preserves omitted frequency and existing link-shortener settings. Does not request timeline generation or message changes; normal quota processing may later affect pending reshares. A local lock prevents overlapping changes to this profile through these tools. Fresh checks can detect changed settings, but cannot prevent another client from changing Cloud settings at the same time. An error after submission may leave a saved change; inspect the profile before retrying. No automatic retry or rollback is performed.',
				'category'            => 'nelio-content-profiles',
				'input_schema'        => $this->object_schema( $properties, array( 'profile_id', 'expected_frequencies', 'confirm_shared_frequencies' ), 'One profile frequency change. At least one new frequency is required; current values and confirmation that the limits apply across all groups using the profile are mandatory.' ),
				'output_schema'       => $this->object_schema(
					array(
						'profile_id'            => $properties['profile_id'],
						'publication_frequency' => $this->frequency_schema( 30, 'Verified saved publication limit, including an unchanged legacy value up to 30.' ),
						'reshare_frequency'     => $this->frequency_schema( 10, 'Verified saved daily reshare limit.' ),
						'changed'               => array(
							'type'        => 'boolean',
							'description' => 'Whether a verified save changed either frequency. If the requested values are already saved, no changes are sent to Cloud.',
						),
					),
					array( 'profile_id', 'publication_frequency', 'reshare_frequency', 'changed' ),
					'Profile limits read after saving, without credentials, raw provider settings or a message-generation guarantee.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}

	/**
	 * @param int    $max Maximum.
	 * @param string $description Meaning.
	 * @return array<string,mixed>
	 */
	private function frequency_schema( $max, $description ) {
		return array(
			'type'        => 'integer',
			'minimum'     => 0,
			'maximum'     => $max,
			'description' => $description,
		);
	}
	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return nelio_content_can_current_user_manage_plugin() && 'all' === nelio_content_get_social_editor_permission();
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/update-social-profile-frequencies';
	}
}

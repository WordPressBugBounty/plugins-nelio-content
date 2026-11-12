<?php
/**
 * Shared contract for generating automatic promotion from a saved WordPress post.
 */

defined( 'ABSPATH' ) || exit;

/** Exposes the same Cloud endpoint and post preparation used by the editor. */
class Nelio_Content_Generate_Social_Timeline_Ability extends Nelio_Content_Generate_Timeline_Ability {

	/**
	 * Registers saved-post timeline generation.
	 *
	 * @return void
	 */
	public function register() {
		$properties                                      = $this->generation_properties();
		$properties['confirm_generation']['description'] = 'Confirm automatic promotion using all applicable automation settings, including profiles you cannot access. Generation can replace automatic messages and pause or resume reshare messages across the site. Requires plugin management, full social editing and post editing; only accessible messages are returned.';
		$properties['post_id']                           = array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Editable saved WordPress post, including drafts. Generation uses its current saved content and metadata; it does not save editor changes.',
		);
		wp_register_ability(
			'nelio-content/generate-social-timeline',
			array(
				'label'               => _x( 'Generate social timeline', 'command', 'nelio-content' ),
				'description'         => 'Generate or regenerate automatic social promotion for one saved WordPress post using its content, highlighted text, prominent text and automation settings. Requires Nelio Content Premium, plugin management, full social editing, and post editing. Access to every connected profile is not required: generation uses all applicable automation settings, including profiles unavailable to the caller; returned messages are restricted to accessible profiles. Replaces eligible unpublished automatic messages for this post and may pause or resume reshare messages across the site to respect publishing limits. Individual profiles cannot be selected. For an unpublished post, generated messages are drafts with planned dates and are scheduled automatically when the post is published and its publication update reaches Nelio Cloud. Do not regenerate or manually reschedule solely because they are drafts. Generation may produce no messages. A failed request may already have deleted or created messages; inspect the calendar before retrying. Returns at most 100 confirmed timeline messages, which can include existing messages.',
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $properties, array( 'post_id', 'confirm_generation' ), 'Saved post identity and explicit confirmation that generation can replace automatic messages and pause or resume reshare messages across the site.' ),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'   => $properties['post_id'],
						'items'     => array(
							'type'        => 'array',
							'maxItems'    => 100,
							'items'       => $this->message_schema(),
							'description' => 'Confirmed accessible timeline messages returned by Cloud, including existing messages where applicable. This is not a count of newly created messages.',
						),
						'truncated' => array(
							'type'        => 'boolean',
							'description' => 'True when more messages were returned than fit here. Query the timeline or calendar for remaining messages; never repeat generation to paginate.',
						),
					),
					array( 'post_id', 'items', 'truncated' ),
					'A confirmed post timeline response. Draft messages for an unpublished post are expected and are scheduled automatically after post publication reaches Nelio Cloud. Empty items is valid and does not imply that previous automatic messages were preserved.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->generation_metadata(),
			)
		);
	}

	/**
	 * Identifies the paid implementation.
	 *
	 * @return string
	 */
	protected function get_premium_ability_name() {
		return 'nelio-content/generate-social-timeline';
	}
}

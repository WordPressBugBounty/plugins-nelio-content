<?php
/**
 * Shared contract for running the same reshare scheduling used by cron.
 */

defined( 'ABSPATH' ) || exit;

/** Exposes cron scheduling without inventing a per-post or per-profile reshare operation. */
class Nelio_Content_Generate_Reshare_Timeline_Ability extends Nelio_Content_Generate_Timeline_Ability {

	/**
	 * Registers the site-wide reshare operation.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-content/generate-reshare-timeline',
			array(
				'label'               => _x( 'Generate reshare timeline', 'command', 'nelio-content' ),
				'description'         => _x( 'Fill remaining days in the current weekly reshare scheduling window with automatic republication of eligible content, using existing automation groups and publishing limits. Requires Nelio Content Premium, plugin management, full social editing and access to every connected profile. Runs across the site; individual posts, profiles and arbitrary date ranges cannot be selected, and previously scheduled days are not reset. The scheduling window uses UTC dates. Messages are generated asynchronously: success confirms that scheduling requests were accepted, not that messages already exist. Does nothing if the window is already scheduled or no eligible posts are found. A failed request may already have been accepted; inspect the calendar before retrying.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-messages',
				'input_schema'        => $this->object_schema( $this->generation_properties(), array( 'confirm_generation' ), _x( 'Explicit confirmation to generate reshare messages across the site for the remaining weekly scheduling window.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->object_schema(
					array(
						'status'      => array(
							'type'        => 'string',
							'enum'        => array( 'queued', 'no_change' ),
							'description' => _x( 'queued means Cloud acknowledged day scheduling requests; generation happens asynchronously. no_change means no request was needed.', 'text', 'nelio-content' ),
						),
						'queued_days' => array(
							'type'        => 'array',
							'maxItems'    => 7,
							'items'       => array(
								'type'        => 'string',
								'format'      => 'date',
								'description' => _x( 'UTC calendar day acknowledged by Cloud.', 'text', 'nelio-content' ),
							),
							'description' => _x( 'Days whose scheduling requests Cloud acknowledged. These are not message IDs or a count of generated messages.', 'text', 'nelio-content' ),
						),
						'reason'      => array(
							'type'        => 'string',
							'enum'        => array( '', 'already_scheduled', 'no_candidates' ),
							'description' => _x( 'Reason for no_change, or an empty string when requests were queued.', 'text', 'nelio-content' ),
						),
					),
					array( 'status', 'queued_days', 'reason' ),
					_x( 'Accepted requests to generate reshare messages for the listed days. Inspect the calendar later to see messages actually generated.', 'text', 'nelio-content' )
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
		return 'nelio-content/generate-reshare-timeline';
	}
}

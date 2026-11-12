<?php
/** Premium bounded action discovery contract. */
defined( 'ABSPATH' ) || exit;

/** Lists saved actions by explicit post or calendar scope. */
class Nelio_Content_List_Future_Actions_Ability extends Nelio_Content_Future_Action_Ability {
	/** @return void */
	public function register() {
		$properties                           = array_intersect_key( $this->query_properties(), array_flip( array( 'post_id', 'start_date', 'end_date', 'cursor', 'per_page' ) ) );
		$properties['post_id']['description'] = 'Readable and editable target post with future actions enabled. Searching by post includes unscheduled actions and cannot be combined with dates.';
		wp_register_ability(
			'nelio-content/list-future-actions',
			array(
				'label'               => _x( 'List future actions', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium. Choose exactly one way to search: post_id for all its saved actions including unscheduled ones, or start_date and end_date for a site-local date range spanning at most 31 days. Searching by dates uses the saved WordPress record date, as the calendar does, and omits actions without a saved schedule; it does not filter by WP-Cron or recalculate dates. Records are examined in ascending action ID, up to 100 records per page before checking access and validating the saved action data. Records the current user cannot access, records with no valid related post and records with invalid saved data are left out without identities or totals. Continue with next_cursor after empty pages while has_more is true. Keep the selected post or date range and per_page unchanged. Cursor values are valid only for the same user and configuration. The search can skip at most 100000 records, and records may change between page requests. Each action requires read/edit permission on both record and enabled target. Custom-field names and values are not included. A scheduled event or an action marked as executed does not prove that the requested change succeeded. No writes, execution, scheduling, rendering or external requests occur.',
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( $properties, array(), 'Either post_id without dates, or both start_date and end_date without post_id; optional page size and continuation values.' ),
				'output_schema'       => $this->page_schema( $this->action_schema(), 'One page of validated accessible saved actions. has_more means there are more records to check, not that the user can access more actions.' ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-future-actions';
	}
}

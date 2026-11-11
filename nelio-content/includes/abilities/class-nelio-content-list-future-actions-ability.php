<?php
/** Premium bounded action discovery contract. */
defined( 'ABSPATH' ) || exit;

/** Lists saved actions by explicit post or calendar scope. */
class Nelio_Content_List_Future_Actions_Ability extends Nelio_Content_Future_Action_Ability {
	/** @return void */
	public function register() {
		$properties                           = array_intersect_key( $this->query_properties(), array_flip( array( 'post_id', 'start_date', 'end_date', 'cursor', 'per_page' ) ) );
		$properties['post_id']['description'] = _x( 'Readable and editable target post with future actions enabled. This scope includes unscheduled actions and cannot be combined with dates.', 'text', 'nelio-content' );
		wp_register_ability(
			'nelio-content/list-future-actions',
			array(
				'label'               => _x( 'List future actions', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. Choose exactly one scope: post_id for all its saved actions including unscheduled ones, or start_date and end_date for a site-local date range spanning at most 31 days. Date scope uses the saved WordPress record date, as the calendar does, and omits actions without a saved schedule; it does not filter by WP-Cron or recalculate dates. Records are examined in ascending action ID, up to 100 per source page, before access and payload checks. Inaccessible, orphaned, standalone and corrupt records are omitted without identities or totals. Continue with next_cursor after empty pages while has_more is true. Keep scope and per_page unchanged. Cursors are user/configuration-bound and support offsets up to 100000; pagination is not a snapshot. Each action requires read/edit permission on both record and enabled target. Custom-field keys/values are redacted. Observed scheduling and recorded execution do not prove success. No writes, execution, scheduling, rendering or external requests occur.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-future-actions',
				'input_schema'        => $this->object_schema( $properties, array(), _x( 'Either post_id without dates, or both start_date and end_date without post_id; optional bounded pagination.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->page_schema( $this->action_schema(), _x( 'One page of validated accessible saved actions. has_more describes remaining source records, not a visible-action total.', 'text', 'nelio-content' ) ),
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

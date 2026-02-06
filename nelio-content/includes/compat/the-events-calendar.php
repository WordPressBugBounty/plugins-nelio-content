<?php
namespace Nelio_Content\Compat\The_Events_Calendar;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add events from the events calendar.
 *
 * @param list<TInternal_Event> $events Events.
 *
 * @return list<TInternal_Event>
 */
function include_events( $events ) {
	/** @var \wpdb $wpdb */
	global $wpdb;

	nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) {
		return $events;
	}

	/** @var list<\WP_Post>|null $results */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i p WHERE p.post_type = %s',
			$wpdb->posts,
			'tribe_events'
		)
	);
	if ( empty( $results ) ) {
		return $events;
	}

	$found_events = array_map( fn( $d ) => post_to_internal_event( $d ), $results );
	$found_events = array_values( array_filter( $found_events ) );
	return array_merge( $events, $found_events );
}
add_filter( 'nelio_content_internal_events', __NAMESPACE__ . '\include_events' );

/**
 * Converts post to internal event.
 *
 * @param \WP_Post $data Data.
 *
 * @return TInternal_Event|false
 */
function post_to_internal_event( $data ) {
	$id         = absint( $data->ID );
	$start_date = get_post_meta( $id, '_EventStartDateUTC', true );
	$end_date   = get_post_meta( $id, '_EventEndDateUTC', true );
	if ( ! is_string( $start_date ) || ! is_string( $end_date ) ) {
		return false;
	}

	if ( ! current_user_can( 'read_post', $data->ID ) ) {
		return false;
	}

	return array(
		'id'              => 'tribe_event-' . $id,
		'backgroundColor' => '#334aff',
		'color'           => '#fff',
		'date'            => $start_date . ' +00:00',
		'description'     => $data->post_excerpt,
		'editLink'        => admin_url( 'post.php?post=' . $id . '&action=edit' ),
		'end'             => $end_date . ' +00:00',
		'isDayEvent'      => get_post_meta( $id, '_EventAllDay', true ) === 'yes',
		'start'           => $start_date . ' +00:00',
		'title'           => $data->post_title,
		'type'            => 'the-events-calendar',
	);
}

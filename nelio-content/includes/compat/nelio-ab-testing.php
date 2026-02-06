<?php
namespace Nelio_Content\Compat\Nelio_AB_Testing;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add tests.
 *
 * @param list<TInternal_Event> $events Events.
 *
 * @return list<TInternal_Event>
 */
function include_nab_tests( $events ) {
	/** @var \wpdb $wpdb */
	global $wpdb;

	nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( 'nelio-ab-testing/nelio-ab-testing.php' ) ) {
		return $events;
	}

	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'edit_nab_experiments' ) ) {
		return $events;
	}

	/** @var list<\WP_Post>|null $results */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM %i p
				WHERE
					p.post_type = 'nab_experiment' AND
					p.post_status <> 'trash' AND
					p.post_status <> 'nab_paused'",
			$wpdb->posts
		)
	);
	if ( empty( $results ) ) {
		return $events;
	}

	$found_exps = array_map(
		function ( $data ) {
			$id = absint( $data->ID );
			return nab_get_experiment( $id );
		},
		$results
	);
	/* @var array<\Nelio_AB_Testing_Experiment> $found_exps */
	$found_exps = array_filter( $found_exps, fn( $e ) => ! is_wp_error( $e ) );

	$found_events = array_map( fn( $e ) => experiment_to_event( $e ), $found_exps );
	$found_events = array_values( array_filter( $found_events ) );

	return array_merge( $events, $found_events );
}
add_filter( 'nelio_content_internal_events', __NAMESPACE__ . '\include_nab_tests' );

// =======
// HELPERS
// =======

/**
 * Gets experiment end date.
 *
 * @param \Nelio_AB_Testing_Experiment $exp Experiment.
 *
 * @return string
 */
function get_end_date( $exp ) {
	if ( ! empty( $exp->get_end_date() ) ) {
		return $exp->get_end_date();
	}

	$start_date = $exp->get_start_date();
	if ( ! empty( $start_date ) && $exp->get_end_mode() === 'duration' ) {
		$days = $exp->get_end_value();
		$time = absint( strtotime( $start_date ) );
		return gmdate( 'c', $time + ( $days * DAY_IN_SECONDS ) );
	}

	return 'future';
}

/**
 * Converts experiment into internal event.
 *
 * @param \Nelio_AB_Testing_Experiment $exp Experiment.
 *
 * @return TInternal_Event|false
 */
function experiment_to_event( $exp ) {
	$start_date = $exp->get_start_date();
	if ( empty( $start_date ) ) {
		return false;
	}

	$end_date     = get_end_date( $exp );
	$is_day_event = 'future' === $end_date ? true : substr( $start_date, 0, 10 ) === substr( $end_date, 0, 10 );

	return array(
		'id'              => 'nab-' . $exp->get_id(),
		'date'            => $start_date,
		'start'           => $start_date,
		'end'             => $end_date,
		'description'     => $exp->get_description(),
		'color'           => '#fff',
		'backgroundColor' => '#ac3626',
		'editLink'        => $exp->get_url(),
		'isDayEvent'      => $is_day_event,
		'title'           => $exp->get_name(),
		'type'            => 'nelio-ab-testing',
	);
}

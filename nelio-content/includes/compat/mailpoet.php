<?php
namespace Nelio_Content\Compat\Mailpoet;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add Mailpoet’s newsletters.
 *
 * @param list<TInternal_Event> $events Events.
 *
 * @return list<TInternal_Event>
 */
function include_newsletters( $events ) {
	/** @var \wpdb $wpdb */
	global $wpdb;

	nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
	if ( ! is_plugin_active( 'mailpoet/mailpoet.php' ) ) {
		return $events;
	}

	/** @var list<object{newsletter_id:string,subject:string,preheader:string,status:string,value:string,sent_at:string}>|null $results */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM %i n, %i o
				WHERE
					n.type = 'standard' AND
					n.deleted_at IS NULL AND
					o.newsletter_id = n.id AND
					o.option_field_id = 2",
			"{$wpdb->prefix}mailpoet_newsletters",
			"{$wpdb->prefix}mailpoet_newsletter_option"
		)
	);
	if ( empty( $results ) ) {
		return $events;
	}

	$newsletters = array_map( fn( $n ) => newsletter_to_event( $n ), $results );
	$newsletters = array_values( array_filter( $newsletters ) );
	return array_merge( $events, $newsletters );
}
add_filter( 'nelio_content_internal_events', __NAMESPACE__ . '\include_newsletters' );

/**
 * Converts a newsletter to an internal event.
 *
 * @param object{newsletter_id:string,subject:string,preheader:string,status:string,value:string,sent_at:string} $data Data.
 *
 * @return TInternal_Event|false
 */
function newsletter_to_event( $data ) {
	$sent_at = ! empty( $data->sent_at ) ? strtotime( $data->sent_at ) : false;
	$sent_at = false !== $sent_at ? gmdate( 'c', $sent_at ) : null;

	$date = strtotime( $data->value );
	$date = false !== $date ? gmdate( 'c', $date ) : null;
	$date = ! empty( $sent_at ) ? $sent_at : $date;

	if ( empty( $date ) ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'mailpoet_manage_emails' ) ) {
		return false;
	}

	return array(
		'id'              => 'mp-newsletter-' . absint( $data->newsletter_id ),
		'date'            => $date,
		'description'     => $data->preheader,
		'color'           => '#fff',
		'backgroundColor' => '#ff6900',
		'editLink'        => admin_url( 'admin.php?page=mailpoet-newsletter-editor&id=' . absint( $data->newsletter_id ) ),
		'isDayEvent'      => false,
		'title'           => $data->subject,
		'type'            => 'mailpoet-newsletter',
	);
}

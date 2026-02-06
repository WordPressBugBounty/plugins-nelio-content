<?php
/**
 * Nelio Content core functions.
 *
 * General core functions available on both the front-end and admin.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns this site's ID.
 *
 * @return string This site's ID. This option is used for accessing AWS.
 *
 * @since 4.0.8
 */
function nelio_content_get_site_id() {
	return get_option( 'nc_site_id', '' );
}

/**
 * Returns the limits the plugin has, based on the current subscription and so on.
 *
 * @return TSite_Limits the limits the plugin has.
 *
 * @since 4.0.8
 */
function nelio_content_get_site_limits() {
	$limits = get_option( 'nc_site_limits', array() );
	return array(
		'maxAutomationGroups'   => $limits['maxAutomationGroups'] ?? 1,
		'maxProfiles'           => $limits['maxProfiles'] ?? -1,
		'maxProfilesPerNetwork' => $limits['maxProfilesPerNetwork'] ?? 1,
	);
}

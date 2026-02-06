<?php
/**
 * The plugin bootstrap file.
 *
 * Plugin Name:       Nelio Content - Editorial Calendar & Social Media Auto-Posting
 * Plugin URI:        https://neliosoftware.com/content/
 * Description:       Auto-post, schedule, and share your posts on Twitter, Facebook, LinkedIn, Instagram, and other social networks. Save time with useful automations.
 * Version:           4.2.1
 *
 * Author:            Nelio Software
 * Author URI:        https://neliosoftware.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Requires at least: 6.7
 * Requires PHP:      7.4
 *
 * Text Domain:       nelio-content
 */

defined( 'ABSPATH' ) || exit;

define( 'NELIO_CONTENT', true );
require untrailingslashit( __DIR__ ) . '/class-nelio-content.php';

/**
 * Returns the unique instance of Nelio Content’s main class.
 *
 * @return Nelio_Content unique instance of Nelio Content’s main class.
 *
 * Since @2.0.0
 */
function nelio_content() {
	return Nelio_Content::instance();
}

// Start plugin.
nelio_content();

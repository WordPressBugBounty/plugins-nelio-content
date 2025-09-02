<?php
/**
 * This file defines some additional hooks to make Nelio Content compatible with third-party plugins and themes.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/compat
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/advanced-custom-fields.php';
require_once __DIR__ . '/divi.php';
require_once __DIR__ . '/elementor.php';
require_once __DIR__ . '/flamingo.php';
require_once __DIR__ . '/mailpoet.php';
require_once __DIR__ . '/pagefrog.php';
require_once __DIR__ . '/the-events-calendar.php';
require_once __DIR__ . '/nelio-ab-testing.php';
require_once __DIR__ . '/nelio-forms.php';
require_once __DIR__ . '/user-submitted-posts.php';
require_once __DIR__ . '/woocommerce.php';
require_once __DIR__ . '/wpml.php';

<?php
/**
 * List of settings.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/data
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

return array(

	array(
		'type'     => 'custom',
		'name'     => 'social_post_types',
		'label'    => esc_html_x( 'Shareable Content', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'social_post_types',
				'help' => _x(
					'Enable social media capabilities for these post types and share content easily with your followers.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'    => 'select',
		'name'    => 'auto_share_default_mode',
		'label'   => esc_html_x( 'Automatic Social Sharing', 'text', 'nelio-content' ),
		'desc'    => esc_html_x( 'Nelio Content can automatically share content on your social media according to your preferences:', 'text', 'nelio-content' ),
		'default' => 'include-in-auto-share',
		'options' => array(
			array(
				'value' => 'include-in-auto-share',
				'label' => esc_html_x( 'Include all posts, unless stated otherwise', 'command', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content can automatically share any post on your social profiles, unless you’ve explicitly excluded it from resharing.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'exclude-from-auto-share',
				'label' => esc_html_x( 'Exclude all posts, unless stated otherwise', 'command', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content will only automatically share those posts that you’ve manually marked as eligible for automatic sharing.', 'text', 'nelio-content' ),
			),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'are_meta_tags_active',
		'label'   => esc_html_x( 'Meta', 'text', 'nelio-content' ),
		'desc'    => esc_html_x( 'Add Facebook’s Open Graph and X’s Card meta tags on shared content from your site', 'command', 'nelio-content' ),
		'default' => false,
	),

	array(
		'type'     => 'custom',
		'name'     => 'cloud_notification_emails',
		'label'    => esc_html_x( 'Cloud Notifications', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_Cloud_Notification_Emails_Setting(),
		'default'  => '',
	),

);

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

return array(

	// =========================================================================
	// =========================================================================
	array(
		'type'  => 'section',
		'name'  => 'misc-section',
		'label' => nc_make_settings_title( esc_html_x( 'Miscellaneous', 'text', 'nelio-content' ), 'admin-tools' ),
	),
	// =========================================================================
	// =========================================================================

	array(
		'type'    => 'checkbox',
		'name'    => 'use_missed_schedule_handler',
		'label'   => esc_html_x( 'Missed Schedule Handler', 'text', 'nelio-content' ),
		'desc'    => esc_html_x( 'Check for scheduled WordPress posts not properly published with a “missed schedule” error and automatically publish them', 'command', 'nelio-content' ),
		'default' => false,
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_feeds',
		'label'   => esc_html_x( 'Feeds', 'text', 'nelio-content' ),
		'desc'    => _x( 'Show feeds section to keep track of publications on other websites', 'command', 'nelio-content' ),
		'default' => false,
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'are_auto_tutorials_enabled',
		'label'   => esc_html_x( 'Tutorials', 'text', 'nelio-content' ),
		'desc'    => _x( 'Show plugin tutorials automatically to introduce new users to Nelio Content’s features', 'command', 'nelio-content' ),
		'default' => true,
	),

	// =========================================================================
	// =========================================================================
	array(
		'type'  => 'section',
		'name'  => 'efi-section',
		'label' => nc_make_settings_title( esc_html_x( 'External Featured Images', 'text', 'nelio-content' ), 'format-image' ),
	),
	// =========================================================================
	// =========================================================================

	array(
		'type'     => 'custom',
		'name'     => 'efi_post_types',
		'label'    => esc_html_x( 'Compatible Content', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'efi_post_types',
				'help' => _x(
					'Insert an external image as the featured image of a compatible post type just by indicating its URL.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'    => 'select',
		'name'    => 'efi_mode',
		'label'   => esc_html_x( 'Mode', 'text', 'nelio-content' ),
		'desc'    => _x( 'Themes can insert featured images in different ways. For example, some themes use a WordPress function named <code>(get_)the_post_thumbnail</code> whereas others use a combination of <code>wp_get_attachment_image_src</code> and <code>get_post_thumbnail_id</code>. Depending on how your theme operates, Nelio Content may or may not be compatible with it. In order to maximize the number of compatible themes, the plugin implements different <em>modes</em>.', 'html', 'nelio-content' ),
		'default' => 'default',
		'options' => array(
			array(
				'value' => 'default',
				'label' => esc_html_x( 'Default Mode', 'text', 'nelio-content' ),
				'desc'  => _x( 'This mode assumes your theme uses the function <code>(get_)the_post_thumbnail</code> for inserting featured images. For example, WordPress default themes should work with this setting.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'double-quotes',
				'label' => esc_html_x( 'Double-Quote Mode', 'text', 'nelio-content' ),
				'desc'  => _x( 'If your theme retrieves the URL of the featured image and outputs it within an <code>img</code> tag, this mode might be the one you need. Compatible themes include Newspaper, Newsmag, Enfold, and others.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'single-quotes',
				'label' => esc_html_x( 'Single-Quote Mode', 'text', 'nelio-content' ),
				'desc'  => esc_html_x( 'Equivalent to “Double-Quote Mode,” but using single quotes instead.', 'text', 'nelio-content' ),
			),
		),
	),

	array(
		'type'    => 'select',
		'name'    => 'auto_feat_image',
		'label'   => esc_html_x( 'Autoset Featured Image', 'text', 'nelio-content' ),
		'desc'    => esc_html_x( 'If a post doesn’t have a featured image set, Nelio Content can set it automatically for you. To do this, it looks for all the images included in the post and uses one of them as the featured image.', 'text', 'nelio-content' ),
		'default' => 'disabled',
		'options' => array(
			array(
				'value' => 'disabled',
				'label' => esc_html_x( 'Disabled', 'text', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content doesn’t set the featured image automatically.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'first',
				'label' => esc_html_x( 'Use First Image in Post', 'text', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content will use the first image included in the post.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'any',
				'label' => esc_html_x( 'Use Any Image In Post', 'text', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content will use one of the images included in the post, selecting it randomly. If there are more than two images, Nelio Content will ignore the first and the last image.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'last',
				'label' => esc_html_x( 'Use Last Image In Post', 'text', 'nelio-content' ),
				'desc'  => esc_html_x( 'Nelio Content will use the last image included in the post.', 'text', 'nelio-content' ),
			),
		),
	),

);

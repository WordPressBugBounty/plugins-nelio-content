<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

return array(

	array(
		'type'     => 'custom',
		'name'     => 'task_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Editorial Tasks', 'text', 'nelio-content' ), 'flag' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'task_post_types',
				'help' => _x(
					'Editorial tasks are activites that someone (either you or a member in your team) has to get done before its due date. If you want tasks to be related to certain posts, enable the appropriate post types here.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_task_notifications',
		'label'   => '',
		'desc'    => esc_html_x( 'Send email notifications when editorial tasks are created or completed', 'command', 'nelio-content' ),
		'default' => false,
	),

	array(
		'type'     => 'custom',
		'name'     => 'comment_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Editorial Comments', 'text', 'nelio-content' ), 'admin-comments' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'comment_post_types',
				'help' => _x(
					'With editorial comments, the members of your team and you can discuss anything about a certain post within the context of that post. Enable editorial comments for these post types.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_comment_notifications',
		'label'   => '',
		'desc'    => esc_html_x( 'Send email notifications when editorial comments are added', 'command', 'nelio-content' ),
		'default' => false,
	),

	array(
		'type'     => 'custom',
		'name'     => 'quality_check_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Quality Checks', 'text', 'nelio-content' ), 'saved' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'quality_check_post_types',
				'help' => _x(
					'Nelio Content can analyze the quality of your posts according to different criteria and help you improve the overall quality of your website. Select the post types you’d like to get help with.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'future_action_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Future Actions', 'text', 'nelio-content' ), 'admin-tools' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'future_action_post_types',
				'help' => _x(
					'Future actions allow to schedule changes to your posts, pages and other content types. If you want to set future actions to certain posts, enable the appropriate post types here.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'reference_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Editorial References', 'text', 'nelio-content' ), 'admin-links' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'reference_post_types',
				'help' => _x(
					'Editorial references keep track of all those links that you’d like to link from your content or might inspire you to write better content. Enable them for these post types.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'notification_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Notification Emails', 'text', 'nelio-content' ), 'email' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'notification_post_types',
				'help' => _x(
					'Enable notifications to let your team members know when there’s some relevant activity in any of these post types.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

);

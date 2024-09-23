<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

return array(

	array(
		'type'     => 'custom',
		'name'     => 'calendar_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Editorial Calendar', 'text', 'nelio-content' ), 'calendar-alt' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name'        => 'calendar_post_types',
				'help'        => _x(
					'Post types that will show up in Nelio Content’s Editorial Calendar.',
					'text',
					'nelio-content'
				),
				'isMandatory' => true,
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'use_ics_subscription',
		'label'    => esc_html_x( 'iCal Calendar Feed', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_ICS_Calendar_Setting(),
		'default'  => false,
	),

	array(
		'type'     => 'custom',
		'name'     => 'content_board_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Content Board', 'text', 'nelio-content' ), 'columns' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'content_board_post_types',
				'help' => _x(
					'Post types that will show up in the Content Board. If no post types are selected, this feature will be disabled.',
					'text',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'analytics_post_types',
		'label'    => nc_make_settings_title( esc_html_x( 'Analytics', 'text', 'nelio-content' ), 'chart-bar' ),
		'instance' => new Nelio_Content_Post_Type_Setting(
			array(
				'name' => 'analytics_post_types',
				'help' => _x(
					'Retrieve relevant analytics from Google Analytics, as well as social media, for the posts included in these post types. If no post types are selected, this feature will be disabled.',
					'user',
					'nelio-content'
				),
			)
		),
		'default'  => array( 'post' ),
	),

	array(
		'type'     => 'custom',
		'name'     => 'ga4_property_id',
		'label'    => '',
		'instance' => new Nelio_Content_Google_Analytics_Setting(),
		'default'  => '',
	),

);

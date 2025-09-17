<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

return array(

	// =========================================================================
	// =========================================================================
	array(
		'type'  => 'section',
		'name'  => 'series-taxonomy-section',
		'label' => nc_make_settings_title( esc_html_x( 'Series Taxonomy', 'text', 'nelio-content' ), 'category' ),
	),
	// =========================================================================
	// =========================================================================

	array(
		'type'    => 'text',
		'name'    => 'series_taxonomy_slug',
		'label'   => esc_html_x( 'Series Taxonomy Slug', 'text', 'nelio-content' ),
		'desc'    => esc_html_x( 'This feature allows you to use a different taxonomy for this plugin if you don’t want to use the default "Series" taxonomy.', 'user', 'nelio-content' ),
		'default' => 'series',
	),

	// =========================================================================
	// =========================================================================
	array(
		'type'  => 'section',
		'name'  => 'series-frontend-data-section',
		'label' => nc_make_settings_title( esc_html_x( 'Series Frontend Display', 'text', 'nelio-content' ), 'welcome-view-site' ),
	),
	// =========================================================================
	// =========================================================================

	array(
		'type'    => 'select',
		'name'    => 'series_archive_order',
		'label'   => esc_html_x( 'Series Archive Order Method', 'text', 'nelio-content' ),
		'desc'    => _x( 'Posts in a series archive page are listed according to an order method depending on their part in the series and their date. This selector allows you to choose the order method between the following:', 'text', 'nelio-content' ),
		'default' => 'ASC',
		'options' => array(
			array(
				'value' => 'ASC',
				'label' => esc_html_x( 'Ascending', 'text', 'nelio-content' ),
				'desc'  => _x( 'List posts by part in ascending order, but for those posts that do not have a part number set, it orders them by date in ascending order.', 'text', 'nelio-content' ),
			),
			array(
				'value' => 'DESC',
				'label' => esc_html_x( 'Descending', 'text', 'nelio-content' ),
				'desc'  => _x( 'List posts by part in descending order, but for those posts that do not have a part number set, it orders them by date in descending order.', 'text', 'nelio-content' ),
			),
		),
	),

	array(
		'type'     => 'custom',
		'name'     => 'series_metadata',
		'label'    => esc_html_x( 'Series Metadata', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_Series_Metadata_Setting(),
		'default'  => array(
			'isActive' => true,
			'location' => 'top',
			'template' => sprintf(
				/* translators: %1$s: HTML wrapper opening tag. %2$s: Number. %3$s: Number. %4$s: Link. %5$s: HTML wrapper closing tag. */
				_x( '%1$sThis entry is part %2$s of %3$s in the series %4$s%5$s', 'text', 'nelio-content' ),
				'<div class="nelio-content-series-meta">',
				'{series_part_number}',
				'{total_posts_in_series}',
				'{series_title_linked}',
				'</div>'
			),
		),
	),

	array(
		'type'     => 'custom',
		'name'     => 'series_post_list',
		'label'    => esc_html_x( 'Series Post List', 'text', 'nelio-content' ),
		'instance' => new Nelio_Content_Series_Post_List_Setting(),
		'default'  => array(
			'isActive'            => true,
			'location'            => 'top',
			'generalTemplate'     => '<div class="nelio-content-series-post-list"><div class="nelio-content-series-post-list__title">{series_title_linked}</div><ul class="nelio-content-series-post-list__list">{series_post_list}</ul></div>',
			'currentPostTemplate' => '<li class="nelio-content-series-post-list__item--current">{post_title}</li>',
			'otherPostTemplate'   => '<li class="nelio-content-series-post-list__item">{post_title_linked}</li>',
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_series_default_styles',
		'label'   => esc_html_x( 'Frontend Styles', 'text', 'nelio-content' ),
		'desc'    => _x( 'Use Nelio Content default CSS styles', 'command', 'nelio-content' ),
		'default' => true,
	),

);

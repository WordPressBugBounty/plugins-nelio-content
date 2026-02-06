<?php

namespace Nelio_Content\Admin\Views\Overview_Dashboard_Widget;

use function Nelio_Content\Helpers\key_by;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add the overview widget.
 *
 * @return void
 */
function add_widget() {
	wp_add_dashboard_widget(
		'nelio-content-dashboard-overview',
		_x( 'Nelio Content Overview', 'text', 'nelio-content' ),
		__NAMESPACE__ . '\render_widget'
	);

	// Move our widget to top.
	global $wp_meta_boxes;
	if (
		! is_array( $wp_meta_boxes ) ||
			! is_array( $wp_meta_boxes['dashboard'] ) ||
			! is_array( $wp_meta_boxes['dashboard']['normal'] ) ||
			! is_array( $wp_meta_boxes['dashboard']['normal']['core'] )
	) {
		return;
	}

	$dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	if ( empty( $dashboard['nelio-content-dashboard-overview'] ) ) {
		return;
	}

	$ours = array( 'nelio-content-dashboard-overview' => $dashboard['nelio-content-dashboard-overview'] );
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $ours, $dashboard );
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\add_widget' );

/**
 * Callback to enqueue assets.
 *
 * @return void
 */
function enqueue_assets() {
	$screen = get_current_screen();
	if ( empty( $screen ) || 'dashboard' !== $screen->id ) {
		return;
	}
	wp_enqueue_style(
		'nelio-content-dashboard-page',
		nelio_content()->plugin_url . '/assets/dist/css/dashboard-page.css',
		array( 'nelio-content-components' ),
		nelio_content_get_script_version( 'dashboard-page' )
	);

	nelio_content_enqueue_script_with_auto_deps( 'nelio-content-dashboard-page', 'dashboard-page', true );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );

/**
 * AJAX callback to retrieve and return the news.
 *
 * @return void
 */
function fetch_news() {
	$news = get_news( 'fetch' );
	if ( empty( $news ) ) {
		echo '';
		die();
	}

	printf( '<h3>%s</h3>', esc_html_x( 'News & Updates', 'text', 'nelio-content' ) );
	echo '<ul>';
	array_walk( $news, __NAMESPACE__ . '\render_single_news' );
	echo '</ul>';
	die();
}
add_action( 'wp_ajax_nelio_content_fetch_news', __NAMESPACE__ . '\fetch_news' );

/**
 * Callback function to render the widget.
 *
 * @return void
 */
function render_widget() {
	render_title();
	render_posts();
	render_news();
	render_actions();
}

/**
 * Callback function to render the widget’s title.
 *
 * @return void
 */
function render_title() {
	nelio_content_require_wp_file( '/wp-admin/includes/class-wp-filesystem-base.php' );
	nelio_content_require_wp_file( '/wp-admin/includes/class-wp-filesystem-direct.php' );
	$filesystem = new \WP_Filesystem_Direct( true );
	$icon       = $filesystem->get_contents( nelio_content()->plugin_path . '/assets/dist/images/logo.svg' );
	$icon       = is_string( $icon ) ? $icon : '';
	$icon       = str_replace( 'fill="inherit"', 'fill="currentcolor"', $icon );
	$icon       = str_replace( 'width="20"', '', $icon );
	$icon       = str_replace( 'height="20"', '', $icon );
	printf(
		'<div class="nelio-content-header"><div class="nelio-content-header__icon">%s</div><div class="nelio-content-header__version"><p>%s</p><p>%s</p></div></div>',
		wp_kses(
			$icon,
			array(
				'svg'  => array(
					'version' => true,
					'xmlns'   => true,
					'viewbox' => true,
				),
				'path' => array(
					'd'    => true,
					'fill' => true,
				),
			)
		),
		esc_html( 'Nelio Content v' . nelio_content()->plugin_version ),
		/**
		 * Filters the extra version in overview widget.
		 *
		 * @param string $version Extra version. Default: empty string.
		 *
		 * @since 6.2.0
		 */
		esc_html( apply_filters( 'nelio_content_extra_version_in_overview_widget', '' ) )
	);
}

/**
 * Callback function to render the latest posts (if any) inside the widget.
 *
 * @return void
 */
function render_posts() {
	$posts = get_last_posts();
	if ( empty( $posts ) ) {
		return;
	}
	echo '<div class="nelio-content-posts">';
	printf( '<h3>%s</h3>', esc_html_x( 'Recently Updated', 'text (tests)', 'nelio-content' ) );
	echo '<ul>';
	array_walk( $posts, __NAMESPACE__ . '\render_post' );
	echo '</ul>';
	echo '</div>';
}

/**
 * Callback function to render the news.
 *
 * @return void
 */
function render_news() {
	$news = get_news( 'cache' );
	if ( empty( $news ) ) {
		echo '<div class="nelio-content-news"><div class="spinner is-active"></div></div>';
		printf(
			'<script type="text/javascript">fetch(%s).then((r)=>r.text()).then((d)=>{document.querySelector(".nelio-content-news").innerHTML=d;})</script>',
			wp_json_encode( add_query_arg( 'action', 'nelio_content_fetch_news', admin_url( 'admin-ajax.php' ) ) )
		);
		return;
	}

	echo '<div class="nelio-content-news">';
	printf( '<h3>%s</h3>', esc_html_x( 'News & Updates', 'text', 'nelio-content' ) );
	echo '<ul>';
	array_walk( $news, __NAMESPACE__ . '\render_single_news' );
	echo '</ul>';
	echo '</div>';
}

/**
 * Callback function to render the widget actions.
 *
 * @return void
 */
function render_actions() {
	echo '<div class="nelio-content-actions">';
	if ( nelio_content_can_current_user_use_plugin() ) {
		printf(
			'<span><a href="%s">%s</a></span>',
			esc_url( add_query_arg( 'page', 'nelio-content', admin_url( 'admin.php' ) ) ),
			esc_html_x( 'Editorial Calendar', 'text', 'nelio-content' )
		);
	}

	printf(
		'<span><a href="%s" target="_blank">%s <span class="dashicons dashicons-external"></span></a></span>',
		esc_url(
			add_query_arg(
				array(
					'utm_source'   => 'nelio-content',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'support',
					'utm_content'  => 'overview-widget',
				),
				'https://neliosoftware.com/blog'
			)
		),
		esc_html_x( 'Blog', 'text', 'nelio-content' )
	);

	printf(
		'<span><a href="%s" target="_blank">%s <span class="dashicons dashicons-external"></span></a></span>',
		esc_url(
			add_query_arg(
				array(
					'utm_source'   => 'nelio-content',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'support',
					'utm_content'  => 'overview-widget',
				),
				'https://neliosoftware.com/content/help'
			)
		),
		esc_html_x( 'Help', 'text', 'nelio-content' )
	);
	echo '</div>';
}

/**
 * Returns the latest posts.
 *
 * @return list<\WP_Post>
 */
function get_last_posts() {
	$post_types = nelio_content_get_post_types( 'calendar' );
	$statuses   = array_map( fn( $t ) => nelio_content_get_post_statuses( $t ), $post_types );
	$statuses   = \Nelio_Content\Helpers\flatten( $statuses );
	$statuses   = array_map( fn( $s ) => $s['slug'], $statuses );
	$statuses   = array_values( array_unique( $statuses ) );
	return array_values(
		get_posts(
			array(
				'post_type'   => $post_types,
				'count'       => 5,
				'post_status' => $statuses,
			)
		)
	);
}

/**
 * Renders the given post.
 *
 * @param \WP_Post $p Experiment.
 *
 * @return void
 */
function render_post( \WP_Post $p ) {
	$title  = trim( $p->post_title );
	$title  = empty( $title ) ? esc_html_x( 'Unnamed post', 'text', 'nelio-content' ) : $title;
	$format = esc_html_x( 'M d, h:ia', 'PHP datetime format', 'nelio-content' );
	$date   = get_the_modified_date( $format, $p->ID );
	$date   = is_string( $date ) ? $date : '';

	$post_type = get_post_type_object( $p->post_type );
	$post_type = empty( $post_type ) ? '' : $post_type->labels->singular_name;
	$post_type = is_string( $post_type ) && 0 < strlen( $post_type ) ? "| {$post_type}" : '';

	$default_icon = 'publish' === $p->post_status ? 'visibility' : 'edit';
	$statuses     = \nelio_content_get_post_statuses( $p->post_type );
	$statuses     = key_by( $statuses, 'slug' );
	$icon         = ! empty( $statuses[ $p->post_status ]['icon'] )
		? $statuses[ $p->post_status ]['icon']
		: $default_icon;

	echo '<li class="nelio-content-post">';

	if ( 'publish' === $p->post_status ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( get_permalink( $p ) ),
			esc_html( $title )
		);
	} elseif ( current_user_can( 'edit_post', $p->ID ) ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $p ) ?? '' ),
			esc_html( $title )
		);
	} else {
		printf( '<span>%s</span>', esc_html( $title ) );
	}

	printf(
		' <span class="nelio-content-post__type">%s</span> <span class="nelio-content-post__status-icon" data-icon="%s"><svg></svg></span> <span class="nelio-content-post__date">%s</span>',
		esc_html( $post_type ),
		esc_attr( $icon ),
		esc_html( $date )
	);

	echo '</li>';
}

/**
 * Retrieves the latest news from Nelio Software’s blog.
 *
 * @param 'fetch'|'cache' $mode Where to get the data from.
 *
 * @return list<array{
 *   title: string,
 *   link: string,
 *   type: string,
 *   excerpt: string,
 * }>
 */
function get_news( $mode ) {
	if ( 'fetch' === $mode ) {
		$rss = fetch_feed( 'https://neliosoftware.com/overview-widget/?tag=nelio-content' );
		if ( is_wp_error( $rss ) ) {
			return array();
		}
		$news = $rss->get_items( 0, 3 );
		$news = is_array( $news ) ? $news : array();
		$news = array_map(
			function ( $n ) {
				return array(
					'title'   => $n->get_title(),
					'link'    => $n->get_permalink(),
					'type'    => $n->get_description(),
					'excerpt' => $n->get_content(),
				);
			},
			$news
		);
		set_transient( 'nelio_content_news', $news, WEEK_IN_SECONDS );
	}

	/**
	 * Type safety.
	 *
	 * @var list<array{
	 *   title: string,
	 *   link: string,
	 *   type: string,
	 *   excerpt: string,
	 * }>
	 */
	$news = get_transient( 'nelio_content_news' );
	return empty( $news ) ? array() : $news;
}

/**
 * Renders a news item.
 *
 * @param TNews $n News item.
 *
 * @return void
 *
 * @template TNews of array{
 *  title: string,
 *  link: string,
 *  type: string,
 *  excerpt: string,
 * }
 */
function render_single_news( $n ) {
	echo '<div class="nelio-content-single-news">';

	echo '<div class="nelio-content-single-news__header">';
	printf(
		'<span class="nelio-content-single-news__type nelio-content-single-news__type--is-%s">%s</span> ',
		esc_attr( $n['type'] ),
		esc_html(
			'release' === $n['type']
				? esc_html_x( 'NEW', 'text', 'nelio-content' )
				: esc_html_x( 'INFO', 'text', 'nelio-content' )
		)
	);
	printf(
		'<a class="nelio-content-single-news__title" href="%s" target="_blank">%s</a>',
		esc_url( $n['link'] ),
		esc_html( $n['title'] )
	);
	echo '</div>';

	printf(
		'<div class="nelio-content-single-news__excerpt">%s</div>',
		esc_html( $n['excerpt'] )
	);

	echo '</div>';
}

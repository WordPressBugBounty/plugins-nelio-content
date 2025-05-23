<?php
/**
 * Helper functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * Checks if the current user can manage the account or not.
 *
 * @return boolean whether the current user can manage the account or not.
 *
 * @since 2.0.0
 */
function nc_can_current_user_manage_account() {
	if ( ! nc_can_current_user_manage_plugin() ) {
		return false;
	}//end if

	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}//end if

	$can_manage = current_user_can( 'manage_options' );

	/**
	 * Filters whether the user can manage the account or not.
	 *
	 * @param boolean $can_manage whether the user can or can’t manage the account.
	 * @param id      $user_id    user id.
	 *
	 * @since 2.0.0
	 */
	$can_manage = apply_filters( 'nelio_content_can_user_manage_account', $can_manage, get_current_user_id() );
	return ! empty( $can_manage );
}//end nc_can_current_user_manage_account()

/**
 * Checks if the current user can manage the plugin or not.
 *
 * @return boolean whether the current user can manage the plugin or not.
 *
 * @since 2.0.0
 */
function nc_can_current_user_manage_plugin() {
	if ( ! nc_can_current_user_use_plugin() ) {
		return false;
	}//end if

	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}//end if

	$can_manage = current_user_can( 'edit_others_posts' );

	/**
	 * Filters whether the current user can or can’t manage the plugin.
	 *
	 * @param boolean $can_manage whether the user can or can’t manage the plugin.
	 * @param id      $user_id    user id.
	 *
	 * @since 2.0.0
	 */
	$can_manage = apply_filters( 'nelio_content_can_user_manage_plugin', $can_manage, get_current_user_id() );
	return ! empty( $can_manage );
}//end nc_can_current_user_manage_plugin()

/**
 * Checks if the current user can use the plugin or not.
 *
 * @return boolean whether the current user can use the plugin or not.
 *
 * @since 2.0.0
 */
function nc_can_current_user_use_plugin() {
	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}//end if

	/**
	 * Short-circuits the user’s ability to use the plugin.
	 *
	 * If set to `true`, the plugin won’t have access to the plugin. Otherwise, it’ll depend on their capabilities.
	 *
	 * @param boolean $revoke_access whether the user shouldn’t have access to the plugin.
	 * @param id      $user_id       the user.
	 *
	 * @since 2.0.0
	 */
	if ( apply_filters( 'nelio_content_revoke_plugin_access_to_user', false, get_current_user_id() ) ) {
		return false;
	}//end if

	$settings = Nelio_Content_Settings::instance();
	if ( ! $settings->are_ready() ) {
		return false;
	}//end if

	static $can_edit_managed_post_type;
	if ( is_bool( $can_edit_managed_post_type ) ) {
		return $can_edit_managed_post_type;
	}//end if

	$post_types = nelio_content_get_post_types( 'calendar' );
	foreach ( $post_types as $name ) {
		$type = get_post_type_object( $name );
		if ( empty( $type ) || is_wp_error( $type ) ) {
			continue;
		}//end if
		if ( current_user_can( $type->cap->edit_posts ) ) {
			$can_edit_managed_post_type = true;
			return true;
		}//end if
	}//end foreach

	$can_edit_managed_post_type = false;
	return false;
}//end nc_can_current_user_use_plugin()

/**
 * Generates a title for our settings screen.
 *
 * @param string $title the title of the section.
 * @param string $icon  a Dashicon identifier.
 *
 * @return string the title of the section.
 *
 * @since 2.0.0
 */
function nc_make_settings_title( $title, $icon ) {
	if ( empty( $icon ) ) {
		return $title;
	}//end if

	return sprintf(
		'<span class="dashicons dashicons-%s"></span> %s',
		$icon,
		$title
	);
}//end nc_make_settings_title()

/**
 * Registers a script loading the dependencies automatically.
 *
 * @param string  $handle    the script handle name.
 * @param string  $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param boolean $footer    whether the script should be included in the footer or not.
 *
 * @since 2.0.0
 */
function nc_register_script_with_auto_deps( $handle, $file_name, $footer ) {

	$asset = array(
		'dependencies' => array(),
		'version'      => nelio_content()->plugin_version,
	);

	if ( file_exists( nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php" ) ) {
		$asset = include nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	}//end if

	// NOTE. Add the regenerator-runtime to all our scripts to make sure AsyncPaginate works.
	if ( is_wp_version_compatible( '5.8' ) ) {
		$asset['dependencies'] = array_merge( $asset['dependencies'], array( 'regenerator-runtime' ) );
	}//end if

	wp_register_script(
		$handle,
		nelio_content()->plugin_url . "/assets/dist/js/$file_name.js",
		$asset['dependencies'],
		$asset['version'],
		$footer
	);

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( $handle, 'nelio-content' );
	}//end if
}//end nc_register_script_with_auto_deps()

/**
 * Returns the script version if available. If it isn’t, it defaults to the plugin’s version.
 *
 * @param string $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 *
 * @return string the version of the given script or the plugin’s version if the former wasn’t be found.
 *
 * @since 2.0.0
 */
function nc_get_script_version( $file_name ) {
	if ( ! file_exists( nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php" ) ) {
		return nelio_content()->plugin_version;
	}//end if
	$asset = include nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	return $asset['version'];
}//end nc_get_script_version()

/**
 * Enqueues a script loading the dependencies automatically.
 *
 * @param string  $handle    the script handle name.
 * @param string  $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param boolean $footer    whether the script should be included in the footer or not.
 *
 * @since 2.0.0
 */
function nc_enqueue_script_with_auto_deps( $handle, $file_name, $footer ) {

	nc_register_script_with_auto_deps( $handle, $file_name, $footer );
	wp_enqueue_script( $handle );
}//end nc_enqueue_script_with_auto_deps()

/**
 * This function makes sure that a certain pair of meta key and value for a
 * given posts exists only once in the database.
 *
 * @param string $post_id    the post ID related to the given meta.
 * @param string $meta_key   the meta key.
 * @param mixed  $meta_value the meta value.
 *
 * @return integer the meta ID, false otherwise.
 *
 * @since 1.0.0
 */
function nc_add_post_meta_once( $post_id, $meta_key, $meta_value ) {

	delete_post_meta( $post_id, $meta_key, $meta_value );
	return add_post_meta( $post_id, $meta_key, $meta_value );
}//end nc_add_post_meta_once()

/**
 * This function makes sure that only the values in the array of meta values
 * exists in the database for the given post and meta key (one row per value).
 *
 * @param string $post_id     the post ID related to the given meta.
 * @param string $meta_key    the meta key.
 * @param array  $meta_values the meta values.
 *
 * @return boolean true on success, false otherwise.
 *
 * @since 1.4.2
 */
function nc_update_post_meta_array( $post_id, $meta_key, $meta_values ) {

	delete_metadata( 'post', $post_id, $meta_key );
	foreach ( $meta_values as $value ) {
		if ( ! add_post_meta( $post_id, $meta_key, $value, false ) ) {
			return false;
		}//end if
	}//end foreach

	return true;
}//end nc_update_post_meta_array()

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 1.0.0
 */
function nc_get_timezone() {

	$timezone_string = get_option( 'timezone_string', '' );
	if ( ! empty( $timezone_string ) ) {
		return 'UTC' === $timezone_string ? '+00:00' : $timezone_string;
	}//end if

	$utc_offset = get_option( 'gmt_offset', 0 );

	if ( $utc_offset < 0 ) {
		$utc_offset_no_dec = ceil( $utc_offset );
		$result            = sprintf( '-%02d', absint( $utc_offset_no_dec ) );
	} else {
		$utc_offset_no_dec = floor( $utc_offset );
		$result            = sprintf( '+%02d', absint( $utc_offset_no_dec ) );
	}//end if

	if ( $utc_offset == $utc_offset_no_dec ) { // phpcs:ignore
		$result .= ':00';
	} else {
		$result .= ':30';
	}//end if

	return $result;
}//end nc_get_timezone()

/**
 * This function returns the two-letter locale used in WordPress.
 *
 * @return string the two-letter locale used in WordPress.
 *
 * @since 1.0.0
 */
function nc_get_language() {

	// Language of the blog.
	$lang = get_option( 'WPLANG' );
	if ( empty( $lang ) ) {
		$lang = 'en_US';
	}//end if

	// Convert into a two-char string.
	if ( strpos( $lang, '_' ) > 0 ) {
		$lang = substr( $lang, 0, strpos( $lang, '_' ) );
	}//end if

	return $lang;
}//end nc_get_language()

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * @return boolean Whether this site is a staging site or not.
 *
 * @since 1.4.0
 */
function nc_is_staging() {
	if ( 'staging' === wp_get_environment_type() ) {
		return true;
	}//end if

	/**
	 * List of URLs (or keywords) used to identify a staging site.
	 *
	 * If `home_url` matches one of the given values, the current site will
	 * be considered as a staging site.
	 *
	 * @param array $urls list of staging URLs (or fragments). Default: `[ 'staging' ]`.
	 *
	 * @since 1.4.0
	 */
	$staging_urls = apply_filters( 'nelio_content_staging_urls', array( 'staging', '.local' ) );
	foreach ( $staging_urls as $staging_url ) {
		if ( strpos( home_url(), $staging_url ) !== false ) {
			return true;
		}//end if
	}//end foreach

	return false;
}//end nc_is_staging()

/**
 * Generates a unique ID.
 *
 * @return string unique ID.
 *
 * @since 2.0.0
 */
function nc_uuid() {

	$data    = random_bytes( 16 );
	$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
	$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
}//end nc_uuid()

/**
 * Returns the list of auto share end modes.
 *
 * @return array list of auto share end modes.
 *
 * @since 2.2.8
 */
function nc_get_auto_share_end_modes() {
	// NOTICE. “value” options (i.e. AutoShareEndModeId) are defined in packages/types/automations.ts.
	return array(
		array(
			'value'  => 'never',
			'label'  => esc_html_x( 'Always eligible', 'text (resharable content)', 'nelio-content' ),
			'months' => 0,
		),
		array(
			'value'  => '1-month',
			'label'  => esc_html_x( 'Disable resharing after one month', 'command', 'nelio-content' ),
			'months' => 1,
		),
		array(
			'value'  => '2-months',
			'label'  => esc_html_x( 'Disable resharing after two months', 'command', 'nelio-content' ),
			'months' => 2,
		),
		array(
			'value'  => '3-months',
			'label'  => esc_html_x( 'Disable resharing after three months', 'command', 'nelio-content' ),
			'months' => 3,
		),
		array(
			'value'  => '6-months',
			'label'  => esc_html_x( 'Disable resharing after six months', 'command', 'nelio-content' ),
			'months' => 6,
		),
		array(
			'value'  => '1-year',
			'label'  => esc_html_x( 'Disable resharing after one year', 'command', 'nelio-content' ),
			'months' => 12,
		),
	);
}//end nc_get_auto_share_end_modes()

/**
 * Returns the post ID of a given URL.
 *
 * @param string $url a URL.
 *
 * @return int post ID or 0 on failure
 *
 * @since 2.3.0
 */
function nc_url_to_postid( $url ) {
	if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
		return wpcom_vip_url_to_postid( $url );
	}//end if

	// phpcs:ignore
	return url_to_postid( $url );
}//end nc_url_to_postid()

function nc_term_exists( $term, $taxonomy = '', $parent = null ) { // phpcs:ignore
	if ( function_exists( 'wpcom_vip_term_exists' ) ) {
		return wpcom_vip_term_exists( $term, $taxonomy, $parent );
	}//end if

	// phpcs:ignore
	return term_exists( $term, $taxonomy, $parent );
}//end nc_term_exists()

/**
 * Returns the list of automation groups.
 *
 * @return array list of automation groups.
 *
 * @since 3.0.0
 */
function nc_get_automation_groups() {
	$site_id = nc_get_site_id();
	if ( empty( $site_id ) ) {
		return array();
	}//end if

	$groups = get_transient( 'nc_automation_groups' );
	if ( ! empty( $groups ) ) {
		return $groups;
	}//end if

	$data = array(
		'method'    => 'GET',
		'timeout'   => apply_filters( 'nelio_content_request_timeout', 30 ),
		'sslverify' => ! nc_does_api_use_proxy(),
		'headers'   => array(
			'Authorization' => 'Bearer ' . nc_generate_api_auth_token(),
			'accept'        => 'application/json',
			'content-type'  => 'application/json',
		),
	);


	$url      = nc_get_api_url( "/site/{$site_id}/automation-groups", 'wp' );
	$response = wp_remote_request( $url, $data );
	if ( is_wp_error( $response ) ) {
		return array();
	}//end if

	$is_universal = function ( $group ) {
		return 'universal' === $group['id'];
	};

	$is_regular = function ( $group ) {
		return 'universal' !== $group['id'];
	};

	$groups    = json_decode( $response['body'], true );
	$universal = array_values( array_filter( $groups, $is_universal ) )[0];
	$regular   = array_values( array_filter( $groups, $is_regular ) );
	$groups    = array_merge( array( $universal ), $regular );

	set_transient( 'nc_automation_groups', $groups, 5 * MINUTE_IN_SECONDS );

	return $groups;
}//end nc_get_automation_groups()

/**
 * Returns the post statuses available for this post type.
 *
 * @param string $post_type post type name.
 *
 * @return array post statuses available for this post type.
 *
 * @since 3.0.7
 */
function nelio_content_get_post_statuses( $post_type ) {
	$statuses = array(
		array(
			'slug'   => 'trash',
			'name'   => __( 'Trash' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'icon'   => 'trash',
			'colors' => array(
				'main'       => '#c44',
				'background' => '#fee',
			),
		),
		array(
			'slug'   => 'draft',
			'name'   => __( 'Draft' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'icon'   => 'edit',
			'colors' => array(
				'main'       => '#c44',
				'background' => '#fee',
			),
		),
		array(
			'slug'   => 'pending',
			'name'   => __( 'Pending' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'icon'   => 'visibility',
			'colors' => array(
				'main'       => '#f9d510',
				'background' => '#fffdf1',
			),
		),
		array(
			'slug'   => 'future',
			'name'   => __( 'Scheduled' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'icon'   => 'clock',
			'colors' => array(
				'main'       => '#447d37',
				'background' => '#e5f0e7',
			),
		),
		array(
			'slug'   => 'publish',
			'name'   => __( 'Published' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			'colors' => array(
				'main'       => '#447d37',
				'background' => '#e5f0e7',
			),
		),
	);

	if ( current_user_can( 'administrator' ) ) { // phpcs:ignore
		array_push(
			$statuses,
			array(
				'slug'   => 'private',
				'name'   => __( 'Private' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'icon'   => 'hidden',
				'colors' => array(
					'main'       => '#447d37',
					'background' => '#e5f0e7',
				),
			)
		);
	}//end if

	/**
	 * Filters whether the given post type supports its content being “unscheduled” or not.
	 *
	 * @param boolean $supported  whether unscheduled content is supported or not. Default: `true`.
	 * @param string  $post_type  post type.
	 *
	 * @since 3.6.0
	 */
	if ( apply_filters( 'nelio_content_can_post_type_be_unscheduled', true, $post_type ) ) {
		$unscheduled = array(
			'slug'   => 'nelio-content-unscheduled',
			'name'   => _x( 'Unscheduled', 'text', 'nelio-content' ),
			'colors' => array(
				'main'       => '#c44',
				'background' => '#fee',
			),
		);
		$statuses    = array_merge( array( $unscheduled ), $statuses );
	}//end if

	/**
	 * Filters the available post statuses for a given post type.
	 *
	 * Each status must contain a `slug`, a `name`, an optional `icon`
	 * dashicon, and a `colors` array with two values:
	 * `main` and `background`.
	 *
	 * Since 3.6.0, statuses may also contain a `flags` property. It’s
	 * an array that can accept the following string values:
	 *
	 * - `disabled-in-editor`: post status can’t be selected in Nelio’s quick post editor.
	 * - `hide-in-board`: post status is not visible in Nelio’s content board.
	 * - `no-drop-in-board`: content can’t be dropped into this status in Nelio’s content board.
	 *
	 * @param array  $statues   list of post statuses.
	 * @param string $post_type post type.
	 *
	 * @since 2.2.2
	 */
	$statuses = apply_filters( 'nelio_content_post_statuses', $statuses, $post_type );

	$type_object = get_post_type_object( $post_type );
	$statuses    = array_map(
		function ( $status ) use ( &$type_object ) {
			if ( empty( $type_object ) ) {
				$status['available'] = false;
			} elseif ( in_array( $status['slug'], array( 'publish', 'future' ), true ) ) {
				$status['available'] = current_user_can( $type_object->cap->publish_posts );
			} else {
				$status['available'] = true;
			}//end if
			return $status;
		},
		$statuses
	);

	return array_map(
		function ( $status ) use ( $post_type ) {
			/**
			 * Filters whether current user can set this status or not.
			 *
			 * @param boolean  $available  whether current user can set this status or not.
			 * @param string   $status     status slug.
			 * @param string   $post_type  post type.
			 *
			 * @since 2.3.5
			 */
			$available           = apply_filters( 'nelio_content_can_use_post_status', $status['available'], $status['slug'], $post_type );
			$status['available'] = ! empty( $available );
			return $status;
		},
		$statuses
	);
}//end nelio_content_get_post_statuses()

/**
 * Returns the supported post types in the given context.
 *
 * @param string $context Expected context or list of expected contexts separated by comma.
 *
 * @return array List of post type names.
 *
 * @since 3.6.0
 */
function nelio_content_get_post_types( string $context ): array {
	switch ( $context ) {
		case 'cloud':
			$context = 'social';
			break;
		case 'editor':
			$context = 'efi,social,comments,future-actions,notifications,references,series,tasks';
			break;
	}//end switch

	$get_types = function ( $c ) {
		$s = Nelio_Content_Settings::instance();
		switch ( $c ) {
			case 'analytics':
			case 'calendar':
			case 'efi':
			case 'social':
				return $s->get( "{$c}_post_types" );

			case 'comments':
				return $s->get( 'comment_post_types' );

			case 'duplicate':
				return $s->get( 'duplicate_post_types' );

			case 'future-actions':
				return $s->get( 'future_action_post_types' );

			case 'notifications':
				return $s->get( 'notification_post_types' );

			case 'references':
				return $s->get( 'reference_post_types' );

			case 'rewrite':
				return $s->get( 'rewrite_post_types' );

			case 'series':
				return $s->get( 'series_post_types' );

			case 'tasks':
				return $s->get( 'task_post_types' );

			case 'content-board':
				return $s->get( 'content_board_post_types' );

			case 'quality-checks':
				return $s->get( 'quality_check_post_types' );

			default:
				return wp_die( esc_html( "Unknown context {$c}" ) );
		}//end switch
	};

	$flatten = function ( $ass ) {
		$r = array();
		foreach ( $ass as $as ) {
			$r = array_merge( $r, $as );
		}//end foreach
		return $r;
	};

	$result = array_map( $get_types, explode( ',', $context ) );
	$result = array_map( fn( $a ) => is_array( $a ) ? $a : array(), $result );
	$result = $flatten( $result );
	$result = array_values( $result );
	$result = array_values( $result );
	return $result;
}//end nelio_content_get_post_types()

/**
 * Returns current user’s social editor permission.
 *
 * @return string current user’s social editor permission.
 *
 * @since 3.6.0
 */
function nelio_content_get_social_editor_permission() {
	$permission = 'none';
	if ( nc_can_current_user_use_plugin() ) {
		$permission = 'post-type';
	}//end if
	if ( nc_can_current_user_manage_plugin() ) {
		$permission = 'all';
	}//end if

	/**
	 * Filters the required permission for the user to be able to edit social messages.
	 *
	 * Possible values are:
	 *
	 * - `all`: the user can edit any social message
	 * - `post-type`: the user can edit social messages related to a post type they can edit or social messages assigned to them
	 * - `none`: the user can’t edit any social message
	 *
	 * @param string $permission the required permission. Possibe values are:
	 * @param int    $user_id    current user id
	 *
	 * @since 2.0.0
	 */
	$new_permission = apply_filters( 'nelio_content_social_editor_permission', $permission, get_current_user_id() );

	if ( in_array( $new_permission, array( 'all', 'post-type', 'none' ), true ) ) {
		$permission = $new_permission;
	}//end if

	return $permission;
}//end nelio_content_get_social_editor_permission()

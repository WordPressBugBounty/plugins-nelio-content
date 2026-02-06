<?php
/**
 * Helper functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the current user can manage the account or not.
 *
 * @return boolean whether the current user can manage the account or not.
 *
 * @since 4.0.8
 */
function nelio_content_can_current_user_manage_account() {
	if ( ! nelio_content_can_current_user_manage_plugin() ) {
		return false;
	}

	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	$can_manage = current_user_can( 'manage_options' );

	/**
	 * Filters whether the user can manage the account or not.
	 *
	 * @param boolean $can_manage whether the user can or can’t manage the account.
	 * @param int     $user_id    user id.
	 *
	 * @since 2.0.0
	 */
	$can_manage = apply_filters( 'nelio_content_can_user_manage_account', $can_manage, get_current_user_id() );
	return ! empty( $can_manage );
}

/**
 * Checks if the current user can manage the plugin or not.
 *
 * @return boolean whether the current user can manage the plugin or not.
 *
 * @since 4.0.8
 */
function nelio_content_can_current_user_manage_plugin() {
	if ( ! nelio_content_can_current_user_use_plugin() ) {
		return false;
	}

	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	$can_manage = current_user_can( 'edit_others_posts' );

	/**
	 * Filters whether the current user can or can’t manage the plugin.
	 *
	 * @param boolean $can_manage whether the user can or can’t manage the plugin.
	 * @param int     $user_id    user id.
	 *
	 * @since 2.0.0
	 */
	$can_manage = apply_filters( 'nelio_content_can_user_manage_plugin', $can_manage, get_current_user_id() );
	return ! empty( $can_manage );
}

/**
 * Checks if the current user can use the plugin or not.
 *
 * @return boolean whether the current user can use the plugin or not.
 *
 * @since 4.0.8
 */
function nelio_content_can_current_user_use_plugin() {
	if ( ! function_exists( 'current_user_can' ) ) {
		return false;
	}

	/**
	 * Short-circuits the user’s ability to use the plugin.
	 *
	 * If set to `true`, the plugin won’t have access to the plugin. Otherwise, it’ll depend on their capabilities.
	 *
	 * @param boolean $revoke_access whether the user shouldn’t have access to the plugin.
	 * @param int     $user_id       the user.
	 *
	 * @since 2.0.0
	 */
	if ( apply_filters( 'nelio_content_revoke_plugin_access_to_user', false, get_current_user_id() ) ) {
		return false;
	}

	$settings = Nelio_Content_Settings::instance();
	if ( ! $settings->are_ready() ) {
		return false;
	}

	static $can_edit_managed_post_type;
	if ( is_bool( $can_edit_managed_post_type ) ) {
		return $can_edit_managed_post_type;
	}

	$post_types = nelio_content_get_post_types( 'calendar' );
	foreach ( $post_types as $name ) {
		$type = get_post_type_object( $name );
		if ( empty( $type ) ) {
			continue;
		}

		if ( is_string( $type->cap->edit_posts ) && current_user_can( $type->cap->edit_posts ) ) {
			$can_edit_managed_post_type = true;
			return true;
		}
	}

	$can_edit_managed_post_type = false;
	return false;
}

/**
 * Generates a title for our settings screen.
 *
 * @param string $title the title of the section.
 * @param string $icon  a Dashicon identifier.
 *
 * @return string the title of the section.
 *
 * @since 4.0.8
 */
function nelio_content_make_settings_title( $title, $icon ) {
	if ( empty( $icon ) ) {
		return $title;
	}

	return sprintf(
		'<span class="dashicons dashicons-%s"></span> %s',
		$icon,
		$title
	);
}

/**
 * Registers a script loading the dependencies automatically.
 *
 * @param string                                          $handle    the script handle name.
 * @param string                                          $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array{strategy?: string, in_footer?: bool}|bool $args      (optional) An array of additional script loading strategies.
 *                                                        Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_register_script_with_auto_deps( $handle, $file_name, $args = false ) {

	$path = nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	if ( file_exists( $path ) ) {
		$asset = include $path;
	}

	$asset = ! empty( $asset ) && is_array( $asset ) ? $asset : array();
	/** @var array{dependencies:list<string>, version:string} */
	$asset = wp_parse_args(
		$asset,
		array(
			'dependencies' => array(),
			'version'      => nelio_content()->plugin_version,
		)
	);

	// NOTE. Add the regenerator-runtime to all our scripts to make sure AsyncPaginate works.
	if ( is_wp_version_compatible( '5.8' ) ) {
		$asset['dependencies'] = array_merge( $asset['dependencies'], array( 'regenerator-runtime' ) );
	}

	wp_register_script(
		$handle,
		nelio_content()->plugin_url . "/assets/dist/js/$file_name.js",
		$asset['dependencies'],
		$asset['version'],
		$args
	);

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( $handle, 'nelio-content' );
	}
}

/**
 * Returns the script version if available. If it isn’t, it defaults to the plugin’s version.
 *
 * @param string $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 *
 * @return string the version of the given script or the plugin’s version if the former wasn’t be found.
 *
 * @since 4.0.8
 */
function nelio_content_get_script_version( $file_name ) {
	$path = nelio_content()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	if ( ! file_exists( $path ) ) {
		return nelio_content()->plugin_version;
	}

	$asset = include $path;
	$asset = ! empty( $asset ) && is_array( $asset ) ? $asset : array();
	/** @var array{dependencies:list<string>, version:string} */
	$asset = wp_parse_args(
		$asset,
		array(
			'dependencies' => array(),
			'version'      => nelio_content()->plugin_version,
		)
	);

	return $asset['version'];
}

/**
 * Enqueues a script loading the dependencies automatically.
 *
 * @param string                                          $handle    the script handle name.
 * @param string                                          $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array{strategy?: string, in_footer?: bool}|bool $args      (optional) An array of additional script loading strategies.
 *                                                                   Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_enqueue_script_with_auto_deps( $handle, $file_name, $args = false ) {

	nelio_content_register_script_with_auto_deps( $handle, $file_name, $args );
	wp_enqueue_script( $handle );
}

/**
 * This function makes sure that a certain pair of meta key and value for a
 * given posts exists only once in the database.
 *
 * @param int    $post_id    the post ID related to the given meta.
 * @param string $meta_key   the meta key.
 * @param mixed  $meta_value the meta value.
 *
 * @return int|false
 *
 * @since 4.0.8
 */
function nelio_content_add_post_meta_once( $post_id, $meta_key, $meta_value ) {
	delete_post_meta( $post_id, $meta_key, $meta_value );
	return add_post_meta( $post_id, $meta_key, $meta_value );
}

/**
 * This function makes sure that only the values in the array of meta values
 * exists in the database for the given post and meta key (one row per value).
 *
 * @param int          $post_id     the post ID related to the given meta.
 * @param string       $meta_key    the meta key.
 * @param array<mixed> $meta_values the meta values.
 *
 * @return boolean true on success, false otherwise.
 *
 * @since 4.0.8
 */
function nelio_content_update_post_meta_array( $post_id, $meta_key, $meta_values ) {
	delete_metadata( 'post', $post_id, $meta_key );
	foreach ( $meta_values as $value ) {
		if ( ! add_post_meta( $post_id, $meta_key, $value, false ) ) {
			return false;
		}
	}

	return true;
}

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 4.0.8
 */
function nelio_content_get_timezone() {

	$timezone_string = get_option( 'timezone_string' );
	if ( ! empty( $timezone_string ) ) {
		return 'UTC' === $timezone_string ? '+00:00' : $timezone_string;
	}

	$utc_offset = get_option( 'gmt_offset', 0 );
	if ( ! is_numeric( $utc_offset ) ) {
		return '+00:00';
	}

	if ( $utc_offset < 0 ) {
		$utc_offset_no_dec = ceil( (float) $utc_offset );
		$result            = sprintf( '-%02d', absint( $utc_offset_no_dec ) );
	} else {
		$utc_offset_no_dec = floor( (float) $utc_offset );
		$result            = sprintf( '+%02d', absint( $utc_offset_no_dec ) );
	}

	if ( "$utc_offset" === "$utc_offset_no_dec" ) {
		$result .= ':00';
	} else {
		$result .= ':30';
	}

	return $result;
}

/**
 * This function returns the two-letter locale used in WordPress.
 *
 * @return string the two-letter locale used in WordPress.
 *
 * @since 4.0.8
 */
function nelio_content_get_language() {

	// Language of the blog.
	$lang = get_option( 'WPLANG' );
	if ( empty( $lang ) || ! is_string( $lang ) ) {
		$lang = 'en_US';
	}

	// Convert into a two-char string.
	if ( strpos( $lang, '_' ) > 0 ) {
		$lang = substr( $lang, 0, strpos( $lang, '_' ) );
	}

	return $lang;
}

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * @return boolean Whether this site is a staging site or not.
 *
 * @since 4.0.8
 */
function nelio_content_is_staging() {
	/**
	 * Filters whether WP environment’s type should be ignored to determine if we’re on a staging site.
	 *
	 * If not ignored and WP’s environment type is anything other than `production`, Nelio
	 * will consider the site as a staging site.
	 *
	 * @param bool $ignored Is WP’s environment type ignore. Default: `false`.
	 *
	 * @since 4.1.1
	 */
	if ( ! apply_filters( 'nelio_content_staging_ignore_wp_environment_type', false ) ) {
		if ( 'production' !== wp_get_environment_type() ) {
			return true;
		}
	}

	/**
	 * List of URLs (or keywords) used to identify a staging site.
	 *
	 * If `home_url` matches one of the given values, the current site will
	 * be considered as a staging site.
	 *
	 * @param list<string> $urls list of staging URLs (or fragments). Default: `[ 'staging' ]`.
	 *
	 * @since 1.4.0
	 */
	$staging_urls = apply_filters( 'nelio_content_staging_urls', array( 'staging', '.local' ) );
	foreach ( $staging_urls as $staging_url ) {
		if ( strpos( home_url(), $staging_url ) !== false ) {
			return true;
		}
	}

	return false;
}

/**
 * Generates a unique ID.
 *
 * @return string unique ID.
 *
 * @since 4.0.8
 */
function nelio_content_uuid() {

	$data    = random_bytes( 16 );
	$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
	$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
}

/**
 * Returns the list of auto share end modes.
 *
 * @return list<TAuto_Share_End_Mode> list of auto share end modes.
 *
 * @since 4.0.8
 */
function nelio_content_get_auto_share_end_modes() {
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
}

/**
 * Returns the post ID of a given URL.
 *
 * @param string $url a URL.
 *
 * @return int post ID or 0 on failure
 *
 * @since 4.0.8
 */
function nelio_content_url_to_postid( $url ) {
	if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
		return wpcom_vip_url_to_postid( $url );
	}

	return url_to_postid( $url );
}

/**
 * Returns the supported post types in the given context.
 *
 * @param TPost_Type_Context|'cloud'|'editor' $context Expected context or list of expected contexts separated by comma.
 *
 * @return list<string> List of post type names.
 *
 * @since 4.0.8
 */
function nelio_content_get_post_types( $context ) {
	switch ( $context ) {
		case 'cloud':
			$context = 'social';
			break;
		case 'editor':
			$context = 'efi,social,comments,future-actions,notifications,references,series,tasks';
			break;
	}

	/** @var list<TPost_Type_Context> $aux */
	$aux     = explode( ',', $context );
	$context = $aux;

	/**
	 * @var \Closure(TPost_Type_Context): list<string> $get_types
	 * @phpstan-ignore-next-line varTag.nativeType
	 */
	$get_types = function ( $c ) {
		/** @var TPost_Type_Context $c */

		$s = Nelio_Content_Settings::instance();
		switch ( $c ) {
			case 'analytics':
				return $s->get( 'analytics_post_types' );

			case 'calendar':
				return $s->get( 'calendar_post_types' );

			case 'comments':
				return $s->get( 'comment_post_types' );

			case 'content-board':
				return $s->get( 'content_board_post_types' );

			case 'duplicate':
				return $s->get( 'duplicate_post_types' );

			case 'efi':
				return $s->get( 'efi_post_types' );

			case 'future-actions':
				return $s->get( 'future_action_post_types' );

			case 'notifications':
				return $s->get( 'notification_post_types' );

			case 'quality-checks':
				return $s->get( 'quality_check_post_types' );

			case 'references':
				return $s->get( 'reference_post_types' );

			case 'rewrite':
				return $s->get( 'rewrite_post_types' );

			case 'series':
				return $s->get( 'series_post_types' );

			case 'social':
				return $s->get( 'social_post_types' );

			case 'tasks':
				return $s->get( 'task_post_types' );

			case 'wp':
				$post_types = get_post_types(
					array(
						'show_ui'      => true,
						'show_in_rest' => true,
					)
				);
				$post_types = array_filter(
					$post_types,
					fn ( $post_type ) => ! in_array( $post_type, array( 'nav_menu', 'attachment', 'revision', 'wp_navigation', 'wp_block' ), true )
				);
				$post_types = array_values( $post_types );

				/**
				 * List of post types that can be used in Nelio Content in the context of WordPress.
				 *
				 * @param list<string> $post_types List of post types.
				 *
				 * @since 4.0.0
				 */
				return apply_filters( 'nelio_content_get_post_types', $post_types );

			default:
				wp_die( esc_html( "Unknown context {$c}" ) );
		}
	};

	$result = array_map( $get_types, $context );
	$result = \Nelio_Content\Helpers\flatten( $result );
	$result = array_values( array_unique( $result ) );
	return $result;
}

/**
 * Returns current user’s social editor permission.
 *
 * @return string current user’s social editor permission.
 *
 * @since 4.0.8
 */
function nelio_content_get_social_editor_permission() {
	if ( nelio_content_is_staging() ) {
		return 'none';
	}

	$permission = 'none';
	if ( nelio_content_can_current_user_use_plugin() ) {
		$permission = 'post-type';
	}
	if ( nelio_content_can_current_user_manage_plugin() ) {
		$permission = 'all';
	}

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
	}

	return $permission;
}

/**
 * Requires the file from WordPress once.
 *
 * @param string $path Filename relative from ABSPATH.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_require_wp_file( $path ) {
	if ( 0 !== strpos( $path, '/' ) ) {
		$path = "/{$path}";
	}
	require_once untrailingslashit( ABSPATH ) . $path;
}

/**
 * Returns the external featured image associated to a post, if any.
 *
 * @param int $post_id the ID of the post.
 *
 * @return string|boolean the URL of the external featured image or \`false\` otherwise.
 *
 * @since 4.0.8
 */
function nelio_content_get_external_featured_image( $post_id ) {
	$aux = Nelio_Content_External_Featured_Image_Helper::instance();
	return $aux->get_external_featured_image( $post_id );
}

/**
 * Returns the current user role.
 *
 * @return string the current user role.
 *
 * @since 4.0.8
 */
function nelio_content_get_current_user_role() {
	if ( ! is_user_logged_in() ) {
		return '__not_logged_in__';
	}

	$user  = wp_get_current_user();
	$roles = (array) $user->roles;
	return ! empty( $roles ) ? $roles[0] : 'subscriber';
}

<?php
/**
 * DEPRECATED. This file contains deprecated helper functions.
 *
 * If you’re using any of these functions in your website, please migrate to the new version.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to use Nelio’s proxy instead of accessing AWS directly or not.
 *
 * @return boolean whether to use Nelio’s proxy instead of accessing AWS directly or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_does_api_use_proxy instead.
 */
function nc_does_api_use_proxy() {
	return nelio_content_does_api_use_proxy();
}

/**
 * Returns the API url for the specified method.
 *
 * @param string $method  The metho we want to use.
 * @param string $context Either 'wp' or 'browser', depending on the location
 *                        in which the resulting URL has to be used.
 *                        Only wp calls might use the proxy URL.
 *
 * @return string the API url for the specified method.
 *
 * @since 1.1.0
 *
 * @deprecated Use nelio_content_get_api_url instead.
 */
function nc_get_api_url( $method, $context ) {
	return nelio_content_get_api_url( $method, $context );
}

/**
 * Returns a new token for accessing the API.
 *
 * @param string $mode Either 'regular' or 'skip-errors'. If the latter is used, the function
 *                     won't generate any HTML errors.
 *
 * @return string a new token for accessing the API.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_generate_api_auth_token instead.
 */
function nc_generate_api_auth_token( $mode = 'regular' ) {
	return nelio_content_generate_api_auth_token( $mode );
}


/**
 * Returns the error message associated to the given code.
 *
 * @param string       $code          API error code.
 * @param string|false $default_value Optional. Default error message.
 *
 * @return string|false
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_get_error_message instead.
 */
function nc_get_error_message( $code, $default_value = false ) {
	return nelio_content_get_error_message( $code, $default_value );
}

/**
 * This function converts a remote request response into either a WP_Error
 * object (if something failed) or whatever the original response had in its body.
 *
 * @param array<string,mixed>|WP_Error $response the response of a `wp_remote_*` call.
 *
 * @return mixed|WP_Error
 *
 * @since 5.0.0
 *
 * @deprecated Use nelio_content_extract_response_body instead.
 */
function nc_extract_response_body( $response ) {
	return nelio_content_extract_response_body( $response );
}

/**
 * Returns the API secret.
 *
 * @return string the API secret.
 *
 * @since 5.0.0
 *
 * @deprecated Use nelio_content_get_api_secret instead.
 */
function nc_get_api_secret() {
	return nelio_content_get_api_secret();
}

/**
 * Returns the list of automation groups.
 *
 * @return list<TAutomation_Group> list of automation groups.
 *
 * @since 3.0.0
 *
 * @deprecated Use nelio_content_get_automation_groups instead.
 */
function nc_get_automation_groups() {
	return nelio_content_get_automation_groups();
}

/**
 * Returns this site's ID.
 *
 * @return string This site's ID. This option is used for accessing AWS.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_get_site_id instead.
 */
function nc_get_site_id() {
	return nelio_content_get_site_id();
}

/**
 * Returns the limits the plugin has, based on the current subscription and so on.
 *
 * @return TSite_Limits the limits the plugin has.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_get_site_limits instead.
 */
function nc_get_site_limits() {
	return nelio_content_get_site_limits();
}

/**
 * Checks if the current user can manage the account or not.
 *
 * @return boolean whether the current user can manage the account or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_can_current_user_manage_account instead.
 */
function nc_can_current_user_manage_account() {
	return nelio_content_can_current_user_manage_account();
}

/**
 * Checks if the current user can manage the plugin or not.
 *
 * @return boolean whether the current user can manage the plugin or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_can_current_user_manage_plugin instead.
 */
function nc_can_current_user_manage_plugin() {
	return nelio_content_can_current_user_manage_plugin();
}

/**
 * Checks if the current user can use the plugin or not.
 *
 * @return boolean whether the current user can use the plugin or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_can_current_user_use_plugin instead.
 */
function nc_can_current_user_use_plugin() {
	return nelio_content_can_current_user_use_plugin();
}

/**
 * Generates a title for our settings screen.
 *
 * @param string $title the title of the section.
 * @param string $icon  a Dashicon identifier.
 *
 * @return string the title of the section.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_make_settings_title instead.
 */
function nc_make_settings_title( $title, $icon ) {
	return nelio_content_make_settings_title( $title, $icon );
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
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_register_script_with_auto_deps instead.
 */
function nc_register_script_with_auto_deps( $handle, $file_name, $args = false ) {
	nelio_content_register_script_with_auto_deps( $handle, $file_name, $args );
}

/**
 * Returns the script version if available. If it isn’t, it defaults to the plugin’s version.
 *
 * @param string $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 *
 * @return string the version of the given script or the plugin’s version if the former wasn’t be found.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_get_script_version instead.
 */
function nc_get_script_version( $file_name ) {
	return nelio_content_get_script_version( $file_name );
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
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_enqueue_script_with_auto_deps instead.
 */
function nc_enqueue_script_with_auto_deps( $handle, $file_name, $args = false ) {
	nelio_content_enqueue_script_with_auto_deps( $handle, $file_name, $args );
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
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_add_post_meta_once instead.
 */
function nc_add_post_meta_once( $post_id, $meta_key, $meta_value ) {
	return nelio_content_add_post_meta_once( $post_id, $meta_key, $meta_value );
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
 * @since 1.4.2
 *
 * @deprecated Use nelio_content_update_post_meta_array instead.
 */
function nc_update_post_meta_array( $post_id, $meta_key, $meta_values ) {
	return nelio_content_update_post_meta_array( $post_id, $meta_key, $meta_values );
}

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_get_timezone instead.
 */
function nc_get_timezone() {
	return nelio_content_get_timezone();
}

/**
 * This function returns the two-letter locale used in WordPress.
 *
 * @return string the two-letter locale used in WordPress.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_get_language instead.
 */
function nc_get_language() {
	return nelio_content_get_language();
}

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * @return boolean Whether this site is a staging site or not.
 *
 * @since 1.4.0
 *
 * @deprecated Use nelio_content_is_staging instead.
 */
function nc_is_staging() {
	return nelio_content_is_staging();
}

/**
 * Generates a unique ID.
 *
 * @return string unique ID.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_uuid instead.
 */
function nc_uuid() {
	return nelio_content_uuid();
}

/**
 * Returns the list of auto share end modes.
 *
 * @return list<TAuto_Share_End_Mode> list of auto share end modes.
 *
 * @since 2.2.8
 *
 * @deprecated Use nelio_content_get_auto_share_end_modes instead.
 */
function nc_get_auto_share_end_modes() {
	return nelio_content_get_auto_share_end_modes();
}

/**
 * Returns the post ID of a given URL.
 *
 * @param string $url a URL.
 *
 * @return int post ID or 0 on failure
 *
 * @since 2.3.0
 *
 * @deprecated Use nelio_content_url_to_postid instead.
 */
function nc_url_to_postid( $url ) {
	return nelio_content_url_to_postid( $url );
}

/**
 * Determines whether a taxonomy term exists.
 *
 * Wraps WordPress’ built-int `term_exists` function.
 *
 * @param int|string $term        The term to check. Accepts term ID, slug, or name.
 * @param string     $taxonomy    Optional. The taxonomy name to use.
 * @param int        $parent_term Optional. ID of parent term under which to confine the exists search.
 *
 * @return mixed
 *
 * @deprecated Use term_exists instead.
 */
function nc_term_exists( $term, $taxonomy = '', $parent_term = null ) {
	return term_exists( $term, $taxonomy, $parent_term );
}

/**
 * Returns the external featured image associated to a post, if any.
 *
 * @param int $post_id the ID of the post.
 *
 * @return string|boolean the URL of the external featured image or \`false\` otherwise.
 *
 * @since 2.0.1
 *
 * @deprecated Use nelio_content_get_external_featured_image instead.
 */
function nc_get_external_featured_image( $post_id ) {
	return nelio_content_get_external_featured_image( $post_id );
}

/**
 * Returns the reference whose ID is the given ID.
 *
 * @param integer $id The ID of the reference.
 *
 * @return Nelio_Content_Reference|false The reference with the given ID or `false` if such a reference does not exist.
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_get_reference instead.
 */
function nc_get_reference( $id ) {
	return nelio_content_get_reference( $id );
}

/**
 * Returns the reference whose URL is the given URL.
 *
 * @param string $url The URL of the reference we want to retrieve.
 *
 * @return Nelio_Content_Reference|false The reference with the given URL or
 *               false if such a reference does not exist.
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_get_reference_by_url instead.
 */
function nc_get_reference_by_url( $url ) {
	return nelio_content_get_reference_by_url( $url );
}

/**
 * Creates a new reference with the given URL.
 *
 * If a reference with the given URL already exists, that reference will be returned.
 *
 * @param string $url The URL of the (possibly) new reference.
 *
 * @return Nelio_Content_Reference|false The new reference (or an
 *              existing one, if there already existed one reference
 *              with the given URL). If the reference didn't exist and
 *              couldn't be created, `false` is returned.
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_create_reference instead.
 */
function nc_create_reference( $url ) {
	return nelio_content_create_reference( $url );
}

/**
 * Returns a list of all the references related to a given post.
 *
 * @param integer|WP_Post                    $post_id The post whose references will be returned.
 * @param 'included'|'suggested'|'discarded' $status  Optional. It specifies which references have to be returned. Default: `included`.
 *
 * @return list<int> a list of all the references related to the given post.
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_get_post_reference instead.
 */
function nc_get_post_reference( $post_id, $status = 'included' ) {
	return nelio_content_get_post_reference( $post_id, $status );
}

/**
 * Adds a reference to the given post, which means the reference appears in post's content.
 *
 * @param integer|WP_Post $post_id      The post in which a certain reference has to be added.
 * @param integer|WP_Post $reference_id The reference to be added.
 *
 * @return void
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_add_post_reference instead.
 */
function nc_add_post_reference( $post_id, $reference_id ) {
	nelio_content_add_post_reference( $post_id, $reference_id );
}

/**
 * Removes a reference from the list of "included references" of a certain post.
 *
 * @param integer|WP_Post $post_id      The post from which a certain reference has to be deleted.
 * @param integer|WP_Post $reference_id The reference to be deleted.
 *
 * @return void
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_delete_post_reference instead.
 */
function nc_delete_post_reference( $post_id, $reference_id ) {
	nelio_content_delete_post_reference( $post_id, $reference_id );
}

/**
 * Adds a reference as a suggestion of our post.
 *
 * @param integer|WP_Post $post_id      The post in which a certain reference has been suggested.
 * @param integer|WP_Post $reference_id The suggested reference.
 * @param integer         $advisor      The user ID who's suggesting this reference.
 *                                      If the advisor is Nelio Content itself, the ID is 0.
 *
 * @return void
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_suggest_post_reference instead.
 */
function nc_suggest_post_reference( $post_id, $reference_id, $advisor ) {
	nelio_content_suggest_post_reference( $post_id, $reference_id, $advisor );
}

/**
 * Removes a reference from the list of suggested references in a post.
 *
 * @param integer|WP_Post $post_id      The post from which a certain suggested reference has been discarded.
 * @param integer|WP_Post $reference_id The discarded reference.
 *
 * @return void
 *
 * @since  1.0.0
 *
 * @deprecated Use nelio_content_discard_post_reference instead.
 */
function nc_discard_post_reference( $post_id, $reference_id ) {
	nelio_content_discard_post_reference( $post_id, $reference_id );
}

/**
 * Returns meta information about a suggested reference, such as who suggested
 * it in a certain post.
 *
 * @param integer|WP_Post $post_id      The post for which the reference was suggested.
 * @param integer|WP_Post $reference_id The reference from which we want to obtain its meta information.
 *
 * @return TSuggested_Reference_Meta|false
 *
 * @since  2.0.0
 *
 * @deprecated Use nelio_content_get_suggested_reference_meta instead.
 */
function nc_get_suggested_reference_meta( $post_id, $reference_id ) {
	return nelio_content_get_suggested_reference_meta( $post_id, $reference_id );
}

/**
 * Removes a reference from the database, iff it's not used by any post.
 *
 * @param integer|WP_Post $reference_id The reference to be deleted.
 *
 * @return void
 *
 * @since  1.3.4
 *
 * @deprecated Use nelio_content_remove_unused_reference instead.
 */
function nc_remove_unused_reference( $reference_id ) {
	nelio_content_remove_unused_reference( $reference_id );
}

/**
 * This function returns the current subscription plan, if any.
 *
 * @return string|false name of the current subscription plan, or `false` if it has none.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_get_subscription instead.
 */
function nc_get_subscription() {
	return nelio_content_get_subscription();
}

/**
 * Returns whether the current user is a paying customer or not.
 *
 * @return boolean whether the current user is a paying customer or not.
 *
 * @since 1.0.0
 *
 * @deprecated Use nelio_content_is_subscribed instead.
 */
function nc_is_subscribed() {
	return nelio_content_is_subscribed();
}

/**
 * This helper function updates the current subscription.
 *
 * @param string       $plan   The plan of the subscription.
 * @param TSite_Limits $limits Max profile limit values.
 *
 * @return void
 *
 * @since 2.0.17
 *
 * @deprecated Use nelio_content_update_subscription instead.
 */
function nc_update_subscription( $plan, $limits ) {
	nelio_content_update_subscription( $plan, $limits );
}

/**
 * Returns the plan related to the given product.
 *
 * @param string $product Product name.
 *
 * @return string plan related to the given product.
 *
 * @since 2.0.17
 *
 * @deprecated Use nelio_content_get_plan instead.
 */
function nc_get_plan( $product ) {
	return nelio_content_get_plan( $product );
}

/**
 * Returns a list of active promos.
 *
 * @return list<string> list of active promos
 *
 * @since 3.6.0
 *
 * @deprecated Use nelio_content_get_active_promos instead.
 */
function nc_get_active_promos() {
	return nelio_content_get_active_promos();
}

/**
 * Checks if the variable “seems” a natural number.
 *
 * That is, it checks if the variable is a positive integer or a string that can be converted to a positive integer.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable seems a natural number.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_can_be_natural_number instead.
 */
function nc_can_be_natural_number( $variable ) {
	return nelio_content_can_be_natural_number( $variable );
}

/**
 * Checks if the variable is a valid date (YYYY-MM-DD).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid date.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_date instead.
 */
function nc_is_date( $variable ) {
	return nelio_content_is_date( $variable );
}

/**
 * Checks if the variable is a valid time (HH:MM).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid time.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_time instead.
 */
function nc_is_time( $variable ) {
	return nelio_content_is_time( $variable );
}

/**
 * Checks if the variable is a valid datetime (YYYY-MM-DDThh:mm:ssTZ).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid datetime.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_datetime instead.
 */
function nc_is_datetime( $variable ) {
	return nelio_content_is_datetime( $variable );
}

/**
 * Checks if the variable is not empty (as in, the opposite of what PHP’s `empty` function returns).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether variable is empty or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_not_empty instead.
 */
function nc_is_not_empty( $variable ) {
	return nelio_content_is_not_empty( $variable );
}

/**
 * Checks if the varirable is a valid Nelio Content license.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid Nelio Content license.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_valid_license instead.
 */
function nc_is_valid_license( $variable ) {
	return nelio_content_is_valid_license( $variable );
}

/**
 * Checks if the varirable is a valid URL.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid URL.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_url instead.
 */
function nc_is_url( $variable ) {
	return nelio_content_is_url( $variable );
}

/**
 * Checks if the varirable is a valid email address.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid email address.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_email instead.
 */
function nc_is_email( $variable ) {
	return nelio_content_is_email( $variable );
}

/**
 * Checks if the varirable is a valid twitter handle.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid twitter handle.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_is_twitter_handle instead.
 */
function nc_is_twitter_handle( $variable ) {
	return nelio_content_is_twitter_handle( $variable );
}

/**
 * Checks if the variable seems a boolean or not.
 *
 * That is, it checks if the variable is indeed a boolean, or if it’s a string such as “true” or “false”.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a boolean or not.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_can_be_bool instead.
 */
function nc_can_be_bool( $variable ) {
	return nelio_content_can_be_bool( $variable );
}

/**
 * Converts a variable that seems a bool into an actual bool.
 *
 * @param mixed $variable the variable that seems like a bool.
 *
 * @return boolean the variable as a boolean.
 *
 * @since 2.0.0
 *
 * @deprecated Use nelio_content_bool instead.
 */
function nc_bool( $variable ) {
	return nelio_content_bool( $variable );
}

/**
 * Returns a function that checks if the variable is an array and all its elements are of the given predicate.
 *
 * @param callable $predicate name of a boolean function to test each element in the array.
 *
 * @return callable a function that checks if the variable is an array of the expected type.
 *
 * @since 2.2.2
 *
 * @deprecated Use nelio_content_is_array instead.
 */
function nc_is_array( $predicate ) {
	return nelio_content_is_array( $predicate );
}

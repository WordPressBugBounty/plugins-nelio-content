<?php
/**
 * Validator functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the variable “seems” a natural number.
 *
 * That is, it checks if the variable is a positive integer or a string that can be converted to a positive integer.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable seems a natural number.
 *
 * @since 4.0.8
 */
function nelio_content_can_be_natural_number( $variable ) {
	if ( is_string( $variable ) ) {
		return ! empty( preg_match( '/^[0-9]+$/', $variable ) );
	}
	return is_int( $variable ) && 0 < $variable;
}

/**
 * Checks if the variable is a valid date (YYYY-MM-DD).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid date.
 *
 * @since 4.0.8
 */
function nelio_content_is_date( $variable ) {
	return is_string( $variable ) && ! empty( preg_match( '/^[0-9]{4}-[01][0-9]-[0123][0-9]$/', $variable ) );
}

/**
 * Checks if the variable is a valid time (HH:MM).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid time.
 *
 * @since 4.0.8
 */
function nelio_content_is_time( $variable ) {
	return is_string( $variable ) && ! empty( preg_match( '/^[012][0-9]:[0-5][0-9]$/', $variable ) );
}

/**
 * Checks if the variable is a valid datetime (YYYY-MM-DDThh:mm:ssTZ).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the variable is a valid datetime.
 *
 * @since 4.0.8
 */
function nelio_content_is_datetime( $variable ) {
	if ( ! is_string( $variable ) ) {
		return false;
	}

	if ( false === strpos( $variable, 'T' ) ) {
		return false;
	}

	$datetime = explode( 'T', $variable );
	$date     = $datetime[0];
	if ( ! nelio_content_is_date( $date ) ) {
		return false;
	}

	$time = substr( $datetime[1], 0, 5 );
	if ( ! nelio_content_is_time( $time ) ) {
		return false;
	}

	return true;
}

/**
 * Checks if the variable is not empty (as in, the opposite of what PHP’s `empty` function returns).
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether variable is empty or not.
 *
 * @since 4.0.8
 */
function nelio_content_is_not_empty( $variable ) {
	return ! empty( $variable );
}

/**
 * Checks if the varirable is a valid Nelio Content license.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid Nelio Content license.
 *
 * @since 4.0.8
 */
function nelio_content_is_valid_license( $variable ) {
	if ( ! is_string( $variable ) ) {
		return false;
	}

	return (
		! empty( preg_match( '/^[a-zA-Z0-9$#]{21}$/', $variable ) ) ||
		! empty( preg_match( '/^[a-zA-Z0-9$#]{26}$/', $variable ) )
	);
}

/**
 * Checks if the varirable is a valid URL.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid URL.
 *
 * @since 4.0.8
 */
function nelio_content_is_url( $variable ) {
	return is_string( $variable ) && ! empty( filter_var( $variable, FILTER_VALIDATE_URL ) );
}

/**
 * Checks if the varirable is a valid email address.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid email address.
 *
 * @since 4.0.8
 */
function nelio_content_is_email( $variable ) {
	return is_string( $variable ) && ! empty( preg_match( '/^[a-z0-9._%+-]+@[a-z0-9][a-z0-9.-]*\.[a-z]{2,63}$/', $variable ) );
}

/**
 * Checks if the varirable is a valid twitter handle.
 *
 * @param mixed $variable the variable we want to check.
 *
 * @return boolean whether the varirable is a valid twitter handle.
 *
 * @since 4.0.8
 */
function nelio_content_is_twitter_handle( $variable ) {
	return is_string( $variable ) && ! empty( preg_match( '/^@[^@\s]+$/', $variable ) );
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
 * @since 4.0.8
 */
function nelio_content_can_be_bool( $variable ) {
	return true === $variable || false === $variable || 'true' === $variable || 'false' === $variable;
}

/**
 * Converts a variable that seems a bool into an actual bool.
 *
 * @param mixed $variable the variable that seems like a bool.
 *
 * @return boolean the variable as a boolean.
 *
 * @since 4.0.8
 */
function nelio_content_bool( $variable ) {
	return true === $variable || 'true' === $variable;
}

/**
 * Returns a function that checks if the variable is an array and all its elements are of the given predicate.
 *
 * @param callable $predicate name of a boolean function to test each element in the array.
 *
 * @return callable a function that checks if the variable is an array of the expected type.
 *
 * @since 4.0.8
 */
function nelio_content_is_array( $predicate ) {
	return function ( $value ) use ( $predicate ) {
		return is_array( $value ) && array_reduce(
			$value,
			function ( $carry, $item ) use ( $predicate ) {
				return $carry && call_user_func( $predicate, $item );
			},
			true
		);
	};
}

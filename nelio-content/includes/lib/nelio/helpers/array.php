<?php

namespace Nelio_Content\Helpers;

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param mixed        $default the value returned for null resolved values.
 *
 * @return mixed The value at path or default if not found.
 */
function get( $element, $path, $default = null ) { // phpcs:ignore
	if ( is_string( $path ) ) {
		$path = explode( '.', $path );
		$path = empty( $path ) ? array() : $path;
	}//end if

	$result = array_reduce(
		$path,
		function ( $e, $k ) {
			if ( is_array( $e ) ) {
				return isset( $e[ $k ] ) ? $e[ $k ] : null;
			} elseif ( is_object( $e ) ) {
				return property_exists( $e, $k ) ? $e->$k : null;
			} else {
				return null;
			}//end if
		},
		$element
	);

	return is_null( $result ) ? $default : $result;
}//end get()

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null or is not an array, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param array        $default the value returned for null resolved values.
 *
 * @return array The value at path or default if not found.
 */
function get_array( $element, $path, array $default = array() ) { // phpcs:ignore
	$result = get( $element, $path, $default );
	return is_array( $result ) ? $result : $default;
}//end get_array()

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null or is not a float, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param float        $default the value returned for null resolved values.
 *
 * @return float The value at path or default if not found.
 */
function get_float( $element, $path, float $default = 0.0 ) { // phpcs:ignore
	$result = get( $element, $path, $default );
	return is_float( $result ) ? $result : $default;
}//end get_float()

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null or is not an int, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param int          $default the value returned for null resolved values.
 *
 * @return int The value at path or default if not found.
 */
function get_int( $element, $path, int $default = 0 ) { // phpcs:ignore
	$result = get( $element, $path, $default );
	return is_int( $result ) ? $result : $default;
}//end get_int()

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null or is not an object, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param object       $default the value returned for null resolved values.
 *
 * @return object The value at path or default if not found.
 */
function get_object( $element, $path, $default = null ) { // phpcs:ignore
	$default = is_null( $default ) ? new \stdClass() : $default;
	$result  = get( $element, $path, $default );
	return is_object( $result ) ? $result : $default;
}//end get_object()

/**
 * Returns the value at `$path` of `$element`. If the resolved value is null or is not a string, `$default` is returned in its place.
 *
 * @param object|array $element the element to query.
 * @param string|array $path    the path of the property to get.
 * @param string       $default the value returned for null resolved values.
 *
 * @return string The value at path or default if not found.
 */
function get_string( $element, $path, string $default = '' ) { // phpcs:ignore
	$result = get( $element, $path, $default );
	return is_string( $result ) ? $result : $default;
}//end get_string()

/**
 * Flattens `$array` a single level deep.
 *
 * @param array $array the array to flatten.
 *
 * @return array The new flattened array.
 */
function flatten( array $array ): array { // phpcs:ignore
	$result = array();
	foreach ( $array as $a ) {
		$result = array_merge( $result, $a );
	}//end foreach
	return $result;
}//end flatten()

/**
 * Returns an array excluding all given values.
 *
 * If `$array` had numeric indices, the new array will have its indices reset.
 *
 * @param array $array    The array to inspect.
 * @param mixed ...$items The values to exclude.
 *
 * @return array The new array of filtered values.
 */
function without( array $array, ...$items ): array { // phpcs:ignore
	$result = array_reduce(
		$items,
		fn( $r, $i ) => array_filter( $r, fn( $c ) => $c !== $i ),
		$array
	);
	return every( array_keys( $array ), 'is_int' ) ? array_values( $result ) : $result;
}//end without()

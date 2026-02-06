<?php

namespace Nelio_Content\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Flattens `$input` a single level deep.
 *
 * @template T
 *
 * @param array<list<T>> $input the array to flatten.
 *
 * @return list<T> The new flattened array.
 */
function flatten( $input ) {
	$result = array();
	foreach ( $input as $a ) {
		$result = array_merge( $result, $a );
	}
	return $result;
}

/**
 * Returns an array excluding all given values.
 *
 * If `$input` had numeric indices, the new array will have its indices reset.
 *
 * @template T
 *
 * @param array<T> $input    The array to inspect.
 * @param mixed    ...$items The values to exclude.
 *
 * @return array<T> The new array of filtered values.
 */
function without( $input, ...$items ) {
	$result = array_reduce(
		$items,
		fn( $r, $i ) => array_filter( $r, fn( $c ) => $c !== $i ),
		$input
	);
	return every( array_keys( $input ), 'is_int' ) ? array_values( $result ) : $result;
}

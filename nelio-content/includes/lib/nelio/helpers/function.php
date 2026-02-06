<?php

namespace Nelio_Content\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a function that returns the result of invoking the given functions, where
 * each successive invocation is supplied the return value of the previous.
 *
 * @param callable $func     The first function to invoke.
 * @param callable ...$funcs The remaining functions to invoke.
 *
 * @return callable
 */
function flow( $func, ...$funcs ) {
	return fn( $value ) => array_reduce(
		array( $func, ...$funcs ),
		fn( $v, $f ) => call_user_func( $f, $v ),
		$value
	);
}

/**
 * Returns the given argument as is.
 *
 * @param mixed $value Any value.
 *
 * @return mixed The $value.
 */
function identity( $value ) {
	return $value;
}

/**
 * Creates a function that negates the result of `$predicate`.
 *
 * @param callable $predicate The predicate to negate.
 *
 * @return callable The new negated predicate.
 */
function not( $predicate ) {
	/** @phpstan-ignore-next-line It’s too complicated to properly type */
	return fn( ...$values ) => ! call_user_func( $predicate, ...$values );
}

<?php

namespace Nelio_Content\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Checks if `$predicate` returns truthy for all elements of `$collection`.
 * The predicate is invoked with one argument: ($value).
 *
 * @param array<mixed> $collection The collection to iterate over.
 * @param callable     $predicate  The function invoked per iteration.
 *
 * @return bool `true` if any element passes the predicate check, else `false`.
 */
function every( array $collection, callable $predicate ): bool {
	return ! some( $collection, not( $predicate ) );
}

/**
 * Iterates over elements of `$collection`, returning the first element
 * `$predicate` returns truthy for. The predicate is invoked with one
 * argument: ($value).
 *
 * @template T
 *
 * @param array<T> $collection The collection to iterate over.
 * @param callable $predicate  The function invoked per iteration.
 *
 * @return T|null the the matched element, else `null`
 */
function find( $collection, $predicate = null ) {
	$predicate = is_null( $predicate ) ? __NAMESPACE__ . '\identity' : $predicate;
	return array_reduce(
		$collection,
		/** @phpstan-ignore-next-line Too complicated to type */
		fn( $result, $item ) => empty( $result ) && $predicate( $item ) ? $item : $result,
		null
	);
}

/**
 * Creates a dictionary composed of keys generated from the results of running each element of $collection thru
 * $iteratee. The corresponding value of each key is the last element responsible for generating the key. The
 * $iteratee is invoked with one argument.
 *
 * @template T
 *
 * @param array<T>        $collection The collection to iterate over.
 * @param callable|string $iteratee   The iteratee to transform keys.
 *
 * @return array<string|int,T> The composed aggregate dictionary.
 */
function key_by( $collection, $iteratee = null ) {
	$iteratee = is_null( $iteratee ) ? __NAMESPACE__ . '\identity' : $iteratee;
	/** @phpstan-ignore-next-line Too complicated to type */
	$is_callable = is_callable( $iteratee ) && ! some( $collection, fn( $i ) => is_string( $iteratee ) && isset( $i[ $iteratee ] ) );
	$keys        = array_map(
		fn( $item ) => $is_callable
			? call_user_func( $iteratee, $item )
			/** @phpstan-ignore-next-line Too complicated to type */
			: $item[ $iteratee ] ?? null,
		$collection
	);
	/** @phpstan-ignore-next-line Too complicated to type */
	return array_combine( $keys, $collection );
}

/**
 * Checks if `$predicate` returns truthy for any element of `collection`.
 * The predicate is invoked with one argument: ($value).
 *
 * @template T
 *
 * @param array<T>         $collection The collection to iterate over.
 * @param callable(T):bool $predicate  The function invoked per iteration.
 *
 * @return bool `true` if any element passes the predicate check, else `false`.
 */
function some( $collection, $predicate ) {
	return array_reduce(
		$collection,
		fn( $result, $item ) => $result || ! empty( call_user_func( $predicate, $item ) ),
		false
	);
}

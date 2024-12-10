<?php

namespace Nelio_Content\Zod;

class EnumSchema extends Schema {

	private array $values;

	public static function make( array $values ): EnumSchema {
		$instance         = new self();
		$instance->values = $values;
		return $instance;
	}//end make()

	public function parse_value( $value ) {
		if ( ! in_array( $value, $this->values, true ) ) {
			throw new \Exception(
				sprintf(
					'Expected one of %1$s, but %2$s found.',
					sprintf( '(%s)', esc_html( implode( ', ', $this->values ) ) ),
					esc_html( $value )
				)
			);
		}//end if

		return $value;
	}//end parse_value()
}//end class

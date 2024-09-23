<?php

namespace Nelio_Content\Zod;

class RecordSchema extends Schema {

	protected Schema $key_schema;
	protected Schema $value_schema;

	public static function make( Schema $key_schema, Schema $value_schema ): RecordSchema {
		$instance               = new self();
		$instance->key_schema   = $key_schema;
		$instance->value_schema = $value_schema;
		return $instance;
	}//end make()

	public function parse_value( $value ) {
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}//end if

		if ( ! is_array( $value ) ) {
			throw new \Exception(
				sprintf(
					'Expected a record, but %s found.',
					gettype( $value )
				)
			);
		}//end if

		$result = array();
		foreach ( $value as $key => $val ) {
			try {
				$this->key_schema->parse( $key );
			} catch ( \Exception $e ) {
				throw new \Exception( $this->add_path( 'Invalid key:' . $e->getMessage(), "{$key}" ) );
			}//end try

			try {
				$result[ $key ] = $this->value_schema->parse( $val );
			} catch ( \Exception $e ) {
				throw new \Exception( $this->add_path( 'Invalid value:' . $e->getMessage(), "{$key}" ) );
			}//end try
		}//end foreach

		return array_filter( $result, fn( $p ) => ! is_null( $p ) );
	}//end parse_value()

}//end class

<?php

namespace Nelio_Content\Zod;

require_once dirname( __FILE__ ) . '/abstract-schema.php';
require_once dirname( __FILE__ ) . '/array-schema.php';
require_once dirname( __FILE__ ) . '/boolean-schema.php';
require_once dirname( __FILE__ ) . '/enum-schema.php';
require_once dirname( __FILE__ ) . '/literal-schema.php';
require_once dirname( __FILE__ ) . '/number-schema.php';
require_once dirname( __FILE__ ) . '/object-schema.php';
require_once dirname( __FILE__ ) . '/record-schema.php';
require_once dirname( __FILE__ ) . '/string-schema.php';
require_once dirname( __FILE__ ) . '/union-schema.php';

class Zod {
	public static function array( Schema $schema ): ArraySchema {
		return ArraySchema::make( $schema );
	}//end array()

	public static function boolean(): BooleanSchema {
		return BooleanSchema::make();
	}//end boolean()

	public static function enum( array $values ): EnumSchema {
		return EnumSchema::make( $values );
	}//end enum()

	public static function literal( $value ): LiteralSchema {
		return LiteralSchema::make( $value );
	}//end literal()

	public static function number(): NumberSchema {
		return NumberSchema::make();
	}//end number()

	public static function record( Schema $key_schema, Schema $value_schema ): RecordSchema {
		return RecordSchema::make( $key_schema, $value_schema );
	}//end record()

	public static function object( array $schema ): ObjectSchema {
		return ObjectSchema::make( $schema );
	}//end object()

	public static function string(): StringSchema {
		return StringSchema::make();
	}//end string()

	public static function union( array $schemas ): UnionSchema {
		return UnionSchema::make( $schemas );
	}//end union()

}//end class

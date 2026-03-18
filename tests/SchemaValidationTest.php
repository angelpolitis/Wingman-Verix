<?php
    /**
     * Project Name:    Wingman Verix - Schema Validation Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 17 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Tests namespace.
    namespace Wingman\Verix\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Verix\Exceptions\SchemaViolationException;
    use Wingman\Verix\Facades\Schema;
    use Wingman\Verix\Registries\Registry;

    /**
     * Tests for Schema::validate(), covering all built-in primitive types, composite types,
     * parameterised constraints, strict vs. non-strict coercion, and the `throwIfInvalid()` bridge.
     */
    class SchemaValidationTest extends Test {

        /**
         * Creates a fresh, isolated registry for every test.
         * @return Registry The isolated registry.
         */
        private function makeRegistry () : Registry {
            return new Registry();
        }

        // ─── Primitive: string ───────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string — Accepts String Value",
            description: "A string value passes validation against the 'string' schema."
        )]
        public function testStringAcceptsString () : void {
            $result = Schema::from("string", $this->makeRegistry())->validate("hello");

            $this->assertTrue($result->isValid(), "'string' schema should accept a string value.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string — Rejects Integer (Strict)",
            description: "An integer value fails validation against 'string' in strict mode."
        )]
        public function testStringRejectsIntegerStrict () : void {
            $result = Schema::from("string", $this->makeRegistry())->validate(42, true);

            $this->assertFalse($result->isValid(), "'string' schema should reject an integer in strict mode.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string — Coerces Integer (Non-Strict)",
            description: "An integer value is coerced to string and passes validation when strict mode is off."
        )]
        public function testStringCoercesIntegerNonStrict () : void {
            $result = Schema::from("string", $this->makeRegistry())->validate(42, false);

            $this->assertTrue($result->isValid(), "'string' schema should coerce an integer to string in non-strict mode.");
            $this->assertTrue($result->getValue() === "42", "Coerced value should be the string '42'.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string — Rejects Array",
            description: "An array cannot be coerced to string and is rejected by the 'string' schema."
        )]
        public function testStringRejectsArray () : void {
            $result = Schema::from("string", $this->makeRegistry())->validate([], false);

            $this->assertFalse($result->isValid(), "'string' schema should reject an array even in non-strict mode.");
        }

        // ─── Primitive: bool ─────────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "bool — Accepts true",
            description: "The boolean value true passes strict validation."
        )]
        public function testBoolAcceptsTrue () : void {
            $result = Schema::from("bool", $this->makeRegistry())->validate(true, true);

            $this->assertTrue($result->isValid(), "'bool' schema should accept true.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "bool — Accepts false",
            description: "The boolean value false passes strict validation."
        )]
        public function testBoolAcceptsFalse () : void {
            $result = Schema::from("bool", $this->makeRegistry())->validate(false, true);

            $this->assertTrue($result->isValid(), "'bool' schema should accept false.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "bool — Rejects String (Strict)",
            description: "A plain string value is rejected by 'bool' in strict mode."
        )]
        public function testBoolRejectsStringStrict () : void {
            $result = Schema::from("bool", $this->makeRegistry())->validate("abc", true);

            $this->assertFalse($result->isValid(), "'bool' schema should reject a non-boolean string in strict mode.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "bool — Coerces 'true' String (Non-Strict)",
            description: "The string 'true' is coerced to boolean true in non-strict mode."
        )]
        public function testBoolCoercesStringNonStrict () : void {
            $result = Schema::from("bool", $this->makeRegistry())->validate("true", false);

            $this->assertTrue($result->isValid(), "'bool' schema should coerce 'true' string to boolean in non-strict mode.");
            $this->assertTrue($result->getValue() === true, "Coerced value should be boolean true.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "bool — Rejects Array",
            description: "An array is rejected by the 'bool' schema in both strict and non-strict modes."
        )]
        public function testBoolRejectsArray () : void {
            $result = Schema::from("bool", $this->makeRegistry())->validate([]);

            $this->assertFalse($result->isValid(), "'bool' schema should reject an array.");
        }

        // ─── Primitive: int ──────────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int — Accepts Integer",
            description: "An integer value passes validation against 'int'."
        )]
        public function testIntAcceptsInteger () : void {
            $result = Schema::from("int", $this->makeRegistry())->validate(42);

            $this->assertTrue($result->isValid(), "'int' schema should accept an integer value.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int — Rejects Non-Numeric String",
            description: "A non-numeric string like 'hello' fails validation against 'int'."
        )]
        public function testIntRejectsNonNumericString () : void {
            $result = Schema::from("int", $this->makeRegistry())->validate("hello", strict: true);

            $this->assertFalse($result->isValid(), "'int' schema should reject a non-numeric string in strict mode.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int — Rejects null",
            description: "A null value fails validation against 'int'."
        )]
        public function testIntRejectsNull () : void {
            $result = Schema::from("int", $this->makeRegistry())->validate(null);

            $this->assertFalse($result->isValid(), "'int' schema should reject null.");
        }

        // ─── Primitive: float ────────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "float — Accepts Float",
            description: "A float value passes validation against 'float'."
        )]
        public function testFloatAcceptsFloat () : void {
            $result = Schema::from("float", $this->makeRegistry())->validate(3.14);

            $this->assertTrue($result->isValid(), "'float' schema should accept a float value.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "float — Rejects Non-Numeric String",
            description: "A non-numeric string fails validation against 'float'."
        )]
        public function testFloatRejectsNonNumericString () : void {
            $result = Schema::from("float", $this->makeRegistry())->validate("not-a-number", strict: true);

            $this->assertFalse($result->isValid(), "'float' schema should reject a non-numeric string in strict mode.");
        }

        // ─── Primitive: null ─────────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "null — Accepts null",
            description: "The PHP null value passes validation against 'null'."
        )]
        public function testNullAcceptsNull () : void {
            $result = Schema::from("null", $this->makeRegistry())->validate(null);

            $this->assertTrue($result->isValid(), "'null' schema should accept null.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "null — Rejects Non-null",
            description: "Any non-null value fails validation against 'null'."
        )]
        public function testNullRejectsNonNull () : void {
            $result = Schema::from("null", $this->makeRegistry())->validate("something");

            $this->assertFalse($result->isValid(), "'null' schema should reject a non-null value.");
        }

        // ─── Primitive: any ──────────────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "any — Accepts All Value Types",
            description: "The 'any' schema accepts strings, integers, booleans, arrays, and null."
        )]
        public function testAnyAcceptsAllTypes () : void {
            $reg = $this->makeRegistry();
            $schema = Schema::from("any", $reg);

            $this->assertTrue($schema->validate("string")->isValid(), "'any' should accept string.");
            $this->assertTrue($schema->validate(42)->isValid(), "'any' should accept integer.");
            $this->assertTrue($schema->validate(true)->isValid(), "'any' should accept bool.");
            $this->assertTrue($schema->validate(null)->isValid(), "'any' should accept null.");
            $this->assertTrue($schema->validate([])->isValid(), "'any' should accept array.");
        }

        // ─── Parameterised Constraints ────────────────────────────────────────────

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int<min=1, max=10> — In-Range Value Is Valid",
            description: "An integer within the min/max bounds passes the constrained schema."
        )]
        public function testIntInRangeIsValid () : void {
            $result = Schema::from("int<min=1, max=10>", $this->makeRegistry())->validate(5);

            $this->assertTrue($result->isValid(), "Integer 5 should be valid for 'int<min=1, max=10>'.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int<min=1, max=10> — Below-Minimum Is Invalid",
            description: "An integer below the min bound fails the constrained schema."
        )]
        public function testIntBelowMinIsInvalid () : void {
            $result = Schema::from("int<min=1, max=10>", $this->makeRegistry())->validate(0);

            $this->assertFalse($result->isValid(), "Integer 0 should be invalid for 'int<min=1, max=10>'.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "int<min=1, max=10> — Above-Maximum Is Invalid",
            description: "An integer above the max bound fails the constrained schema."
        )]
        public function testIntAboveMaxIsInvalid () : void {
            $result = Schema::from("int<min=1, max=10>", $this->makeRegistry())->validate(11);

            $this->assertFalse($result->isValid(), "Integer 11 should be invalid for 'int<min=1, max=10>'.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string<length=5> — Exact-Length String Is Valid",
            description: "A string of exactly the required length passes the length constraint."
        )]
        public function testStringExactLengthValid () : void {
            $result = Schema::from("string<length=5>", $this->makeRegistry())->validate("hello");

            $this->assertTrue($result->isValid(), "5-character string should be valid for 'string<length=5>'.");
        }

        #[Group("Schema — Validation — Primitives")]
        #[Define(
            name: "string<length=5> — Wrong-Length String Is Invalid",
            description: "A string of the wrong length fails the length constraint."
        )]
        public function testStringWrongLengthInvalid () : void {
            $result = Schema::from("string<length=5>", $this->makeRegistry())->validate("hi");

            $this->assertFalse($result->isValid(), "2-character string should be invalid for 'string<length=5>'.");
        }

        // ─── Composites: Union ────────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string|int — Accepts String",
            description: "A string value passes the 'string|int' union."
        )]
        public function testUnionAcceptsString () : void {
            $result = Schema::from("string|int", $this->makeRegistry())->validate("hello");

            $this->assertTrue($result->isValid(), "'string|int' should accept a string.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string|int — Accepts Integer",
            description: "An integer value passes the 'string|int' union."
        )]
        public function testUnionAcceptsInt () : void {
            $result = Schema::from("string|int", $this->makeRegistry())->validate(42);

            $this->assertTrue($result->isValid(), "'string|int' should accept an integer.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string|int — Rejects null",
            description: "A null value fails the 'string|int' union since neither branch matches."
        )]
        public function testUnionRejectsNull () : void {
            $result = Schema::from("string|int", $this->makeRegistry())->validate(null, true);

            $this->assertFalse($result->isValid(), "'string|int' should reject null in strict mode.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string? — Accepts null",
            description: "The optional shorthand 'string?' accepts null in addition to strings."
        )]
        public function testOptionalAcceptsNull () : void {
            $result = Schema::from("string?", $this->makeRegistry())->validate(null);

            $this->assertTrue($result->isValid(), "'string?' should accept null.");
        }

        // ─── Composites: Arrays ───────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string[] — Accepts Array Of Strings",
            description: "An array of valid strings passes the 'string[]' schema."
        )]
        public function testArrayAcceptsStringArray () : void {
            $result = Schema::from("string[]", $this->makeRegistry())->validate(["a", "b", "c"]);

            $this->assertTrue($result->isValid(), "'string[]' should accept an array of strings.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string[] — Rejects Non-Array",
            description: "A non-array value fails the 'string[]' schema."
        )]
        public function testArrayRejectsNonArray () : void {
            $result = Schema::from("string[]", $this->makeRegistry())->validate("hello", true);

            $this->assertFalse($result->isValid(), "'string[]' should reject a non-array value.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "string[] — Rejects Array With Non-String Element",
            description: "An array containing a non-string element fails 'string[]' in strict mode."
        )]
        public function testArrayRejectsWrongElementType () : void {
            $result = Schema::from("string[]", $this->makeRegistry())->validate(["hello", 42], true);

            $this->assertFalse($result->isValid(), "'string[]' should reject mixed arrays in strict mode.");
        }

        // ─── Composites: Tuples ───────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "[string, int] — Accepts Matching Tuple",
            description: "An array matching the tuple positions and types passes validation."
        )]
        public function testTupleAcceptsMatchingValues () : void {
            $result = Schema::from("[string, int]", $this->makeRegistry())->validate(["hello", 42]);

            $this->assertTrue($result->isValid(), "A matching tuple should pass validation.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "[string, int] — Rejects Wrong Position Type",
            description: "A tuple where the second element does not match fails validation."
        )]
        public function testTupleRejectsWrongPositionType () : void {
            $result = Schema::from("[string, int]", $this->makeRegistry())->validate(["hello", "world"], true);

            $this->assertFalse($result->isValid(), "A tuple with a wrong element type should fail.");
        }

        // ─── Composites: Structs ──────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "struct — Accepts Matching Object",
            description: "An associative array with all required fields passes struct validation."
        )]
        public function testStructAcceptsMatchingData () : void {
            $result = Schema::from("{name: string, age: int}", $this->makeRegistry())->validate(["name" => "Alice", "age" => 30]);

            $this->assertTrue($result->isValid(), "Matching associative array should pass struct validation.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "struct — Rejects Missing Required Field",
            description: "An associative array missing a non-optional field fails struct validation."
        )]
        public function testStructRejectsMissingRequiredField () : void {
            $result = Schema::from("{name: string, age: int}", $this->makeRegistry())->validate(["name" => "Alice"], true);

            $this->assertFalse($result->isValid(), "A struct missing 'age' should fail.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "struct — Accepts Empty Object For All-Optional Schema",
            description: "An empty array passes a struct schema where all fields are optional."
        )]
        public function testStructAcceptsEmptyForAllOptionalSchema () : void {
            $result = Schema::from("{name?: string, age?: int}", $this->makeRegistry())->validate([]);

            $this->assertTrue($result->isValid(), "An empty array should pass a struct where all fields are optional.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "struct — Rejects Extra Fields In Exact Mode",
            description: "An associative array with undeclared extra fields fails when the struct is exact."
        )]
        public function testStructRejectsExtraFieldsInExactMode () : void {
            $result = Schema::from("{name: string}", $this->makeRegistry())->validate(["name" => "Alice", "extra" => "oops"], true);

            $this->assertFalse($result->isValid(), "Exact struct should reject undeclared fields.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "struct — Rejects Non-Array",
            description: "A non-array value is immediately rejected by a struct schema."
        )]
        public function testStructRejectsNonArray () : void {
            $result = Schema::from("{name: string}", $this->makeRegistry())->validate("not-an-object");

            $this->assertFalse($result->isValid(), "A struct schema should reject a non-array value.");
        }

        // ─── Literals ─────────────────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "Literal 42 — Accepts Matching Integer",
            description: "The exact integer value 42 passes validation against the literal schema '42'."
        )]
        public function testLiteralAcceptsMatchingInt () : void {
            $result = Schema::from("42", $this->makeRegistry())->validate(42);

            $this->assertTrue($result->isValid(), "Literal '42' should accept the integer 42.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "Literal 42 — Rejects Different Integer",
            description: "A different integer value fails validation against the literal schema '42'."
        )]
        public function testLiteralRejectsNonMatchingValue () : void {
            $result = Schema::from("42", $this->makeRegistry())->validate(43);

            $this->assertFalse($result->isValid(), "Literal '42' should reject the integer 43.");
        }

        // ─── Enum ─────────────────────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "enum{'a','b','c'} — Accepts Member Value",
            description: "A value that appears in the enum definition passes validation."
        )]
        public function testEnumAcceptsMember () : void {
            $result = Schema::from("enum{'a', 'b', 'c'}", $this->makeRegistry())->validate("b");

            $this->assertTrue($result->isValid(), "A value in the enum should pass validation.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "enum{'a','b','c'} — Rejects Non-Member",
            description: "A value not in the enum fails validation."
        )]
        public function testEnumRejectsNonMember () : void {
            $result = Schema::from("enum{'a', 'b', 'c'}", $this->makeRegistry())->validate("d");

            $this->assertFalse($result->isValid(), "A value not in the enum should fail validation.");
        }

        // ─── Error Details ────────────────────────────────────────────────────────

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "Errors Array — Empty On Valid Result",
            description: "A passing validation result has an empty errors array."
        )]
        public function testValidResultHasNoErrors () : void {
            $result = Schema::from("string", $this->makeRegistry())->validate("hello");

            $this->assertTrue($result->getErrors() === [], "A valid result should have no errors.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "Errors Array — Non-Empty On Failed Result",
            description: "A failing validation result carries at least one error."
        )]
        public function testInvalidResultHasErrors () : void {
            $result = Schema::from("{name: string}", $this->makeRegistry())->validate([]);

            $this->assertTrue(count($result->getErrors()) > 0, "An invalid result should have at least one error.");
        }

        #[Group("Schema — Validation — Composites")]
        #[Define(
            name: "throwIfInvalid() — Throws When Schema Violated",
            description: "Calling throwIfInvalid() on a failed validate() result throws a SchemaViolationException."
        )]
        public function testThrowIfInvalidThrowsOnSchemaViolation () : void {
            $thrown = false;

            try {
                Schema::from("{name: string}", $this->makeRegistry())->validate([])->throwIfInvalid();
            }
            catch (SchemaViolationException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "throwIfInvalid() should throw SchemaViolationException on a schema violation.");
        }
    }
?>
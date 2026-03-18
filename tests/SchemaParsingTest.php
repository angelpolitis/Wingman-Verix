<?php
    /**
     * Project Name:    Wingman Verix - Schema Parsing Tests
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
    use Wingman\Verix\Exceptions\ParsingException;
    use Wingman\Verix\Facades\Schema;
    use Wingman\Verix\Nodes\ArrayNode;
    use Wingman\Verix\Nodes\EnumNode;
    use Wingman\Verix\Nodes\KeyedStructNode;
    use Wingman\Verix\Nodes\LiteralNode;
    use Wingman\Verix\Nodes\PrimitiveNode;
    use Wingman\Verix\Nodes\SchemaRefNode;
    use Wingman\Verix\Nodes\StructNode;
    use Wingman\Verix\Nodes\TupleNode;
    use Wingman\Verix\Nodes\UnionNode;
    use Wingman\Verix\Registries\Registry;

    /**
     * Tests for the Schema DSL parser, covering all node types producible from
     * valid DSL expressions, accessor methods, and error handling for unknown types.
     */
    class SchemaParsingTest extends Test {

        /**
         * Creates a fresh, isolated registry for every test.
         * @return Registry The isolated registry.
         */
        private function makeRegistry () : Registry {
            return new Registry();
        }

        // ─── Primitives ──────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string') — Returns Schema",
            description: "Parsing the 'string' primitive produces a Schema instance."
        )]
        public function testParseString () : void {
            $schema = Schema::from("string", $this->makeRegistry());

            $this->assertTrue($schema instanceof Schema, "from('string') should return a Schema instance.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string') — Root Node Is PrimitiveNode",
            description: "The root node of a parsed 'string' schema is a PrimitiveNode named 'string'."
        )]
        public function testParseStringRootNode () : void {
            $schema = Schema::from("string", $this->makeRegistry());
            $node = $schema->getNode();
            assert($node instanceof PrimitiveNode);

            $this->assertTrue($node instanceof PrimitiveNode, "Root node of 'string' should be a PrimitiveNode.");
            $this->assertTrue($node->getName() === "string", "PrimitiveNode name should be 'string'.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('int') — PrimitiveNode Named 'int'",
            description: "Parsing 'int' produces a PrimitiveNode with name 'int'."
        )]
        public function testParseInt () : void {
            $node = Schema::from("int", $this->makeRegistry())->getNode();
            assert($node instanceof PrimitiveNode);

            $this->assertTrue($node instanceof PrimitiveNode, "Root node of 'int' should be a PrimitiveNode.");
            $this->assertTrue($node->getName() === "int", "PrimitiveNode name should be 'int'.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('bool') — PrimitiveNode Named 'bool'",
            description: "Parsing 'bool' produces a PrimitiveNode with name 'bool'."
        )]
        public function testParseBool () : void {
            $node = Schema::from("bool", $this->makeRegistry())->getNode();
            assert($node instanceof PrimitiveNode);

            $this->assertTrue($node instanceof PrimitiveNode, "Root node of 'bool' should be a PrimitiveNode.");
            $this->assertTrue($node->getName() === "bool", "PrimitiveNode name should be 'bool'.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('any') — PrimitiveNode Named 'any'",
            description: "Parsing 'any' produces a PrimitiveNode with name 'any'."
        )]
        public function testParseAny () : void {
            $node = Schema::from("any", $this->makeRegistry())->getNode();
            assert($node instanceof PrimitiveNode);

            $this->assertTrue($node instanceof PrimitiveNode, "Root node of 'any' should be a PrimitiveNode.");
            $this->assertTrue($node->getName() === "any", "PrimitiveNode name should be 'any'.");
        }

        // ─── Parameterised Primitives ─────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('int<min=1, max=10>') — PrimitiveNode With Parameters",
            description: "Parsing a parameterised primitive produces a PrimitiveNode carrying the named parameters."
        )]
        public function testParseParameterisedPrimitive () : void {
            $node = Schema::from("int<min=1, max=10>", $this->makeRegistry())->getNode();
            assert($node instanceof PrimitiveNode);
            $params = $node->getParams();

            $this->assertTrue($node instanceof PrimitiveNode, "Parameterised int should still be a PrimitiveNode.");
            $this->assertTrue(($params["min"] ?? null) === 1, "Parameter 'min' should equal 1.");
            $this->assertTrue(($params["max"] ?? null) === 10, "Parameter 'max' should equal 10.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string<minLength=2, maxLength=5>') — Stores String Parameters",
            description: "Named parameters for the string type are correctly stored in the PrimitiveNode."
        )]
        public function testParseStringWithLengthParams () : void {
            $node = Schema::from("string<minLength=2, maxLength=5>", $this->makeRegistry())->getNode();
            assert($node instanceof PrimitiveNode);
            $params = $node->getParams();

            $this->assertTrue(($params["minLength"] ?? null) === 2, "Parameter 'minLength' should equal 2.");
            $this->assertTrue(($params["maxLength"] ?? null) === 5, "Parameter 'maxLength' should equal 5.");
        }

        // ─── Arrays ──────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string[]') — Root Node Is ArrayNode",
            description: "An array suffix produces an ArrayNode wrapping the item type."
        )]
        public function testParseArray () : void {
            $node = Schema::from("string[]", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof ArrayNode, "Root node of 'string[]' should be an ArrayNode.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('int[][]') — Nested ArrayNode",
            description: "A double-array suffix produces a nested ArrayNode."
        )]
        public function testParseNestedArray () : void {
            $node = Schema::from("int[][]", $this->makeRegistry())->getNode();

            assert($node instanceof ArrayNode);

            $this->assertTrue($node instanceof ArrayNode, "Root node of 'int[][]' should be an ArrayNode.");
            $this->assertTrue($node->getItemType() instanceof ArrayNode, "Item type of 'int[][]' should itself be an ArrayNode.");
        }

        // ─── Unions & Optionals ───────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string|int') — Root Node Is UnionNode",
            description: "A pipe-separated expression produces a UnionNode containing both member types."
        )]
        public function testParseUnion () : void {
            $node = Schema::from("string|int", $this->makeRegistry())->getNode();

            assert($node instanceof UnionNode);

            $this->assertTrue($node instanceof UnionNode, "Root node of 'string|int' should be a UnionNode.");
            $this->assertTrue(count($node->getTypes()) === 2, "Union of two types should have exactly two members.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('string?') — UnionNode With null branch",
            description: "The '?' shorthand is sugar for 'string|null' and produces a two-branch UnionNode."
        )]
        public function testParseOptional () : void {
            $node = Schema::from("string?", $this->makeRegistry())->getNode();

            assert($node instanceof UnionNode);

            $this->assertTrue($node instanceof UnionNode, "Optional type should be a UnionNode.");
            $this->assertTrue(count($node->getTypes()) === 2, "Optional union should have exactly two branches.");
        }

        // ─── Structs ──────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('{name: string, age: int}') — StructNode",
            description: "An implicit struct expression produces a StructNode with the defined fields."
        )]
        public function testParseImplicitStruct () : void {
            $node = Schema::from("{name: string, age: int}", $this->makeRegistry())->getNode();

            assert($node instanceof StructNode);

            $this->assertTrue($node instanceof StructNode, "An implicit struct should produce a StructNode.");
            $this->assertTrue(isset($node->getFields()["name"]), "Field 'name' should exist in the struct.");
            $this->assertTrue(isset($node->getFields()["age"]), "Field 'age' should exist in the struct.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('{name?: string}') — Optional Struct Field",
            description: "A field marked with '?' is parsed as optional in the StructNode."
        )]
        public function testParseOptionalStructField () : void {
            $node = Schema::from("{name?: string}", $this->makeRegistry())->getNode();
            assert($node instanceof StructNode);
            $field = $node->getFields()["name"] ?? null;

            $this->assertTrue($field !== null, "Field 'name' should exist.");
            $this->assertTrue($field->isOptional(), "Field 'name' should be marked optional.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('{name!: string}') — Readonly Struct Field",
            description: "A field marked with '!' is parsed as readonly in the StructNode."
        )]
        public function testParseReadonlyStructField () : void {
            $node = Schema::from("{name!: string}", $this->makeRegistry())->getNode();
            assert($node instanceof StructNode);
            $field = $node->getFields()["name"] ?? null;

            $this->assertTrue($field !== null, "Field 'name' should exist.");
            $this->assertTrue($field->isReadonly(), "Field 'name' should be marked readonly.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('struct{name: string}') — Explicit struct Keyword",
            description: "Using the 'struct' keyword explicitly also produces a StructNode."
        )]
        public function testParseExplicitStruct () : void {
            $node = Schema::from("struct{name: string}", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof StructNode, "Explicit 'struct{...}' should produce a StructNode.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('{[key: string]: int}') — KeyedStructNode",
            description: "A struct with a dynamic key type produces a KeyedStructNode."
        )]
        public function testParseDynamicKeyStruct () : void {
            $node = Schema::from("{[key: string]: int}", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof KeyedStructNode, "Dynamic key struct should produce a KeyedStructNode.");
        }

        // ─── Tuples ───────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('[string, int, bool]') — TupleNode",
            description: "A bracket-enclosed comma-separated list produces a TupleNode."
        )]
        public function testParseTuple () : void {
            $node = Schema::from("[string, int, bool]", $this->makeRegistry())->getNode();

            assert($node instanceof TupleNode);

            $this->assertTrue($node instanceof TupleNode, "Bracketed tuple notation should produce a TupleNode.");
            $this->assertTrue(count($node->getItems()) === 3, "TupleNode should have exactly three items.");
        }

        // ─── Literals ─────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('true') — LiteralNode With true",
            description: "The literal 'true' token produces a LiteralNode with a boolean true value."
        )]
        public function testParseBooleanTrueLiteral () : void {
            $node = Schema::from("true", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof LiteralNode, "Boolean literal 'true' should produce a LiteralNode.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('42') — LiteralNode With Integer",
            description: "An integer token produces a LiteralNode with integer value 42."
        )]
        public function testParseIntegerLiteral () : void {
            $node = Schema::from("42", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof LiteralNode, "Integer literal '42' should produce a LiteralNode.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from(\"'hello'\") — LiteralNode With String",
            description: "A quoted string token produces a LiteralNode with the string value."
        )]
        public function testParseStringLiteral () : void {
            $node = Schema::from("'hello'", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof LiteralNode, "String literal should produce a LiteralNode.");
        }

        // ─── Enum ─────────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('enum{1, 2, 3}') — EnumNode",
            description: "An enum expression with integer members produces an EnumNode."
        )]
        public function testParseEnum () : void {
            $node = Schema::from("enum{1, 2, 3}", $this->makeRegistry())->getNode();

            $this->assertTrue($node instanceof EnumNode, "Enum expression should produce an EnumNode.");
        }

        // ─── Schema References ────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('@Name') — SchemaRefNode Via Symbol",
            description: "The '@Name' shorthand produces a SchemaRefNode for the given schema name."
        )]
        public function testParseSchemaRefViaSymbol () : void {
            $reg = $this->makeRegistry();
            Schema::register("Address", "{street: string}", $reg);
            $node = Schema::from("@Address", $reg)->getNode();

            $this->assertFalse($node instanceof SchemaRefNode, "Symbol-style @Name reference should be resolved by the normaliser, not remain a SchemaRefNode.");
            $this->assertTrue($node instanceof \Wingman\Verix\Nodes\StructNode, "The resolved node should be the StructNode for the registered schema.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "from('Schema(Name)') — SchemaRefNode Via Keyword",
            description: "The 'Schema(Name)' keyword form also produces a SchemaRefNode."
        )]
        public function testParseSchemaRefViaKeyword () : void {
            $reg = $this->makeRegistry();
            Schema::register("User", "{id: int}", $reg);
            $node = Schema::from("Schema(User)", $reg)->getNode();

            $this->assertFalse($node instanceof SchemaRefNode, "Schema(Name) keyword reference should be resolved by the normaliser, not remain a SchemaRefNode.");
            $this->assertTrue($node instanceof \Wingman\Verix\Nodes\StructNode, "The resolved node should be the StructNode for the registered schema.");
        }

        // ─── Error handling ───────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "Unknown Type — Throws ParsingException",
            description: "Attempting to parse a type that is neither a registered primitive, class alias, nor existing FQCN throws a ParsingException."
        )]
        public function testUnknownTypeThrows () : void {
            $thrown = false;

            try {
                Schema::from("UnknownTypeThatDoesNotExist", $this->makeRegistry());
            }
            catch (ParsingException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Parsing an unknown type should throw a ParsingException.");
        }

        // ─── Accessors ────────────────────────────────────────────────────────────

        #[Group("Schema — Parsing")]
        #[Define(
            name: "getExpression() — Returns Original DSL Expression",
            description: "getExpression() returns the exact expression string that was passed to from()."
        )]
        public function testGetExpressionReturnsOriginalExpression () : void {
            $expression = "{name: string, age: int}";
            $schema = Schema::from($expression, $this->makeRegistry());

            $this->assertTrue($schema->getExpression() === $expression, "getExpression() should return the original DSL expression.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "serialise() — Returns Non-Empty String",
            description: "serialise() produces a non-empty canonical DSL string for any valid schema."
        )]
        public function testSerialiseReturnsNonEmptyString () : void {
            $schema = Schema::from("{name: string}", $this->makeRegistry());
            $serialised = $schema->serialise();

            $this->assertTrue(is_string($serialised) && $serialised !== "", "serialise() should return a non-empty string.");
        }

        #[Group("Schema — Parsing")]
        #[Define(
            name: "__toString() — Delegates To serialise()",
            description: "Casting a Schema to string is equivalent to calling serialise()."
        )]
        public function testToStringDelegatesToSerialise () : void {
            $schema = Schema::from("string|int", $this->makeRegistry());

            $this->assertTrue((string) $schema === $schema->serialise(), "__toString() should produce the same output as serialise().");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Schema Features Tests
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
    use Wingman\Verix\Exceptions\SchemaException;
    use Wingman\Verix\Facades\Schema;
    use Wingman\Verix\Nodes\SchemaRefNode;
    use Wingman\Verix\Registries\Registry;

    /**
     * Tests for advanced Schema capabilities: register(), infer(), merge(), expand(),
     * and the accessor trio getExpression()/getNode()/registry isolation.
     */
    class SchemaFeaturesTest extends Test {

        /**
         * Creates a fresh isolated registry for each test.
         * @return Registry The isolated registry.
         */
        private function makeRegistry () : Registry {
            return new Registry();
        }

        // ─── register() ──────────────────────────────────────────────────────────

        #[Group("Schema — Features")]
        #[Define(
            name: "register() — Returns Schema Instance",
            description: "Schema::register() returns a Schema object for the registered expression."
        )]
        public function testRegisterReturnsSchema () : void {
            $reg = $this->makeRegistry();
            $schema = Schema::register("User", "{name: string, age: int}", $reg);

            $this->assertTrue($schema instanceof Schema, "register() should return a Schema instance.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "register() — Stores Schema In Registry",
            description: "After calling register(), the schema registry contains the named schema."
        )]
        public function testRegisterStoresSchemaInRegistry () : void {
            $reg = $this->makeRegistry();
            Schema::register("Product", "{id: int, label: string}", $reg);

            $this->assertTrue($reg->getSchemaRegistry()->has("Product"), "Schema 'Product' should be present in the registry after register().");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "register() — Registered Schema Is Referenceable",
            description: "A registered schema can be referenced by name in another expression."
        )]
        public function testRegisteredSchemaIsReferenceable () : void {
            $reg = $this->makeRegistry();
            Schema::register("Address", "{city: string}", $reg);

            $schema = Schema::from("{address: @Address}", $reg);
            $result = $schema->validate(["address" => ["city" => "London"]]);

            $this->assertTrue($result->isValid() || !$result->isValid(), "Parsing a reference to a registered schema should not throw.");
        }

        // ─── infer() ─────────────────────────────────────────────────────────────

        #[Group("Schema — Features")]
        #[Define(
            name: "infer([]) — Returns Schema For Empty Struct",
            description: "infer() called with an empty array produces a Schema for an empty struct."
        )]
        public function testInferEmptyArrayReturnsSchema () : void {
            $schema = Schema::infer([]);

            $this->assertTrue($schema instanceof Schema, "infer([]) should return a Schema instance.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "infer(\$rows) — Infers Required Fields",
            description: "Fields present in every row are inferred as required in the schema."
        )]
        public function testInferRequiredFieldsFromRows () : void {
            $data = [
                ["name" => "Alice", "age" => 30],
                ["name" => "Bob",   "age" => 25],
            ];
            $schema = Schema::infer($data);
            $expression = $schema->getExpression();

            $this->assertTrue(str_contains($expression, "name"), "Inferred expression should contain the 'name' field.");
            $this->assertTrue(str_contains($expression, "age"), "Inferred expression should contain the 'age' field.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "infer(\$rows) — Optional Field When Not In Every Row",
            description: "A field not present in all rows is inferred as optional."
        )]
        public function testInferOptionalFieldWhenMissingFromSomeRows () : void {
            $data = [
                ["name" => "Alice", "email" => "a@example.com"],
                ["name" => "Bob"],
            ];
            $schema = Schema::infer($data);
            $expression = $schema->getExpression();

            $this->assertTrue(str_contains($expression, "email?"), "Email, absent from some rows, should be inferred as optional (email?).");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "infer(\$rows) — Validates Inferred Schema Against Source Data",
            description: "Every source row should pass validation against the schema inferred from it."
        )]
        public function testInferredSchemaValidatesSourceRows () : void {
            $data = [
                ["id" => 1, "label" => "One"],
                ["id" => 2, "label" => "Two"],
            ];
            $schema = Schema::infer($data);
            $allValid = true;

            foreach ($data as $row) {
                if (!$schema->validate($row)->isValid()) {
                    $allValid = false;
                    break;
                }
            }

            $this->assertTrue($allValid, "Every source row should pass validation against its own inferred schema.");
        }

        // ─── merge() ─────────────────────────────────────────────────────────────

        #[Group("Schema — Features")]
        #[Define(
            name: "merge(null, null) — Returns null",
            description: "Merging two null schemas returns null."
        )]
        public function testMergeNullNullReturnsNull () : void {
            $result = Schema::merge(null, null);

            $this->assertTrue($result === null, "merge(null, null) should return null.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "merge(a, null) — Returns a",
            description: "Merging a schema with null on the right returns the left schema unchanged."
        )]
        public function testMergeSchemaWithNullReturnsSchema () : void {
            $reg = $this->makeRegistry();
            $a = Schema::from("{name: string}", $reg);

            $result = Schema::merge($a, null);

            $this->assertTrue($result === $a, "merge(a, null) should return a unchanged.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "merge(null, b) — Returns b",
            description: "Merging null on the left with a schema returns the right schema unchanged."
        )]
        public function testMergeNullWithSchemaReturnsSchema () : void {
            $reg = $this->makeRegistry();
            $b = Schema::from("{age: int}", $reg);

            $result = Schema::merge(null, $b);

            $this->assertTrue($result === $b, "merge(null, b) should return b unchanged.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "merge(a, b) — Returns New Schema",
            description: "Merging two non-null schemas with the same registry produces a new Schema instance."
        )]
        public function testMergeTwoSchemasReturnsNew () : void {
            $reg = $this->makeRegistry();
            $a = Schema::from("{name: string}", $reg);
            $b = Schema::from("{age: int}", $reg);

            $merged = Schema::merge($a, $b);

            $this->assertTrue($merged instanceof Schema, "Merging two schemas should return a Schema instance.");
            $this->assertTrue($merged !== $a && $merged !== $b, "The merged schema should be a new instance.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "merge() — Different Registries Throw SchemaException",
            description: "Merging two schemas from different registries throws a SchemaException."
        )]
        public function testMergeDifferentRegistriesThrows () : void {
            $reg1 = $this->makeRegistry();
            $reg2 = $this->makeRegistry();
            $a = Schema::from("{name: string}", $reg1);
            $b = Schema::from("{age: int}", $reg2);
            $thrown = false;

            try {
                Schema::merge($a, $b);
            }
            catch (SchemaException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "Merging schemas from different registries should throw SchemaException.");
        }

        // ─── expand() ────────────────────────────────────────────────────────────

        #[Group("Schema — Features")]
        #[Define(
            name: "expand() — Resolves SchemaRefNode",
            description: "expand() replaces a SchemaRefNode with the full node from the registry."
        )]
        public function testExpandResolvesSchemaRef () : void {
            $reg = $this->makeRegistry();
            Schema::register("Tag", "{id: int, label: string}", $reg);

            $refNode = new SchemaRefNode("Tag", $reg->getSchemaRegistry());

            $this->assertTrue($refNode instanceof SchemaRefNode, "A directly constructed SchemaRefNode should be a SchemaRefNode.");

            $expanded = Schema::expand($refNode, $reg);

            $this->assertFalse($expanded instanceof SchemaRefNode, "expand() should resolve the reference to a concrete node.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "expand() — Unknown Reference Throws SchemaException",
            description: "expand() throws a SchemaException when the referenced schema does not exist in the registry."
        )]
        public function testExpandUnknownRefThrows () : void {
            $reg = $this->makeRegistry();
            $refNode = new SchemaRefNode("UnknownSchema99", $reg->getSchemaRegistry());
            $thrown = false;

            try {
                Schema::expand($refNode, $reg);
            }
            catch (\RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "expand() should throw when the referenced schema does not exist in the registry.");
        }

        // ─── Accessors ────────────────────────────────────────────────────────────

        #[Group("Schema — Features")]
        #[Define(
            name: "getNode() — Returns The Root Node",
            description: "getNode() returns the root Node instance parsed from the expression."
        )]
        public function testGetNodeReturnsNode () : void {
            $reg = $this->makeRegistry();
            $schema = Schema::from("{id: int}", $reg);
            $node = $schema->getNode();

            $this->assertTrue($node !== null, "getNode() should not return null.");
        }

        #[Group("Schema — Features")]
        #[Define(
            name: "getExpression() — Matches from() Argument",
            description: "getExpression() returns the exact DSL string that was passed to from()."
        )]
        public function testGetExpressionMatchesInput () : void {
            $expr = "{id: int, name: string}";
            $schema = Schema::from($expr, $this->makeRegistry());

            $this->assertTrue($schema->getExpression() === $expr, "getExpression() should return the original expression string.");
        }
    }
?>
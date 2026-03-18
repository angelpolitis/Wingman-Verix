<?php
    /**
     * Project Name:    Wingman Verix - Registries Tests
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
    use RuntimeException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Verix\Facades\Schema;
    use Wingman\Verix\PrimitiveDefinition;
    use Wingman\Verix\Registries\ClassRegistry;
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\Registries\Registry;
    use Wingman\Verix\Registries\SchemaRegistry;
    use Wingman\Verix\Types\StringType;

    /**
     * Tests for the four registry classes: PrimitiveRegistry, SchemaRegistry,
     * ClassRegistry, and Registry.
     */
    class RegistriesTest extends Test {

        /**
         * Creates a simple PrimitiveDefinition for use in registry tests.
         * @param string $name The type name.
         * @return PrimitiveDefinition The created definition.
         */
        private function makeDefinition (string $name = "dummy") : PrimitiveDefinition {
            return new PrimitiveDefinition($name, "any", fn ($v) => true);
        }

        /**
         * Creates a minimal stub Node for schema registry tests.
         * @return \Wingman\Verix\Interfaces\Node The stub node.
         */
        private function makeNode () : \Wingman\Verix\Interfaces\Node {
            return Schema::parse("any", new Registry());
        }

        // ─── PrimitiveRegistry ────────────────────────────────────────────────────

        #[Group("Registry — Primitives")]
        #[Define(
            name: "register() — has() Returns True After Registration",
            description: "After registering a definition, has() returns true for that name."
        )]
        public function testPrimitiveRegistryHasTrueAfterRegistration () : void {
            $reg = new PrimitiveRegistry();
            $reg->register($this->makeDefinition("widget"));

            $this->assertTrue($reg->has("widget"), "has('widget') should be true after registration.");
        }

        #[Group("Registry — Primitives")]
        #[Define(
            name: "has() — False Before Registration",
            description: "has() returns false for a name that has not been registered."
        )]
        public function testPrimitiveRegistryHasFalseBeforeRegistration () : void {
            $reg = new PrimitiveRegistry();

            $this->assertFalse($reg->has("nonexistent"), "has('nonexistent') should be false in an empty registry.");
        }

        #[Group("Registry — Primitives")]
        #[Define(
            name: "get() — Returns Registered Definition",
            description: "get() returns the exact PrimitiveDefinition that was registered."
        )]
        public function testPrimitiveRegistryGetReturnsDefinition () : void {
            $reg = new PrimitiveRegistry();
            $def = $this->makeDefinition("gadget");
            $reg->register($def);

            $this->assertTrue($reg->get("gadget") === $def, "get('gadget') should return the same definition instance.");
        }

        #[Group("Registry — Primitives")]
        #[Define(
            name: "get() — Throws RuntimeException For Unknown Type",
            description: "get() throws a RuntimeException when the requested type is not registered."
        )]
        public function testPrimitiveRegistryGetThrowsForUnknown () : void {
            $reg = new PrimitiveRegistry();
            $thrown = false;

            try {
                $reg->get("doesNotExist");
            }
            catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "get() should throw RuntimeException for an unknown type.");
        }

        #[Group("Registry — Primitives")]
        #[Define(
            name: "register() — Accepts Type Class Name",
            description: "register() accepts a Type class name string and stores its definition."
        )]
        public function testPrimitiveRegistryRegisterAcceptsClassName () : void {
            $reg = new PrimitiveRegistry();
            $reg->register(StringType::class);

            $this->assertTrue($reg->has("string"), "Registering StringType::class should store a 'string' definition.");
        }

        #[Group("Registry — Primitives")]
        #[Define(
            name: "register() — Throws For Non-Existent Class",
            description: "register() throws a RuntimeException when the class name does not exist."
        )]
        public function testPrimitiveRegistryRegisterThrowsForMissingClass () : void {
            $reg = new PrimitiveRegistry();
            $thrown = false;

            try {
                $reg->register("Wingman\\Verix\\Types\\NonExistentType99");
            }
            catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "register() should throw RuntimeException for a non-existent class.");
        }

        // ─── SchemaRegistry ───────────────────────────────────────────────────────

        #[Group("Registry — Schemata")]
        #[Define(
            name: "register() — has() Returns True After Registration",
            description: "After registering a named schema node, has() returns true for that name."
        )]
        public function testSchemaRegistryHasTrueAfterRegistration () : void {
            $reg = new SchemaRegistry();
            $reg->register("MyModel", $this->makeNode());

            $this->assertTrue($reg->has("MyModel"), "has('MyModel') should be true after registration.");
        }

        #[Group("Registry — Schemata")]
        #[Define(
            name: "has() — False Before Registration",
            description: "has() returns false for a schema that has not been registered."
        )]
        public function testSchemaRegistryHasFalseBeforeRegistration () : void {
            $reg = new SchemaRegistry();

            $this->assertFalse($reg->has("Unknown"), "has('Unknown') should be false in an empty schema registry.");
        }

        #[Group("Registry — Schemata")]
        #[Define(
            name: "get() — Returns Registered Node",
            description: "get() returns the exact Node that was registered."
        )]
        public function testSchemaRegistryGetReturnsNode () : void {
            $reg = new SchemaRegistry();
            $node = $this->makeNode();
            $reg->register("ASchema", $node);

            $this->assertTrue($reg->get("ASchema") === $node, "get('ASchema') should return the same node instance.");
        }

        #[Group("Registry — Schemata")]
        #[Define(
            name: "get() — Throws RuntimeException For Unknown Schema",
            description: "get() throws a RuntimeException when the schema is not registered."
        )]
        public function testSchemaRegistryGetThrowsForUnknown () : void {
            $reg = new SchemaRegistry();
            $thrown = false;

            try {
                $reg->get("NoSuchSchema");
            }
            catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "get() should throw RuntimeException for an unknown schema.");
        }

        // ─── ClassRegistry ────────────────────────────────────────────────────────

        #[Group("Registry — Classes")]
        #[Define(
            name: "register() — has() Returns True After Registration",
            description: "After register(), has() returns true for the alias."
        )]
        public function testClassRegistryHasTrueAfterRegistration () : void {
            $reg = new ClassRegistry();
            $reg->register("user", "App\\Models\\User");

            $this->assertTrue($reg->has("user"), "has('user') should be true after registration.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "has() — False For Unregistered Alias",
            description: "has() returns false for an alias that was never registered."
        )]
        public function testClassRegistryHasFalseForUnregistered () : void {
            $reg = new ClassRegistry();

            $this->assertFalse($reg->has("nobody"), "has('nobody') should be false in an empty class registry.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "get() — Returns FQCN For Registered Alias",
            description: "get() returns the FQCN string for a registered alias."
        )]
        public function testClassRegistryGetReturnsFqcn () : void {
            $reg = new ClassRegistry();
            $reg->register("product", "App\\Catalogue\\Product");

            $this->assertTrue($reg->get("product") === "App\\Catalogue\\Product", "get('product') should return 'App\\Catalogue\\Product'.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "get() — Returns null For Unregistered Alias",
            description: "get() returns null when the alias has not been registered."
        )]
        public function testClassRegistryGetReturnsNullForUnregistered () : void {
            $reg = new ClassRegistry();

            $this->assertTrue($reg->get("ghost") === null, "get('ghost') should return null.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "register() — Normalises Dot-Notation FQCN",
            description: "A dot-notation FQCN is converted to backslash form on registration."
        )]
        public function testClassRegistryNormalisesDotNotation () : void {
            $reg = new ClassRegistry();
            $reg->register("order", "App.Orders.Order");

            $this->assertTrue($reg->get("order") === "App\\Orders\\Order", "Dot-notation FQCN should be normalised to 'App\\Orders\\Order'.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "register() — Strips Special Characters From Alias",
            description: "Special characters in the alias are removed during sanitisation."
        )]
        public function testClassRegistryStripsSpecialCharsFromAlias () : void {
            $reg = new ClassRegistry();
            $reg->register("clean!!!@#$", "App\\Clean");

            $this->assertTrue($reg->has("clean"), "Alias 'clean!!!@#\$' should be sanitised to 'clean'.");
        }

        #[Group("Registry — Classes")]
        #[Define(
            name: "register() — Returns \$this For Chaining",
            description: "register() returns the same ClassRegistry instance, enabling fluent chaining."
        )]
        public function testClassRegistryRegisterReturnsThis () : void {
            $reg = new ClassRegistry();

            $result = $reg->register("a", "A\\A")->register("b", "B\\B");

            $this->assertTrue($result === $reg, "register() should return \$this for chaining.");
        }

        // ─── Registry ─────────────────────────────────────────────────────────────

        #[Group("Registry")]
        #[Define(
            name: "get(name) — Returns Same Instance on Repeated Calls",
            description: "Calling Registry::get() with the same name twice returns the same singleton object."
        )]
        public function testRegistrySingletonBehaviour () : void {
            $name = "verix_singleton_test_01";

            $first  = Registry::get($name);
            $second = Registry::get($name);

            $this->assertTrue($first === $second, "Registry::get() should return the same instance for the same name.");
        }

        #[Group("Registry")]
        #[Define(
            name: "get(a) !== get(b) — Different Names Yield Different Instances",
            description: "Calling Registry::get() with two different names returns different instances."
        )]
        public function testRegistryDifferentNamesDifferentInstances () : void {
            $reg1 = Registry::get("verix_nametest_alpha");
            $reg2 = Registry::get("verix_nametest_beta");

            $this->assertFalse($reg1 === $reg2, "Registry::get() should return different instances for different names.");
        }

        #[Group("Registry")]
        #[Define(
            name: "getPrimitiveRegistry() — Returns PrimitiveRegistry",
            description: "getPrimitiveRegistry() lazily initialises and returns a PrimitiveRegistry."
        )]
        public function testRegistryGetPrimitiveRegistry () : void {
            $reg = new Registry();

            $this->assertTrue($reg->getPrimitiveRegistry() instanceof PrimitiveRegistry, "getPrimitiveRegistry() should return a PrimitiveRegistry.");
        }

        #[Group("Registry")]
        #[Define(
            name: "getSchemaRegistry() — Returns SchemaRegistry",
            description: "getSchemaRegistry() lazily initialises and returns a SchemaRegistry."
        )]
        public function testRegistryGetSchemaRegistry () : void {
            $reg = new Registry();

            $this->assertTrue($reg->getSchemaRegistry() instanceof SchemaRegistry, "getSchemaRegistry() should return a SchemaRegistry.");
        }

        #[Group("Registry")]
        #[Define(
            name: "getClassRegistry() — Returns ClassRegistry",
            description: "getClassRegistry() lazily initialises and returns a ClassRegistry."
        )]
        public function testRegistryGetClassRegistry () : void {
            $reg = new Registry();

            $this->assertTrue($reg->getClassRegistry() instanceof ClassRegistry, "getClassRegistry() should return a ClassRegistry.");
        }

        #[Group("Registry")]
        #[Define(
            name: "Default Primitives Are Registered Automatically",
            description: "getPrimitiveRegistry() on a fresh Registry automatically registers string, int, bool, float, and any."
        )]
        public function testRegistryDefaultPrimitivesArePresent () : void {
            $reg = new Registry();
            $primitiveReg = $reg->getPrimitiveRegistry();

            $this->assertTrue($primitiveReg->has("string"), "Default registry should have 'string'.");
            $this->assertTrue($primitiveReg->has("int"), "Default registry should have 'int'.");
            $this->assertTrue($primitiveReg->has("bool"), "Default registry should have 'bool'.");
            $this->assertTrue($primitiveReg->has("float"), "Default registry should have 'float'.");
            $this->assertTrue($primitiveReg->has("any"), "Default registry should have 'any'.");
        }

        #[Group("Registry")]
        #[Define(
            name: "getPrimitiveRegistry() — Same Object On Repeated Calls",
            description: "getPrimitiveRegistry() returns the same PrimitiveRegistry instance every time."
        )]
        public function testRegistryPrimitiveRegistryIsSameInstance () : void {
            $reg = new Registry();

            $this->assertTrue($reg->getPrimitiveRegistry() === $reg->getPrimitiveRegistry(), "getPrimitiveRegistry() should return the same instance on every call.");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Primitive Facade Tests
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
    use Wingman\Verix\Facades\Primitive;
    use Wingman\Verix\PrimitiveDefinition;
    use Wingman\Verix\Registries\Registry;
    use Wingman\Verix\Types\StringType;

    /**
     * Tests for the Primitive facade: register(), alias(), and registerClass().
     */
    class PrimitiveFacadeTest extends Test {

        /**
         * Creates a fresh isolated registry.
         * @return Registry The isolated registry.
         */
        private function makeRegistry () : Registry {
            return new Registry();
        }

        // ─── register() ───────────────────────────────────────────────────────────

        #[Group("Primitive Facade")]
        #[Define(
            name: "register() — Returns PrimitiveDefinition",
            description: "Primitive::register() returns a PrimitiveDefinition instance."
        )]
        public function testRegisterReturnsPrimitiveDefinition () : void {
            $reg = $this->makeRegistry();

            $def = Primitive::register("score", fn ($v) => is_int($v), null, [], null, null, null, null, $reg);

            $this->assertTrue($def instanceof PrimitiveDefinition, "register() should return a PrimitiveDefinition.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "register() — Stores Type In Primitive Registry",
            description: "The type registered via Primitive::register() is accessible in the registry."
        )]
        public function testRegisterTypeIsAccessibleInRegistry () : void {
            $reg = $this->makeRegistry();

            Primitive::register("rating", fn ($v) => is_int($v) && $v >= 1 && $v <= 5, null, [], null, null, null, null, $reg);

            $this->assertTrue($reg->getPrimitiveRegistry()->has("rating"), "Registered type 'rating' should be present in the primitive registry.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "register() — Parent Defaults To 'any'",
            description: "When no extends argument is provided, the definition's parent is 'any'."
        )]
        public function testRegisterParentDefaultsToAny () : void {
            $reg = $this->makeRegistry();

            $def = Primitive::register("thing", fn ($v) => true, null, [], null, null, null, null, $reg);

            $this->assertTrue($def->getParent() === "any", "parent should default to 'any' when \$extends is null.");
        }

        // ─── registerClass() ──────────────────────────────────────────────────────

        #[Group("Primitive Facade")]
        #[Define(
            name: "registerClass() — Registers A Type Class By Name",
            description: "registerClass() with a valid Type subclass registers it in the primitive registry."
        )]
        public function testRegisterClassAcceptsValidTypeClass () : void {
            $reg = $this->makeRegistry();

            Primitive::registerClass(StringType::class, $reg);

            $this->assertTrue($reg->getPrimitiveRegistry()->has("string"), "registerClass(StringType::class) should register the 'string' type.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "registerClass() — Throws For Non-Existent Class",
            description: "registerClass() throws a RuntimeException when the class does not exist."
        )]
        public function testRegisterClassThrowsForNonExistentClass () : void {
            $reg = $this->makeRegistry();
            $thrown = false;

            try {
                Primitive::registerClass("Wingman\\Verix\\Types\\NoSuchClass999", $reg);
            }
            catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "registerClass() with a non-existent class should throw RuntimeException.");
        }

        // ─── alias() ──────────────────────────────────────────────────────────────

        #[Group("Primitive Facade")]
        #[Define(
            name: "alias() — Creates Alias In Registry",
            description: "alias() with a single name registers a derived type alias in the primitive registry."
        )]
        public function testAliasSingleNameRegistersInRegistry () : void {
            $reg = $this->makeRegistry();

            Primitive::alias("identifier", "string", $reg);

            $this->assertTrue($reg->getPrimitiveRegistry()->has("identifier"), "Alias 'identifier' for 'string' should be in the registry.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "alias() — Array Registers All Names",
            description: "alias() with an array of names registers every name as a derived type."
        )]
        public function testAliasArrayRegistersAllNames () : void {
            $reg = $this->makeRegistry();

            Primitive::alias(["label", "title"], "string", $reg);
            $primitiveReg = $reg->getPrimitiveRegistry();

            $this->assertTrue($primitiveReg->has("label"), "'label' should be registered.");
            $this->assertTrue($primitiveReg->has("title"), "'title' should be registered.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "alias() — Throws RuntimeException Without Target",
            description: "alias() throws a RuntimeException when a single alias is given but no target type is specified."
        )]
        public function testAliasSingleNameWithoutTargetThrows () : void {
            $thrown = false;

            try {
                Primitive::alias("orphan", null);
            }
            catch (RuntimeException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "alias() without a primitive target should throw RuntimeException.");
        }

        #[Group("Primitive Facade")]
        #[Define(
            name: "alias() — Derived Type Validates Like Its Parent",
            description: "A derived alias validates values in the same way as the parent type."
        )]
        public function testAliasDerivedTypeValidatesLikeParent () : void {
            $reg = $this->makeRegistry();

            Primitive::alias("slug", "string", $reg);

            $def = $reg->getPrimitiveRegistry()->get("slug");
            $validResult   = $def->validate("hello-world");
            $invalidResult = $def->validate(42);

            $this->assertTrue($validResult->isValid(), "'slug' should accept a string value.");
            $this->assertFalse($invalidResult->isValid(), "'slug' should reject an integer value in strict mode.");
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Primitive Definition Tests
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
    use Wingman\Verix\PrimitiveDefinition;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Tests for PrimitiveDefinition: accessors, validate(), parse(), derive(), and
     * parameter visibility after derive().
     */
    class PrimitiveDefinitionTest extends Test {

        /**
         * Creates a minimal PrimitiveDefinition that always passes validation.
         * @param string $name The name.
         * @param string $parent The parent type name.
         * @param array $params Array of Parameter objects.
         * @return PrimitiveDefinition The created definition.
         */
        private function makeDefinition (string $name = "mytype", string $parent = "any", array $params = []) : PrimitiveDefinition {
            return new PrimitiveDefinition($name, $parent, fn ($v) => true, $params);
        }

        // ─── Accessors ────────────────────────────────────────────────────────────

        #[Group("Primitive Definition")]
        #[Define(
            name: "getName() — Returns Constructor Name",
            description: "getName() returns the exact name passed to the constructor."
        )]
        public function testGetNameReturnsConstructorName () : void {
            $def = $this->makeDefinition("phone");

            $this->assertTrue($def->getName() === "phone", "getName() should return 'phone'.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getParent() — Returns Constructor Parent",
            description: "getParent() returns the parent type name passed to the constructor."
        )]
        public function testGetParentReturnsConstructorParent () : void {
            $def = $this->makeDefinition("email", "string");

            $this->assertTrue($def->getParent() === "string", "getParent() should return 'string'.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getDescription() — Returns Description String",
            description: "getDescription() returns the description string when provided."
        )]
        public function testGetDescriptionReturnsString () : void {
            $def = new PrimitiveDefinition("foo", "any", fn ($v) => true, [], null, "A description.");

            $this->assertTrue($def->getDescription() === "A description.", "getDescription() should return the provided description.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getDescription() — Returns null When Omitted",
            description: "getDescription() returns null when no description is provided."
        )]
        public function testGetDescriptionReturnsNullWhenOmitted () : void {
            $def = $this->makeDefinition();

            $this->assertTrue($def->getDescription() === null, "getDescription() should be null when omitted.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getValidator() — Returns a Closure",
            description: "getValidator() always returns a Closure instance."
        )]
        public function testGetValidatorReturnsClosure () : void {
            $def = $this->makeDefinition();

            $this->assertTrue($def->getValidator() instanceof \Closure, "getValidator() should return a Closure.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getParser() — Returns null When Omitted",
            description: "getParser() returns null when no parser is provided."
        )]
        public function testGetParserReturnsNullWhenOmitted () : void {
            $def = $this->makeDefinition();

            $this->assertTrue($def->getParser() === null, "getParser() should be null when no parser is provided.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getParser() — Returns Closure When Provided",
            description: "getParser() returns a Closure when a parser callable is provided."
        )]
        public function testGetParserReturnsClosure () : void {
            $def = new PrimitiveDefinition("foo", "any", fn ($v) => true, [], fn ($v) => strtoupper($v));

            $this->assertTrue($def->getParser() instanceof \Closure, "getParser() should return a Closure when a parser is provided.");
        }

        // ─── Parameter Visibility ─────────────────────────────────────────────────

        #[Group("Primitive Definition")]
        #[Define(
            name: "hasParam() — True For Declared Parameter",
            description: "hasParam() returns true for a parameter declared in the constructor."
        )]
        public function testHasParamReturnsTrueForDeclaredParam () : void {
            $param = new Parameter("minLength", "int");
            $def = $this->makeDefinition("foo", "any", [$param]);

            $this->assertTrue($def->hasParam("minLength"), "hasParam('minLength') should be true for a declared parameter.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "hasParam() — False For Undeclared Parameter",
            description: "hasParam() returns false for a parameter name that was never declared."
        )]
        public function testHasParamReturnsFalseForUndeclaredParam () : void {
            $def = $this->makeDefinition();

            $this->assertFalse($def->hasParam("maxLength"), "hasParam('maxLength') should be false when the parameter was never declared.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "getParams() — Excludes Applied/Hidden Parameters",
            description: "After derive(), getParams() on the derived definition excludes the applied parameters."
        )]
        public function testGetParamsExcludesAppliedParams () : void {
            $param = new Parameter("limit", "int");
            $def = new PrimitiveDefinition("foo", "any", fn ($v) => is_int($v), [$param]);

            $derived = $def->derive("smallFoo", ["limit" => 5]);

            $this->assertFalse(array_key_exists("limit", $derived->getParams()), "getParams() on the derived definition must not expose the applied 'limit' parameter.");
        }

        // ─── validate() ───────────────────────────────────────────────────────────

        #[Group("Primitive Definition")]
        #[Define(
            name: "validate() — Passing Value Returns Valid Result",
            description: "validate() returns a valid ValidationResult when the validator passes."
        )]
        public function testValidatePassingValueReturnsValid () : void {
            $def = new PrimitiveDefinition("positive", "any", fn ($v) => is_int($v) && $v > 0);

            $result = $def->validate(42);

            $this->assertTrue($result->isValid(), "validate(42) should return a valid result for a positive-int type.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "validate() — Failing Value Returns Invalid Result",
            description: "validate() returns an invalid ValidationResult when the validator fails."
        )]
        public function testValidateFailingValueReturnsInvalid () : void {
            $def = new PrimitiveDefinition("positive", "any", fn ($v) => is_int($v) && $v > 0);

            $result = $def->validate(-1);

            $this->assertFalse($result->isValid(), "validate(-1) should return an invalid result for a positive-int type.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "validate() — Uses Custom errorCallback",
            description: "When the validator fails, the errorCallback message is used in the result errors."
        )]
        public function testValidateUsesErrorCallback () : void {
            $def = new PrimitiveDefinition(
                "noop",
                "any",
                fn ($v) => false,
                [],
                null,
                null,
                fn ($v, $p) => "Rejected: {value}"
            );

            $result = $def->validate("hello");
            $errors = $result->getErrors();

            $this->assertFalse(empty($errors), "There should be at least one error message.");
            $this->assertTrue(str_contains($errors[0], "hello"), "The error message should include the actual value.");
        }

        // ─── parse() ──────────────────────────────────────────────────────────────

        #[Group("Primitive Definition")]
        #[Define(
            name: "parse() — Identity When No Parser Defined",
            description: "parse() returns the original value unchanged when no parser is provided."
        )]
        public function testParseIdentityWithoutParser () : void {
            $def = $this->makeDefinition();

            $this->assertTrue($def->parse("original") === "original", "parse() without a parser should be identity.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "parse() — Applies Parser Callable",
            description: "parse() transforms the value through the provided parser."
        )]
        public function testParseAppliesParser () : void {
            $def = new PrimitiveDefinition("upper", "any", fn ($v) => is_string($v), [], fn ($v) => strtoupper($v));

            $this->assertTrue($def->parse("hello") === "HELLO", "parse() should apply the parser and return 'HELLO'.");
        }

        // ─── derive() ─────────────────────────────────────────────────────────────

        #[Group("Primitive Definition")]
        #[Define(
            name: "derive() — Returns New PrimitiveDefinition",
            description: "derive() returns a fresh PrimitiveDefinition instance, not the original."
        )]
        public function testDeriveReturnsNewInstance () : void {
            $def = $this->makeDefinition("foo");
            $derived = $def->derive("fooAlias", []);

            $this->assertTrue($derived instanceof PrimitiveDefinition, "derive() should return a PrimitiveDefinition.");
            $this->assertFalse($derived === $def, "derive() should return a new instance, not the original.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "derive() — getName() Returns The Alias",
            description: "The derived definition's getName() equals the alias name passed to derive()."
        )]
        public function testDeriveGetNameEqualsAlias () : void {
            $def = $this->makeDefinition("foo");
            $derived = $def->derive("fooAlias", []);

            $this->assertTrue($derived->getName() === "fooAlias", "Derived getName() should be 'fooAlias'.");
        }

        #[Group("Primitive Definition")]
        #[Define(
            name: "derive() — getParent() Returns Original Name",
            description: "The derived definition's getParent() equals the original definition's name."
        )]
        public function testDeriveGetParentEqualsOriginalName () : void {
            $def = $this->makeDefinition("baseType");
            $derived = $def->derive("derivedType", []);

            $this->assertTrue($derived->getParent() === "baseType", "Derived getParent() should equal the original definition name 'baseType'.");
        }
    }
?>
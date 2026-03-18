<?php
    /**
     * Project Name:    Wingman Verix - Validation Result Tests
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
    use Wingman\Verix\Exceptions\ValidationFailureException;
    use Wingman\Verix\ValidationResult;

    /**
     * Tests for the ValidationResult value object, covering construction,
     * static factories, boolean flags, accessor methods, and all `with*` fluent builders.
     */
    class ValidationResultTest extends Test {

        // ─── Static Factories ───────────────────────────────────────────────────

        #[Group("Validation Result")]
        #[Define(
            name: "ok() — isValid() Returns true",
            description: "A result created with ok() reports isValid() as true."
        )]
        public function testOkIsValid () : void {
            $result = ValidationResult::ok("hello");

            $this->assertTrue($result->isValid(), "ok() should produce a valid result.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "ok() — getValue() Returns Stored Value",
            description: "A result created with ok() returns the exact value passed in."
        )]
        public function testOkStoresValue () : void {
            $value = ["name" => "Alice"];
            $result = ValidationResult::ok($value);

            $this->assertTrue($result->getValue() === $value, "ok() should store and return the given value.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "ok() — getErrors() Is Empty",
            description: "A result created with ok() has no validation errors."
        )]
        public function testOkHasNoErrors () : void {
            $result = ValidationResult::ok(42);

            $this->assertTrue($result->getErrors() === [], "ok() should produce a result with no errors.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "error() — isValid() Returns false",
            description: "A result created with error() reports isValid() as false."
        )]
        public function testErrorIsInvalid () : void {
            $result = ValidationResult::error("Expected string", 42);

            $this->assertFalse($result->isValid(), "error() should produce an invalid result.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "error() — getErrors() Contains One Exception",
            description: "A result created with error() contains exactly one ValidationFailureException."
        )]
        public function testErrorContainsOneException () : void {
            $result = ValidationResult::error("Expected string", 42);
            $errors = $result->getErrors();

            $this->assertTrue(count($errors) === 1, "error() should produce exactly one error.");
            $this->assertTrue($errors[0] instanceof ValidationFailureException, "The error should be a ValidationFailureException.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "error() — Error Message Is Preserved",
            description: "The error message passed to error() is accessible via getErrors()."
        )]
        public function testErrorMessageIsPreserved () : void {
            $result = ValidationResult::error("Expected integer", "abc");
            $message = $result->getErrors()[0]->getMessage();

            $this->assertTrue($message === "Expected integer", "The error message should match what was passed to error().");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "error() — getValue() Returns Invalid Value",
            description: "A result created with error() returns the invalid value via getValue()."
        )]
        public function testErrorStoresInvalidValue () : void {
            $result = ValidationResult::error("Expected string", 99);

            $this->assertTrue($result->getValue() === 99, "error() should store and return the invalid value.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "getMetadata() — Returns Empty Array By Default",
            description: "A freshly created result has no metadata."
        )]
        public function testMetadataDefaultsToEmptyArray () : void {
            $result = ValidationResult::ok("x");

            $this->assertTrue($result->getMetadata() === [], "A fresh result should have empty metadata.");
        }

        // ─── merge() ────────────────────────────────────────────────────────────

        #[Group("Validation Result")]
        #[Define(
            name: "merge() — Two Valid Results → Valid",
            description: "Merging two valid results produces a valid result."
        )]
        public function testMergeBothValidIsValid () : void {
            $a = ValidationResult::ok("hello");
            $b = ValidationResult::ok("world");

            $this->assertTrue($a->merge($b)->isValid(), "Merging two valid results should yield a valid result.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "merge() — Valid + Invalid → Invalid",
            description: "Merging a valid result with an invalid one yields an invalid result."
        )]
        public function testMergeValidWithInvalidIsInvalid () : void {
            $a = ValidationResult::ok("hello");
            $b = ValidationResult::error("Expected int", "hello");

            $this->assertFalse($a->merge($b)->isValid(), "Merging with an invalid result should yield an invalid result.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "merge() — Errors Are Accumulated",
            description: "Merging two error results combines all errors into the result."
        )]
        public function testMergeAccumulatesErrors () : void {
            $a = ValidationResult::error("Error A", 1);
            $b = ValidationResult::error("Error B", 2);
            $merged = $a->merge($b);

            $this->assertTrue(count($merged->getErrors()) === 2, "Merged result should carry both errors.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "merge() — Uses Other's Value",
            description: "The merged result uses the value from the right-hand operand."
        )]
        public function testMergeUsesOtherValue () : void {
            $a = ValidationResult::ok("first");
            $b = ValidationResult::ok("second");

            $this->assertTrue($a->merge($b)->getValue() === "second", "merge() should use the other result's value.");
        }

        // ─── Fluent Builders ────────────────────────────────────────────────────

        #[Group("Validation Result")]
        #[Define(
            name: "withValue() — Changes Value, Preserves Validity",
            description: "withValue() returns a new result with the replaced value but the same valid flag."
        )]
        public function testWithValueChangesValue () : void {
            $original = ValidationResult::ok("hello");
            $updated = $original->withValue(42);

            $this->assertTrue($updated->getValue() === 42, "withValue() should replace the value.");
            $this->assertTrue($updated->isValid(), "withValue() should preserve validity.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withPath() — Sets Path On Each Error",
            description: "withPath() propagates the given path to all contained errors."
        )]
        public function testWithPathSetsErrorPath () : void {
            $result = ValidationResult::error("Bad value", "abc")->withPath("user.name");
            $path = $result->getErrors()[0]->getPath();

            $this->assertTrue($path === "user.name", "withPath() should set the path on all errors.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withPath() — No-Op On Valid Result",
            description: "Calling withPath() on a valid result returns a valid result with no errors."
        )]
        public function testWithPathOnValidResultIsNoOp () : void {
            $result = ValidationResult::ok("hello")->withPath("some.path");

            $this->assertTrue($result->isValid(), "withPath() on a valid result should preserve validity.");
            $this->assertTrue($result->getErrors() === [], "withPath() on a valid result should leave errors empty.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withMetadata() — Merges Metadata By Default",
            description: "Calling withMetadata() adds the new data alongside existing metadata."
        )]
        public function testWithMetadataMergesMetadata () : void {
            $result = ValidationResult::ok("x")->withMetadata(["key" => "value"]);
            $result = $result->withMetadata(["other" => "data"]);

            $this->assertTrue($result->getMetadata()["key"] === "value", "Original metadata should be preserved after second withMetadata().");
            $this->assertTrue($result->getMetadata()["other"] === "data", "New metadata should be present after withMetadata().");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withMetadata() — Replace Mode Discards Old Data",
            description: "Calling withMetadata(\$data, true) replaces the entire metadata array."
        )]
        public function testWithMetadataReplaceMode () : void {
            $result = ValidationResult::ok("x")
                ->withMetadata(["original" => true])
                ->withMetadata(["replacement" => true], true);

            $this->assertFalse(isset($result->getMetadata()["original"]), "Replace mode should discard the original metadata key.");
            $this->assertTrue($result->getMetadata()["replacement"] === true, "Replace mode should store the new metadata key.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withErrors() — Appends Errors On Invalid Result",
            description: "withErrors() adds extra errors to an already-failing result."
        )]
        public function testWithErrorsAppendsErrors () : void {
            $base = ValidationResult::error("First error", 1);
            $extra = [new ValidationFailureException("Second error", "", 2)];
            $result = $base->withErrors($extra);

            $this->assertTrue(count($result->getErrors()) === 2, "withErrors() should append the new errors.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "withErrors() — No-Op On Valid Result",
            description: "Calling withErrors() on a valid result returns it unchanged."
        )]
        public function testWithErrorsIsNoOpOnValidResult () : void {
            $base = ValidationResult::ok("hello");
            $result = $base->withErrors([new ValidationFailureException("Ignored", "", "val")]);

            $this->assertTrue($result->isValid(), "withErrors() on a valid result should not change validity.");
        }

        // ─── throwIfInvalid() ────────────────────────────────────────────────────

        #[Group("Validation Result")]
        #[Define(
            name: "throwIfInvalid() — Returns Self On Valid Result",
            description: "Calling throwIfInvalid() on a valid result returns the result unchanged without throwing."
        )]
        public function testThrowIfInvalidReturnsSelfOnSuccess () : void {
            $result = ValidationResult::ok("hello");
            $returned = $result->throwIfInvalid();

            $this->assertTrue($returned === $result, "throwIfInvalid() should return the same instance on a valid result.");
        }

        #[Group("Validation Result")]
        #[Define(
            name: "throwIfInvalid() — Throws SchemaViolationException On Error",
            description: "Calling throwIfInvalid() on an invalid result throws a SchemaViolationException."
        )]
        public function testThrowIfInvalidThrowsOnFailure () : void {
            $result = ValidationResult::error("Expected string", 42);
            $thrown = false;

            try {
                $result->throwIfInvalid();
            }
            catch (SchemaViolationException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "throwIfInvalid() should throw SchemaViolationException on a failed result.");
        }
    }
?>
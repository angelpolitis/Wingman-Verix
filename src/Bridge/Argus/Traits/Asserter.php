<?php
    /**
     * Project Name:    Wingman Verix - Asserter Trait
     * Created by:      Angel Politis
     * Creation Date:   Feb 26 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Bridge.Argus.Traits namespace.
    namespace Wingman\Verix\Bridge\Argus\Traits;

    # Import the following classes to the current scope.
    use Throwable;
    use Wingman\Verix\Facades\Schema;
    use Wingman\Verix\Registries\Registry;

    /**
     * A trait that provides assertion methods for verifying the existence and validity of classes, primitives, and schemas.
     * This trait is designed to be used in testing contexts where assertions about the state of the Verix registries and schema validations are needed.
     * @package Wingman\Verix\Bridge\Argus\Traits
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    trait Asserter {
        /**
         * Executes a class registration assertion, checking whether a class is registered in the specified registry and recording the result.
         * @param string $className The name of the class to check for registration.
         * @param string|null $registry An optional registry name to look for the class. If null, the default registry will be used.
         * @param bool $shouldExist Indicates whether the class is expected to be registered (true) or not registered (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runClassAssertion (string $className, ?string $registry, bool $shouldExist, string $message) : void {
            try {
                $status = Registry::get($registry)->getClassRegistry()->has($className);
                $this->recordAssertion($shouldExist ? $status : !$status, ($shouldExist ? "Class registered: " : "Class not registered: ") . $className, $status ? "Class found in registry" : "Class not found in registry", $message);
            }
            catch (Throwable $e) {
                $this->recordAssertion(false, ($shouldExist ? "Class registered: " : "Class not registered: ") . $className, "Error: " . $e->getMessage(), $message);
            }
        }

        /**
         * Executes a primitive existence assertion, checking whether a primitive is registered in the specified registry and recording the result.
         * @param string $name The name of the primitive to check for existence.
         * @param string|null $registry An optional registry name to look for the primitive. If null, the default registry will be used.
         * @param bool $shouldExist Indicates whether the primitive is expected to exist (true) or not exist (false) in the registry.
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runPrimitiveAssertion (string $name, ?string $registry, bool $shouldExist, string $message) : void {
            try {
                $status = Registry::get($registry)->getPrimitiveRegistry()->has($name);
                $this->recordAssertion($shouldExist ? $status : !$status, ($shouldExist ? "Primitive exists: " : "Primitive not exists: ") . $name, $status ? "Primitive found in registry" : "Primitive not found in registry", $message);
            }
            catch (Throwable $e) {
                $this->recordAssertion(false, ($shouldExist ? "Primitive exists: " : "Primitive not exists: ") . $name, "Error: " . $e->getMessage(), $message);
            }
        }

        /**
         * Executes a schema registration assertion, checking whether a schema is registered in the specified registry and recording the result.
         * @param string $name The name of the schema to check for registration.
         * @param string|null $registry An optional registry name to look for the schema. If null, the default registry will be used.
         * @param bool $shouldExist Indicates whether the schema is expected to be registered (true) or not registered (false).
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runSchemaAssertion (string $name, ?string $registry, bool $shouldExist, string $message) : void {
            try {
                $status = Registry::get($registry)->getSchemaRegistry()->has($name);
                $this->recordAssertion($shouldExist ? $status : !$status, ($shouldExist ? "Schema registered: " : "Schema not registered: ") . $name, $status ? "Schema found in registry" : "Schema not found in registry", $message);
            }
            catch (Throwable $e) {
                $this->recordAssertion(false, ($shouldExist ? "Schema registered: " : "Schema not registered: ") . $name, "Error: " . $e->getMessage(), $message);
            }
        }

        /**
         * Executes a schema assertion, validating the actual value against the provided schema definition and recording the result.
         * @param string $definition The schema definition to validate against.
         * @param mixed $actual The actual value to be validated against the schema.
         * @param bool $shouldMatch Indicates whether the actual value is expected to match (true) or violate (false) the schema.
         * @param string $message An optional message providing additional context about the assertion.
         */
        private function runSchemaMatchAssertion (string $definition, mixed $actual, bool $shouldMatch, string $message) : void {
            $status = false;
            $error = "";

            try {
                $result = Schema::from($definition)->validate($actual);
                $status = $result->isValid();
                if (!$status) {
                    $error = "Violations: " . implode(", ", $result->getErrors());
                }
            }
            catch (Throwable $e) {
                $status = false; 
                $error = $e->getMessage();
            }

            $finalStatus = ($status === $shouldMatch);
            $this->recordAssertion(
                $finalStatus,
                ($shouldMatch ? "Matches" : "Violates") . " Schema: $definition",
                $status ? "Valid" : $error,
                $message ?: "Verix verification failed."
            );
        }

        /**
         * Records the result of an assertion, including its status, expected and actual values, and an optional message.
         * This method is intended to be implemented by the consuming class to handle assertion recording in a way that fits its architecture.
         * @param bool $status The result of the assertion (true for pass, false for fail).
         * @param mixed $expected The expected value in the assertion.
         * @param mixed $actual The actual value obtained during the test.
         * @param string $message An optional message providing additional context about the assertion.
         */
        abstract protected function recordAssertion (bool $status, mixed $expected, mixed $actual, string $message) : void;

        /**
         * Asserts that a class with the given name is registered in the specified registry, recording the result of the assertion.
         * @param string $className The name of the class to check for registration.
         * @param string|null $registry An optional registry name to look for the class. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertClassRegistered (string $className, ?string $registry = null, string $message = "") : void {
            $this->runClassAssertion($className, $registry, true, $message);
        }

        /**
         * Asserts that a class with the given name is not registered in the specified registry, recording the result of the assertion.
         * @param string $className The name of the class to check for non-registration.
         * @param string|null $registry An optional registry name to look for the class. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertClassNotRegistered (string $className, ?string $registry = null, string $message = "") : void {
            $this->runClassAssertion($className, $registry, false, $message);
        }

        /**
         * Asserts that a value matches a given schema definition, recording the result of the assertion.
         * @param string $definition The schema definition to validate against.
         * @param mixed $actual The actual value to be validated against the schema.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertMatchesSchema (string $definition, mixed $actual, string $message = "") : void {
            $this->runSchemaMatchAssertion($definition, $actual, true, $message);
        }

        /**
         * Asserts that a value violates a given schema definition, recording the result of the assertion.
         * @param string $definition The schema definition to validate against.
         * @param mixed $actual The actual value to be validated against the schema.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertNotMatchesSchema (string $definition, mixed $actual, string $message = "") : void {
            $this->runSchemaMatchAssertion($definition, $actual, false, $message);
        }

        /**
         * Asserts that a primitive with the given name exists in the specified registry, recording the result of the assertion.
         * @param string $name The name of the primitive to check for existence.
         * @param string|null $registry An optional registry name to look for the primitive. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPrimitiveExists (string $name, ?string $registry = null, string $message = "") : void {
            $this->runPrimitiveAssertion($name, $registry, true, $message);
        }

        /**
         * Asserts that a primitive with the given name does not exist in the specified registry, recording the result of the assertion.
         * @param string $name The name of the primitive to check for non-existence.
         * @param string|null $registry An optional registry name to look for the primitive. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertPrimitiveNotExists (string $name, ?string $registry = null, string $message = "") : void {
            $this->runPrimitiveAssertion($name, $registry, false, $message);
        }

        /**
         * Asserts that a schema with the given name exists in the specified registry, recording the result of the assertion.
         * @param string $name The name of the schema to check for existence.
         * @param string|null $registry An optional registry name to look for the schema. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertSchemaExists (string $name, ?string $registry = null, string $message = "") : void {
            $this->runSchemaAssertion($name, $registry, true, $message);
        }

        /**
         * Asserts that a schema with the given name does not exist in the specified registry, recording the result of the assertion.
         * @param string $name The name of the schema to check for non-existence.
         * @param string|null $registry An optional registry name to look for the schema. If null, the default registry will be used.
         * @param string $message An optional message providing additional context about the assertion.
         */
        public function assertSchemaNotExists (string $name, ?string $registry = null, string $message = "") : void {
            $this->runSchemaAssertion($name, $registry, false, $message);
        }
    }
?>
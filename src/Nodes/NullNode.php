<?php
    /**
     * Project Name:    Wingman Verix - Null Node
     * Created by:      Angel Politis
     * Creation Date:   Dec 21 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;

    # Import the following classes to the current scope.
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a node of null type.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NullNode extends PrimitiveNode {
        /**
         * Creates a new null node.
         * @param PrimitiveRegistry $registry The primitive registry.
         */
        public function __construct (PrimitiveRegistry $registry) {
            parent::__construct("null", [], $registry);
        }

        /**
         * Gets the value of a null node.
         * @return mixed The value.
         */
        public function serialise () : string {
            return "null";
        }

        /**
         * Validates a value against a null node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            return $value === null ? ValidationResult::ok($value) : ValidationResult::error("Expected null", $value)->withPath($path);
        }
    }
?>
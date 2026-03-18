<?php
    /**
     * Project Name:    Wingman Verix - Any Node
     * Created by:      Angel Politis
     * Creation Date:   Dec 22 2025
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
     * Represents a node of any type.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AnyNode extends PrimitiveNode {
        /**
         * Creates a new any type.
         * @param PrimitiveRegistry $registry The primitive registry.
         */
        public function __construct (PrimitiveRegistry $registry) {
            parent::__construct("any", [], $registry);
        }
    
        /**
         * Validates a value against an any node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            return ValidationResult::ok($value);
        }
    
        /**
         * Serialises an any node to a string.
         * @return string The serialised any node.
         */
        public function serialise () : string {
            return "any";
        }
    }
?>
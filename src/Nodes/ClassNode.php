<?php
    /**
     * Project Name:    Wingman Verix - Class Node
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
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a node that validates instances of a specific class.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ClassNode extends CompositeNode {
        /**
         * The name of a class node.
         * @var string
         */
        public readonly string $name;

        /**
         * Creates a new class node.
         * @param string $name The name of the class.
         */
        public function __construct (string $name) {
            $this->name = $name;
        }

        /**
         * Gets the name of a class node.
         * @return string The name of the class.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Serialises a class node to a string.
         * @return string The serialised class node.
         */
        public function serialise () : string {
            return $this->name;
        }
    
        /**
         * Validates a value against a class node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!$value instanceof $this->name) {
                return ValidationResult::error("Expected instance of class {$this->name}", $value)->withPath($path);
            }
            return ValidationResult::ok($value);
        }
    }
?>
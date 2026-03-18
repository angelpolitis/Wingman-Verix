<?php
    /**
     * Project Name:    Wingman Verix - Union Node
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
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a union of nodes.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class UnionNode extends CompositeNode {
        /**
         * The types of a union node.
         * @var Node[]
         */
        public readonly array $types;
        
        /**
         * Creates a new union node.
         * @param Node[] $types The types.
         */
        public function __construct (array $types) {
            $this->types = $types;
        }

        /**
         * Gets the types of a union node.
         * @return Node[] The types.
         */
        public function getTypes () : array {
            return $this->types;
        }

        /**
         * Serialises a union node to a string.
         * @return string The serialised union node.
         */
        public function serialise () : string {
            return implode(" | ", array_map(fn ($type) => $type->serialise(), $this->types));
        }
        
        /**
         * Validates a value against a union node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            $errors = [];
        
            foreach ($this->types as $type) {
                $result = $type->validate($value, $strict, $path);
                
                if ($result->isValid()) return $result;
        
                $errors = array_merge($errors, $result->getErrors());
            }
            
            return new ValidationResult($value, false, $errors);
        }
    }
?>
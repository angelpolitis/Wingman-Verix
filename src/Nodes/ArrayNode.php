<?php
    /**
     * Project Name:    Wingman Verix - Array Node
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
     * Represents a node that contains other nodes.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ArrayNode extends CompositeNode {
        /**
         * The item type.
         * @var Node
         */
        public readonly Node $itemType;

        /**
         * Creates a new array type.
         * @param Node $itemType The item type.
         */
        public function __construct (Node $itemType) {
            $this->itemType = $itemType;
        }

        /**
         * Gets the item type of an array node.
         * @return Node The item type.
         */
        public function getItemType () : Node {
            return $this->itemType;
        }

        /**
         * Validates a value against an array node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!is_array($value)) {
                return ValidationResult::error("Expected array", $value)->withPath($path);
            }
        
            $normalised = [];
            $result = ValidationResult::ok([]);

            foreach ($value as $i => $item) {
                $itemResult = $this->itemType->validate($item, $strict, "{$path}[{$i}]");
                $normalised[$i] = $itemResult->value;
                $result = $result->merge($itemResult);
            }

            return new ValidationResult($normalised, $result->isValid(), $result->getErrors(), $result->getMetadata());
        }

        /**
         * Serialises an array node to a string.
         * @return string The serialised array node.
         */
        public function serialise () : string {
            $pattern = $this->itemType instanceof UnionNode || $this->itemType instanceof IntersectionNode ? "(%s)[]" : "%s[]";
            return sprintf($pattern, $this->itemType->serialise());
        }
    }
?>
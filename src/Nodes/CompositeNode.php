<?php
    /**
     * Project Name:    Wingman Verix - Composite Node
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
     * Represents a composite node.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class CompositeNode implements Node {
        /**
         * Converts a composite node to a string.
         * @return string The string representation of the composite node.
         */
        public function __toString () : string {
            return $this->serialise();
        }

        /**
         * Serialises a composite node node to a string.
         * @return string The serialised composite node node.
         */
        abstract public function serialise () : string;

        /**
         * Validates a value against a composite node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated.
         * @return ValidationResult The validation result.
         */
        abstract public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult;
    }
?>
<?php
    /*/
	 * Project Name:    Wingman — Verix — Node
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 23 2025
	 * Last Modified:   Dec 23 2025
    /*/

    # Use the Verix.Interfaces namespace.
    namespace Wingman\Verix\Interfaces;

    # Import the following classes to the current scope.
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a node.
     * @package Wingman\Verix\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface Node {
        /**
         * Converts a node to a string.
         * @return string The string representation of the node.
         */
        public function __toString () : string;

        /**
         * Serialises a node to a string.
         * @return string The serialised node.
         */
        public function serialise () : string;

        /**
         * Validates a value against a node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path of the value.
         * @return ValidationResult The validation result.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult;
    }
?>
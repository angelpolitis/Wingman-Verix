<?php
    /*/
	 * Project Name:    Wingman — Verix — Any Type
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 23 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    /**
     * Represents any value.
     * @package Wingman\Verix\Types
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AnyType extends Type {
        /**
         * Creates a new any type.
         */
        public function __construct () {
            $this->name = "any";
            $this->description = "Any value.";
            $this->parameters = [];
        }

        /**
         * Serialises an any type to a string.
         * @return string The serialised any type.
         */
        public function getError (mixed $value) : string {
            return "Expected any value. Input: " . (is_scalar($value) ? $value : gettype($value));
        }

        /**
         * Maps positional parameters to named parameters for an any type.
         * @param array $values The positional parameter values.
         * @return array|null The mapped named parameters, or `null` if mapping is not possible.
         */
        public function mapPositionalParams (array $values) : ?array {
            return null;
        }

        /**
         * Parses a value as an any type.
         * @param mixed $value The value to parse.
         * @param array $params The named parameters for parsing (not used for any type).
         * @return mixed The parsed value (same as input for any type).
         */
        public function parse (mixed $value, array $params = []) : mixed {
            return $value;
        }

        /**
         * Validates a value against an any type.
         * @param mixed $value The value to validate.
         * @param array $params The named parameters for validation (not used for any type).
         * @return bool Whether the value is valid (always `true` for any type).
         */
        public function validate (mixed $value, array $params = []) : bool {
            return true;
        }
    }
?>
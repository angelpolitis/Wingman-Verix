<?php
    /*/
	 * Project Name:    Wingman — Verix — Boolean Type
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 22 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    /**
     * Represents the boolean type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class BooleanType extends Type {
        /**
         * Creates a new boolean type.
         */
        public function __construct () {
            $this->name = "bool";
            $this->description = "A boolean value (true or false).";
            $this->parameters = [];
        }

        /**
         * Gets an error message for a boolean type.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected a boolean value. Input: " . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        /**
         * Maps positional parameters to named parameters for a boolean type.
         * @param array $values The positional parameter values.
         * @return array|null The mapped named parameters, or `null` if mapping is not possible.
         */
        public function mapPositionalParams (array $values) : ?array {
            return null;
        }

        /**
         * Parses a value as a boolean type.
         * @param mixed $value The value to parse.
         * @param array $params The named parameters for parsing (not used for boolean type).
         * @return mixed The parsed value (boolean if parsing is successful, original value otherwise).
         */
        public function parse (mixed $value, array $params = []) : mixed {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
        }

        /**
         * Validates a value against a boolean type.
         * @param mixed $value The value to validate.
         * @param array $params The named parameters for validation (not used for boolean type).
         * @return bool Whether the value is valid.
         */
        public function validate (mixed $value, array $params = []) : bool {
            return is_bool($value);
        }
    }
?>
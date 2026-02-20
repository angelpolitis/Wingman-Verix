<?php
    /*/
	 * Project Name:    Wingman — Verix — Number Type
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 22 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Represents the number type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class NumberType extends Type {
        /**
         * Creates a new number type.
         */
        public function __construct () {
            $this->name = "number";
            $this->description = "A numeric value.";
            $this->parameters = [
                new Parameter(
                    name: "min",
                    type: "float"
                ),
                new Parameter(
                    name: "max",
                    type: "float"
                )
            ];
        }

        /**
         * Gets the error message for a value that fails validation.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected a numeric value. Input: " . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        /**
         * Maps positional parameters to named parameters.
         * @param array $values The positional parameter values.
         * @return array|null The mapped named parameters or null if mapping is not possible.
         */
        public function mapPositionalParams (array $values) : ?array {
            return match (count($values)) {
                2 => [
                    "min" => $values[0],
                    "max" => $values[1]
                ],
                default => throw new RuntimeException("{$this->name}{} expects 2 positional parameters, got " . count($values))
            };
        }

        /**
         * Parses a value according to the type's rules.
         * @param mixed $value The value to parse.
         * @param array $params The parameters for parsing (optional).
         * @return mixed The parsed value.
         */
        public function parse (mixed $value, array $params = []) : mixed {
            return is_scalar($value) ? floatval($value) : $value;
        }

        /**
         * Validates a value against the type's rules.
         * @param mixed $value The value to validate.
         * @param array $params The parameters for validation (optional).
         * @return bool True if the value is valid, false otherwise.
         */
        public function validate (mixed $value, array $params = []) : bool {
            return is_numeric($value);
        }
    }
?>
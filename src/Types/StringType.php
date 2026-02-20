<?php
    /*/
	 * Project Name:    Wingman — Verix — String Type
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 23 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Represents any string.
     * @package Wingman\Verix\Types
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class StringType extends AnyType {
        /**
         * Creates a new string type.
         */
        public function __construct () {
            $this->name = "string";
            $this->description = "A string value.";
            $this->parameters = [
                new Parameter(
                    name: "length",
                    type: "int",
                    constraint: fn ($v) => $v >= 0,
                    constraintError: "Length must be non-negative.",
                    validator: fn ($value, $paramValue) => strlen($value) === $paramValue,
                    validatorError: "String length must be equal to {length}."
                ),
                new Parameter(
                    name: "minLength",
                    type: "int",
                    constraint: fn ($v) => $v >= 0,
                    constraintError: "Minimum length must be non-negative.",
                    validator: fn ($value, $paramValue) => strlen($value) >= $paramValue,
                    validatorError: "String length must be at least {minLength}."
                ),
                new Parameter(
                    name: "maxLength",
                    type: "int",
                    constraint: fn ($v) => $v >= 0,
                    constraintError: "Maximum length must be non-negative.",
                    validator: fn ($value, $paramValue) => strlen($value) <= $paramValue,
                    validatorError: "String length must be at most {maxLength}."
                ),
                new Parameter(
                    name: "pattern",
                    type: "string",
                    validator: fn ($value, $paramValue) => preg_match($paramValue, $value) === 1,
                    validatorError: "String must match the pattern {pattern}."
                ),
                new Parameter(
                    name: "charset",
                    type: "string",
                    constraint: fn ($value) => in_array(strtolower($value), array_map("strtolower", mb_list_encodings())),
                    constraintError: "Charset must be a valid character set.",
                    validator: fn ($value, $paramValue) => mb_check_encoding($value, $paramValue),
                    validatorError: "String must be valid in the character set {charset}."
                ),
                new Parameter(
                    name: "lowercase",
                    type: "bool",
                    validator: fn ($value, $paramValue) => $paramValue ? mb_strtolower($value) === $value : mb_strtolower($value) !== $value,
                    validatorError: fn ($value, $paramValue) => $paramValue ? "String must be lowercase." : "String must not be lowercase."
                ),
                new Parameter(
                    name: "uppercase",
                    type: "bool",
                    validator: fn ($value, $paramValue) => $paramValue ? mb_strtoupper($value) === $value : mb_strtoupper($value) !== $value,
                    validatorError: fn ($value, $paramValue) => $paramValue ? "String must be uppercase." : "String must not be uppercase."
                )
            ];
        }

        /**
         * Gets the error message for a value that fails validation.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected a string. Input: " . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        /**
         * @throws RuntimeException If the number of positional parameters is invalid.
         */
        public function mapPositionalParams (array $values) : array {
            return match (count($values)) {
                1 => [
                    "length" => $values[0]
                ],
                2 => [
                    "minLength" => $values[0],
                    "maxLength" => $values[1]
                ],
                default => throw new RuntimeException("{$this->name}{} expects 1 or 2 positional parameters, got " . count($values))
            };
        }

        /**
         * Parses a value according to the type's rules.
         * @param mixed $value The value to parse.
         * @param array $params The parameters for parsing (optional).
         * @return mixed The parsed value.
         */
        public function parse (mixed $value, array $params = []) : mixed {
            return is_scalar($value) || is_object($value) && method_exists($value, "__toString") ? (string) $value : $value;
        }

        /**
         * Validates a value against the type's rules.
         * @param mixed $value The value to validate.
         * @param array $params The parameters for validation (optional).
         * @return bool True if the value is valid, false otherwise.
         */
        public function validate (mixed $value, array $params = []) : bool {
            return is_string($value) && parent::validate($value, $params);
        }
    }
?>
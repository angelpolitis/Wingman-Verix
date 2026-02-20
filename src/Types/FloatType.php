<?php
    /*/
	 * Project Name:    Wingman — Verix — Float Type
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
     * Represents the float type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class FloatType extends NumberType {
        /**
         * Creates a new float type.
         */
        public function __construct () {
            $this->name = "float";
            $this->description = "A float value.";
            $this->parameters = [
                new Parameter(
                    name: "min",
                    type: "float",
                    validator: fn (mixed $value, float $paramValue) => $value >= $paramValue,
                    validatorError: "Float value must be at least {min}."
                ),
                new Parameter(
                    name: "max",
                    type: "float",
                    validator: fn (mixed $value, float $paramValue) => $value <= $paramValue,
                    validatorError: "Float value must be at most {max}."
                ),
                new Parameter(
                    name: "precision",
                    type: "int",
                    constraint: fn (int $v) => $v >= 0,
                    constraintError: "Precision must be non-negative.",
                    validator: function (mixed $value, int $paramValue) : bool {
                        $decimalPart = explode('.', strval($value))[1] ?? "";
                        return strlen($decimalPart) <= $paramValue;
                    },
                    validatorError: "Float value must have at most {precision} decimal places."
                )
            ];
        }

        /**
         * Gets the error message for an invalid value.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected a float value. Input: " . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        /**
         * Maps positional parameters to named parameters for a float type.
         * @param array $values The positional parameter values.
         * @throws RuntimeException If the number of positional parameters is invalid.
         */
        public function mapPositionalParams (array $values) : array {
            return match (count($values)) {
                1 => [
                    "precision" => $values[0]
                ],
                2 => [
                    "min" => $values[0],
                    "max" => $values[1]
                ],
                3 => [
                    "precision" => $values[0],
                    "min" => $values[1],
                    "max" => $values[2]
                ],
                default => throw new RuntimeException("{$this->name}{} expects 1, 2 or 3 positional parameters, got " . count($values))
            };
        }

        /**
         * Parses a value as a float type.
         * @param mixed $value The value to parse.
         * @param array $params The named parameters for parsing (not used for float type).
         * @return mixed The parsed value (float if parsing is successful, original value otherwise).
         */
        public function parse (mixed $value, array $params = []) : mixed {
            if (is_int($value) || is_float($value)) {
                return floatval($value);
            }
        
            if (is_string($value)) {
                if (isset($params["base"])) {
                    return intval($value, (int) $params["base"]);
                }
        
                return intval($value);
            }
        
            return $value;
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Integer Type
     * Created by:      Angel Politis
     * Creation Date:   Dec 22 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Represents the integer type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class IntegerType extends NumberType {
        /**
         * Creates a new integer type.
         */
        public function __construct () {
            $parameters = [
                new Parameter(
                    name: "base",
                    type: "int",
                    default: 10,
                    constraint: fn (int $v) => $v >= 2 && $v <= 36,
                    constraintError: "The base must be between 2 and 36.",
                    validator: fn (int $v, int $base) => $this->matchesBase((string) $v, $base),
                    validatorError: "The value does not match the specified base."
                ),
                new Parameter(
                    name: "min",
                    type: "float",
                    validator: fn (int $v, $min) => $v >= $min,
                    validatorError: "The value is less than the specified minimum."
                ),
                new Parameter(
                    name: "max",
                    type: "float",
                    validator: fn (int $v, $max) => $v <= $max,
                    validatorError: "The value exceeds the specified maximum."
                ),
                new Parameter(
                    name: "length",
                    type: "int",
                    constraint: fn (int $v) => $v >= 0,
                    constraintError: "The length must be a non-negative integer.",
                    validator: fn (int $v, int $length) => strlen((string) $v) === $length,
                    validatorError: "The value does not match the specified length."
                ),
                new Parameter(
                    name: "minLength",
                    type: "int",
                    constraint: fn (int $v) => $v >= 0,
                    constraintError: "The minimum length must be a non-negative integer.",
                    validator: fn (int $v, int $minLength) => strlen((string) $v) >= $minLength,
                    validatorError: "The value is shorter than the specified minimum length."
                ),
                new Parameter(
                    name: "maxLength",
                    type: "int",
                    constraint: fn (int $v) => $v >= 0,
                    constraintError: "The maximum length must be a non-negative integer.",
                    validator: fn (int $v, int $maxLength) => strlen((string) $v) <= $maxLength,
                    validatorError: "The value exceeds the specified maximum length."
                ),
                new Parameter(
                    name: "even",
                    type: "bool",
                    validator: fn (int $v, bool $isEven) => $isEven ? ($v % 2 === 0) : true,
                    validatorError: "The integer must be even."
                ),
                new Parameter(
                    name: "odd",
                    type: "bool",
                    validator: fn (int $v, bool $isOdd) => $isOdd ? ($v % 2 !== 0) : true,
                    validatorError: "The integer must be odd."
                ),
                new Parameter(
                    name: "divisibleBy",
                    type: "int",
                    constraint: fn (int $v) => $v !== 0,
                    constraintError: "The divisor cannot be zero.",
                    validator: fn (int $v, int $divisor) => $v % $divisor === 0,
                    validatorError: "The integer must be divisible by {divisibleBy}."
                ),
                new Parameter(
                    name: "unsigned",
                    type: "bool",
                    validator: fn (int $v, bool $isUnsigned) => $isUnsigned ? ($v >= 0) : true,
                    validatorError: "The integer must be unsigned."
                ),
                new Parameter(
                    name: "bitDepth",
                    type: "int",
                    constraint: fn (int $v) => in_array($v, [8, 16, 32, 64]),
                    constraintError: "The bit depth must be one of the following values: 8, 16, 32, 64.",
                    validator: function (int $v, int $bitDepth) {
                        $min = $bitDepth === 8 ? -128 : ($bitDepth === 16 ? -32768 : ($bitDepth === 32 ? -2147483648 : -9223372036854775808));
                        $max = $bitDepth === 8 ? 127 : ($bitDepth === 16 ? 32767 : ($bitDepth === 32 ? 2147483647 : 9223372036854775807));
                        return $v >= $min && $v <= $max;
                    },
                    validatorError: "The integer must fit within the specified bit depth."
                )
            ];
            parent::__construct("int", $parameters, "An integer value.");
        }

        /**
         * Checks if a string value matches the specified base.
         * @param string $value The string value to check.
         * @param int $base The base to check against.
         * @return bool Whether the value matches the base.
         */
        private function matchesBase (string $value, int $base) : bool {
            $digits = substr("0123456789abcdefghijklmnopqrstuvwxyz", 0, $base);
            $pattern = '/^-?[' . preg_quote($digits, '/') . ']+$/i';
            return preg_match($pattern, $value) === 1;
        }        

        /**
         * Gets the error message for a failed validation.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected an integer value. Input: " . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        /**
         * Maps positional parameters to their corresponding named parameters.
         * @param array $values The array of positional parameter values.
         * @throws RuntimeException If the number of positional parameters is invalid.
         */
        public function mapPositionalParams (array $values) : array {
            return match (count($values)) {
                1 => [
                    "base" => $values[0]
                ],
                2 => [
                    "min" => $values[0],
                    "max" => $values[1]
                ],
                3 => [
                    "base" => $values[0],
                    "min" => $values[1],
                    "max" => $values[2]
                ],
                default => throw new RuntimeException("{$this->name}{} expects 1, 2 or 3 positional parameters, got " . count($values))
            };
        }

        /**
         * Parses a value according to the integer type's rules.
         * @param mixed $value The value to parse.
         * @param array $params The parameters for parsing.
         * @return mixed The parsed value.
         */
        public function parse (mixed $value, array $params = []) : mixed {
            if (is_int($value) || is_float($value)) {
                return intval("$value", (int) $params["base"]);
            }
        
            if (is_string($value)) {
                $base = (int) $params["base"];
                if ($this->matchesBase($value, $base)) {
                    return intval($value, $base);
                }
        
                return intval($value);
            }
        
            return $value;
        }
    }
?>
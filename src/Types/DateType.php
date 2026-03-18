<?php
    /**
     * Project Name:    Wingman Verix - Date Type
     * Created by:      Angel Politis
     * Creation Date:   Dec 23 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    # Import the following classes to the current scope.
    use DateTimeImmutable;
    use DateTimeZone;
    use RuntimeException;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Represents a date value.
     * @package Wingman\Verix\Types
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class DateType extends StringType {
        /**
         * Creates a new date type.
         */
        public function __construct () {
            $parameters = [
                new Parameter(
                    name: "format",
                    type: "string",
                    default: "Y-m-d",
                    validator: function ($v, $format) {
                        if ($v instanceof DateTimeImmutable) $v = $v->format($format);
                        return DateTimeImmutable::createFromFormat($format, $v) !== false;
                    },
                    validatorError: "The date does not match the format {format}."
                ),
                new Parameter(
                    name: "strict",
                    type: "bool",
                    default: true,
                    validator: function ($v, $strict, $params) {
                        if (!$strict) return true;
                        $format = $params['format'] ?? "Y-m-d";
                        $dt = DateTimeImmutable::createFromFormat($format, $v);
                        # Check for overflow (e.g., Feb 31 becoming March 3)
                        if ($v instanceof DateTimeImmutable) $v = $v->format($format);
                        return $dt && $dt->format($format) === $v;
                    },
                    validatorError: "The provided date is logically invalid (calendar overflow)."
                ),
                new Parameter(
                    name: "after",
                    type: "string",
                    validator: function ($v, $after, array $params) {
                        $v = $v instanceof DateTimeImmutable ? $v : new DateTimeImmutable($v);
                        return $v > new DateTimeImmutable($after);
                    },
                    validatorError: "The date must be after {after}."
                ),
                new Parameter(
                    name: "before",
                    type: "string",
                    validator: function ($v, $after, array $params) {
                        $v = $v instanceof DateTimeImmutable ? $v : new DateTimeImmutable($v);
                        return $v < new DateTimeImmutable($after);
                    },
                    validatorError: "The date must be before {before}."
                ),
                new Parameter(
                    name: "isFuture",
                    type: "bool",
                    validator: function ($v, $isFuture) {
                        $v = $v instanceof DateTimeImmutable ? $v : new DateTimeImmutable($v);
                        return $isFuture ? ($v > new DateTimeImmutable()) : true;
                    },
                    validatorError: "The date must be in the future."
                ),
                new Parameter(
                    name: "isPast",
                    type: "bool",
                    validator: function ($v, $isPast) {
                        $v = $v instanceof DateTimeImmutable ? $v : new DateTimeImmutable($v);
                        return $isPast ? ($v < new DateTimeImmutable()) : true;
                    },
                    validatorError: "The date must be in the past."
                ),
                new Parameter(
                    name: "dayOfWeek",
                    type: "array", # Array of ints 1-7 (Mon-Sun)
                    validator: fn ($v, array $days) => 
                        in_array((int)(new DateTimeImmutable($v))->format('N'), $days),
                    validatorError: "The date falls on a disallowed day of the week."
                ),
                new Parameter(
                    name: "timezone",
                    type: "string",
                    constraint: fn ($tz) => in_array($tz, timezone_identifiers_list()),
                    constraintError: "Invalid timezone identifier.",
                    # Validation logic handled during parsing
                )
            ];
            parent::__construct("date", $parameters, "A date value.");
        }
    
        /**
         * Gets an error message for a date type.
         * @param mixed $value The value that failed validation.
         * @return string The error message.
         */
        public function getError (mixed $value) : string {
            return "Expected a valid date string or timestamp. Input: " . var_export($value, true);
        }
    
        /**
         * Maps positional parameters to named parameters for a date type.
         * @param array $values The positional parameter values.
         * @return array The mapped named parameters.
         * @throws RuntimeException If the number of positional parameters is not 1, 2, or 3.
         */
        public function mapPositionalParams (array $values) : array {
            return match (count($values)) {
                1 => ["format" => $values[0]],
                2 => ["after" => $values[0], "before" => $values[1]],
                3 => ["format" => $values[0], "after" => $values[1], "before" => $values[2]],
                default => throw new RuntimeException("date{} expects 1, 2 or 3 positional parameters.")
            };
        }
    
        /**
         * Parses a value as a date type.
         * @param mixed $value The value to parse.
         * @param array $params The named parameters for parsing.
         * @return mixed The parsed value (DateTimeImmutable if parsing is successful, original value otherwise).
         */
        public function parse (mixed $value, array $params = []) : mixed {
            $format = $params["format"] ?? "Y-m-d";
            $tz = isset($params["timezone"]) ? new DateTimeZone($params["timezone"]) : null;
    
            if (is_string($value)) {
                $dt = DateTimeImmutable::createFromFormat($format, $value, $tz);
                return $dt ?: $value; 
            }
    
            if (is_int($value)) {
                return (new DateTimeImmutable())->setTimestamp($value)->setTimezone($tz ?? new DateTimeZone("UTC"));
            }
    
            return $value;
        }

        /**
         * Validates a value against a date type.
         * @param mixed $value The value to validate.
         * @param array $params The named parameters for validation.
         * @return bool Whether the value is valid.
         */
        public function validate (mixed $value, array $params = []) : bool {
            return $value instanceof DateTimeImmutable || is_string($value) || is_int($value);
        }
    }
?>
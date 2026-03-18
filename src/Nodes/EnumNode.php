<?php
    /**
     * Project Name:    Wingman Verix - Enum Node
     * Created by:      Angel Politis
     * Creation Date:   Dec 22 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;
    
    # Import the following classes to the current scope.
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents an enum type with a set of allowed values.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class EnumNode extends PrimitiveNode {
        /**
         * The allowed enum values.
         * @var array<mixed>
         */
        public readonly array $values;
    
        /**
         * Creates a new enum node.
         * @param array<mixed> $values The allowed enum values.
         * @param array<string, mixed> $params Additional parameters.
         * @param PrimitiveRegistry $registry The primitive registry.
         */
        public function __construct (array $values, array $params = [], PrimitiveRegistry $registry) {
            parent::__construct("enum", $params, $registry);
            $this->values = $values;
        }

        /**
         * Serialises the enum values for representation.
         * @return array<string> The serialised enum values.
         */
        protected function serialiseValues () : array {
            return array_map(fn ($value) => match (true) {
                is_string($value) => "'$value'",
                is_bool($value) => var_export($value, true),
                is_null($value) => "null",
                default => (string) $value,
            }, $this->values);
        }

        /**
         * Gets the allowed enum values.
         * @return array<mixed> The allowed enum values.
         */
        public function getValues () : array {
            return $this->values;
        }
    
        /**
         * Serialises an enum node to a string.
         * @return string The serialised enum node.
         */
        public function serialise () : string {
            return "enum{" . implode('|', $this->serialiseValues()) . "}";
        }
    
        /**
         * Validates a value against an enum node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!in_array($value, $this->values, true)) {
                return ValidationResult::error("Expected one of [" . implode(", ", $this->serialiseValues()) . "]", $value)->withPath($path);
            }
            return ValidationResult::ok($value);
        }
    }
?>
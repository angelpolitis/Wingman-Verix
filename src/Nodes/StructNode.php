<?php
    /**
     * Project Name:    Wingman Verix - Struct Node
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
    use Wingman\Verix\Specs\RestField;
    use Wingman\Verix\Specs\StructField;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a struct of fields.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class StructNode extends CompositeNode {
        /**
         * The fields of a struct.
         * @var array<string, StructField>
         */
        public readonly array $fields;

        /**
         * The rest field of the struct, if any.
         * @var ?RestField
         */
        public readonly ?RestField $rest;

        /** 
         * Whether the struct is exact (no extra fields allowed).
         * @var bool
         */
        public readonly bool $exact;

        /**
         * Creates a new struct type.
         * @param array<string, StructField> $fields The fields of the struct.
         * @param bool $exact Whether the struct is exact (no extra fields allowed).
         * @param ?RestField $rest The rest field of the struct, if any.
         */
        public function __construct (array $fields = [], bool $exact = true, ?RestField $rest = null) {
            $this->fields = array_map(
                fn ($field) => $field instanceof StructField ? $field : new StructField(...$field),
                $fields
            );
            $this->rest = $rest;
            $this->exact = $exact;
        }

        /**
         * Validates the fixed fields of a struct.
         * @param ValidationResult $result The current validation result.
         * @param bool $strict Whether to perform strict validation.
         * @param string $path The current validation path.
         * @return ValidationResult The updated validation result.
         */
        protected function validateFixedFields (ValidationResult $result, bool $strict, string $path) : ValidationResult {
            $usedKeys = [];
            $value = $result->getValue();

            foreach ($this->fields as $name => $field) {
                $fieldPath = $path === "" ? $name : "{$path}.{$name}";
        
                if (!array_key_exists($name, $value)) {
                    if ($field->isOptional()) continue;
                    if ($field->getDefault() !== null) {
                        $value[$name] = $field->getDefault();
                        $usedKeys[$name] = true;
                        continue;
                    }
                    $result = $result->merge(ValidationResult::error("Struct missing required field", $field)->withPath($fieldPath));
                    continue;
                }
        
                $usedKeys[$name] = true;
        
                $fieldResult = $field->getType()->validate($value[$name], $strict, $fieldPath);
                $value[$name] = $fieldResult->getValue();
                $result = $result->merge($fieldResult);
            }
            return $result->with([
                "value" => $value,
                "metadata" => compact("usedKeys")
            ]);
        }

        /**
         * Gets the fields of a struct.
         * @return array<string, StructField> The fields.
         */
        public function getFields () : array {
            return $this->fields;
        }

        /**
         * Gets the rest field of a struct.
         * @return ?RestField The rest field; `null` if none.
         */
        public function getRest () : ?RestField {
            return $this->rest;
        }

        /**
         * Checks whether the struct is exact (no extra fields allowed).
         * @return bool Whether the struct is exact.
         */
        public function isExact () : bool {
            return $this->exact;
        }

        /**
         * Serialises a struct node to a string.
         * @return string The serialised struct node.
         */
        public function serialise () : string {
            $parts = [];
        
            # Serialise the fixed fields of the struct.
            foreach ($this->fields as $name => $field) {
                $part = $name;
                if ($field->isOptional()) $part .= '?';
                $part .= ": " . $field->getType()->serialise();
                $parts[] = $part;
            }
        
            # Serialise the rest fields of the struct.
            if ($this->rest?->getType() instanceof AnyNode) $parts[] = "...";
        
            return "struct{" . implode(", ", $parts) . '}';
        }
    
        /**
         * Validates a value against a struct node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!is_array($value)) {
                return ValidationResult::error("Expected struct", $value)->withPath($path);
            }
        
            $normalised = $value;
            $result = ValidationResult::ok($normalised);
        
            # (1) Validate the fixed fields of the struct.
            $result = $this->validateFixedFields($result, $strict, $path);
            $normalised = $result->getValue();
            $usedKeys = $result->getMetadata()["usedKeys"] ?? [];
        
            # (2) Validate the unexpected/rest fields of the struct.
            foreach ($normalised as $key => $val) {
                if (isset($usedKeys[$key])) continue;
        
                $fieldPath = $path === "" ? $key : "{$path}.{$key}";
        
                if ($this->rest) {
                    $restResult = $this->rest->getType()->validate($val, $strict, $fieldPath);
                    $normalised[$key] = $restResult->getValue();
                    $result = $result->merge($restResult);
                }
                else {
                    $result = $result->merge(ValidationResult::error("Unexpected struct field '{$key}'", $val)->withPath($fieldPath));
                }
            }
        
            return $result->withValue($normalised);
        }
    }
?>
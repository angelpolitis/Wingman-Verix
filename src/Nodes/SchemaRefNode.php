<?php
    /**
     * Project Name:    Wingman Verix - Schema Reference Node
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
    use Wingman\Verix\Registries\SchemaRegistry;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a reference to a schema by name.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SchemaRefNode extends CompositeNode {
        /**
         * The name of the referenced schema.
         * @var string
         */
        public readonly string $name;

        /**
         * The schema registry.
         * @var SchemaRegistry
         */
        public readonly SchemaRegistry $registry;
        
        /**
         * Creates a new schema reference type.
         * @param string $name The name of the referenced schema.
         * @param SchemaRegistry $registry The schema registry.
         */
        public function __construct (string $name, SchemaRegistry $registry) {
            $this->name = $name;
            $this->registry = $registry;
        }

        /**
         * Gets the name of a schema reference.
         * @return string The name of the referenced schema.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the registry of a schema reference.
         * @return SchemaRegistry The schema registry.
         */
        public function getRegistry () : SchemaRegistry {
            return $this->registry;
        }

        /**
         * Serialises a schema reference node to a string.
         * @return string The serialised schema reference node.
         */
        public function serialise () : string {
            return "Schema({$this->name})";
        }

        /**
         * Validates a value against a schema reference node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!$this->registry->has($this->name)) {
                return ValidationResult::error("Unknown schema: {$this->name}", $value)->withPath($path);
            }
            $schema = $this->registry->get($this->name);
            return $schema->validate($value, $strict, $path);
        }
    }
?>
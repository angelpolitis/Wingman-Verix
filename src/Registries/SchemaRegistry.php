<?php
    /**
     * Project Name:    Wingman Verix - Schema Registry
     * Created by:      Angel Politis
     * Creation Date:   Dec 21 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Registries namespace.
    namespace Wingman\Verix\Registries;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Bridge\Corvus\Emitter;
    use Wingman\Verix\Enums\Signal;
    use Wingman\Verix\Interfaces\Node;

    /**
     * Represents a registry for schema definitions.
     * @package Wingman\Verix\Registries
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SchemaRegistry {
        /**
         * The registered schema definitions.
         * @var array<string, Node>
         */
        protected array $schemata = [];

        /**
         * Retrieves a composite type node by name.
         * @param string $name The name of the schema.
         * @return Node The schema definition.
         * @throws RuntimeException If the schema is unknown.
         */
        public function get (string $name) : Node {
            if (!isset($this->schemata[$name])) {
                throw new RuntimeException("Unknown composite type: {$name}");
            }
            return $this->schemata[$name];
        }

        /**
         * Registers a new schema definition.
         * @param string $name The name of the schema.
         * @param Node $schema The node to register.
         */
        public function register (string $name, Node $schema) : void {
            $this->schemata[$name] = $schema;
            Emitter::create()->with(name: $name, node: $schema, registry: $this)->emit(Signal::SCHEMA_REGISTERED);
        }

        /**
         * Checks whether a schema definition exists by name.
         * @param string $name The name of the schema.
         * @return bool Whether the definition exists.
         */
        public function has (string $name) : bool {
            return isset($this->schemata[$name]);
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Signal
     * Created by:      Angel Politis
     * Creation Date:   Mar 17 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Enums namespace.
    namespace Wingman\Verix\Enums;

    /**
     * Represents a signal emitted by Verix during type registration and schema lifecycle
     * operations.
     *
     * Each case maps to a dot-notation string identifier consumed by Corvus listeners.
     * Cases can be passed directly to `emit()` — coercion to their string value via `->value`
     * is required when the method expects a plain string.
     *
     * @package Wingman\Verix\Enums
     * @author  Angel Politis <info@angelpolitis.com>
     * @since   1.0
     */
    enum Signal : string {

        // ─── Class ───────────────────────────────────────────────────────────────

        /**
         * Emitted after a class alias has been registered in a ClassRegistry.
         * Payload: `alias` (string), `fqcn` (string), `registry` (ClassRegistry).
         */
        case CLASS_REGISTERED = "verix.class.registered";

        // ─── Primitive ───────────────────────────────────────────────────────────

        /**
         * Emitted after a primitive type definition (including derived aliases) has been
         * registered in a PrimitiveRegistry.
         * Payload: `definition` (PrimitiveDefinition), `registry` (PrimitiveRegistry).
         */
        case PRIMITIVE_REGISTERED = "verix.primitive.registered";

        // ─── Schema ──────────────────────────────────────────────────────────────

        /**
         * Emitted after a DSL expression has been successfully parsed into a normalised
         * node tree and a Schema object constructed from it. Fires for every `Schema::from()`,
         * `Schema::register()`, and `Schema::merge()` call.
         * Payload: `expression` (string), `schema` (Schema).
         */
        case SCHEMA_PARSED = "verix.schema.parsed";

        /**
         * Emitted after a named schema has been stored in a SchemaRegistry.
         * Payload: `name` (string), `node` (Node), `registry` (SchemaRegistry).
         */
        case SCHEMA_REGISTERED = "verix.schema.registered";

        /**
         * Emitted after `Schema::validate()` has been called, regardless of the result.
         * Payload: `value` (mixed), `result` (ValidationResult), `schema` (Schema).
         */
        case SCHEMA_VALIDATED = "verix.schema.validated";
    }
?>
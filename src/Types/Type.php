<?php
    /**
     * Project Name:    Wingman Verix - Type
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
    use Wingman\Verix\PrimitiveDefinition;

    /**
     * The base class for all types; types define the structure and validation rules for primitive types.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class Type {
        /**
         * The name of a type.
         * @var string
         */
        public readonly string $name;

        /**
         * The parameters of a type.
         * @var array
         */
        public readonly array $parameters;

        /**
         * The description of a type.
         * @var string|null
         */
        public readonly ?string $description;

        /**
         * Initialises the type's immutable properties.
         * @param string $name The name of the type.
         * @param array $parameters The parameters of the type.
         * @param string|null $description The description of the type.
         */
        public function __construct (string $name = "", array $parameters = [], ?string $description = null) {
            $this->name = $name;
            $this->parameters = $parameters;
            $this->description = $description;
        }

        /**
         * Handles an error during parsing or validation.
         * @param mixed $value The value that caused the error.
         * @return string $message The error message.
         */
        abstract public function getError (mixed $value) : string;

        /**
         * Maps positional parameters to named parameters based on implicit parameter definitions.
         * @param array $values The positional parameters.
         * @return array|null The mapped named parameters or `null` if not supported.
         */
        abstract public function mapPositionalParams (array $values) : ?array;

        /**
         * Parses a value according to a type.
         * @param mixed $value The value to parse.
         * @param array $params The parameters for parsing.
         * @return mixed The parsed value.
         */
        abstract public function parse (mixed $value, array $params = []) : mixed;

        /**
         * Validates a value against a type.
         * @param mixed $value The value to validate.
         * @param array $params The parameters for validation.
         * @return bool The result of the validation.
         */
        abstract public function validate (mixed $value, array $params = []) : bool;

        /**
         * Gets the primitive definition of a type.
         * @return PrimitiveDefinition The primitive definition.
         */
        public static function getDefinition () : PrimitiveDefinition {
            $type = new static();
            return new PrimitiveDefinition(
                $type->name,
                get_parent_class($type),
                [$type, "validate"],
                $type->parameters,
                method_exists($type, "parse") ? [$type, "parse"] : null,
                $type->description,
                method_exists($type, "getError") ? [$type, "getError"] : null,
                [$type, "mapPositionalParams"]
            );
        }
    }
?>
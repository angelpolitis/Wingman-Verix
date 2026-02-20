<?php
    /*/
	 * Project Name:    Wingman — Verix — Type
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 22 2025
	 * Last Modified:   Feb 19 2026
    /*/

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
        public string $name;

        /**
         * The parameters of a type.
         * @var array
         */
        public array $parameters;

        /**
         * The description of a type.
         * @var string|null
         */
        public ?string $description;

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
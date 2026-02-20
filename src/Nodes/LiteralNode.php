<?php
    /*/
	 * Project Name:    Wingman — Verix — Literal Node
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 24 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;

    # Import the following classes to the current scope.
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a literal node.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class LiteralNode extends PrimitiveNode {
        /**
         * The value of a literal node.
         * @var mixed
         */
        protected mixed $value;

        /**
         * Creates a new any type.
         * @param PrimitiveRegistry $registry The primitive registry.
         */
        public function __construct (mixed $value, PrimitiveRegistry $registry) {
            parent::__construct(static::serialiseLiteral($value), [], $registry);
            $this->value = $value;
        }

        /**
         * Serialises a literal value.
         * @param mixed $value The value to serialise.
         * @return string The serialised value.
         */
        protected static function serialiseLiteral (mixed $value) : string {
            return is_string($value)
                ? "'" . addslashes($value) . "'"
                : var_export($value, true);
        }

        /**
         * Gets the value of a literal node.
         * @return mixed The value.
         */
        public function getValue () : mixed {
            return $this->value;
        }
    
        /**
         * Serialises a literal node to a string.
         * @return string The serialised literal node.
         */
        public function serialise () : string {
            return static::serialiseLiteral($this->value);
        }
    
        /**
         * Validates a value against a literal node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            $ok = $strict ? $value === $this->value : $value == $this->value;
    
            return $ok
                ? ValidationResult::ok($this->value)
                : ValidationResult::error("Expected literal " . var_export($this->value, true), $value)->withPath($path);
        }
    }
?>
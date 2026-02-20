<?php
    /*/
	 * Project Name:    Wingman — Verix — Struct Field Specification
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Dec 23 2025
    /*/

    # Use the Verix.Specs namespace.
    namespace Wingman\Verix\Specs;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;

    /**
     * Represents a struct field specification.
     * @package Wingman\Verix\Specs
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class StructField {
        /**
         * The type of the struct field.
         * @var Node
         */
        public Node $type;

        /**
         * Whether the struct field is optional.
         * @var bool
         */
        public bool $optional;

        /**
         * Whether the struct field is readonly.
         * @var bool
         */
        public bool $readonly;

        /**
         * The default value of the struct field.
         * @var mixed
         */
        public mixed $default;

        /**
         * Creates a new struct field specification.
         * @param Node $type The type of the struct field.
         * @param bool $optional Whether the struct field is optional.
         * @param bool $readonly Whether the struct field is readonly.
         * @param mixed $default The default value of the struct field.
         */
        public function __construct (Node $type, bool $optional = false, bool $readonly = false, mixed $default = null) {
            $this->type = $type;
            $this->optional = $optional;
            $this->readonly = $readonly;
            $this->default = $default;
        }

        /**
         * Gets the default value of the struct field.
         * @return mixed The default value.
         */
        public function getDefault () : mixed {
            return $this->default;
        }

        /**
         * Gets the type of the struct field.
         * @return Node The type.
         */
        public function getType () : Node {
            return $this->type;
        }

        /**
         * Checks whether the struct field is optional.
         * @return bool Whether it is optional.
         */
        public function isOptional () : bool {
            return $this->optional;
        }

        /**
         * Checks whether the struct field is readonly.
         * @return bool Whether it is readonly.
         */
        public function isReadonly () : bool {
            return $this->readonly;
        }

        /**
         * Creates a new struct field specification with modified properties.
         * @param array{type?: Node, optional?: bool, readonly?: bool, default?: mixed} $options The properties to modify.
         * @return static The new struct field specification.
         */
        public function with (array $options) : static {
            $type = $options["type"] ?? $this->type;
            $optional = $options["optional"] ?? $this->optional;
            $readonly = $options["readonly"] ?? $this->readonly;
            $default = $options["default"] ?? $this->default;

            return new static($type, $optional, $readonly, $default);
        }

        /**
         * Creates a new struct field specification with the given optionality.
         * @param bool $optional Whether the struct field is optional.
         * @return static The new struct field specification.
         */
        public function withDefault (mixed $default) : static {
            return new static($this->type, $this->optional, $this->readonly, $default);
        }

        /**
         * Creates a new struct field specification with the given type.
         * @param Node $type The type of the struct field.
         * @return static The new struct field specification.
         */
        public function withType (Node $type) : static {
            return new static($type, $this->optional, $this->readonly, $this->default);
        }
    }
?>
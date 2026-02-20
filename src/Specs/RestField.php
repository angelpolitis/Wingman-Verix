<?php
    /*/
	 * Project Name:    Wingman — Verix — Rest Field Specification
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 22 2025
	 * Last Modified:   Dec 23 2025
    /*/

    # Use the Verix.Specs namespace.
    namespace Wingman\Verix\Specs;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;

    /**
     * Represents a rest field specification.
     * @package Wingman\Verix\Specs
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RestField {
        /**
         * The type of a rest field.
         * @var Node
         */
        public readonly Node $type;

        /**
         * Whether a rest field is optional.
         * @var bool
         */
        public readonly bool $optional;

        /**
         * Creates a new rest field specification.
         * @param Node $type The type of the rest field.
         * @param bool $optional Whether the rest field is optional.
         */
        public function __construct (Node $type, bool $optional = false) {
            $this->type = $type;
            $this->optional = $optional;
        }

        /**
         * Gets the type of a rest field.
         * @return Node The type.
         */
        public function getType () : Node {
            return $this->type;
        }

        /**
         * Checks whether a rest field is optional.
         * @return bool Whether the rest field is optional.
         */
        public function isOptional () : bool {
            return $this->optional;
        }

        /**
         * Creates a new rest field specification with the given type.
         * @param Node $type The type of the rest field.
         * @return static The new rest field specification.
         */
        public function withType (Node $type) : static {
            return new static($type, $this->optional);
        }
    }    
?>
<?php
    /*/
	 * Project Name:    Wingman — Verix — Primitive Registry
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Dec 23 2025
    /*/

    # Use the Verix.Registries namespace.
    namespace Wingman\Verix\Registries;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\PrimitiveDefinition;
    use Wingman\Verix\Types\Type;

    /**
     * Represents a registry for primitive type definitions.
     * @package Wingman\Verix\Registries
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PrimitiveRegistry {
        /**
         * The registered primitive type definitions.
         * @var array<string, PrimitiveDefinition>
         */
        protected array $definitions = [];

        /**
         * Retrieves a primitive type definition by name.
         * @param string $name The name of the primitive type.
         * @return PrimitiveDefinition The primitive type definition.
         * @throws RuntimeException If the primitive type is unknown.
         */
        public function get (string $name) : PrimitiveDefinition {
            if (!isset($this->definitions[$name])) {
                throw new RuntimeException("Unknown primitive type: {$name}");
            }
            return $this->definitions[$name];
        }

        /**
         * Checks if a primitive type definition exists by name.
         * @param string $name The name of the primitive type.
         * @return bool Whether the definition exists.
         */
        public function has (string $name) : bool {
            return isset($this->definitions[$name]);
        }

        /**
         * Registers a new primitive type definition.
         * @param PrimitiveDefinition $typeOrDefinition The primitive type definition to register.
         * @throws RuntimeException If the provided class does not exist or is not a subclass of Type.
         */
        public function register (PrimitiveDefinition|string $typeOrDefinition) : void {
            if (is_string($typeOrDefinition)) {
                if (!class_exists($typeOrDefinition)) {
                    throw new RuntimeException("Primitive type class not found: {$typeOrDefinition}");
                }
                if (!is_subclass_of($typeOrDefinition, Type::class)) {
                    throw new RuntimeException("Class {$typeOrDefinition} is not a subclass of Type.");
                }

                /** @var PrimitiveDefinition */
                $definition = $typeOrDefinition::getDefinition();
            }

            /** @var PrimitiveDefinition */
            else $definition = $typeOrDefinition;
            
            $this->definitions[$definition->getName()] = $definition;
        }
    }
?>
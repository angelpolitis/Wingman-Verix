<?php
    /**
     * Project Name:    Wingman Verix - Primitive Facade
     * Created by:      Angel Politis
     * Creation Date:   Dec 21 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Facades namespace.
    namespace Wingman\Verix\Facades;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Nodes\PrimitiveNode;
    use Wingman\Verix\PrimitiveDefinition;
    use Wingman\Verix\Registries\Registry;

    /**
     * A facade for registering primitive types to the global schema registry.
     * @package Wingman\Verix\Facades
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Primitive {
        /**
         * Retrieves a registry instance based on the provided argument.
         * @param Registry|string|null $registry The registry instance, name, or null for default.
         * @return Registry The resolved registry instance.
         */
        protected static function getRegistry (Registry|string|null $registry = null) : Registry {
            return $registry instanceof Registry ? $registry : Registry::get($registry);
        }

        /**
         * Creates an alias for an existing primitive type.
         * @param array|string $aliasOrAliases The alias name or an array of alias names.
         * @param string|null $primitive The existing primitive type name (required if a single alias is provided).
         * @throws RuntimeException If the primitive type is not provided when aliasing a single name or if the target is not a primitive type.
         */
        public static function alias (array|string $aliasOrAliases, ?string $primitive = null, Registry|string|null $registry = null) : void {
            if (is_string($aliasOrAliases)) {
                if ($primitive === null) {
                    throw new RuntimeException("Primitive type must be provided when aliasing a single name.");
                }

                $aliases = [$aliasOrAliases];
            }
            else $aliases = $aliasOrAliases;
    
            $registry = static::getRegistry($registry)->getPrimitiveRegistry();

            foreach ($aliases as $alias) {
                $schema = Schema::from($primitive);
    
                $node = $schema->getNode();
    
                if (!($node instanceof PrimitiveNode)) {
                    throw new RuntimeException("Cannot create an alias to a schema.");
                }
    
                $baseDef = $registry->get($node->getName());
    
                $derived = $baseDef->derive($alias, $node->getParams());
    
                $registry->register($derived);
            }
        }

        /**
         * Registers a new primitive type.
         * @param string $name The name of the primitive type.
         * @param callable $validator The validator function.
         * @param string $extends The parent type of the primitive type.
         * @param array $parameters The parameters of the primitive type.
         * @param callable|null $parser The parser function.
         * @param string|null $description The description of the primitive type.
         * @param callable|null $errorCallback The error callback function.
         * @param callable|null $positionalParamMapper The positional parameter mapper function.
         * @param Registry|string|null $registry The registry to use (optional).
         * @return PrimitiveDefinition The registered primitive type definition.
         */
        public static function register (
            string $name,
            callable $validator,
            ?string $extends = null,
            array $parameters = [],
            ?callable $parser = null,
            ?string $description = null,
            ?callable $errorCallback = null,
            ?callable $positionalParamMapper = null,
            Registry|string|null $registry = null
        ) : PrimitiveDefinition {
            $definition = new PrimitiveDefinition(
                $name,
                $extends ?? "any",
                $validator,
                $parameters,
                $parser,
                $description,
                $errorCallback,
                $positionalParamMapper
            );
            static::getRegistry($registry)->getPrimitiveRegistry()->register($definition);
            return $definition;
        }

        /**
         * Registers an existing class as a primitive type.
         * @param string $className The class name.
         * @param Registry|string|null $registry The registry to use (optional).
         */
        public static function registerClass (string $className, Registry|string|null $registry = null) : void {
            static::getRegistry($registry)->getPrimitiveRegistry()->register($className);
        }
    }
?>
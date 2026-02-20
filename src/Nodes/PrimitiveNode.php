<?php
    /*/
	 * Project Name:    Wingman — Verix — Primitive Node
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a primitive node.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PrimitiveNode implements Node {
        /**
         * The name of a primitive node.
         * @var string
         */
        public readonly string $name;

        /**
         * The parameters of a primitive node.
         * @var array<string, mixed>
         */
        public readonly array $params;

        /**
         * The primitive registry used to get the definition of a node.
         * @var PrimitiveRegistry
         */
        public readonly PrimitiveRegistry $registry;

        /**
         * Creates a new primitive node.
         * @param string $name The name of the primitive node.
         * @param array<string, mixed> $params The parameters of the primitive node.
         * @param PrimitiveRegistry $registry The primitive registry.
         */
        public function __construct (string $name, array $params, PrimitiveRegistry $registry) {
            $this->name = $name;
            $this->params = $params;
            $this->registry = $registry;
        }

        /**
         * Gets the debug information of a primitive node.
         * @return array The debug information of the primitive node.
         */
        public function __debugInfo () : array {
            return [
                "name" => $this->name,
                "params" => $this->params
            ];
        }

        /**
         * Converts a primitive node to a string.
         * @return string The string representation of the primitive node.
         */
        public function __toString () : string {
            return $this->serialise();
        }

        /**
         * Gets the name of a primitive node.
         * @return string The name of the primitive node.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the parameters of a primitive node.
         * @return array The parameters of the primitive node.
         */
        public function getParams () : array {
            return $this->params;
        }

        /**
         * Gets the primitive registry used to get the definition of a node.
         * @return PrimitiveRegistry The primitive registry.
         */
        public function getRegistry () : PrimitiveRegistry {
            return $this->registry;
        }

        /**
         * Serialises a primitive node to a string.
         * @return string The serialised primitive node.
         */
        public function serialise () : string {
            if (!$this->params) return $this->name;

            $parts = [];
            foreach ($this->params as $k => $v) {
                $parts[] = "{$k}=" . json_encode($v);
            }
            return $this->name . '<' . implode(',', $parts) . '>';
        }

        /**
         * Validates a value against a primitive node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path of the value.
         * @return ValidationResult The validation result.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            $definition = $this->registry->get($this->name);

            # (1) Fill in any missing parameters with their defaults.
            $params = [];
            foreach ($definition->getParams() as $paramName => $paramSpec) {
                # (a) Use the provided parameter value if it exists.
                if (array_key_exists($paramName, $this->params)) {
                    $params[$paramName] = $this->params[$paramName];
                    continue;
                }

                # (b) Otherwise, use the default value if it exists.
                if (($default = $paramSpec->getDefault()) !== null) {
                    $params[$paramName] = $default;
                }
            }
            
            # (2) Coerce the value if the definition has a parser and strict mode is off.
            $coerced = !$strict && ($definition->parser ?? null) ? $definition->parse($value, $params) : $value;
        
            $result = $definition->validate($coerced, $params)->withPath($path);
            
            return new ValidationResult($coerced, $result->isValid(), $result->getErrors(), $result->getMetadata());
        }
    }
?>
<?php
    /*/
	 * Project Name:    Wingman — Verix — Tuple Node
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a tuple of nodes.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TupleNode extends CompositeNode {
        /**
         * The items.
         * @var Node[]
         */
        public readonly array $items;
        
        /**
         * The rest item type for variadic tuples.
         * @var ?Node
         */
        public readonly ?Node $restType;
        
        /**
         * Creates a new tuple type.
         * @param Node[] $items The items.
         */
        public function __construct (array $items, ?Node $restType = null) {
            $this->items = $items;
            $this->restType = $restType;
        }

        /**
         * Gets the items of a tuple.
         * @return Node[] The items.
         */
        public function getItems () : array {
            return $this->items;
        }

        /**
         * Gets the type of the variadic items of a tuple.
         * @return ?Node The rest type; `null` if the tuple is not variadic.
         */
        public function getRestType () : ?Node {
            return $this->restType;
        }

        /**
         * Checks whether a tuple is variadic.
         * @return bool Whether the tuple is variadic.
         */
        public function isVariadic () : bool {
            return $this->restType !== null;
        }

        /**
         * Serialises a tuple node to a string.
         * @return string The serialised tuple node.
         */
        public function serialise () : string {
            $parts = array_map(fn ($types) => $types->serialise(), $this->items);
            return "array[" . implode(", ", $parts) . "]";
        }

        /**
         * Validates a value against a tuple node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!is_array($value)) {
                return ValidationResult::error("Expected tuple", $value)->withPath($path);
            }
        
            if (!$this->restType && count($value) !== count($this->items)) {
                return ValidationResult::error("Expected tuple of length " . count($this->items), $value)->withPath($path);
            }
        
            if ($this->restType && count($value) < count($this->items)) {
                return ValidationResult::error("Expected tuple with at least " . count($this->items) . " elements", $value)->withPath($path);
            }
        
            $normalised = [];
            $result = ValidationResult::ok($normalised);
        
            # Handle the fixed items of the tuple.
            foreach ($this->items as $i => $type) {
                $itemResult = $type->validate($value[$i], $strict, "{$path}[{$i}]");
                $normalised[$i] = $itemResult->getValue();
                $result = $result->merge($itemResult);
            }
        
            # Handle the variadic items of the tuple.
            if ($this->restType) {
                for ($i = count($this->items); $i < count($value); $i++) {
                    $itemResult = $this->restType->validate($value[$i], $strict, "{$path}[{$i}]");
                    $normalised[$i] = $itemResult->getValue();
                    $result = $result->merge($itemResult);
                }
            }
        
            return new ValidationResult($normalised, $result->isValid(), $result->getErrors(), $result->getMetadata());
        }
    }
?>
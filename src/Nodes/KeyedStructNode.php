<?php
    /*/
	 * Project Name:    Wingman — Verix — Keyed Struct Node
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Nodes namespace.
    namespace Wingman\Verix\Nodes;

    # Import the following classes to the current scope.
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\Specs\RestField;
    use Wingman\Verix\Specs\StructField;
    use Wingman\Verix\ValidationResult;

    /**
     * Represents a keyed struct node.
     * @package Wingman\Verix\Nodes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class KeyedStructNode extends StructNode {
        /**
         * The type of the dynamic keys of a struct.
         * @var ?Node
         */
        public readonly ?Node $keyType;

        /**
         * The type of the dynamic values of a struct.
         * @var ?Node
         */
        public readonly ?Node $valueType;

        /**
         * Whether the existence of a dynamic key is optional.
         * @var bool
         */
        public readonly bool $keyOptional;

        /**
         * Creates a new struct type.
         * @param array<string, StructField> $fields The fields of the struct.
         * @param bool $exact Whether the struct is exact (no extra fields allowed).
         * @param ?RestField $rest The rest field of the struct, if any.
         * @param ?Node $keyType The type of the dynamic keys of the struct.
         * @param ?Node $valueType The type of the dynamic values of the struct.
         * @param bool $keyOptional Whether the existence of a dynamic key is optional.
         */
        public function __construct (
            array $fields = [],
            bool $exact = true,
            ?RestField $rest = null,
            ?Node $keyType = null,
            ?Node $valueType = null,
            bool $keyOptional = false
        ) {
            parent::__construct($fields, $exact, $rest);
            $this->keyType = $keyType;
            $this->valueType = $valueType;
            $this->keyOptional = $keyOptional;
        }

        /**
         * Validates the dynamic key fields of a struct.
         * @param ValidationResult $result The current validation result.
         * @param mixed $value The original value.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The current validation path.
         * @return ValidationResult The updated validation result.
         */
        protected function validateDynamicKeyFields (ValidationResult $result, mixed $value, bool $strict, string $path) : ValidationResult {
            $dynamicKeysSeen = [];
            $normalised = $result->getValue();
            $usedKeys = $result->getMetadata()["usedKeys"] ?? [];

            if ($this->keyType && $this->valueType) {
                foreach ($normalised as $key => $val) {
                    # Skip keys that have already been used.
                    if (isset($usedKeys[$key])) continue;
        
                    $keyResult = $this->keyType->validate($key, $strict);
                    if ($keyResult->valid) {
                        $dynamicKeysSeen[$key] = true;
                        $keyPath = $path === "" ? "[{$key}]" : "{$path}[{$key}]";
        
                        $valResult = $this->valueType->validate($val, $strict, $keyPath);
                        $normalised[$key] = $valResult->value;
                        $result = $result->merge($valResult);
                    }
                }
        
                if (!$this->keyOptional && count($dynamicKeysSeen) === 0) {
                    $result = $result->merge(
                        ValidationResult::error(
                            "Expected at least one dynamic key matching keyType",
                            $value
                        )->withPath($path === "" ? "*" : "{$path}[*]")
                    );
                }
            }

            return $result->with([
                "value" => $normalised,
                "metadata" => compact("dynamicKeysSeen")
            ]);
        }

        /**
         * Validates the variadic fields of a struct.
         * @param ValidationResult $result The current validation result.
         * @param bool $strict Whether to use strict validation.
         * @param string $path The current validation path.
         * @return ValidationResult The updated validation result.
         */
        protected function validateVariadicFields (ValidationResult $result, bool $strict, string $path) : ValidationResult {
            if (is_null($this->rest)) return $result;

            # Skip validation if the are no rest fields or they can have any type.
            if (is_null($this->rest) || $this->rest->getType() instanceof AnyNode) {
                return $result;
            }

            $normalised = $result->getValue();
            $usedKeys = $result->getMetadata()["usedKeys"] ?? [];
            $dynamicKeysSeen = $result->getMetadata()["dynamicKeysSeen"] ?? [];
            $variadicKeys = [];

            foreach ($normalised as $key => $val) {
                # Skip keys that have already been used.
                if (isset($usedKeys[$key]) || isset($dynamicKeysSeen[$key])) continue;
    
                $restPath = $path === "" ? $key : "{$path}.{$key}";
                $restResult = $this->rest->getType()->validate($val, $strict, $restPath);
                $normalised[$key] = $restResult->getValue();
                $variadicKeys[$key] = true;
                $result = $result->merge($restResult);
            }

            return $result->with([
                "value" => $normalised,
                "metadata" => compact("variadicKeys")
            ]);
        }

        /**
         * Gets the type of the dynamic keys of a struct.
         * @return ?Node The type of the dynamic keys, or `null` if none is set.
         */
        public function getKeyType () : ?Node {
            return $this->keyType;
        }

        /**
         * Gets the type of the dynamic values of a struct.
         * @return ?Node The type of the dynamic values, or `null` if none is set.
         */
        public function getValueType () : ?Node {
            return $this->valueType;
        }

        /**
         * Checks whether the existence of a dynamic key is optional.
         * @return bool Whether the dynamic key is optional.
         */
        public function isKeyOptional () : bool {
            return $this->keyOptional;
        }

        /**
         * Serialises a keyed struct node to a string.
         * @return string The serialised keyed struct node.
         */
        public function serialise () : string {
            $parts = [];
        
            # Serialise the fixed fields of the struct.
            foreach ($this->fields as $name => $field) {
                $part = $name;
                if ($field->optional) $part .= '?';
                $part .= ": " . $field->type->serialise();
                $parts[] = $part;
            }
        
            # Serialise the dynamic-key fields of the struct.
            if ($this->keyType && $this->valueType) {
                $keyStr = $this->keyType->serialise();
                if ($this->keyOptional) $keyStr .= "?";
                $parts[] = "[key: {$keyStr}]: {$this->valueType->serialise()}";
            }

            $restType = $this->rest?->getType();
        
            # Serialise the rest fields of the struct.
            if ($restType) {
                $part = "...";

                if (!($restType instanceof AnyNode)) $part .= ": " . $restType->serialise();

                $parts[] = $part;
            }
        
            return "struct{" . implode(", ", $parts) . "}";
        }

        /**
         * Validates a value against a keyed struct node.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no parsing).
         * @param string $path The path to the value being validated (used for error reporting).
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, bool $strict = false, string $path = "") : ValidationResult {
            if (!is_array($value)) {
                return ValidationResult::error("Expected struct", $value)->withPath($path);
            }
        
            $normalised = $value;
            $result = ValidationResult::ok($normalised);

            # (1) Validate the fixed fields of the struct.
            $result = $this->validateFixedFields($result, $strict, $path);
            $normalised = $result->getValue();
            $usedKeys = $result->getMetadata()["usedKeys"] ?? [];
        
            # (2) Validate the dynamic keys of the struct.
            $result = $this->validateDynamicKeyFields($result, $value, $strict, $path);
            $normalised = $result->getValue();
            $dynamicKeysSeen = $result->getMetadata()["dynamicKeysSeen"] ?? [];
        
            # (3) Validate the variadic fields of the struct.
            $result = $this->validateVariadicFields($result, $strict, $path);
            $normalised = $result->getValue();

            # (4) Enforce exactness for unexpected keys.
            if ($this->exact) {
                foreach ($normalised as $key => $val) {
                    # Skip keys that have already been used.
                    if (isset($usedKeys[$key])) continue;
                    if (isset($dynamicKeysSeen[$key]) && $this->keyType) continue;
        
                    $errPath = $path === "" ? $key : "{$path}.{$key}";
                    $result = $result->merge(ValidationResult::error("Unexpected field '{$key}'", $val)->withPath($errPath));
                }
            }
        
            return $result->withValue($normalised);
        }
    }
?>
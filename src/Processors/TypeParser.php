<?php
    /**
     * Project Name:    Wingman Verix - Type Parser
     * Created by:      Angel Politis
     * Creation Date:   Dec 21 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Processors namespace.
    namespace Wingman\Verix\Processors;

    # Import the following classes to the current scope.
    use Wingman\Verix\Exceptions\ParsingException;
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\Nodes\AnyNode;
    use Wingman\Verix\Nodes\ArrayNode;
    use Wingman\Verix\Nodes\ClassNode;
    use Wingman\Verix\Nodes\EnumNode;
    use Wingman\Verix\Nodes\IntersectionNode;
    use Wingman\Verix\Nodes\KeyedStructNode;
    use Wingman\Verix\Nodes\LiteralNode;
    use Wingman\Verix\Nodes\NullNode;
    use Wingman\Verix\Nodes\PrimitiveNode;
    use Wingman\Verix\Nodes\SchemaRefNode;
    use Wingman\Verix\Nodes\StructNode;
    use Wingman\Verix\Nodes\TupleNode;
    use Wingman\Verix\Nodes\UnionNode;
    use Wingman\Verix\Registries\ClassRegistry;
    use Wingman\Verix\Registries\PrimitiveRegistry;
    use Wingman\Verix\Registries\SchemaRegistry;
    use Wingman\Verix\Specs\RestField;
    use Wingman\Verix\Specs\StructField;

    /**
     * Parses type definitions into type objects.
     * @package Wingman\Verix\Processors
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypeParser {
        /**
         * The default symbol.
         * @var string
         */
        public const ASSIGNMENT_SYMBOL = '=';

        /**
         * The delimiter symbol.
         * @var string
         */
        public const DELIMITER_SYMBOL = ',';

        /**
         * The intersection symbol.
         * @var string
         */
        public const INTERSECTION_SYMBOL = '&';

        /**
         * The optional symbol.
         * @var string
         */
        public const OPTIONAL_SYMBOL = '?';

        /**
         * The readonly symbol.
         * @var string
         */
        public const READONLY_SYMBOL = '!';

        /**
         * The rest symbol.
         * @var string
         */
        public const REST_SYMBOL = "...";

        /**
         * The schema symbol.
         * @var string
         */
        public const SCHEMA_SYMBOL = '@';

        /**
         * The type symbol.
         * @var string
         */
        public const TYPE_SYMBOL = ':';

        /**
         * The union symbol.
         * @var string
         */
        public const UNION_SYMBOL = '|';

        /**
         * The class registry of a parser.
         * @var ClassRegistry
         */
        protected ClassRegistry $classRegistry;

        /**
         * The primitive registry of a parser.
         * @var PrimitiveRegistry
         */
        protected PrimitiveRegistry $primitiveRegistry;

        /**
         * The schema registry of a parser.
         * @var SchemaRegistry
         */
        protected SchemaRegistry $schemaRegistry;

        /**
         * The type tokeniser of a parser.
         * @var TypeTokeniser
         */
        protected TypeTokeniser $tokeniser;

        /**
         * Creates a new type parser.
         * @param TypeTokeniser $tokeniser The type tokeniser.
         * @param PrimitiveRegistry $primitiveRegistry The primitive registry.
         * @param SchemaRegistry $schemaRegistry The schema registry.
         * @param ClassRegistry $classRegistry The class registry.
         */
        public function __construct (TypeTokeniser $tokeniser, PrimitiveRegistry $primitiveRegistry, SchemaRegistry $schemaRegistry, ClassRegistry $classRegistry) {
            $this->tokeniser = $tokeniser;
            $this->primitiveRegistry = $primitiveRegistry;
            $this->schemaRegistry = $schemaRegistry;
            $this->classRegistry = $classRegistry;
        }

        /**
         * Parses array suffixes (e.g., `type[]`).
         * @param Node $type The base type.
         * @return Node The array type node.
         */
        protected function parseArraySuffix (Node $type) : Node {
            while ($this->tokeniser->peek() === '[') {
                $this->tokeniser->next();
                $this->tokeniser->expect(']');
                $type = new ArrayNode($type);
            }
            return $type;
        }

        /**
         * Parses a class or primitive type.
         * @return Node The parsed node.
         * @throws ParsingException If the type or class is unknown.
         */
        protected function parseClassOrPrimitive () : Node {
            $identifier = $this->tokeniser->next();

            # (1) Check if it's a registered primitive.
            if ($this->primitiveRegistry->has($identifier)) {
                return $this->parsePrimitiveByName($identifier);
            }
            
            # (2) Check if it's a registered class alias.
            if ($className = $this->classRegistry->get($identifier)) {
                return new ClassNode($className);
            }

            # (3) Fallback: Assume it's a fully qualified class name.
            $fqcn = str_replace('.', '\\', $identifier);
            if (class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn)) {
                return new ClassNode($fqcn);
            }

            throw new ParsingException("Unknown type or class: {$identifier}");
        }

        /**
         * Parses an enumeration node.
         * @return EnumNode The parsed enumeration node.
         */
        protected function parseEnum () : EnumNode {
            # Consume the keyword.
            $this->tokeniser->next();

            $this->tokeniser->expect('{');

            $values = [];
        
            while ($this->tokeniser->peek() !== '}') {
                $value = $this->tokeniser->next();
        
                # Skip separator tokens.
                if ($value === static::DELIMITER_SYMBOL || $value === static::UNION_SYMBOL) continue;

                $values[] = $this->parseParamValue($value);
            }
        
            $this->tokeniser->expect('}');

            return new EnumNode($values, [], $this->primitiveRegistry);
        }

        /**
         * Parses an intersection node.
         * @return Node The parsed node.
         */
        protected function parseIntersection () : Node {
            $types = [$this->parsePrimary()];
        
            while ($this->tokeniser->peek() === static::INTERSECTION_SYMBOL) {
                $this->tokeniser->next();
                $types[] = $this->parsePrimary();
            }
        
            if (count($types) === 1) return $types[0];

            return new IntersectionNode($types);
        }

        /**
         * Parses a literal node.
         * @return Node|null The parsed literal node, or null if no literal was found.
         */
        protected function parseLiteral () : ?Node {
            $tok = $this->tokeniser->peek();
        
            # (1) Handle boolean and null literals.
            if ($tok === "true" || $tok === "false" || $tok === "null") {
                $this->tokeniser->next();
                return new LiteralNode(match ($tok) {
                    "true"  => true,
                    "false" => false,
                    "null"  => null
                }, $this->primitiveRegistry);
            }
            
            # (2) Handle string literals.
            if (is_string($tok) && (str_starts_with($tok, "'") || str_starts_with($tok, '"'))) {
                $this->tokeniser->next();
                return new LiteralNode(trim($tok, "'\""), $this->primitiveRegistry);
            }
        
            # Handle numeric literals.
            if (is_numeric($tok)) {
                $this->tokeniser->next();
                return new LiteralNode(str_contains($tok, '.') ? (float) $tok : (int) $tok, $this->primitiveRegistry);
            }
        
            return null;
        }
        

        /**
         * Parses an optional type.
         * @param Node $type The node itself or a union containing it alonside `null`.
         */
        protected function parseOptional (Node $type): Node {
            if ($this->tokeniser->peek() === static::OPTIONAL_SYMBOL) {
                $this->tokeniser->next();
                return new UnionNode([$type, new NullNode($this->primitiveRegistry)]);
            }

            return $type;
        }

        /**
         * Parses a parameter value from its string representation.
         * @param string $value The string representation of the parameter value.
         * @return mixed The parsed parameter value.
         */
        protected function parseParamValue (string $value) : mixed {
            # (1) Type coercion: boolean.
            if ($value === "true") return true;
            if ($value === "false") return false;

            # (2) Type coercion: number, or string.
            if (is_numeric($value)) {
                return strpos($value, '.') !== false ? (float) $value : (int) $value;
            }

            # (3) Strip quotes if present; strings have them.
            if (str_starts_with($value, '"') || str_starts_with($value, "'")) {
                return trim($value, '"\'');
            }

            return $value;
        }

        /**
         * Parses a primary node.
         * @return Node The parsed node.
         */
        protected function parsePrimary () : Node {
            $token = $this->tokeniser->peek();
        
            if ($token === '(') {
                $this->tokeniser->next();
                $type = $this->parseUnion();
                $this->tokeniser->expect(')');
            }
            elseif ($token === "struct" || $token === '{') {
                $type = $this->parseStruct($token === '{');
            }
            elseif ($token === "Schema" || ($usingSymbol = $token === static::SCHEMA_SYMBOL)) {
                $type = $this->parseSchemaRef($usingSymbol ?? false);
            }
            elseif ($token === "enum") {
                $type = $this->parseEnum();
            }
            elseif ($token === '[') {
                $type = $this->parseTupleOrGroupedType();
            }
            elseif ($literal = $this->parseLiteral()) {
                $type = $literal;
            }
            else $type = $this->parseClassOrPrimitive();
        
            $type = $this->parseOptional($type);
            
            $type = $this->parseArraySuffix($type);
        
            return $type;
        }
        
        /**
         * Parses a primitive node by its name.
         * @param string $name The name of the primitive.
         * @return PrimitiveNode The parsed primitive node.
         */
        protected function parsePrimitiveByName (string $name) : PrimitiveNode {
            $params = [];
            $primitive = $this->primitiveRegistry->get($name);
        
            # (1) Named parameters.
            if ($this->tokeniser->peek() === '<') {
                $this->tokeniser->next();

                if (sizeof($primitive->getParams()) === 0) {
                    throw new ParsingException("Primitive '{$name}' does not accept parameters.");
                }

                while (true) {
                    $key = $this->tokeniser->next();

                    # (a) Explicit assignment.
                    if ($this->tokeniser->peek() === static::ASSIGNMENT_SYMBOL) {
                        $this->tokeniser->next();
                        $value = $this->tokeniser->next();
                        $params[$key] = $this->parseParamValue($value);
                    }

                    # (b) Implicit boolean assignment.
                    else $params[$key] = true;

                    if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) {
                        $this->tokeniser->next();
                        continue;
                    }
                    break;
                }

                $this->tokeniser->expect('>');
            }
        
            # (2) Positional parameters.
            elseif ($this->tokeniser->peek() === '{') {
                $this->tokeniser->next();

                if (sizeof($primitive->getParams()) === 0) {
                    throw new ParsingException("Primitive '{$name}' does not accept parameters.");
                }
            
                # (a) Collect the positional values.
                $values = [];
                while (true) {
                    $token = $this->tokeniser->next();
                    $values[] = $this->parseParamValue($token);
            
                    if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) {
                        $this->tokeniser->next();
                        continue;
                    }
                    break;
                }
            
                $this->tokeniser->expect('}');
            
                # (b) Delegate positional parameter interpretation to the primitive.
                $positionalParamMapper = $primitive->getPositionalParamMapper();
                if (!$positionalParamMapper) {
                    throw new ParsingException("Primitive '{$name}' does not support positional parameters.");
                }
            
                $mapped = $positionalParamMapper($values);
            
                if (!is_array($mapped)) {
                    throw new ParsingException("Invalid positional parameter mapping for '{$name}'.");
                }
            
                foreach ($mapped as $key => $value) {
                    $params[$key] = $value;
                }
            }

            # (3) Check for unknown parameters.
            foreach ($params as $paramName => $paramValue) {
                if (!$primitive->hasParam($paramName)) {
                    throw new ParsingException("Unknown parameter '{$paramName}' for primitive type '{$name}'.");
                }
            }

            return new PrimitiveNode($name, $params, $this->primitiveRegistry);
        }

        /**
         * Parses a schema reference node.
         * @param bool $usingSymbol Whether the schema symbol is used instead of the keyword syntax.
         * @return SchemaRefNode The parsed schema reference node.
         */
        protected function parseSchemaRef (bool $usingSymbol = false) : SchemaRefNode {
            # Consume the keyword/symbol.
            $this->tokeniser->next();

            if ($usingSymbol) {
                $schemaName = $this->tokeniser->next();
                return new SchemaRefNode($schemaName, $this->schemaRegistry);
            }
            
            $this->tokeniser->expect('(');
            $schemaName = $this->tokeniser->next();
            $this->tokeniser->expect(')');

            return new SchemaRefNode($schemaName, $this->schemaRegistry);
        }

        /**
         * Parses a struct node.
         * @param bool $implicit Whether the 'struct' keyword is implicit.
         * @return StructNode The parsed struct node.
         */
        protected function parseStruct (bool $implicit = false) : StructNode {
            if (!$implicit) $this->tokeniser->next();

            $this->tokeniser->expect('{');
        
            $fixedFields = [];
            $keyType = null;
            $valueType = null;
            $keyOptional = false;
            $keyReadonly = false;
            $restType = null;
            $exact = true;
        
            while (($token = $this->tokeniser->peek()) !== '}') {
                # (1) Parse the variadic fields.
                if ($token === static::REST_SYMBOL) {
                    $this->tokeniser->next();
                
                    if ($this->tokeniser->peek() === static::TYPE_SYMBOL) {
                        $this->tokeniser->next();
                        $restType = $this->parseUnion();
                    }

                    # Consider the variadic fields of 'any' type.
                    else {
                        $restType = new AnyNode($this->primitiveRegistry);
                        $exact = false;
                    }
                
                    if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) $this->tokeniser->next();
                    continue;
                }
        
                # (2) Parse the dynamic keys.
                if ($token === '[') {
                    # Consume the '[' and the optional label.
                    $this->tokeniser->next();
                    $this->tokeniser->next();

                    $keyOptional = false;
                    $keyReadonly = false;

                    # (a) Check for optional key (e.g. [key?: type]).
                    if ($this->tokeniser->peek() === static::OPTIONAL_SYMBOL) {
                        $this->tokeniser->next();
                        $keyOptional = true;
                    }

                    # (b) Check for readonly key (e.g. [key!: type]).
                    if ($this->tokeniser->peek() === static::READONLY_SYMBOL) {
                        $this->tokeniser->next();
                        $keyReadonly = true;
                    }
                    
                    # Dynamic keys require a type.
                    $this->tokeniser->expect(static::TYPE_SYMBOL);

                    $keyType = $this->parseUnion();

                    $this->tokeniser->expect(']');

                    # (c) Check for optional key (e.g. [key: type]?).
                    if ($this->tokeniser->peek() === static::OPTIONAL_SYMBOL) {
                        $this->tokeniser->next();
                        $keyOptional = true;
                    }

                    # (d) Check for readonly key (e.g. [key: type]!).
                    if ($this->tokeniser->peek() === static::READONLY_SYMBOL) {
                        $this->tokeniser->next();
                        $keyReadonly = true;
                    }

                    # A value type must follow.
                    $this->tokeniser->expect(static::TYPE_SYMBOL);

                    $valueType = $this->parseUnion();

                    if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) $this->tokeniser->next();

                    continue;
                }
        
                # (3) Parse the fixed fields.
                $fieldName = $this->tokeniser->next();
                $optional = false;
                $readonly = false;
                $default = null;
        
                # (3.1) Check for optional, readonly, and default value.
                if ($this->tokeniser->peek() === static::OPTIONAL_SYMBOL) {
                    $this->tokeniser->next();
                    $optional = true;
                }
        
                if ($this->tokeniser->peek() === static::READONLY_SYMBOL) {
                    $this->tokeniser->next();
                    $readonly = true;
                }
        
                if ($this->tokeniser->peek() === static::ASSIGNMENT_SYMBOL) {
                    $this->tokeniser->next();
                    $default = $this->parseParamValue($this->tokeniser->next());
                }
        
                # (3.2) Expect that a type follows the key.
                $this->tokeniser->expect(static::TYPE_SYMBOL);

                $type = $this->parseUnion();

                # (4) Create a struct field.
                $fixedFields[$fieldName] = new StructField(
                    type: $type,
                    optional: $optional,
                    readonly: $readonly,
                    default: $default
                );
        
                if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) $this->tokeniser->next();
            }
        
            $this->tokeniser->expect('}');

            # (5) Create the rest field.
            $rest = null;
            $rest = $restType ? new RestField($restType, /* Rest is always optional */true) : null;
        
            # (6) Decide which struct class to instantiate.
            if ($keyType || $valueType) {
                return new KeyedStructNode(
                    fields: $fixedFields,
                    exact: $exact,
                    rest: $rest,
                    keyType: $keyType,
                    valueType: $valueType,
                    keyOptional: $keyOptional,
                    keyReadonly: $keyReadonly
                );
            }
            return new StructNode(
                fields: $fixedFields,
                exact: $exact,
                rest: $rest
            );
        }

        /**
         * Parses a tuple or grouped type.
         * @return Node The parsed node.
         */
        protected function parseTupleOrGroupedType () : Node {
            $this->tokeniser->expect('[');

            # (1) Check for empty array type: []
            if ($this->tokeniser->peek() === ']') {
                $this->tokeniser->next();
                return new ArrayNode(new AnyNode($this->primitiveRegistry));
            }
        
            $items = [];
            $items[] = $this->parseUnion();
        
            # (2) If we see a delimiter, this is a tuple.
            if ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) {
                while ($this->tokeniser->peek() === static::DELIMITER_SYMBOL) {
                    $this->tokeniser->next();

                    # (a) Check for rest element: ...T
                    if ($this->tokeniser->peek() === static::REST_SYMBOL) {
                        $this->tokeniser->next();
                        $rest = $this->parseUnion();
                        $this->tokeniser->expect(']');
                        return new TupleNode($items, $rest);
                    }

                    # (b) Regular tuple element.
                    $items[] = $this->parseUnion();
                }
        
                $this->tokeniser->expect(']');
        
                return new TupleNode($items);
            }
        
            # (3) Otherwise it's just a grouped single type: [T]
            $this->tokeniser->expect(']');
        
            return $items[0];
        }

        /**
         * Parses a union node.
         * @return Node The parsed node.
         */
        protected function parseUnion () : Node {
            $types = [$this->parseIntersection()];
            while ($this->tokeniser->peek() === static::UNION_SYMBOL) {
                $this->tokeniser->next();
                $types[] = $this->parseIntersection();
            }

            if (count($types) === 1) return $types[0];

            return new UnionNode($types);
        }

        /**
         * Begins the parsing process of the tokens.
         * @return Node The parsed type object.
         */
        public function parse () : Node {
            # By default, assume the root node is a union.
            return $this->parseUnion();
        }
    }
?>
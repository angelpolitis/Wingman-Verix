<?php
    /*/
	 * Project Name:    Wingman — Verix — Schema Facade
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix.Facades namespace.
    namespace Wingman\Verix\Facades;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Wingman\Verix\Interfaces\Node;
    use Wingman\Verix\Nodes\ArrayNode;
    use Wingman\Verix\Nodes\IntersectionNode;
    use Wingman\Verix\Nodes\KeyedStructNode;
    use Wingman\Verix\Nodes\SchemaRefNode;
    use Wingman\Verix\Nodes\StructNode;
    use Wingman\Verix\Nodes\TupleNode;
    use Wingman\Verix\Nodes\UnionNode;
    use Wingman\Verix\Processors\TypeNormaliser;
    use Wingman\Verix\Processors\TypeParser;
    use Wingman\Verix\Processors\TypeTokeniser;
    use Wingman\Verix\Registries\Registry;
    use Wingman\Verix\Specs\RestField;
    use Wingman\Verix\Specs\StructField;
    use Wingman\Verix\ValidationResult;

    /**
     * A facade for working with schemas in the global schema registry.
     * @package Wingman\Verix\Facades
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Schema {
        /**
         * The expression used to create a schema.
         * @var string
         */
        protected string $expression;

        /**
         * The root node of a schema.
         * @var Node
         */
        protected Node $node;

        /**
         * The registry associated with the schema.
         * @var Registry
         */
        protected Registry $registry;

        /**
         * Creates a new schema.
         * @param string $expression The DSL expression of the schema.
         * @param Registry|string|null $registry The registry to use for resolving references (optional).
         */
        public function __construct (string $expression, Registry|string|null $registry = null) {
            $this->expression = $expression;
            $this->registry = static::getRegistry($registry);
            $this->node = static::parse($expression, $this->registry);
        }

        /**
         * Converts a schema to a string.
         * @return string The string representation of the schema.
         */
        public function __toString () : string {
            return $this->serialise();
        }

        /**
         * Infers an array type from an array value.
         * @param array $value The array value to infer the type from.
         * @return string The inferred array type.
         */
        protected static function inferArrayType (array $value) : string {
            if ($value === []) return "any[]";
        
            $isList = array_keys($value) === range(0, count($value) - 1);
        
            if ($isList) {
                $types = array_map([self::class, "inferTypeFromValue"], $value);
                $types = array_unique($types);
                return count($types) === 1
                    ? "{$types[0]}[]"
                    : "(" . implode('|', $types) . ")[]";
            }
        
            $fields = [];
            foreach ($value as $k => $v) {
                $fields[] = "{$k}: " . self::inferTypeFromValue($v);
            }
            return '{' . implode(", ", $fields) . '}';
        }

        /**
         * Infers a type from a value.
         * @param mixed $value The value to infer the type from.
         * @return string The inferred type.
         */
        protected static function inferTypeFromValue (mixed $value) : string {
            return match (true) {
                is_null($value) => 'null',
                is_int($value) => 'int',
                is_float($value) => 'float',
                is_bool($value) => 'bool',
                is_string($value) => 'string',
                is_array($value) => self::inferArrayType($value),
                is_object($value) => 'object',
                default => 'any'
            };
        }

        /**
         * Merges multiple field types into a single type.
         * @param array $types The field types to merge.
         * @return string The merged field type.
         */
        protected static function mergeFieldTypes (array $types) : string {
            $types = array_unique($types);
            return count($types) === 1 ? $types[0] : implode('|', $types);
        }

        /**
         * Retrieves a registry instance based on the provided argument.
         * @param Registry|string|null $registry The registry instance, name, or null for default.
         * @return Registry The resolved registry instance.
         */
        protected static function getRegistry (Registry|string|null $registry = null) : Registry {
            return $registry instanceof Registry ? $registry : Registry::get($registry);
        }

        /**
         * Expands a schema by resolving all schema references.
         * @param Node $type The schema to expand.
         * @param Registry|string|null $registry The registry to use (optional).
         * @return Node The expanded schema.
         * @throws RuntimeException If a schema reference cannot be resolved.
         */
        public static function expand (Node $type, Registry|string|null $registry = null) : Node {
            if ($type instanceof SchemaRefNode) {
                $name = $type->getName();
                $resolved = static::getRegistry($registry)->getSchemaRegistry()->get($name);
                if (!$resolved) {
                    throw new RuntimeException("Cannot expand unknown schema: {$name}");
                }
                return static::expand($resolved, $registry);
            }

            if ($type instanceof KeyedStructNode) {
                $fixedFields = [];
                foreach ($type->fields as $name => $field) {
                    $fixedFields[$name] = new StructField(
                        static::expand($field->getType(), $registry),
                        $field->isOptional(),
                        $field->isReadonly(),
                        $field->getDefault()
                    );
                }

                return new KeyedStructNode(
                    $fixedFields,
                    $type->isExact(),
                    $type->getRest()
                        ? new RestField(static::expand($type->getRest()->getType(), $registry), $type->getRest()->isOptional())
                        : null,
                    $type->getKeyType() ? static::expand($type->getKeyType(), $registry) : null,
                    $type->getValueType() ? static::expand($type->getValueType(), $registry) : null,
                    $type->isKeyOptional()
                );
            }

            if ($type instanceof StructNode) {
                $fields = [];
                foreach ($type->fields as $name => $field) {
                    $fields[$name] = new StructField(
                        static::expand($field->getType(), $registry),
                        $field->isOptional(),
                        $field->isReadonly(),
                        $field->getDefault()
                    );
                }
                return new StructNode($fields, $type->isExact(), $type->getRest());
            }

            if ($type instanceof ArrayNode) {
                return new ArrayNode(static::expand($type->getItemType(), $registry));
            }

            if ($type instanceof TupleNode) {
                $items = array_map(fn ($type) => static::expand($type, $registry), $type->getItems());
                $restType = $type->getRestType() ? static::expand($type->getRestType(), $registry) : null;
                return new TupleNode($items, $restType);
            }

            if ($type instanceof UnionNode) {
                return new UnionNode(array_map(fn ($type) => static::expand($type, $registry), $type->getTypes()));
            }

            if ($type instanceof IntersectionNode) {
                return new IntersectionNode(array_map(fn ($type) => static::expand($type, $registry), $type->getTypes()));
            }

            return $type;
        }

        /**
         * Creates a schema from a DSL string.
         * @param string $expression The DSL expression of the schema.
         * @param Registry|string|null $registry The registry to use for resolving references (optional).
         * @return static The schema.
         */
        public static function from (string $expression, Registry|string|null $registry = null) : static {
            return new static($expression, $registry);
        }

        /**
         * Gets the DSL expression of a schema.
         * @return string The DSL expression.
         */
        public function getExpression () : string {
            return $this->expression;
        }

        /**
         * Gets the root node of a schema.
         * @return Node The root node.
         */
        public function getNode () : Node {
            return $this->node;
        }

        /**
         * Infers a schema from a set of data.
         * @param iterable $data The data to infer the schema from.
         * @return Schema The inferred schema.
         */
        public static function infer (iterable $data) : Schema {
            if ($data === []) {
                return Schema::from("{}");
            }
        
            $fieldTypes = [];
            $fieldPresence = [];
        
            foreach ($data as $row) {
                foreach ($row as $field => $value) {
                    $fieldTypes[$field][] = self::inferTypeFromValue($value);
                    $fieldPresence[$field] = ($fieldPresence[$field] ?? 0) + 1;
                }
            }
        
            $rowCount = count($data);
            $parts = [];
        
            foreach ($fieldTypes as $field => $types) {
                $type = self::mergeFieldTypes($types);
                $optional = $fieldPresence[$field] < $rowCount;
                $parts[] = $optional
                    ? "{$field}?: {$type}"
                    : "{$field}: {$type}";
            }
        
            return Schema::from('{ ' . implode(', ', $parts) . ' }');
        }

        /**
         * Merges two schemata into one.
         * @param Schema|null $a The first schema.
         * @param Schema|null $b The second schema.
         * @return Schema|null The merged schema or `null` if both schemas are `null`.
         * @throws RuntimeException If the schemata belong to different registries.
         */
        public static function merge (?self $a, ?self $b) : ?static {
            if (!$a && !$b) return null;
            if (!$a) return $b;
            if (!$b) return $a;

            if ($a->registry !== $b->registry) {
                throw new RuntimeException("Cannot merge schemata from different registries.");
            }
        
            $intersection = new IntersectionNode([
                $a->getNode(),
                $b->getNode()
            ]);
        
            $normaliser = new TypeNormaliser($a->registry->getSchemaRegistry());
            $normalised = $normaliser->normalise($intersection);
        
            return new Schema($normalised, $a->registry);
        }

        /**
         * Parses a DSL expression into a node.
         * @param string $expression The DSL expression.
         * @param Registry|string|null $registry The registry to use for resolving references (optional).
         * @return Node The parsed node.
         */
        public static function parse (string $expression, Registry|string|null $registry = null) : Node {
            $registry = static::getRegistry($registry);
            $tokeniser = new TypeTokeniser($expression);
            $parser = new TypeParser(
                $tokeniser,
                $registry->getPrimitiveRegistry(),
                $registry->getSchemaRegistry(),
                $registry->getClassRegistry()
            );

            $node = $parser->parse();

            return (new TypeNormaliser($registry->getSchemaRegistry()))->normalise($node);
        }

        /**
         * Registers a schema in the global schema registry.
         * @param string $name The name of the schema.
         * @param string $expression The DSL expression of the schema.
         * @param Registry|string|null $registry The registry to register the schema in (optional).
         * @return static The registered schema.
         */
        public static function register (string $name, string $expression, Registry|string|null $registry = null) : static {
            $registry = static::getRegistry($registry);
            $schema = static::from($expression, $registry);
            $registry->getSchemaRegistry()->register($name, $schema->node);
            return $schema;
        }

        /**
         * Serialises a schema to a string.
         * @return string The serialised schema.
         */
        public function serialise () : string {
            return $this->node->serialise();
        }

        /**
         * Validates a value against a schema.
         * @param mixed $value The value to validate.
         * @param bool $strict Whether to use strict validation (no type coercion).
         * @return ValidationResult The validation result.
         */
        public function validate (mixed $value, bool $strict = false) : ValidationResult {
            return $this->node->validate($value, $strict);
        }
    }
?>
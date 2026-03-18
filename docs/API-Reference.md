# Wingman Verix — API Reference

Complete public method reference for every class and facade in Verix.

---

## Facades

### `Schema` — `Wingman\Verix\Facades\Schema`

| Method | Returns | Description |
| --- | --- | --- |
| `Schema::from(string $expression, Registry\|string\|null $registry = null)` | `Schema` | Parses and normalises the DSL expression. Returns a new instance. |
| `Schema::parse(string $expression, Registry\|string\|null $registry = null)` | `Node` | Parses the expression and returns the raw normalised node. |
| `Schema::register(string $name, string $expression, Registry\|string\|null $registry = null)` | `Schema` | Parses, stores in registry, and returns the schema. |
| `Schema::expand(Node $node, Registry\|string\|null $registry = null)` | `Node` | Resolves all `SchemaRefNode` instances in the tree. |
| `Schema::infer(array $data)` | `Schema` | Infers the most specific schema from an array of sample values. |
| `Schema::merge(?Schema $a, ?Schema $b)` | `?Schema` | Merges two struct schemas into one; fields missing from either are made optional. |
| `$schema->validate(mixed $value, bool $strict = false)` | `ValidationResult` | Validates `$value` and returns the result. |
| `$schema->getNode()` | `Node` | Returns the root node of the parsed AST. |
| `$schema->getExpression()` | `string` | Returns the original DSL expression string. |
| `$schema->serialise()` | `string` | Returns a canonical DSL string produced by the root node. |

---

### `Primitive` — `Wingman\Verix\Facades\Primitive`

| Method | Returns | Description |
| --- | --- | --- |
| `Primitive::register(string $name, callable $validator, ?string $extends, array $parameters, ?callable $parser, ?string $description, ?callable $errorCallback, ?callable $positionalParamMapper, Registry\|string\|null $registry)` | `PrimitiveDefinition` | Registers a new primitive type and returns its definition. |
| `Primitive::registerClass(string $className, Registry\|string\|null $registry = null)` | `void` | Registers an existing `Type` subclass into the primitive registry. |
| `Primitive::alias(array\|string $aliasOrAliases, ?string $primitive = null, Registry\|string\|null $registry = null)` | `void` | Creates one or more aliases for an existing primitive type. |

---

## Schema — `Wingman\Verix\Schema`

*(Instance methods only — static methods are listed under the facade above.)*

| Method | Returns | Description |
| --- | --- | --- |
| `getNode()` | `Node` | Returns the root node. |
| `getExpression()` | `string` | Returns the original DSL expression. |
| `serialise()` | `string` | Returns a canonical DSL representation. |
| `validate(mixed $value, bool $strict = false)` | `ValidationResult` | Validates `$value` against the schema. |

---

## ValidationResult — `Wingman\Verix\ValidationResult`

### Static factories

| Method | Returns | Description |
| --- | --- | --- |
| `ValidationResult::ok(mixed $value)` | `static` | Creates a passing result. |
| `ValidationResult::error(string $message, mixed $value)` | `static` | Creates a failing result with one error. |

### Readonly properties

| Property | Type | Description |
| --- | --- | --- |
| `$valid` | `bool` | Whether validation passed. |
| `$value` | `mixed` | The validated (possibly coerced) value. |
| `$errors` | `ValidationFailureException[]` | Validation errors. |
| `$metadata` | `array` | Arbitrary metadata. |

### Instance methods

| Method | Returns | Description |
| --- | --- | --- |
| `isValid()` | `bool` | Alias for `$valid`. |
| `getValue()` | `mixed` | Alias for `$value`. |
| `getErrors()` | `array` | Alias for `$errors`. |
| `getMetadata()` | `array` | Alias for `$metadata`. |
| `merge(ValidationResult $other)` | `static` | Combines two results; result is valid only if both are. Value taken from `$other`. |
| `throwIfInvalid()` | `static` | Throws `SchemaViolationException` if invalid; otherwise returns `$this`. |
| `withValue(mixed $value)` | `static` | Returns a copy with a different value. |
| `withErrors(array $errors)` | `static` | Returns a copy with additional errors appended. |
| `withPath(string $path)` | `static` | Returns a copy with `$path` prepended to every error's path. |
| `withMetadata(array $metadata, bool $replace = false)` | `static` | Returns a copy with merged (or replaced) metadata. |
| `with(array $options, bool $replaceMetadata = false)` | `static` | General-purpose copy-with: accepts `value`, `valid`, `errors`, `metadata` keys. |

---

## ValidationFailureException — `Wingman\Verix\Exceptions\ValidationFailureException`

| Method | Returns | Description |
| --- | --- | --- |
| `getMessage()` | `string` | The human-readable error description. |
| `getPath()` | `string` | Dot-notation path to the invalid field. Empty at root. |
| `getInvalidValue()` | `mixed` | The value that failed validation. |
| `withPath(string $path)` | `static` | Returns a copy with `$path` as the new path. |

---

## SchemaViolationException — `Wingman\Verix\Exceptions\SchemaViolationException`

| Method | Returns | Description |
| --- | --- | --- |
| `getMessage()` | `string` | Top-level violation message. |
| `getErrors()` | `ValidationFailureException[]` | All underlying validation errors. |

---

## PrimitiveDefinition — `Wingman\Verix\PrimitiveDefinition`

| Method | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | The type name. |
| `getParent()` | `string` | The parent type name (default `"any"`). |
| `getDescription()` | `string\|null` | Human-readable description. |
| `getParameters()` | `Parameter[]` | Ordered list of `Parameter` specs. |
| `derive(string $alias, array $params = [])` | `static` | Creates a child definition inheriting all properties but using `$alias` as the name. |

---

## Registry — `Wingman\Verix\Registries\Registry`

| Method | Returns | Description |
| --- | --- | --- |
| `Registry::get(?string $name = null)` | `static` | Returns the named registry singleton (default `"default"`). |
| `getPrimitiveRegistry()` | `PrimitiveRegistry` | Returns the primitive registry, bootstrapping built-in types. |
| `getSchemaRegistry()` | `SchemaRegistry` | Returns the schema registry. |
| `getClassRegistry()` | `ClassRegistry` | Returns the class registry. |
| `registerTypes(string $directory, ?string $namespace = null)` | `void` | Scans `$directory` for `Type` subclasses and registers them. |

---

## PrimitiveRegistry — `Wingman\Verix\Registries\PrimitiveRegistry`

| Method | Returns | Description |
| --- | --- | --- |
| `register(PrimitiveDefinition\|string $type)` | `void` | Registers a definition or resolves a class name and registers it. |
| `get(string $name)` | `PrimitiveDefinition\|null` | Returns the definition for `$name`, or `null`. |
| `has(string $name)` | `bool` | Returns `true` if `$name` is registered. |
| `getAll()` | `array` | Returns all definitions, keyed by name. |

---

## SchemaRegistry — `Wingman\Verix\Registries\SchemaRegistry`

| Method | Returns | Description |
| --- | --- | --- |
| `register(string $name, Schema $schema)` | `void` | Stores the schema under `$name`. |
| `get(string $name)` | `Schema\|null` | Returns the schema for `$name`, or `null`. |
| `has(string $name)` | `bool` | Returns `true` if `$name` is registered. |
| `getAll()` | `array` | Returns all schemas, keyed by name. |

---

## ClassRegistry — `Wingman\Verix\Registries\ClassRegistry`

| Method | Returns | Description |
| --- | --- | --- |
| `register(string $className, string $typeName)` | `void` | Maps `$className` to `$typeName`. |
| `get(string $className)` | `string\|null` | Returns the type name for `$className`, or `null`. |
| `has(string $className)` | `bool` | Returns `true` if `$className` is mapped. |
| `getAll()` | `array` | Returns all class → type name mappings. |

---

## TypeClassLocator — `Wingman\Verix\TypeClassLocator`

| Method | Returns | Description |
| --- | --- | --- |
| `locate(?string $directory, ?string $namespace)` | `string[]` | Returns class names of all concrete `Type` subclasses found in `$directory`. Uses and writes cache. |
| `findTypeClasses(string $directory, ?string $namespace)` | `string[]` | Scans `$directory` without using cache. |
| `cache(array $classes, ?string $targetPath)` | `static` | Writes `$classes` to a PHP return-array cache file. |
| `getClassesFromCache(?string $directory)` | `string[]` | Reads the cached class list. |
| `getCachePath(?string $directory)` | `string` | Returns the cache file path for `$directory`. |

---

## Node Interface — `Wingman\Verix\Nodes\Node`

All node classes implement the following interface:

| Method | Returns | Description |
| --- | --- | --- |
| `serialise()` | `string` | Returns the canonical DSL string for the node. |
| `validate(mixed $value, array $params, bool $strict)` | `ValidationResult` | Validates `$value` against the node. |

---

## Composite Nodes — `Wingman\Verix\Nodes\CompositeNode` subclasses

### `ArrayNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getItemType()` | `Node` | Returns the item type node. |

### `UnionNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getTypes()` | `Node[]` | Returns the list of branch nodes. |

### `IntersectionNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getTypes()` | `Node[]` | Returns the list of branch nodes. |

### `StructNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getFields()` | `StructField[]` | Returns ordered field specs. |
| `isOpen()` | `bool` | Returns `true` if extra keys are permitted (`...`). |
| `getRestField()` | `RestField\|null` | Returns the rest/spread field spec, or `null`. |

### `KeyedStructNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getKeyType()` | `Node` | Returns the key type node. |
| `getValueType()` | `Node` | Returns the value type node. |

### `TupleNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getItems()` | `Node[]` | Returns the ordered item type nodes. |

### `ClassNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | Returns the class name. |

### `SchemaRefNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | Returns the referenced schema name. |

---

## Primitive Nodes — `Wingman\Verix\Nodes\PrimitiveNode` subclasses

### `PrimitiveNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | Returns the primitive type name. |
| `getParams()` | `array` | Returns the named parameter values. |

### `LiteralNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getValue()` | `string\|int` | Returns the literal value. |

### `EnumNode`

| Method | Returns | Description |
| --- | --- | --- |
| `getValues()` | `array` | Returns the allowed literal values. |

### `AnyNode` / `NullNode`

No additional methods beyond `serialise()` and `validate()`.

---

## Specs

### `Parameter` — `Wingman\Verix\Specs\Parameter`

| Property | Type | Description |
| --- | --- | --- |
| `$name` | `string` | Parameter name. |
| `$type` | `string` | Expected PHP type string. |
| `$default` | `mixed` | Default value when omitted. |
| `$required` | `bool` | Whether the parameter must be supplied. |
| `$description` | `string\|null` | Human-readable description. |

### `StructField` — `Wingman\Verix\Specs\StructField`

| Method | Returns | Description |
| --- | --- | --- |
| `getName()` | `string` | Field key name. |
| `getSchema()` | `Schema` | The field's type schema. |
| `isOptional()` | `bool` | Whether the field may be absent. |

### `RestField` — `Wingman\Verix\Specs\RestField`

| Method | Returns | Description |
| --- | --- | --- |
| `getSchema()` | `Schema\|null` | The type schema for extra keys, or `null` for untyped. |

---

## Argus Bridge

### `SchemaMatcher` — `Wingman\Verix\Bridge\Argus\Matchers\SchemaMatcher`

| Method | Returns | Description |
| --- | --- | --- |
| `__construct(string $definition)` | — | Stores the DSL expression to match against. |
| `matches(mixed $value)` | `bool` | Returns `true` if `$value` is valid for the schema. |
| `__toString()` | `string` | Returns `"Matches Verix Schema: {definition}"`. |

### `Asserter` trait — `Wingman\Verix\Bridge\Argus\Traits\Asserter`

| Method | Description |
| --- | --- |
| `assertMatchesSchema(string $definition, mixed $actual, string $message = "")` | Asserts that `$actual` is valid. |
| `assertNotMatchesSchema(string $definition, mixed $actual, string $message = "")` | Asserts that `$actual` is invalid. |
| `assertPrimitiveExists(string $name, ?string $registry, string $message = "")` | Asserts the primitive is registered. |
| `assertPrimitiveNotExists(string $name, ?string $registry, string $message = "")` | Asserts the primitive is not registered. |
| `assertSchemaExists(string $name, ?string $registry, string $message = "")` | Asserts the schema is registered. |
| `assertSchemaNotExists(string $name, ?string $registry, string $message = "")` | Asserts the schema is not registered. |
| `assertClassRegistered(string $className, ?string $registry, string $message = "")` | Asserts the class is in the `ClassRegistry`. |
| `assertClassNotRegistered(string $className, ?string $registry, string $message = "")` | Asserts the class is not in the `ClassRegistry`. |

---

## Signals — `Wingman\Verix\Enums\Signal`

| Case | Value | Emitted by |
| --- | --- | --- |
| `CLASS_REGISTERED` | `"verix.class.registered"` | `ClassRegistry::register()` |
| `PRIMITIVE_REGISTERED` | `"verix.primitive.registered"` | `PrimitiveRegistry::register()` |
| `SCHEMA_PARSED` | `"verix.schema.parsed"` | `Schema::from()`, `Schema::register()`, `Schema::merge()` |
| `SCHEMA_REGISTERED` | `"verix.schema.registered"` | `Schema::register()` |
| `SCHEMA_VALIDATED` | `"verix.schema.validated"` | `Schema->validate()` |

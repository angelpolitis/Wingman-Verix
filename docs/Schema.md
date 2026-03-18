# Wingman Verix — Schema

The `Schema` class is the central value object of Verix. It wraps a parsed and normalised `Node` alongside the original DSL expression string. All interaction with the type system flows through the `Schema` facade.

---

## The Schema Facade

```php
use Wingman\Verix\Facades\Schema;
```

The facade is a static helper that delegates to the underlying `Schema` class. Every method that accepts an optional `$registry` parameter defaults to `"default"` when none is supplied.

---

## Creating a Schema

### `Schema::from(string $expression, ?string $registry = null): Schema`

Parses and normalises the DSL expression, returning a ready-to-use `Schema` instance. This is the most common entry point.

```php
$schema = Schema::from("string<minLength=1>");
```

The resulting schema is **not** cached — each call to `from()` creates a new instance.

### `Schema::parse(string $expression, ?string $registry = null): Node`

Parses the expression and returns the raw `Node` without constructing a `Schema` wrapper. Useful when you only need the AST.

```php
$node = Schema::parse("int | string");
```

---

## Registering a Schema

### `Schema::register(string $name, string $expression, ?string $registry = null): Schema`

Parses the expression, stores the resulting schema in the registry under the given name, and returns the schema. Registered schemas can later be referenced in other expressions as `@Name`.

```php
Schema::register("Address", "{
    street:  string,
    city:    string,
    country: string<minLength=2, maxLength=2>
}");

$schema = Schema::from("@Address");
```

If the name is already registered, the existing entry is overwritten.

Emits the `SCHEMA_REGISTERED` signal if Corvus is available. See [Bridge.md](Bridge.md) for the payload structure.

---

## Expanding References

### `Schema::expand(Node $node, ?string $registry = null): Node`

Resolves all `SchemaRefNode` instances within the given node tree, replacing each reference with the underlying node from the registry. Returns the fully expanded node.

```php
$node     = Schema::parse("@User");
$expanded = Schema::expand($node);
// $expanded is now the actual node tree for the User schema
```

This is called automatically during normalisation inside `from()` and `register()`, so you rarely need to call it manually.

---

## Inferring a Schema from Data

### `Schema::infer(array $data): Schema`

Analyses an array of sample values and infers the most specific schema that would accept all of them. Fields present in some but not all samples are inferred as optional.

```php
$schema = Schema::infer([
    ["name" => "Alice", "age" => 30, "active" => true],
    ["name" => "Bob",   "age" => 25],
]);

echo $schema->serialise(); // {name: string, age?: float, active?: bool}
```

`infer()` works recursively on nested arrays and uses type widening when samples disagree (e.g. `int` + `string` → `any`).

---

## Merging Schemas

### `Schema::merge(?Schema $a, ?Schema $b): ?Schema`

Merges two struct schemata into one. Fields present in only one schema are made optional in the result. If both schemas represent the same struct, the merged result is equivalent to the original.

```php
$a = Schema::from("{id: int, name: string}");
$b = Schema::from("{id: int, email: string, age?: int}");

$merged = Schema::merge($a, $b);

echo $merged->serialise();
// {id: int, name?: string, email?: string, age?: int}
```

If either argument is `null`, the other is returned unchanged. If both are `null`, `null` is returned. If the schemas are structurally incompatible (e.g. one is a struct and the other is a primitive), a `SchemaException` is thrown.

---

## Validating a Value

### `Schema->validate(mixed $value, bool $strict = false): ValidationResult`

Validates `$value` against the schema and returns a `ValidationResult`.

```php
$result = Schema::from("int<min=0>")->validate(42);

if ($result->isValid()) {
    echo $result->getValue(); // 42
}
```

In non-strict mode (the default), minor coercions are applied (e.g. a numeric string `"42"` is accepted for `int`).

In strict mode, values that do not precisely match the expected type fail validation:

```php
$result = Schema::from("int")->validate("42", strict: true);
$result->isValid(); // false — string is not strictly an int
```

Validation errors are collected rather than thrown; every field in a struct is tested even if an earlier field already failed. This gives you a complete list of all violations in one pass.

Emits the `SCHEMA_VALIDATED` signal if Corvus is available.

See [Validation.md](Validation.md) for the full `ValidationResult` API.

---

## Instance Methods

Once you have a `Schema` instance (from `from()`, `register()`, `infer()`, or `merge()`), the following instance methods are available:

### `getNode(): Node`

Returns the root node of the parsed and normalised AST.

```php
$node = Schema::from("string[]")->getNode();
// instanceof ArrayNode
```

### `getExpression(): string`

Returns the original DSL expression string passed to the parser.

```php
$expr = Schema::from("int<min=0>")->getExpression();
// "int<min=0>"
```

### `serialise(): string`

Returns a canonical DSL string produced by serialising the root node. The canonical form may differ from the original expression (e.g. whitespace is normalised, optional shorthand is expanded).

```php
Schema::from("string ?  ")->serialise();
// "string | null"
```

---

## Signal Emissions

When Wingman/Corvus is available, `Schema` emits the following signals:

| Method | Signal | Payload keys |
| --- | --- | --- |
| `register()` | `SCHEMA_REGISTERED` | `name`, `expression`, `registry` |
| `validate()` | `SCHEMA_VALIDATED` | `schema`, `value`, `result` |
| `parse()` | `SCHEMA_PARSED` | `expression`, `node`, `registry` |

See [Bridge.md](Bridge.md) for details on listening to these signals.

---

## Error Handling

| Scenario | Behaviour |
| --- | --- |
| Malformed DSL expression | `ParsingException` thrown immediately |
| Unrecognisable token | `TokenisationException` thrown immediately |
| `@Ref` not found in registry | `SchemaException` thrown during normalisation |
| Validation failure (non-strict) | `ValidationResult` with errors; no exception |
| Validation failure (strict) | `ValidationResult` with errors; no exception — use `throwIfInvalid()` if needed |

```php
try {
    $schema = Schema::from("int<min=0>");
    $result = $schema->validate($userInput);
    $result->throwIfInvalid();
} catch (\Wingman\Verix\Exceptions\SchemaViolationException $e) {
    // Handle validation failure
} catch (\Wingman\Verix\Exceptions\ParsingException $e) {
    // Handle malformed DSL — should not happen with static expressions
}
```

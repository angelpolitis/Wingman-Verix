# Wingman Verix — Overview

Verix is Wingman's type system. It provides a concise **Domain-Specific Language (DSL)** for expressing data types as plain strings, a **parsing pipeline** that converts those strings into an abstract syntax tree, a **registry system** for named types and schemata, and a **validation engine** that tests runtime values against any parsed type.

Verix has no external PHP dependencies. The optional Corvus and Argus integrations are isolated behind bridge classes that fail gracefully when those packages are absent.

---

## Core Concepts

### Schema

A `Schema` is the primary value object you interact with. It wraps a parsed and normalised `Node` alongside the original DSL expression string. Once constructed, a schema is immutable and can be reused across any number of validations.

The `Schema` facade is the main entry point:

```php
use Wingman\Verix\Facades\Schema;

$schema  = Schema::from("string<minLength=1, maxLength=255>");
$result  = $schema->validate("hello");
```

### Node

A `Node` is the internal Abstract Syntax Tree representation of a type. Every node can serialise itself back to a canonical DSL expression and validate a value independently.

Nodes fall into two broad families:

| Family | Examples |
| --- | --- |
| **Primitive** | `PrimitiveNode`, `LiteralNode`, `AnyNode`, `NullNode`, `EnumNode` |
| **Composite** | `ArrayNode`, `StructNode`, `UnionNode`, `IntersectionNode`, `TupleNode`, `ClassNode`, `SchemaRefNode` |

### Primitive Type

A primitive type is a named scalar type that carries an optional set of constraint parameters. The built-in types (`string`, `int`, `float`, `number`, `bool`, `date`, `any`) are always available. Custom types are registered via the `Primitive` facade.

### Registry

A `Registry` is a named, singleton container. It aggregates three sub-registries:

- **`PrimitiveRegistry`** — stores `PrimitiveDefinition` instances by type name.
- **`SchemaRegistry`** — stores named `Schema` instances.
- **`ClassRegistry`** — maps PHP class names to registered type names, enabling class-based schema auto-discovery.

Multiple independent registries can coexist; if no name is given, the `"default"` registry is used.

### Validation Result

Every validation call returns a `ValidationResult`. This is a simple value object that carries the validated value, a pass/fail flag, a list of `ValidationError` instances, a path, and optional metadata.

---

## Architecture

```
DSL string
    │
    ▼
TypeTokeniser          Produces a flat list of typed token objects.
    │
    ▼
TypeParser             Transforms tokens into a Node AST.
    │
    ▼
TypeNormaliser         Resolves optional suffixes (`?`), expands `@SchemaRef`
    │                  nodes, and canonicalises the tree.
    ▼
Schema                 Wraps the normalised Node. Ready for validation.
    │
    ▼
Node::validate()       Walks the AST and tests each value. Returns a
                       ValidationResult.
```

### TypeTokeniser

The tokeniser walks the expression character by character and emits `Token` objects. Each token carries a kind (e.g. `IDENTIFIER`, `LBRACE`, `COLON`, `COMMA`) and its raw string value. Whitespace is skipped. An unrecognisable character throws a `TokenisationException`.

### TypeParser

The parser consumes the token stream in a single pass using a recursive-descent grammar. It constructs a `Node` for each sub-expression and returns the root node. A malformed expression throws a `ParsingException`.

### TypeNormaliser

The normaliser traverses the raw AST and applies these transformations:

1. **Optional suffix** — `T?` becomes `UnionNode(T, NullNode)`.
2. **Schema reference expansion** — `@Name` nodes are resolved from the registry to their underlying nodes.
3. **Struct field normalisation** — optional field markers are canonicalised.

### StructMerger

`StructMerger` is a separate processor used by `Schema::merge()`. It combines two compatible `StructNode` instances into one, marking fields that appear in only one source as optional.

### TypeClassLocator

`TypeClassLocator` scans a directory tree for PHP classes and maps each class name to its Verix type, using the `ClassRegistry` for lookups. Results are optionally persisted to a cache file to avoid re-scanning on subsequent requests.

---

## DSL Reference

See [DSL.md](DSL.md) for the complete syntax reference.

---

## Bootstrapping

There is no explicit initialisation step. The autoloader is the only requirement:

```php
require_once '/path/to/verix/autoload.php';
```

All registries are lazy singletons; they are created on first access and cached for the process lifetime.

If Corvus is available, the bridge will emit lifecycle events automatically. If it is absent, the bridge returns a silent null stub — no code change required on your part.

---

## Error Handling Strategy

Verix distinguishes between two categories of errors:

| Category | Behaviour |
| --- | --- |
| **Parse / structural errors** | Thrown immediately as exceptions (`ParsingException`, `TokenisationException`, `SchemaException`). DSL parsing happens at definition time, not at validation time, so errors surface early. |
| **Validation errors** | Packaged into a `ValidationResult` and returned. Exceptions are only thrown if you explicitly call `throwIfInvalid()` or `Schema::validate(value, strict: true)`. |

This design lets you validate user input without wrapping every call in a try/catch, while still allowing the fail-fast style when validating internal data.

---

## Next Steps

| Guide | Description |
| --- | --- |
| [DSL.md](DSL.md) | Full DSL syntax with examples |
| [Schema.md](Schema.md) | Schema facade deep dive |
| [Primitives.md](Primitives.md) | Built-in types, parameters, and custom registration |
| [Registries.md](Registries.md) | Registry system |
| [Validation.md](Validation.md) | ValidationResult and error handling |
| [Bridge.md](Bridge.md) | Corvus and Argus integrations |
| [API-Reference.md](API-Reference.md) | Full method reference |

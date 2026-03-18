# Wingman — Verix

A powerful and flexible PHP type system for the Wingman framework. Verix provides a concise **Domain-Specific Language (DSL)** for expressing types as plain strings, then parses, normalises, and validates runtime values against them — with no code generation and no external dependencies beyond PHP 8.1.

---

## Requirements

- PHP **8.1** or later
- **Wingman/Corvus** *(optional)* — enables lifecycle event emissions
- **Wingman/Argus** *(optional, dev)* — enables schema-aware test assertions

---

## Installation

Copy or symlink the package into your project and include its autoloader:

```php
require_once '/path/to/verix/autoload.php';
```

The autoloader registers a PSR-style class map for the `Wingman\Verix` namespace.

---

## Quick Start

### Validate a value against a DSL expression

```php
use Wingman\Verix\Facades\Schema;

$result = Schema::from("string<minLength=3, maxLength=50>")->validate("hello");

if ($result->isValid()) {
    echo $result->getValue(); // "hello"
} else {
    foreach ($result->getErrors() as $error) {
        echo $error->getMessage();
    }
}
```

### Register and reuse a named schema

```php
use Wingman\Verix\Facades\Schema;

Schema::register("User", "{
    id:      int<min=1>,
    name:    string<minLength=1, maxLength=100>,
    email:   string<pattern=\"/^[^@]+@[^@]+$/\">,
    role?:   'admin' | 'editor' | 'viewer',
    tags?:   string[]
}");

$result = Schema::from("@User")->validate([
    "id"    => 1,
    "name"  => "Alice",
    "email" => "alice@example.com",
]);
```

### Register a custom primitive type

```php
use Wingman\Verix\Facades\Primitive;

Primitive::register(
    name:      "uuid",
    validator: fn ($v) => is_string($v) && preg_match('/^[0-9a-f\-]{36}$/i', $v),
    extends:   "string",
    description: "A UUID string."
);

$result = Schema::from("uuid")->validate("550e8400-e29b-41d4-a716-446655440000");
```

### Infer a schema from data

```php
use Wingman\Verix\Facades\Schema;

$schema = Schema::infer([
    ["name" => "Alice", "age" => 30],
    ["name" => "Bob",   "age" => 25],
    ["name" => "Carol"],            // missing "age" — inferred as optional
]);

echo $schema->serialise(); // struct{name: string, age?: float}
```

---

## DSL at a Glance

| Expression | Meaning |
| --- | --- |
| `string` | Any string |
| `int<min=1, max=100>` | Integer between 1 and 100 |
| `string?` | `string \| null` |
| `string[]` | Array of strings |
| `{name: string, age?: int}` | Struct with required name and optional age |
| `'active' \| 'inactive'` | Literal union |
| `[string, int, bool]` | Fixed-length tuple |
| `enum{'a' \| 'b' \| 'c'}` | Enum of literals |
| `@UserSchema` | Reference to a registered schema |

See [docs/DSL.md](docs/DSL.md) for the full syntax reference.

---

## Key Concepts

- **Schema** — A parsed and normalised type expression, ready to validate values. The `Schema` facade is the primary entry point.
- **Registry** — A container for primitive types, named schemata, and class aliases. Multiple named registries can coexist.
- **Primitive** — A named scalar type with optional constraints (e.g. `int`, `string`, `date`). Custom primitives are registered via `Primitive::register()`.
- **Node** — The internal AST representation of a parsed type. Each node can serialise and validate itself independently.
- **Signal** — Optional Corvus lifecycle events emitted on parsing, registration, and validation.

---

## Documentation

| Document | Description |
| --- | --- |
| [Overview](docs/Overview.md) | Architecture, concepts, and the parsing pipeline |
| [DSL](docs/DSL.md) | Complete DSL syntax reference |
| [Schema](docs/Schema.md) | `Schema` facade — parsing, validation, registration, inference, and merging |
| [Primitives](docs/Primitives.md) | Built-in types, custom types, parameters, and `Parameter` spec |
| [Registries](docs/Registries.md) | `Registry`, `PrimitiveRegistry`, `SchemaRegistry`, `ClassRegistry` |
| [Validation](docs/Validation.md) | `ValidationResult`, error handling, and `throwIfInvalid()` |
| [Bridge](docs/Bridge.md) | Corvus event bridge and Argus testing utilities |
| [API Reference](docs/API-Reference.md) | Full method and class reference |

---

## Licence

This project is licensed under the **Mozilla Public License 2.0 (MPL 2.0)**.

Wingman Verix is part of the **Wingman Framework**, Copyright (c) 2018–2026 Angel Politis.

For the full licence text, please see the [LICENSE](LICENSE) file.

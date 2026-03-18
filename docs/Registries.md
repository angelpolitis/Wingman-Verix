# Wingman Verix — Registries

Verix uses a layered registry system to manage primitive types, named schemata, and PHP class mappings. All registries are named singletons scoped to the current PHP process.

---

## Registry

`Wingman\Verix\Registries\Registry` is the top-level registry factory and container. It aggregates three sub-registries and a `TypeClassLocator`.

### Obtaining a Registry

```php
use Wingman\Verix\Registries\Registry;

// Gets (or lazily creates) the default registry
$registry = Registry::get();

// Gets (or lazily creates) a named registry
$registry = Registry::get("payments");
```

`Registry::get()` is a caching factory — calling it twice with the same name returns the same instance. The constant `Registry::DEFAULT_NAME` (`"default"`) identifies the default registry.

### Creating a Registry with Configuration

```php
$registry = new Registry("payments", [
    Registry::ALLOW_CACHING      => false,
    Registry::AUTO_REFRESH_CACHE => false,
]);
```

The configuration array is forwarded to the `TypeClassLocator` used internally. See [TypeClassLocator](#typeclasslocator) below.

### Accessing Sub-Registries

| Method | Returns | Description |
| --- | --- | --- |
| `getPrimitiveRegistry()` | `PrimitiveRegistry` | Returns the primitive registry, bootstrapping built-in types on first access. |
| `getSchemaRegistry()` | `SchemaRegistry` | Returns the schema registry. |
| `getClassRegistry()` | `ClassRegistry` | Returns the class registry. |

### Registering Type Classes from a Directory

```php
$registry->registerTypes("/path/to/my/types", "App\\Types");
```

This scans the directory for concrete `Type` subclasses, resolves their class names using the given namespace, and registers each one into the `PrimitiveRegistry`. If no namespace is given, `Wingman\Verix\Types` is used.

---

## PrimitiveRegistry

`Wingman\Verix\Registries\PrimitiveRegistry` stores `PrimitiveDefinition` instances keyed by type name. It is bootstrapped automatically with the seven built-in types the first time it is accessed.

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `register(PrimitiveDefinition\|string $type)` | `void` | Registers a definition directly, or a class name string (which is resolved via reflection). |
| `get(string $name)` | `PrimitiveDefinition\|null` | Returns the definition for a type name, or `null` if not registered. |
| `has(string $name)` | `bool` | Returns `true` if the type name is registered. |
| `getAll()` | `array` | Returns all registered `PrimitiveDefinition` instances, keyed by name. |

```php
$registry = Registry::get()->getPrimitiveRegistry();

$def = $registry->get("int");
echo $def->getName(); // "int"

$registry->has("uuid"); // false — unless registered
```

---

## SchemaRegistry

`Wingman\Verix\Registries\SchemaRegistry` stores named `Schema` instances. Named schemas are used by `@SchemaRef` nodes in DSL expressions.

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `register(string $name, Schema $schema)` | `void` | Registers a schema under the given name. Overwrites any existing entry. |
| `get(string $name)` | `Schema\|null` | Returns the schema registered under `$name`, or `null` if not found. |
| `has(string $name)` | `bool` | Returns `true` if a schema is registered under `$name`. |
| `getAll()` | `array` | Returns all registered schemas, keyed by name. |

```php
use Wingman\Verix\Facades\Schema;
use Wingman\Verix\Registries\Registry;

Schema::register("Point", "{x: float, y: float}");

$schemaRegistry = Registry::get()->getSchemaRegistry();
$point          = $schemaRegistry->get("Point");
echo $point->serialise(); // {x: float, y: float}
```

Named schemata are also accessible in DSL expressions through `@Point` syntax anywhere a type is expected.

---

## ClassRegistry

`Wingman\Verix\Registries\ClassRegistry` maps fully-qualified PHP class names to their Verix type names. It enables the `ClassNode` DSL construct and the `Primitive::registerClass()` workflow.

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `register(string $className, string $typeName)` | `void` | Associates a PHP class with a Verix type name. |
| `get(string $className)` | `string\|null` | Returns the type name for a class, or `null` if unregistered. |
| `has(string $className)` | `bool` | Returns `true` if the class is registered. |
| `getAll()` | `array` | Returns all class → type name mappings. |

```php
use Wingman\Verix\Registries\Registry;

$classRegistry = Registry::get()->getClassRegistry();
$classRegistry->register(\App\ValueObjects\Money::class, "money");

// Now "Money" in a DSL expression resolves to the "money" primitive
```

---

## TypeClassLocator

`Wingman\Verix\TypeClassLocator` resolves PHP class names to registered Verix types by scanning a directory tree. Results are cached to a PHP file to avoid repeated filesystem scans.

### Constructor

```php
$locator = new TypeClassLocator([
    TypeClassLocator::ALLOW_CACHING      => true,  // default
    TypeClassLocator::AUTO_REFRESH_CACHE => false, // default
]);
```

| Config key | Type | Default | Description |
| --- | --- | --- | --- |
| `ALLOW_CACHING` | `bool` | `true` | Whether to write the discovered class list to a cache file. |
| `AUTO_REFRESH_CACHE` | `bool` | `false` | When `true`, the cache is regenerated whenever the directory modification time is newer than the cache file. |

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `locate(?string $directory, ?string $namespace)` | `array` | Returns an array of fully-qualified class names that extend `Type`. Uses cache when available. |
| `findTypeClasses(string $directory, ?string $namespace)` | `array` | Scans the directory directly, bypassing the cache. |
| `cache(array $classes, ?string $targetPath)` | `static` | Writes the class list to a PHP cache file at `$targetPath`. |
| `getClassesFromCache(?string $directory)` | `array` | Reads and returns the cached class list. |
| `getCachePath(?string $directory)` | `string` | Returns the cache file path for a given directory (derived via `md5` of the directory path). |

### Cache File Format

Cache files are plain PHP return-array files written to `cache/` in the Verix root:

```php
<?php

return [
    'Wingman\\Verix\\Types\\StringType',
    'Wingman\\Verix\\Types\\IntegerType',
    // ...
];
```

Cache files are invalidated and regenerated when:
- The file does not exist yet.
- `AUTO_REFRESH_CACHE` is `true` and the directory `mtime` is newer than the cache file `mtime`.

---

## Multiple Registries

You can use named registries to keep type sets isolated from one another:

```php
use Wingman\Verix\Facades\Schema;
use Wingman\Verix\Facades\Primitive;

// Register a type only in the "payments" registry
Primitive::register(
    name:      "currency",
    validator: fn ($v) => is_string($v) && strlen($v) === 3,
    registry:  "payments"
);

// Validate against that registry
$result = Schema::from("currency", "payments")->validate("USD");

// The "default" registry knows nothing about "currency"
$result2 = Schema::from("currency")->validate("USD"); // throws SchemaException
```

Every `Schema` and `Primitive` facade method accepts an optional `$registry` argument that can be either a registry name string or a `Registry` instance. When omitted, `"default"` is used.

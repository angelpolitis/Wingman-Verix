# Wingman Verix — Bridge

Verix provides two optional integrations with other Wingman modules: a **Corvus bridge** for lifecycle event emission, and an **Argus bridge** for schema-aware test assertions.

Both bridges are isolated behind conditional class definitions; if an optional dependency is absent the integration is silently skipped with no code change required.

---

## Corvus Bridge

The Corvus bridge emits lifecycle events on Wingman/Corvus's event bus whenever Verix performs a significant operation.

### Availability

The bridge activates automatically when `Wingman\Corvus\Emitter` is available in the current PHP environment. If it is absent, the `Emitter` class resolves to a null-object stub that absorbs all calls silently.

```php
// Works with or without Corvus — no conditional code needed
Schema::register("User", "{id: int, name: string}");
// ↑ Emits SCHEMA_REGISTERED if Corvus present; otherwise no-op
```

### Re-entrancy Guard

The bridge's `Emitter` overrides the standard Corvus `emit()` with a re-entrancy guard:

```
static bool $dispatching = false
```

If a Corvus listener itself triggers a Verix registration or validation operation, the nested emission is swallowed rather than dispatched. This prevents infinite recursion without requiring listeners to be aware of the guard.

---

### Signal Enum

`Wingman\Verix\Enums\Signal` is a backed string enum. Each case corresponds to a dot-notation event identifier on the Corvus bus.

| Case | Value | When emitted |
| --- | --- | --- |
| `CLASS_REGISTERED` | `"verix.class.registered"` | After a class alias is registered in a `ClassRegistry`. |
| `PRIMITIVE_REGISTERED` | `"verix.primitive.registered"` | After a `PrimitiveDefinition` (or alias) is registered in a `PrimitiveRegistry`. |
| `SCHEMA_PARSED` | `"verix.schema.parsed"` | After any DSL expression has been fully parsed and normalised — fires for `Schema::from()`, `Schema::register()`, and `Schema::merge()`. |
| `SCHEMA_REGISTERED` | `"verix.schema.registered"` | After a named schema is stored in a `SchemaRegistry` via `Schema::register()`. |
| `SCHEMA_VALIDATED` | `"verix.schema.validated"` | After `Schema->validate()` completes, regardless of the result. |

### Signal Payloads

Each signal is emitted with a structured associative array payload:

| Signal | Payload keys | Types |
| --- | --- | --- |
| `CLASS_REGISTERED` | `alias`, `fqcn`, `registry` | `string`, `string`, `ClassRegistry` |
| `PRIMITIVE_REGISTERED` | `definition`, `registry` | `PrimitiveDefinition`, `PrimitiveRegistry` |
| `SCHEMA_PARSED` | `expression`, `schema` | `string`, `Schema` |
| `SCHEMA_REGISTERED` | `name`, `node`, `registry` | `string`, `Node`, `SchemaRegistry` |
| `SCHEMA_VALIDATED` | `value`, `result`, `schema` | `mixed`, `ValidationResult`, `Schema` |

### Listening to Signals

Use the `Listener` fluent builder to subscribe to signals. Calling `when()` (or any other signal-registration method) automatically registers the listener on the bus — no separate `Bus::registerListener()` call is required.

```php
use Wingman\Corvus\Listener;
use Wingman\Corvus\Objects\HandlerExecution;
use Wingman\Verix\Enums\Signal;

Listener::create()
    ->when(Signal::SCHEMA_VALIDATED)
    ->do(function (HandlerExecution $execution) {
        $schema = $execution->payload["schema"];
        $result = $execution->payload["result"];

        if (!$result->isValid()) {
            error_log(
                "Schema validation failed for: " . $schema->getExpression() .
                " — " . count($result->getErrors()) . " error(s)."
            );
        }
    });
```

The `HandlerExecution` object exposes the following readonly properties:

| Property | Type | Description |
| --- | --- | --- |
| `$execution->signals` | `SignalCollection` | The signal(s) that triggered the handler. |
| `$execution->target` | `?object` | The emitting target (always `null` for Verix signals). |
| `$execution->payload` | `array` | The structured payload for the signal (see [Signal Payloads](#signal-payloads) above). |
| `$execution->date` | `DateTimeImmutable` | Timestamp of the emission. |

To target a named bus other than the default, call `useBus(string $name)` before registering signals:

```php
Listener::create()
    ->useBus("my-bus")
    ->when(Signal::SCHEMA_PARSED)
    ->do(fn (HandlerExecution $execution) => var_dump($execution->payload["schema"]));
```

---

## Argus Bridge

The Argus bridge adds Verix-specific assertion methods and an argument matcher to Wingman/Argus test classes.

### Availability

The bridge classes are always present in the `src/Bridge/Argus/` directory. They consume the `Wingman\Argus\Interfaces\ArgumentMatcher` interface and the Argus `recordAssertion()` method. If Argus is not installed, including the bridge files will cause an autoload failure — only do so in a dev context.

### SchemaMatcher

`Wingman\Verix\Bridge\Argus\Matchers\SchemaMatcher` implements the Argus `ArgumentMatcher` interface. Pass it wherever Argus expects a matcher to verify that a spied/mocked call received an argument satisfying a Verix schema.

```php
use Wingman\Verix\Bridge\Argus\Matchers\SchemaMatcher;

// Verify a mock was called with a valid User argument
$mock->assertCalledWith(new SchemaMatcher("{id: int<min=1>, name: string}"));
```

`SchemaMatcher::matches(mixed $value): bool` calls `Schema::from($definition)->validate($value)->isValid()` internally.

`SchemaMatcher::__toString()` returns `"Matches Verix Schema: {definition}"` for readable failure messages.

### Asserter Trait

`Wingman\Verix\Bridge\Argus\Traits\Asserter` is a trait for use in Argus test classes. It exposes the following assertion methods:

#### Schema Matching

| Method | Description |
| --- | --- |
| `assertMatchesSchema(string $definition, mixed $actual, string $message = "")` | Asserts that `$actual` is valid against the DSL `$definition`. |
| `assertNotMatchesSchema(string $definition, mixed $actual, string $message = "")` | Asserts that `$actual` violates the DSL `$definition`. |

#### Registry Assertions

| Method | Description |
| --- | --- |
| `assertPrimitiveExists(string $name, ?string $registry, string $message = "")` | Asserts that a primitive type named `$name` is registered. |
| `assertPrimitiveNotExists(string $name, ?string $registry, string $message = "")` | Asserts that no primitive type named `$name` is registered. |
| `assertSchemaExists(string $name, ?string $registry, string $message = "")` | Asserts that a named schema `$name` is registered. |
| `assertSchemaNotExists(string $name, ?string $registry, string $message = "")` | Asserts that no schema named `$name` is registered. |
| `assertClassRegistered(string $className, ?string $registry, string $message = "")` | Asserts that `$className` is registered in the `ClassRegistry`. |
| `assertClassNotRegistered(string $className, ?string $registry, string $message = "")` | Asserts that `$className` is not registered in the `ClassRegistry`. |

#### Contract

The `Asserter` trait depends on an abstract `recordAssertion()` method that the consuming class must provide:

```php
abstract protected function recordAssertion(
    bool   $status,
    mixed  $expected,
    mixed  $actual,
    string $message
): void;
```

Argus test base classes implement this method automatically.

#### Usage Example

```php
use Wingman\Argus\Test;
use Wingman\Verix\Bridge\Argus\Traits\Asserter;
use Wingman\Verix\Facades\Schema;
use Wingman\Verix\Facades\Primitive;

class UserSchemaTest extends Test {
    use Asserter;

    public function testRegistration () : void {
        Primitive::register("uuid", fn ($v) => is_string($v) && strlen($v) === 36);
        Schema::register("User", "{id: uuid, name: string}");

        $this->assertPrimitiveExists("uuid");
        $this->assertSchemaExists("User");
        $this->assertMatchesSchema(
            "{id: uuid, name: string}",
            ["id" => "550e8400-e29b-41d4-a716-446655440000", "name" => "Alice"]
        );
        $this->assertNotMatchesSchema(
            "{id: uuid, name: string}",
            ["id" => "not-a-uuid", "name" => "Bob"]
        );
    }
}
```

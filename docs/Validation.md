# Wingman Verix — Validation

Verix separates the *result* of validation from validation *exceptions*. Every call to `Schema->validate()` returns a `ValidationResult` — a small value object that carries the value, a pass/fail flag, and a list of errors. Exceptions are only thrown when you explicitly ask for them.

---

## ValidationResult

`Wingman\Verix\ValidationResult` is immutable. All `with*` methods return a new instance.

### Factories

| Method | Returns | Description |
| --- | --- | --- |
| `ValidationResult::ok(mixed $value)` | `static` | Creates a passing result carrying `$value`. |
| `ValidationResult::error(string $message, mixed $value)` | `static` | Creates a failing result with a single `ValidationFailureException` error. |

```php
use Wingman\Verix\ValidationResult;

$pass = ValidationResult::ok(42);
$fail = ValidationResult::error("Expected a positive integer.", -5);
```

### Reading a Result

| Method | Returns | Description |
| --- | --- | --- |
| `isValid()` | `bool` | `true` if validation passed. |
| `getValue()` | `mixed` | The value that was validated (may be a coerced version of the original). |
| `getErrors()` | `ValidationFailureException[]` | The list of validation errors. Empty when the result is valid. |
| `getMetadata()` | `array` | Arbitrary metadata attached to the result. |

```php
$result = Schema::from("int<min=1>")->validate(-3);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "[" . $error->getPath() . "] " . $error->getMessage();
        echo " — invalid value: " . var_export($error->getInvalidValue(), true);
    }
}
```

### Throwing on Failure

`throwIfInvalid()` converts a failing result into a `SchemaViolationException`. It is a no-op on a passing result and always returns the result for chaining.

```php
$schema->validate($value)->throwIfInvalid();

// Or use the strict mode shortcut:
$schema->validate($value, strict: true);
```

### Combining Results

`merge(ValidationResult $other): static` combines two results:

- The merged result is valid only if **both** results are valid.
- Errors from both results are concatenated.
- Metadata from both results is merged.
- The **value** of the merged result is taken from `$other`.

```php
$a = ValidationResult::ok(1);
$b = ValidationResult::error("Too small.", 0);

$merged = $a->merge($b);
$merged->isValid(); // false
count($merged->getErrors()); // 1
```

### Modifying a Result

All modification methods return a **new** `ValidationResult` instance.

| Method | Description |
| --- | --- |
| `withValue(mixed $value)` | Returns a new result with `$value` replacing the current value. |
| `withErrors(array $errors)` | Appends additional `ValidationFailureException` instances. Only meaningful on a failing result. |
| `withPath(string $path)` | Prefixes `$path` onto every error's path. Used internally to build dot-notation paths for nested struct fields. |
| `withMetadata(array $metadata, bool $replace = false)` | Merges (or replaces) the metadata. |
| `with(array $options, bool $replaceMetadata = false)` | General-purpose modifier; accepts any combination of `value`, `valid`, `errors`, and `metadata` keys. |

```php
$result = Schema::from("string")->validate(42);
$result = $result->withPath("user.name");

$error = $result->getErrors()[0];
echo $error->getPath(); // "user.name"
```

---

## ValidationFailureException

`Wingman\Verix\Exceptions\ValidationFailureException` is used as the error type inside `ValidationResult`. It is an exception but is typically never thrown directly — it lives inside the `errors` array.

### Constructor

```php
new ValidationFailureException(
    message:      "String length must be at least 3.",
    path:         "user.name",
    invalidValue: "ab",
    code:         0,
    previous:     null
);
```

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `getMessage()` | `string` | The human-readable error description. |
| `getPath()` | `string` | Dot-notation path to the invalid field (e.g. `"user.address.city"`). Empty string at the root. |
| `getInvalidValue()` | `mixed` | The value that caused the failure. |
| `withPath(string $path)` | `static` | Returns a copy of the error with the path replaced by `$path`. |

---

## SchemaViolationException

`Wingman\Verix\Exceptions\SchemaViolationException` is thrown by `throwIfInvalid()`. It wraps all `ValidationFailureException` instances from the result.

### Constructor

```php
new SchemaViolationException(
    message: "The value is invalid.",
    code:    0,
    errors:  [$error1, $error2]
);
```

### Methods

| Method | Returns | Description |
| --- | --- | --- |
| `getMessage()` | `string` | The top-level message. |
| `getErrors()` | `ValidationFailureException[]` | All underlying validation errors. |

```php
try {
    Schema::from("{id: int<min=1>, name: string<minLength=1>}")
        ->validate(["id" => -1, "name" => ""])
        ->throwIfInvalid();
} catch (SchemaViolationException $e) {
    foreach ($e->getErrors() as $error) {
        printf("%-20s %s\n", $error->getPath(), $error->getMessage());
    }
}
```

---

## Strict Mode

Pass `strict: true` to `Schema->validate()` to disable coercions. In strict mode, a numeric string `"42"` will **not** satisfy an `int` constraint, and a `float` will not satisfy an `int` constraint.

```php
$schema = Schema::from("int");

$schema->validate("42")->isValid();              // true  (coerced)
$schema->validate("42", strict: true)->isValid(); // false (strict)
```

---

## Error Collection Strategy

Verix collects **all** violations in a single pass rather than stopping at the first failure. For struct and composite types, every field and sub-expression is tested, so you receive a complete diagnostic list:

```text
id          — The value is less than the specified minimum.
name        — String length must be at least 1.
email       — String must match the pattern /^[^@]+@[^@]+$/.
```

This avoids the frustrating "fix one error, discover the next" loop when validating user input.

---

## Exception Hierarchy

All Verix exceptions implement the `VerixException` marker interface, so you can catch them broadly:

```php
try {
    // ...
} catch (\Wingman\Verix\Exceptions\VerixException $e) {
    // catches any Verix exception
}
```

| Exception | Extends | When thrown |
| --- | --- | --- |
| `VerixException` | *(interface)* | Marker only — never thrown directly. |
| `InvalidParameterException` | `InvalidArgumentException` | A type parameter value is malformed or out of range. |
| `ParsingException` | `RuntimeException` | The DSL expression is syntactically invalid. |
| `TokenisationException` | `RuntimeException` | The tokeniser encounters an unrecognisable character. |
| `SchemaException` | `RuntimeException` | A `@SchemaRef` cannot be resolved, or two incompatible schemas are merged. |
| `SchemaViolationException` | `RuntimeException` | `throwIfInvalid()` is called on a failing `ValidationResult`. |
| `ValidationFailureException` | `RuntimeException` | Stored inside `ValidationResult::$errors`; rarely thrown directly. |

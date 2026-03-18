# Wingman Verix — Primitives

A primitive type is a named scalar type with an optional set of named, typed constraint parameters. Verix ships with seven built-in primitive types. Custom types can be registered at any time via the `Primitive` facade.

---

## Built-in Types

| Type | Extends | Description |
| --- | --- | --- |
| `any` | — | Accepts any value; never fails validation. |
| `bool` | `any` | A boolean value (`true` or `false`). |
| `string` | `any` | A UTF-8 string. |
| `number` | — | Any numeric value (integer or float). |
| `int` | `number` | An integer value. |
| `float` | `number` | A floating-point value. |
| `date` | `string` | A date string or `DateTimeImmutable` instance. |

---

## Parameters Reference

### `any`

No parameters. Accepts every value.

---

### `bool`

No parameters. Accepts PHP `true` and `false`.

---

### `string`

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `length` | `int` | — | Exact character length. |
| `minLength` | `int` | — | Minimum character length (inclusive). |
| `maxLength` | `int` | — | Maximum character length (inclusive). |
| `pattern` | `string` | — | PCRE regex the value must match (e.g. `"/^[a-z]+$/"`). |
| `charset` | `string` | — | [mb_list_encodings](https://www.php.net/mb_list_encodings) charset the value must be valid in. |
| `lowercase` | `bool` | — | When `true`, the string must be entirely lowercase. When `false`, it must *not* be lowercase. |
| `uppercase` | `bool` | — | When `true`, the string must be entirely uppercase. When `false`, it must *not* be uppercase. |

**Positional shorthand** (using curly braces):

| Count | Maps to |
| --- | --- |
| 1 | `length` |
| 2 | `minLength`, `maxLength` |

```php
Schema::from("string{10}");          // length = 10
Schema::from("string{1, 100}");      // minLength = 1, maxLength = 100
```

---

### `number`

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `min` | `float` | — | Minimum value (inclusive). |
| `max` | `float` | — | Maximum value (inclusive). |

**Positional shorthand** (using curly braces):

| Count | Maps to |
| --- | --- |
| 2 | `min`, `max` |

```php
Schema::from("number{0, 1000}");     // min = 0, max = 1000
```

---

### `int`

Extends `number`. Accepts PHP integers (and numeric strings in non-strict mode).

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `base` | `int` | `10` | Numeric base (2–36) used for string parsing and validation. |
| `min` | `float` | — | Minimum value (inclusive). |
| `max` | `float` | — | Maximum value (inclusive). |
| `length` | `int` | — | Exact number of digits in the decimal representation. |
| `minLength` | `int` | — | Minimum number of digits. |
| `maxLength` | `int` | — | Maximum number of digits. |
| `even` | `bool` | — | When `true`, the integer must be even. |
| `odd` | `bool` | — | When `true`, the integer must be odd. |
| `divisibleBy` | `int` | — | The integer must be divisible by this value (non-zero). |
| `unsigned` | `bool` | — | When `true`, the integer must be ≥ 0. |
| `bitDepth` | `int` | — | Must be one of `8`, `16`, `32`, or `64`. Constrains the value to fit the signed range for that bit width. |

**Positional shorthand** (using curly braces):

| Count | Maps to |
| --- | --- |
| 1 | `base` |
| 2 | `min`, `max` |
| 3 | `base`, `min`, `max` |

```php
Schema::from("int{1, 100}");         // min = 1, max = 100
Schema::from("int{16}");             // base = 16 (hexadecimal)
Schema::from("int{16, 0, 255}");     // base = 16, min = 0, max = 255
```

---

### `float`

Extends `number`. Accepts PHP `int` and `float` values (and numeric strings in non-strict mode).

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `min` | `float` | — | Minimum value (inclusive). |
| `max` | `float` | — | Maximum value (inclusive). |
| `precision` | `int` | — | Maximum number of decimal places. |

**Positional shorthand** (using curly braces):

| Count | Maps to |
| --- | --- |
| 1 | `precision` |
| 2 | `min`, `max` |
| 3 | `precision`, `min`, `max` |

```php
Schema::from("float{2}");            // precision = 2
Schema::from("float{0.0, 1.0}");     // min = 0.0, max = 1.0
Schema::from("float{2, 0.0, 1.0}"); // precision = 2, min = 0.0, max = 1.0
```

---

### `date`

Extends `string`. Accepts date strings (parsed with [`DateTimeImmutable::createFromFormat`](https://www.php.net/DateTimeImmutable.createFromFormat)) or `DateTimeImmutable` instances.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `format` | `string` | `"Y-m-d"` | PHP date format string used to parse and validate the value. |
| `strict` | `bool` | `true` | When `true`, rejects calendar overflows (e.g. `"2024-02-31"`). |
| `after` | `string` | — | The date must be strictly after this date string. |
| `before` | `string` | — | The date must be strictly before this date string. |
| `isFuture` | `bool` | — | When `true`, the date must be in the future. |
| `isPast` | `bool` | — | When `true`, the date must be in the past. |
| `dayOfWeek` | `array` | — | Array of allowed ISO day-of-week numbers (1 = Monday … 7 = Sunday). |
| `timezone` | `string` | — | A valid PHP timezone identifier the date is interpreted in. |

```php
Schema::from('date<format="d/m/Y", after="2020-01-01", isFuture=true>');
```

---

## Registering Custom Primitive Types

### `Primitive::register()`

```php
use Wingman\Verix\Facades\Primitive;

Primitive::register(
    name:                 "slug",
    validator:            fn ($v) => is_string($v) && preg_match('/^[a-z0-9\-]+$/', $v),
    extends:              "string",
    parameters:           [],
    parser:               null,
    description:          "A URL-safe slug string.",
    errorCallback:        fn ($v) => "Expected a slug, got: " . var_export($v, true),
    positionalParamMapper: null,
    registry:             null
);
```

| Argument | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `string` | *(required)* | The type name used in DSL expressions. |
| `validator` | `callable` | *(required)* | A callable receiving `(mixed $value, array $params)` that returns `bool`. |
| `extends` | `string\|null` | `null` (`"any"`) | The parent type name. Validators of ancestor types are also run. |
| `parameters` | `array` | `[]` | Array of `Parameter` instances defining the accepted parameters. |
| `parser` | `callable\|null` | `null` | Optional pre-validation coercion: receives `(mixed $value, array $params)` and returns the coerced value. |
| `description` | `string\|null` | `null` | Human-readable description shown in error messages and tooling. |
| `errorCallback` | `callable\|null` | `null` | Generates the base-type failure message: receives `(mixed $value)` and returns `string`. |
| `positionalParamMapper` | `callable\|null` | `null` | Maps positional param arrays to named params: receives `(array $values)` and returns `array`. |
| `registry` | `Registry\|string\|null` | `null` | Registry to register into. Defaults to `"default"`. |

`register()` returns the new `PrimitiveDefinition` and emits the `PRIMITIVE_REGISTERED` signal if Corvus is available.

---

### `Primitive::registerClass()`

Registers an existing PHP class as a primitive type. The class must implement the `Type` contract or be locatable by the `TypeClassLocator`.

```php
use Wingman\Verix\Facades\Primitive;

Primitive::registerClass(\App\Types\PhoneNumberType::class);
```

---

### `Primitive::alias()`

Creates one or more aliases for an existing primitive type. Aliases behave identically to the original type but appear under a different name in DSL expressions.

```php
use Wingman\Verix\Facades\Primitive;

// Single alias
Primitive::alias("integer", "int");

// Multiple aliases at once
Primitive::alias(["str", "text"], "string");
```

Aliases are derived from the base `PrimitiveDefinition` via `derive()` and include any default parameters of the original.

---

## PrimitiveDefinition

`PrimitiveDefinition` is the value object that represents a registered primitive type. It is returned by `Primitive::register()` and stored in the `PrimitiveRegistry`.

### Key properties

| Property | Type | Description |
| --- | --- | --- |
| `name` | `string` | The canonical type name. |
| `parent` | `string` | The parent type name (default `"any"`). |
| `description` | `string\|null` | Human-readable description. |

### `PrimitiveDefinition::derive(string $alias, array $params = []): PrimitiveDefinition`

Creates a new `PrimitiveDefinition` that inherits all properties of the current one but uses a different name and optionally a different default parameter set. Used internally by `Primitive::alias()`.

---

## Parameter Spec

A `Parameter` describes one accepted parameter for a type. It is used when building custom types.

### Constructor arguments

| Argument | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `string` | *(required)* | The parameter name, used in DSL expressions and error messages. |
| `type` | `string` | *(required)* | The expected PHP type of the parameter value. |
| `default` | `mixed` | `null` | The default value when the parameter is omitted. |
| `required` | `bool` | `false` | Whether the parameter must always be supplied. |
| `positional` | `int\|null` | `null` | If set, the zero-based positional index this parameter occupies. |
| `description` | `string\|null` | `null` | Human-readable description. |
| `constraint` | `callable\|null` | `null` | Validates the parameter value itself (not the input value). Returns `bool`. |
| `constraintError` | `string\|null` | `null` | Error message when `constraint` fails. |
| `validator` | `callable\|null` | `null` | Tests the input value against this parameter. Receives `(mixed $value, mixed $paramValue)`. Returns `bool`. |
| `validatorError` | `string\|callable\|null` | `null` | Error message template when `validator` fails. Use `{paramName}` placeholders. |

```php
use Wingman\Verix\Specs\Parameter;

new Parameter(
    name:           "minLength",
    type:           "int",
    constraint:     fn ($v) => $v >= 0,
    constraintError: "minLength must be non-negative.",
    validator:      fn ($value, $min) => strlen($value) >= $min,
    validatorError: "String must be at least {minLength} characters."
);
```

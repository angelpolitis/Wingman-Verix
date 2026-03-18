# Wingman Verix — DSL Reference

Verix uses a compact string syntax to describe types. Expressions can be embedded anywhere you pass a type string — `Schema::from()`, struct field definitions, parameter defaults, and so on.

---

## Primitives

A primitive is a named type, optionally followed by a parameter block.

```
string
int
float
number
bool
date
any
```

### Named Parameters

Named parameters are written inside **angle brackets** (`< >`), as comma-separated `key=value` pairs.

```
int<min=0, max=100>
string<minLength=1, maxLength=255>
date<format="Y-m-d">
```

### Positional Parameters

Positional parameters are written inside **curly braces** (`{ }`), as a comma-separated list of values in the order defined by the type's `Parameter` spec. Not all types support positional parameters; a `ParsingException` is thrown if the type has no positional parameter mapper.

```
string{32}           // length = 32
string{1, 100}       // minLength = 1, maxLength = 100
int{1, 100}          // min = 1, max = 100
float{2}             // precision = 2
```

```php
Schema::from("int{1, 100}");     // equivalent to int<min=1, max=100>
Schema::from("string{3, 50}");   // equivalent to string<minLength=3, maxLength=50>
```

Custom types registered via `Primitive::register()` support positional parameters when a `$positionalParamMapper` callback is provided.

---

## Null

The literal keyword `null` matches only the PHP value `null`.

```
null
```

---

## Any

The keyword `any` matches every value without constraint.

```
any
```

---

## Optional Suffix

Appending `?` to a type is syntactic sugar for `T | null`.

```
string?          // equivalent to: string | null
int<min=0>?      // equivalent to: int<min=0> | null
{name: string}?  // equivalent to: {name: string} | null
```

---

## Unions

Two or more types separated by `|`. The value must match **at least one** branch.

```
string | int
'active' | 'inactive' | 'pending'
string | int | null
```

Unions are left-associative and have lower precedence than all other constructs, so they bind last.

---

## Intersections

Two or more types separated by `&`. The value must satisfy **every** branch simultaneously.

```
string & email        // must be a string AND pass the "email" custom type
Serializable & Countable
```

Intersections have higher precedence than unions.

---

## Literals

A literal matches one exact value.

**String literal** — single-quoted:

```
'active'
'pending'
'hello world'
```

**Integer literal** — bare digits:

```
42
0
-1
```

Literals are most useful inside unions to model allowed values without registering a custom type.

---

## Arrays

An array expression matches a PHP array where every element satisfies the item type.

```
string[]
int[]
{name: string}[]
(string | int)[]
```

Complex item types are wrapped in parentheses when needed.

---

## Structs

A struct matches a PHP associative array (or object) with specific keys.

### Exact struct

All listed keys are required. No extra keys are allowed.

```
{name: string, age: int}
```

### Open struct

Trailing `...` permits extra keys with any value.

```
{name: string, ...}
```

### Optional fields

A `?` after the field name marks it as optional (may be missing or `null`).

```
{name: string, nickname?: string, age?: int}
```

### Keyed (index) struct

Matches any associative array where every key satisfies the key type and every value satisfies the value type.

```
{[string]: int}
{[int]: bool}
```

---

## Tuples

A tuple matches a PHP array where each positional element satisfies its corresponding type. The array length must match exactly.

```
[string, int, bool]
[string, float, string]
```

Tuple elements are sequentially typed — the first element must satisfy `string`, the second `int`, and so on.

---

## Enums

An enum matches any value that equals one of the listed literals, similar to a literal union but with explicit intent.

```
enum{'active' | 'inactive' | 'pending'}
enum{1 | 2 | 3}
```

---

## Schema References

A `@Name` token is a reference to a named schema registered in the current registry. It is expanded during normalisation.

```
@UserSchema
@Address
@ProductVariant
```

```php
use Wingman\Verix\Facades\Schema;

Schema::register("Address", "{
    street:  string,
    city:    string,
    country: string<minLength=2, maxLength=2>
}");

Schema::register("User", "{
    id:      int<min=1>,
    name:    string,
    address: @Address
}");
```

---

## Precedence & Associativity

From **highest** to **lowest**:

| Level | Constructs |
| --- | --- |
| 1 (highest) | Parentheses `(…)`, structs `{…}`, tuples `[…]`, enums `enum{…}`, literals, primitives, `any`, `null`, `@Ref` |
| 2 | Optional suffix `T?` |
| 3 | Array suffix `T[]` |
| 4 | Intersection `A & B` |
| 5 (lowest) | Union `A \| B` |

Parentheses can always be used to override precedence:

```
(string | int)[]     // array of (string or int)
string | (int[])     // string or (array of int)
```

---

## Complex Examples

```php
// Nullable array of objects with an optional sub-field
Schema::from("{id: int, tags?: string[]}[]?");

// A value that is either a literal or a typed primitive
Schema::from("'unknown' | int<min=0>");

// A deeply nested struct
Schema::from("{
    user: {
        id:    int<min=1>,
        roles: enum{'admin' | 'editor' | 'viewer'}[]
    },
    meta?: {[string]: any}
}");

// A tagged-union style via intersection with a literal
Schema::from("{type: 'circle', radius: float} | {type: 'rect', width: float, height: float}");
```

---

## Notes

- Whitespace (spaces, tabs, newlines) is ignored everywhere in a DSL expression.
- Quoting inside parameter values follows PHP string rules; only single-quoted values are supported in the DSL.
- Nested expressions of arbitrary depth are supported.
- A `ParsingException` is thrown at parse time (not validation time) for malformed expressions, so errors surface immediately when a schema is first constructed.

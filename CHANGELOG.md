# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — 2026-03-17

### Added

#### Facades
- `Schema` facade with `from()`, `parse()`, `register()`, `expand()`, `infer()`, `merge()`, `validate()`, `getNode()`, `getExpression()`, and `serialise()` methods.
- `Primitive` facade with `register()`, `registerClass()`, and `alias()` for managing primitive types.

#### DSL Parsing Pipeline
- `TypeTokeniser` — converts a DSL expression string into a flat token stream.
- `TypeParser` — transforms the token stream into an abstract syntax tree of `Node` objects.
- `TypeNormaliser` — resolves optional suffixes, expands schema references, and canonicalises the AST.
- `StructMerger` — merges two compatible struct nodes, used internally by `Schema::merge()`.

#### Node Types
- `ArrayNode` — typed array expressions (e.g. `string[]`).
- `AnyNode` — the top-level wildcard type (e.g. `any`).
- `ClassNode` — references to a class registered via `ClassRegistry`.
- `EnumNode` — literal-union enumerations (e.g. `enum{'a' | 'b' | 'c'}`).
- `IntersectionNode` — intersections of two or more types (e.g. `A & B`).
- `LiteralNode` — exact string or integer literals (e.g. `'active'`, `42`).
- `NullNode` — the `null` type.
- `PrimitiveNode` — primitive-type references with optional parameters.
- `SchemaRefNode` — references to named schemata (e.g. `@UserSchema`).
- `StructNode` — open/exact structs and tuple-structs (e.g. `{name: string, ...}`).
- `KeyedStructNode` — keyed index structs (e.g. `{[string]: int}`).
- `TupleNode` — fixed-length positional tuples (e.g. `[string, int, bool]`).
- `UnionNode` — union of two or more types (e.g. `string | null`).

#### Built-in Primitive Types
- `any` — accepts any value without constraints.
- `bool` — boolean values.
- `string` — strings with optional `minLength`, `maxLength`, `pattern`, and `encoding` parameters.
- `number` — numeric values with optional `min`, `max`, `step`, and `multipleOf` parameters.
- `int` — integer values extending `number`.
- `float` — floating-point values extending `number`.
- `date` — date strings with optional `format`, `minDate`, and `maxDate` parameters.

#### Registry System
- `Registry` — named, cached singleton registry factory.
- `PrimitiveRegistry` — stores `PrimitiveDefinition` instances, keyed by type name.
- `SchemaRegistry` — stores named `Schema` instances.
- `ClassRegistry` — stores class name → type name mappings for auto-discovery.
- `TypeClassLocator` — resolves PHP class names to registered types, with optional file-system caching.

#### Specs
- `Parameter` — specification for a single type parameter: name, type, default, required, positional index, and description.
- `StructField` — specification for a named struct field: name, schema, optional flag.
- `RestField` — specification for a struct rest/spread element.
- `PrimitiveDefinition` — encapsulates a registered primitive: name, validator, parent name, parameters, description, and aliases. Includes `derive()` for inheritance.

#### Validation
- `ValidationResult` — value object representing pass/fail with value, path, errors, and metadata. Factories: `ok()`, `error()`. Chain methods: `withPath()`, `withValue()`, `withErrors()`, `withMetadata()`. `merge()` for combining results. `throwIfInvalid()` convenience method.
- `ValidationError` — single validation error with message, path, value, and code.

#### Exception Hierarchy
- `VerixException` — marker interface for all Verix exceptions.
- `InvalidParameterException` — malformed or missing type parameter.
- `ParsingException` — thrown by `TypeParser` on malformed DSL.
- `SchemaException` — general schema construction or resolution error.
- `SchemaViolationException` — thrown by `throwIfInvalid()` when validation fails.
- `TokenisationException` — thrown by `TypeTokeniser` on unrecognisable input.
- `ValidationFailureException` — thrown when a validation pipeline encounters a fatal structural mismatch.

#### Corvus Bridge
- `CorvisBridge` — emits typed `Signal` events via Wingman/Corvus when the module is present. Falls back to a null stub when Corvus is unavailable. Includes a re-entrancy guard to prevent recursive emissions.
- `Signal` enum — defines `CLASS_REGISTERED`, `PRIMITIVE_REGISTERED`, `SCHEMA_PARSED`, `SCHEMA_REGISTERED`, and `SCHEMA_VALIDATED` lifecycle signals with structured payloads.

#### Argus Bridge
- `SchemaMatcher` — Argus `Matcher` implementation for asserting schema conformance in tests.
- `Asserter` trait — provides `assertSchema()` and `assertNotSchema()` helper methods for Argus test classes.

#### Test Suite
- 186 tests across 9 test files covering DSL parsing, struct merging, primitive types, schema inference, validation, schema references, TypeClassLocator, `SchemaViolationException`, and the `Primitive` facade.

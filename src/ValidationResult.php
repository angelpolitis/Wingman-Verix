<?php
    /*/
	 * Project Name:    Wingman — Verix — Validation Result
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix namespace.
    namespace Wingman\Verix;

    # Import the following classes to the current scope.
    use Wingman\Verix\Exceptions\SchemaViolationException;
    use Wingman\Verix\Exceptions\ValidationFailureException;

    /**
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ValidationResult {
        /**
         * The validation errors.
         * @var ValidationFailureException[]
         */
        public readonly array $errors;

        /**
         * The metadata associated with a validation.
         * @var array
         */
        public readonly array $metadata;

        /**
         * Whether the validation was successful.
         * @var bool
         */
        public readonly bool $valid;

        /**
         * The value.
         * @var mixed
         */
        public readonly mixed $value;

        /**
         * Creates a new validation result.
         * @param mixed $value The value.
         * @param bool $valid Whether the validation was successful.
         * @param ValidationFailureException[] $errors The validation errors.
         * @param array $metadata The metadata associated with the validation.
         */
        public function __construct (mixed $value, bool $valid, array $errors = [], array $metadata = []) {
            $this->value = $value;
            $this->valid = $valid;
            $this->errors = $errors;
            $this->metadata = $metadata;
        }

        /**
         * Gets the errors of a result.
         * @return ValidationFailureException[] The errors of the result.
         */
        public function getErrors () : array {
            return $this->errors;
        }

        /**
         * Gets the metadata of a result.
         * @return array The metadata.
         */
        public function getMetadata () : array {
            return $this->metadata;
        }

        /**
         * Gets the value of a result.
         * @return mixed The value.
         */
        public function getValue () : mixed {
            return $this->value;
        }

        /**
         * Checks whether the validation was successful.
         * @return bool Whether the validation was successful.
         */
        public function isValid () : bool {
            return $this->valid;
        }

        /**
         * Creates a successful validation result.
         * @return static The validation result.
         */
        public static function ok (mixed $value) : static {
            return new static($value, true);
        }

        /**
         * Creates a failed validation result.
         * @param string $message The error message.
         * @param mixed $value The invalid value.
         * @return static The validation result.
         */
        public static function error (string $message, mixed $value) : static {
            return new static($value, false, [new ValidationFailureException($message, "", $value)]);
        }

        /**
         * Merges a validation result with another.
         * @param self $other The other validation result.
         * @return static The merged validation result.
         */
        public function merge (self $other) : static {
            if ($this->valid && $other->valid) {
                return static::ok($this->value);
            }
            return new static($other->value ?? $this->value, false, array_merge($this->errors, $other->errors));
        }
        
        /**
         * Throws a schema violation exception if a validation failed.
         * @return static The validation result.
         * @throws SchemaViolationException If the validation failed.
         */
        public function throwIfInvalid () : static {
            if (!$this->valid) {
                throw new SchemaViolationException(message: "The value " . var_export($this->value, true) . " is invalid.", errors: $this->errors);
            }
            return $this;
        }

        /**
         * Creates a new result with modified properties.
         * @param array{value?: mixed, valid?: bool, errors?: array, metadata?: array} $options The properties to modify.
         * @param bool $replaceMetadata Whether to replace the metadata instead of merging it.
         * @return static The result.
         */
        public function with (array $options, bool $replaceMetadata = false) : static {
            $value = $options["value"] ?? $this->value;
            $valid = $options["valid"] ?? $this->valid;
            $errors = $options["errors"] ?? [];
            $metadata = $options["metadata"] ?? [];

            $errors = array_merge($this->errors, $errors);
            $metadata = $replaceMetadata ? $metadata : array_merge($this->metadata, $metadata);

            return new static($value, $valid, $errors, $metadata);
        }

        /**
         * Creates a new result with added errors.
         * @param array $errors The errors to add.
         * @return static The result.
         */
        public function withErrors (array $errors) : static {
            if ($this->valid) return $this;
            return new static($this->value, false, array_merge($this->errors, $errors));
        }

        /**
         * Creates a new result with added metadata.
         * @param array $metadata The metadata to add.
         * @param bool $replace Whether to replace the metadata instead of merging it.
         * @return static The result.
         */
        public function withMetadata (array $metadata, bool $replace = false) : static {
            $metadata = $replace ? $metadata : array_merge($this->metadata, $metadata);
            return new static($this->value, $this->valid, $this->errors, $metadata);
        }

        /**
         * Creates a new result with an added path.
         * @param string $path The path to add.
         * @return static The result.
         */
        public function withPath (string $path) : static {
            if ($this->valid) return $this;
            $errors = array_map(fn ($error) => $error->withPath($path), $this->errors);
            return new static($this->value, false, $errors);
        }

        /**
         * Creates a new result with a different value.
         * @param mixed $value The new value.
         * @return static The result.
         */
        public function withValue (mixed $value) : static {
            return new static($value, $this->valid, $this->errors);
        }
    }
?>
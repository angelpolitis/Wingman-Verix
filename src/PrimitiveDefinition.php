<?php
    /*/
	 * Project Name:    Wingman — Verix — Primitive Definition
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix namespace.
    namespace Wingman\Verix;

    # Import the following classes to the current scope.
    use Closure;
    use RuntimeException;
    use Wingman\Verix\Specs\Parameter;

    /**
     * Represents the definition of a primitive type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PrimitiveDefinition {
        /**
         * The applied parameters of a primitive type.
         * @var array<string, mixed>
         */
        protected array $appliedParams = [];

        /**
         * The names of parameters that aren't exposed externally.
         * @var array<string>
         */
        protected array $hiddenParams = [];

        /**
         * The name of a primitive type.
         * @var string
         */
        public readonly string $name;

        /**
         * The parent type of a primitive type.
         * @var string
         */
        public readonly string $parent;

        /**
         * The parameters of a primitive type.
         * @var array<string, Parameter>
         */
        public readonly array $parameters;

        /**
         * The validator function of a primitive type.
         * @var Closure
         */
        public readonly Closure $validator;

        /**
         * The parser function of a primitive type.
         * @var Closure|null
         */
        public readonly ?Closure $parser;

        /**
         * The description of a primitive type.
         * @var string|null
         */
        public readonly ?string $description;

        /**
         * The error callback function of a primitive type.
         * @var Closure|null
         */
        public readonly ?Closure $errorCallback;

        /**
         * The positional parameter mapper function of a primitive type.
         * @var Closure|null
         */
        public ?Closure $positionalParamMapper;

        /**
         * Creates a new primitive type definition.
         * @param string $name The name of the primitive type.
         * @param string $parent The parent type of the primitive type.
         * @param callable $validator The validator function for the primitive type.
         * @param array<string, Parameter> $parameters The parameters of the primitive type.
         * @param callable|null $parser The parser function for the primitive type.
         * @param string|null $description The description of the primitive type.
         * @param callable|null $errorCallback The error callback function for the primitive type.
         * @param callable|null $positionalParamMapper The positional parameter mapper for the primitive type.
         * @param array<string, mixed> $appliedParams The applied parameters for the primitive type.
         */
        public function __construct (
            string $name,
            string $parent,
            callable $validator,
            array $parameters = [],
            ?callable $parser = null,
            ?string $description = null,
            ?callable $errorCallback = null,
            ?callable $positionalParamMapper = null,
            array $appliedParams = []
        ) {
            $this->name = $name;
            $this->parent = $parent;
            $this->description = $description;
            if (!$validator instanceof Closure) {
                $this->validator = fn (...$args) => $validator(...$args);
            }
            else $this->validator = $validator;
            if (!is_null($parser)) {
                if ($parser instanceof Closure) {
                    $this->parser = $parser;
                }
                else $this->parser = fn (...$args) => $parser(...$args);
            }
            if (!is_null($errorCallback)) {
                if ($errorCallback instanceof Closure) {
                    $this->errorCallback = $errorCallback;
                }
                else $this->errorCallback = fn (...$args) => $errorCallback(...$args);
            }
            if (!is_null($positionalParamMapper)) {
                if ($positionalParamMapper instanceof Closure) {
                    $this->positionalParamMapper = $positionalParamMapper;
                }
                else $this->positionalParamMapper = fn (...$args) => $positionalParamMapper(...$args);
            }

            $params = [];
            foreach ($parameters as $param) {
                $params[$param->getName()] = $param;
            }
            $this->parameters = $params;

            $this->appliedParams = $appliedParams;
            $this->hiddenParams = array_keys($appliedParams);
        }

        /**
         * Derives a new primitive type definition from the current one.
         * @param string $name The name of the derived primitive type.
         * @param array<string, mixed> $params The parameters for the derived primitive type.
         * @return static The derived primitive type definition.
         */
        public function derive (string $name, array $params) : static {
            $parentMapper = $this->positionalParamMapper;

            $derived = new static(
                $name,
                $this->name,
                $this->validator,
                $this->parameters,
                $this->parser,
                $this->description,
                $this->errorCallback,
                $this->positionalParamMapper,
                $params
            );

            $positionalParamMapper = function (array $values) use ($parentMapper, $derived) {
                $mapped = $parentMapper($values);

                foreach ($derived->hiddenParams as $param) {
                    if (array_key_exists($param, $mapped)) {
                        throw new RuntimeException("Positional parameters cannot target '{$param}' for type {$derived->name}.");
                    }
                }

                return $mapped;
            };

            $derived->positionalParamMapper = $positionalParamMapper;

            return $derived;
        }

        /**
         * Gets the description of a primitive type.
         * @return string|null The description of the primitive type.
         */
        public function getDescription () : ?string {
            return $this->description;
        }

        /**
         * Gets the error callback of a primitive type.
         * @return Closure|null The error callback function or `null` if not defined.
         */
        public function getErrorCallback () : ?Closure {
            return $this->errorCallback;
        }

        /**
         * Gets the name of a primitive type.
         * @return string The name of the primitive type.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the parameters of a primitive type.
         * @return array<string, Parameter> The parameters of the primitive type.
         */
        public function getParams () : array {
            return array_diff_key($this->parameters, array_flip($this->hiddenParams));
        }

        /**
         * Gets the parent type of a primitive type.
         * @return string The parent type of the primitive type.
         */
        public function getParent () : string {
            return $this->parent;
        }

        /**
         * Gets the parser of a primitive type.
         * @return Closure|null The parser function or `null` if not defined.
         */
        public function getParser () : ?Closure {
            return $this->parser;
        }

        /**
         * Gets the positional parameter mapper of a primitive type.
         * @return Closure|null The positional parameter mapper function or `null` if not defined.
         */
        public function getPositionalParamMapper () : ?Closure {
            return $this->positionalParamMapper;
        }

        /**
         * Gets the validator of a primitive type.
         * @return Closure The validator function.
         */
        public function getValidator () : Closure {
            return $this->validator;
        }

        /**
         * Checks whether a parameter exists in the primitive type definition.
         * @param string $name The name of the parameter.
         * @return bool Whether the parameter exists.
         */
        public function hasParam (string $name) : bool {
            return array_key_exists($name, $this->parameters) && !in_array($name, $this->hiddenParams);
        }

        /**
         * Parses a value according to a primitive type definition.
         * @param mixed $value The value to parse.
         * @param array $params The parameters for parsing.
         * @return mixed The parsed value.
         */
        public function parse (mixed $value, array $params = []) : mixed {
            if ($this->parser) {
                $value = ($this->parser)($value, $params);
            }
            return $value;
        }

        /**
         * Validates a value against a primitive type definition.
         * @param mixed $value The value to validate.
         * @param array $params The parameters for validation.
         * @return ValidationResult The result of the validation.
         */
        public function validate (mixed $value, array $params = []) : ValidationResult {
            # (1) Merge applied parameters with provided parameters.
            $params = array_merge($this->appliedParams, $params);

            # (2) Check the main validator.
            if (!($this->validator)($value, $params)) {
                # (a) Use the custom error callback if defined.
                if (isset($this->errorCallback)) {
                    $message = ($this->errorCallback)($value, $params);

                    $v = is_scalar($value) ? $value : gettype($value);
                    $message = str_replace("{value}", $v, $message);

                    return ValidationResult::error($message, $value);
                }
        
                # (b) A generic fallback.
                return ValidationResult::error("Expected {$this->name}", $value);
            }

            # (3) Check parameter-specific validators.
            foreach ($this->parameters as $paramName => $param) {
                $paramValue = $params[$paramName] ?? null;

                if (!array_key_exists($paramName, $params)) {
                    if ($param->isRequired()) {
                        return ValidationResult::error("Missing required parameter: {$paramName}", $value);
                    }
                }
                else if ($param->getConstraint() && !$param->getConstraint()($paramValue, $params, $this->parameters)) {
                    $message = $param->getConstraintError($paramValue, $params, $this->parameters) ?? "Parameter constraint failed: {$paramName}";

                    $paramValue = is_scalar($paramValue) ? $paramValue : gettype($paramValue);
                    $v = is_scalar($value) ? $value : gettype($value);

                    $message = str_replace(["{value}", '{' . $paramName . '}'], [$v, $paramValue], $message);

                    return ValidationResult::error($message, $paramValue);
                }
                else if ($param->getValidator() && !$param->getValidator()($value, $paramValue, $params, $this->parameters)) {
                    $message = $param->getValidatorError($value, $paramValue, $params, $this->parameters) ?? "Parameter validator failed: {$paramName}";

                    $paramValue = is_scalar($paramValue) ? $paramValue : gettype($paramValue);
                    $v = is_scalar($value) ? $value : gettype($value);

                    $message = str_replace(["{value}", '{' . $paramName . '}'], [$v, $paramValue], $message);

                    return ValidationResult::error($message, $value);
                }
            }

            return ValidationResult::ok($value);
        }
    }
?>
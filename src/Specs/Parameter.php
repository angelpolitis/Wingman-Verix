<?php
    /*/
	 * Project Name:    Wingman — Verix — Parameter Specification
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Dec 24 2025
    /*/

    # Use the Verix.Specs namespace.
    namespace Wingman\Verix\Specs;

    # Import the following classes to the current scope.
    use Closure;

    /**
     * Represents a parameter specification.
     * @package Wingman\Verix\Specs
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Parameter {
        /**
         * The name of a parameter.
         * @var string
         */
        public readonly string $name;

        /**
         * The type of a parameter.
         * @var string
         */
        public readonly string $type;

        /**
         * Whether a parameter is required.
         * @var bool
         */
        public readonly bool $required;

        /**
         * The default value of a parameter.
         * @var mixed
         */
        public readonly mixed $default;

        /**
         * A constraint callable for a parameter.
         * @var Closure|null
         */
        public readonly Closure $constraint;

        /**
         * An error message for a parameter's constraint.
         * @var Closure|null
         */
        public readonly ?Closure $constraintError;

        /**
         * A validator callable for a parameter.
         * @var Closure
         */
        public readonly Closure $validator;

        /**
         * An error message for a parameter's validator.
         * @var Closure|null
         */
        public readonly ?Closure $validatorError;

        /**
         * Creates a new parameter specification.
         * @param string $name The name of the parameter.
         * @param string $type The type of the parameter.
         * @param bool $required Whether the parameter is required.
         * @param mixed $default The default value of the parameter.
         * @param callable|null $constraint An optional constraint callable for the parameter.
         * @param callable|string|null $constraintError An optional error message for the parameter.
         * @param callable|null $validator An optional validator callable for the parameter.
         * @param callable|string|null $validatorError An optional error message for the parameter's validator.
         */
        public function __construct (
            string $name,
            string $type,
            bool $required = false,
            mixed $default = null,
            ?callable $constraint = null,
            callable|string|null $constraintError = null,
            ?callable $validator = null,
            callable|string|null $validatorError = null
        ) {
            $this->name = $name;
            $this->type = $type;
            $this->required = $required;
            $this->default = $default;
            if (!is_null($constraint)) {
                if ($constraint instanceof Closure) {
                    $this->constraint = $constraint;
                }
                else $this->constraint = fn (...$args) => $constraint(...$args);
            }
            if (!is_null($validator)) {
                if ($validator instanceof Closure) {
                    $this->validator = $validator;
                }
                else $this->validator = fn (...$args) => $validator(...$args);
            }
            else $this->validator = fn () => true;

            if (is_callable($constraintError)) {
                $this->constraintError = fn (...$args) => $constraintError(...$args);
            }
            else if (is_string($constraintError)) {
                $this->constraintError = fn () => $constraintError;
            }
            else $this->constraintError = null;

            if (is_callable($validatorError)) {
                $this->validatorError = fn (...$args) => $validatorError(...$args);
            }
            else if (is_string($validatorError)) {
                $this->validatorError = fn () => $validatorError;
            }
            else $this->validatorError = null;
        }

        /**
         * Gets the constraint callable of a parameter.
         * @return ?Closure The constraint callable, or null if none is set.
         */
        public function getConstraint () : ?Closure {
            return $this->constraint ?? null;
        }

        /**
         * Gets the constraint error message of a parameter.
         * @param mixed $value The parameter value.
         * @param array $params The full set of parameters.
         * @param static[] $paramSpecs The full set of parameter specifications.
         * @return ?string The constraint error message, or `null` if none is set.
         */
        public function getConstraintError (mixed $value, array $params, array $paramSpecs) : ?string {
            return $this->constraintError ? ($this->constraintError)($value, $params, $paramSpecs) : null;
        }

        /**
         * Gets the default value of a parameter.
         * @return mixed The default value of the parameter.
         */
        public function getDefault () : mixed {
            return $this->default;
        }

        /**
         * Gets the name of a parameter.
         * @return string The name of the parameter.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the type of a parameter.
         * @return string The type of the parameter.
         */
        public function getType () : string {
            return $this->type;
        }

        /**
         * Gets the validator callable of a parameter.
         * @return Closure The validator callable.
         */
        public function getValidator () : Closure {
            return $this->validator;
        }

        /**
         * Gets the validator error message of a parameter.
         * @param mixed $value The value being validated.
         * @param mixed $paramValue The parameter value.
         * @param array $params The full set of parameters.
         * @param static[] $paramSpecs The full set of parameter specifications.
         * @return ?string The validator error message, or `null` if none is set.
         */
        public function getValidatorError (mixed $value, mixed $paramValue, array $params, array $paramSpecs) : ?string {
            return $this->validatorError ? ($this->validatorError)($value, $paramValue, $params, $paramSpecs) : null;
        }

        /**
         * Checks whether a parameter is required.
         * @return bool Whether the parameter is required.
         */
        public function isRequired () : bool {
            return $this->required;
        }
    
        /**
         * Validates a value against a parameter specification.
         * @param mixed $value The value to validate.
         * @return bool Whether the value is valid.
         */
        public function validate (mixed $value) : bool {
            if ($value === null) {
                return !$this->required;
            }
    
            return match ($this->type) {
                "int" => is_int($value),
                "float" => is_float($value) || is_int($value),
                "string" => is_string($value),
                "bool" => is_bool($value),
                default  => false
            } && ($this->constraint ? ($this->constraint)($value) : true);
        }
    }
?>
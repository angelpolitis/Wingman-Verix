<?php
    /*/
     * Project Name:    Wingman — Verix — Schema Violation Exception
     * Created by:      Angel Politis
     * Creation Date:   Feb 19 2026
     * Last Modified:   Feb 19 2026
    /*/

    # Use the Verix namespace.
    namespace Wingman\Verix\Exceptions;

    # Import the following classes to the current scope.
    use RuntimeException;

    /**
     * Represents a schema violation exception.
     * @package Wingman\Verix\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SchemaViolationException extends RuntimeException {
        /**
         * The errors that caused the schema violation.
         * @var ValidationFailureException[]
         */
        protected array $errors;

        /**
         * Creates a new schema violation exception.
         * @param string $message The error message.
         * @param int $code The error code.
         * @param ValidationFailureException[] $errors The errors that caused the schema violation.
         */
        public function __construct (string $message = "A schema violation occurred.", int $code = 0, array $errors = []) {
            parent::__construct($message, $code);
            $this->errors = $errors;
        }

        /**
         * Gets the errors that caused a schema violation.
         * @return ValidationFailureException[] The errors that caused the schema violation.
         */
        public function getErrors () : array {
            return $this->errors;
        }
    }
?>
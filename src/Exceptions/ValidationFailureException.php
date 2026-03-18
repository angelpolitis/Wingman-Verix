<?php
    /**
     * Project Name:    Wingman Verix - Validation Failure Exception
     * Created by:      Angel Politis
     * Creation Date:   Feb 19 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix namespace.
    namespace Wingman\Verix\Exceptions;

    # Import the following classes to the current scope.
    use RuntimeException;
    use Throwable;
    use Wingman\Verix\Exceptions\VerixException;

    /**
     * Represents a validation failure exception.
     * @package Wingman\Verix\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ValidationFailureException extends RuntimeException implements VerixException {
        /**
         * The error message.
         * @var string
         */
        protected $message;

        /**
         * The path to the invalid value.
         * @var string
         */
        protected string $path;

        /**
         * The invalid value.
         * @var mixed
         */
        protected mixed $invalidValue;

        /**
         * The error code.
         * @var int
         */
        protected $code = 0;

        /**
         * The previous throwable.
         * @var Throwable|null
         */
        protected ?Throwable $previous = null;

        /**
         * Creates a new validation failure exception.
         * @param string $message The error message.
         * @param string $path The path to the invalid value.
         * @param mixed $invalidValue The invalid value.
         * @param int $code The error code.
         * @param Throwable|null $previous The previous throwable.
         */
        public function __construct (string $message, string $path, mixed $invalidValue, int $code = 0, ?Throwable $previous = null) {
            parent::__construct($message, $code, $previous);
            $this->path = $path;
            $this->invalidValue = $invalidValue;
        }

        /**
         * Gets the invalid value of a validation failure exception.
         * @return mixed The invalid value.
         */
        public function getInvalidValue () : mixed {
            return $this->invalidValue;
        }

        /**
         * Gets the path to the invalid value of a validation failure exception.
         * @return string The path to the invalid value.
         */
        public function getPath () : string {
            return $this->path;
        }

        /**
         * Creates a new validation failure exception with a path.
         * @param string $path The path to the invalid value.
         * @return static The validation failure exception with the specified path.
         */
        public function withPath (string $path) : static {
            return new static($this->message, $path, $this->invalidValue, $this->code, $this->previous);
        }
    }
?>
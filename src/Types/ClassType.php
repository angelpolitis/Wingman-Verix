<?php
    /**
     * Project Name:    Wingman Verix - Class Type
     * Created by:      Angel Politis
     * Creation Date:   Dec 24 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Types namespace.
    namespace Wingman\Verix\Types;

    /**
     * Represents a class type.
     * @package Wingman\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class ClassType extends Type {
        /**
         * The class a class type is bound to.
         * @var string
         */
        protected string $class;

        /**
         * Creates a new class type.
         * @param string $class The class name.
         */
        public function __construct (string $class) {
            $this->class = $class;
        }

        /**
         * Gets the class a class type is bound to.
         * @return string The class name.
         */
        public function getClass () : string {
            return $this->class;
        }

        /**
         * Validates a value against a class type.
         * @param mixed $value The value to validate.
         * @param array $params The parameters for validation.
         * @return bool The result of the validation.
         */
        public function validate (mixed $value, array $params = []) : bool {
            return $value instanceof $this->class;
        }
    }
?>
<?php
    /**
     * Project Name:    Wingman Verix - Schema Matcher
     * Created by:      Angel Politis
     * Creation Date:   Feb 26 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Bridge.Argus.Matchers namespace.
    namespace Wingman\Verix\Bridge\Argus\Matchers;

    # Import the following classes to the current scope.
    use Wingman\Argus\Interfaces\ArgumentMatcher;
    use Wingman\Verix\Facades\Schema;

    /**
     * A matcher that validates arguments against a Verix schema definition, allowing for flexible and powerful argument matching in tests.
     * @package Wingman\Verix\Bridge\Argus\Matchers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class SchemaMatcher implements ArgumentMatcher {
        /**
         * The schema definition that a matcher will use to validate arguments.
         * @var string
         */
        protected string $definition;

        /**
         * Creates a new matcher with the given schema definition.
         * @param string $definition The schema definition that this matcher will use to validate arguments.
         */
        public function __construct (string $definition) {
            $this->definition = $definition;
        }

        /**
         * Serialises a matcher to a string representation for debugging and reporting purposes.
         * @return string A string representation of the matcher, useful for debugging and reporting.
         */
        public function __toString () : string {
            return "Matches Verix Schema: {$this->definition}";
        }

        /**
         * Determines if the provided value matches the conditions defined by a matcher.
         * @param mixed $value The value to be matched against the matcher's conditions.
         * @return bool Whether the value satisfies the matcher's conditions.
         */
        public function matches (mixed $value) : bool {
            return Schema::from($this->definition)->validate($value)->isValid();
        }
    }
?>
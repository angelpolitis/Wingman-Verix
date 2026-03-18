<?php
    /**
     * Project Name:    Wingman Verix - Class Registry
     * Created by:      Angel Politis
     * Creation Date:   Dec 22 2025
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Registries namespace.
    namespace Wingman\Verix\Registries;

    # Import the following classes to the current scope.
    use Wingman\Verix\Bridge\Corvus\Emitter;
    use Wingman\Verix\Enums\Signal;

    /**
     * Represents a registry for class aliases.
     * @package Wingman\Verix\Registries
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ClassRegistry {
        /**
         * The registered class aliases (alias => FQCN).
         * @var array<string,string>
         */
        protected array $classes = [];

        /**
         * Converts classes using the dot notation to PHP FQCN.
         * @param string $fqcn The FQCN or dot notation.
         * @return string The normalized FQCN.
         */
        protected static function normaliseFQCN (string $fqcn) : string {
            $fqcn = trim($fqcn);
            $fqcn = str_replace(['.', '/'], '\\', $fqcn);
            return ltrim($fqcn, '\\');
        }

        /**
         * Sanitises an alias to ensure it adheres to the naming convention.
         * @param string $alias The alias to sanitise.
         * @return string The sanitised alias.
         */
        protected static function sanitiseAlias (string $alias) : string {
            # Allow letters, numbers, underscores and dots.
            return preg_replace('/[^a-zA-Z0-9_.]/', "", $alias);
        }

        /**
         * Registers a class alias.
         * @param string $alias Short name or dot notation.
         * @param string $fqcn Fully-qualified class name or dot notation.
         * @return static The registry instance for chaining.
         */
        public function register (string $alias, string $fqcn) : static {
            $alias = self::sanitiseAlias($alias);
            $fqcn = self::normaliseFQCN($fqcn);
            $this->classes[$alias] = $fqcn;
            Emitter::create()->with(alias: $alias, fqcn: $fqcn, registry: $this)->emit(Signal::CLASS_REGISTERED);
            return $this;
        }

        /**
         * Gets the FQCN registered for a given alias.
         * @param string $alias The alias to look up.
         * @return string|null The FQCN or `null` if not found.
         */
        public function get (string $alias) : ?string {
            return $this->classes[$alias] ?? null;
        }

        /**
         * Checks whether an alias exists.
         * @param string $alias The alias to look up.
         * @return bool Whether the alias exists.
         */
        public function has (string $alias) : bool {
            return isset($this->classes[$alias]);
        }
    }
?>
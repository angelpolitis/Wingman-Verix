<?php
    /**
     * Project Name:    Wingman Verix - Corvus Bridge Emitter
     * Created by:      Angel Politis
     * Creation Date:   Mar 17 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Bridge.Corvus namespace.
    namespace Wingman\Verix\Bridge\Corvus;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the class is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\Emitter', false)) return;

    # Import the following classes to the current scope.
    use BackedEnum;

    # If Corvus is installed, extend the real Emitter so callers get the full Corvus API;
    # otherwise provide a null-object stub that absorbs all calls silently.
    if (class_exists(\Wingman\Corvus\Emitter::class)) {
        /**
         * A thin extension of the Corvus `Emitter` used by Verix to fire signals on the
         * active Corvus bus. Defined only when the `Wingman/Corvus` package is present.
         *
         * Overrides `emit()` with a re-entrancy guard to prevent stack overflows should a
         * Corvus listener itself trigger a Verix registration or validation operation, which
         * would otherwise fire another signal → another dispatch → infinite recursion.
         * @package Wingman\Verix\Bridge\Corvus
         * @author Angel Politis <info@angelpolitis.com>
         * @since 1.0
         */
        class Emitter extends \Wingman\Corvus\Emitter {
            /**
             * Whether a Verix-originated emission is currently being dispatched.
             * @var bool
             */
            private static bool $dispatching = false;

            /**
             * Emits the signal(s), guarded against re-entrant calls.
             * @param array|string|\BackedEnum ...$signalPatterns The signal patterns.
             * @return static The emitter.
             */
            public function emit (array|string|\BackedEnum ...$signalPatterns) : static {
                if (static::$dispatching) return $this;

                static::$dispatching = true;

                try {
                    parent::emit(...$signalPatterns);
                }
                finally {
                    static::$dispatching = false;
                }

                return $this;
            }
        }
    }
    else {
        /**
         * A null-object stub that replaces the Corvus `Emitter` when `Wingman/Corvus` is not
         * installed. Every method returns `$this` and no signals are ever fired.
         * @package Wingman\Verix\Bridge\Corvus
         * @author Angel Politis <info@angelpolitis.com>
         * @since 1.0
         */
        class Emitter {
            /**
             * The accumulated payload data; present only to mirror the real Emitter's interface.
             * @var array
             */
            private array $payload = [];

            /**
             * Prevent direct instantiation; use `create()` instead.
             */
            private function __construct () {}

            /**
             * Creates a new stub emitter.
             * @return static A new instance.
             */
            public static function create () : static {
                return new static();
            }

            /**
             * No-op: absorbs bus assignment calls.
             * @param string $bus The bus name.
             * @return static The emitter.
             */
            public function useBus (string $bus) : static {
                return $this;
            }

            /**
             * Accumulates payload data to mirror the real Emitter's interface.
             * @param mixed ...$data The data to accumulate.
             * @return static The emitter.
             */
            public function with (mixed ...$data) : static {
                array_push($this->payload, ...array_values($data));
                return $this;
            }

            /**
             * Replaces the current payload with the provided data.
             * @param mixed ...$data The replacement payload data.
             * @return static The emitter.
             */
            public function withOnly (mixed ...$data) : static {
                $this->payload = [];
                return $this->with(...$data);
            }

            /**
             * No-op: absorbs signal emission calls.
             * @param array|string|BackedEnum ...$signalPatterns The signal patterns.
             * @return static The emitter.
             */
            public function emit (array|string|BackedEnum ...$signalPatterns) : static {
                return $this;
            }
        }
    }
?>
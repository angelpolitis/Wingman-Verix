<?php
    /**
     * Project Name:    Wingman Verix - Bridge & Signal Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 17 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Verix.Tests namespace.
    namespace Wingman\Verix\Tests;

    # Import the following classes to the current scope.
    use BackedEnum;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Verix\Bridge\Corvus\Emitter;
    use Wingman\Verix\Enums\Signal;

    /**
     * Tests for the Signal enum and the Corvus bridge Emitter (both the real extension
     * and the null-object stub).
     */
    class BridgeStubTest extends Test {

        // ─── Signal Enum ──────────────────────────────────────────────────────────

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal — Is A Backed Enum",
            description: "Signal is a string-backed enum."
        )]
        public function testSignalIsBackedEnum () : void {
            $reflection = new \ReflectionEnum(Signal::class);

            $this->assertTrue($reflection->isBacked(), "Signal should be a backed enum.");
            $this->assertTrue($reflection->getBackingType()->getName() === "string", "Signal backing type should be 'string'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal::CLASS_REGISTERED — Correct String Value",
            description: "Signal::CLASS_REGISTERED has the value 'verix.class.registered'."
        )]
        public function testSignalClassRegisteredValue () : void {
            $this->assertTrue(Signal::CLASS_REGISTERED->value === "verix.class.registered", "Signal::CLASS_REGISTERED should equal 'verix.class.registered'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal::PRIMITIVE_REGISTERED — Correct String Value",
            description: "Signal::PRIMITIVE_REGISTERED has the value 'verix.primitive.registered'."
        )]
        public function testSignalPrimitiveRegisteredValue () : void {
            $this->assertTrue(Signal::PRIMITIVE_REGISTERED->value === "verix.primitive.registered", "Signal::PRIMITIVE_REGISTERED should equal 'verix.primitive.registered'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal::SCHEMA_PARSED — Correct String Value",
            description: "Signal::SCHEMA_PARSED has the value 'verix.schema.parsed'."
        )]
        public function testSignalSchemaParsedValue () : void {
            $this->assertTrue(Signal::SCHEMA_PARSED->value === "verix.schema.parsed", "Signal::SCHEMA_PARSED should equal 'verix.schema.parsed'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal::SCHEMA_REGISTERED — Correct String Value",
            description: "Signal::SCHEMA_REGISTERED has the value 'verix.schema.registered'."
        )]
        public function testSignalSchemaRegisteredValue () : void {
            $this->assertTrue(Signal::SCHEMA_REGISTERED->value === "verix.schema.registered", "Signal::SCHEMA_REGISTERED should equal 'verix.schema.registered'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal::SCHEMA_VALIDATED — Correct String Value",
            description: "Signal::SCHEMA_VALIDATED has the value 'verix.schema.validated'."
        )]
        public function testSignalSchemaValidatedValue () : void {
            $this->assertTrue(Signal::SCHEMA_VALIDATED->value === "verix.schema.validated", "Signal::SCHEMA_VALIDATED should equal 'verix.schema.validated'.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Signal — All Cases Are BackedEnum Instances",
            description: "Every Signal case implements BackedEnum."
        )]
        public function testSignalCasesAreBackedEnum () : void {
            foreach (Signal::cases() as $case) {
                $this->assertTrue($case instanceof BackedEnum, "Signal case '{$case->name}' should be a BackedEnum.");
            }
        }

        // ─── Emitter ──────────────────────────────────────────────────────────────

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Emitter::create() — Does Not Throw",
            description: "Emitter::create() can always be called without throwing, regardless of whether Corvus is installed."
        )]
        public function testEmitterCreateDoesNotThrow () : void {
            $emitter = null;
            $thrown = false;

            try {
                $emitter = Emitter::create();
            }
            catch (\Throwable) {
                $thrown = true;
            }

            $this->assertFalse($thrown, "Emitter::create() must not throw.");
            $this->assertTrue($emitter !== null, "Emitter::create() must return an instance.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Emitter — with() Is Fluent",
            description: "Emitter::create()->with() returns the Emitter instance for chaining."
        )]
        public function testEmitterWithIsFluent () : void {
            $emitter = Emitter::create();
            $result  = $emitter->with(key: "value");

            $this->assertTrue($result === $emitter, "with() should return the same Emitter instance.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Emitter — emit() Is Fluent",
            description: "Calling emit() after with() does not throw and returns the Emitter instance."
        )]
        public function testEmitterEmitIsFluent () : void {
            $emitter = Emitter::create();
            $thrown = false;
            $result = null;

            try {
                $result = $emitter->with(x: 1)->emit(Signal::SCHEMA_PARSED);
            }
            catch (\Throwable) {
                $thrown = true;
            }

            $this->assertFalse($thrown, "emit() should not throw.");
            $this->assertTrue($result !== null, "emit() should return the Emitter instance.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Emitter — Full Pipeline Does Not Throw",
            description: "A complete create()->with()->emit() pipeline completes without exceptions for every Signal case."
        )]
        public function testEmitterFullPipelineDoesNotThrow () : void {
            $thrown = false;

            try {
                foreach (Signal::cases() as $signal) {
                    Emitter::create()->with(test: true)->emit($signal);
                }
            }
            catch (\Throwable) {
                $thrown = true;
            }

            $this->assertFalse($thrown, "The full Emitter pipeline should not throw for any Signal case.");
        }

        #[Group("Bridge — Corvus")]
        #[Define(
            name: "Emitter — Inherits Corvus Emitter When Available",
            description: "When Wingman\\\\Corvus\\\\Emitter exists, the bridge Emitter is a subclass of it."
        )]
        public function testEmitterInheritsCordusWhenAvailable () : void {
            if (!class_exists(\Wingman\Corvus\Emitter::class)) {
                $this->assertTrue(true, "Corvus is not installed — skip inheritance check.");
                return;
            }

            $this->assertTrue(is_a(Emitter::class, \Wingman\Corvus\Emitter::class, true), "When Corvus is installed, Verix's Emitter must extend \\Wingman\\Corvus\\Emitter.");
        }
    }
?>
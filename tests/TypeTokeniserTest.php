<?php
    /**
     * Project Name:    Wingman Verix - Type Tokeniser Tests
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
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Verix\Exceptions\TokenisationException;
    use Wingman\Verix\Processors\TypeTokeniser;

    /**
     * Unit tests for the TypeTokeniser, covering all token categories,
     * cursor mechanics, and error handling for invalid input.
     */
    class TypeTokeniserTest extends Test {

        // ─── Token extraction ────────────────────────────────────────────────────

        #[Group("Tokeniser")]
        #[Define(
            name: "Simple Identifier — Single Token",
            description: "A plain identifier like 'string' produces exactly one token."
        )]
        public function testSimpleIdentifierProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("string");

            $this->assertTrue($tokeniser->getTokens() === ["string"], "A single identifier should yield exactly one token.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Dot-Notation Identifier — Single Token",
            description: "A dot-notation identifier like 'My.Namespace.Class' is treated as one token."
        )]
        public function testDotNotationIdentifierProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("My.Namespace.Class");

            $this->assertTrue(count($tokeniser->getTokens()) === 1, "Dot-notation identifier should be a single token.");
            $this->assertTrue($tokeniser->getTokens()[0] === "My.Namespace.Class", "Dot-notation token value should match the full identifier.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Integer Literal — Single Token",
            description: "An integer like '42' is tokenised as a single numeric token."
        )]
        public function testIntegerLiteralProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("42");

            $this->assertTrue($tokeniser->getTokens() === ["42"], "An integer literal should yield one token.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Float Literal — Single Token",
            description: "A float like '3.14' is tokenised as a single numeric token."
        )]
        public function testFloatLiteralProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("3.14");

            $this->assertTrue($tokeniser->getTokens() === ["3.14"], "A float literal should yield one token.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Double-Quoted String — Single Token",
            description: "A double-quoted string like '\"hello\"' is tokenised as a single token including quotes."
        )]
        public function testDoubleQuotedStringProducesOneToken () : void {
            $tokeniser = new TypeTokeniser('"hello world"');
            $tokens = $tokeniser->getTokens();

            $this->assertTrue(count($tokens) === 1, "A double-quoted string should yield one token.");
            $this->assertTrue($tokens[0] === '"hello world"', "The token should include the surrounding quotes.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Single-Quoted String — Single Token",
            description: "A single-quoted string like \"'hello'\" is tokenised as a single token."
        )]
        public function testSingleQuotedStringProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("'hello'");
            $tokens = $tokeniser->getTokens();

            $this->assertTrue(count($tokens) === 1, "A single-quoted string should yield one token.");
            $this->assertTrue($tokens[0] === "'hello'", "The token should include the surrounding single quotes.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Rest Operator — Single Token",
            description: "The three-dot rest operator '...' is tokenised as a single token, not three dots."
        )]
        public function testRestOperatorProducesOneToken () : void {
            $tokeniser = new TypeTokeniser("...");

            $this->assertTrue($tokeniser->getTokens() === ["..."], "The rest operator should be tokenised as a single '...' token.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Union Expression — Correct Token Sequence",
            description: "The expression 'string|int' tokenises into ['string', '|', 'int']."
        )]
        public function testUnionExpressionTokenSequence () : void {
            $tokeniser = new TypeTokeniser("string|int");

            $this->assertTrue($tokeniser->getTokens() === ["string", "|", "int"], "Union expression should tokenise into the identifier, pipe, and identifier tokens.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Parametric Expression — Correct Token Sequence",
            description: "The expression 'int<min=1>' tokenises into the correct sequence of identifiers and punctuation."
        )]
        public function testParametricExpressionTokenSequence () : void {
            $tokeniser = new TypeTokeniser("int<min=1>");
            $tokens = $tokeniser->getTokens();

            $this->assertTrue($tokens === ["int", "<", "min", "=", "1", ">"], "Parametric expression should tokenise into the correct sequence.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "Whitespace — Ignored Between Tokens",
            description: "Leading, trailing, and inter-token whitespace does not produce tokens."
        )]
        public function testWhitespaceIsIgnored () : void {
            $tokeniser = new TypeTokeniser("string | int");
            $tokens = $tokeniser->getTokens();

            $this->assertTrue($tokens === ["string", "|", "int"], "Inter-token whitespace should be stripped and not produce extra tokens.");
        }

        // ─── Cursor mechanics ────────────────────────────────────────────────────

        #[Group("Tokeniser")]
        #[Define(
            name: "peek() — Does Not Advance Position",
            description: "Calling peek() twice returns the same token both times."
        )]
        public function testPeekDoesNotAdvancePosition () : void {
            $tokeniser = new TypeTokeniser("string|int");

            $first = $tokeniser->peek();
            $second = $tokeniser->peek();

            $this->assertTrue($first === $second, "peek() should not advance the cursor.");
            $this->assertTrue($first === "string", "First peek() should return the first token.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "next() — Advances Position",
            description: "Calling next() twice returns consecutive tokens."
        )]
        public function testNextAdvancesPosition () : void {
            $tokeniser = new TypeTokeniser("string|int");

            $first = $tokeniser->next();
            $second = $tokeniser->next();

            $this->assertTrue($first === "string", "First next() should return 'string'.");
            $this->assertTrue($second === "|", "Second next() should return '|'.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "next() — Returns null At End",
            description: "Calling next() past the last token returns null."
        )]
        public function testNextReturnsNullAtEnd () : void {
            $tokeniser = new TypeTokeniser("x");
            $tokeniser->next();

            $this->assertTrue($tokeniser->next() === null, "next() should return null when the token list is exhausted.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "expect() — Passes For Matching Token",
            description: "expect() does not throw when the next token matches the expected value."
        )]
        public function testExpectPassesForMatchingToken () : void {
            $tokeniser = new TypeTokeniser("{");
            $thrown = false;

            try {
                $tokeniser->expect("{");
            }
            catch (TokenisationException) {
                $thrown = true;
            }

            $this->assertFalse($thrown, "expect() should not throw when the next token matches.");
        }

        #[Group("Tokeniser")]
        #[Define(
            name: "expect() — Throws For Mismatched Token",
            description: "expect() throws a TokenisationException when the next token does not match."
        )]
        public function testExpectThrowsForMismatchedToken () : void {
            $tokeniser = new TypeTokeniser("string");
            $thrown = false;

            try {
                $tokeniser->expect("{");
            }
            catch (TokenisationException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "expect() should throw TokenisationException when the token does not match.");
        }

        // ─── Error handling ──────────────────────────────────────────────────────

        #[Group("Tokeniser")]
        #[Define(
            name: "Invalid Character — Throws TokenisationException",
            description: "An unexpected character in the input throws a TokenisationException during construction."
        )]
        public function testInvalidCharacterThrows () : void {
            $thrown = false;

            try {
                new TypeTokeniser("string # invalid");
            }
            catch (TokenisationException) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "An unexpected character should cause a TokenisationException.");
        }
    }
?>
<?php
    /*/
	 * Project Name:    Wingman — Verix — Type Tokeniser
	 * Created by:      Angel Politis
	 * Creation Date:   Dec 21 2025
	 * Last Modified:   Dec 23 2025
    /*/

    # Use the Verix.Processors namespace.
    namespace Wingman\Verix\Processors;

    # Import the following classes to the current scope.
    use RuntimeException;

    /**
     * Tokenises type definition strings.
     * @package Wingman\Verix\Processors
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypeTokeniser {
        /**
         * The input string.
         * @var string
         */
        protected string $input;

        /**
         * The length of the input string.
         * @var int
         */
        protected int $length;

        /**
         * The current position in the token list.
         * @var int
         */
        protected int $position = 0;

        /**
         * The list of tokens.
         * @var array
         */
        protected array $tokens = [];

        /**
         * Creates a new type tokeniser.
         * @param string $input The input string.
         */
        public function __construct (string $input) {
            $this->input = $input;
            $this->length = strlen($input);
            $this->tokenise();
        }
        /**
         * Tokenises a tokeniser's input string into tokens.
         * @throws RuntimeException If an unexpected character is encountered.
         */
        protected function tokenise () : void {
            # 1) rest
            # 2) identifiers
            # 3) numbers
            # 4) strings
            # 5) punctuation
            $pattern = '/\G\s*(?:
                (\.\.\.) |
                ([a-zA-Z_][a-zA-Z0-9_.]*) |
                (\d+\.\d+|\d+) |
                ("[^"]*"|\'[^\']*\') |
                ([<>{}\[\]():,|&=?@])
            )/x';
        
            $position = 0;
        
            while ($position < $this->length) {
                if (!preg_match($pattern, $this->input, $m, 0, $position)) {
                    throw new RuntimeException("Unexpected character '{$this->input[$position]}' at position {$position}");
                }
        
                # (1) Extract the first non-empty capture.
                $token = null;
                for ($i = 1; $i <= 5; $i++) {
                    if (isset($m[$i]) && $m[$i] !== "") {
                        $token = $m[$i];
                        break;
                    }
                }
        
                if ($token === null) {
                    throw new RuntimeException("Tokeniser error at position {$position}");
                }
        
                $this->tokens[] = $token;
        
                # (2) Advance the position by the exact length of the matched token.
                $tokenPos = strpos($m[0], $token);
                $position += $tokenPos + strlen($token);
            }
        
            $this->position = 0;
        }

        /**
         * Expects the next token to match a specific value.
         * @param string $value The expected token value.
         * @throws RuntimeException If the next token does not match the expected value.
         */
        public function expect (string $value) : void {
            $token = $this->next();
            if ($token !== $value) {
                throw new RuntimeException("Expected '{$value}', got '{$token}'.");
            }
        }
        
        /**
         * Gets all tokens of a tokeniser.
         * @return array The tokens.
         */
        public function getTokens () : array {
            return $this->tokens;
        }

        /**
         * Gets the next token and advances the position.
         * @return string|null The next token, or null if at the end.
         */
        public function next () : ?string {
            return $this->tokens[$this->position++] ?? null;
        }

        /**
         * Peeks at the next token without advancing the position.
         * @return string|null The next token, or null if at the end.
         */
        public function peek () : ?string {
            return $this->tokens[$this->position] ?? null;
        }
    }
?>
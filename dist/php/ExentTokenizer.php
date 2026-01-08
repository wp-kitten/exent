<?php
/**
 * EXENT Tokenizer for PHP
 *
 * @author Costin Trifan
 * @copyright 2025 Costin Trifan
 * @license MIT
 * @version 1.0.0
 */

namespace Exent;

use DateTime;
use Exception;

/**
 * Class responsible for breaking EXENT text into a stream of tokens.
 */
class ExentTokenizer {
    private $text;
    private $pos;
    private $length;

    public function __construct($text) {
        $this->text = $text;
        $this->pos = 0;
        $this->length = strlen($text);
    }

    private function peek() {
        return $this->pos < $this->length ? $this->text[$this->pos] : null;
    }

    private function next() {
        return $this->pos < $this->length ? $this->text[$this->pos++] : null;
    }

    private function skipWhitespace() {
        while ($this->pos < $this->length) {
            $char = $this->text[$this->pos];
            if (ctype_space($char)) {
                $this->pos++;
            }
            elseif ($char === '/' && isset($this->text[$this->pos + 1]) && $this->text[$this->pos + 1] === '/') {
                // Single line comment
                $this->pos += 2;
                while ($this->pos < $this->length && $this->text[$this->pos] !== "\n") {
                    $this->pos++;
                }
            }
            elseif ($char === '/' && isset($this->text[$this->pos + 1]) && $this->text[$this->pos + 1] === '*') {
                // Multi line comment
                $this->pos += 2;
                while ($this->pos < $this->length && !($this->text[$this->pos] === '*' && isset($this->text[$this->pos + 1]) && $this->text[$this->pos + 1] === '/')) {
                    $this->pos++;
                }
                $this->pos += 2;
            }
            else {
                break;
            }
        }
    }

    public function tokenize() {
        $tokens = [];
        while ($this->pos < $this->length) {
            $this->skipWhitespace();
            if ($this->pos >= $this->length) {
                break;
            }

            $char = $this->peek();

            if (strpos('{}[]:,', $char) !== false) {
                $tokens[] = ['type' => 'punct', 'value' => $this->next()];
            }
            elseif ($char === '"' || $char === "'") {
                $tokens[] = ['type' => 'string', 'value' => $this->readQuotedString($this->next())];
            }
            elseif ($char === '`') {
                $tokens[] = ['type' => 'multiline_string', 'value' => $this->readMultilineString()];
            }
            elseif ($char === '@') {
                $tokens[] = ['type' => 'date', 'value' => $this->readDate()];
            }
            elseif ($char === '&') {
                $this->next();
                $tokens[] = ['type' => 'anchor', 'value' => $this->readIdentifier()];
            }
            elseif ($char === '*') {
                $this->next();
                $tokens[] = ['type' => 'ref', 'value' => $this->readIdentifier()];
            }
            else {
                $val = $this->readIdentifierOrNumber();
                if ($val === 'true' || $val === 'false' || $val === 'null') {
                    $tokens[] = ['type' => 'literal', 'value' => $val === 'null' ? null : ($val === 'true')];
                }
                elseif (substr($val, -1) === 'n' && preg_match('/^\d+n$/', $val)) {
                    $tokens[] = ['type' => 'bigint', 'value' => substr($val, 0, -1)];
                }
                elseif (substr($val, -1) === 'd' && preg_match('/^\d+(\.\d+)?d$/', $val)) {
                    $tokens[] = ['type' => 'decimal', 'value' => (float)substr($val, 0, -1)];
                }
                elseif (preg_match('/^-?\d+$/', $val)) {
                    $tokens[] = ['type' => 'number', 'value' => (int)$val];
                }
                elseif (preg_match('/^-?\d+(\.\d+)?([eE][+-]?\d+)?$/', $val)) {
                    $tokens[] = ['type' => 'number', 'value' => (float)$val];
                }
                else {
                    $tokens[] = ['type' => 'identifier', 'value' => $val];
                }
            }
        }
        return $tokens;
    }

    /**
     * @throws Exception
     */
    private function readQuotedString($quote) {
        $result = '';
        $startPos = $this->pos;
        while ($this->pos < $this->length) {
            $char = $this->next();
            if ($char === $quote) {
                return $result;
            }
            if ($char === '\\') {
                $esc = $this->next();
                if ($esc === null) {
                    break;
                }
                switch ($esc) {
                    case 'n':
                        $result .= "\n";
                        break;
                    case 'r':
                        $result .= "\r";
                        break;
                    case 't':
                        $result .= "\t";
                        break;
                    case 'b':
                        $result .= "\x08";
                        break;
                    case 'f':
                        $result .= "\x0C";
                        break;
                    case 'u':
                        $hex = substr($this->text, $this->pos, 4);
                        if (strlen($hex) < 4) {
                            $this->pos = $this->length;
                            break;
                        }
                        $result .= mb_convert_encoding(pack('H*', $hex), 'UTF-8', 'UCS-2BE');
                        $this->pos += 4;
                        break;
                    default:
                        $result .= $esc;
                        break;
                }
            } else {
                $result .= $char;
            }
        }
        throw new Exception("Unterminated string starting at position " . ($startPos - 1));
    }

    /**
     * @throws Exception
     */
    private function readMultilineString() {
        $startPos = $this->pos;
        $this->next(); // skip `
        $result = '';
        while ($this->pos < $this->length) {
            $char = $this->next();
            if ($char === '`') {
                return $result;
            }
            $result .= $char;
        }
        throw new Exception("Unterminated multiline string starting at position " . $startPos);
    }

    /**
     * @throws Exception
     */
    private function readDate() {
        $this->next(); // skip @
        $result = '';
        while ($this->pos < $this->length && preg_match('/[0-9-T:Z.+-]/', $this->peek())) {
            $result .= $this->next();
        }
        return new DateTime($result);
    }

    private function readIdentifier() {
        $result = '';
        while ($this->pos < $this->length && preg_match('/[a-zA-Z0-9_-]/', $this->peek())) {
            $result .= $this->next();
        }
        return $result;
    }

    private function readIdentifierOrNumber() {
        $result = '';
        while ($this->pos < $this->length && preg_match('/[a-zA-Z0-9._+-]/', $this->peek())) {
            $result .= $this->next();
        }
        return $result;
    }
}

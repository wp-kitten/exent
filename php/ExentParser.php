<?php
/**
 * EXENT Parser for PHP
 *
 * @author Costin Trifan
 * @copyright 2025 Costin Trifan
 * @license MIT
 * @version 1.0.0
 */

namespace Exent;

use Exception;
use stdClass;

/**
 * Class responsible for parsing EXENT tokens into PHP data structures.
 */
class ExentParser {
    private $tokens;
    private $pos;
    private $anchors;
    private $assoc;
    private $depth;
    private $maxDepth;

    public function __construct($tokens, $assoc = true, $options = []) {
        $this->tokens = $tokens;
        $this->pos = 0;
        $this->anchors = [];
        $this->assoc = $assoc;
        $this->depth = 0;
        $this->maxDepth = isset($options['maxDepth']) ? $options['maxDepth'] : 200;
    }

    private function peek() {
        return isset($this->tokens[$this->pos]) ? $this->tokens[$this->pos] : null;
    }

    private function next() {
        return isset($this->tokens[$this->pos]) ? $this->tokens[$this->pos++] : null;
    }

    /**
     * @throws Exception
     */
    public function parse() {
        $val = $this->parseValue();
        if ($this->pos < count($this->tokens)) {
            throw new Exception("Unexpected token at end of input: " . json_encode($this->peek()));
        }
        return $val;
    }

    /**
     * @throws Exception
     */
    private function parseValue() {
        if ($this->depth > $this->maxDepth) {
            throw new Exception("Maximum nesting depth exceeded");
        }
        $anchorName = null;
        if ($this->peek() && $this->peek()['type'] === 'anchor') {
            $anchorName = $this->next()['value'];
        }

        $token = $this->peek();
        if (!$token) {
            throw new Exception("Unexpected end of input");
        }

        $value = null;
        switch ($token['type']) {
            case 'punct':
                if ($token['value'] === '{') {
                    $value = $this->assoc ? [] : new stdClass();
                    if ($anchorName !== null) {
                        $this->anchors[$anchorName] = &$value;
                    }
                    $this->depth++;
                    $this->parseObject($value);
                    $this->depth--;
                }
                elseif ($token['value'] === '[') {
                    $value = [];
                    if ($anchorName !== null) {
                        $this->anchors[$anchorName] = &$value;
                    }
                    $this->depth++;
                    $this->parseArray($value);
                    $this->depth--;
                } else {
                    throw new Exception("Unexpected punctuation: " . $token['value']);
                }
                break;
            case 'string':
            case 'multiline_string':
            case 'number':
            case 'decimal':
            case 'date':
            case 'literal':
            case 'identifier':
                $value = $this->next()['value'];
                break;
            case 'bigint':
                $val = $this->next()['value'];
                $value = function_exists('gmp_init') ? gmp_init($val) : $val;
                break;
            case 'ref':
                $refName = $this->next()['value'];
                if (!array_key_exists($refName, $this->anchors)) {
                    throw new Exception("Undefined reference: " . $refName);
                }
                return $this->anchors[$refName];
            default:
                throw new Exception("Unexpected token type: " . $token['type']);
        }

        if ($anchorName !== null) {
            $this->anchors[$anchorName] = $value;
        }
        return $value;
    }

    /**
     * @throws Exception
     */
    private function parseObject(&$obj) {
        $this->next(); // {
        while ($this->pos < count($this->tokens)) {
            $this->skipOptionalCommas();
            if ($this->peek() && $this->peek()['type'] === 'punct' && $this->peek()['value'] === '}') {
                $this->next();
                return $obj;
            }

            $keyToken = $this->next();
            if (!$keyToken) {
                break;
            }
            if ($keyToken['type'] !== 'identifier' && $keyToken['type'] !== 'string' && $keyToken['type'] !== 'literal') {
                throw new Exception("Expected identifier or string as object key, got " . json_encode($keyToken));
            }
            $key = (string)$keyToken['value'];
            if ($keyToken['type'] === 'literal') {
                if ($keyToken['value'] === null) {
                    $key = 'null';
                }
                elseif ($keyToken['value'] === true) {
                    $key = 'true';
                }
                elseif ($keyToken['value'] === false) {
                    $key = 'false';
                }
            }

            $colon = $this->next();
            if (!$colon || $colon['type'] !== 'punct' || $colon['value'] !== ':') {
                throw new Exception("Expected ':' after key " . $key);
            }

            $val = $this->parseValue();
            if ($this->assoc) {
                $obj[$key] = $val;
            } else {
                $obj->$key = $val;
            }

            $this->skipOptionalCommas();
            if ($this->peek() && $this->peek()['type'] === 'punct' && $this->peek()['value'] === '}') {
                $this->next();
                return $obj;
            }
        }
        throw new Exception("Unterminated object");
    }

    /**
     * @throws Exception
     */
    private function parseArray(&$arr) {
        $this->next(); // [
        $arr = [];
        while ($this->pos < count($this->tokens)) {
            $this->skipOptionalCommas();
            if ($this->peek() && $this->peek()['type'] === 'punct' && $this->peek()['value'] === ']') {
                $this->next();
                return $arr;
            }

            $arr[] = $this->parseValue();

            $this->skipOptionalCommas();
            if ($this->peek() && $this->peek()['type'] === 'punct' && $this->peek()['value'] === ']') {
                $this->next();
                return $arr;
            }
        }
        throw new Exception("Unterminated array");
    }

    private function skipOptionalCommas() {
        while ($this->peek() && $this->peek()['type'] === 'punct' && $this->peek()['value'] === ',') {
            $this->next();
        }
    }
}

<?php
/**
 * EXENT Implementation for PHP
 * EXtended ENtity Transfer
 *
 * @author Costin Trifan
 * @copyright 2025 Costin Trifan
 * @license MIT
 * @version 1.0.0
 */

namespace Exent;

use DateTime;
use Exception;
use GMP;
use SplObjectStorage;
use stdClass;

require_once __DIR__ . '/ExentTokenizer.php';
require_once __DIR__ . '/ExentParser.php';

/**
 * Main EXENT class providing static methods for parsing, stringifying, packing, and unpacking data.
 */
class Exent {
    /**
     * Parses EXENT text into a PHP associative array or object.
     *
     * @param string $text The EXENT string to parse.
     * @param bool $assoc When true, returned objects will be converted into associative arrays.
     * @param array $options Additional parsing options (e.g., maxDepth).
     * @return mixed
     * @throws Exception
     */
    public static function parse($text, $assoc = true, $options = []) {
        $tokenizer = new ExentTokenizer($text);
        $tokens = $tokenizer->tokenize();
        $parser = new ExentParser($tokens, $assoc, $options);
        return $parser->parse();
    }

    /**
     * Converts a PHP variable into an EXENT string.
     */
    public static function stringify($data, $pretty = true, $indent = "") {
        $seen = new SplObjectStorage();
        $anchors = new SplObjectStorage();
        $anchorNames = new SplObjectStorage();
        $anchorCount = 0;

        // First pass: detect repeated objects for anchoring
        $detectAnchors = function($v) use (&$detectAnchors, $seen, $anchors, &$anchorNames, &$anchorCount) {
            if (is_object($v) && !($v instanceof DateTime) && !($v instanceof GMP)) {
                if ($seen->contains($v)) {
                    if (!$anchors->contains($v)) {
                        $anchors->attach($v, true);
                        $anchorNames->attach($v, "a" . ($anchorCount++));
                    }
                    return;
                }
                $seen->attach($v, true);
                if ($v instanceof stdClass) {
                    foreach (get_object_vars($v) as $val) {
                        $detectAnchors($val);
                    }
                }
            }
            elseif (is_array($v)) {
                // To avoid issues with large arrays being seen as "same" if they have same content (PHP behavior), 
                // we only anchor objects for now as it's safer and more common.
                // But if we want to anchor arrays, we'd need a way to distinguish them.
                foreach ($v as $val) {
                    $detectAnchors($val);
                }
            }
        };
        $detectAnchors($data);

        $writtenAnchors = new SplObjectStorage();

        return self::serialize($data, $pretty, $indent, $anchorNames, $writtenAnchors);
    }

    private static function serialize($data, $pretty, $indent, $anchorNames, $writtenAnchors) {
        $anchor = "";
        if (is_object($data) && $anchorNames->contains($data)) {
            if ($writtenAnchors->contains($data)) {
                return "*" . $anchorNames[$data];
            }
            $anchor = "&" . $anchorNames[$data] . " ";
            $writtenAnchors->attach($data, true);
        }

        if ($data === null) {
            return "null";
        }
        if (is_bool($data)) {
            return $data ? "true" : "false";
        }
        
        // Handle GMP BigInts
        if (is_object($data) && get_class($data) === 'GMP') {
            return gmp_strval($data) . "n";
        }
        
        if (is_numeric($data) && !is_string($data)) {
            if (is_int($data)) {
                if ($data > 2147483647 || $data < -2147483648) {
                    return $data . "n";
                }
            }
            return (string)$data;
        }
        
        if ($data instanceof DateTime) {
            return "@" . $data->format('c');
        }
        
        if (is_string($data)) {
            if (preg_match('/^\d+$/', $data) && (strlen($data) > 10 || (float)$data > 2147483647 || (float)$data < -2147483648)) {
                return $data . "n";
            }
            if (substr($data, -1) === 'n' || substr($data, -1) === 'd') {
                if (is_numeric(substr($data, 0, -1))) {
                    return $data;
                }
            }
            if (strpos($data, "\n") !== false) {
                return $anchor . "`" . $data . "`";
            }
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9]*$/', $data) && !in_array($data, ['true', 'false', 'null'])) {
                return $anchor . $data;
            }
            return $anchor . '"' . addcslashes($data, '"\\') . '"';
        }

        $nextIndent = $pretty ? $indent . "    " : "";
        $newline = $pretty ? "\n" : " ";

        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1) && !empty($data);
            if ($isAssoc) {
                $parts = [];
                foreach ($data as $k => $v) {
                    $keyStr = preg_match('/^[a-zA-Z_][a-zA-Z0-9]*$/', $k) ? $k : '"' . addcslashes($k, '"\\') . '"';
                    $parts[] = $nextIndent . $keyStr . ": " . self::serialize($v, $pretty, $nextIndent, $anchorNames, $writtenAnchors);
                }
                return $anchor . "{" . $newline . implode("," . $newline, $parts) . $newline . $indent . "}";
            }
            else {
                $parts = [];
                foreach ($data as $v) {
                    $parts[] = $nextIndent . self::serialize($v, $pretty, $nextIndent, $anchorNames, $writtenAnchors);
                }
                if (empty($parts)) {
                    return $anchor . "[]";
                }
                return $anchor . "[" . $newline . implode("," . $newline, $parts) . $newline . $indent . "]";
            }
        }

        if (is_object($data)) {
            $parts = [];
            foreach (get_object_vars($data) as $k => $v) {
                $keyStr = preg_match('/^[a-zA-Z_][a-zA-Z0-9]*$/', $k) ? $k : '"' . addcslashes($k, '"\\') . '"';
                $parts[] = $nextIndent . $keyStr . ": " . self::serialize($v, $pretty, $nextIndent, $anchorNames, $writtenAnchors);
            }
            return $anchor . "{" . $newline . implode("," . $newline, $parts) . $newline . $indent . "}";
        }

        return $anchor . '"' . (string)$data . '"';
    }

    const TAG_NULL = 0x00;
    const TAG_TRUE = 0x01;
    const TAG_FALSE = 0x02;
    const TAG_INT32 = 0x03;
    const TAG_FLOAT64 = 0x04;
    const TAG_BIGINT64 = 0x05;
    const TAG_STRING = 0x06;
    const TAG_DATE = 0x07;
    const TAG_ARRAY = 0x08;
    const TAG_OBJECT = 0x09;
    const TAG_DECIMAL = 0x0A;
    const TAG_REF = 0x0B;

    /**
     * Packs data into B-EXENT binary format.
     */
    public static function pack($data) {
        $buffer = "";
        $anchors = new SplObjectStorage();
        $anchorCount = 0;
        self::encode($data, $buffer, $anchors, $anchorCount);
        return $buffer;
    }

    private static function encode($v, &$buffer, $anchors, &$anchorCount) {
        if (is_null($v)) {
            $buffer .= chr(self::TAG_NULL);
            return;
        }
        
        $isObject = is_object($v) && !($v instanceof DateTime) && !($v instanceof GMP);
        if ($isObject) {
            if ($anchors->contains($v)) {
                $buffer .= chr(self::TAG_REF) . pack("N", $anchors[$v]);
                return;
            }
            $anchors->attach($v, $anchorCount++);
        }
        elseif (is_array($v)) {
            // We don't anchor arrays in PHP as they lack identity, but we increment to stay in sync
            $anchorCount++;
        }

        if (is_bool($v)) {
            $buffer .= chr($v ? self::TAG_TRUE : self::TAG_FALSE);
        }
        elseif (is_int($v)) {
            if ($v >= -2147483648 && $v <= 2147483647) {
                $buffer .= chr(self::TAG_INT32) . pack("N", $v);
            }
            else {
                $buffer .= chr(self::TAG_BIGINT64) . pack("J", $v);
            }
        }
        elseif (is_float($v)) {
            $buffer .= chr(self::TAG_FLOAT64) . pack("E", $v);
        }
        elseif ($v instanceof DateTime) {
            $buffer .= chr(self::TAG_DATE) . pack("E", (float)$v->format('U.u') * 1000);
        }
        elseif (is_object($v) && get_class($v) === 'GMP') {
            $buffer .= chr(self::TAG_BIGINT64) . pack("J", (float)gmp_strval($v));
        }
        elseif (is_string($v)) {
            if (substr($v, -1) === 'n' && preg_match('/^\d+n$/', $v)) {
                $buffer .= chr(self::TAG_BIGINT64) . pack("J", (float)substr($v, 0, -1));
            }
            elseif (substr($v, -1) === 'd' && preg_match('/^\d+(\.\d+)?d$/', $v)) {
                $buffer .= chr(self::TAG_DECIMAL) . pack("E", (float)substr($v, 0, -1));
            }
            else {
                $buffer .= chr(self::TAG_STRING) . pack("N", strlen($v)) . $v;
            }
        }
        elseif (is_array($v)) {
            $isAssoc = array_keys($v) !== range(0, count($v) - 1) && !empty($v);
            if (!$isAssoc) {
                $buffer .= chr(self::TAG_ARRAY) . pack("N", count($v));
                foreach ($v as $item) {
                    self::encode($item, $buffer, $anchors, $anchorCount);
                }
            }
            else {
                $buffer .= chr(self::TAG_OBJECT) . pack("N", count($v));
                foreach ($v as $k => $item) {
                    $buffer .= pack("N", strlen($k)) . $k;
                    self::encode($item, $buffer, $anchors, $anchorCount);
                }
            }
        }
        elseif (is_object($v)) {
            $vars = get_object_vars($v);
            $buffer .= chr(self::TAG_OBJECT) . pack("N", count($vars));
            foreach ($vars as $k => $item) {
                $buffer .= pack("N", strlen($k)) . $k;
                self::encode($item, $buffer, $anchors, $anchorCount);
            }
        }
    }

    /**
     * Unpacks B-EXENT binary format into PHP data.
     * @param string $buffer The binary B-EXENT string.
     * @param bool $assoc When true, returned objects will be converted into associative arrays.
     * @param array $options Additional unpacking options (e.g., maxDepth).
     * @throws Exception
     */
    public static function unpack($buffer, $assoc = true, $options = []) {
        $pos = 0;
        $anchors = [];
        $depth = 0;
        $maxDepth = isset($options['maxDepth']) ? $options['maxDepth'] : 200;
        return self::decode($buffer, $pos, $assoc, $anchors, $depth, $maxDepth);
    }

    /**
     * @throws Exception
     */
    private static function decode($buffer, &$pos, $assoc, &$anchors, &$depth, $maxDepth) {
        if ($depth > $maxDepth) {
            throw new Exception("Maximum nesting depth exceeded during unpacking");
        }
        $tag = ord($buffer[$pos++]);
        switch ($tag) {
            case self::TAG_NULL:
                return null;
            case self::TAG_TRUE:
                return true;
            case self::TAG_FALSE:
                return false;
            case self::TAG_INT32:
                $val = unpack("N", substr($buffer, $pos, 4))[1];
                $pos += 4;
                if ($val >= 0x80000000) {
                    $val -= 0x100000000;
                }
                return $val;
            case self::TAG_FLOAT64:
            case self::TAG_DECIMAL:
                $val = unpack("E", substr($buffer, $pos, 8))[1];
                $pos += 8;
                return $val;
            case self::TAG_BIGINT64:
                $val = unpack("J", substr($buffer, $pos, 8))[1];
                $pos += 8;
                return function_exists('gmp_init') ? gmp_init($val) : $val;
            case self::TAG_STRING:
                $len = unpack("N", substr($buffer, $pos, 4))[1];
                $pos += 4;
                $val = substr($buffer, $pos, $len);
                $pos += $len;
                return $val;
            case self::TAG_DATE:
                $ms = unpack("E", substr($buffer, $pos, 8))[1];
                $pos += 8;
                $seconds = floor($ms / 1000);
                $dt = new DateTime();
                $dt->setTimestamp($seconds);
                return $dt;
            case self::TAG_REF:
                $id = unpack("N", substr($buffer, $pos, 4))[1];
                $pos += 4;
                return $anchors[$id];
            case self::TAG_ARRAY:
                $len = unpack("N", substr($buffer, $pos, 4))[1];
                $pos += 4;
                $arr = [];
                $anchors[] = &$arr;
                $depth++;
                for ($i = 0; $i < $len; $i++) {
                    $arr[] = self::decode($buffer, $pos, $assoc, $anchors, $depth, $maxDepth);
                }
                $depth--;
                return $arr;
            case self::TAG_OBJECT:
                $len = unpack("N", substr($buffer, $pos, 4))[1];
                $pos += 4;
                $obj = $assoc ? [] : new stdClass();
                $anchors[] = &$obj;
                $depth++;
                for ($i = 0; $i < $len; $i++) {
                    $kLen = unpack("N", substr($buffer, $pos, 4))[1];
                    $pos += 4;
                    $k = substr($buffer, $pos, $kLen);
                    $pos += $kLen;
                    $val = self::decode($buffer, $pos, $assoc, $anchors, $depth, $maxDepth);
                    if ($assoc) {
                        $obj[$k] = $val;
                    }
                    else {
                        $obj->$k = $val;
                    }
                }
                $depth--;
                return $obj;
            default:
                throw new Exception("Unknown B-EXENT tag: " . sprintf("0x%02X", $tag) . " at pos " . ($pos - 1));
        }
    }
}

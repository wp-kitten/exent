<?php

require_once __DIR__ . '/../php/Exent.php';

use Exent\Exent;

/**
 * @throws Exception
 */
function assert_equal($actual, $expected, $message = "", $visited = null) {
    if ($visited === null) {
        $visited = new SplObjectStorage();
    }

    if (is_object($actual) && is_object($expected)) {
        if (get_class($actual) !== get_class($expected)) {
            goto fail;
        }

        if ($visited->contains($actual)) {
            if ($actual !== $expected) {
                goto fail;
            }
            return;
        }
        $visited->attach($actual);

        if ($actual instanceof DateTime) {
            if ($actual->getTimestamp() !== $expected->getTimestamp()) {
                goto fail;
            }
            return;
        }

        if (get_class($actual) === 'GMP') {
            if (gmp_cmp($actual, $expected) !== 0) {
                goto fail;
            }
            return;
        }

        $actualVars = get_object_vars($actual);
        $expectedVars = get_object_vars($expected);
        if (count($actualVars) !== count($expectedVars)) {
            goto fail;
        }
        foreach ($actualVars as $k => $v) {
            if (!array_key_exists($k, $expectedVars)) {
                goto fail;
            }
            assert_equal($v, $expectedVars[$k], $message, $visited);
        }
        return;
    }

    if (is_array($actual) && is_array($expected)) {
        if (count($actual) !== count($expected)) {
            goto fail;
        }
        foreach ($actual as $k => $v) {
            if (!array_key_exists($k, $expected)) {
                goto fail;
            }
            assert_equal($v, $expected[$k], $message, $visited);
        }
        return;
    }

    if ($actual !== $expected) {
        goto fail;
    }

    return;

    fail:
    echo "MISMATCH: $message\n";
    echo "Actual: " . var_export($actual, true) . "\n";
    echo "Expected: " . var_export($expected, true) . "\n";
    throw new Exception("Assertion failed: $message");
}

/**
 * @throws Exception
 */
function assert_throws($callback, $expectedMessagePart, $message = "") {
    try {
        $callback();
    } catch (Exception $e) {
        if (strpos($e->getMessage(), $expectedMessagePart) !== false) {
            return;
        }
        echo "WRONG EXCEPTION: $message\n";
        echo "Expected part: $expectedMessagePart\n";
        echo "Actual message: " . $e->getMessage() . "\n";
        throw new Exception("Assertion failed: $message");
    }
    echo "NO EXCEPTION THROWN: $message\n";
    throw new Exception("Assertion failed: $message");
}

/**
 * @throws Exception
 */
function runComprehensiveTests() {
    echo "Running Comprehensive PHP Tests...\n";

    // --- 1. Basic Types ---
    echo "  Test 1. Basic Types...\n";
    assert_equal(Exent::parse("null"), null, "null failed");
    assert_equal(Exent::parse("true"), true, "true failed");
    assert_equal(Exent::parse("false"), false, "false failed");
    assert_equal(Exent::parse("123"), 123, "int failed");
    assert_equal(Exent::parse("-123"), -123, "negative int failed");
    assert_equal(Exent::parse("123.45"), 123.45, "float failed");
    
    // --- 2. Strings ---
    echo "  Test 2. Strings...\n";
    assert_equal(Exent::parse("ident"), "ident", "identifier failed");
    assert_equal(Exent::parse('"quoted"'), "quoted", "quoted string failed");
    assert_equal(Exent::parse('"escaped \" quote"'), 'escaped " quote', "escaped quote failed");
    assert_equal(Exent::parse('"backslash \\\\"'), 'backslash \\', "escaped backslash failed");
    assert_equal(Exent::parse("`multiline\nstring`"), "multiline\nstring", "multiline string failed");
    
    // Test stringification of strings
    assert_equal(Exent::stringify("simple", false), "simple", "stringify simple string failed");
    assert_equal(Exent::stringify("with space", false), '"with space"', "stringify string with space failed");
    assert_equal(Exent::stringify("true", false), '"true"', "stringify 'true' string failed");
    assert_equal(Exent::stringify("123", false), '"123"', "stringify '123' string failed");
    assert_equal(Exent::stringify("123n", false), "123n", "stringify '123n' string failed");

    // --- 3. BigInt & Decimal ---
    echo "  Test 3. BigInt & Decimal...\n";
    $bigIntStr = "12345678901234567890n";
    $parsedBigInt = Exent::parse($bigIntStr);
    $bigIntValStr = function_exists('gmp_strval') ? gmp_strval($parsedBigInt) : (string)$parsedBigInt;
    assert_equal($bigIntValStr, "12345678901234567890", "BigInt parse failed");
    assert_equal(Exent::stringify($parsedBigInt, false), $bigIntStr, "BigInt stringify failed");

    $decimalStr = "123.45d";
    assert_equal(Exent::parse($decimalStr), 123.45, "Decimal parse failed");
    // Note: PHP implementation currently stringifies floats as is, without 'd' unless it was already a string with 'd'
    // Let's check how it handles numeric strings with 'd'
    assert_equal(Exent::stringify("123.45d", false), "123.45d", "Decimal string stringify failed");

    // --- 4. Collections ---
    echo "  Test 4. Collections...\n";
    assert_equal(Exent::parse("[]"), [], "empty array failed");
    assert_equal(Exent::parse("{}"), [], "empty object (assoc) failed");
    assert_equal(Exent::parse("[1, 2, 3]"), [1, 2, 3], "simple array failed");
    assert_equal(Exent::parse("{a: 1, b: 2}"), ["a" => 1, "b" => 2], "simple object failed");
    assert_equal(Exent::parse("[1 2 3]"), [1, 2, 3], "array without commas failed");
    assert_equal(Exent::parse("{a: 1 b: 2}"), ["a" => 1, "b" => 2], "object without commas failed");

    // --- 5. Anchors & References ---
    echo "  Test 5. Anchors & References...\n";
    $cyclic = new stdClass();
    $cyclic->self = $cyclic;
    $cyclicStr = Exent::stringify($cyclic, false);
    // Should be something like &a0 { self: *a0 }
    $parsedCyclic = Exent::parse($cyclicStr, false);
    if ($parsedCyclic !== $parsedCyclic->self) {
        throw new Exception("Circular reference failed");
    }

    // --- 6. B-EXENT (Binary) ---
    echo "  Test 6. B-EXENT (Binary)...\n";
    $data = [
        "int" => 1,
        "float" => 1.5,
        "bool" => true,
        "null" => null,
        "string" => "foo",
        "date" => new DateTime("2023-01-01T00:00:00Z"),
        "arr" => [1, 2],
        "obj" => ["x" => 1]
    ];
    $packed = Exent::pack($data);
    $unpacked = Exent::unpack($packed);
    assert_equal($unpacked["int"], $data["int"]);
    assert_equal($unpacked["float"], $data["float"]);
    assert_equal($unpacked["bool"], $data["bool"]);
    assert_equal($unpacked["null"], $data["null"]);
    assert_equal($unpacked["string"], $data["string"]);
    assert_equal($unpacked["date"]->getTimestamp(), $data["date"]->getTimestamp());
    assert_equal($unpacked["arr"], $data["arr"]);
    assert_equal($unpacked["obj"], $data["obj"]);

    // Binary Circular Reference
    $binCyclic = new stdClass();
    $binCyclic->me = $binCyclic;
    $binPacked = Exent::pack($binCyclic);
    $binUnpacked = Exent::unpack($binPacked, false);
    if ($binUnpacked !== $binUnpacked->me) {
        throw new Exception("Binary circular reference failed");
    }

    // --- 7. Error Conditions ---
    echo "  Test 7. Error Conditions...\n";
    assert_throws(function() { Exent::parse("{"); }, "Unterminated object", "unterminated object");
    assert_throws(function() { Exent::parse("["); }, "Unterminated array", "unterminated array");
    assert_throws(function() { Exent::parse('"abc'); }, "Unterminated string", "unterminated string");
    assert_throws(function() { Exent::parse('`abc'); }, "Unterminated multiline string", "unterminated multiline");
    assert_throws(function() { Exent::parse('*unknown'); }, "Undefined reference", "unknown anchor");

    // --- 8. Pretty Printing ---
    echo "  Test 8. Pretty Printing...\n";
    $prettyData = ["a" => 1, "b" => [2, 3]];
    $prettyStr = Exent::stringify($prettyData, true);
    $expectedPretty = "{\n    a: 1,\n    b: [\n        2,\n        3\n    ]\n}";
    assert_equal(str_replace("\r\n", "\n", $prettyStr), $expectedPretty, "pretty print mismatch");

    // --- 9. Bulletproof Edge Cases ---
    echo "  Test 9. Bulletproof Edge Cases...\n";
    $edgeCases = [
        "deep_nested" => [[[[["nested"]]]]],
        "weird_keys" => [" " => "space", "" => "empty", "null" => 0, "true" => 1],
        "escapes" => "line\nbreak\rreturn\ttab\x08backspace\x0Cformfeed\\backslash\"quote"
    ];
    $edgeExent = Exent::stringify($edgeCases, false);
    $parsedEdge = Exent::parse($edgeExent);
    assert_equal($parsedEdge, $edgeCases, "Edge cases failed");

    echo "All Comprehensive PHP tests passed!\n";
}

try {
    runComprehensiveTests();
} catch (Exception $e) {
    echo "Test failed!\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

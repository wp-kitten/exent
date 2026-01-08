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
function runTests() {
    echo "Running PHP Tests...\n";

    // 1. Basic Parsing & Stringification
    echo "  Test 1. Basic Parsing & Stringification...\n";
    $obj1 = [
        "string" => "Hello World",
        "number" => 123.45,
        "bool" => true,
        "null" => null,
        "array" => [1, 2, 3],
        "nested" => ["a" => 1]
    ];
    $exentStr1 = Exent::stringify($obj1, false);
    $parsed1 = Exent::parse($exentStr1);
    assert_equal($parsed1, $obj1, "Basic roundtrip failed");

    // 2. Data Types: Date
    echo "  Test 2. Data Types: Date...\n";
    $date = new DateTime('2025-12-25T12:00:00Z');
    $exentDate = Exent::stringify(["d" => $date], false);
    $parsedDate = Exent::parse($exentDate);
    assert_equal($parsedDate['d'] instanceof DateTime, true, "Not a DateTime object");
    assert_equal($parsedDate['d']->getTimestamp(), $date->getTimestamp(), "Timestamp mismatch");

    // 3. Data Types: BigInt
    echo "  Test 3. Data Types: BigInt...\n";
    $bigInt = "9007199254740991n";
    $exentBigInt = '{ b: ' . $bigInt . ' }';
    $parsedBigInt = Exent::parse($exentBigInt);
    $val = $parsedBigInt['b'];
    $valStr = is_object($val) ? gmp_strval($val) : (string)$val;
    assert_equal($valStr, "9007199254740991", "BigInt value mismatch");

    // 4. Data Types: Decimal
    echo "  Test 4. Data Types: Decimal...\n";
    $exentDecimal = '{ price: 99.99d }';
    $parsedDecimal = Exent::parse($exentDecimal);
    assert_equal($parsedDecimal['price'], 99.99, "Decimal value mismatch");

    // 5. Binary Packing/Unpacking (B-EXENT)
    echo "  Test 5. Binary Packing/Unpacking (B-EXENT)...\n";
    $complexObj = [
        "name" => "Exent",
        "version" => 1,
        "date" => new DateTime(),
        "big" => "1000000000000000n",
        "list" => [1, "two", ["three" => 3]]
    ];
    $packed = Exent::pack($complexObj);
    $unpacked = Exent::unpack($packed);
    assert_equal($unpacked['name'], $complexObj['name']);
    assert_equal($unpacked['date']->getTimestamp(), $complexObj['date']->getTimestamp());
    $bigValStr = is_object($unpacked['big']) ? gmp_strval($unpacked['big']) : (string)$unpacked['big'];
    assert_equal($bigValStr, "1000000000000000");
    assert_equal($unpacked['list'][2], $complexObj['list'][2]);

    // 6. Anchors and References
    echo "  Test 6. Anchors and References...\n";
    // PHP implementation handles anchors for objects and arrays.
    $exentRefs = '{
        base: &me { name: "Self" },
        link: *me
    }';
    $parsedRefs = Exent::parse($exentRefs, false); // Objects as stdClass
    if ($parsedRefs->base !== $parsedRefs->link) {
         throw new Exception("Reference equality failed");
    }

    // 7. Comments and Relaxed Syntax
    echo "  Test 7. Comments and Relaxed Syntax...\n";
    $exentRelaxed = '{
        // Single line
        key: value /* Multi
                      line */
        arr: [
            1
            2
            3, // Trailing
        ]
    }';
    $parsedRelaxed = Exent::parse($exentRelaxed);
    assert_equal($parsedRelaxed['key'], "value");
    assert_equal($parsedRelaxed['arr'], [1, 2, 3]);

    // 8. Nesting Depth Limit
    echo "  Test 8. Nesting Depth Limit...\n";
    $deep = [];
    $current = &$deep;
    for ($i = 0; $i < 201; $i++) {
        $current['inner'] = [];
        $current = &$current['inner'];
    }
    unset($current);
    $deepExent = Exent::stringify($deep, false);
    try {
        Exent::parse($deepExent);
        throw new Exception("Should have thrown nesting depth exception");
    }
    catch (Exception $e) {
        if (strpos($e->getMessage(), "Maximum nesting depth exceeded") === false) {
             throw $e;
        }
    }

    echo "All PHP tests passed!\n";
}

try {
    runTests();
}
catch (Exception $e) {
    echo "Test failed!\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

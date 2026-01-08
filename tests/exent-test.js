const Exent = require('../js/exent.js');
const assert = require('assert');

function runTests() {
    console.log("Running JavaScript Tests...");

    // 1. Basic Parsing & Stringification
    const obj1 = {
        string: "Hello World",
        number: 123.45,
        bool: true,
        null: null,
        array: [1, 2, 3],
        nested: { a: 1 }
    };
    const exentStr1 = Exent.stringify(obj1);
    const parsed1 = Exent.parse(exentStr1);
    assert.deepStrictEqual(parsed1, obj1, "Basic roundtrip failed");

    // 2. Data Types: Date
    const date = new Date('2025-12-25T12:00:00Z');
    const exentDate = Exent.stringify({ d: date });
    const parsedDate = Exent.parse(exentDate);
    assert.strictEqual(parsedDate.d instanceof Date, true);
    assert.strictEqual(parsedDate.d.getTime(), date.getTime());

    // 3. Data Types: BigInt
    const bigInt = 9007199254740991n;
    const exentBigInt = Exent.stringify({ b: bigInt });
    const parsedBigInt = Exent.parse(exentBigInt);
    assert.strictEqual(typeof parsedBigInt.b, 'bigint');
    assert.strictEqual(parsedBigInt.b, bigInt);

    // 4. Data Types: Decimal (parsed as number in JS, but matches suffix 'd' in stringify)
    const exentDecimal = '{ price: 99.99d }';
    const parsedDecimal = Exent.parse(exentDecimal);
    assert.strictEqual(parsedDecimal.price, 99.99);

    // 5. Binary Packing/Unpacking (B-EXENT)
    const complexObj = {
        name: "Exent",
        version: 1,
        date: new Date(),
        big: 1000000000000000n,
        list: [1, "two", { three: 3 }]
    };
    const packed = Exent.pack(complexObj);
    const unpacked = Exent.unpack(packed);
    assert.deepStrictEqual(unpacked.name, complexObj.name);
    assert.strictEqual(unpacked.date.getTime(), complexObj.date.getTime());
    assert.strictEqual(unpacked.big, complexObj.big);
    assert.deepStrictEqual(unpacked.list[2], complexObj.list[2]);

    // 6. Anchors and References
    const exentRefs = `
    {
        base: &me { name: "Self" }
        link: *me
        circular: &circ { self: *circ }
    }
    `;
    const parsedRefs = Exent.parse(exentRefs);
    assert.strictEqual(parsedRefs.base, parsedRefs.link);
    assert.strictEqual(parsedRefs.circular, parsedRefs.circular.self);

    // 7. Comments and Relaxed Syntax
    const exentRelaxed = `
    {
        // Single line
        key: value /* Multi
                      line */
        arr: [
            1
            2
            3, // Trailing
        ]
    }
    `;
    const parsedRelaxed = Exent.parse(exentRelaxed);
    assert.strictEqual(parsedRelaxed.key, "value");
    assert.deepStrictEqual(parsedRelaxed.arr, [1, 2, 3]);

    // 8. Nesting Depth Limit
    let deep = {};
    let current = deep;
    for (let i = 0; i < 201; i++) {
        current.inner = {};
        current = current.inner;
    }
    const deepExent = Exent.stringify(deep);
    assert.throws(() => Exent.parse(deepExent), /Maximum nesting depth exceeded/);

    console.log("All JavaScript tests passed!");
}

try {
    runTests();
} catch (e) {
    console.error("Test failed!");
    console.error(e);
    process.exit(1);
}

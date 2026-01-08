# EXENT: EXtended ENtity Transfer

EXENT is a data transfer format designed for the modern web. It overcomes the fundamental limitations of JSON and XML by providing native support for essential data types, a human-friendly syntax, and a high-performance binary mode.

## 🚀 Key Features

- **Native Type Support**: First-class citizens for Dates, BigInts, and high-precision Decimals.
- **Human-Friendly Syntax**: Supports comments (`//` and `/* */`), unquoted keys, and trailing commas.
- **Reference System**: Eliminate data duplication with anchors (`&`) and references (`*`). **Automatic anchoring** is supported during stringification in both PHP and JS.
- **Binary Mode (B-EXENT)**: A compact, high-performance binary representation for machine-to-machine communication, supporting all native types including references.
- **Multi-platform**: Native implementations for **JavaScript** and **PHP**.
- **Developer Experience (DX)**: Multiline strings and relaxed comma rules make writing and reading data a breeze.

---

## 🛠 Usage

### JavaScript

Include `js/exent.js` in your project:

```javascript
// Parsing EXENT text
const text = `{ project: EXENT, version: 1.0.0, release: @2025-12-26 }`;
const data = Exent.parse(text);
console.log(data.release instanceof Date); // true

// Stringifying to EXENT
const exentString = Exent.stringify(data);

// Packing to B-EXENT (Binary)
const binaryBuffer = Exent.pack(data); // Returns Uint8Array

// Unpacking B-EXENT
const unpackedData = Exent.unpack(binaryBuffer);
```

### PHP

Use the `Exent\Exent` class from `php/Exent.php`:

```php
use Exent\Exent;

// Parsing EXENT text
$text = '{ project: EXENT, version: 1.0.0, release: @2025-12-26 }';
$data = Exent::parse($text);
echo $data['release']->format('Y-m-d'); // 2025-12-26

// Stringifying to EXENT
$exentString = Exent::stringify($data);

// Packing to B-EXENT (Binary)
$binaryBuffer = Exent::pack($data);

// Unpacking B-EXENT
$unpackedData = Exent::unpack($binaryBuffer);
```

---

## 📝 Syntax Overview

### Primitives & Literals

- **Strings**: `"Quoted"` or `` `Multiline backticks` ``.
- **Numbers**: `123` (Integer), `123.45` (Float).
- **BigInt**: `9007199254740991n` (suffix `n`).
- **Decimal**: `123.45d` (suffix `d`) for high-precision financial data.
- **Dates**: `@2025-12-26` or `@2025-12-26T21:15:00Z` (prefix `@`).
- **Booleans**: `true`, `false`.
- **Null**: `null`.

### Objects & Arrays

```exent
{
    // Unquoted keys
    key: "value"
    
    // Nested array
    list: [
        1, 
        2,
        3, // Trailing commas allowed
    ]
    
    // Newline-separated (no commas needed)
    relaxed: {
        a: 1
        b: 2
    }
}
```

### References (DRY)

Avoid repeating large objects by using anchors and references:

```exent
{
    default_style: &blue_theme {
        color: "blue"
        font: "Inter"
    }
    
    sidebar: *blue_theme
    footer: *blue_theme
}
```

---

## 📊 Comparisons

### EXENT vs JSON

| Feature | JSON | EXENT |
| :--- | :--- | :--- |
| **Comments** | ❌ Forbidden | ✅ Supported (`//`, `/* */`) |
| **Trailing Commas** | ❌ Error | ✅ Supported |
| **Quoted Keys** | ✅ Mandatory | ✅ Optional |
| **Native Dates** | ❌ String only | ✅ Native (`@`) |
| **Large Integers** | ❌ Unsafe > 2^53 | ✅ BigInt (`n`) |
| **Financial Data** | ❌ Binary Float | ✅ Decimal (`d`) |
| **Multiline Strings**| ❌ `\n` Escapes | ✅ Backticks (``` ` ```) |
| **References** | ❌ Duplication | ✅ Supported (`&`, `*`) |

### EXENT vs XML

| Feature | XML | EXENT | Why EXENT is Better |
| :--- | :--- | :--- | :--- |
| **Verbosity** | ❌ High (Closing tags) | ✅ Low (Minimalist) | EXENT reduces payload size by avoiding redundant closing tags. |
| **Type Support** | ❌ String only | ✅ Native Types | No more manual `parseInt()` or date parsing from strings. |
| **Binary Mode** | ❌ Base64/External | ✅ Native (B-EXENT) | XML is text-only. B-EXENT is optimized for raw performance. |
| **Readability** | ⚠️ Low (Cluttered) | ✅ High (Clean) | EXENT's syntax is closer to modern programming languages. |
| **References** | ⚠️ ID/IDREF (Complex) | ✅ Anchors (Simple) | EXENT's `&` and `*` system is intuitive and natively supported. |

For a full technical breakdown, see **[Data Format Comparisons](specs/comparisons.md)**.

---

## ⚙️ B-EXENT (Binary Mode)

B-EXENT is designed for maximum performance. It uses a tag-based system and length-prefixed data to eliminate the overhead of string parsing.

**Technical Limits:**
- **Max String/Array/Object size**: 4GB (Uint32 length prefix).
- **Numbers**: Fixed-width 32-bit and 64-bit formats for rapid processing.
- **Precision**: Dedicated tags for Decimals and References ensure high-fidelity data transfer.

### 💡 Use Case: Shared Database Objects

EXENT automatically handles circular references and shared objects. If your database result contains repeated objects (e.g., multiple users sharing the same role), EXENT will detect this and use anchors automatically:

**PHP Controller:**

```php
$data = $db->fetchAll(); // Users sharing Role objects
header('Content-Type: application/exent');
echo Exent\Exent::stringify($data); // Automatic anchoring is native!
```

**JavaScript Frontend:**
```javascript
const data = Exent.parse(await response.text()); 
// References are automatically resolved to shared object instances!
```

See `database-to-frontend.php` for a full demonstration.

See **[Implementation Limits](specs/exent-spec.md#5-implementation-limits)** for more details.

---

## 📂 Project Structure

- `js/`: JavaScript implementation.
- `php/`: PHP implementation.
- `specs/`: Detailed formal specifications.
- `examples/`: Interactive demos and integration examples.
- `tests/`: Comprehensive test suites for JavaScript and PHP.

---

## 🧪 Running Tests

### JavaScript
Requires Node.js:
```bash
node tests/exent-test.js
```

### PHP
Requires PHP CLI:
```bash
php tests/ExentTest.php
```

---

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

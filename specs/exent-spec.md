# EXENT Specification (v1.0.0)

EXENT (EXtended ENtity Transfer) is a state-of-the-art data transfer format designed to be 
human-friendly, high-performance, and more capable than JSON or XML.

- **[Formal Schema](exent-schema.md)**: Detailed grammar and structure.
- **[Data Format Comparisons](comparisons.md)**: Why EXENT is the superior choice.

## 1. Human-Friendly Syntax
- **Comments**: Supports single-line (`// comment`) and multi-line (`/* comment */`).
- **Trailing Commas**: Commas are optional at the end of objects and arrays.
- **Optional Quotes**: Keys do not require quotes if they are valid identifiers (start with a letter/underscore, contain
    alphanumeric characters).
- **Relaxed Commas**: Commas between elements are optional if elements are on new lines.

## 2. Advanced Data Types
- **Native Dates**: Represented using the `@` prefix followed by an ISO-8601 string or timestamp (e.g., `@2025-12-26`).
- **BigInt**: Suffix `n` for large integers (e.g., `12345678901234567890n`).
- **Decimal**: High-precision decimals for financial data, suffixed with `d` (e.g., `10.50d`).
- **References**: 
  - Define an anchor with `&name`.
  - Reference it with `*name`.

## 3. Enhanced Strings
- **Multiline Strings**: Wrapped in backticks (`` ` ``). Supports template-like syntax and preserves whitespace/newlines.

## 4. Optimized Performance
- **Text Mode**: The standard human-readable format.
- **Binary Mode (B-EXENT)**: A compact binary representation for machine-to-machine communication.
  - **Type Tags**: Uses single-byte tags (e.g., `0x03` for Int32, `0x06` for String).
  - **Fixed-width Numbers**: Int32, Float64, and BigInt64 for fast parsing.
  - **Length-prefixing**: Strings, Arrays, and Objects are prefixed with a Uint32 length, eliminating the need for delimiter scanning.
  - **Efficiency**: Typically 30-50% smaller than JSON and significantly faster to parse.

### B-EXENT Tag Specification:
| Tag | Type | Description |
|-----|------|-------------|
| 0x00 | Null | Null value |
| 0x01 | True | Boolean true |
| 0x02 | False | Boolean false |
| 0x03 | Int32 | 32-bit signed integer (Big-Endian) |
| 0x04 | Float64 | 64-bit floating point (IEEE 754 Big-Endian) |
| 0x05 | BigInt64 | 64-bit signed integer (Big-Endian) |
| 0x06 | String | Uint32 length + UTF-8 bytes |
| 0x07 | Date | 64-bit Float64 (Milliseconds since Epoch) |
| 0x08 | Array | Uint32 element count + elements |
| 0x09 | Object | Uint32 entry count + (String key + value) pairs |
| 0x0A | Decimal | 64-bit Float64 |
| 0x0B | Reference | Uint32 anchor ID |

## 5. Implementation Limits
While JSON does not specify technical limits, practical implementations vary. EXENT defines the following limits for its reference implementations:
- **Max String Length**: 4,294,967,295 bytes (4GB) in B-EXENT, as defined by Uint32 length prefix.
- **Max Array Elements**: 4,294,967,295 elements.
- **Max Object Keys**: 4,294,967,295 entries.
- **Max Integer (standard)**: 64-bit signed integer (BigInt64).
- **BigInt (text mode)**: Unlimited precision (limited only by memory).
- **Max Nesting Depth**: 200 (default), configurable via `maxDepth` option in `parse` and `unpack`. This prevents stack overflow attacks from malicious payloads.

Compared to JSON, which often struggles with integers larger than 2^53 in JavaScript environments, EXENT's explicit `BigInt64` tag and `n` suffix ensure consistency across platforms.

## 6. Example
```exent
// EXENT Configuration
{
    project: EXENT
    version: 1.0.0
    
    /* 
       Native Date support 
    */
    created: @2025-12-26
    
    description: `
        EXENT is a bulletproof
        data transfer object.
    `
    
    // Financial data with Decimal
    price: 99.99d
    
    // Large integer support
    iterations: 1000000000000000000n
    
    // Reference system
    default_config: &defaults {
        theme: dark
        timeout: 3000
    }
    
    user_settings: *defaults
    
    tags: [
        high-performance,
        bulletproof,
        modern, // Trailing comma!
    ]
}
```

## 6. Implementations
- **JavaScript**: Functional implementation in `js/exent.js`.
- **PHP**: Functional implementation in `php/Exent.php`.

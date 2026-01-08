# EXENT Schema Specification (v1.0.0)

This document defines the formal structure and validation rules for the **EXENT** (EXtended ENtity Transfer) data format.

## 1. Document Structure
An EXENT document must contain exactly one **Value**. Typically, this is an **Object** or an **Array**, but it can also 
be any **Literal**.

## 2. Values
A **Value** in EXENT can be:
- **Object**
- **Array**
- **String**
- **Number** (Integer, Float, Decimal, BigInt)
- **Date**
- **Boolean** (`true`, `false`)
- **Null** (`null`)
- **Reference**

## 3. Objects
An **Object** is an unordered set of name/value pairs.
- Starts with `{` (left brace) and ends with `}` (right brace).
- Each name is followed by `:` (colon).
- Pairs are separated by `,` (comma) OR a **Newline** (relaxed commas).
- **Keys (Names)**:
    - Can be a **String** (quoted).
    - Can be an **Identifier** (unquoted): Must start with a letter (`a-z`, `A-Z`) or underscore (`_`), followed by 
      alphanumeric characters or underscores.
- **Trailing Commas**: A comma after the last pair is permitted.

## 4. Arrays
An **Array** is an ordered collection of values.
- Starts with `[` (left bracket) and ends with `]` (right bracket).
- Values are separated by `,` (comma) OR a **Newline** (relaxed commas).
- **Trailing Commas**: A comma after the last value is permitted.

## 5. Primitives & Literals

### 5.1 Strings
- **Quoted Strings**: Wrapped in `"` (double quotes). Supports standard escape characters (`\n`, `\t`, `\"`, etc.).
- **Multiline Strings**: Wrapped in `` ` `` (backticks). Preserves all characters including newlines and whitespace.

### 5.2 Numbers
- **Integer**: Standard base-10 digits.
- **Float**: Digits with a decimal point (e.g., `3.14`).
- **BigInt**: Integer suffixed with `n` (e.g., `9007199254740991n`).
- **Decimal**: Number suffixed with `d` for high-precision (e.g., `123.45d`).

### 5.3 Dates
- Starts with `@` followed by an ISO-8601 formatted string (e.g., `@2025-12-26T21:15:00Z` or `@2025-12-26`).

### 5.4 Identifiers (Unquoted Values)
- If a value is not quoted and does not match a Number, Date, Boolean, or Null, it is treated as a **String Identifier**
  if it follows Identifier rules.

## 6. References & Anchors
- **Anchor**: `&identifier` placed before a value (e.g., `&myRef { ... }`).
- **Reference**: `*identifier` used as a value to refer back to an anchored value.

## 7. Comments
- **Single-line**: `//` followed by any text until the end of the line.
- **Multi-line**: `/*` followed by any text until `*/`.

## 8. Formal Grammar (Simplified EBNF)
```ebnf
Exent      ::= Value
Value      ::= Object | Array | String | Number | Date | Boolean | Null | Reference
Object     ::= '{' [ Member { separator Member } [ ',' ] ] '}'
Member     ::= Key ':' Value
Key        ::= Identifier | String
Array      ::= '[' [ Value { separator Value } [ ',' ] ] ']'
separator  ::= ',' | Newline
Identifier ::= [a-zA-Z_][a-zA-Z0-9_]*
Date       ::= '@' ISO8601_String
BigInt     ::= Integer 'n'
Decimal    ::= (Integer | Float) 'd'
Reference  ::= '*' Identifier
Anchor     ::= '&' Identifier
```

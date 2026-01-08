<?php
require_once 'php/Exent.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXENT - Specifications</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --exent-primary: #0066ff;
            --exent-dark: #0a0a0a;
            --exent-light: #f8f9fa;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--exent-light);
            color: #333;
            line-height: 1.6;
        }
        .navbar-brand {
            font-weight: 800;
            letter-spacing: -1px;
        }
        .spec-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 50px;
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        h1, h2, h3 {
            font-weight: 800;
            color: var(--exent-dark);
        }
        h1 { font-size: 2.5rem; margin-bottom: 1.5rem; }
        h2 { font-size: 1.8rem; margin-top: 2.5rem; margin-bottom: 1.2rem; border-bottom: 2px solid var(--exent-light); padding-bottom: 10px; }
        h3 { font-size: 1.4rem; margin-top: 1.5rem; }
        code {
            font-family: 'JetBrains Mono', monospace;
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--exent-primary);
            font-size: 0.9em;
        }
        .sidebar {
            position: sticky;
            top: 20px;
        }
        .nav-link {
            color: #666;
            font-weight: 500;
            padding: 8px 0;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--exent-primary);
        }
        .badge-type {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand text-primary fs-3" href="index.php">EXENT</a>
            <div class="ms-auto d-flex gap-3">
                <a href="index.php" class="btn btn-link text-decoration-none text-dark fw-semibold">Home</a>
                <a href="examples/examples-exent.html" class="btn btn-outline-primary fw-semibold">Interactive Demo</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-lg-3 d-none d-lg-block">
                <div class="sidebar py-5">
                    <h5 class="fw-bold mb-3">Specifications</h5>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="#core-spec">Core Specification</a>
                        <a class="nav-link" href="#schema">Formal Schema</a>
                        <a class="nav-link" href="#comparisons">Comparisons</a>
                        <a class="nav-link" href="#binary">B-EXENT (Binary)</a>
                        <a class="nav-link" href="#limits">Limits</a>
                    </nav>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="spec-container my-5">
                    <section id="core-spec">
                        <h1>EXENT Specification <span class="text-muted fs-4">v1.0.0</span></h1>
                        <p class="lead">EXENT (EXtended ENtity Transfer) is a state-of-the-art data transfer format designed to be human-friendly, high-performance, and more capable than JSON or XML.</p>
                        
                        <h2>1. Human-Friendly Syntax</h2>
                        <ul>
                            <li><strong>Comments</strong>: Supports single-line (<code>// comment</code>) and multi-line (<code>/* comment */</code>).</li>
                            <li><strong>Trailing Commas</strong>: Commas are optional at the end of objects and arrays.</li>
                            <li><strong>Optional Quotes</strong>: Keys do not require quotes if they are valid identifiers (start with a letter/underscore, contain alphanumeric characters).</li>
                            <li><strong>Relaxed Commas</strong>: Commas between elements are optional if elements are on new lines.</li>
                        </ul>

                        <h2>2. Advanced Data Types</h2>
                        <div class="mb-3">
                            <span class="badge bg-primary badge-type">Date</span> <code>@2025-12-26</code> - Represented using the <code>@</code> prefix followed by an ISO-8601 string or timestamp.
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-success badge-type">BigInt</span> <code>1234567890n</code> - Suffix <code>n</code> for large integers.
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-info badge-type">Decimal</span> <code>10.50d</code> - High-precision decimals for financial data, suffixed with <code>d</code>.
                        </div>
                        <div class="mb-3">
                            <span class="badge bg-warning badge-type">Reference</span> <code>&amp;anchor</code> and <code>*reference</code> - Define an anchor with <code>&amp;name</code> and reference it with <code>*name</code>.
                        </div>

                        <h2>3. Enhanced Strings</h2>
                        <p><strong>Multiline Strings</strong>: Wrapped in backticks (<code>`</code>). Supports preserving whitespace and newlines natively.</p>
                        <pre><code>description: `
    EXENT is a bulletproof
    data transfer object.
`</code></pre>
                    </section>

                    <section id="schema" class="mt-5">
                        <hr>
                        <h2>Formal Schema</h2>
                        <p>The simplified EBNF grammar for EXENT:</p>
                        <pre><code>Exent      ::= Value
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
Anchor     ::= '&amp;' Identifier</code></pre>
                    </section>

                    <section id="comparisons" class="mt-5">
                        <hr>
                        <h2>EXENT vs JSON</h2>
                        <table class="table mb-5">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>JSON</th>
                                    <th>EXENT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Comments</td><td>❌ Forbidden</td><td>✅ Supported</td></tr>
                                <tr><td>Trailing Commas</td><td>❌ Error</td><td>✅ Supported</td></tr>
                                <tr><td>Quoted Keys</td><td>✅ Mandatory</td><td>✅ Optional</td></tr>
                                <tr><td>Native Dates</td><td>❌ String only</td><td>✅ Native (@)</td></tr>
                                <tr><td>Large Integers</td><td>❌ Unsafe > 2^53</td><td>✅ BigInt (n)</td></tr>
                                <tr><td>Financial Data</td><td>❌ Binary Float</td><td>✅ Decimal (d)</td></tr>
                                <tr><td>Multiline Strings</td><td>❌ \n Escapes</td><td>✅ Backticks (`)</td></tr>
                                <tr><td>References</td><td>❌ Duplication</td><td>✅ Supported</td></tr>
                            </tbody>
                        </table>

                        <h2>EXENT vs XML</h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>XML</th>
                                    <th>EXENT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Verbosity</td><td>❌ High (Closing tags)</td><td>✅ Low (Minimalist)</td></tr>
                                <tr><td>Type Support</td><td>❌ String only</td><td>✅ Native Types</td></tr>
                                <tr><td>Binary Mode</td><td>❌ Text-only</td><td>✅ Native (B-EXENT)</td></tr>
                                <tr><td>Parsing Speed</td><td>❌ Slow (DOM/SAX)</td><td>✅ Fast (Direct)</td></tr>
                                <tr><td>Schema</td><td>⚠️ Complex (XSD)</td><td>✅ Schema-less</td></tr>
                                <tr><td>References</td><td>⚠️ Manual (IDREF)</td><td>✅ Native (&/*)</td></tr>
                            </tbody>
                        </table>
                    </section>

                    <section id="binary" class="mt-5">
                        <hr>
                        <h2>B-EXENT (Binary Mode)</h2>
                        <p>A compact binary representation for machine-to-machine communication.</p>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tag</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>0x00</td><td>Null</td><td>Null value</td></tr>
                                <tr><td>0x01</td><td>True</td><td>Boolean true</td></tr>
                                <tr><td>0x02</td><td>False</td><td>Boolean false</td></tr>
                                <tr><td>0x03</td><td>Int32</td><td>32-bit signed integer</td></tr>
                                <tr><td>0x04</td><td>Float64</td><td>64-bit floating point</td></tr>
                                <tr><td>0x05</td><td>BigInt64</td><td>64-bit signed integer</td></tr>
                                <tr><td>0x06</td><td>String</td><td>Uint32 length + UTF-8 bytes</td></tr>
                                <tr><td>0x07</td><td>Date</td><td>64-bit Float64 (ms since Epoch)</td></tr>
                                <tr><td>0x08</td><td>Array</td><td>Uint32 element count + elements</td></tr>
                                <tr><td>0x09</td><td>Object</td><td>Uint32 entry count + pairs</td></tr>
                                <tr><td>0x0A</td><td>Decimal</td><td>64-bit Float64</td></tr>
                                <tr><td>0x0B</td><td>Reference</td><td>Uint32 anchor ID</td></tr>
                            </tbody>
                        </table>
                    </section>

                    <section id="limits" class="mt-5">
                        <hr>
                        <h2>Implementation Limits</h2>
                        <ul>
                            <li><strong>Max String Length</strong>: 4GB (Uint32 length prefix).</li>
                            <li><strong>Max Array Elements</strong>: 4,294,967,295 elements.</li>
                            <li><strong>Max Object Keys</strong>: 4,294,967,295 entries.</li>
                            <li><strong>Max Integer</strong>: 64-bit signed (standard), unlimited in text mode.</li>
                            <li><strong>Max Nesting Depth</strong>: 200 (default, configurable).</li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-4 text-center text-muted border-top bg-white">
        <div class="container">
            <p class="mb-0">&copy; 2025 EXENT Data Format. State-of-the-art data transfer.</p>
        </div>
    </footer>
</body>
</html>

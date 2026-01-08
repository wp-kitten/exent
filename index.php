<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXENT - EXtended ENtity Transfer</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --exent-primary: #0066ff;
            --exent-secondary: #00d2ff;
            --exent-dark: #0a0a0a;
            --exent-light: #f8f9fa;
        }
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            overflow-x: hidden;
        }
        .hero-section {
            background: var(--exent-dark);
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(0, 102, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(0, 210, 255, 0.15) 0%, transparent 50%);
            color: white;
            padding: 120px 0;
            position: relative;
        }
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom right, transparent 49.5%, var(--exent-light) 50%);
        }
        .exent-logo {
            font-weight: 800;
            letter-spacing: -2px;
            font-size: 5rem;
            background: linear-gradient(135deg, #fff 0%, #aaa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: var(--exent-primary);
            margin-bottom: 1.5rem;
            height: 64px;
            width: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 102, 255, 0.05);
            border-radius: 16px;
        }
        .card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: 20px;
            background: white;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            border-color: var(--exent-primary);
        }
        .btn-primary {
            background: var(--exent-primary);
            border: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #0052cc;
            transform: scale(1.05);
        }
        .btn-outline-light {
            border-width: 2px;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
        }
        .btn-outline-primary {
            border-width: 2px;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 600;
        }
        code {
            font-family: 'JetBrains Mono', monospace;
        }
        .code-window {
            background: #1e1e1e;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            text-align: left;
        }
        .code-header {
            background: #333;
            padding: 10px 15px;
            display: flex;
            gap: 6px;
        }
        .dot { height: 12px; width: 12px; border-radius: 50%; }
        .red { background: #ff5f56; }
        .yellow { background: #ffbd2e; }
        .green { background: #27c93f; }
        .code-body { padding: 20px; font-size: 0.9rem; }
    </style>
</head>
<body class="bg-light">

    <section class="hero-section text-center">
        <div class="container">
            <h1 class="exent-logo mb-2">EXENT</h1>
            <p class="lead mb-5 fs-4 opacity-75">EXtended ENtity Transfer</p>
            <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mb-5">
                <a href="specifications.php" class="btn btn-primary btn-lg">Explore Specifications</a>
                <a href="examples/examples-exent-php.html" class="btn btn-outline-light btn-lg">PHP Integration</a>
                <a href="https://github.com/wp-kitten/exent" class="btn btn-outline-light btn-lg" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-github me-2" viewBox="0 0 16 16">
                        <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                    </svg>
                    GitHub
                </a>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
            <div class="feature col">
                <div class="card h-100 p-4">
                    <div class="feature-icon d-inline-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-lightning-fill" viewBox="0 0 16 16">
                            <path d="M5.52.359A.5.5 0 0 1 6 0h4a.5.5 0 0 1 .474.658L8.694 6H12.5a.5.5 0 0 1 .395.807l-7 9a.5.5 0 0 1-.873-.454L6.823 9.5H3.5a.5.5 0 0 1-.48-.641l2.5-8.5z"/>
                        </svg>
                    </div>
                    <h3 class="fs-4 fw-bold">State-of-the-Art</h3>
                    <p>A robust, modern data format designed to overcome the limitations of JSON and XML with native type support.</p>
                </div>
            </div>
            <div class="feature col">
                <div class="card h-100 p-4">
                    <div class="feature-icon d-inline-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
                            <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                            <path d="M6.854 4.646a.5.5 0 0 1 0 .708L5.207 7l1.647 1.646a.5.5 0 0 1-.708.708l-2-2a.5.5 0 0 1 0-.708l2-2a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L10.793 7l-1.647 1.646a.5.5 0 0 0 .708.708l2-2a.5.5 0 0 0 0-.708l-2-2a.5.5 0 0 0-.708 0z"/>
                        </svg>
                    </div>
                    <h3 class="fs-4 fw-bold">Full PHP Support</h3>
                    <p>Native PHP implementation with high-performance parsing and stringification, supporting Dates, BigInt, and Decimals.</p>
                </div>
            </div>
            <div class="feature col">
                <div class="card h-100 p-4">
                    <div class="feature-icon d-inline-flex align-items-center justify-content-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                        </svg>
                    </div>
                    <h3 class="fs-4 fw-bold">Human Readable</h3>
                    <p>Designed for humans. Supports comments, unquoted keys, and a clean syntax that's easy to write and maintain.</p>
                </div>
            </div>
        </div>

        <div class="row align-items-center">
            <div class="col-lg-7">
                <h2 class="fw-bold mb-4">Ready to explore?</h2>
                <p class="text-muted mb-4 fs-5">Check out our interactive examples to see EXENT in action. We provide both a browser-based parser demo and a full PHP backend integration example.</p>
                <div class="d-flex gap-3">
                    <a href="specifications.php" class="btn btn-primary">Specifications</a>
                    <a href="examples/examples-exent.html" class="btn btn-outline-primary">Interactive Demo</a>
                    <a href="examples/examples-exent-php.html" class="btn btn-outline-primary">PHP Integration</a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <div class="code-window">
                    <div class="code-header">
                        <div class="dot red"></div>
                        <div class="dot yellow"></div>
                        <div class="dot green"></div>
                    </div>
                    <div class="code-body">
                        <code class="text-info">// EXENT PHP Example</code><br>
                        <code class="text-light">echo Exent\Exent::stringify([</code><br>
                        <code class="text-light">&nbsp;&nbsp;status: 'success',</code><br>
                        <code class="text-light">&nbsp;&nbsp;date: new DateTime()</code><br>
                        <code class="text-light">]);</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="py-4 text-center text-muted border-top bg-white">
        <div class="container">
            <p class="mb-2">&copy; 2025 EXENT Data Format. State-of-the-art data transfer.</p>
            <a href="https://github.com/wp-kitten/exent" class="text-decoration-none text-muted" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-github me-1" viewBox="0 0 16 16">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
                </svg>
                View on GitHub
            </a>
        </div>
    </footer>
</body>
</html>
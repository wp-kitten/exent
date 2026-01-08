<?php
/**
 * Backend EXENT Provider
 */

require_once 'php/Exent.php';

use Exent\Exent;

// Sample rich data
$data = [
    'status' => 'success',
    'timestamp' => new DateTime(),
    'payload' => [
        'project' => 'EXENT PHP Integration',
        'message' => 'This data was generated on the server and sent as EXENT.',
        'features' => [
            'Native Dates',
            'BigInt Support',
            'Decimal Precision',
            'Unquoted Keys'
        ],
        'server_info' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => '1.5MB', // Unquoted value example
            'load_time' => "0.0042d" // Decimal literal
        ],
        'big_num' => "9007199254740991n", // BigInt literal as string for stringify to handle
    ]
];

// Set content type based on format
$format = isset($_GET['format']) ? $_GET['format'] : 'text';

if ($format === 'binary') {
    header('Content-Type: application/octet-stream');
    echo Exent::pack($data);
}
else {
    header('Content-Type: application/exent; charset=UTF-8');
    echo Exent::stringify($data);
}

<?php
require_once '../php/Exent.php';

use Exent\Exent;

$exentCode = "// EXENT Configuration for PHP\n" .
"{
    project: EXENT-PHP
    version: 1.0.0
    
    /* 
       Native Date literals!
    */
    release_date: @2025-12-26
    
    description: `
        EXENT is a bulletproof
        data transfer object, 
        now with PHP support!
    `
    
    stats: {
        // BigInt support
        requests: 5000000000n
        // Decimal support
        latency: 0.15d
    }
    
    tags: [
        high-performance
        php-ready
        bulletproof
    ]
}";

try {
    echo "--- Parsing EXENT ---\n";
    $parsed = Exent::parse($exentCode);
    
    echo "Project: " . $parsed['project'] . "\n";
    echo "Version: " . $parsed['version'] . "\n";
    echo "Date Type: " . get_class($parsed['release_date']) . " (" . $parsed['release_date']->format('Y-m-d') . ")\n";
    $requests = $parsed['stats']['requests'];
    echo "BigInt: " . (is_object($requests) ? gmp_strval($requests) : $requests) . " (Type: " . (is_object($requests) ? 'GMP object' : gettype($requests)) . ")\n";
    echo "Decimal: " . $parsed['stats']['latency'] . "\n";
    
    echo "\n--- Stringifying back to EXENT ---\n";
    $backToExent = Exent::stringify($parsed);
    echo $backToExent . "\n";

}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

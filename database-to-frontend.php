<?php
require_once 'php/Exent.php';

use Exent\Exent;

// Use case: Database results with repeated objects (e.g. users sharing the same role)
$roleAdmin = (object)[
    'id' => 1,
    'name' => 'Administrator',
    'permissions' => ['read', 'write', 'delete']
];

$roleEditor = (object)[
    'id' => 2,
    'name' => 'Editor',
    'permissions' => ['read', 'write']
];

$users = [
    [
        'id' => 101,
        'username' => 'alice',
        'role' => $roleAdmin
    ],
    [
        'id' => 102,
        'username' => 'bob',
        'role' => $roleEditor
    ],
    [
        'id' => 103,
        'username' => 'charlie',
        'role' => $roleAdmin // Shared object!
    ]
];

echo "--- Original PHP Data (with shared objects) ---\n";
echo "User 0 role matches User 2 role: " . ($users[0]['role'] === $users[2]['role'] ? "YES" : "NO") . "\n\n";

echo "--- EXENT Text Representation (Automatic Anchoring) ---\n";
$exentText = Exent::stringify($users);
echo $exentText . "\n\n";

echo "--- Parsed back into PHP ---\n";
$parsedUsers = Exent::parse($exentText, true);
echo "User 0 role matches User 2 role: " . ($parsedUsers[0]['role'] === $parsedUsers[2]['role'] ? "YES" : "NO") . "\n";
echo "Role name: " . $parsedUsers[2]['role']['name'] . "\n";

echo "\n--- EXENT Binary Representation (B-EXENT) ---\n";
$packed = Exent::pack($users);
echo "Binary size: " . strlen($packed) . " bytes\n";
$unpacked = Exent::unpack($packed, true);
echo "User 0 role matches User 2 role: " . (json_encode($unpacked[0]['role']) === json_encode($unpacked[2]['role']) ? "YES" : "NO") . "\n";
if ($unpacked[0]['role'] !== $unpacked[2]['role']) {
    echo "Direct comparison failed, checking values...\n";
    var_dump($unpacked[0]['role']);
    var_dump($unpacked[2]['role']);
}

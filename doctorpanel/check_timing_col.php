<?php
// Quick column info
require_once __DIR__ . '/../admin/inc/db.php';
$pdo = getPDO();
$db = defined('DB_NAME') ? DB_NAME : 'drmap';
$stmt = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'doctors' AND COLUMN_NAME = 'timing'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
if ($col) {
    echo "Timing column type: " . $col['COLUMN_TYPE'] . "\n";
} else {
    echo "timing column not found\n";
}

// Check row
$stmt = $pdo->prepare("SELECT id, CHAR_LENGTH(timing) as len FROM doctors WHERE id = ? LIMIT 1");
$stmt->execute([$argv[1] ?? 1]);
$row = $stmt->fetch();
if ($row) {
    echo "Doctor {$row['id']} timing length: {$row['len']} chars\n";
}

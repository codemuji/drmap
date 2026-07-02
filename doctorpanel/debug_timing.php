<?php
// Usage: php debug_timing.php [doctor_id]
require_once __DIR__ . '/../admin/inc/db.php';
$pdo = getPDO();
$id = isset($argv[1]) ? (int)$argv[1] : 0;
if (!$id) {
    echo "Usage: php debug_timing.php [doctor_id]\n";
    exit(1);
}
$stmt = $pdo->prepare('SELECT id, timing FROM doctors WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo "Doctor id {$id} not found.\n";
    exit(1);
}
echo "Doctor id: " . $row['id'] . "\n";
echo "Stored timing (raw):\n";
var_export($row['timing']);
echo "\nDecoded JSON (if any):\n";
$dec = json_decode($row['timing'], true);
var_export($dec);
echo "\n";

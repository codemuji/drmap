<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$can_edit = isset($_POST['can_edit']) ? (int)$_POST['can_edit'] : 0;

if ($doctor_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid doctor ID']);
    exit;
}

// Ensure can_edit is 0 or 1
$can_edit = $can_edit ? 1 : 0;

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE doctors SET can_edit = :can_edit WHERE id = :id');
    $stmt->execute([
        'can_edit' => $can_edit,
        'id' => $doctor_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Edit access updated successfully',
        'can_edit' => $can_edit
    ]);
} catch (PDOException $e) {
    error_log('Toggle edit access error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}

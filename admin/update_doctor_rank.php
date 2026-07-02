<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$rank = isset($_POST['rank']) && $_POST['rank'] !== '' ? (int)$_POST['rank'] : null;

if (!$doctorId) {
    echo json_encode(['success' => false, 'error' => 'Invalid doctor ID']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE doctors SET `rank` = :rank WHERE id = :id');
    $stmt->execute([
        ':rank' => $rank,
        ':id' => $doctorId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Rank updated successfully',
        'doctor_id' => $doctorId,
        'rank' => $rank
    ]);
} catch (PDOException $e) {
    error_log('Error updating doctor rank: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

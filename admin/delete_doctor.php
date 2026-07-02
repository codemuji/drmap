<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

// Only accept POST to delete a doctor
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: doctors.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: doctors.php');
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM doctors WHERE id = :id');
    $stmt->execute([':id' => $id]);
} catch (PDOException $e) {
    error_log('Failed deleting doctor id=' . $id . ' : ' . $e->getMessage());
}

header('Location: doctors.php');
exit;
?>
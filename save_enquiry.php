<?php
require_once __DIR__ . '/admin/inc/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$doctor_id = (int)($_POST['doctor_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate required fields
if (empty($doctor_id) || $doctor_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor ID']);
    exit;
}

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

// Verify doctor exists
try {
    $pdo = getPDO();
    
    $verify_stmt = $pdo->prepare('SELECT id FROM doctors WHERE id = ? LIMIT 1');
    $verify_stmt->execute([$doctor_id]);
    $doctor = $verify_stmt->fetch();
    
    if (!$doctor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit;
    }
    
    // Insert enquiry
    $insert_stmt = $pdo->prepare('
        INSERT INTO enquiries (doctor_id, name, email, phone, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ');
    
    $insert_stmt->execute([
        $doctor_id,
        $name,
        $email,
        $phone,
        !empty($message) ? $message : null,
        'new'
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your enquiry has been submitted. We will contact you soon.',
        'enquiry_id' => $pdo->lastInsertId(),
        'track_enquiry' => true  // Signal to frontend to track this enquiry
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your request. Please try again.'
    ]);
    error_log('Enquiry submission error: ' . $e->getMessage());
}
?>

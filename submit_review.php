<?php
require_once __DIR__ . '/admin/inc/db.php';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    
    $response = ['success' => false, 'message' => ''];
    
    // Validate
    if ($doctorId <= 0) {
        $response['message'] = 'Invalid doctor selection';
    } elseif (empty($name)) {
        $response['message'] = 'Name is required';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Valid email is required';
    } elseif ($rating < 1 || $rating > 5) {
        $response['message'] = 'Please select a rating';
    } elseif (empty($reviewText)) {
        $response['message'] = 'Review text is required';
    } else {
        try {
            $pdo = getPDO();
            
            // Check if user already reviewed this doctor
            $checkStmt = $pdo->prepare('SELECT id FROM reviews WHERE doctor_id = ? AND customer_email = ?');
            $checkStmt->execute([$doctorId, $email]);
            
            if ($checkStmt->fetch()) {
                $response['message'] = 'You have already submitted a review for this doctor';
            } else {
                // Insert review
                $stmt = $pdo->prepare('INSERT INTO reviews (doctor_id, customer_name, customer_email, rating, review_text, status) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$doctorId, $name, $email, $rating, $reviewText, 'pending']);
                
                $response['success'] = true;
                $response['message'] = 'Thank you! Your review has been submitted and is pending approval.';
            }
        } catch (PDOException $e) {
            error_log('Review submission error: ' . $e->getMessage());
            $response['message'] = 'Error submitting review. Please try again.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

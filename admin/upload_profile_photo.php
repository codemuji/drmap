<?php
require_once __DIR__ . '/inc/auth.php';
require_login();

header('Content-Type: application/json');

// Get doctor ID from POST or use GET
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($doctor_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Doctor ID not specified']);
    exit;
}

if (empty($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_photo'];

// Server-side MIME detection
$allowed = ['image/jpeg','image/png','image/webp','image/gif'];
$mime = '';
if (function_exists('finfo_open')) {
    $f = finfo_open(FILEINFO_MIME_TYPE);
    if ($f) {
        $mime = finfo_file($f, $file['tmp_name']);
        finfo_close($f);
    }
}
if (empty($mime) && function_exists('getimagesize')) {
    $info = @getimagesize($file['tmp_name']);
    if (!empty($info['mime'])) $mime = $info['mime'];
}
if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type', 'detected' => $mime]);
    exit;
}

$maxBytes = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
    exit;
}

$baseDir = __DIR__ . '/../uploads';
$targetDir = $baseDir . '/doctors/' . $doctor_id . '/profile';
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create directory']);
        exit;
    }
}

$origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destPath = $targetDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
    exit;
}

$webPath = 'uploads/doctors/' . $doctor_id . '/profile/' . $filename;

// compute absolute URL based on script location (handles subfolder installs)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$siteRoot = dirname(dirname($_SERVER['SCRIPT_NAME']));
$absoluteUrl = rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $siteRoot, '/') . '/' . $webPath;

// Update doctor's photo column
try {
    $pdo = getPDO();
    $u = $pdo->prepare('UPDATE doctors SET photo = :photo WHERE id = :id');
    $u->execute(['photo' => $webPath, 'id' => $doctor_id]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'url' => $webPath, 'absolute_url' => $absoluteUrl, 'local_path' => $destPath, 'warning' => 'Uploaded but failed to update DB: ' . $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true, 'url' => $webPath, 'absolute_url' => $absoluteUrl, 'local_path' => $destPath]);
exit;

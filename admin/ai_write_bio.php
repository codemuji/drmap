<?php
// AI & Offline description generator
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Session Authentication
$is_admin = !empty($_SESSION['admin']);
$is_doctor = !empty($_SESSION['doctor']);
if (!$is_admin && !$is_doctor) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// 2. Session-based Rate Limiting (Max 5 requests per minute per user)
$now = time();
if (!isset($_SESSION['ai_requests'])) {
    $_SESSION['ai_requests'] = [];
}
// Filter requests from the last 60 seconds
$_SESSION['ai_requests'] = array_filter($_SESSION['ai_requests'], function($time) use ($now) {
    return ($now - $time) < 60;
});
if (count($_SESSION['ai_requests']) >= 5) {
    header('HTTP/1.1 429 Too Many Requests');
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please wait a minute before requesting again.']);
    exit;
}
$_SESSION['ai_requests'][] = $now;

// 3. Parse Inputs
$name = trim($_GET['name'] ?? '');
// Clean "Dr." or "Dr " prefix if present so that we don't get double "Dr. Dr." in output
$name = preg_replace('/^Dr\.?\s+/i', '', $name);

$specialty = trim($_GET['specialty'] ?? '');
$qualification = trim($_GET['qualification'] ?? '');
$experience = (int)($_GET['experience'] ?? 0);
$type = trim($_GET['type'] ?? 'bio');

if (empty($name) || empty($specialty)) {
    echo json_encode(['success' => false, 'message' => 'Name and specialty are required']);
    exit;
}

// Helper to generate offline bio/motto as a fallback
function generate_fallback_bio($name, $specialty, $qualification, $experience, $type = 'bio') {
    if ($type === 'motto' || $type === 'quote' || $type === 'speech') {
        $templates = [
            "To provide compassionate, comprehensive, and evidence-based care to every patient.",
            "Dedicated to healing, committed to care, and focused on improving patient lives every day.",
            "Empowering patients through personalized healthcare, open communication, and clinical excellence."
        ];
        return $templates[rand(0, count($templates) - 1)];
    }

    $templates = [
        "Dr. {name} is a highly accomplished {specialty} with over {experience} years of clinical expertise. Having completed {qualification}, they specialize in patient-centric diagnostic and advanced treatment programs. Dr. {name} is deeply committed to delivering compassionate, evidence-based care to improve clinical outcomes.",
        "With a distinguished career spanning {experience} years, Dr. {name} is a dedicated {specialty} certified with {qualification}. Known for their thorough approach to diagnosis and individualized therapeutic strategies, Dr. {name} remains at the forefront of medical technology to provide optimal care.",
        "Dr. {name} ({qualification}) is a board-certified {specialty} bringing {experience} years of healthcare excellence. They are dedicated to clinical research and advanced procedures, ensuring patients receive the highest standard of modern treatments with warmth and professional care."
    ];

    $chosen = $templates[rand(0, count($templates) - 1)];

    return str_replace(
        ['{name}', '{specialty}', '{qualification}', '{experience}'],
        [$name, $specialty, $qualification ?: 'medical qualifications', $experience],
        $chosen
    );
}

// 4. Try OpenRouter AI Generation
$bio = null;
$error = null;

try {
    // Load environment loader and client
    require_once __DIR__ . '/inc/env.php';
    require_once __DIR__ . '/inc/openrouter.php';

    $apiKey = getenv('OPENROUTER_API_KEY');
    $model = getenv('OPENROUTER_MODEL') ?: 'google/gemini-2.5-flash';

    if (!empty($apiKey)) {
        $client = new OpenRouterClient($apiKey, $model);
        $bio = $client->generateBio($name, $specialty, $qualification, $experience, $type);
    } else {
        $error = "OpenRouter API key is not configured.";
    }
} catch (Exception $e) {
    $error = "AI Generation failed: " . $e->getMessage();
    error_log($error);
}

// 5. Fallback if AI didn't return a biography
if (empty($bio)) {
    $bio = generate_fallback_bio($name, $specialty, $qualification, $experience, $type);
    $response = [
        'success' => true,
        'bio' => $bio,
        'source' => 'fallback',
        'debug_message' => $error
    ];
} else {
    $response = [
        'success' => true,
        'bio' => $bio,
        'source' => 'openrouter'
    ];
}

echo json_encode($response);
?>

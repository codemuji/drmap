<?php
// Offline description generator (template-based)
header('Content-Type: application/json');

$name = trim($_GET['name'] ?? '');
$specialty = trim($_GET['specialty'] ?? '');
$qualification = trim($_GET['qualification'] ?? '');
$experience = (int)($_GET['experience'] ?? 0);

if (empty($name) || empty($specialty)) {
    echo json_encode(['success' => false, 'message' => 'Name and specialty are required']);
    exit;
}

// Select templates based on variables
$templates = [
    "Dr. {name} is a highly accomplished {specialty} with over {experience} years of clinical expertise. Having completed {qualification}, they specialize in patient-centric diagnostic and advanced treatment programs. Dr. {name} is deeply committed to delivering compassionate, evidence-based care to improve clinical outcomes.",
    "With a distinguished career spanning {experience} years, Dr. {name} is a dedicated {specialty} certified with {qualification}. Known for their thorough approach to diagnosis and individualized therapeutic strategies, Dr. {name} remains at the forefront of medical technology to provide optimal care.",
    "Dr. {name} ({qualification}) is a board-certified {specialty} bringing {experience} years of healthcare excellence. They are dedicated to clinical research and advanced procedures, ensuring patients receive the highest standard of modern treatments with warmth and professional care."
];

$chosen = $templates[rand(0, count($templates) - 1)];

$bio = str_replace(
    ['{name}', '{specialty}', '{qualification}', '{experience}'],
    [$name, $specialty, $qualification ?: 'medical qualifications', $experience],
    $chosen
);

echo json_encode(['success' => true, 'bio' => $bio]);
?>

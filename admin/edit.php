<?php
require_once __DIR__ . '/inc/auth.php';
require_login();

// Get doctor ID from URL parameter
$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($doctor_id <= 0) {
    header('Location: doctors.php');
    exit;
}

$pdo = getPDO();

$success = false;
$error = '';

// Get doctor data
$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: doctors.php');
    exit;
}

// Decode social links
$social = json_decode($doctor['social'] ?? '{}', true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: log raw POST timing to help trace emptying issue
    $rawPostTiming = isset($_POST['timing']) ? $_POST['timing'] : '(missing)';
    error_log('DEBUG POST raw_timing (len=' . strlen((string)$rawPostTiming) . '): ' . substr((string)$rawPostTiming,0,1000));

    $fields = [
        'name' => trim($_POST['name'] ?? ''),
        'specialty' => trim($_POST['specialty'] ?? ''),
        'experience' => max(0, (int)($_POST['experience'] ?? 0)),
        'qualification' => trim($_POST['qualification'] ?? ''),
        'rating' => min(5, max(0, (float)($_POST['rating'] ?? 0))),
        'photo' => filter_var($_POST['photo'] ?? '', FILTER_SANITIZE_URL),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'new_password' => trim($_POST['new_password'] ?? ''),
        'confirm_password' => trim($_POST['confirm_password'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'timing' => trim($_POST['timing'] ?? ''),
        'about' => trim($_POST['about'] ?? ''),
        'speech' => trim($_POST['speech'] ?? ''),
        'addresses' => trim($_POST['addresses'] ?? ''),
        'practice_city' => trim($_POST['practice_city'] ?? ''),
        'quick_facts' => trim($_POST['quick_facts'] ?? ''),
        'locations' => trim($_POST['locations'] ?? ''),
        'videos' => trim($_POST['videos'] ?? ''),
        'gallery' => trim($_POST['gallery'] ?? ''),
        'reviews' => trim($_POST['reviews'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['active', 'on-leave', 'inactive']) ? $_POST['status'] : 'active',
        'linkedin' => filter_var($_POST['linkedin'] ?? '', FILTER_SANITIZE_URL),
        'twitter' => filter_var($_POST['twitter'] ?? '', FILTER_SANITIZE_URL),
        'facebook' => filter_var($_POST['facebook'] ?? '', FILTER_SANITIZE_URL),
    ];

    // Debug: log sanitized fields timing
    error_log('DEBUG fields[timing] after sanitize (len=' . strlen($fields['timing']) . '): ' . substr($fields['timing'],0,1000));

    // If a profile photo was uploaded with the form, handle and store it now
    if (!empty($_FILES['profile_photo']) && isset($_FILES['profile_photo']['error']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if (!in_array($file['type'], $allowed)) {
            $error = 'Uploaded profile photo has an invalid file type.';
        } elseif ($file['size'] > $maxBytes) {
            $error = 'Uploaded profile photo is too large (max 5MB).';
        } else {
            $baseDir = __DIR__ . '/../uploads';
            $targetDir = $baseDir . '/doctors/' . $doctor_id;
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    $error = 'Failed to create upload directory for profile photo.';
                }
            }
            if (empty($error)) {
                $origName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($file['name']));
                $ext = pathinfo($origName, PATHINFO_EXTENSION);
                $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destPath = $targetDir . '/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    $error = 'Failed to move uploaded profile photo.';
                } else {
                    $fields['photo'] = 'uploads/doctors/' . $doctor_id . '/' . $filename;
                }
            }
        }
    }

    // Validate required fields
    if (empty($fields['name'])) {
        $error = 'Name is required';
    } elseif (empty($fields['specialty'])) {
        $error = 'Specialty is required';
    } elseif (!empty($fields['email']) && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (!empty($fields['new_password']) && strlen($fields['new_password']) < 6) {
        $error = 'New password must be at least 6 characters long';
    } elseif ($fields['new_password'] !== '' && $fields['new_password'] !== $fields['confirm_password']) {
        $error = 'New password and confirmation do not match';
    } else {
        // Validate JSON fields (if provided)
        $jsonFields = ['quick_facts','videos','gallery','reviews','timing'];
        $encodedJson = [];
        // Use an empty JSON object for timing when nothing is provided
        $defaultTiming = json_encode((object)[]);
        foreach ($jsonFields as $jf) {
            $raw = $fields[$jf];
            if ($raw === '') {
                // Preserve existing timing value when user did not modify it in the form
                if ($jf === 'timing') {
                    if (!empty($doctor['timing'])) {
                        $encodedJson[$jf] = $doctor['timing'];
                    } else {
                        $encodedJson[$jf] = $defaultTiming;
                    }
                } else {
                    $encodedJson[$jf] = null;
                }
                continue;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = ucfirst(str_replace('_',' ',$jf)) . ' must be valid JSON.';
                break;
            }
            if ($jf === 'timing' && ($decoded === null || $decoded === [])) {
                $encodedJson[$jf] = $defaultTiming;
            } else {
                $encodedJson[$jf] = json_encode($decoded);
            }
        }

        if (empty($error)) {
            try {
                // Combine social links into JSON
                $socialPayload = [
                    'linkedin' => $fields['linkedin'] ?? '',
                    'twitter' => $fields['twitter'] ?? '',
                    'facebook' => $fields['facebook'] ?? '',
                ];

                // Build SQL with optional password field
                $sql = "UPDATE doctors SET 
                        name = :name,
                        specialty = :specialty,
                        experience = :experience,
                        qualification = :qualification,
                        practice_city = :practice_city,
                        rating = :rating,
                        photo = :photo,
                        phone = :phone,
                        email = :email,
                        whatsapp = :whatsapp,
                        timing = :timing,
                        about = :about,
                        speech = :speech,
                        addresses = :addresses,
                        quick_facts = :quick_facts,
                        locations = :locations,
                        videos = :videos,
                        gallery = :gallery,
                        reviews = :reviews,
                        status = :status,
                        social = :social,
                        updated_at = NOW()";

                $updatePassword = false;
                if (!empty($fields['new_password'])) {
                    $sql .= ", password = :password";
                    $updatePassword = true;
                }

                $sql .= " WHERE id = :id";

                $stmt = $pdo->prepare($sql);

                $params = [
                    'name' => $fields['name'],
                    'specialty' => $fields['specialty'],
                    'experience' => $fields['experience'],
                    'qualification' => $fields['qualification'],
                    'rating' => $fields['rating'],
                    'photo' => $fields['photo'],
                    'phone' => $fields['phone'],
                    'email' => $fields['email'],
                    'whatsapp' => $fields['whatsapp'],
                    'timing' => $encodedJson['timing'],
                    'about' => $fields['about'],
                    'speech' => $fields['speech'],
                    'locations' => (function() {
                        $locationsArr = [];
                        $loc1_addr = trim($_POST['location1_address'] ?? '');
                        $loc1_lat = trim($_POST['location1_lat'] ?? '');
                        $loc1_lng = trim($_POST['location1_lng'] ?? '');
                        
                        if ($loc1_addr !== '') {
                            $locationsArr[] = [
                                'address' => $loc1_addr,
                                'lat' => $loc1_lat,
                                'lng' => $loc1_lng
                            ];
                        }
                        
                        $loc2_addr = trim($_POST['location2_address'] ?? '');
                        $loc2_lat = trim($_POST['location2_lat'] ?? '');
                        $loc2_lng = trim($_POST['location2_lng'] ?? '');
                        
                        if ($loc2_addr !== '') {
                            $locationsArr[] = [
                                'address' => $loc2_addr,
                                'lat' => $loc2_lat,
                                'lng' => $loc2_lng
                            ];
                        }
                        return !empty($locationsArr) ? json_encode($locationsArr) : null;
                    })(),
                    'addresses' => (function() {
                        $addressesArr = [];
                        $loc1_addr = trim($_POST['location1_address'] ?? '');
                        if ($loc1_addr !== '') {
                            $addressesArr[] = $loc1_addr;
                        }
                        $loc2_addr = trim($_POST['location2_address'] ?? '');
                        if ($loc2_addr !== '') {
                            $addressesArr[] = $loc2_addr;
                        }
                        return !empty($addressesArr) ? json_encode($addressesArr) : null;
                    })(),
                    'quick_facts' => $encodedJson['quick_facts'],
                    'videos' => $encodedJson['videos'],
                    'gallery' => $encodedJson['gallery'],
                    'reviews' => $encodedJson['reviews'],
                    'status' => $fields['status'],
                    'social' => json_encode($socialPayload),
                    'id' => $doctor_id,
                    'practice_city' => $fields['practice_city'],
                ];

                // Ensure timing is always a JSON string (never NULL) before executing
                if (!isset($params['timing']) || $params['timing'] === null) {
                    $params['timing'] = json_encode((object)[]);
                } else {
                    // cast to string to avoid PDO nullification
                    $params['timing'] = (string)$params['timing'];
                }

                // Log timing parameter for debugging (remove in production)
                error_log('DEBUG: updating doctor id ' . $doctor_id . ' timing param = ' . $params['timing']);

                if ($updatePassword) {
                    $params['password'] = password_hash($fields['new_password'], PASSWORD_BCRYPT);
                }
                
                $stmt->execute($params);

                // Update doctor-hospital associations
                $delHospStmt = $pdo->prepare('DELETE FROM doctor_hospital WHERE doctor_id = ?');
                $delHospStmt->execute([$doctor_id]);
                
                if (isset($_POST['hospitals']) && is_array($_POST['hospitals'])) {
                    $insHospStmt = $pdo->prepare('INSERT INTO doctor_hospital (doctor_id, hospital_id) VALUES (?, ?)');
                    foreach ($_POST['hospitals'] as $hospId) {
                        $insHospStmt->execute([$doctor_id, (int)$hospId]);
                    }
                }

                $success = true;

                // Refresh doctor data
                $stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
                $stmt->execute([$doctor_id]);
                $doctor = $stmt->fetch();
                // Debug: log what was actually stored in DB for timing
                error_log('DEBUG DB stored timing for id ' . $doctor_id . ' => ' . var_export($doctor['timing'] ?? null, true));
                $social = json_decode($doctor['social'] ?? '{}', true);

            } catch (PDOException $e) {
                // Temporary: expose DB error to help debugging (still logged).
                $error = 'Error updating profile: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}

$statusOptions = [
    'active' => ['label' => 'Active', 'color' => 'emerald', 'icon' => 'fa-circle-check'],
    'on-leave' => ['label' => 'On Leave', 'color' => 'amber', 'icon' => 'fa-clock'],
    'inactive' => ['label' => 'Inactive', 'color' => 'red', 'icon' => 'fa-circle-xmark']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor Profile - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc',
                            400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1',
                            800: '#075985', 900: '#0c4a6e',
                        }
                    },
                }
            }
        }
    </script>
    <!-- Leaflet for map picker -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', system-ui, sans-serif; }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 100px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }

        .mesh-gradient {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 40% 20%, rgba(14, 165, 233, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(124, 58, 237, 0.06) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(16, 185, 129, 0.06) 0px, transparent 50%);
        }

        .card-premium {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02), 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.45);
            transform: translateY(-1px);
        }

        .input-premium {
            transition: all 0.2s ease;
        }

        .input-premium:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }
        
        /* Professional Grade UI Enhancements */
        h1 { letter-spacing: -0.5px; }
        h3 { letter-spacing: -0.25px; }
        
        .card-premium {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 1.5px solid rgba(226, 232, 240, 0.6);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 8px 24px rgba(0, 0, 0, 0.02);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-premium:hover {
            border-color: rgba(226, 232, 240, 0.9);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.08), 0 12px 32px rgba(0, 0, 0, 0.03);
        }
        
        .input-premium {
            background: linear-gradient(135deg, #fafbfc 0%, #ffffff 100%);
            border: 2px solid #e2e8f0;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .input-premium::placeholder {
            color: #cbd5e1;
            font-weight: 400;
        }
        
        .input-premium:focus {
            border-color: #0ea5e9;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1), inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        
        /* Enhanced textarea styles */
        textarea.input-premium {
            font-size: 0.95rem;
            line-height: 1.5;
            resize: vertical;
            min-height: 120px;
        }
        
        textarea.input-premium:focus {
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1), inset 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        
        /* Enhanced select styles */
        select.input-premium {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23475569' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-color: transparent;
            padding-right: 32px;
        }
        
        select.input-premium:hover {
            border-color: #cbd5e1;
        }
        
        select.input-premium:focus {
            background-color: #ffffff;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35), 0 8px 24px rgba(14, 165, 233, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.45), 0 12px 32px rgba(14, 165, 233, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .editor-row {
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .editor-row:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.08);
        }
        
        .thumb-wrap {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            border: 1px solid #cbd5e1;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        
        .thumb-wrap img {
            transition: transform 0.3s ease;
        }
        
        .thumb-wrap:hover img {
            transform: scale(1.05);
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
        }
        
        .alert-box {
            border-radius: 1rem;
            backdrop-filter: blur(16px);
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Advanced input focus enhancements */
        input.input-premium:disabled,
        textarea.input-premium:disabled,
        select.input-premium:disabled {
            background-color: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }
        
        /* Form label enhancement */
        label {
            display: block;
        }
        
        label i {
            margin-right: 0.5rem;
        }
        
        /* Button group styling */
        .btn-group {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn-group button {
            flex: 1;
        }
    </style>
</head>
<body class="mesh-gradient min-h-screen antialiased">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-[280px] bg-gradient-to-b from-slate-900 to-slate-800 text-white fixed h-full hidden lg:flex flex-col z-50">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-stethoscope text-xl"></i>
                </div>
                <h1 class="text-xl font-bold">DrMap Panel</h1>
            </div>
        </div>

        <nav class="flex-1 px-3 space-y-1 overflow-y-auto py-6">
            <a href="index.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-grid-2 w-5 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="doctors.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-user-doctor w-5 mr-3"></i>
                <span>Manage Doctors</span>
            </a>
            <a href="enquiries.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-envelope w-5 mr-3"></i>
                <span>Enquiries</span>
            </a>
        </nav>

        <div class="p-4 border-t border-white/10">
            <a href="logout.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                <i class="fa-solid fa-sign-out w-5 mr-3"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-[280px]">
        <!-- Header -->
        <header class="glass sticky top-0 z-40 border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="doctors.php" class="w-10 h-10 rounded-xl bg-white shadow-sm border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900">Edit Doctor Profile</h1>
                            <p class="text-sm text-slate-600">Editing: <?php echo htmlspecialchars($doctor['name']); ?></p>
                        </div>
                    </div>
                    <a href="view.php?id=<?php echo $doctor_id; ?>" class="px-4 py-2.5 text-primary-600 border-2 border-primary-600 rounded-xl text-sm font-medium hover:bg-primary-50">
                        <i class="fa-solid fa-eye mr-2"></i>View
                    </a>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <!-- Success Message -->
            <?php if ($success): ?>
            <div class="mb-6 alert-box bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start space-x-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0 text-lg">
                    <i class="fa-solid fa-check text-emerald-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-emerald-900">Success!</h3>
                    <p class="text-sm text-emerald-700 mt-0.5">Your profile has been updated successfully.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 alert-box bg-red-50 border border-red-200 rounded-xl p-4 flex items-start space-x-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0 text-lg">
                    <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-red-900">Error</h3>
                    <p class="text-sm text-red-700 mt-0.5"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Personal Information Section -->
                <div class="card-premium rounded-2xl p-6 lg:p-8">
                    <div class="mb-6 pb-4 border-b-2 border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            Personal Information
                        </h3>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Full Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="Your full name">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Specialty *</label>
                                <input type="text" name="specialty" value="<?php echo htmlspecialchars($doctor['specialty']); ?>" required
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="e.g., Cardiologist">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Years of Experience</label>
                                <input type="number" name="experience" value="<?php echo (int)$doctor['experience']; ?>" min="0" max="100"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="e.g., 15">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="you@example.com">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Profile Photo</label>
                            <div class="flex gap-3">
                                <div class="flex-1">
                                    <!-- hidden field stores the photo path (set by JS or server after upload) -->
                                    <input type="hidden" name="photo" id="photoUrlInput" value="<?php echo htmlspecialchars($doctor['photo']); ?>">
                                    <input type="file" id="photoUploadInput" name="profile_photo" accept="image/*" class="hidden">
                                    <button type="button" id="photoUploadBtn" class="w-44 px-3 py-2 border-2 border-slate-200 rounded-lg btn-primary text-white text-sm">Upload Photo</button>
                                </div>
                                <div class="w-20 h-12 rounded-xl overflow-hidden border-2 border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0" id="photoPreviewWrap">
                                    <?php if ($doctor['photo']): ?>
                                    <img id="photoPreviewImg" src="<?php echo htmlspecialchars($doctor['photo']); ?>" alt="Profile" class="w-full h-full object-cover">
                                    <i id="photoPreviewIcon" class="fa-solid fa-image text-slate-400 hidden"></i>
                                    <?php else: ?>
                                    <i id="photoPreviewIcon" class="fa-solid fa-image text-slate-400"></i>
                                    <img id="photoPreviewImg" src="" alt="Profile" class="w-full h-full object-cover hidden">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Upload a profile photo (max 5MB). The image will be saved to your account.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">New Password</label>
                                <input type="password" name="new_password" value="" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="Leave blank to keep current password">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Confirm New Password</label>
                                <input type="password" name="confirm_password" value="" autocomplete="new-password"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information Section -->
                <div class="card-premium rounded-2xl p-6 lg:p-8">
                    <div class="mb-6 pb-4 border-b-2 border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            Professional Information
                        </h3>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Qualification</label>
                            <input type="text" name="qualification" value="<?php echo htmlspecialchars($doctor['qualification']); ?>"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="e.g., MBBS, MD (Cardiology), FACC">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Rating (0-5)</label>
                                <input type="number" name="rating" value="<?php echo $doctor['rating']; ?>" min="0" max="5" step="0.1"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="0-5">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium">
                                    <?php foreach ($statusOptions as $key => $status): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($doctor['status'] === $key ? 'selected' : ''); ?>>
                                        <?php echo $status['label']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-semibold text-slate-900">Professional Bio / About</label>
                                <button type="button" onclick="generateBio('about')" class="text-xs bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all">
                                    <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i> Write Bio with AI
                                </button>
                            </div>
                            <textarea name="about" id="about" rows="6" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium"
                                      placeholder="Tell patients about yourself, your experience, approach to medicine, specializations, etc..."><?php echo htmlspecialchars($doctor['about']); ?></textarea>
                            <p class="text-xs text-slate-500 mt-2">This will be displayed on your profile page</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Professional Quote / Motto</label>
                            <input type="text" name="speech" value="<?php echo htmlspecialchars($doctor['speech']); ?>"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" 
                                   placeholder="e.g., 'I believe in providing compassionate, patient-centered care.'">
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="card-premium rounded-2xl p-6 lg:p-8">
                    <div class="mb-6 pb-4 border-b-2 border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            Contact Information
                        </h3>
                    </div>
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">Phone</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="+1 (555) 123-4567">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-900 mb-2">WhatsApp Number</label>
                                <input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($doctor['whatsapp']); ?>"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="+1 (555) 123-4567">
                            </div>
                        </div>

                         <?php
                         $locsDec = json_decode($doctor['locations'] ?? '[]', true);
                         $loc1_addr = $locsDec[0]['address'] ?? '';
                         $loc1_lat = $locsDec[0]['lat'] ?? '';
                         $loc1_lng = $locsDec[0]['lng'] ?? '';

                         $loc2_addr = $locsDec[1]['address'] ?? '';
                         $loc2_lat = $locsDec[1]['lat'] ?? '';
                         $loc2_lng = $locsDec[1]['lng'] ?? '';
                         ?>
                         <!-- Structured Locations Panel (Req 32) -->
                         <div>
                             <label class="block text-sm font-semibold text-slate-900 mb-2">Practice Locations (Max 2)</label>
                             <div class="space-y-4 p-4 border border-slate-200 rounded-xl bg-slate-50">
                                 <div>
                                     <span class="text-xs font-bold uppercase text-slate-500 block mb-1">Location 1</span>
                                     <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                         <input type="text" name="location1_address" id="location1_address" value="<?php echo htmlspecialchars($loc1_addr); ?>" placeholder="Address 1 (e.g. Guwahati Metro Hospital)" class="md:col-span-2 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:border-teal-500">
                                         <div class="flex gap-1">
                                             <input type="text" name="location1_lat" id="location1_lat" value="<?php echo htmlspecialchars($loc1_lat); ?>" placeholder="Latitude" class="w-1/2 px-2 py-2 border border-slate-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                             <input type="text" name="location1_lng" id="location1_lng" value="<?php echo htmlspecialchars($loc1_lng); ?>" placeholder="Longitude" class="w-1/2 px-2 py-2 border border-slate-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                         </div>
                                     </div>
                                 </div>
                                 <div class="border-t border-slate-200 pt-3">
                                     <span class="text-xs font-bold uppercase text-slate-500 block mb-1">Location 2 (Optional)</span>
                                     <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                         <input type="text" name="location2_address" id="location2_address" value="<?php echo htmlspecialchars($loc2_addr); ?>" placeholder="Address 2 (e.g. City Dental Clinic)" class="md:col-span-2 px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:border-teal-500">
                                         <div class="flex gap-1">
                                             <input type="text" name="location2_lat" id="location2_lat" value="<?php echo htmlspecialchars($loc2_lat); ?>" placeholder="Latitude" class="w-1/2 px-2 py-2 border border-slate-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                             <input type="text" name="location2_lng" id="location2_lng" value="<?php echo htmlspecialchars($loc2_lng); ?>" placeholder="Longitude" class="w-1/2 px-2 py-2 border border-slate-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                         <div>
                             <label class="block text-sm font-semibold text-slate-900 mb-2">Practice City</label>
                             <?php
                             $citiesStmt = $pdo->query("SELECT name FROM cities ORDER BY name ASC");
                             $allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
                             if (empty($allCities)) {
                                 $allCities = ['Guwahati', 'Tezpur', 'Kolkata', 'Delhi', 'Dibrugarh'];
                             }
                             $currCity = $doctor['practice_city'] ?? 'Guwahati';
                             ?>
                             <select name="practice_city" id="practice_city" 
                                     class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium text-slate-800 font-medium">
                                 <?php foreach ($allCities as $cityName): ?>
                                     <option value="<?php echo htmlspecialchars($cityName); ?>" <?php echo $cityName === $currCity ? 'selected' : ''; ?>>
                                         <?php echo htmlspecialchars($cityName); ?>
                                     </option>
                                 <?php endforeach; ?>
                             </select>
                         </div>

                        <!-- Hospital / Clinic Associations -->
                        <div>
                             <label class="block text-sm font-semibold text-slate-900 mb-2">Hospital / Clinic Associations</label>
                             <div class="space-y-2 border border-slate-200 rounded-xl p-4 bg-slate-50 max-h-48 overflow-y-auto">
                                 <?php
                                 $currHospStmt = $pdo->prepare('SELECT hospital_id FROM doctor_hospital WHERE doctor_id = ?');
                                 $currHospStmt->execute([$doctor_id]);
                                 $currHospitals = $currHospStmt->fetchAll(PDO::FETCH_COLUMN);

                                 $hospStmt = $pdo->query("SELECT id, name, city FROM hospitals ORDER BY name ASC");
                                 $allHospitals = $hospStmt->fetchAll(PDO::FETCH_ASSOC);
                                 
                                 if (count($allHospitals) === 0):
                                 ?>
                                     <p class="text-xs text-slate-400">No partner hospitals registered yet.</p>
                                 <?php else: ?>
                                     <?php foreach ($allHospitals as $hosp): ?>
                                         <label class="flex items-center space-x-3 cursor-pointer p-1 hover:bg-white rounded-lg transition">
                                             <input type="checkbox" name="hospitals[]" value="<?php echo $hosp['id']; ?>" 
                                                    class="w-4 h-4 text-teal-600 border-slate-300 rounded focus:ring-teal-500"
                                                    <?php echo in_array($hosp['id'], $currHospitals) ? 'checked' : ''; ?>>
                                             <span class="text-xs font-semibold text-dark-800"><?php echo htmlspecialchars($hosp['name']); ?> <span class="text-[10px] text-dark-400">(<?php echo htmlspecialchars($hosp['city']); ?>)</span></span>
                                         </label>
                                     <?php endforeach; ?>
                                 <?php endif; ?>
                             </div>
                             <p class="text-xs text-slate-500 mt-2">Check the hospitals/clinics where this doctor is practicing</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Availability / Timing</label>
                            <div id="timingEditor" class="p-4 bg-white rounded-xl border border-slate-200">
                                <p class="text-sm text-slate-600 mb-4">Select days and add multiple time ranges for each day.</p>
                                <div class="space-y-4" id="timingWeek">
                                    <!-- Week calendar cards generated by JS -->
                                </div>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Each day can have multiple time slots. Days are disabled by default.</p>
                        </div>
                    </div>
                </div>

                <!-- Social Links Section -->
                <div class="card-premium rounded-2xl p-6 lg:p-8">
                    <div class="mb-6 pb-4 border-b-2 border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fa-solid fa-share-nodes"></i>
                            </div>
                            Social Media Links
                        </h3>
                    </div>
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">
                                <i class="fa-brands fa-linkedin text-blue-600 mr-2"></i>LinkedIn URL
                            </label>
                            <input type="url" name="linkedin" value="<?php echo htmlspecialchars($social['linkedin'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="https://linkedin.com/in/...">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">
                                <i class="fa-brands fa-twitter text-sky-500 mr-2"></i>Twitter / X URL
                            </label>
                            <input type="url" name="twitter" value="<?php echo htmlspecialchars($social['twitter'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="https://twitter.com/...">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">
                                <i class="fa-brands fa-facebook text-blue-700 mr-2"></i>Facebook URL
                            </label>
                            <input type="url" name="facebook" value="<?php echo htmlspecialchars($social['facebook'] ?? ''); ?>"
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl input-premium" placeholder="https://facebook.com/...">
                        </div>
                    </div>
                </div>

                <!-- Advanced Data (user-friendly editors) -->
                <div class="card-premium rounded-2xl p-6 lg:p-8">
                    <div class="mb-6 pb-4 border-b-2 border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            Media & Data
                        </h3>
                    </div>

                    <!-- Hidden JSON holders (submitted to server) -->
                    <textarea name="quick_facts" id="hf_quick_facts" class="hidden"><?php echo htmlspecialchars($doctor['quick_facts'] ?? ''); ?></textarea>
                    <textarea name="locations" id="hf_locations" class="hidden"><?php echo htmlspecialchars($doctor['locations'] ?? ''); ?></textarea>
                    <textarea name="videos" id="hf_videos" class="hidden"><?php echo htmlspecialchars($doctor['videos'] ?? ''); ?></textarea>
                    <textarea name="gallery" id="hf_gallery" class="hidden"><?php echo htmlspecialchars($doctor['gallery'] ?? ''); ?></textarea>
                    <textarea name="reviews" id="hf_reviews" class="hidden"><?php echo htmlspecialchars($doctor['reviews'] ?? ''); ?></textarea>
                    <textarea name="timing" id="hf_timing" class="hidden"><?php echo htmlspecialchars($doctor['timing'] ?? ''); ?></textarea>

                    <div class="space-y-6">
                        <!-- Quick Facts editor -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-slate-900">Quick Facts</label>
                                <button type="button" id="addQuickFact" class="px-3 py-1 text-xs rounded bg-amber-100 text-amber-800">Add Fact</button>
                            </div>
                            <div id="quickFactsEditor" class="space-y-2"></div>
                            <p class="text-xs text-slate-500 mt-1">Add short label/value pairs (e.g., Languages, Consultation Fee).</p>
                        </div>

                        <!-- Videos editor -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-slate-900">Videos</label>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="addVideo" class="px-3 py-1 text-xs rounded bg-amber-100 text-amber-800">Add URL</button>
                                </div>
                            </div>
                            <div id="videosEditor" class="space-y-2"></div>
                            <p class="text-xs text-slate-500 mt-1">Add video URLs (YouTube, Vimeo) or relative paths to hosted videos.</p>
                        </div>

                        <!-- Gallery editor -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-bold text-slate-900">Gallery Images</label>
                                <div class="flex items-center gap-2">
                                    <input type="file" id="galleryUploadInput" accept="image/*" class="hidden">
                                    <button type="button" id="galleryUploadBtn" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 text-white hover:shadow-lg transition-all transform hover:-translate-y-0.5">Upload Image</button>
                                    <button type="button" id="addImage" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white hover:shadow-lg transition-all transform hover:-translate-y-0.5">Add URL</button>
                                </div>
                            </div>
                            <div id="galleryEditor" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"></div>
                            <p class="text-xs text-slate-500 mt-2">Upload or add image URLs for your gallery. Images will appear on your profile.</p>
                        </div>

                        <!-- Reviews editor -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-bold text-slate-900">Patient Reviews</label>
                                <button type="button" id="addReview" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gradient-to-br from-rose-500 to-rose-600 text-white hover:shadow-lg transition-all transform hover:-translate-y-0.5">Add Review</button>
                            </div>
                            <div id="reviewsEditor" class="space-y-2"></div>
                            <p class="text-xs text-slate-500 mt-2">Add patient reviews to build credibility. These will display on your profile.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end sticky bottom-0 bg-white/95 backdrop-blur-lg -mx-6 lg:-mx-8 px-6 lg:px-8 py-4 border-t border-slate-200">
                    <a href="doctors.php" class="px-6 py-2.5 border-2 border-slate-300 rounded-xl font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 btn-primary rounded-xl font-semibold text-white flex items-center space-x-2 justify-center">
                        <i class="fa-solid fa-save"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
<script>
document.querySelector('form').addEventListener('submit', function() {
    // Quick Facts
    const qf = Array.from(document.querySelectorAll('#quickFactsEditor .editor-row')).map(r=>{
        const inputs = r.querySelectorAll('input');
        return { label: inputs[0].value.trim(), value: inputs[1].value.trim() };
    }).filter(x=>x.label || x.value);
    document.getElementById('hf_quick_facts').value = JSON.stringify(qf);

    // Timing
    const timingObj = {};
    document.querySelectorAll('.day-card').forEach(card=>{
        const day = card.dataset.day;
        const toggle = card.querySelector('.day-toggle');
        const enabled = toggle.checked;
        const slots = [];
        if (enabled) {
            card.querySelectorAll('.range-row').forEach(row=>{
                const open = row.querySelector('.range-open').value.trim();
                const close = row.querySelector('.range-close').value.trim();
                if (open) { slots.push({ open: open, close: close || '' }); }
            });
        }
        timingObj[day] = { enabled: enabled, slots: slots };
    });
    document.getElementById('hf_timing').value = JSON.stringify(timingObj);

    const vids = Array.from(document.querySelectorAll('#videosEditor .editor-row input')).map(i=>i.value.trim()).filter(Boolean);
    document.getElementById('hf_videos').value = JSON.stringify(vids);

    const imgs = Array.from(document.querySelectorAll('#galleryEditor .editor-row input')).map(i=>i.value.trim()).filter(Boolean);
    document.getElementById('hf_gallery').value = JSON.stringify(imgs);

    const revs = Array.from(document.querySelectorAll('#reviewsEditor .editor-row')).map(r=>{
        const inputs = r.querySelectorAll('input');
        const txt = r.querySelector('textarea').value.trim();
        return { name: inputs[0].value.trim(), rating: parseInt(inputs[1].value)||0, date: inputs[2].value, comment: txt };
    }).filter(x=>x.name || x.comment);
    document.getElementById('hf_reviews').value = JSON.stringify(revs);
});

// Restore Dynamic Editors markup for Videos, Gallery, Reviews (Req 32)
document.addEventListener('DOMContentLoaded', ()=>{
    renderQuickFacts(); renderLocations(); renderTiming(); renderVideos(); renderGallery(); renderReviews();
    try {
        const initVal = document.getElementById('photoUrlInput')?.value || '';
        if (initVal) updatePhotoPreviewFromUrl(initVal);
    } catch (e) { console.error(e); }
});

function generateBio(textareaId) {
    const name = document.querySelector('input[name="name"]').value.trim();
    const specialty = document.querySelector('input[name="specialty"]').value.trim();
    const qualification = document.querySelector('input[name="qualification"]').value.trim();
    const experience = document.querySelector('input[name="experience"]').value;
    
    if (!name || !specialty) {
        alert('Please fill out the Doctor Name and Specialty first.');
        return;
    }
    
    const btn = event.target.closest('button');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Writing...';
    
    fetch(`ai_write_bio.php?name=${encodeURIComponent(name)}&specialty=${encodeURIComponent(specialty)}&qualification=${encodeURIComponent(qualification)}&experience=${encodeURIComponent(experience)}`)
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = origText;
            if (data.success) {
                document.getElementById(textareaId).value = data.bio;
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = origText;
            alert('Error generating bio text.');
        });
}

// Helpers for dynamic editors
function createEl(html) {
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    return div.firstChild;
}

function bindRemove(btn) {
    btn.addEventListener('click', e => {
        const wrap = btn.closest('.editor-row');
        if (wrap) wrap.remove();
    });
}

function parseJSONSafe(str) {
    if (!str && str !== '') return null;
    if (typeof str !== 'string') return str;
    str = str.trim();
    if (str === '') return null;

    function decodeHTMLEntities(s) {
        return s.replace(/&quot;/g, '"')
                .replace(/&amp;/g, '&')
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>');
    }

    try {
        let v = JSON.parse(str);
        if (typeof v === 'string') {
            try {
                const decoded = decodeHTMLEntities(v);
                return JSON.parse(decoded);
            } catch (e2) {
                try { return JSON.parse(v); } catch (e3) { return v; }
            }
        }
        return v;
    } catch (e) {
        try {
            const dec = decodeHTMLEntities(str);
            const v2 = JSON.parse(dec);
            if (typeof v2 === 'string') {
                try { return JSON.parse(v2); } catch (e4) { return v2; }
            }
            return v2;
        } catch (e2) {
            return null;
        }
    }
}

function renderQuickFacts() {
    const container = document.getElementById('quickFactsEditor');
    if (!container) return;
    container.innerHTML = '';
    const raw = document.getElementById('hf_quick_facts').value || '';
    const data = parseJSONSafe(raw) || [];
    data.forEach(f => addQuickFactRow(f.label || '', f.value || ''));
}

function addQuickFactRow(label='', value=''){
    const row = createEl(`
        <div class="editor-row flex gap-2 items-center">
            <input type="text" class="flex-1 px-3 py-2 border rounded" placeholder="Label" value="${label.replace(/"/g,'&quot;')}">
            <input type="text" class="flex-1 px-3 py-2 border rounded" placeholder="Value" value="${value.replace(/"/g,'&quot;')}">
            <button type="button" class="px-2 py-1 text-sm text-red-600 border rounded remove-btn">Remove</button>
        </div>
    `);
    const container = document.getElementById('quickFactsEditor');
    if (container) {
        container.appendChild(row);
        bindRemove(row.querySelector('.remove-btn'));
    }
}

document.getElementById('addQuickFact')?.addEventListener('click', ()=> addQuickFactRow());

// Locations (Replaced by static elements, no-op)
function renderLocations(){}

function renderVideos(){
    const container = document.getElementById('videosEditor');
    if (!container) return;
    container.innerHTML = '';
    const raw = document.getElementById('hf_videos').value || '';
    const data = parseJSONSafe(raw) || [];
    data.forEach(u => addVideoRow(u));
}
function addVideoRow(url=''){
    const row = createEl(`
        <div class="editor-row flex gap-2 items-center">
            <input type="text" class="flex-1 px-3 py-2 border rounded" placeholder="Video URL or path" value="${url.replace(/\"/g,'&quot;')}">
            <button type="button" class="px-2 py-1 text-sm text-red-600 border rounded remove-btn">Remove</button>
        </div>
    `);
    const container = document.getElementById('videosEditor');
    if (container) {
        container.appendChild(row);
        bindRemove(row.querySelector('.remove-btn'));
    }
}
document.getElementById('addVideo')?.addEventListener('click', ()=> addVideoRow());

function renderGallery(){
    const container = document.getElementById('galleryEditor');
    if (!container) return;
    container.innerHTML = '';
    const raw = document.getElementById('hf_gallery').value || '';
    const data = parseJSONSafe(raw) || [];
    data.forEach(u => addImageRow(u));
}
function resolveGallerySrc(p){
    if (!p) return '';
    const s = String(p).trim();
    if (s === '') return '';
    if (s.startsWith('http://') || s.startsWith('https://') || s.startsWith('//')) return s;
    const parts = location.pathname.split('/').filter(Boolean);
    const appRoot = parts.length ? '/' + parts[0] : '';
    if (s.startsWith('/')) return appRoot + s;
    return appRoot + '/' + s;
}

function addImageRow(url=''){
    const displaySrc = url ? resolveGallerySrc(url) : '';
    const thumbHtml = displaySrc ? `<img src="${displaySrc.replace(/"/g,'&quot;')}" class="w-full h-full object-cover" onerror="this.src='https://via.placeholder.com/160x120?text=Image'">` : '<i class="fa-solid fa-image text-slate-300"></i>';
    const row = createEl(`
        <div class="editor-row group bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl p-3 border border-slate-200 hover:border-slate-300 hover:shadow-md transition-all">
            <div class="flex gap-3 items-center">
                <div class="w-24 h-20 rounded-lg overflow-hidden border-2 border-slate-200 bg-slate-50 flex items-center justify-center flex-shrink-0 thumb-wrap hover:border-slate-400 transition-colors">
                    ${thumbHtml}
                </div>
                <input type="text" class="flex-1 px-4 py-2.5 border border-slate-200 rounded-lg font-mono text-xs bg-white" placeholder="Image URL or path" value="${url.replace(/"/g,'&quot;')}">
                <button type="button" class="px-3 py-2.5 text-sm font-semibold text-red-600 border-2 border-red-200 rounded-lg hover:bg-red-50 transition-colors remove-btn"><i class="fa-solid fa-trash-alt"></i></button>
            </div>
        </div>
    `);
    const container = document.getElementById('galleryEditor');
    if (container) {
        container.appendChild(row);
        bindRemove(row.querySelector('.remove-btn'));
    }
}
document.getElementById('addImage')?.addEventListener('click', ()=> addImageRow());

const galleryUploadInput = document.getElementById('galleryUploadInput');
const galleryUploadBtn = document.getElementById('galleryUploadBtn');
galleryUploadBtn?.addEventListener('click', ()=> galleryUploadInput.click());
galleryUploadInput?.addEventListener('change', async function(){
    const f = this.files[0];
    if (!f) return;
    const form = new FormData();
    form.append('image', f);
    form.append('doctor_id', '<?php echo $doctor_id; ?>');
    galleryUploadBtn.disabled = true;
    galleryUploadBtn.textContent = 'Uploading...';
    try {
        const res = await fetch('upload_image.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data && data.success && data.url) {
            addImageRow(data.url);
            const imgs = Array.from(document.querySelectorAll('#galleryEditor .editor-row input')).map(i=>i.value.trim()).filter(Boolean);
            document.getElementById('hf_gallery').value = JSON.stringify(imgs);
        } else {
            alert('Upload failed: ' + (data.error || 'unknown'));
        }
    } catch (e) {
        alert('Upload error');
    }
    galleryUploadBtn.disabled = false;
    galleryUploadBtn.textContent = 'Upload Image';
    this.value = '';
});

// Profile photo upload preview
const photoUploadInput = document.getElementById('photoUploadInput');
const photoUploadBtn = document.getElementById('photoUploadBtn');
photoUploadBtn?.addEventListener('click', ()=> photoUploadInput.click());
photoUploadInput?.addEventListener('change', async function(){
    const f = this.files[0];
    if (!f) return;
    const form = new FormData();
    form.append('profile_photo', f);
    form.append('doctor_id', '<?php echo $doctor_id; ?>');
    photoUploadBtn.disabled = true;
    photoUploadBtn.textContent = 'Uploading...';
    try {
        const res = await fetch('upload_profile_photo.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data && data.success && data.url) {
            document.getElementById('photoUrlInput').value = data.url;
            updatePhotoPreviewFromUrl(data.url);
        } else {
            alert('Upload failed: ' + (data.error || 'unknown'));
        }
    } catch (e) {
        alert('Upload error');
    }
    photoUploadBtn.disabled = false;
    photoUploadBtn.textContent = 'Upload Photo';
    this.value = '';
});

function updatePhotoPreviewFromUrl(url){
    const img = document.getElementById('photoPreviewImg');
    const icon = document.getElementById('photoPreviewIcon');
    if (!url) {
        if (img) img.classList.add('hidden');
        if (icon) icon.classList.remove('hidden');
        return;
    }
    const src = resolveGallerySrc(url);
    if (img) { img.src = src; img.classList.remove('hidden'); }
    if (icon) icon.classList.add('hidden');
}

const photoUrlInputElm = document.getElementById('photoUrlInput');
photoUrlInputElm?.addEventListener('input', (e)=> updatePhotoPreviewFromUrl(e.target.value));

function renderTiming(){
    const container = document.getElementById('timingWeek');
    if (!container) return;
    container.innerHTML = '';
    const raw = document.getElementById('hf_timing').value || '';
    const data = parseJSONSafe(raw) || {};
    const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    const labels = {monday:'Monday', tuesday:'Tuesday', wednesday:'Wednesday', thursday:'Thursday', friday:'Friday', saturday:'Saturday', sunday:'Sunday'};

    days.forEach(d=>{
        const info = data[d] || { enabled: false, slots: [] };
        const enabled = !!info.enabled;
        const slots = info.slots || [];

        const card = createEl(`
            <div class="day-card border border-slate-200 rounded-lg p-4 mb-2" data-day="${d}">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-semibold text-slate-900">${labels[d]}</label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="day-toggle" ${enabled? 'checked':''} />
                            <span class="text-xs text-slate-600">Enabled</span>
                        </label>
                    </div>
                </div>
                <div class="space-y-2 ranges-container" ${!enabled? 'style="display:none"':''}></div>
                <button type="button" class="add-range-btn mt-2 text-sm text-blue-600 hover:underline ${!enabled? 'hidden':''}" style="${!enabled? 'display:none':''}">+ Add Time Range</button>
            </div>
        `);
        container.appendChild(card);

        const rangesContainer = card.querySelector('.ranges-container');
        const addBtn = card.querySelector('.add-range-btn');
        const toggle = card.querySelector('.day-toggle');

        slots.forEach(slot=>{ addRangeRow(rangesContainer, slot.open || '', slot.close || ''); });

        toggle.addEventListener('change', ()=>{
            rangesContainer.style.display = toggle.checked ? '' : 'none';
            addBtn.style.display = toggle.checked ? '' : 'none';
            if (toggle.checked && slots.length === 0) { addRangeRow(rangesContainer, '', ''); }
        });

        addBtn.addEventListener('click', (e)=>{
            e.preventDefault();
            addRangeRow(rangesContainer, '', '');
        });
    });
}

function addRangeRow(container, openTime='', closeTime=''){
    const row = createEl(`
        <div class="range-row flex gap-2 items-center bg-slate-50 p-2 rounded">
            <span class="text-xs text-slate-600">From</span>
            <input type="time" class="range-open px-2 py-1 border border-slate-300 rounded text-sm" value="${openTime}">
            <span class="text-xs text-slate-600">To</span>
            <input type="time" class="range-close px-2 py-1 border border-slate-300 rounded text-sm" value="${closeTime}">
            <button type="button" class="remove-range ml-auto px-2 py-1 text-xs text-red-600 border border-red-200 rounded hover:bg-red-50">Remove</button>
        </div>
    `);
    container.appendChild(row);
    row.querySelector('.remove-range').addEventListener('click', (e)=>{
        e.preventDefault();
        row.remove();
    });
}

function renderReviews(){
    const container = document.getElementById('reviewsEditor');
    if (!container) return;
    container.innerHTML = '';
    const raw = document.getElementById('hf_reviews').value || '';
    const data = parseJSONSafe(raw) || [];
    data.forEach(r => addReviewRow(r.name||'', r.rating||5, r.comment||'', r.date||'') );
}
function addReviewRow(name='', rating=5, comment='', date=''){
    const row = createEl(`
        <div class="editor-row border p-3 rounded">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                <input type="text" class="px-3 py-2 border rounded" placeholder="Reviewer name" value="${name.replace(/"/g,'&quot;')}">
                <input type="number" class="px-3 py-2 border rounded" min="0" max="5" step="1" value="${rating}">
                <input type="date" class="px-3 py-2 border rounded" value="${date}">
            </div>
            <div class="flex gap-2 items-start">
                <textarea class="flex-1 px-3 py-2 border rounded" rows="2" placeholder="Comment">${comment.replace(/</g,'&lt;')}</textarea>
                <button type="button" class="px-2 py-1 text-sm text-red-600 border rounded remove-btn">Remove</button>
            </div>
        </div>
    `);
    const container = document.getElementById('reviewsEditor');
    if (container) {
        container.appendChild(row);
        bindRemove(row.querySelector('.remove-btn'));
    }
}
document.getElementById('addReview')?.addEventListener('click', ()=> addReviewRow());

</script>

</body>
</html>

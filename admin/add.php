<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';
$pdo = getPDO();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $fields = [
        'name' => trim($_POST['name'] ?? ''),
        'specialty' => trim($_POST['specialty'] ?? ''),
        'experience' => max(0, (int)($_POST['experience'] ?? 0)),
        'qualification' => trim($_POST['qualification'] ?? ''),
        'practice_city' => trim($_POST['practice_city'] ?? 'Guwahati'),
        'rating' => min(5, max(0, (float)($_POST['rating'] ?? 0))),
        'photo' => filter_var($_POST['photo'] ?? '', FILTER_SANITIZE_URL),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'password' => trim($_POST['password'] ?? ''),
        'whatsapp' => trim($_POST['whatsapp'] ?? ''),
        'timing' => trim($_POST['timing'] ?? ''),
        'about' => trim($_POST['about'] ?? ''),
        'speech' => trim($_POST['speech'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['active', 'on-leave', 'inactive']) ? $_POST['status'] : 'active',
        'linkedin' => filter_var($_POST['linkedin'] ?? '', FILTER_SANITIZE_URL),
        'twitter' => filter_var($_POST['twitter'] ?? '', FILTER_SANITIZE_URL),
        'facebook' => filter_var($_POST['facebook'] ?? '', FILTER_SANITIZE_URL),
        'addresses' => trim($_POST['addresses'] ?? ''),
    ];

    // Validation
    if (empty($fields['name'])) {
        $error = 'Doctor name is required';
    } elseif (empty($fields['specialty'])) {
        $error = 'Specialty is required';
    } elseif (!empty($fields['email']) && !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (!empty($fields['email']) && empty($fields['password'])) {
        $error = 'Password is required when email is provided';
    } elseif (!empty($fields['password']) && strlen($fields['password']) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            // Combine social links into a single JSON column 'social'
            $socialPayload = [
                'linkedin' => $fields['linkedin'] ?? '',
                'twitter' => $fields['twitter'] ?? '',
                'facebook' => $fields['facebook'] ?? '',
            ];
            $fields['social'] = json_encode($socialPayload);

            // Hash password if provided
            $hashedPassword = !empty($fields['password']) ? password_hash($fields['password'], PASSWORD_BCRYPT) : null;

            // Process structured multiple locations (Req 32)
            $locationsArr = [];
            $addressesArr = [];
            
            $loc1_addr = trim($_POST['location1_address'] ?? '');
            $loc1_lat = trim($_POST['location1_lat'] ?? '');
            $loc1_lng = trim($_POST['location1_lng'] ?? '');
            
            if ($loc1_addr !== '') {
                $locationsArr[] = [
                    'address' => $loc1_addr,
                    'lat' => $loc1_lat,
                    'lng' => $loc1_lng
                ];
                $addressesArr[] = $loc1_addr;
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
                $addressesArr[] = $loc2_addr;
            }
            
            $locationsJson = !empty($locationsArr) ? json_encode($locationsArr) : null;
            $addressesJson = !empty($addressesArr) ? json_encode($addressesArr) : null;

            $sql = "INSERT INTO doctors (
                    name, 
                    specialty, 
                    experience, 
                    qualification, 
                    practice_city,
                    rating, 
                    photo, 
                    phone, 
                    email, 
                    password,
                    whatsapp, 
                    timing, 
                    about,
                    speech,
                    status, 
                    social,
                    addresses,
                    locations
                    ) VALUES (
                    :name, 
                    :specialty, 
                    :experience, 
                    :qualification, 
                    :practice_city,
                    :rating, 
                    :photo, 
                    :phone, 
                    :email, 
                    :password,
                    :whatsapp, 
                    :timing, 
                    :about,
                    :speech,
                    :status, 
                    :social,
                    :addresses,
                    :locations
                    )";

            $stmt = $pdo->prepare($sql);

            // Build explicit params matching the SQL placeholders
            $params = [
                'name' => $fields['name'],
                'specialty' => $fields['specialty'],
                'experience' => $fields['experience'],
                'qualification' => $fields['qualification'],
                'practice_city' => $fields['practice_city'],
                'rating' => $fields['rating'],
                'photo' => $fields['photo'],
                'phone' => $fields['phone'],
                'email' => $fields['email'],
                'password' => $hashedPassword,
                'whatsapp' => $fields['whatsapp'],
                'timing' => $fields['timing'],
                'about' => $fields['about'],
                'speech' => $fields['speech'],
                'status' => $fields['status'],
                'social' => $fields['social'],
                'addresses' => $addressesJson,
                'locations' => $locationsJson,
            ];

            $stmt->execute($params);
            $newId = $pdo->lastInsertId();

            // Save hospital associations
            if (isset($_POST['hospitals']) && is_array($_POST['hospitals'])) {
                $insHospStmt = $pdo->prepare('INSERT INTO doctor_hospital (doctor_id, hospital_id) VALUES (?, ?)');
                foreach ($_POST['hospitals'] as $hospId) {
                    $insHospStmt->execute([$newId, (int)$hospId]);
                }
            }

            // Redirect to view the new doctor
            header('Location: view.php?id=' . $newId . '&created=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error. Please try again. (' . htmlspecialchars($e->getMessage()) . ')';
            error_log($e->getMessage());
        }
    }
}

// Page title
$pageTitle = 'Add New Doctor';

// Specialty options
$specialties = [
    'Cardiologist',
    'Orthopedic Surgeon',
    'Pediatrician',
    'Dermatologist',
    'Neurologist',
    'Gynecologist',
    'Ophthalmologist',
    'ENT Specialist',
    'Psychiatrist',
    'General Physician',
    'Dentist',
    'Oncologist',
    'Urologist',
    'Nephrologist',
    'Pulmonologist'
];

// Status config
$statusOptions = [
    'active' => ['label' => 'Active', 'color' => 'emerald', 'icon' => 'fa-circle-check'],
    'on-leave' => ['label' => 'On Leave', 'color' => 'amber', 'icon' => 'fa-clock'],
    'inactive' => ['label' => 'Inactive', 'color' => 'red', 'icon' => 'fa-circle-xmark']
];

// Default values for new doctor
$defaultPhoto = 'https://ui-avatars.com/api/?name=New+Doctor&background=0ea5e9&color=fff&size=200';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - DrMap</title>
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
                        },
                        dark: {
                            50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1',
                            400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155',
                            800: '#1e293b', 900: '#0f172a', 950: '#020617',
                        }
                    },
                }
            }
        }
    </script>
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

        .sidebar-gradient {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
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

        .input-premium {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-premium:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }

        .input-premium:hover:not(:focus) {
            border-color: #94a3b8;
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

        .btn-primary:active {
            transform: translateY(0);
        }

        .floating-label {
            position: absolute;
            left: 44px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }

        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label {
            top: 0;
            left: 12px;
            transform: translateY(-50%) scale(0.85);
            background: white;
            padding: 0 6px;
            color: #0ea5e9;
            font-weight: 600;
        }

        .input-group:focus-within .input-icon {
            color: #0ea5e9;
        }

        .status-card {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .status-card:hover {
            transform: translateY(-2px);
        }

        .status-card.selected {
            border-width: 2px;
        }

        .photo-preview {
            transition: all 0.3s ease;
        }

        .photo-preview:hover {
            transform: scale(1.05);
        }

        .section-card {
            transition: all 0.3s ease;
        }

        .section-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .shake {
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
        }

        .slide-in {
            animation: slideIn 0.4s ease forwards;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .textarea-premium {
            min-height: 120px;
            resize: vertical;
        }

        .char-counter {
            transition: color 0.2s ease;
        }

        .char-counter.warning {
            color: #f59e0b;
        }

        .char-counter.danger {
            color: #ef4444;
        }
    </style>
</head>
<body class="mesh-gradient min-h-screen antialiased">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-[280px]">
        <!-- Header -->
        <header class="glass sticky top-0 z-40 border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="doctors.php" 
                           class="w-10 h-10 rounded-xl bg-white shadow-sm border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 hover:border-dark-300 transition-all">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <div class="flex items-center space-x-2 text-sm text-dark-500 mb-1">
                                <a href="doctors.php" class="hover:text-primary-600 transition-colors">Doctors</a>
                                <i class="fa-solid fa-chevron-right text-xs text-dark-300"></i>
                                <span class="text-dark-700 font-medium">Add New</span>
                            </div>
                            <h1 class="text-xl font-bold text-dark-900">Add New Doctor</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="doctors.php" 
                           class="px-4 py-2.5 bg-white border border-dark-200 rounded-xl text-sm font-medium text-dark-700 hover:bg-dark-50 transition-colors flex items-center space-x-2">
                            <i class="fa-solid fa-list"></i>
                            <span>Back to List</span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start space-x-3 slide-in shake" id="errorAlert">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-800">Error</h3>
                    <p class="text-sm text-red-600 mt-0.5"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <button onclick="document.getElementById('errorAlert').remove()" class="text-red-400 hover:text-red-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php endif; ?>

            <form method="POST" id="addForm" class="space-y-6">

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <!-- Left Column - Main Info -->
                    <div class="xl:col-span-2 space-y-6">
                        
                        <!-- Profile Preview Card -->
                        <div class="card-premium rounded-2xl p-6 section-card slide-in">
                            <div class="flex items-start space-x-6">
                                <div class="relative group">
                                    <img id="photoPreview" 
                                         src="<?php echo $defaultPhoto; ?>" 
                                         alt="Doctor Preview"
                                         class="w-28 h-28 rounded-2xl object-cover ring-4 ring-white shadow-lg photo-preview"
                                         onerror="this.src='https://ui-avatars.com/api/?name=New+Doctor&background=0ea5e9&color=fff&size=200'">
                                    <div class="absolute inset-0 bg-black/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <i class="fa-solid fa-camera text-white text-xl"></i>
                                    </div>
                                    <div class="absolute -bottom-2 -right-2 w-8 h-8 rounded-full bg-emerald-500 border-4 border-white flex items-center justify-center">
                                        <i class="fa-solid fa-circle-check text-white text-xs"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-dark-900">New Doctor Profile</h2>
                                    <p class="text-dark-500 font-medium mt-1">Fill in the details below</p>
                                    <div class="flex items-center space-x-4 mt-3">
                                        <div class="flex items-center space-x-1">
                                            <i class="fa-solid fa-star text-amber-400"></i>
                                            <span class="font-semibold text-dark-700">0.0</span>
                                        </div>
                                        <div class="w-px h-4 bg-dark-200"></div>
                                        <div class="flex items-center space-x-1 text-dark-500">
                                            <i class="fa-solid fa-briefcase-medical"></i>
                                            <span>0 years exp.</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-dark-400 mt-3">
                                        <i class="fa-solid fa-clock mr-1"></i>
                                        Profile will be created on save
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.1s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-primary-50 to-blue-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                                        <i class="fa-solid fa-user-doctor text-primary-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Basic Information</h3>
                                        <p class="text-xs text-dark-500">Personal and professional details</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Full Name -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-user input-icon transition-colors"></i>
                                        </div>
                                        <input type="text" name="name" id="name" required placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Full Name *</label>
                                    </div>

                                    <!-- Specialty -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-stethoscope input-icon transition-colors"></i>
                                        </div>
                                        <select name="specialty" id="specialty" required
                                                class="w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium appearance-none cursor-pointer">
                                            <option value="">Select Specialty</option>
                                            <?php foreach ($specialties as $spec): ?>
                                            <option value="<?php echo htmlspecialchars($spec); ?>">
                                                <?php echo htmlspecialchars($spec); ?>
                                            </option>
                                            <?php endforeach; ?>
                                            <option value="other">Other</option>
                                        </select>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-400 pointer-events-none">
                                            <i class="fa-solid fa-chevron-down text-sm"></i>
                                        </div>
                                    </div>

                                    <!-- Experience -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-briefcase input-icon transition-colors"></i>
                                        </div>
                                        <input type="number" name="experience" id="experience" min="0" max="70" placeholder=" "
                                               value="0"
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Experience (Years)</label>
                                    </div>

                                    <!-- Rating -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-star input-icon transition-colors"></i>
                                        </div>
                                        <input type="number" name="rating" id="rating" min="0" max="5" step="0.1" placeholder=" "
                                               value="0"
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Rating (0-5)</label>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center space-x-0.5" id="ratingStars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-solid fa-star text-sm text-dark-200"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <!-- Qualification -->
                                    <div class="md:col-span-2 input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-graduation-cap input-icon transition-colors"></i>
                                        </div>
                                        <input type="text" name="qualification" id="qualification" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Qualification (e.g., MBBS, MD, FACC)</label>
                                    </div>

                                    <!-- Photo URL -->
                                    <div class="md:col-span-2 input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-image input-icon transition-colors"></i>
                                        </div>
                                        <input type="url" name="photo" id="photo" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Photo URL</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.2s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-emerald-50 to-teal-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                                        <i class="fa-solid fa-address-book text-emerald-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Contact Information</h3>
                                        <p class="text-xs text-dark-500">How patients can reach the doctor</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Phone -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-phone input-icon transition-colors"></i>
                                        </div>
                                        <input type="tel" name="phone" id="phone" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Phone Number</label>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-brands fa-whatsapp input-icon transition-colors text-lg"></i>
                                        </div>
                                        <input type="tel" name="whatsapp" id="whatsapp" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">WhatsApp Number</label>
                                    </div>

                                    <!-- Email -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-envelope input-icon transition-colors"></i>
                                        </div>
                                        <input type="email" name="email" id="email" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Email Address</label>
                                    </div>

                                    <!-- Password -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-lock input-icon transition-colors"></i>
                                        </div>
                                        <input type="password" name="password" id="password" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Login Password</label>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-400 cursor-pointer" onclick="togglePasswordVisibility('password')">
                                            <i class="fa-solid fa-eye text-sm"></i>
                                        </div>
                                    </div>

                                    <!-- Timing -->
                                    <div class="input-group relative">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                            <i class="fa-solid fa-clock input-icon transition-colors"></i>
                                        </div>
                                        <input type="text" name="timing" id="timing" placeholder=" "
                                               class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                        <label class="floating-label">Working Hours</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bio & About -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.3s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-purple-50 to-pink-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                                        <i class="fa-solid fa-file-medical text-purple-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Professional Profile</h3>
                                        <p class="text-xs text-dark-500">Bio and professional description</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-5">
                                <!-- About -->
                                <div>
                                    <label class="block text-sm font-semibold text-dark-700 mb-2">
                                        <i class="fa-solid fa-align-left mr-2 text-dark-400"></i>About
                                    </label>
                                    <textarea name="about" id="about" 
                                              class="w-full px-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium textarea-premium"
                                              placeholder="Write a detailed professional bio..."
                                              maxlength="1000"></textarea>
                                    <div class="flex justify-between items-center mt-1">
                                        <button type="button" onclick="generateBio('about')" class="text-xs bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-700 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all">
                                            <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i> Write Bio with AI
                                        </button>
                                        <span class="text-xs text-dark-400 char-counter" id="aboutCounter">
                                            <span id="aboutCount">0</span>/1000
                                        </span>
                                    </div>
                                </div>

                                <!-- Speech/Quote -->
                                <div class="input-group relative">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                        <i class="fa-solid fa-quote-left input-icon transition-colors"></i>
                                    </div>
                                    <input type="text" name="speech" id="speech" placeholder=" " maxlength="200"
                                           class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                    <label class="floating-label">Professional Quote or Motto</label>
                                </div>
                            </div>
                        </div>

                        <!-- Hospital & Location Associations (Req 7 & Req 16) -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.35s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                        <i class="fa-solid fa-hospital text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Locations & Hospitals</h3>
                                        <p class="text-xs text-dark-500">Practice city, addresses, and hospital affiliations</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <?php
                                $citiesStmt = $pdo->query("SELECT name FROM cities ORDER BY name ASC");
                                $allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
                                if (empty($allCities)) {
                                    $allCities = ['Guwahati', 'Tezpur', 'Kolkata', 'Delhi', 'Dibrugarh'];
                                }
                                ?>
                                <div class="input-group relative">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                        <i class="fa-solid fa-city input-icon transition-colors"></i>
                                    </div>
                                    <select name="practice_city" id="practice_city" 
                                            class="w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium appearance-none">
                                        <?php foreach ($allCities as $cityName): ?>
                                            <option value="<?php echo htmlspecialchars($cityName); ?>" <?php echo $cityName === 'Guwahati' ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cityName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Structured Locations Panel (Req 32) -->
                                <div>
                                    <label class="block text-sm font-semibold text-dark-700 mb-2">
                                        <i class="fa-solid fa-map-location-dot mr-2 text-dark-400"></i>Multiple Practice Locations (Max 2)
                                    </label>
                                    <div class="space-y-4 p-4 border border-dark-100 rounded-xl bg-dark-50">
                                        <div>
                                            <span class="text-xs font-bold uppercase text-dark-500 block mb-1">Location 1</span>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <input type="text" name="location1_address" id="location1_address" placeholder="Address 1 (e.g. Guwahati Metro Hospital)" class="md:col-span-2 px-3 py-2 border border-dark-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:border-teal-500">
                                                <div class="flex gap-1">
                                                    <input type="text" name="location1_lat" id="location1_lat" placeholder="Latitude" class="w-1/2 px-2 py-2 border border-dark-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                                    <input type="text" name="location1_lng" id="location1_lng" placeholder="Longitude" class="w-1/2 px-2 py-2 border border-dark-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border-t border-dark-100 pt-3">
                                            <span class="text-xs font-bold uppercase text-dark-500 block mb-1">Location 2 (Optional)</span>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <input type="text" name="location2_address" id="location2_address" placeholder="Address 2 (e.g. City Dental Clinic)" class="md:col-span-2 px-3 py-2 border border-dark-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:border-teal-500">
                                                <div class="flex gap-1">
                                                    <input type="text" name="location2_lat" id="location2_lat" placeholder="Latitude" class="w-1/2 px-2 py-2 border border-dark-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                                    <input type="text" name="location2_lng" id="location2_lng" placeholder="Longitude" class="w-1/2 px-2 py-2 border border-dark-200 rounded-lg text-xs bg-white text-center font-medium focus:outline-none focus:border-teal-500">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-dark-700 mb-2">
                                        <i class="fa-solid fa-circle-check mr-2 text-dark-400"></i>Associate with Partner Hospitals
                                    </label>
                                    <?php
                                    $hospStmt = $pdo->query("SELECT id, name, city FROM hospitals ORDER BY name ASC");
                                    $allHospitals = $hospStmt->fetchAll(PDO::FETCH_ASSOC);
                                    if (count($allHospitals) === 0):
                                    ?>
                                        <p class="text-xs text-dark-400">No partner hospitals registered yet.</p>
                                    <?php else: ?>
                                        <div class="space-y-2 border border-dark-100 rounded-xl p-3 bg-dark-50 max-h-48 overflow-y-auto animate-fade-in">
                                            <?php foreach ($allHospitals as $hosp): ?>
                                                <label class="flex items-center space-x-3 cursor-pointer p-1.5 hover:bg-white rounded-lg transition">
                                                    <input type="checkbox" name="hospitals[]" value="<?php echo $hosp['id']; ?>" class="w-4 h-4 text-teal-600 border-dark-300 rounded focus:ring-teal-500">
                                                    <span class="text-xs font-semibold text-dark-800"><?php echo htmlspecialchars($hosp['name']); ?> <span class="text-[10px] text-dark-400">(<?php echo htmlspecialchars($hosp['city']); ?>)</span></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Status & Social -->
                    <div class="space-y-6">
                        <!-- Status Selection -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.15s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-amber-50 to-orange-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                                        <i class="fa-solid fa-toggle-on text-amber-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Status</h3>
                                        <p class="text-xs text-dark-500">Current availability</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-3">
                                <?php foreach ($statusOptions as $key => $status): ?>
                                <label class="status-card block p-4 rounded-xl border-2 cursor-pointer transition-all
                                    <?php echo ($key === 'active') 
                                        ? 'border-' . $status['color'] . '-500 bg-' . $status['color'] . '-50 selected' 
                                        : 'border-dark-200 hover:border-dark-300 bg-white'; ?>">
                                    <input type="radio" name="status" value="<?php echo $key; ?>" 
                                           class="sr-only" <?php echo ($key === 'active') ? 'checked' : ''; ?>>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-<?php echo $status['color']; ?>-100 flex items-center justify-center">
                                            <i class="fa-solid <?php echo $status['icon']; ?> text-<?php echo $status['color']; ?>-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-dark-900"><?php echo $status['label']; ?></p>
                                            <p class="text-xs text-dark-500">
                                                <?php 
                                                echo match($key) {
                                                    'active' => 'Available for appointments',
                                                    'on-leave' => 'Temporarily unavailable',
                                                    'inactive' => 'Not accepting patients'
                                                };
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="card-premium rounded-2xl overflow-hidden section-card slide-in" style="animation-delay: 0.25s">
                            <div class="px-6 py-4 border-b border-dark-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                                        <i class="fa-solid fa-share-nodes text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-dark-900">Social Links</h3>
                                        <p class="text-xs text-dark-500">Professional profiles</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <!-- LinkedIn -->
                                <div class="input-group relative">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[#0A66C2] z-10">
                                        <i class="fa-brands fa-linkedin input-icon transition-colors"></i>
                                    </div>
                                    <input type="url" name="linkedin" id="linkedin" placeholder=" "
                                           class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                    <label class="floating-label">LinkedIn URL</label>
                                </div>

                                <!-- Twitter -->
                                <div class="input-group relative">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[#1DA1F2] z-10">
                                        <i class="fa-brands fa-twitter input-icon transition-colors"></i>
                                    </div>
                                    <input type="url" name="twitter" id="twitter" placeholder=" "
                                           class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                    <label class="floating-label">Twitter URL</label>
                                </div>

                                <!-- Facebook -->
                                <div class="input-group relative">
                                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-[#1877F2] z-10">
                                        <i class="fa-brands fa-facebook input-icon transition-colors"></i>
                                    </div>
                                    <input type="url" name="facebook" id="facebook" placeholder=" "
                                           class="floating-input w-full pl-12 pr-4 py-3.5 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                                    <label class="floating-label">Facebook URL</label>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card-premium rounded-2xl p-6 section-card slide-in" style="animation-delay: 0.35s">
                            <h3 class="font-semibold text-dark-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <button type="button" onclick="resetForm()" 
                                        class="w-full px-4 py-3 bg-dark-50 hover:bg-dark-100 rounded-xl text-sm font-medium text-dark-700 transition-colors flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Clear Form</span>
                                </button>
                                <a href="doctors.php" 
                                   class="w-full px-4 py-3 bg-dark-50 hover:bg-dark-100 rounded-xl text-sm font-medium text-dark-700 transition-colors flex items-center justify-center space-x-2">
                                    <i class="fa-solid fa-list"></i>
                                    <span>Back to List</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Save Bar -->
                <div class="sticky bottom-0 left-0 right-0 bg-white/95 backdrop-blur-lg border-t border-dark-200 -mx-6 lg:-mx-8 px-6 lg:px-8 py-4 mt-8 shadow-lg slide-in" style="animation-delay: 0.4s">
                    <div class="flex items-center justify-between max-w-7xl mx-auto">
                        <div class="flex items-center space-x-3">
                            <p class="text-sm text-dark-500">Ready to create a new doctor profile</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="doctors.php" 
                               class="px-6 py-2.5 bg-white border-2 border-dark-300 rounded-xl font-semibold text-dark-700 hover:bg-dark-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" id="saveBtn"
                                    class="btn-primary px-8 py-2.5 rounded-xl font-semibold text-white flex items-center space-x-2">
                                <i class="fa-solid fa-plus"></i>
                                <span>Create Doctor</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
    // DOM Elements
    const form = document.getElementById('addForm');
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    const ratingInput = document.getElementById('rating');
    const ratingStars = document.getElementById('ratingStars');
    const aboutTextarea = document.getElementById('about');
    const aboutCounter = document.getElementById('aboutCounter');
    const aboutCount = document.getElementById('aboutCount');
    const saveBtn = document.getElementById('saveBtn');

    // Photo preview update
    photoInput.addEventListener('input', function() {
        const url = this.value.trim();
        if (url) {
            photoPreview.src = url;
        } else {
            const name = document.getElementById('name').value || 'New Doctor';
            photoPreview.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=0ea5e9&color=fff&size=200`;
        }
    });

    photoPreview.addEventListener('error', function() {
        const name = document.getElementById('name').value || 'New Doctor';
        this.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=0ea5e9&color=fff&size=200`;
    });

    // Rating stars update
    ratingInput.addEventListener('input', function() {
        const rating = parseFloat(this.value) || 0;
        const stars = ratingStars.querySelectorAll('i');
        stars.forEach((star, index) => {
            if (index < Math.floor(rating)) {
                star.className = 'fa-solid fa-star text-sm text-amber-400';
            } else if (index < rating) {
                star.className = 'fa-solid fa-star-half-stroke text-sm text-amber-400';
            } else {
                star.className = 'fa-solid fa-star text-sm text-dark-200';
            }
        });
    });

    // Character counter for about
    aboutTextarea.addEventListener('input', function() {
        const count = this.value.length;
        aboutCount.textContent = count;
        
        aboutCounter.classList.remove('warning', 'danger');
        if (count > 900) {
            aboutCounter.classList.add('danger');
        } else if (count > 750) {
            aboutCounter.classList.add('warning');
        }
    });

    // Status card selection
    document.querySelectorAll('.status-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.status-card').forEach(c => {
                c.classList.remove('selected', 'border-emerald-500', 'bg-emerald-50', 
                                  'border-amber-500', 'bg-amber-50', 
                                  'border-red-500', 'bg-red-50');
                c.classList.add('border-dark-200', 'bg-white');
            });
            
            const input = this.querySelector('input');
            const status = input.value;
            const colors = {
                'active': { border: 'border-emerald-500', bg: 'bg-emerald-50' },
                'on-leave': { border: 'border-amber-500', bg: 'bg-amber-50' },
                'inactive': { border: 'border-red-500', bg: 'bg-red-50' }
            };
            
            this.classList.remove('border-dark-200', 'bg-white');
            this.classList.add('selected', colors[status].border, colors[status].bg);
        });
    });

    // Reset form
    function resetForm() {
        if (confirm('Are you sure you want to clear all fields?')) {
            form.reset();
            photoPreview.src = 'https://ui-avatars.com/api/?name=New+Doctor&background=0ea5e9&color=fff&size=200';
            ratingInput.dispatchEvent(new Event('input'));
            document.querySelector('input[name="status"][value="active"]').closest('.status-card').click();
        }
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Creating...</span>';
        saveBtn.disabled = true;
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        ratingInput.dispatchEvent(new Event('input'));
    });

    // Password visibility toggle
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = event.target.closest('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.className = 'fa-solid fa-eye-slash text-sm';
        } else {
            field.type = 'password';
            icon.className = 'fa-solid fa-eye text-sm';
        }
    }

    // Offline AI Bio writer AJAX
    function generateBio(textareaId) {
        const name = document.getElementById('name').value.trim();
        const specialty = document.getElementById('specialty').value;
        const qualification = document.getElementById('qualification').value.trim();
        const experience = document.getElementById('experience').value;
        
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
                    document.getElementById(textareaId).dispatchEvent(new Event('input'));
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
</script>
</body>
</html>

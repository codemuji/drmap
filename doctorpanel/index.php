<?php
require_once __DIR__ . '/inc/auth.php';
require_doctor_login();

$doctor_id = current_doctor()['id'];
$pdo = getPDO();

// Get doctor data
$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ? LIMIT 1');
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: logout.php');
    exit;
}

// Decode JSON fields
$social = json_decode($doctor['social'] ?? '{}', true);
$addresses = json_decode($doctor['addresses'] ?? '[]', true);
$locations = json_decode($doctor['locations'] ?? '[]', true);
$quickFacts = json_decode($doctor['quick_facts'] ?? '[]', true);
$videos = json_decode($doctor['videos'] ?? '[]', true);
$gallery = json_decode($doctor['gallery'] ?? '[]', true);

// Calculate profile completeness
$fields = [
    'name' => !empty($doctor['name']),
    'specialty' => !empty($doctor['specialty']),
    'qualification' => !empty($doctor['qualification']),
    'experience' => !empty($doctor['experience']),
    'photo' => !empty($doctor['photo']),
    'phone' => !empty($doctor['phone']),
    'email' => !empty($doctor['email']),
    'about' => !empty($doctor['about']),
    'addresses' => is_array($addresses) && count($addresses) > 0,
    'social' => (!empty($social['linkedin']) || !empty($social['twitter']) || !empty($social['facebook'])),
    'videos' => is_array($videos) && count($videos) > 0,
    'gallery' => is_array($gallery) && count($gallery) > 0,
    'speech' => !empty($doctor['speech']),
    'quick_facts' => is_array($quickFacts) && count($quickFacts) > 0,
];

$completed = count(array_filter($fields));
$total = count($fields);
$completeness = round(($completed / $total) * 100);

// Get enquiries count
$enquiry_stmt = $pdo->prepare('SELECT COUNT(*) FROM enquiries WHERE doctor_id = ?');
$enquiry_stmt->execute([$doctor_id]);
$enquiry_count = $enquiry_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Doctor Panel</title>
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

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-emerald { background: #d1fae5; color: #047857; }
        .badge-amber { background: #fef3c7; color: #b45309; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(14, 165, 233, 0.02) 100%);
            border: 1px solid rgba(14, 165, 233, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.15);
        }
        
        .progress-ring {
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .action-card {
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
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
            <a href="index.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-white bg-primary-600">
                <i class="fa-solid fa-grid-2 w-5 mr-3"></i>
                <span>Dashboard</span>
            </a>
            <a href="view.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-eye w-5 mr-3"></i>
                <span>View Profile</span>
            </a>
            <a href="edit.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-pen w-5 mr-3"></i>
                <span>Edit Profile</span>
            </a>
            <a href="enquiries.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-envelope w-5 mr-3"></i>
                <span>Patient Enquiries</span>
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
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                        <p class="text-slate-600 text-sm mt-1">Welcome back, <?php echo htmlspecialchars($doctor['name']); ?></p>
                    </div>
                    <button onclick="toggleMobileMenu()" class="lg:hidden w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 max-w-[1600px] mx-auto">
            <!-- Welcome Banner -->
            <div class="card-premium rounded-2xl p-6 md:p-8 mb-6 bg-gradient-to-r from-primary-50 via-blue-50 to-purple-50 border-primary-200">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Profile Photo -->
                    <div class="relative flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($doctor['photo'] ? '../' . $doctor['photo'] : 'https://via.placeholder.com/120?text=Profile'); ?>" 
                             alt="<?php echo htmlspecialchars($doctor['name']); ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('<?php echo htmlspecialchars($doctor['name']); ?>')+'&background=0ea5e9&color=fff&size=120'"
                             class="w-24 h-24 md:w-32 md:h-32 rounded-2xl object-cover shadow-xl ring-4 ring-white">
                        <?php if (!empty($doctor['rank'])): ?>
                        <div class="absolute -top-2 -right-2 w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                            #<?php echo $doctor['rank']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Welcome Text -->
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-2">
                            <h2 class="text-2xl md:text-4xl font-bold text-slate-900">Welcome back, <?php echo htmlspecialchars($doctor['name']); ?>! 👋</h2>
                            <span class="badge badge-<?php echo ($doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red')); ?>">
                                <i class="fa-solid fa-circle text-xs"></i>
                                <?php echo ucfirst(str_replace('-', ' ', $doctor['status'])); ?>
                            </span>
                        </div>
                        <p class="text-lg text-slate-600 mb-1"><?php echo htmlspecialchars($doctor['specialty']); ?> • <?php echo htmlspecialchars($doctor['qualification']); ?></p>
                        <p class="text-slate-500 mb-4">Manage your profile and keep your information up to date</p>
                        
                        <!-- Quick Info Pills -->
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start">
                            <span class="px-3 py-1.5 bg-white/60 backdrop-blur-sm rounded-lg text-sm font-medium text-slate-700">
                                <i class="fa-solid fa-star text-amber-500 mr-1"></i>
                                <?php echo number_format($doctor['rating'], 1); ?> Rating
                            </span>
                            <span class="px-3 py-1.5 bg-white/60 backdrop-blur-sm rounded-lg text-sm font-medium text-slate-700">
                                <i class="fa-solid fa-briefcase text-primary-500 mr-1"></i>
                                <?php echo $doctor['experience']; ?> Years
                            </span>
                            <span class="px-3 py-1.5 bg-white/60 backdrop-blur-sm rounded-lg text-sm font-medium text-slate-700">
                                <i class="fa-solid fa-hospital text-emerald-500 mr-1"></i>
                                <?php echo is_array($addresses) ? count($addresses) : 0; ?> Clinics
                            </span>
                            <?php if (!empty($doctor['practice_city'])): ?>
                            <span class="px-3 py-1.5 bg-white/60 backdrop-blur-sm rounded-lg text-sm font-medium text-slate-700">
                                <i class="fa-solid fa-location-dot text-rose-500 mr-1"></i>
                                <?php echo htmlspecialchars($doctor['practice_city']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile Completeness Circle -->
                    <div class="flex-shrink-0">
                        <div class="relative w-28 h-28">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="56" cy="56" r="48" stroke="#e2e8f0" stroke-width="8" fill="none"/>
                                <circle cx="56" cy="56" r="48" 
                                        stroke="<?php echo $completeness >= 80 ? '#10b981' : ($completeness >= 50 ? '#f59e0b' : '#ef4444'); ?>" 
                                        stroke-width="8" 
                                        fill="none"
                                        stroke-linecap="round"
                                        class="progress-ring"
                                        style="stroke-dasharray: <?php echo 2 * 3.14159 * 48; ?>; stroke-dashoffset: <?php echo 2 * 3.14159 * 48 * (1 - $completeness / 100); ?>;"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-slate-900"><?php echo $completeness; ?>%</span>
                                <span class="text-xs text-slate-600 font-medium">Complete</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Dashboard -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <!-- Experience -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-graduation-cap text-primary-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $doctor['experience']; ?></p>
                    <p class="text-xs text-slate-600 font-medium">Years Experience</p>
                </div>

                <!-- Rating -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-star text-amber-500 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo number_format($doctor['rating'], 1); ?></p>
                    <p class="text-xs text-slate-600 font-medium">Rating</p>
                </div>

                <!-- Clinics -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-hospital text-emerald-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo is_array($addresses) ? count($addresses) : 0; ?></p>
                    <p class="text-xs text-slate-600 font-medium">Clinic<?php echo (is_array($addresses) && count($addresses) != 1) ? 's' : ''; ?></p>
                </div>

                <!-- Videos -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-video text-red-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo is_array($videos) ? count($videos) : 0; ?></p>
                    <p class="text-xs text-slate-600 font-medium">Videos</p>
                </div>

                <!-- Gallery -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-images text-indigo-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo is_array($gallery) ? count($gallery) : 0; ?></p>
                    <p class="text-xs text-slate-600 font-medium">Images</p>
                </div>

                <!-- Enquiries -->
                <div class="stat-card rounded-xl p-4">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-envelope text-purple-600 text-lg"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $enquiry_count; ?></p>
                    <p class="text-xs text-slate-600 font-medium">Enquiries</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Quick Actions -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-5">
                            <i class="fa-solid fa-bolt text-amber-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-slate-900">Quick Actions</h3>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <a href="view.php" class="action-card p-5 rounded-xl border-2 border-slate-200 hover:border-primary-300 hover:bg-primary-50 group">
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center flex-shrink-0 group-hover:from-primary-200 group-hover:to-primary-300 transition-all">
                                        <i class="fa-solid fa-eye text-primary-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-900 mb-1">View Profile</p>
                                        <p class="text-slate-600 text-sm">See your complete profile overview</p>
                                    </div>
                                </div>
                            </a>

                            <a href="edit.php" class="action-card p-5 rounded-xl border-2 border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 group">
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-200 flex items-center justify-center flex-shrink-0 group-hover:from-emerald-200 group-hover:to-emerald-300 transition-all">
                                        <i class="fa-solid fa-pen text-emerald-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-900 mb-1">Edit Profile</p>
                                        <p class="text-slate-600 text-sm">Update your information and details</p>
                                    </div>
                                </div>
                            </a>

                            <a href="enquiries.php" class="action-card p-5 rounded-xl border-2 border-slate-200 hover:border-purple-300 hover:bg-purple-50 group">
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-100 to-purple-200 flex items-center justify-center flex-shrink-0 group-hover:from-purple-200 group-hover:to-purple-300 transition-all">
                                        <i class="fa-solid fa-envelope text-purple-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-900 mb-1">Patient Enquiries</p>
                                        <p class="text-slate-600 text-sm">View and respond to patient messages</p>
                                    </div>
                                </div>
                            </a>

                            <a href="upload_video.php" class="action-card p-5 rounded-xl border-2 border-slate-200 hover:border-red-300 hover:bg-red-50 group">
                                <div class="flex items-start space-x-4">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-100 to-red-200 flex items-center justify-center flex-shrink-0 group-hover:from-red-200 group-hover:to-red-300 transition-all">
                                        <i class="fa-solid fa-video text-red-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-slate-900 mb-1">Upload Videos</p>
                                        <p class="text-slate-600 text-sm">Add videos to your profile</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Profile Summary -->
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-5">
                            <i class="fa-solid fa-user-circle text-primary-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-slate-900">Profile Summary</h3>
                        </div>
                        <div class="space-y-4">
                            <?php if (!empty($doctor['about'])): ?>
                            <div class="p-4 rounded-lg bg-slate-50">
                                <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-2">About</p>
                                <p class="text-slate-700 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars(substr($doctor['about'], 0, 200))); ?><?php echo strlen($doctor['about']) > 200 ? '...' : ''; ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="grid md:grid-cols-2 gap-4">
                                <?php if (!empty($doctor['phone'])): ?>
                                <div class="p-4 rounded-lg bg-slate-50">
                                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-1">Phone</p>
                                    <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($doctor['email'])): ?>
                                <div class="p-4 rounded-lg bg-slate-50">
                                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-1">Email</p>
                                    <p class="text-slate-900 font-medium text-sm break-all"><?php echo htmlspecialchars($doctor['email']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($doctor['speech'])): ?>
                            <div class="p-4 rounded-lg bg-gradient-to-r from-primary-50 to-blue-50 border border-primary-200">
                                <p class="text-xs text-primary-700 font-semibold uppercase tracking-wide mb-2">Motto</p>
                                <p class="text-slate-900 italic font-medium text-sm">"<?php echo htmlspecialchars($doctor['speech']); ?>"</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Profile Completeness -->
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-5">
                            <i class="fa-solid fa-chart-line text-emerald-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-slate-900">Profile Progress</h3>
                        </div>
                        
                        <!-- Completeness Bar -->
                        <div class="mb-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-slate-700">Overall Completeness</span>
                                <span class="text-lg font-bold text-slate-900"><?php echo $completeness; ?>%</span>
                            </div>
                            <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 <?php echo $completeness >= 80 ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : ($completeness >= 50 ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-gradient-to-r from-red-500 to-red-600'); ?>" 
                                     style="width: <?php echo $completeness; ?>%"></div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="space-y-2">
                            <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-3">Checklist</p>
                            <?php foreach ($fields as $field => $completed): ?>
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-<?php echo $completed ? 'check-circle text-emerald-500' : 'circle text-slate-300'; ?> text-sm"></i>
                                <span class="text-sm <?php echo $completed ? 'text-slate-700' : 'text-slate-400'; ?>"><?php echo ucfirst(str_replace('_', ' ', $field)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($completeness < 100): ?>
                        <div class="mt-5 pt-5 border-t border-slate-200">
                            <a href="edit.php" class="btn-primary w-full py-2.5 rounded-xl text-sm font-semibold text-white flex items-center justify-center space-x-2">
                                <i class="fa-solid fa-arrow-up"></i>
                                <span>Complete Profile</span>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Account Status -->
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-5">
                            <i class="fa-solid fa-shield-check text-primary-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-slate-900">Account Info</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="p-3 rounded-lg bg-<?php echo $doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red'); ?>-50 border border-<?php echo $doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red'); ?>-200">
                                <p class="text-xs text-<?php echo $doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red'); ?>-700 font-semibold uppercase tracking-wide mb-1">Status</p>
                                <p class="text-sm text-<?php echo $doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red'); ?>-900 font-bold flex items-center">
                                    <i class="fa-solid fa-circle text-xs mr-2"></i>
                                    <?php echo ucfirst(str_replace('-', ' ', $doctor['status'])); ?>
                                </p>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50">
                                <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-1">Profile ID</p>
                                <p class="text-sm text-slate-900 font-bold">#<?php echo $doctor['id']; ?></p>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-50">
                                <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-1">Edit Permission</p>
                                <p class="text-sm font-bold <?php echo $doctor['can_edit'] ? 'text-emerald-600' : 'text-red-600'; ?>">
                                    <i class="fa-solid fa-<?php echo $doctor['can_edit'] ? 'check' : 'times'; ?> mr-1"></i>
                                    <?php echo $doctor['can_edit'] ? 'Enabled' : 'Disabled'; ?>
                                </p>
                            </div>

                            <?php if (!empty($doctor['created_at'])): ?>
                            <div class="p-3 rounded-lg bg-slate-50">
                                <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide mb-1">Member Since</p>
                                <p class="text-sm text-slate-900 font-medium"><?php echo date('M d, Y', strtotime($doctor['created_at'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Need Help -->
                    <div class="card-premium rounded-2xl p-6 bg-gradient-to-br from-slate-50 to-slate-100">
                        <div class="text-center">
                            <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-question text-primary-600 text-xl"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-2">Need Help?</h4>
                            <p class="text-sm text-slate-600 mb-4">Contact support if you have any questions</p>
                            <a href="mailto:support@drmap.com" class="text-primary-600 hover:text-primary-700 font-semibold text-sm">
                                support@drmap.com
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function toggleMobileMenu() {
    // Mobile menu functionality
}
</script>

</body>
</html>

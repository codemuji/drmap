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

// Helper function to convert 24-hour to 12-hour format
function convert24to12($time24) {
    if (!$time24) return $time24;
    $parts = explode(':', $time24);
    $hours = (int)($parts[0] ?? 0);
    $minutes = $parts[1] ?? '00';
    $period = $hours >= 12 ? 'PM' : 'AM';
    $hours = $hours % 12 ?: 12;
    return sprintf('%d:%s %s', $hours, $minutes, $period);
}

// Helper function to format timing from JSON
function formatTiming($timingData) {
    $timing = $timingData;
    
    // If it's a string, try to parse as JSON
    if (is_string($timing)) {
        $timing = json_decode($timing, true);
        if (!is_array($timing)) {
            return 'No schedule available';
        }
    }
    
    if (empty($timing) || !is_array($timing)) {
        return 'No schedule available';
    }

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $labels = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];
    
    $parts = [];
    
    foreach ($days as $day) {
        $dayInfo = $timing[$day] ?? null;
        if ($dayInfo && ($dayInfo['enabled'] === true || $dayInfo['enabled'] === 'true') && is_array($dayInfo['slots']) && count($dayInfo['slots']) > 0) {
            $slots = [];
            foreach ($dayInfo['slots'] as $slot) {
                if (isset($slot['open']) && $slot['open']) {
                    $open = convert24to12($slot['open']);
                    $close = isset($slot['close']) && $slot['close'] ? convert24to12($slot['close']) : '';
                    $slots[] = $close ? "$open - $close" : $open;
                }
            }
            
            if (count($slots) > 0) {
                $slotText = implode(', ', $slots);
                $parts[] = $labels[$day] . ': ' . $slotText;
            }
        }
    }
    
    return count($parts) > 0 ? implode(' | ', $parts) : 'No schedule available';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Doctor Panel</title>
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
        }
        
        .video-card {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            aspect-ratio: 16/9;
            background: #f1f5f9;
        }
        
        .video-card:hover .play-overlay {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.4);
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .gallery-img {
            aspect-ratio: 1;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .gallery-img:hover {
            transform: scale(1.05);
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
            <a href="view.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-white bg-primary-600">
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
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="w-10 h-10 rounded-xl bg-white shadow-sm border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900">Complete Profile Overview</h1>
                            <p class="text-sm text-slate-600">View all your professional details</p>
                        </div>
                    </div>
                    <a href="edit.php" class="px-4 py-2.5 btn-primary rounded-xl text-sm font-medium text-white flex items-center space-x-2">
                        <i class="fa-solid fa-pen"></i>
                        <span>Edit Profile</span>
                    </a>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 max-w-[1400px] mx-auto">
            <!-- Profile Header Card -->
            <div class="card-premium rounded-2xl p-6 md:p-8 mb-6">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Photo -->
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($doctor['photo'] ? '../' . $doctor['photo'] : 'https://via.placeholder.com/160?text=Profile'); ?>" 
                             alt="<?php echo htmlspecialchars($doctor['name']); ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('<?php echo htmlspecialchars($doctor['name']); ?>')+'&background=0ea5e9&color=fff&size=160'"
                             class="w-40 h-40 rounded-2xl object-cover shadow-xl ring-4 ring-white">
                        <?php if (!empty($doctor['rank'])): ?>
                        <div class="absolute -top-2 -right-2 w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold shadow-lg">
                            #<?php echo $doctor['rank']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <h2 class="text-3xl md:text-4xl font-bold text-slate-900"><?php echo htmlspecialchars($doctor['name']); ?></h2>
                            <span class="badge badge-<?php echo ($doctor['status'] === 'active' ? 'emerald' : ($doctor['status'] === 'on-leave' ? 'amber' : 'red')); ?>">
                                <i class="fa-solid fa-circle text-xs"></i>
                                <?php echo ucfirst(str_replace('-', ' ', $doctor['status'])); ?>
                            </span>
                            <?php if ($doctor['can_edit']): ?>
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">
                                <i class="fa-solid fa-pen text-xs"></i>
                                Can Edit
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xl text-primary-600 font-semibold mb-2">
                            <i class="fa-solid fa-user-doctor mr-2"></i><?php echo htmlspecialchars($doctor['specialty']); ?>
                        </p>
                        <p class="text-slate-600 mb-2">
                            <i class="fa-solid fa-graduation-cap mr-2"></i><?php echo htmlspecialchars($doctor['qualification']); ?>
                        </p>
                        <?php if (!empty($doctor['practice_city'])): ?>
                        <p class="text-slate-600 mb-4">
                            <i class="fa-solid fa-location-dot mr-2"></i><?php echo htmlspecialchars($doctor['practice_city']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Quick Stats Row -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-briefcase-medical text-2xl text-primary-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $doctor['experience']; ?></p>
                                <p class="text-xs text-slate-600 font-medium">Years Experience</p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-star text-2xl text-amber-500"></i>
                                </div>
                                <p class="text-2xl font-bold text-slate-900"><?php echo number_format($doctor['rating'], 1); ?></p>
                                <p class="text-xs text-slate-600 font-medium">Rating</p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-hospital text-2xl text-emerald-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-slate-900"><?php echo is_array($addresses) ? count($addresses) : 0; ?></p>
                                <p class="text-xs text-slate-600 font-medium">Clinic<?php echo (is_array($addresses) && count($addresses) != 1) ? 's' : ''; ?></p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-id-card text-2xl text-purple-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-slate-900">#<?php echo $doctor['id']; ?></p>
                                <p class="text-xs text-slate-600 font-medium">Profile ID</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content Column -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- About Section -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-4">About</h3>
                        <p class="text-slate-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($doctor['about'])); ?></p>
                    </div>

                    <!-- Quote Section -->
                    <?php if (!empty($doctor['speech'])): ?>
                    <div class="card-premium rounded-2xl p-8 bg-gradient-to-r from-primary-50 to-blue-50">
                        <div class="flex items-start space-x-4">
                            <i class="fa-solid fa-quote-left text-3xl text-primary-300 flex-shrink-0"></i>
                            <p class="text-lg text-slate-900 italic font-medium"><?php echo htmlspecialchars($doctor['speech']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Contact Information -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Contact Information</h3>
                        <div class="space-y-4">
                            <?php if (!empty($doctor['email'])): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                    <i class="fa-solid fa-envelope text-primary-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Email</p>
                                    <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($doctor['email']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($doctor['phone'])): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i class="fa-solid fa-phone text-emerald-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Phone</p>
                                    <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($doctor['whatsapp'])): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <i class="fa-brands fa-whatsapp text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">WhatsApp</p>
                                    <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($doctor['whatsapp']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($doctor['timing'])): ?>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <i class="fa-solid fa-clock text-amber-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Availability</p>
                                    <div class="text-slate-900 font-medium text-sm mt-1">
                                        <?php echo htmlspecialchars(formatTiming($doctor['timing'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php 
                            $addresses = json_decode($doctor['addresses'] ?? '[]', true);
                            if (is_array($addresses) && count($addresses) > 0): 
                            ?>
                            <div class="flex items-start space-x-4 pt-4 border-t border-slate-200">
                                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-location-dot text-orange-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-slate-600">Clinic Addresses</p>
                                    <div class="text-slate-900 font-medium space-y-2 mt-2">
                                        <?php foreach ($addresses as $addr): ?>
                                        <p><?php echo htmlspecialchars($addr); ?></p>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Professional Details -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Professional Details</h3>
                        <div class="space-y-4">
                            <?php if (!empty($doctor['qualification'])): ?>
                            <div>
                                <p class="text-sm text-slate-600">Qualification</p>
                                <p class="text-slate-900 font-medium"><?php echo htmlspecialchars($doctor['qualification']); ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-slate-600">Experience</p>
                                    <p class="text-slate-900 font-medium"><?php echo $doctor['experience']; ?> years</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">Rating</p>
                                    <p class="text-slate-900 font-medium"><?php echo number_format($doctor['rating'], 1); ?>/5.0 <i class="fa-solid fa-star text-amber-400"></i></p>
                                </div>
                            </div>

                            <div>
                                <p class="text-sm text-slate-600">Status</p>
                                <p class="text-slate-900 font-medium">
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                        <?php echo $doctor['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : ($doctor['status'] === 'on-leave' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'); ?>">
                                        <?php echo ucfirst(str_replace('-', ' ', $doctor['status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                    <!-- Social Links -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Social Links</h3>
                        <div class="space-y-3">
                            <?php if (!empty($social['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($social['linkedin']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <i class="fa-brands fa-linkedin text-blue-600 text-lg"></i>
                                </div>
                                <span class="font-medium text-slate-900">LinkedIn</span>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($social['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($social['twitter']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-sky-100 flex items-center justify-center">
                                    <i class="fa-brands fa-twitter text-sky-600 text-lg"></i>
                                </div>
                                <span class="font-medium text-slate-900">Twitter</span>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($social['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($social['facebook']); ?>" target="_blank" 
                               class="flex items-center space-x-3 p-3 rounded-lg hover:bg-slate-50 transition-colors">
                                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <i class="fa-brands fa-facebook text-indigo-600 text-lg"></i>
                                </div>
                                <span class="font-medium text-slate-900">Facebook</span>
                            </a>
                            <?php endif; ?>

                            <?php if (empty($social['linkedin']) && empty($social['twitter']) && empty($social['facebook'])): ?>
                            <p class="text-slate-500 text-center py-4">No social links added</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile Info -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Profile Info</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm text-slate-600">Last Updated</p>
                                <p class="text-slate-900 font-medium"><?php echo date('M d, Y', strtotime($doctor['updated_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-600">Profile ID</p>
                                <p class="text-slate-900 font-medium">#<?php echo $doctor['id']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>

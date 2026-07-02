<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';
$pdo = getPDO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: doctors.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    header('Location: doctors.php');
    exit;
}

// Decode JSON fields
$social = json_decode($doc['social'] ?? '{}', true);
$addresses = json_decode($doc['addresses'] ?? '[]', true);
$locations = json_decode($doc['locations'] ?? '[]', true);
$quickFacts = json_decode($doc['quick_facts'] ?? '[]', true);
$videos = json_decode($doc['videos'] ?? '[]', true);
$gallery = json_decode($doc['gallery'] ?? '[]', true);

// Backward compatibility for old social fields
if (!is_array($social)) $social = [];
$doc['linkedin'] = $social['linkedin'] ?? $doc['linkedin'] ?? '';
$doc['twitter'] = $social['twitter'] ?? $doc['twitter'] ?? '';
$doc['facebook'] = $social['facebook'] ?? $doc['facebook'] ?? '';

// Status mapping for consistent badges
$statusOptions = [
    'active' => ['label' => 'Active', 'color' => 'emerald', 'icon' => 'fa-circle-check'],
    'on-leave' => ['label' => 'On Leave', 'color' => 'amber', 'icon' => 'fa-clock'],
    'inactive' => ['label' => 'Inactive', 'color' => 'red', 'icon' => 'fa-circle-xmark']
];

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
    
    if (is_string($timing)) {
        $timing = json_decode($timing, true);
        if (!is_array($timing)) return 'No schedule available';
    }
    
    if (empty($timing) || !is_array($timing)) return 'No schedule available';

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $labels = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 
               'friday' => 'Fri', 'saturday' => 'Sat', 'sunday' => 'Sun'];
    
    $parts = [];
    
    foreach ($days as $day) {
        $dayInfo = $timing[$day] ?? null;
        if ($dayInfo && ($dayInfo['enabled'] === true || $dayInfo['enabled'] === 'true') && 
            is_array($dayInfo['slots']) && count($dayInfo['slots']) > 0) {
            $slots = [];
            foreach ($dayInfo['slots'] as $slot) {
                if (isset($slot['open']) && $slot['open']) {
                    $open = convert24to12($slot['open']);
                    $close = isset($slot['close']) && $slot['close'] ? convert24to12($slot['close']) : '';
                    $slots[] = $close ? "$open - $close" : $open;
                }
            }
            
            if (count($slots) > 0) {
                $parts[] = $labels[$day] . ': ' . implode(', ', $slots);
            }
        }
    }
    
    return count($parts) > 0 ? implode(' | ', $parts) : 'No schedule available';
}
?>
<?php include __DIR__ . '/inc/head.php'; ?>
<style>
    .stat-card {
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(14, 165, 233, 0.02) 100%);
        border: 1px solid rgba(14, 165, 233, 0.1);
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
    
    .video-card {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
        aspect-ratio: 16/9;
        background: #f1f5f9;
    }
    
    .video-card:hover .play-overlay {
        opacity: 1;
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
<body class="mesh-gradient min-h-screen antialiased">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <main class="flex-1 lg:ml-[280px]">
        <header class="glass sticky top-0 z-40 border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="doctors.php" class="w-10 h-10 rounded-xl bg-white shadow-sm border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 hover:border-dark-300 transition-all">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <div class="flex items-center space-x-2 text-sm text-dark-500 mb-1">
                                <a href="doctors.php" class="hover:text-primary-600 transition-colors">Doctors</a>
                                <i class="fa-solid fa-chevron-right text-xs text-dark-300"></i>
                                <span class="text-dark-700 font-medium">Profile</span>
                            </div>
                            <h1 class="text-xl font-bold text-dark-900">Complete Profile Overview</h1>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="edit.php?id=<?php echo (int)$doc['id']; ?>" class="px-4 py-2.5 btn-primary rounded-xl text-sm font-medium text-white flex items-center space-x-2">
                            <i class="fa-solid fa-pen"></i>
                            <span>Edit</span>
                        </a>
                        <form method="POST" action="delete_doctor.php" onsubmit="return confirm('Delete this doctor?');">
                            <input type="hidden" name="id" value="<?php echo (int)$doc['id']; ?>">
                            <button type="submit" class="px-4 py-2.5 bg-red-50 border border-red-100 rounded-xl text-sm font-medium text-red-600 hover:bg-red-100 transition-colors">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 max-w-[1400px] mx-auto">
            <!-- Profile Header Card -->
            <div class="card-premium rounded-2xl p-6 md:p-8 mb-6">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Photo -->
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($doc['photo'] ? '../' . $doc['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($doc['name']) . '&background=0ea5e9&color=fff&size=200'); ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('<?php echo addslashes($doc['name'] ?: 'Doctor'); ?>')" 
                             class="w-40 h-40 rounded-2xl object-cover shadow-xl ring-4 ring-white">
                        <?php if (!empty($doc['rank'])): ?>
                        <div class="absolute -top-2 -right-2 w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold shadow-lg">
                            #<?php echo $doc['rank']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <h2 class="text-3xl md:text-4xl font-bold text-dark-900"><?php echo htmlspecialchars($doc['name']); ?></h2>
                            <?php $st = $doc['status'] ?? 'active'; $so = $statusOptions[$st] ?? $statusOptions['active']; ?>
                            <span class="badge badge-<?php echo $so['color']; ?>">
                                <i class="fa-solid <?php echo $so['icon']; ?> text-xs"></i>
                                <?php echo $so['label']; ?>
                            </span>
                            <?php if ($doc['can_edit']): ?>
                            <span class="badge" style="background: #dbeafe; color: #1e40af;">
                                <i class="fa-solid fa-pen text-xs"></i>
                                Can Edit
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xl text-primary-600 font-semibold mb-2">
                            <i class="fa-solid fa-user-doctor mr-2"></i><?php echo htmlspecialchars($doc['specialty']); ?>
                        </p>
                        <p class="text-dark-600 mb-2">
                            <i class="fa-solid fa-graduation-cap mr-2"></i><?php echo htmlspecialchars($doc['qualification']); ?>
                        </p>
                        <?php if (!empty($doc['practice_city'])): ?>
                        <p class="text-dark-600 mb-4">
                            <i class="fa-solid fa-location-dot mr-2"></i><?php echo htmlspecialchars($doc['practice_city']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Quick Stats Row -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-briefcase-medical text-2xl text-primary-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-dark-900"><?php echo $doc['experience']; ?></p>
                                <p class="text-xs text-dark-600 font-medium">Years Experience</p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-star text-2xl text-amber-500"></i>
                                </div>
                                <p class="text-2xl font-bold text-dark-900"><?php echo number_format($doc['rating'], 1); ?></p>
                                <p class="text-xs text-dark-600 font-medium">Rating</p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-hospital text-2xl text-emerald-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-dark-900"><?php echo is_array($addresses) ? count($addresses) : 0; ?></p>
                                <p class="text-xs text-dark-600 font-medium">Clinic<?php echo (is_array($addresses) && count($addresses) != 1) ? 's' : ''; ?></p>
                            </div>
                            <div class="stat-card rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fa-solid fa-id-card text-2xl text-purple-600"></i>
                                </div>
                                <p class="text-2xl font-bold text-dark-900">#<?php echo $doc['id']; ?></p>
                                <p class="text-xs text-dark-600 font-medium">Profile ID</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Main Content Column -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- About Section -->
                    <?php if (!empty($doc['about'])): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <i class="fa-solid fa-user-circle text-primary-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-dark-900">About</h3>
                        </div>
                        <p class="text-dark-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($doc['about'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Quote Section -->
                    <?php if (!empty($doc['speech'])): ?>
                    <div class="card-premium rounded-2xl p-6 bg-gradient-to-r from-primary-50 to-blue-50 border-primary-200">
                        <div class="flex items-start space-x-4">
                            <i class="fa-solid fa-quote-left text-4xl text-primary-400 flex-shrink-0"></i>
                            <p class="text-lg text-dark-900 italic font-medium pt-2"><?php echo htmlspecialchars($doc['speech']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Facts -->
                    <?php if (is_array($quickFacts) && count($quickFacts) > 0): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <i class="fa-solid fa-lightbulb text-amber-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-dark-900">Quick Facts</h3>
                        </div>
                        <ul class="space-y-3">
                            <?php foreach ($quickFacts as $fact): ?>
                            <li class="flex items-start space-x-3">
                                <i class="fa-solid fa-check-circle text-emerald-500 mt-1 flex-shrink-0"></i>
                                <div class="flex-1">
                                    <?php 
                                    if (is_array($fact) && isset($fact['label']) && isset($fact['value'])) {
                                        echo '<span class="font-semibold text-dark-900">' . htmlspecialchars($fact['label']) . ':</span> ';
                                        echo '<span class="text-dark-700">' . htmlspecialchars($fact['value']) . '</span>';
                                    } else if (is_array($fact)) {
                                        echo '<span class="text-dark-700">' . htmlspecialchars(implode(', ', $fact)) . '</span>';
                                    } else {
                                        echo '<span class="text-dark-700">' . htmlspecialchars((string)$fact) . '</span>';
                                    }
                                    ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Videos -->
                    <?php if (is_array($videos) && count($videos) > 0): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <i class="fa-solid fa-video text-red-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-dark-900">Videos</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($videos as $video): ?>
                            <?php 
                            $videoUrl = is_array($video) && isset($video['url']) ? $video['url'] : (is_string($video) ? $video : '');
                            $videoTitle = is_array($video) && isset($video['title']) ? $video['title'] : '';
                            if (empty($videoUrl)) continue;
                            ?>
                            <div class="video-card">
                                <iframe src="<?php echo htmlspecialchars(strpos($videoUrl, 'http') === 0 ? $videoUrl : '../' . $videoUrl); ?>" 
                                        class="w-full h-full" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen></iframe>
                                <?php if ($videoTitle): ?>
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-3">
                                    <p class="text-white text-sm font-medium"><?php echo htmlspecialchars($videoTitle); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Gallery -->
                    <?php if (is_array($gallery) && count($gallery) > 0): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <div class="flex items-center space-x-2 mb-4">
                            <i class="fa-solid fa-images text-purple-600 text-xl"></i>
                            <h3 class="text-xl font-bold text-dark-900">Gallery</h3>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($gallery as $image): ?>
                            <?php 
                            $imgUrl = is_array($image) && isset($image['url']) ? $image['url'] : (is_string($image) ? $image : '');
                            if (empty($imgUrl)) continue;
                            ?>
                            <div class="overflow-hidden rounded-xl">
                                <img src="<?php echo htmlspecialchars(strpos($imgUrl, 'http') === 0 ? $imgUrl : '../' . $imgUrl); ?>" 
                                     alt="Gallery image" 
                                     class="gallery-img w-full">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Column -->
                <div class="space-y-6">
                    <!-- Contact Info -->
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-dark-900 mb-4">Contact Information</h3>
                        <div class="space-y-3">
                            <div class="flex items-start space-x-3">
                                <i class="fa-solid fa-phone text-primary-600 mt-1"></i>
                                <div>
                                    <p class="text-xs text-dark-500 font-medium">Phone</p>
                                    <p class="text-dark-900"><?php echo htmlspecialchars($doc['phone']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-3">
                                <i class="fa-solid fa-envelope text-primary-600 mt-1"></i>
                                <div>
                                    <p class="text-xs text-dark-500 font-medium">Email</p>
                                    <p class="text-dark-900 break-words"><?php echo htmlspecialchars($doc['email']); ?></p>
                                </div>
                            </div>
                            <?php if (!empty($doc['whatsapp'])): ?>
                            <div class="flex items-start space-x-3">
                                <i class="fa-brands fa-whatsapp text-emerald-600 mt-1"></i>
                                <div>
                                    <p class="text-xs text-dark-500 font-medium">WhatsApp</p>
                                    <p class="text-dark-900"><?php echo htmlspecialchars($doc['whatsapp']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Schedule -->
                    <?php if (!empty($doc['timing'])): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-dark-900 mb-4">
                            <i class="fa-solid fa-clock text-primary-600 mr-2"></i>Schedule
                        </h3>
                        <p class="text-sm text-dark-700 leading-relaxed"><?php echo formatTiming($doc['timing']); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Social Links -->
                    <?php if (!empty($doc['linkedin']) || !empty($doc['twitter']) || !empty($doc['facebook'])): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-dark-900 mb-4">Social Links</h3>
                        <div class="flex flex-col space-y-3">
                            <?php if (!empty($doc['linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($doc['linkedin']); ?>" target="_blank" class="flex items-center space-x-3 text-primary-600 hover:text-primary-700">
                                <i class="fa-brands fa-linkedin text-xl"></i>
                                <span class="text-sm font-medium">LinkedIn</span>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($doc['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($doc['twitter']); ?>" target="_blank" class="flex items-center space-x-3 text-[#1DA1F2] hover:opacity-80">
                                <i class="fa-brands fa-twitter text-xl"></i>
                                <span class="text-sm font-medium">Twitter</span>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($doc['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($doc['facebook']); ?>" target="_blank" class="flex items-center space-x-3 text-[#1877F2] hover:opacity-80">
                                <i class="fa-brands fa-facebook text-xl"></i>
                                <span class="text-sm font-medium">Facebook</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Addresses/Locations -->
                    <?php if (is_array($addresses) && count($addresses) > 0): ?>
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-dark-900 mb-4">
                            <i class="fa-solid fa-location-dot text-red-600 mr-2"></i>Practice Locations
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($addresses as $idx => $addr): ?>
                            <div class="p-4 bg-dark-50 rounded-xl">
                                <p class="font-semibold text-dark-900 mb-2"><?php echo isset($addr['name']) ? htmlspecialchars($addr['name']) : 'Location ' . ($idx + 1); ?></p>
                                <?php if (isset($addr['address'])): ?>
                                <p class="text-sm text-dark-700"><?php echo nl2br(htmlspecialchars($addr['address'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>

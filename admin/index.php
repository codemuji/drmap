<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
// Fetch doctors once for server-side rendering
require_once __DIR__ . '/inc/db.php';
$pdo = getPDO();

// Get all distinct specialties for the filter dropdown
$specialtiesStmt = $pdo->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
$allSpecialties = $specialtiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get filter values from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause based on filters
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(name LIKE :search OR specialty LIKE :search OR email LIKE :search OR qualification LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($specialty)) {
    $whereConditions[] = "specialty = :specialty";
    $params[':specialty'] = $specialty;
}

if (!empty($status)) {
    $whereConditions[] = "status = :status";
    $params[':status'] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Total count with filters
$countSql = "SELECT COUNT(*) FROM doctors $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$__totalDoctors = (int)$countStmt->fetchColumn();

// Fetch only current page rows with filters
$sql = "SELECT id, name, specialty, experience, qualification, rating, photo, phone, email, whatsapp, timing, about, status, social FROM doctors $whereClause ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$__doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($__doctors as & $__d) {
    if (!empty($__d['social'])) {
        $__s = json_decode($__d['social'], true);
        $__d['social'] = $__s ?: ['linkedin' => '#', 'twitter' => '#'];
    } else {
        $__d['social'] = ['linkedin' => '#', 'twitter' => '#'];
    }
    $__d['experience'] = isset($__d['experience']) ? (int)$__d['experience'] : 0;
    $__d['rating'] = isset($__d['rating']) ? (float)$__d['rating'] : 0.0;
}

// Stats computed from DB for accuracy
$avgStmt = $pdo->query("SELECT AVG(rating) FROM doctors");
$__avgRating = round((float)($avgStmt->fetchColumn() ?: 0), 1);
$specStmt = $pdo->query("SELECT COUNT(DISTINCT specialty) FROM doctors");
$__specialties = (int)$specStmt->fetchColumn();

$__totalPages = max(1, (int)ceil($__totalDoctors / $perPage));
$showing_start = $__totalDoctors ? ($offset + 1) : 0;
$showing_end = min($__totalDoctors, $offset + count($__doctors));

// Build query string for pagination links
$queryString = http_build_query(array_filter([
    'search' => $search,
    'specialty' => $specialty,
    'status' => $status
]));
$pageParam = !empty($queryString) ? '&' : '?';
?>
<?php include __DIR__ . '/inc/head.php'; ?>
<body class="mesh-gradient min-h-screen antialiased">

    <!-- Command Palette (K) -->
    <div id="commandPalette" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-dark-900/60 backdrop-blur-sm" onclick="closeCommandPalette()"></div>
        <div class="relative max-w-2xl mx-auto mt-[15vh]">
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-dark-200">
                <div class="flex items-center px-5 py-4 border-b border-dark-100">
                    <i class="fa-solid fa-magnifying-glass text-dark-400 mr-3"></i>
                    <input type="text" id="commandInput" placeholder="Search doctors, actions, or type a command..." 
                           class="flex-1 text-base outline-none placeholder:text-dark-400">
                    <kbd class="px-2 py-1 text-xs font-medium bg-dark-100 text-dark-500 rounded">ESC</kbd>
                </div>
                <div class="command-palette p-2">
                    <div class="px-3 py-2 text-xs font-semibold text-dark-400 uppercase tracking-wider">Quick Actions</div>
                    <button class="w-full flex items-center px-3 py-3 rounded-xl hover:bg-primary-50 group transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center mr-3 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="text-left">
                            <p class="font-semibold text-dark-800">Add New Doctor</p>
                            <p class="text-xs text-dark-500">Create a new doctor profile</p>
                        </div>
                        <kbd class="ml-auto px-2 py-1 text-xs font-medium bg-dark-100 text-dark-500 rounded">N</kbd>
                    </button>
                    <button class="w-full flex items-center px-3 py-3 rounded-xl hover:bg-primary-50 group transition-colors">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mr-3 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i class="fa-solid fa-file-export"></i>
                        </div>
                        <div class="text-left">
                            <p class="font-semibold text-dark-800">Export Data</p>
                            <p class="text-xs text-dark-500">Download doctor list as CSV</p>
                        </div>
                        <kbd class="ml-auto px-2 py-1 text-xs font-medium bg-dark-100 text-dark-500 rounded">E</kbd>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="flex min-h-screen">
        <?php include __DIR__ . '/inc/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-[280px]">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 glass border-b border-white/50">
                <div class="px-6 lg:px-8 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Mobile Menu Button -->
                        <button class="lg:hidden w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-dark-600">
                            <i class="fa-solid fa-bars"></i>
                        </button>

                        <!-- Breadcrumb -->
                        <div class="hidden md:flex items-center space-x-2 text-sm">
                            <a href="#" class="text-dark-500 hover:text-dark-700 transition-colors">Dashboard</a>
                            <i class="fa-solid fa-chevron-right text-dark-300 text-xs"></i>
                            <span class="text-dark-800 font-medium">Doctor Management</span>
                        </div>

                        <!-- Right Section -->
                        <div class="flex items-center space-x-3">
                            <!-- Search Button -->
                            <button onclick="openCommandPalette()" class="hidden md:flex items-center px-4 py-2 bg-white rounded-xl shadow-sm border border-dark-200 text-sm text-dark-500 hover:border-primary-300 transition-colors">
                                <i class="fa-solid fa-magnifying-glass mr-2"></i>
                                <span>Search</span>
                                <kbd class="ml-3 px-1.5 py-0.5 text-xs bg-dark-100 text-dark-400 rounded font-medium">⌘K</kbd>
                            </button>

                            <!-- Notifications -->
                            <button class="relative w-10 h-10 rounded-xl bg-white shadow-sm border border-dark-200 flex items-center justify-center text-dark-600 hover:border-primary-300 transition-colors">
                                <i class="fa-solid fa-bell"></i>
                                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center badge-pulse">3</span>
                            </button>

                            <!-- Divider -->
                            <div class="hidden md:block w-px h-8 bg-dark-200"></div>

                            <!-- User Menu -->
                            <div class="hidden md:flex items-center space-x-3 pl-2">
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-dark-800">Alex Johnson</p>
                                    <p class="text-xs text-dark-500">Super Admin</p>
                                </div>
                                <button class="relative">
                                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face" 
                                         class="w-10 h-10 rounded-xl object-cover ring-2 ring-white shadow-sm">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6 lg:p-8">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-dark-900 tracking-tight">Doctor Management</h1>
                        <p class="text-dark-500 mt-1">Manage and monitor your healthcare professionals</p>
                    </div>
                    <div class="flex items-center space-x-3 mt-4 md:mt-0">
                        <button class="px-4 py-2.5 bg-white border border-dark-200 rounded-xl text-sm font-medium text-dark-700 hover:border-dark-300 transition-colors flex items-center space-x-2">
                            <i class="fa-solid fa-file-export"></i>
                            <span>Export</span>
                        </button>
                        <a href="add.php" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center space-x-2 transition-all">
                            <i class="fa-solid fa-plus"></i>
                            <span>Add Doctor</span>
                        </a>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <!-- Total Doctors -->
                    <div class="card-premium rounded-2xl p-6 transition-all hover:scale-[1.02] cursor-pointer group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-dark-500">Total Doctors</p>
                                <p id="stat-total" class="text-3xl font-bold text-dark-900 mt-2 counter"><?php echo $__totalDoctors; ?></p>
                                <div class="flex items-center mt-3 space-x-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <i class="fa-solid fa-arrow-up mr-1 text-[10px]"></i>12%
                                    </span>
                                    <span class="text-xs text-dark-400">vs last month</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl stat-gradient-blue flex items-center justify-center shadow-lg shadow-primary-500/20 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-user-doctor text-white text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Average Rating -->
                    <div class="card-premium rounded-2xl p-6 transition-all hover:scale-[1.02] cursor-pointer group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-dark-500">Average Rating</p>
                                <p id="stat-rating" class="text-3xl font-bold text-dark-900 mt-2 counter"><?php echo $__avgRating; ?></p>
                                <div class="flex items-center mt-3 space-x-1">
                                    <div class="flex">
                                        <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                        <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                        <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                        <i class="fa-solid fa-star text-amber-400 text-xs"></i>
                                        <i class="fa-solid fa-star-half-stroke text-amber-400 text-xs"></i>
                                    </div>
                                    <span class="text-xs text-dark-400 ml-1">from 1.2k reviews</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl stat-gradient-amber flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-star text-white text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Specialties -->
                    <div class="card-premium rounded-2xl p-6 transition-all hover:scale-[1.02] cursor-pointer group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-dark-500">Specialties</p>
                                <p id="stat-specialty" class="text-3xl font-bold text-dark-900 mt-2 counter"><?php echo $__specialties; ?></p>
                                <div class="flex items-center mt-3 space-x-1">
                                    <span class="text-xs text-dark-400">Across all departments</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl stat-gradient-purple flex items-center justify-center shadow-lg shadow-purple-500/20 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-stethoscope text-white text-lg"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Active Today -->
                    <div class="card-premium rounded-2xl p-6 transition-all hover:scale-[1.02] cursor-pointer group">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-dark-500">Active Today</p>
                                <p class="text-3xl font-bold text-dark-900 mt-2 counter">18</p>
                                <div class="flex items-center mt-3 space-x-1">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <span class="text-xs text-dark-400 ml-1">Currently online</span>
                                </div>
                            </div>
                            <div class="w-12 h-12 rounded-2xl stat-gradient-emerald flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-circle-check text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                

                <!-- Filters & Search -->
                <div class="card-premium rounded-2xl p-5 mb-6">
                    <form method="GET" class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <!-- Search -->
                        <div class="flex-1 relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-dark-400"></i>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, specialty, or email..." 
                                   class="w-full pl-11 pr-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium placeholder:text-dark-400 transition-all">
                        </div>

                        <!-- Filters -->
                        <div class="flex items-center gap-3">
                            <select name="specialty" class="px-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium text-dark-700 min-w-[180px] transition-all">
                                <option value="">All Specialties</option>
                                <?php foreach ($allSpecialties as $spec): ?>
                                <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialty === $spec) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="px-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium text-dark-700 min-w-[140px] transition-all">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="on-leave" <?php echo ($status === 'on-leave') ? 'selected' : ''; ?>>On Leave</option>
                                <option value="inactive" <?php echo ($status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <button type="submit" class="px-4 py-3 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-colors flex items-center space-x-2">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Search</span>
                            </button>
                            <?php if (!empty($search) || !empty($specialty) || !empty($status)): ?>
                            <a href="index.php" class="px-4 py-3 bg-dark-50 text-dark-700 rounded-xl text-sm font-semibold hover:bg-dark-100 transition-colors">
                                <i class="fa-solid fa-xmark mr-1"></i>Clear
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Doctors Table -->
                <div class="card-premium rounded-2xl overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-6 py-4 border-b border-dark-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" class="checkbox-premium" id="selectAll">
                            <label for="selectAll" class="text-sm font-medium text-dark-600">Select All</label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-dark-500"><span id="showing-count" class="font-semibold text-dark-700">0</span> doctors</span>
                            <div class="w-px h-4 bg-dark-200"></div>
                            <button class="p-2 rounded-lg hover:bg-dark-50 text-dark-500 transition-colors">
                                <i class="fa-solid fa-arrow-down-wide-short"></i>
                            </button>
                            <button class="p-2 rounded-lg hover:bg-dark-50 text-dark-500 transition-colors">
                                <i class="fa-solid fa-grid-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-dark-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider w-10">
                                        <span class="sr-only">Select</span>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Doctor</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Specialty</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Experience</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Rating</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-dark-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-dark-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="doctors-list" class="divide-y divide-dark-100">
                                <?php if ($__totalDoctors === 0): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-2xl bg-dark-100 flex items-center justify-center mb-4">
                                                <i class="fa-solid fa-user-doctor text-dark-400 text-2xl"></i>
                                            </div>
                                            <h3 class="font-semibold text-dark-900 mb-1">No doctors found</h3>
                                            <p class="text-sm text-dark-500 mb-4">Try adjusting your search or filters</p>
                                            <button onclick="openModal()" class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold text-white transition-all">
                                                <i class="fa-solid fa-plus mr-2"></i>Add First Doctor
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($__doctors as $doc):
                                        $specialty = $doc['specialty'] ?? '';
                                        $status = $doc['status'] ?? 'active';
                                        $statusDot = ($status === 'on-leave') ? 'bg-amber-500' : (($status === 'inactive') ? 'bg-dark-400' : 'bg-emerald-500');
                                        $statusBg = ($status === 'on-leave') ? 'bg-amber-50' : (($status === 'inactive') ? 'bg-dark-100' : 'bg-emerald-50');
                                        $statusText = ($status === 'on-leave') ? 'text-amber-700' : (($status === 'inactive') ? 'text-dark-600' : 'text-emerald-700');
                                    ?>
                                    <tr class="table-row group">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" class="checkbox-premium">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-4">
                                                <div class="relative">
                                                    <img src="<?php echo htmlspecialchars($doc['photo'] ?: ''); ?>" 
                                                         alt="<?php echo htmlspecialchars($doc['name'] ?? 'Doctor'); ?>"
                                                         class="w-12 h-12 rounded-xl object-cover ring-2 ring-white shadow-sm"
                                                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo rawurlencode($doc['name'] ?? ''); ?>&background=0ea5e9&color=fff'">
                                                    <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 <?php echo $statusDot; ?> border-2 border-white rounded-full"></div>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-dark-900"><?php echo htmlspecialchars($doc['name'] ?? ''); ?></p>
                                                    <p class="text-xs text-dark-500 mt-0.5 max-w-[200px] truncate"><?php echo htmlspecialchars($doc['qualification'] ?? ''); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-dark-50 text-dark-700 border border-dark-200">
                                                <?php echo htmlspecialchars($specialty); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center">
                                                    <i class="fa-solid fa-award text-primary-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-dark-900 text-sm"><?php echo (int)($doc['experience'] ?? 0); ?> years</p>
                                                    <p class="text-xs text-dark-500">Experience</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <p class="text-sm font-medium text-dark-800 flex items-center">
                                                    <i class="fa-solid fa-phone text-primary-500 mr-2 text-xs"></i>
                                                    <?php echo htmlspecialchars($doc['phone'] ?? ''); ?>
                                                </p>
                                                <p class="text-xs text-dark-500 flex items-center">
                                                    <i class="fa-solid fa-envelope text-dark-400 mr-2 text-xs"></i>
                                                    <?php echo htmlspecialchars($doc['email'] ?? ''); ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <div class="flex items-center space-x-0.5">
                                                    <?php
                                                    $rating = isset($doc['rating']) ? (float)$doc['rating'] : 0;
                                                    for ($i=1;$i<=5;$i++){
                                                        if ($i <= floor($rating)) {
                                                            echo '<i class="fa-solid fa-star text-amber-400 text-xs"></i>';
                                                        } elseif ($i === ceil($rating) && $rating%1!==0) {
                                                            echo '<i class="fa-solid fa-star-half-stroke text-amber-400 text-xs"></i>';
                                                        } else {
                                                            echo '<i class="fa-regular fa-star text-dark-300 text-xs"></i>';
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <span class="font-bold text-dark-900 text-sm"><?php echo htmlspecialchars($rating); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $statusBg . ' ' . $statusText; ?>">
                                                <span class="w-1.5 h-1.5 <?php echo $statusDot; ?> rounded-full mr-1.5"></span>
                                                <?php echo ($status === 'on-leave') ? 'On Leave' : (($status === 'inactive') ? 'Inactive' : 'Active'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <a href="view.php?id=<?php echo (int)$doc['id']; ?>" class="action-btn bg-dark-50 text-dark-600 hover:bg-dark-100 tooltip" data-tooltip="View">
                                                    <i class="fa-solid fa-eye text-sm"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo (int)$doc['id']; ?>" class="action-btn bg-primary-50 text-primary-600 hover:bg-primary-100 tooltip" data-tooltip="Edit">
                                                    <i class="fa-solid fa-pen text-sm"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo (int)$doc['id']; ?>" class="action-btn bg-red-50 text-red-600 hover:bg-red-100 tooltip" data-tooltip="Delete">
                                                    <i class="fa-solid fa-trash text-sm"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-dark-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-dark-500">
                            Showing <span class="font-semibold text-dark-700"><?php echo $showing_start; ?></span> to <span class="font-semibold text-dark-700"><?php echo $showing_end; ?></span> of <span class="font-semibold text-dark-700" id="total-count"><?php echo $__totalDoctors; ?></span> results
                        </p>
                        <div class="flex items-center space-x-1">
                            <?php
                                $prevPage = max(1, $page - 1);
                                $nextPage = min($__totalPages, $page + 1);
                                $disabledPrev = ($page <= 1);
                                $disabledNext = ($page >= $__totalPages);
                                $paginationParams = http_build_query(array_filter(['search' => $search, 'specialty' => $specialty, 'status' => $status]));
                                $pageParamStr = !empty($paginationParams) ? $paginationParams . '&' : '';
                            ?>
                            <a href="?<?php echo $pageParamStr; ?>page=<?php echo $prevPage; ?>" class="w-9 h-9 rounded-lg border border-dark-200 flex items-center justify-center text-dark-500 hover:bg-dark-50 transition-colors <?php echo $disabledPrev ? 'opacity-50 pointer-events-none' : ''; ?>">
                                <i class="fa-solid fa-chevron-left text-sm"></i>
                            </a>

                            <?php
                                $start = max(1, $page - 2);
                                $end = min($__totalPages, $page + 2);

                                if ($start > 1) {
                                    echo '<a href="?' . $pageParamStr . 'page=1" class="w-9 h-9 rounded-lg border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 transition-colors">1</a>';
                                    if ($start > 2) echo '<span class="px-2 text-dark-400">...</span>';
                                }

                                for ($p = $start; $p <= $end; $p++) {
                                    $active = ($p === $page);
                                    $cls = $active ? 'w-9 h-9 rounded-lg bg-primary-600 text-white flex items-center justify-center font-semibold text-sm' : 'w-9 h-9 rounded-lg border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 transition-colors font-medium text-sm';
                                    echo '<a href="?' . $pageParamStr . 'page=' . $p . '" class="' . $cls . '">' . $p . '</a>';
                                }

                                if ($end < $__totalPages) {
                                    if ($end < $__totalPages - 1) echo '<span class="px-2 text-dark-400">...</span>';
                                    echo '<a href="?' . $pageParamStr . 'page=' . $__totalPages . '" class="w-9 h-9 rounded-lg border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 transition-colors font-medium text-sm">' . $__totalPages . '</a>';
                                }
                            ?>

                            <a href="?<?php echo $pageParamStr; ?>page=<?php echo $nextPage; ?>" class="w-9 h-9 rounded-lg border border-dark-200 flex items-center justify-center text-dark-500 hover:bg-dark-50 transition-colors <?php echo $disabledNext ? 'opacity-50 pointer-events-none' : ''; ?>">
                                <i class="fa-solid fa-chevron-right text-sm"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Doctor Modal -->
    <div id="doctorModal" class="modal-overlay fixed inset-0 bg-dark-900/70 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-3xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl">
            <!-- Modal Header -->
            <div class="sticky top-0 z-10 bg-gradient-to-r from-primary-600 to-primary-700 px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 id="modalTitle" class="text-2xl font-bold text-white">Add New Doctor</h2>
                        <p class="text-primary-100 text-sm mt-1">Fill in the details to add a new healthcare professional</p>
                    </div>
                    <button onclick="closeModal()" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto max-h-[calc(90vh-180px)]">
                <form id="doctorForm" class="p-8">
                    <input type="hidden" id="doctorId">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <!-- Section: Basic Info -->
                            <div>
                                <div class="flex items-center space-x-2 mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-primary-100 flex items-center justify-center">
                                        <i class="fa-solid fa-user text-primary-600 text-sm"></i>
                                    </div>
                                    <h3 class="font-semibold text-dark-900">Basic Information</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="relative">
                                        <input type="text" id="docName" required placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Full Name *</label>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="text" id="docSpecialty" required placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Specialty *</label>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="relative">
                                            <input type="number" id="docExp" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">Experience (Years)</label>
                                        </div>
                                        <div class="relative">
                                            <input type="number" step="0.1" max="5" id="docRating" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">Rating (0-5)</label>
                                        </div>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="text" id="docQualification" placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Qualification</label>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="url" id="docPhoto" placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Photo URL</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Section: Contact -->
                            <div>
                                <div class="flex items-center space-x-2 mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                        <i class="fa-solid fa-address-book text-emerald-600 text-sm"></i>
                                    </div>
                                    <h3 class="font-semibold text-dark-900">Contact Information</h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="relative">
                                            <input type="tel" id="docPhone" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">Phone Number</label>
                                        </div>
                                        <div class="relative">
                                            <input type="tel" id="docWhatsapp" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">WhatsApp</label>
                                        </div>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="email" id="docEmail" placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Email Address</label>
                                    </div>
                                    
                                    <div class="relative">
                                        <input type="text" id="docTiming" placeholder=" "
                                               class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                        <label class="floating-label">Working Hours</label>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="relative">
                                            <input type="url" id="docLinkedin" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">LinkedIn URL</label>
                                        </div>
                                        <div class="relative">
                                            <input type="url" id="docTwitter" placeholder=" "
                                                   class="floating-input peer w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                            <label class="floating-label">Twitter URL</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Width: About -->
                        <div class="lg:col-span-2">
                            <div class="flex items-center space-x-2 mb-5">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-medical text-purple-600 text-sm"></i>
                                </div>
                                <h3 class="font-semibold text-dark-900">Professional Profile</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="relative">
                                    <textarea id="docAbout" rows="4" placeholder="Write a brief professional bio..."
                                              class="w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium resize-none"></textarea>
                                </div>
                                <div class="relative">
                                    <input type="text" id="docSpeech" placeholder="Professional quote or motto..."
                                           class="w-full px-4 py-3.5 border-2 border-dark-200 rounded-xl input-premium text-sm font-medium">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="sticky bottom-0 bg-dark-50 px-8 py-5 border-t border-dark-200 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeModal()" 
                        class="px-6 py-2.5 border-2 border-dark-300 rounded-xl font-semibold text-dark-700 hover:bg-dark-100 transition-colors">
                    Cancel
                </button>
                <button type="submit" form="doctorForm"
                        class="btn-primary px-8 py-2.5 rounded-xl font-semibold text-white flex items-center space-x-2 transition-all">
                    <i class="fa-solid fa-check"></i>
                    <span>Save Doctor</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-6 right-6 z-[200] space-y-3"></div>

    <script>
        // Minimal client helpers for modal behavior (server renders table)
        const doctorModal = document.getElementById('doctorModal');
        const doctorForm = document.getElementById('doctorForm');
        const searchInput = document.getElementById('searchInput');
    </script>
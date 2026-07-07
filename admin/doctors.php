<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();

// Get all distinct specialties for the filter dropdown
$specialtiesStmt = $pdo->query("SELECT DISTINCT specialty FROM doctors WHERE specialty IS NOT NULL AND specialty != '' ORDER BY specialty ASC");
$allSpecialties = $specialtiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get all distinct practice cities for the filter dropdown
$citiesStmt = $pdo->query("SELECT DISTINCT practice_city FROM doctors WHERE practice_city IS NOT NULL AND practice_city != '' ORDER BY practice_city ASC");
$allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get filter values from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialty = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
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

if (!empty($city)) {
    $whereConditions[] = "practice_city = :city";
    $params[':city'] = $city;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Pagination
$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Total count with filters
$countSql = "SELECT COUNT(*) FROM doctors $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalDoctors = (int)$countStmt->fetchColumn();

// Fetch rows for page with filters
$sql = "SELECT id, name, specialty, practice_city, experience, qualification, rating, photo, phone, email, whatsapp, timing, about, status, can_edit, `rank` FROM doctors $whereClause ORDER BY IFNULL(`rank`, 999999) ASC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPages = max(1, (int)ceil($totalDoctors / $perPage));
$showing_start = $totalDoctors ? ($offset + 1) : 0;
$showing_end = min($totalDoctors, $offset + count($doctors));

// Build query string for pagination links
$queryString = http_build_query(array_filter([
    'search' => $search,
    'specialty' => $specialty,
    'city' => $city,
    'status' => $status
]));
$pageParam = !empty($queryString) ? '&' : '?';
?>
<?php include __DIR__ . '/inc/head.php'; ?>
<body class="mesh-gradient min-h-screen antialiased">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <main style="margin-left:280px;min-width:0;overflow-x:hidden;flex:1;">
        <!-- Top Header -->
        <header class="sticky top-0 z-40 glass border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <button class="lg:hidden w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-dark-600">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="hidden md:flex items-center space-x-2 text-sm">
                        <a href="index.php" class="text-dark-500 hover:text-dark-700 transition-colors">Dashboard</a>
                        <i class="fa-solid fa-chevron-right text-dark-300 text-xs"></i>
                        <span class="text-dark-800 font-medium">Doctor Management</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="openCommandPalette()" class="hidden md:flex items-center px-4 py-2 bg-white rounded-xl shadow-sm border border-dark-200 text-sm text-dark-500 hover:border-primary-300 transition-colors">
                            <i class="fa-solid fa-magnifying-glass mr-2"></i>
                            <span>Search</span>
                        </button>
                        <div class="hidden md:block w-px h-8 bg-dark-200"></div>
                        <div class="hidden md:flex items-center space-x-3 pl-2">
                            <div class="text-right">
                                <p class="text-sm font-semibold text-dark-800">Admin</p>
                                <p class="text-xs text-dark-500">Manager</p>
                            </div>
                            <button class="relative">
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face" class="w-10 h-10 rounded-xl object-cover ring-2 ring-white shadow-sm">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-dark-900 tracking-tight">Doctor Management</h1>
                    <p class="text-dark-500 mt-1">Manage and monitor your healthcare professionals</p>
                </div>
                <div class="flex items-center space-x-3 mt-4 md:mt-0">
                    <a href="add.php" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center space-x-2 transition-all">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Doctor</span>
                    </a>
                </div>
            </div>

           

            <!-- Filters & Search -->
            <div class="card-premium rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <div class="flex-1 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-dark-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, specialty, or email..." class="w-full pl-11 pr-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium placeholder:text-dark-400 transition-all">
                    </div>
                    <div class="flex items-center gap-3">
                        <select name="specialty" class="px-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium text-dark-700 min-w-[180px] transition-all">
                            <option value="">All Specialties</option>
                            <?php foreach ($allSpecialties as $spec): ?>
                            <option value="<?php echo htmlspecialchars($spec); ?>" <?php echo ($specialty === $spec) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($spec); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="city" class="px-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm font-medium text-dark-700 min-w-[180px] transition-all">
                            <option value="">All Cities</option>
                            <?php foreach ($allCities as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo ($city === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
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
                        <?php if (!empty($search) || !empty($specialty) || !empty($status) || !empty($city)): ?>
                        <a href="doctors.php" class="px-4 py-3 bg-dark-50 text-dark-700 rounded-xl text-sm font-semibold hover:bg-dark-100 transition-colors">
                            <i class="fa-solid fa-xmark mr-1"></i>Clear
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card-premium rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-dark-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <input type="checkbox" class="checkbox-premium" id="selectAll">
                        <label for="selectAll" class="text-sm font-medium text-dark-600">Select All</label>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-dark-500"><span id="showing-count" class="font-semibold text-dark-700"><?php echo $showing_start; ?></span> doctors</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left">Rank</th>
                            <th class="text-left">Doctor</th>
                            <th class="text-left">Specialty</th>
                            <th class="text-left">Location</th>
                            <th class="text-left">Experience</th>
                            <th class="text-left">Contact</th>
                            <th class="text-left">Rating</th>
                            <th class="text-left">Status</th>
                            <th class="text-center">Edit Access</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($doctors)): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-gray-600">No doctors found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($doctors as $doc):
                            $status = $doc['status'] ?? 'active';
                            $canEdit = isset($doc['can_edit']) ? (int)$doc['can_edit'] : 1;
                            $rank = $doc['rank'] ?? null;
                            ?>
                            <tr class="border-t">
                                <td class="px-4 py-3">
                                    <input type="number" 
                                           class="rank-input w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                           data-doctor-id="<?php echo (int)$doc['id']; ?>"
                                           value="<?php echo $rank !== null ? (int)$rank : ''; ?>"
                                           placeholder="-"
                                           min="0"
                                           title="Set rank to show this doctor at top. Lower numbers appear first.">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($doc['photo'] ?: ''); ?>" onerror="this.src='https://ui-avatars.com/api/?name=' + encodeURIComponent('<?php echo addslashes($doc['name'] ?: 'Doctor'); ?>')" class="w-10 h-10 rounded-md object-cover">
                                        <div>
                                            <div class="font-semibold truncate-ellipsis"><?php echo htmlspecialchars($doc['name']); ?></div>
                                            <div class="text-xs text-gray-500 truncate-ellipsis"><?php echo htmlspecialchars($doc['qualification']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-sm text-gray-700"><?php echo htmlspecialchars($doc['specialty']); ?></span></td>
                                <td><span class="text-sm text-gray-700"><?php echo htmlspecialchars($doc['practice_city'] ?? ''); ?></span></td>
                                <td><span class="text-sm text-gray-700"><?php echo (int)($doc['experience'] ?? 0); ?> yrs</span></td>
                                <td>
                                    <div class="text-sm text-gray-700">
                                        <div><?php echo htmlspecialchars($doc['phone']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($doc['email']); ?></div>
                                    </div>
                                </td>
                                <td><span class="text-sm font-semibold"><?php echo htmlspecialchars($doc['rating'] ?? '0'); ?></span></td>
                                <td><span class="text-sm text-gray-700"><?php echo ucfirst($status); ?></span></td>
                                <td class="text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer toggle-edit-access" 
                                               data-doctor-id="<?php echo (int)$doc['id']; ?>"
                                               <?php echo $canEdit ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="view.php?id=<?php echo (int)$doc['id']; ?>" class="action-btn bg-gray-100 text-gray-700" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a href="edit.php?id=<?php echo (int)$doc['id']; ?>" class="action-btn bg-blue-50 text-blue-600" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="delete_doctor.php" onsubmit="return confirm('Delete this doctor?');" style="display:inline-block;">
                                            <input type="hidden" name="id" value="<?php echo (int)$doc['id']; ?>">
                                            <button type="submit" class="action-btn bg-red-50 text-red-600" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div>Showing <?php echo $showing_start; ?> to <?php echo $showing_end; ?> of <?php echo $totalDoctors; ?> results</div>
                <div class="flex items-center gap-2">
                    <?php
                    $prev = max(1, $page-1);
                    $next = min($totalPages, $page+1);
                    $disabledPrev = $page <= 1;
                    $disabledNext = $page >= $totalPages;
                    $paginationParams = http_build_query(array_filter(['search' => $search, 'specialty' => $specialty, 'status' => $status]));
                    ?>
                    <a href="?<?php echo $paginationParams . ($paginationParams ? '&' : ''); ?>page=<?php echo $prev; ?>" class="px-3 py-1 border rounded <?php echo $disabledPrev? 'opacity-50 pointer-events-none':''; ?>">&lt;</a>
                    <?php for($p=1;$p<=$totalPages;$p++): ?>
                        <a href="?<?php echo $paginationParams . ($paginationParams ? '&' : ''); ?>page=<?php echo $p; ?>" class="px-3 py-1 border rounded <?php echo $p===$page? 'bg-blue-600 text-white':''; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                    <a href="?<?php echo $paginationParams . ($paginationParams ? '&' : ''); ?>page=<?php echo $next; ?>" class="px-3 py-1 border rounded <?php echo $disabledNext? 'opacity-50 pointer-events-none':''; ?>">&gt;</a>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Toggle edit access
document.querySelectorAll('.toggle-edit-access').forEach(toggle => {
    toggle.addEventListener('change', async function() {
        const doctorId = this.dataset.doctorId;
        const canEdit = this.checked ? 1 : 0;
        
        try {
            const response = await fetch('toggle_edit_access.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `doctor_id=${doctorId}&can_edit=${canEdit}`
            });
            
            const data = await response.json();
            
            if (!data.success) {
                alert('Failed to update edit access: ' + (data.error || 'Unknown error'));
                this.checked = !this.checked; // Revert toggle
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to update edit access');
            this.checked = !this.checked; // Revert toggle
        }
    });
});

// Update rank with debounce
let rankUpdateTimers = {};

document.querySelectorAll('.rank-input').forEach(input => {
    input.addEventListener('input', function() {
        const doctorId = this.dataset.doctorId;
        const rankValue = this.value.trim();
        
        // Clear previous timer for this doctor
        if (rankUpdateTimers[doctorId]) {
            clearTimeout(rankUpdateTimers[doctorId]);
        }
        
        // Set new timer (debounce 800ms)
        rankUpdateTimers[doctorId] = setTimeout(async () => {
            const originalValue = this.getAttribute('data-original-value') || this.defaultValue;
            
            try {
                const response = await fetch('update_doctor_rank.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `doctor_id=${doctorId}&rank=${rankValue}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store new value as original for future comparison
                    this.setAttribute('data-original-value', rankValue);
                    // Visual feedback
                    this.classList.add('border-green-500');
                    setTimeout(() => {
                        this.classList.remove('border-green-500');
                    }, 1000);
                } else {
                    alert('Failed to update rank: ' + (data.error || 'Unknown error'));
                    this.value = originalValue;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update rank');
                this.value = originalValue;
            }
        }, 800);
    });
    
    // Store original value on focus
    input.addEventListener('focus', function() {
        this.setAttribute('data-original-value', this.value);
    });
});
</script>
</body>
</html>

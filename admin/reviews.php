<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $reviewId = (int)($_POST['review_id'] ?? 0);
    
    if ($reviewId > 0) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare('UPDATE reviews SET status = ? WHERE id = ?');
            $stmt->execute(['approved', $reviewId]);
            $success = 'Review approved successfully!';
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare('UPDATE reviews SET status = ? WHERE id = ?');
            $stmt->execute(['rejected', $reviewId]);
            $success = 'Review rejected successfully!';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM reviews WHERE id = ?');
            $stmt->execute([$reviewId]);
            $success = 'Review deleted successfully!';
        }
        header('Location: reviews.php?success=1');
        exit;
    }
}

// Get filter values
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$whereConditions = [];
$params = [];

if ($status_filter !== 'all') {
    $whereConditions[] = 'r.status = :status';
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $whereConditions[] = '(r.customer_name LIKE :search OR r.customer_email LIKE :search OR d.name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Pagination
$perPage = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Get total count
$countSql = "SELECT COUNT(*) as total FROM reviews r JOIN doctors d ON r.doctor_id = d.id $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = max(1, ceil($total / $perPage));

// Get reviews
$sql = "SELECT r.*, d.name as doctor_name, d.specialty as doctor_specialty 
        FROM reviews r 
        JOIN doctors d ON r.doctor_id = d.id 
        $whereClause 
        ORDER BY r.created_at DESC 
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get stats
$statsStmt = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM reviews");
$stats = $statsStmt->fetch();
?>
<?php include __DIR__ . '/inc/head.php'; ?>
<body class="mesh-gradient min-h-screen antialiased">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <main style="margin-left:280px;min-width:0;overflow-x:hidden;flex:1;">
        <!-- Header -->
        <header class="glass sticky top-0 z-40 border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-dark-900">Reviews Management</h1>
                        <p class="text-sm text-dark-500 mt-1">Manage and approve customer reviews</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-xl">
                <i class="fa-solid fa-check-circle mr-2"></i>Action completed successfully!
            </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card-premium rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-dark-500 mb-1">Total Reviews</p>
                            <p class="text-3xl font-bold text-dark-900"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-star text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-premium rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-dark-500 mb-1">Pending</p>
                            <p class="text-3xl font-bold text-amber-600"><?php echo $stats['pending']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                            <i class="fas fa-clock text-amber-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-premium rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-dark-500 mb-1">Approved</p>
                            <p class="text-3xl font-bold text-emerald-600"><?php echo $stats['approved']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                            <i class="fas fa-check text-emerald-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-premium rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-dark-500 mb-1">Rejected</p>
                            <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected']; ?></p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <i class="fas fa-times text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-premium rounded-2xl p-5 mb-6">
                <form method="GET" class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <div class="flex-1 relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-dark-400"></i>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by customer name, email, or doctor..." class="w-full pl-11 pr-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm">
                    </div>
                    <select name="status" class="px-4 py-3 bg-dark-50 border border-dark-200 rounded-xl input-premium text-sm min-w-[180px]">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition">
                        <i class="fa-solid fa-filter mr-2"></i>Filter
                    </button>
                    <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    <a href="reviews.php" class="px-6 py-3 bg-dark-100 text-dark-700 rounded-xl font-semibold hover:bg-dark-200 transition">
                        <i class="fa-solid fa-xmark mr-2"></i>Clear
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Reviews Table -->
            <div class="card-premium rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-dark-50 border-b border-dark-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Customer</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Doctor</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Rating</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Review</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Date</th>
                                <th class="px-6 py-4 text-right text-sm font-semibold text-dark-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-200">
                            <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-dark-500">No reviews found</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                            <tr class="hover:bg-dark-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-dark-900"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                        <p class="text-xs text-dark-500"><?php echo htmlspecialchars($review['customer_email']); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-dark-900"><?php echo htmlspecialchars($review['doctor_name']); ?></p>
                                    <p class="text-xs text-dark-500"><?php echo htmlspecialchars($review['doctor_specialty']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star text-sm <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                        <span class="ml-2 text-sm font-semibold text-dark-700"><?php echo $review['rating']; ?>/5</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 max-w-md">
                                    <p class="text-sm text-dark-700 line-clamp-2"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $statusColors = [
                                        'pending' => 'amber',
                                        'approved' => 'emerald',
                                        'rejected' => 'red'
                                    ];
                                    $color = $statusColors[$review['status']];
                                    ?>
                                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700">
                                        <?php echo ucfirst($review['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-dark-600">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($review['status'] !== 'approved'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($review['status'] !== 'rejected'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this review permanently?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-dark-200 flex items-center justify-between">
                    <p class="text-sm text-dark-600">
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?> reviews
                    </p>
                    <div class="flex gap-2">
                        <?php
                        $queryParams = http_build_query(array_filter(['search' => $search, 'status' => $status_filter !== 'all' ? $status_filter : null]));
                        for ($p = 1; $p <= $totalPages; $p++): 
                        ?>
                        <a href="?<?php echo $queryParams . ($queryParams ? '&' : ''); ?>page=<?php echo $p; ?>" 
                           class="px-3 py-1 border rounded <?php echo $p === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-dark-200 text-dark-700 hover:bg-dark-50'; ?>">
                            <?php echo $p; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>

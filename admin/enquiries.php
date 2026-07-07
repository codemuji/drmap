<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $enquiry_id = (int)($_POST['enquiry_id'] ?? 0);
    
    if ($action === 'update_doctor_status' && $enquiry_id > 0) {
        // Update doctor's status (for reference only, not editable by admin)
        $new_status = $_POST['status'] ?? 'new';
        if (in_array($new_status, ['new', 'contacted', 'completed', 'closed'])) {
            $stmt = $pdo->prepare('UPDATE enquiries SET status = ? WHERE id = ?');
            $stmt->execute([$new_status, $enquiry_id]);
            header('Location: enquiries.php?success=1');
            exit;
        }
    } elseif ($action === 'update_admin_status' && $enquiry_id > 0) {
        // Update admin's tracking status
        $new_status = $_POST['admin_status'] ?? 'new';
        if (in_array($new_status, ['new', 'contacted', 'completed', 'closed'])) {
            $stmt = $pdo->prepare('UPDATE enquiries SET admin_status = ? WHERE id = ?');
            $stmt->execute([$new_status, $enquiry_id]);
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($action === 'delete' && $enquiry_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM enquiries WHERE id = ?');
        $stmt->execute([$enquiry_id]);
        header('Location: enquiries.php?deleted=1');
        exit;
    }
}

// Get filter and search parameters
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];

if (!empty($filter_status)) {
    $where[] = 'e.status = ?';
    $params[] = $filter_status;
}

if (!empty($search)) {
    $where[] = '(e.name LIKE ? OR e.email LIKE ? OR e.phone LIKE ? OR d.name LIKE ?)';
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM enquiries e
    JOIN doctors d ON e.doctor_id = d.id
    {$where_clause}
");
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get enquiries
$stmt = $pdo->prepare("
    SELECT e.*, d.name as doctor_name, d.specialty as doctor_specialty, 
           e.call_count, e.last_call_time, e.call_log
    FROM enquiries e
    JOIN doctors d ON e.doctor_id = d.id
    {$where_clause}
    ORDER BY e.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

// Ensure admin_status exists for all enquiries (set default if NULL)
foreach ($enquiries as $key => $enquiry) {
    if (!isset($enquiry['admin_status']) || $enquiry['admin_status'] === null) {
        $enquiries[$key]['admin_status'] = 'new';
    }
}

// Status colors
$status_colors = [
    'new' => ['label' => 'New', 'color' => 'blue', 'icon' => 'fa-envelope'],
    'contacted' => ['label' => 'Contacted', 'color' => 'amber', 'icon' => 'fa-phone'],
    'completed' => ['label' => 'Completed', 'color' => 'emerald', 'icon' => 'fa-check-circle'],
    'closed' => ['label' => 'Closed', 'color' => 'slate', 'icon' => 'fa-circle-xmark']
];
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
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="w-10 h-10 rounded-xl bg-white shadow-sm border border-dark-200 flex items-center justify-center text-dark-600 hover:bg-dark-50 hover:border-dark-300 transition-all">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-xl font-bold text-dark-900">Patient Enquiries</h1>
                            <p class="text-xs text-dark-500">Manage appointment enquiries and inquiries</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <!-- Success Messages -->
            <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start space-x-3">
                <i class="fa-solid fa-check-circle text-emerald-600 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="font-semibold text-emerald-900">Success!</h3>
                    <p class="text-sm text-emerald-700">Enquiry status updated successfully.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start space-x-3">
                <i class="fa-solid fa-check-circle text-emerald-600 flex-shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="font-semibold text-emerald-900">Deleted!</h3>
                    <p class="text-sm text-emerald-700">Enquiry has been deleted.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filters and Search -->
            <div class="card-premium rounded-2xl p-6 mb-8">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-semibold text-dark-800 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, email, phone, or doctor..." 
                                   class="w-full px-4 py-2.5 border-2 border-dark-200 rounded-xl focus:border-primary-600 focus:outline-none">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-dark-800 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2.5 border-2 border-dark-200 rounded-xl focus:border-primary-600 focus:outline-none">
                                <option value="">All Statuses</option>
                                <option value="new" <?php echo $filter_status === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="contacted" <?php echo $filter_status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="closed" <?php echo $filter_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>

                        <!-- Submit -->
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2.5 btn-primary rounded-xl text-sm font-medium text-white">
                                <i class="fa-solid fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <?php
                $stats = [];
                foreach (['new', 'contacted', 'completed', 'closed'] as $s) {
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enquiries WHERE status = ?");
                    $count_stmt->execute([$s]);
                    $stats[$s] = $count_stmt->fetch()['total'];
                }
                ?>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-dark-500 mb-1">New Enquiries</p>
                    <p class="text-2xl font-bold text-dark-900"><?php echo $stats['new']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-dark-500 mb-1">Contacted</p>
                    <p class="text-2xl font-bold text-dark-900"><?php echo $stats['contacted']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-dark-500 mb-1">Completed</p>
                    <p class="text-2xl font-bold text-dark-900"><?php echo $stats['completed']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-dark-500 mb-1">Closed</p>
                    <p class="text-2xl font-bold text-dark-900"><?php echo $stats['closed']; ?></p>
                </div>
            </div>

            <!-- Enquiries Table -->
            <div class="card-premium rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-dark-50 border-b border-dark-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">ID</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Patient</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Doctor</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Contact</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Doctor Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Admin Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Call Activity</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Submitted</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-dark-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-200">
                            <?php if (count($enquiries) > 0): ?>
                                <?php foreach ($enquiries as $enquiry): ?>
                                <?php 
                                $has_calls = ($enquiry['call_count'] ?? 0) > 0;
                                $row_class = $has_calls ? 'bg-emerald-50/30 border-l-4 border-emerald-500' : '';
                                ?>
                                <tr class="table-row hover:bg-dark-50 transition-colors <?php echo $row_class; ?>">
                                    <td class="px-6 py-4 text-sm font-semibold text-dark-900">#<?php echo $enquiry['id']; ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="space-y-1">
                                            <p class="font-semibold text-dark-900"><?php echo htmlspecialchars($enquiry['name']); ?></p>
                                            <p class="text-xs text-dark-500"><?php echo htmlspecialchars($enquiry['email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="view.php?id=<?php echo $enquiry['doctor_id']; ?>" class="text-primary-600 hover:text-primary-700 font-medium">
                                            <?php echo htmlspecialchars($enquiry['doctor_name']); ?>
                                        </a>
                                        <p class="text-xs text-dark-500"><?php echo htmlspecialchars($enquiry['doctor_specialty']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-dark-600">
                                        <i class="fa-solid fa-phone mr-2 text-dark-400"></i><?php echo htmlspecialchars($enquiry['phone']); ?>
                                    </td>
                                    <!-- Doctor Status (Read-only) -->
                                    <td class="px-6 py-4 text-sm">
                                        <?php 
                                        $status = $enquiry['status'];
                                        $color = $status_colors[$status]['color'];
                                        $label = $status_colors[$status]['label'];
                                        $icon = $status_colors[$status]['icon'];
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-700 border border-<?php echo $color; ?>-100">
                                            <i class="fa-solid <?php echo $icon; ?> mr-2"></i><?php echo $label; ?>
                                        </span>
                                    </td>
                                    <!-- Admin Status (Editable) -->
                                    <td class="px-6 py-4 text-sm">
                                        <?php 
                                        $admin_status = $enquiry['admin_status'] ?? 'new';
                                        $color = $status_colors[$admin_status]['color'];
                                        $label = $status_colors[$admin_status]['label'];
                                        $icon = $status_colors[$admin_status]['icon'];
                                        ?>
                                        <select class="admin-status-select px-3 py-1.5 rounded-lg text-xs font-semibold bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-700 border border-<?php echo $color; ?>-100 cursor-pointer hover:opacity-80 transition-opacity"
                                                data-enquiry-id="<?php echo $enquiry['id']; ?>"
                                                onchange="updateAdminStatus(this)">
                                            <option value="new" <?php echo $admin_status === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="contacted" <?php echo $admin_status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                            <option value="completed" <?php echo $admin_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="closed" <?php echo $admin_status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </td>
                                    <!-- Call Activity -->
                                    <td class="px-6 py-4 text-sm">
                                        <?php 
                                        $call_count = $enquiry['call_count'] ?? 0;
                                        $last_call = $enquiry['last_call_time'] ?? null;
                                        if ($call_count > 0): 
                                        ?>
                                        <div class="space-y-1">
                                            <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700">
                                                <i class="fa-solid fa-phone-volume text-xs mr-1.5"></i>
                                                <span class="text-xs font-semibold"><?php echo $call_count; ?> call<?php echo $call_count > 1 ? 's' : ''; ?></span>
                                            </div>
                                            <?php if ($last_call): ?>
                                            <p class="text-xs text-dark-500">
                                                <i class="fa-solid fa-clock mr-1"></i>
                                                <?php 
                                                $time_ago = time() - strtotime($last_call);
                                                if ($time_ago < 60) echo 'Just now';
                                                elseif ($time_ago < 3600) echo floor($time_ago / 60) . 'm ago';
                                                elseif ($time_ago < 86400) echo floor($time_ago / 3600) . 'h ago';
                                                else echo date('M d', strtotime($last_call));
                                                ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-xs text-dark-400 italic">No calls yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-dark-600">
                                        <?php echo date('M d, Y', strtotime($enquiry['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="openEnquiryModal(<?php echo htmlspecialchars(json_encode($enquiry)); ?>)" 
                                                    class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 flex items-center justify-center transition-colors tooltip" data-tooltip="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <button onclick="deleteEnquiry(<?php echo $enquiry['id']; ?>)" 
                                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors tooltip" data-tooltip="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-dark-500">
                                        <i class="fa-solid fa-inbox text-4xl text-dark-300 mb-3 block"></i>
                                        <p class="font-medium">No enquiries found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-dark-200 flex items-center justify-between">
                    <p class="text-sm text-dark-600">Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> enquiries</p>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="px-3 py-2 border border-dark-200 rounded-lg text-sm font-medium text-dark-600 hover:bg-dark-50">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-medium <?php echo $i === $page ? 'bg-primary-600 text-white' : 'border border-dark-200 text-dark-600 hover:bg-dark-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="px-3 py-2 border border-dark-200 rounded-lg text-sm font-medium text-dark-600 hover:bg-dark-50">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Enquiry Modal -->
<div id="enquiryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Enquiry Details</h2>
            <button onclick="closeEnquiryModal()" class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center text-white hover:bg-white/30">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4" id="enquiryContent">
            <!-- Content inserted by JS -->
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
        <div class="p-6 text-center">
            <i class="fa-solid fa-exclamation-circle text-4xl text-red-500 mb-3 block"></i>
            <h2 class="text-lg font-bold text-dark-900 mb-2">Delete Enquiry?</h2>
            <p class="text-dark-600 mb-6">This action cannot be undone. The enquiry will be permanently deleted.</p>
            <div class="flex space-x-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 border border-dark-200 rounded-lg text-dark-700 font-medium hover:bg-dark-50">Cancel</button>
                <form method="POST" class="flex-1">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="deleteEnquiryId" name="enquiry_id" value="">
                    <button type="submit" class="w-full px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEnquiryModal(enquiry) {
    document.getElementById('enquiryModal').classList.remove('hidden');
    const content = document.getElementById('enquiryContent');
    
    const statusColors = {
        'new': { label: 'New', color: 'blue' },
        'contacted': { label: 'Contacted', color: 'amber' },
        'completed': { label: 'Completed', color: 'emerald' },
        'closed': { label: 'Closed', color: 'slate' }
    };
    
    const status = statusColors[enquiry.status];
    const createdDate = new Date(enquiry.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    content.innerHTML = `
        <div class="space-y-4">
            <div>
                <p class="text-xs text-dark-500 font-semibold uppercase">Patient Name</p>
                <p class="text-dark-900 font-medium">${escapeHtml(enquiry.name)}</p>
            </div>
            <div>
                <p class="text-xs text-dark-500 font-semibold uppercase">Email</p>
                <a href="mailto:${escapeHtml(enquiry.email)}" class="text-primary-600 hover:underline">${escapeHtml(enquiry.email)}</a>
            </div>
            <div>
                <p class="text-xs text-dark-500 font-semibold uppercase">Phone</p>
                <a href="tel:${escapeHtml(enquiry.phone)}" class="text-primary-600 hover:underline">${escapeHtml(enquiry.phone)}</a>
            </div>
            <div>
                <p class="text-xs text-dark-500 font-semibold uppercase">Status</p>
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-${status.color}-50 text-${status.color}-700 border border-${status.color}-100">
                    ${status.label}
                </span>
            </div>
            <div>
                <p class="text-xs text-dark-500 font-semibold uppercase">Submitted</p>
                <p class="text-dark-900">${createdDate}</p>
            </div>
            ${enquiry.call_count > 0 ? `
            <div class="pt-4 border-t border-dark-200">
                <p class="text-xs text-dark-500 font-semibold uppercase mb-2">Call Activity</p>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-semibold text-emerald-900">
                            <i class="fa-solid fa-phone-volume mr-2"></i>${enquiry.call_count} call${enquiry.call_count > 1 ? 's' : ''} made
                        </span>
                    </div>
                    ${enquiry.last_call_time ? `
                    <p class="text-xs text-emerald-700">
                        Last call: ${new Date(enquiry.last_call_time).toLocaleString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})}
                    </p>
                    ` : ''}
                </div>
            </div>
            ` : ''}
            ${enquiry.message ? `
            <div class="pt-4 border-t border-dark-200">
                <p class="text-xs text-dark-500 font-semibold uppercase mb-2">Message</p>
                <p class="text-dark-700 text-sm leading-relaxed">${escapeHtml(enquiry.message)}</p>
            </div>
            ` : ''}
        </div>
    `;
}

function closeEnquiryModal() {
    document.getElementById('enquiryModal').classList.add('hidden');
}

function deleteEnquiry(enquiryId) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteEnquiryId').value = enquiryId;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Update admin status via AJAX
function updateAdminStatus(selectElement) {
    const enquiryId = selectElement.getAttribute('data-enquiry-id');
    const newStatus = selectElement.value;
    
    // Show loading indicator
    const originalHtml = selectElement.outerHTML;
    selectElement.disabled = true;
    selectElement.style.opacity = '0.6';
    
    // Send AJAX request
    fetch('enquiries.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_admin_status&enquiry_id=${enquiryId}&admin_status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the select styling based on new status
            const colorMap = {
                'new': 'blue',
                'contacted': 'amber',
                'completed': 'emerald',
                'closed': 'slate'
            };
            const color = colorMap[newStatus];
            selectElement.className = `admin-status-select px-3 py-1.5 rounded-lg text-xs font-semibold bg-${color}-50 text-${color}-700 border border-${color}-100 cursor-pointer hover:opacity-80 transition-opacity`;
            selectElement.disabled = false;
            selectElement.style.opacity = '1';
            
            // Show success message
            showToast('Admin status updated successfully', 'success');
        } else {
            // Revert on error
            selectElement.disabled = false;
            selectElement.style.opacity = '1';
            showToast('Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.disabled = false;
        selectElement.style.opacity = '1';
        showToast('Failed to update status', 'error');
    });
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all ${
        type === 'success' ? 'bg-emerald-600' : 'bg-red-600'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Close modals on backdrop click
document.getElementById('enquiryModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeEnquiryModal();
});

document.getElementById('deleteModal')?.addEventListener('click', (e) => {
    if (e.target === e.currentTarget) closeDeleteModal();
});
</script>

</body>
</html>

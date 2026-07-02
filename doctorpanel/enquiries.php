<?php
require_once __DIR__ . '/inc/auth.php';
require_doctor_login();

$pdo = getPDO();
$doctor_id = current_doctor()['id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $enquiry_id = (int)($_POST['enquiry_id'] ?? 0);
    $is_ajax = !empty($_POST['ajax']);
    
    if ($action === 'log_call' && $enquiry_id > 0) {
        // Log the call attempt
        $stmt = $pdo->prepare('SELECT call_count, call_log FROM enquiries WHERE id = ? AND doctor_id = ?');
        $stmt->execute([$enquiry_id, $doctor_id]);
        $enquiry_data = $stmt->fetch();
        
        if ($enquiry_data) {
            $call_count = ($enquiry_data['call_count'] ?? 0) + 1;
            $call_log = json_decode($enquiry_data['call_log'] ?? '[]', true);
            if (!is_array($call_log)) $call_log = [];
            
            $call_log[] = [
                'time' => date('Y-m-d H:i:s'),
                'doctor_id' => $doctor_id
            ];
            
            // Update call data and automatically change status to 'contacted'
            $stmt = $pdo->prepare('UPDATE enquiries SET call_count = ?, call_log = ?, last_call_time = NOW(), status = ? WHERE id = ? AND doctor_id = ?');
            $stmt->execute([$call_count, json_encode($call_log), 'contacted', $enquiry_id, $doctor_id]);
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'call_count' => $call_count, 'last_call_time' => date('Y-m-d H:i:s'), 'status' => 'contacted']);
                exit;
            }
        }
    } elseif ($action === 'update_status' && $enquiry_id > 0) {
        $new_status = $_POST['status'] ?? 'new';
        if (in_array($new_status, ['new', 'contacted', 'completed', 'closed'])) {
            $stmt = $pdo->prepare('UPDATE enquiries SET status = ? WHERE id = ? AND doctor_id = ?');
            $stmt->execute([$new_status, $enquiry_id, $doctor_id]);
            
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'status' => $new_status]);
                exit;
            }
            header('Location: enquiries.php?success=1');
            exit;
        }
    } elseif ($action === 'delete' && $enquiry_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM enquiries WHERE id = ? AND doctor_id = ?');
        $stmt->execute([$enquiry_id, $doctor_id]);
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'deleted' => true]);
            exit;
        }
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
$where = ['e.doctor_id = ?'];
$params = [$doctor_id];

if (!empty($filter_status)) {
    $where[] = 'e.status = ?';
    $params[] = $filter_status;
}

if (!empty($search)) {
    $where[] = '(e.name LIKE ? OR e.email LIKE ? OR e.phone LIKE ?)';
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM enquiries e
    {$where_clause}
");
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get enquiries
$stmt = $pdo->prepare("
    SELECT e.*, e.call_count, e.last_call_time, e.call_log
    FROM enquiries e
    {$where_clause}
    ORDER BY e.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$enquiries = $stmt->fetchAll();

// Status colors
$status_colors = [
    'new' => ['label' => 'New', 'color' => 'blue', 'icon' => 'fa-envelope'],
    'contacted' => ['label' => 'Contacted', 'color' => 'amber', 'icon' => 'fa-phone'],
    'completed' => ['label' => 'Completed', 'color' => 'emerald', 'icon' => 'fa-check-circle'],
    'closed' => ['label' => 'Closed', 'color' => 'slate', 'icon' => 'fa-circle-xmark']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Enquiries - Doctor Panel</title>
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

        .table-row:hover { background: rgba(14, 165, 233, 0.02); }
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
            <a href="view.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-eye w-5 mr-3"></i>
                <span>View Profile</span>
            </a>
            <a href="edit.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-slate-400 hover:text-white hover:bg-white/5">
                <i class="fa-solid fa-pen w-5 mr-3"></i>
                <span>Edit Profile</span>
            </a>
            <a href="enquiries.php" class="flex items-center px-4 py-3 rounded-xl font-medium text-white bg-primary-600">
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
                            <h1 class="text-xl font-bold text-slate-900">Patient Enquiries</h1>
                            <p class="text-xs text-slate-500">Manage your appointment requests and inquiries</p>
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
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by name, email, or phone..." 
                                   class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:border-primary-600 focus:outline-none">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:border-primary-600 focus:outline-none">
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
                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM enquiries WHERE status = ? AND doctor_id = ?");
                    $count_stmt->execute([$s, $doctor_id]);
                    $stats[$s] = $count_stmt->fetch()['total'];
                }
                ?>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500 mb-1">New Enquiries</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['new']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500 mb-1">Contacted</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['contacted']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500 mb-1">Completed</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['completed']; ?></p>
                </div>
                <div class="card-premium rounded-2xl p-6 shadow-sm">
                    <p class="text-sm text-slate-500 mb-1">Closed</p>
                    <p class="text-2xl font-bold text-slate-900"><?php echo $stats['closed']; ?></p>
                </div>
            </div>

            <!-- Enquiries Table -->
            <div class="card-premium rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">ID</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Patient</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Contact</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Status</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Submitted</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php if (count($enquiries) > 0): ?>
                                <?php foreach ($enquiries as $enquiry): ?>
                                <?php 
                                $status_bg = [
                                    'new' => 'bg-blue-50 border-l-4 border-blue-600',
                                    'contacted' => 'bg-amber-50 border-l-4 border-amber-600',
                                    'completed' => 'bg-emerald-50 border-l-4 border-emerald-600',
                                    'closed' => 'bg-slate-50 border-l-4 border-slate-600'
                                ];
                                ?>
                                <tr class="table-row transition-colors <?php echo $status_bg[$enquiry['status']]; ?>" data-enquiry-id="<?php echo $enquiry['id']; ?>" data-status="<?php echo $enquiry['status']; ?>">
                                    <td class="px-6 py-4 text-sm font-semibold text-slate-900">#<?php echo $enquiry['id']; ?></td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="space-y-1">
                                            <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($enquiry['name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($enquiry['email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <i class="fa-solid fa-phone mr-2 text-slate-400"></i><?php echo htmlspecialchars($enquiry['phone']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php 
                                        $status = $enquiry['status'];
                                        $color = $status_colors[$status]['color'];
                                        $label = $status_colors[$status]['label'];
                                        $icon = $status_colors[$status]['icon'];
                                        ?>
                                        <select class="status-select px-3 py-1.5 rounded-lg text-xs font-semibold bg-<?php echo $color; ?>-50 text-<?php echo $color; ?>-700 border border-<?php echo $color; ?>-100 cursor-pointer hover:opacity-80 transition-opacity"
                                                data-enquiry-id="<?php echo $enquiry['id']; ?>"
                                                onchange="updateStatus(this)">
                                            <option value="new" <?php echo $status === 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="contacted" <?php echo $status === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?php echo date('M d, Y', strtotime($enquiry['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex items-center space-x-2">
                                            <a href="tel:<?php echo htmlspecialchars($enquiry['phone']); ?>" 
                                               onclick="logCall(<?php echo $enquiry['id']; ?>, '<?php echo htmlspecialchars($enquiry['phone']); ?>'); return true;"
                                               class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition-colors" 
                                               title="Call <?php echo htmlspecialchars($enquiry['phone']); ?>">
                                                <i class="fa-solid fa-phone"></i>
                                            </a>
                                            
                                            <button onclick="openEnquiryModal(<?php echo htmlspecialchars(json_encode($enquiry)); ?>)" 
                                                    class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 flex items-center justify-center transition-colors" title="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>

                                            <button onclick="deleteEnquiry(<?php echo $enquiry['id']; ?>)" 
                                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-3 block"></i>
                                        <p class="font-medium">No enquiries found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
                    <p class="text-sm text-slate-600">Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $per_page, $total); ?> of <?php echo $total; ?> enquiries</p>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-medium <?php echo $i === $page ? 'bg-primary-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo htmlspecialchars($filter_status); ?>&search=<?php echo htmlspecialchars($search); ?>" 
                           class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50">Next</a>
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
            <h2 class="text-lg font-bold text-slate-900 mb-2">Delete Enquiry?</h2>
            <p class="text-slate-600 mb-6">This action cannot be undone. The enquiry will be permanently deleted.</p>
            <div class="flex space-x-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 border border-slate-200 rounded-lg text-slate-700 font-medium hover:bg-slate-50">Cancel</button>
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
                <p class="text-xs text-slate-500 font-semibold uppercase">Patient Name</p>
                <p class="text-slate-900 font-medium">${escapeHtml(enquiry.name)}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase">Email</p>
                <a href="mailto:${escapeHtml(enquiry.email)}" class="text-primary-600 hover:underline">${escapeHtml(enquiry.email)}</a>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase">Phone</p>
                <a href="tel:${escapeHtml(enquiry.phone)}" class="text-primary-600 hover:underline">${escapeHtml(enquiry.phone)}</a>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase">Status</p>
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-${status.color}-50 text-${status.color}-700 border border-${status.color}-100">
                    ${status.label}
                </span>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-semibold uppercase">Submitted</p>
                <p class="text-slate-900">${createdDate}</p>
            </div>
            ${enquiry.message ? `
            <div class="pt-4 border-t border-slate-200">
                <p class="text-xs text-slate-500 font-semibold uppercase mb-2">Message</p>
                <p class="text-slate-700 text-sm leading-relaxed">${escapeHtml(enquiry.message)}</p>
            </div>
            ` : ''}
        </div>
    `;
}

function closeEnquiryModal() {
    document.getElementById('enquiryModal').classList.add('hidden');
}

function logCall(enquiryId, phoneNumber) {
    // Log the call attempt in the background
    fetch('enquiries.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=log_call&enquiry_id=${enquiryId}&ajax=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Call logged - Status changed to Contacted`, 'success');
            
            // Update status dropdown and row styling
            const row = document.querySelector(`tr[data-enquiry-id="${enquiryId}"]`);
            if (row) {
                // Update status dropdown
                const statusSelect = row.querySelector('.status-select');
                if (statusSelect && statusSelect.value !== 'contacted') {
                    statusSelect.value = 'contacted';
                    statusSelect.className = 'status-select px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100 cursor-pointer hover:opacity-80 transition-opacity';
                }
                
                // Update row background
                row.className = row.className.split(' ').filter(cls => !cls.startsWith('bg-') && !cls.startsWith('border-')).join(' ');
                row.className += ' table-row transition-colors bg-amber-50 border-l-4 border-amber-600';
                row.setAttribute('data-status', 'contacted');
            }
        }
    })
    .catch(error => {
        console.error('Error logging call:', error);
    });
}

function updateStatus(selectElement) {
    const enquiryId = selectElement.getAttribute('data-enquiry-id');
    const newStatus = selectElement.value;
    
    // Show loading indicator
    selectElement.disabled = true;
    selectElement.style.opacity = '0.6';
    
    // Send AJAX request
    fetch('enquiries.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&enquiry_id=${enquiryId}&status=${newStatus}&ajax=1`
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
            selectElement.className = `status-select px-3 py-1.5 rounded-lg text-xs font-semibold bg-${color}-50 text-${color}-700 border border-${color}-100 cursor-pointer hover:opacity-80 transition-opacity`;
            selectElement.disabled = false;
            selectElement.style.opacity = '1';
            
            // Update row background
            const row = selectElement.closest('tr');
            if (row) {
                // Remove all old bg and border classes
                row.className = row.className.split(' ').filter(cls => !cls.startsWith('bg-') && !cls.startsWith('border-')).join(' ');
                
                // Add new status classes
                const bgColorMap = {
                    'new': 'bg-blue-50 border-l-4 border-blue-600',
                    'contacted': 'bg-amber-50 border-l-4 border-amber-600',
                    'completed': 'bg-emerald-50 border-l-4 border-emerald-600',
                    'closed': 'bg-slate-50 border-l-4 border-slate-600'
                };
                row.className += ' ' + bgColorMap[newStatus];
                
                // Update data attribute
                row.setAttribute('data-status', newStatus);
            }
            
            // Show success message
            showToast('Status updated successfully', 'success');
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
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all z-50 ${
        type === 'success' ? 'bg-emerald-600' : 'bg-red-600'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
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

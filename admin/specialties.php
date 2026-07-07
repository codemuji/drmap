<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();
$error = '';
$success = '';

// Helper to check if icon is image file or FontAwesome class
function isImageIcon($iconStr) {
    if (empty($iconStr)) return false;
    return str_contains($iconStr, '/') || str_contains($iconStr, '.') || preg_match('/\.(png|jpg|jpeg|svg|webp|gif)$/i', $iconStr);
}

// Handle Add Specialty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-user-doctor');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Check if custom image icon was uploaded
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['icon_file']['tmp_name'];
        $fileName = $_FILES['icon_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . '/../uploads/specialties/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'spec_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                $icon = 'uploads/specialties/' . $newFileName;
            }
        } else {
            $error = 'Invalid image type. Allowed: PNG, SVG, JPG, WEBP, GIF.';
        }
    }

    if (empty($name)) {
        $error = 'Specialty name is required';
    } elseif (!$error) {
        try {
            $stmt = $pdo->prepare('INSERT INTO specialties (name, icon, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$name, $icon, $sort_order]);
            $success = 'Specialty added successfully!';
        } catch (PDOException $e) {
            $error = 'Error adding specialty: ' . ($e->getCode() == 23000 ? 'Name already exists' : $e->getMessage());
        }
    }
}

// Handle Edit Specialty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $specId = (int)($_POST['specialty_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-user-doctor');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Check if custom image icon was uploaded in Edit
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['icon_file']['tmp_name'];
        $fileName = $_FILES['icon_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $uploadDir = __DIR__ . '/../uploads/specialties/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'spec_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                $icon = 'uploads/specialties/' . $newFileName;
            }
        }
    }

    if (empty($name) || $specId <= 0) {
        $error = 'Specialty name is required';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE specialties SET name = ?, icon = ?, sort_order = ? WHERE id = ?');
            $stmt->execute([$name, $icon, $sort_order, $specId]);
            $success = 'Specialty updated successfully!';
        } catch (PDOException $e) {
            $error = 'Error updating specialty: ' . ($e->getCode() == 23000 ? 'Name already exists' : $e->getMessage());
        }
    }
}

// Handle Delete Specialty
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM specialties WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'Specialty deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting specialty: ' . $e->getMessage();
    }
}

// Fetch all specialties
$stmt = $pdo->query('SELECT * FROM specialties ORDER BY sort_order ASC, name ASC');
$specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Curated list of recognizable medical icons for quick pick
$curatedIcons = [
    ['icon' => 'fa-heart-pulse', 'label' => 'Heart / Cardiology'],
    ['icon' => 'fa-brain', 'label' => 'Brain / Neurology'],
    ['icon' => 'fa-tooth', 'label' => 'Dentistry'],
    ['icon' => 'fa-eye', 'label' => 'Eye / Ophthalmology'],
    ['icon' => 'fa-bone', 'label' => 'Bones / Orthopedics'],
    ['icon' => 'fa-lungs', 'label' => 'Lungs / Pulmonology'],
    ['icon' => 'fa-baby', 'label' => 'Pediatrics / Child Care'],
    ['icon' => 'fa-stethoscope', 'label' => 'General Medicine'],
    ['icon' => 'fa-user-doctor', 'label' => 'Doctor / Specialist'],
    ['icon' => 'fa-ear-listen', 'label' => 'ENT / Ear Nose Throat'],
    ['icon' => 'fa-pills', 'label' => 'Pharmacy / Medicine'],
    ['icon' => 'fa-hospital', 'label' => 'Hospital / Clinic'],
    ['icon' => 'fa-syringe', 'label' => 'Immunization / Vaccine'],
    ['icon' => 'fa-x-ray', 'label' => 'Radiology / X-Ray'],
    ['icon' => 'fa-person-pregnant', 'label' => 'Gynecology / Maternity'],
    ['icon' => 'fa-dna', 'label' => 'Genetics / Lab'],
    ['icon' => 'fa-virus', 'label' => 'Virology / Infection'],
    ['icon' => 'fa-microscope', 'label' => 'Pathology / Testing'],
    ['icon' => 'fa-truck-medical', 'label' => 'Emergency / Ambulance'],
    ['icon' => 'fa-weight-scale', 'label' => 'Nutrition / Fitness'],
];
?>
<?php include __DIR__ . '/inc/head.php'; ?>
<body class="mesh-gradient min-h-screen antialiased">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <main style="margin-left:280px;min-width:0;overflow-x:hidden;flex:1;">
        <header class="sticky top-0 z-40 glass border-b border-white/50">
            <div class="px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <button class="lg:hidden w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-dark-600">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="hidden md:flex items-center space-x-2 text-sm">
                        <a href="index.php" class="text-dark-500 hover:text-dark-700 transition-colors">Dashboard</a>
                        <i class="fa-solid fa-chevron-right text-dark-300 text-xs"></i>
                        <span class="text-dark-800 font-medium">Specialty Management</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-dark-900 tracking-tight">Specialty Management</h1>
                    <p class="text-dark-500 mt-1">Configure specialty names, icons, and custom image uploads</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-700 text-sm">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl text-emerald-700 text-sm">
                    <i class="fa-solid fa-circle-check mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Add Form -->
                <div class="lg:col-span-1">
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm space-y-5">
                        <h2 class="text-lg font-bold text-dark-800 border-b pb-3">Add New Specialty</h2>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Specialty Name</label>
                                <input type="text" name="name" required placeholder="e.g. Cardiologist" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>

                            <!-- Option 1: Upload Custom Image Icon -->
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase">Option A: Upload Custom Image Icon</label>
                                <input type="file" name="icon_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
                                <p class="text-[11px] text-slate-400">Supported: PNG, SVG, JPG, WEBP (transparent background recommended)</p>
                            </div>

                            <!-- Option 2: FontAwesome Icon Picker -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-xs font-bold text-dark-600 uppercase">Option B: FontAwesome Class & Preview</label>
                                    <div class="w-8 h-8 rounded-lg bg-teal-500 text-white flex items-center justify-center text-base shadow-sm" id="iconPreview">
                                        <i class="fa-solid fa-user-doctor"></i>
                                    </div>
                                </div>
                                <input type="text" name="icon" id="iconInput" value="fa-user-doctor" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>

                            <!-- Curated Quick Pick Icon Grid -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Quick Pick Medical Icons</label>
                                <div class="grid grid-cols-5 gap-2 max-h-40 overflow-y-auto p-2 border border-slate-200 rounded-xl bg-slate-50">
                                    <?php foreach ($curatedIcons as $item): ?>
                                        <button type="button" 
                                                onclick="selectIcon('<?php echo $item['icon']; ?>')" 
                                                title="<?php echo htmlspecialchars($item['label']); ?>"
                                                class="w-10 h-10 rounded-xl bg-white border border-slate-200 hover:border-teal-500 hover:bg-teal-50 text-slate-700 hover:text-teal-600 flex items-center justify-center text-lg transition shadow-sm">
                                            <i class="fa-solid <?php echo $item['icon']; ?>"></i>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Sort Order</label>
                                <input type="number" name="sort_order" value="0" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <button type="submit" class="w-full btn-primary py-3 rounded-xl text-sm font-semibold text-white transition-all shadow-md">
                                <i class="fa-solid fa-plus mr-2"></i>Add Specialty
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List Specialties -->
                <div class="lg:col-span-2">
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm overflow-hidden">
                        <h2 class="text-lg font-bold text-dark-800 mb-4">Active Specialties</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-dark-100 text-left text-xs font-bold text-dark-500 uppercase">
                                        <th class="py-3 px-4">Icon</th>
                                        <th class="py-3 px-4">Name</th>
                                        <th class="py-3 px-4">Sort Order</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-dark-50">
                                    <?php if (count($specialties) === 0): ?>
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-dark-400">No specialties registered yet. Add them above.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($specialties as $spec): ?>
                                        <tr class="table-row">
                                            <td class="py-4 px-4">
                                                <div class="w-11 h-11 rounded-xl bg-teal-500 text-white flex items-center justify-center text-lg shadow-sm p-1">
                                                    <?php if (isImageIcon($spec['icon'])): ?>
                                                        <img src="../<?php echo htmlspecialchars($spec['icon']); ?>" alt="icon" class="w-full h-full object-contain filter drop-shadow">
                                                    <?php else: ?>
                                                        <i class="fa-solid <?php echo htmlspecialchars($spec['icon']); ?>"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 font-bold text-dark-800 text-base"><?php echo htmlspecialchars($spec['name']); ?></td>
                                            <td class="py-4 px-4 text-dark-600 font-semibold"><?php echo htmlspecialchars($spec['sort_order']); ?></td>
                                            <td class="py-4 px-4 text-right space-x-1">
                                                <button type="button" onclick="openEditModal(<?php echo $spec['id']; ?>, '<?php echo htmlspecialchars(addslashes($spec['name'])); ?>', '<?php echo htmlspecialchars(addslashes($spec['icon'])); ?>', <?php echo (int)$spec['sort_order']; ?>)" class="inline-flex w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 items-center justify-center transition-colors" title="Edit Specialty">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <a href="?delete=<?php echo $spec['id']; ?>" onclick="return confirm('Are you sure you want to delete this specialty?')" class="inline-flex w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 items-center justify-center transition-colors" title="Delete Specialty">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Edit Specialty Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-bold text-dark-900">Edit Specialty</h3>
            <button onclick="closeEditModal()" class="text-dark-400 hover:text-dark-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="specialty_id" id="edit_specialty_id">
            <div>
                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Specialty Name</label>
                <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <label class="block text-xs font-bold text-slate-700 uppercase">Upload New Image Icon (Optional)</label>
                <input type="file" name="icon_file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
            </div>

            <div>
                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Or FontAwesome Class / Image Path</label>
                <input type="text" name="icon" id="edit_icon" required class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
            </div>

            <div>
                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="edit_sort_order" value="0" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
            </div>

            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-200">Cancel</button>
                <button type="submit" class="px-5 py-2 btn-primary text-white rounded-xl font-semibold text-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const iconInput = document.getElementById('iconInput');
const iconPreview = document.getElementById('iconPreview');

function updatePreview(val) {
    val = val.trim();
    if (val.includes('/') || val.includes('.')) {
        iconPreview.innerHTML = `<img src="../${val}" class="w-full h-full object-contain p-1">`;
    } else {
        if (!val.startsWith('fa-')) {
            val = 'fa-' + val;
        }
        iconPreview.innerHTML = `<i class="fa-solid ${val}"></i>`;
    }
}

iconInput.addEventListener('input', (e) => updatePreview(e.target.value));

function selectIcon(iconClass) {
    iconInput.value = iconClass;
    updatePreview(iconClass);
}

function openEditModal(id, name, icon, sortOrder) {
    document.getElementById('edit_specialty_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_icon').value = icon;
    document.getElementById('edit_sort_order').value = sortOrder;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
</body>
</html>

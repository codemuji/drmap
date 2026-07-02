<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();
$error = '';
$success = '';

// Handle Add Specialty
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-user-doctor');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if (empty($name)) {
        $error = 'Specialty name is required';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO specialties (name, icon, sort_order) VALUES (?, ?, ?)');
            $stmt->execute([$name, $icon, $sort_order]);
            $success = 'Specialty added successfully!';
        } catch (PDOException $e) {
            $error = 'Error adding specialty: ' . ($e->getCode() == 23000 ? 'Name already exists' : $e->getMessage());
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

    <main class="flex-1 lg:ml-[280px]">
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
                    <p class="text-dark-500 mt-1">Configure specialties and clear icons designed for easy recognition</p>
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
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Specialty Name</label>
                                <input type="text" name="name" required placeholder="e.g. Cardiologist" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-xs font-bold text-dark-600 uppercase">Icon Class & Preview</label>
                                    <div class="w-8 h-8 rounded-lg bg-teal-500 text-white flex items-center justify-center text-base shadow-sm" id="iconPreview">
                                        <i class="fa-solid fa-user-doctor"></i>
                                    </div>
                                </div>
                                <input type="text" name="icon" id="iconInput" value="fa-user-doctor" required class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>

                            <!-- Curated Quick Pick Icon Grid -->
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Click to Pick Recognized Icon</label>
                                <div class="grid grid-cols-5 gap-2 max-h-48 overflow-y-auto p-2 border border-slate-200 rounded-xl bg-slate-50">
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
                                                <div class="w-10 h-10 rounded-xl bg-teal-500 text-white flex items-center justify-center text-lg shadow-sm">
                                                    <i class="fa-solid <?php echo htmlspecialchars($spec['icon']); ?>"></i>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 font-bold text-dark-800 text-base"><?php echo htmlspecialchars($spec['name']); ?></td>
                                            <td class="py-4 px-4 text-dark-600 font-semibold"><?php echo htmlspecialchars($spec['sort_order']); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <a href="?delete=<?php echo $spec['id']; ?>" onclick="return confirm('Are you sure you want to delete this specialty?')" class="inline-flex w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 items-center justify-center transition-colors">
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

<script>
const iconInput = document.getElementById('iconInput');
const iconPreview = document.getElementById('iconPreview');

function updatePreview(val) {
    val = val.trim();
    if (!val.startsWith('fa-')) {
        val = 'fa-' + val;
    }
    iconPreview.innerHTML = `<i class="fa-solid ${val}"></i>`;
}

iconInput.addEventListener('input', (e) => updatePreview(e.target.value));

function selectIcon(iconClass) {
    iconInput.value = iconClass;
    updatePreview(iconClass);
}
</script>
</body>
</html>

<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();
$error = '';
$success = '';

// Handle Edit/Update City
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $cityId = (int)($_POST['city_id'] ?? 0);
    $newName = trim($_POST['name'] ?? '');
    $oldName = trim($_POST['old_name'] ?? '');

    if (empty($newName) || $cityId <= 0) {
        $error = 'City name is required';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE cities SET name = ? WHERE id = ?');
            $stmt->execute([$newName, $cityId]);
            
            // Also update any doctors assigned to old city name
            if ($oldName !== '' && $oldName !== $newName) {
                $updDoc = $pdo->prepare('UPDATE doctors SET practice_city = ? WHERE practice_city = ?');
                $updDoc->execute([$newName, $oldName]);
            }
            
            $success = 'City updated successfully!';
        } catch (PDOException $e) {
            $error = 'Error updating city: ' . ($e->getCode() == 23000 ? 'City name already exists' : $e->getMessage());
        }
    }
}

// Handle Add City
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $error = 'City name is required';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO cities (name) VALUES (?)');
            $stmt->execute([$name]);
            $success = 'City added successfully!';
        } catch (PDOException $e) {
            $error = 'Error adding city: ' . ($e->getCode() == 23000 ? 'City already exists' : $e->getMessage());
        }
    }
}

// Handle Delete City
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM cities WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'City deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting city: ' . $e->getMessage();
    }
}

// Fetch all cities with doctor counts
$stmt = $pdo->query('
    SELECT c.*, COUNT(d.id) AS doctor_count 
    FROM cities c 
    LEFT JOIN doctors d ON LOWER(c.name) = LOWER(d.practice_city)
    GROUP BY c.id 
    ORDER BY c.name ASC
');
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <span class="text-dark-800 font-medium">City Management</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-dark-900 tracking-tight">City Management</h1>
                    <p class="text-dark-500 mt-1">Configure practice cities assigned to doctors & visible on search filters</p>
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
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm">
                        <h2 class="text-lg font-bold text-dark-800 mb-4">Add New City</h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">City Name</label>
                                <input type="text" name="name" required placeholder="e.g. Guwahati" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <button type="submit" class="w-full btn-primary py-2.5 rounded-xl text-sm font-semibold text-white transition-all shadow-md">
                                <i class="fa-solid fa-plus mr-2"></i>Add City
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List Cities -->
                <div class="lg:col-span-2">
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm overflow-hidden">
                        <h2 class="text-lg font-bold text-dark-800 mb-4">Active Cities</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-dark-100 text-left text-xs font-bold text-dark-500 uppercase">
                                        <th class="py-3 px-4">City ID</th>
                                        <th class="py-3 px-4">Name</th>
                                        <th class="py-3 px-4 text-center">Assigned Doctors</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-dark-50">
                                    <?php if (count($cities) === 0): ?>
                                        <tr>
                                            <td colspan="4" class="py-8 text-center text-dark-400">No cities registered yet. Add them above.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($cities as $city): ?>
                                        <tr class="table-row">
                                            <td class="py-4 px-4 text-dark-600">#<?php echo $city['id']; ?></td>
                                            <td class="py-4 px-4 font-semibold text-dark-800" id="city-name-<?php echo $city['id']; ?>">
                                                <?php echo htmlspecialchars($city['name']); ?>
                                            </td>
                                            <td class="py-4 px-4 text-center">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                                    <i class="fa-solid fa-user-doctor mr-1.5 text-[10px]"></i><?php echo (int)$city['doctor_count']; ?> doctors
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 text-right space-x-1">
                                                <button type="button" onclick="openEditModal(<?php echo $city['id']; ?>, '<?php echo htmlspecialchars(addslashes($city['name'])); ?>')" class="inline-flex w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 items-center justify-center transition-colors" title="Edit City">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <a href="?delete=<?php echo $city['id']; ?>" onclick="return confirm('Are you sure you want to delete this city?')" class="inline-flex w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 items-center justify-center transition-colors" title="Delete City">
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

<!-- Edit City Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-bold text-dark-900">Edit City Name</h3>
            <button onclick="closeEditModal()" class="text-dark-400 hover:text-dark-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="city_id" id="edit_city_id">
            <input type="hidden" name="old_name" id="edit_old_name">
            <div>
                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">City Name</label>
                <input type="text" name="name" id="edit_city_name" required class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
            </div>
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-semibold text-sm hover:bg-slate-200">Cancel</button>
                <button type="submit" class="px-5 py-2 btn-primary text-white rounded-xl font-semibold text-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name) {
    document.getElementById('edit_city_id').value = id;
    document.getElementById('edit_old_name').value = name;
    document.getElementById('edit_city_name').value = name;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
</body>
</html>

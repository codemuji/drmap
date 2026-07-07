<?php
require_once __DIR__ . '/inc/auth.php';
require_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();
$error = '';
$success = '';

// Handle Add/Edit Hospital
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? 'Guwahati');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $map_embed_url = trim($_POST['map_embed_url'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Hospital/Clinic name is required';
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare('
                    INSERT INTO hospitals (name, address, city, phone, email, image, map_embed_url, description) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$name, $address, $city, $phone, $email, $image, $map_embed_url, $description]);
                $success = 'Hospital/Clinic added successfully!';
            } elseif ($action === 'edit') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare('
                    UPDATE hospitals 
                    SET name = ?, address = ?, city = ?, phone = ?, email = ?, image = ?, map_embed_url = ?, description = ? 
                    WHERE id = ?
                ');
                $stmt->execute([$name, $address, $city, $phone, $email, $image, $map_embed_url, $description, $id]);
                $success = 'Hospital/Clinic updated successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Delete Hospital
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare('DELETE FROM hospitals WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'Hospital/Clinic deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting record: ' . $e->getMessage();
    }
}

// Fetch single hospital detail for editing (via AJAX/GET)
$editingHospital = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM hospitals WHERE id = ?');
    $stmt->execute([$editId]);
    $editingHospital = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all hospitals
$stmt = $pdo->query('SELECT * FROM hospitals ORDER BY id DESC');
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <span class="text-dark-800 font-medium">Hospital Management</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-dark-900 tracking-tight">Hospital / Clinic Management</h1>
                    <p class="text-dark-500 mt-1">Manage physical medical centers and associate them with doctors</p>
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
                <!-- Add/Edit Form -->
                <div class="lg:col-span-1">
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm">
                        <h2 class="text-lg font-bold text-dark-800 mb-4"><?php echo $editingHospital ? 'Edit Hospital' : 'Add New Hospital'; ?></h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="<?php echo $editingHospital ? 'edit' : 'add'; ?>">
                            <?php if ($editingHospital): ?>
                                <input type="hidden" name="id" value="<?php echo $editingHospital['id']; ?>">
                            <?php endif; ?>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Hospital Name</label>
                                <input type="text" name="name" required value="<?php echo $editingHospital ? htmlspecialchars($editingHospital['name']) : ''; ?>" placeholder="e.g. Guwahati Metro Hospital" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">City</label>
                                <input type="text" name="city" value="<?php echo $editingHospital ? htmlspecialchars($editingHospital['city']) : 'Guwahati'; ?>" placeholder="e.g. Guwahati" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Address</label>
                                <textarea name="address" rows="3" placeholder="Street name, landmark..." class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium"><?php echo $editingHospital ? htmlspecialchars($editingHospital['address']) : ''; ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Phone</label>
                                <input type="text" name="phone" value="<?php echo $editingHospital ? htmlspecialchars($editingHospital['phone']) : ''; ?>" placeholder="e.g. +91 361 234 5678" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Email</label>
                                <input type="email" name="email" value="<?php echo $editingHospital ? htmlspecialchars($editingHospital['email']) : ''; ?>" placeholder="e.g. contact@metrohospital.com" class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Image URL</label>
                                <input type="text" name="image" value="<?php echo $editingHospital ? htmlspecialchars($editingHospital['image']) : ''; ?>" placeholder="e.g. https://images.unsplash.com/..." class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Google Map Embed URL</label>
                                <textarea name="map_embed_url" rows="2" placeholder="https://www.google.com/maps/embed?pb=..." class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium"><?php echo $editingHospital ? htmlspecialchars($editingHospital['map_embed_url']) : ''; ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-dark-600 uppercase mb-2">Description</label>
                                <textarea name="description" rows="3" placeholder="Facilities, services, etc..." class="w-full px-4 py-2.5 border border-dark-200 rounded-xl input-premium text-sm font-medium"><?php echo $editingHospital ? htmlspecialchars($editingHospital['description']) : ''; ?></textarea>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 btn-primary py-2.5 rounded-xl text-sm font-semibold text-white transition-all shadow-md">
                                    <i class="fa-solid <?php echo $editingHospital ? 'fa-save' : 'fa-plus'; ?> mr-2"></i><?php echo $editingHospital ? 'Save Changes' : 'Add Hospital'; ?>
                                </button>
                                <?php if ($editingHospital): ?>
                                    <a href="hospitals.php" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm flex items-center transition-all border border-slate-200">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- List Hospitals -->
                <div class="lg:col-span-2">
                    <div class="card-premium rounded-2xl p-6 bg-white shadow-sm overflow-hidden">
                        <h2 class="text-lg font-bold text-dark-800 mb-4">Registered Hospitals / Clinics</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b border-dark-100 text-left text-xs font-bold text-dark-500 uppercase">
                                        <th class="py-3 px-4">Image</th>
                                        <th class="py-3 px-4">Name</th>
                                        <th class="py-3 px-4">Location</th>
                                        <th class="py-3 px-4">Contact</th>
                                        <th class="py-3 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-dark-50">
                                    <?php if (count($hospitals) === 0): ?>
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-dark-400">No clinics or hospitals registered yet. Add one above.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($hospitals as $hosp): ?>
                                        <tr class="table-row">
                                            <td class="py-4 px-4">
                                                <img src="<?php echo $hosp['image'] ?: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=100&h=100&fit=crop'; ?>" class="w-12 h-12 rounded-lg object-cover border border-dark-100">
                                            </td>
                                            <td class="py-4 px-4 font-semibold text-dark-800">
                                                <?php echo htmlspecialchars($hosp['name']); ?>
                                                <p class="text-[11px] text-dark-400 font-normal mt-0.5"><?php echo htmlspecialchars(substr($hosp['description'], 0, 60)) . (strlen($hosp['description']) > 60 ? '...' : ''); ?></p>
                                            </td>
                                            <td class="py-4 px-4">
                                                <span class="text-xs font-medium text-dark-800 block"><?php echo htmlspecialchars($hosp['city']); ?></span>
                                                <span class="text-[11px] text-dark-400 block max-w-[200px] truncate"><?php echo htmlspecialchars($hosp['address']); ?></span>
                                            </td>
                                            <td class="py-4 px-4 text-xs">
                                                <p class="text-dark-700 font-medium"><?php echo htmlspecialchars($hosp['phone']); ?></p>
                                                <p class="text-dark-400"><?php echo htmlspecialchars($hosp['email']); ?></p>
                                            </td>
                                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                                <a href="?edit=<?php echo $hosp['id']; ?>" class="inline-flex w-8 h-8 rounded-lg bg-teal-50 hover:bg-teal-100 text-teal-600 items-center justify-center transition-colors mr-1">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                <a href="?delete=<?php echo $hosp['id']; ?>" onclick="return confirm('Are you sure you want to delete this hospital? All doctor associations will be lost.')" class="inline-flex w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 items-center justify-center transition-colors">
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
</body>
</html>

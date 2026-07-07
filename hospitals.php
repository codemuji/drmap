<?php
require_once __DIR__ . '/admin/inc/db.php';
$pdo = getPDO();
try {
    $citiesStmt = $pdo->query('SELECT name FROM cities ORDER BY name ASC');
    $allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allCities = [];
}
$hospCitiesStmt = $pdo->query("SELECT DISTINCT city FROM hospitals WHERE city IS NOT NULL AND city != ''");
$hospCities = $hospCitiesStmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($hospCities as $c) {
    $c = trim($c);
    if ($c !== '' && !in_array($c, $allCities)) {
        $allCities[] = $c;
    }
}
sort($allCities);
$hospStmt = $pdo->query("SELECT h.*, (SELECT COUNT(*) FROM doctor_hospital dh JOIN doctors d ON d.id=dh.doctor_id WHERE dh.hospital_id=h.id AND d.status='active') AS doctor_count FROM hospitals h ORDER BY h.name ASC");
$dbHospitals = $hospStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($dbHospitals as &$h) {
    $docStmt = $pdo->prepare("SELECT d.id, d.name, d.specialty, d.photo FROM doctors d JOIN doctor_hospital dh ON d.id = dh.doctor_id WHERE dh.hospital_id = ? AND d.status = 'active' LIMIT 6");
    $docStmt->execute([$h['id']]);
    $h['doctors'] = $docStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($h);
$totalHospitals = count($dbHospitals);
$totalCities    = count($allCities);
$totalDoctors   = array_sum(array_column($dbHospitals, 'doctor_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals &amp; Clinics - DrMap | Find Healthcare Centers Near You</title>
    <meta name="description" content="Discover partner hospitals and clinics on DrMap. Search by name, city, or specialty and find doctors practicing at each location.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        * { font-family: "Inter", system-ui, sans-serif; }
        .ecg-preloader-wrapper { position:fixed;inset:0;z-index:10000;background:#0f172a;display:flex;align-items:center;justify-content:center;flex-direction:column;transition:opacity .6s,visibility .6s; }
        .ecg-preloader-wrapper.hidden { opacity:0;visibility:hidden;pointer-events:none; }
        .ecg-svg { width:280px;height:120px; }
        .ecg-path { stroke:#14b8a6;stroke-width:3.5;stroke-linecap:round;stroke-linejoin:round;fill:none;stroke-dasharray:1000;stroke-dashoffset:1000;animation:draw-ecg 2.5s linear infinite; }
        @keyframes draw-ecg { 0%{stroke-dashoffset:1000}70%{stroke-dashoffset:0}100%{stroke-dashoffset:-1000} }
        .ecg-pulse { animation:ecg-glow 1.5s ease-in-out infinite alternate; }
        @keyframes ecg-glow { from{filter:drop-shadow(0 0 2px rgba(20,184,166,.4))}to{filter:drop-shadow(0 0 12px rgba(20,184,166,.9))} }
        .hero-bg { 
            background-color: #0f172a;
            background-image: 
                linear-gradient(rgba(20, 184, 166, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(20, 184, 166, 0.12) 1px, transparent 1px),
                radial-gradient(circle at 50% 30%, rgba(20, 184, 166, 0.25) 0%, transparent 65%),
                linear-gradient(135deg, rgba(15, 23, 42, 0.96) 0%, rgba(19, 78, 74, 0.90) 50%, rgba(15, 23, 42, 0.96) 100%);
            background-size: 48px 48px, 48px 48px, 100% 100%, 100% 100%;
            position: relative; 
            overflow: hidden; 
        }
        .hero-orb { position:absolute;border-radius:50%;background:radial-gradient(circle,rgba(20,184,166,.22) 0%,transparent 70%);filter:blur(60px);pointer-events:none; }
        .search-pill { background:rgba(255,255,255,.95);backdrop-filter:blur(20px);border-radius:1.25rem;transition:box-shadow .25s; }
        @media (min-width: 640px) {
            .search-pill { border-radius:9999px; }
        }
        .search-pill:focus-within { box-shadow:0 0 0 4px rgba(20,184,166,.2); }
        .search-pill input { background:transparent;outline:none; }
        .stat-chip { background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);backdrop-filter:blur(10px);border-radius:1rem;transition:all .25s; }
        .stat-chip:hover { background:rgba(255,255,255,.14);transform:translateY(-2px); }
        .filter-pill { padding:.4rem 1.1rem;border-radius:9999px;font-size:.8rem;font-weight:600;border:2px solid #e2e8f0;background:white;color:#475569;cursor:pointer;transition:all .2s;white-space:nowrap; }
        .filter-pill:hover { border-color:#14b8a6;color:#0d9488; }
        .filter-pill.active { border-color:#14b8a6;background:#ccfbf1;color:#0d9488; }
        .hosp-card { background:white;border:1.5px solid #f1f5f9;border-radius:1.5rem;overflow:hidden;transition:transform .3s cubic-bezier(.34,1.56,.64,1),box-shadow .3s,border-color .3s;display:flex;flex-direction:column; }
        .hosp-card:hover { transform:translateY(-6px);box-shadow:0 20px 60px rgba(20,184,166,.13);border-color:rgba(20,184,166,.35); }
        .hosp-card-img { width:100%;height:200px;object-fit:cover;transition:transform .5s; }
        .hosp-card:hover .hosp-card-img { transform:scale(1.05); }
        .hosp-city-badge { position:absolute;top:.8rem;right:.8rem;background:#14b8a6;color:white;font-size:.65rem;font-weight:700;padding:.25rem .75rem;border-radius:9999px; }
        .doc-avatar-stack { display:flex; }
        .doc-avatar { width:2rem;height:2rem;border-radius:50%;border:2px solid white;object-fit:cover;margin-left:-.4rem;transition:transform .2s; }
        .doc-avatar:first-child { margin-left:0; }
        .doc-avatar:hover { transform:scale(1.15) translateY(-2px);z-index:5; }
        .modal-backdrop { background:rgba(15,23,42,.7);backdrop-filter:blur(6px);z-index:99999 !important; }
        @keyframes modal-in { from{opacity:0;transform:scale(.95) translateY(16px)}to{opacity:1;transform:scale(1) translateY(0)} }
        .modal-panel { animation:modal-in .3s cubic-bezier(.34,1.56,.64,1) forwards; }
        .nav-link { transition:color .2s,background .2s; }
        .nav-link:hover { color:#0d9488; }
        .nav-link.active { color:#0d9488;font-weight:700; }
        .no-scrollbar::-webkit-scrollbar { display:none; }
        .no-scrollbar { -ms-overflow-style:none;scrollbar-width:none; }
    </style>
</head>
<body class="antialiased bg-slate-50 text-slate-700">

<!-- PRELOADER -->
<div class="ecg-preloader-wrapper" id="ecg-preloader">
    <div class="ecg-pulse">
        <svg class="ecg-svg" viewBox="0 0 280 100">
            <path class="ecg-path" d="M0 50 L40 50 L60 50 L70 15 L80 85 L90 50 L100 50 L105 32 L110 68 L115 50 L130 50 L180 50 L200 50 L210 15 L220 85 L230 50 L240 50 L245 32 L250 68 L255 50 L280 50"/>
        </svg>
    </div>
    <p class="text-teal-400 font-semibold text-sm tracking-widest uppercase mt-5">Loading Hospitals...</p>
</div>

<!-- NAVBAR -->
<header class="fixed top-3 left-3 right-3 sm:top-4 sm:left-4 sm:right-4 z-50 rounded-2xl backdrop-blur-md bg-white/85 shadow-xl border border-white/30">
    <nav class="container mx-auto px-4 sm:px-6 py-3 max-w-7xl">
        <div class="flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3 min-w-max">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-heartbeat text-base sm:text-lg"></i>
                </div>
                <div>
                    <span class="text-lg sm:text-xl font-bold bg-gradient-to-r from-teal-600 to-teal-500 bg-clip-text text-transparent">DrMap</span>
                    <p class="text-[10px] sm:text-xs text-teal-600 font-semibold">Healthcare Platform</p>
                </div>
            </a>
            <div class="hidden lg:flex items-center gap-1">
                <a href="index.php" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-slate-600">Home</a>
                <a href="doctors.php" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-slate-600">Find Doctors</a>
                <a href="hospitals.php" class="nav-link active px-4 py-2 rounded-lg text-sm bg-teal-50">Hospitals</a>
                <a href="index.php#contact" class="nav-link px-4 py-2 rounded-lg text-sm font-medium text-slate-600">Contact</a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="https://wa.me/919999999999" target="_blank" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white text-base sm:text-lg shadow-lg transition-all" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="index.php#contact" class="hidden md:flex items-center gap-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-5 py-2.5 rounded-full text-sm font-semibold hover:shadow-lg transition-all">
                    Contact <i class="fas fa-arrow-right text-xs"></i>
                </a>
                <button id="mobile-menu-btn" class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg hover:bg-teal-50 text-slate-700 text-lg transition duration-300">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden lg:hidden mt-3 pt-3 pb-1 border-t border-slate-100 flex flex-col gap-1">
            <a href="index.php" class="px-4 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-teal-50 hover:text-teal-600 transition">Home</a>
            <a href="doctors.php" class="px-4 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-teal-50 hover:text-teal-600 transition">Find Doctors</a>
            <a href="hospitals.php" class="px-4 py-2.5 rounded-lg text-sm font-bold text-teal-600 bg-teal-50 transition">Hospitals</a>
            <a href="index.php#contact" class="px-4 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-teal-50 hover:text-teal-600 transition">Contact</a>
        </div>
    </nav>
</header>

<!-- HERO -->
<section class="hero-bg pt-28 sm:pt-36 pb-20 sm:pb-32 px-4 relative">
    <div class="hero-orb w-96 h-96" style="top:-8rem;right:-4rem;"></div>
    <div class="hero-orb w-64 h-64" style="bottom:-4rem;left:-4rem;"></div>
    <div class="container mx-auto max-w-5xl relative z-10 text-center">
        <div class="inline-flex items-center gap-2 bg-teal-500/15 border border-teal-500/30 text-teal-300 px-4 py-1.5 rounded-full text-xs font-semibold mb-5 backdrop-blur-sm">
            <i class="fas fa-hospital"></i> Partner Healthcare Centers
        </div>
        <h1 class="text-3xl sm:text-5xl md:text-6xl font-black text-white leading-tight mb-4">
            Find Hospitals &amp;
            <span class="bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">Clinics Near You</span>
        </h1>
        <p class="text-teal-100/80 text-sm sm:text-base md:text-lg max-w-2xl mx-auto mb-8">
            Search across our network of verified healthcare centers. Discover the doctors practicing at each facility.
        </p>
        <div class="search-pill flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 p-3 sm:p-2.5 max-w-3xl mx-auto shadow-2xl shadow-black/30 border border-white/20">
            <div class="flex items-center gap-3 w-full px-3.5 py-2.5 bg-slate-100/80 sm:bg-transparent rounded-xl sm:rounded-none">
                <i class="fas fa-search text-teal-500 text-sm flex-shrink-0"></i>
                <input id="hero-search" type="text" placeholder="Search by name, city or address..."
                       class="flex-1 py-1 text-slate-700 text-sm font-medium placeholder-slate-400 min-w-0 bg-transparent outline-none">
            </div>
            <div class="hidden sm:block w-px h-8 bg-slate-200"></div>
            <div class="flex items-center gap-3 w-full sm:w-auto px-3.5 py-2.5 bg-slate-100/80 sm:bg-transparent rounded-xl sm:rounded-none">
                <i class="fas fa-map-marker-alt text-teal-500 text-sm flex-shrink-0"></i>
                <select id="hero-city" class="flex-1 sm:w-40 py-1 text-slate-700 text-sm font-medium bg-transparent outline-none cursor-pointer">
                    <option value="all">All Cities</option>
                    <?php foreach ($allCities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="hero-search-btn" class="w-full sm:w-auto bg-gradient-to-r from-teal-500 to-teal-600 text-white px-7 py-3 rounded-xl sm:rounded-full font-bold text-sm hover:shadow-lg transition-all flex items-center justify-center gap-2 flex-shrink-0">
                <i class="fas fa-search text-xs"></i> Search
            </button>
        </div>
    </div>
    <div class="container mx-auto max-w-4xl relative z-10 mt-10 sm:mt-14">
        <div class="grid grid-cols-3 gap-2.5 sm:gap-4 max-w-lg mx-auto">
            <div class="stat-chip text-center py-3 px-2 sm:py-4 sm:px-3">
                <p class="text-xl sm:text-2xl font-black text-white"><?php echo $totalHospitals; ?></p>
                <p class="text-teal-400 text-[10px] sm:text-xs font-semibold mt-0.5">Hospitals</p>
            </div>
            <div class="stat-chip text-center py-3 px-2 sm:py-4 sm:px-3">
                <p class="text-xl sm:text-2xl font-black text-white"><?php echo $totalCities; ?></p>
                <p class="text-teal-400 text-[10px] sm:text-xs font-semibold mt-0.5">Cities</p>
            </div>
            <div class="stat-chip text-center py-3 px-2 sm:py-4 sm:px-3">
                <p class="text-xl sm:text-2xl font-black text-white"><?php echo $totalDoctors; ?>+</p>
                <p class="text-teal-400 text-[10px] sm:text-xs font-semibold mt-0.5">Doctors</p>
            </div>
        </div>
    </div>
</section>

<!-- FILTER PILLS -->
<section class="bg-white border-b border-slate-100 shadow-sm sticky top-20 z-40">
    <div class="container mx-auto max-w-7xl px-6 py-3">
        <div class="flex items-center gap-3 overflow-x-auto no-scrollbar">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider flex-shrink-0">Filter:</span>
            <button class="filter-pill active flex-shrink-0" data-city="all" onclick="setCityFilter(this,'all')">
                <i class="fas fa-globe-asia mr-1.5"></i> All Cities
            </button>
            <?php foreach ($allCities as $city): ?>
            <button class="filter-pill flex-shrink-0" data-city="<?php echo htmlspecialchars($city); ?>"
                    onclick="setCityFilter(this,'<?php echo htmlspecialchars(addslashes($city)); ?>')">
                <i class="fas fa-map-marker-alt mr-1.5"></i> <?php echo htmlspecialchars($city); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- RESULTS -->
<section class="py-12 px-4">
    <div class="container mx-auto max-w-7xl">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">
                    <span id="result-count"><?php echo $totalHospitals; ?></span>
                    <span class="font-normal text-slate-500"> Hospitals Found</span>
                </h2>
                <p class="text-slate-400 text-sm mt-0.5" id="result-subtitle">Showing all partner healthcare centers</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs text-slate-500 font-medium">Sort by:</label>
                <select id="sort-select" onchange="sortHospitals()"
                        class="text-sm border-2 border-slate-200 rounded-xl px-3 py-2 text-slate-600 focus:border-teal-400 focus:outline-none bg-white">
                    <option value="name">Name (A-Z)</option>
                    <option value="doctors">Most Doctors</option>
                    <option value="city">City</option>
                </select>
            </div>
        </div>
        <div id="hospitals-grid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
        <div id="hospital-no-results" class="hidden text-center py-24 bg-white rounded-3xl border border-slate-100 shadow-sm">
            <div class="w-20 h-20 rounded-full bg-teal-50 flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-hospital-user text-3xl text-teal-400"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-700 mb-2">No Hospitals Found</h3>
            <p class="text-slate-400 text-sm mb-6">Try a different search term or city filter.</p>
            <button onclick="resetFilters()" class="px-6 py-2.5 bg-teal-500 text-white rounded-full text-sm font-bold hover:bg-teal-600 transition">
                <i class="fas fa-undo mr-2"></i> Reset Filters
            </button>
        </div>
    </div>
</section>

<!-- MAP MODAL -->
<div id="mapModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
    <div class="modal-panel relative bg-white rounded-3xl w-full max-w-3xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-teal-500 flex items-center justify-center text-white text-sm"><i class="fas fa-map-location-dot"></i></div>
                <h3 class="font-bold text-slate-800 text-base" id="mapModalTitle">Location Map</h3>
            </div>
            <button onclick="closeMapModal()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="aspect-video w-full" id="mapModalContent"></div>
    </div>
</div>

<!-- DOCTOR DRAWER -->
<div id="docDrawer" class="hidden fixed inset-0 modal-backdrop z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div class="modal-panel bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl p-6 shadow-2xl max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-slate-800 text-base" id="drawerHospName">Doctors</h3>
            <button onclick="closeDrawer()" class="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="drawerDocList" class="space-y-3"></div>
    </div>
</div>

<!-- FOOTER -->
<footer class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white border-t border-slate-800 mt-8">
    <div class="container mx-auto px-6 py-14 max-w-7xl">
        <div class="grid md:grid-cols-4 gap-10 mb-12">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white"><i class="fas fa-heartbeat"></i></div>
                    <span class="text-xl font-bold">DrMap</span>
                </div>
                <p class="text-slate-400 text-sm leading-relaxed">Connecting patients with verified healthcare specialists across the region.</p>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-teal-400">Quick Links</h4>
                <ul class="space-y-2 text-sm text-slate-400">
                    <li><a href="index.php" class="hover:text-white transition">Home</a></li>
                    <li><a href="doctors.php" class="hover:text-white transition">Find Doctors</a></li>
                    <li><a href="hospitals.php" class="hover:text-white transition">Hospitals</a></li>
                    <li><a href="index.php#contact" class="hover:text-white transition">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-teal-400">Policy</h4>
                <div class="text-xs text-slate-400 space-y-2">
                    <p><strong class="text-slate-300">Data Protection:</strong> Patient records are securely stored under medical privacy compliance.</p>
                    <p><strong class="text-slate-300">Verification:</strong> All clinical data and addresses are verified by our administration team.</p>
                </div>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider mb-4 text-teal-400">Support</h4>
                <p class="text-xs text-slate-400 mb-2">Helpline available 24/7:</p>
                <p class="text-lg font-bold text-teal-300"><i class="fas fa-phone mr-1.5 text-sm"></i> +91 99999 99999</p>
            </div>
        </div>
        <div class="border-t border-slate-800 pt-8 text-center text-xs text-slate-500">
            &copy; 2026 DrMap. All rights reserved. Built for medical care.
        </div>
    </div>
</footer>

<script>
const hospitalsData = <?php echo json_encode(array_values($dbHospitals)); ?>;
let filteredData = [...hospitalsData];
let activeCity   = 'all';
let searchQuery  = '';

window.addEventListener('load', () => {
    setTimeout(() => document.getElementById('ecg-preloader').classList.add('hidden'), 600);
});

function renderHospitals(list) {
    const grid = document.getElementById('hospitals-grid');
    const noResults = document.getElementById('hospital-no-results');
    document.getElementById('result-count').textContent = list.length;
    document.getElementById('result-subtitle').textContent = list.length === hospitalsData.length
        ? 'Showing all partner healthcare centers'
        : 'Filtered from ' + hospitalsData.length + ' total hospitals';
    grid.innerHTML = '';
    if (list.length === 0) { noResults.classList.remove('hidden'); return; }
    noResults.classList.add('hidden');

    list.forEach(h => {
        const imgSrc = h.image || 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&h=400&fit=crop';
        const doctorCount = parseInt(h.doctor_count) || (h.doctors ? h.doctors.length : 0);

        let avatarStack = '';
        if (h.doctors && h.doctors.length > 0) {
            const shown = h.doctors.slice(0, 4);
            avatarStack = '<div class="doc-avatar-stack mt-1">'
                + shown.map(d => '<img src="' + (d.photo || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(d.name) + '&background=14b8a6&color=fff&size=60') + '" class="doc-avatar" title="' + d.name + '"'
                + ' onerror="this.src=\'https://ui-avatars.com/api/?name=' + encodeURIComponent(d.name) + '&background=14b8a6&color=fff&size=60\'">').join('')
                + (h.doctors.length > 4 ? '<div class="doc-avatar flex items-center justify-center bg-teal-100 text-teal-700 text-xs font-bold">+' + (h.doctors.length-4) + '</div>' : '')
                + '</div>';
        }

        const mapBtn = (h.map_embed_url && h.map_embed_url.trim())
            ? '<button onclick="openMapModal(\'' + h.name.replace(/'/g,"\\'") + '\',\'' + h.map_embed_url.replace(/'/g,"\\'") + '\',event)" class="mt-3 w-full bg-teal-50 hover:bg-teal-100 text-teal-700 font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all border border-teal-100 hover:border-teal-300"><i class="fas fa-map-location-dot"></i> View on Map</button>'
            : '';

        const docsSection = doctorCount > 0
            ? '<div class="mt-4 pt-4 border-t border-slate-100"><div class="flex items-center justify-between mb-2"><p class="text-xs font-bold text-slate-400"><i class="fas fa-user-md mr-1 text-teal-400"></i> Doctors (' + doctorCount + ')</p>'
              + (h.doctors && h.doctors.length > 0 ? '<button onclick="openDocDrawer(' + h.id + ',event)" class="text-xs text-teal-600 font-bold hover:underline">View All</button>' : '')
              + '</div>' + avatarStack + '</div>'
            : '<div class="mt-4 pt-4 border-t border-slate-100"><p class="text-xs font-bold text-slate-300"><i class="fas fa-user-slash mr-1"></i> No registered doctors yet</p></div>';

        const card = document.createElement('div');
        card.className = 'hosp-card';
        card.innerHTML = '<div class="relative overflow-hidden h-48 bg-slate-100 flex-shrink-0">'
            + '<img src="' + imgSrc + '" class="hosp-card-img" alt="' + h.name + '" onerror="this.src=\'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&h=400&fit=crop\'">'
            + '<span class="hosp-city-badge">' + h.city + '</span>'
            + '</div>'
            + '<div class="p-6 flex-1 flex flex-col">'
            + '<h3 class="font-bold text-slate-800 text-lg leading-snug">' + h.name + '</h3>'
            + '<p class="text-xs flex items-center gap-1.5 mt-2 text-slate-500"><i class="fas fa-map-marker-alt text-teal-500"></i>' + (h.address || 'Address not available') + '</p>'
            + (h.description ? '<p class="text-xs text-slate-400 mt-3 leading-relaxed line-clamp-2">' + h.description + '</p>' : '')
            + '<div class="mt-4 grid grid-cols-2 gap-2 text-xs text-slate-500">'
            + (h.phone ? '<a href="tel:' + h.phone + '" class="flex items-center gap-1.5 hover:text-teal-600 transition"><i class="fas fa-phone text-slate-300 w-3"></i>' + h.phone + '</a>' : '<span></span>')
            + (h.email ? '<a href="mailto:' + h.email + '" class="flex items-center gap-1.5 hover:text-teal-600 transition truncate"><i class="fas fa-envelope text-slate-300 w-3"></i>' + h.email + '</a>' : '')
            + '</div>'
            + mapBtn + docsSection
            + '</div>';
        grid.appendChild(card);
    });
}

function applyFilters() {
    const q = searchQuery.toLowerCase().trim();
    const c = activeCity.toLowerCase();
    filteredData = hospitalsData.filter(h => {
        const mQ = !q || h.name.toLowerCase().includes(q) || (h.address&&h.address.toLowerCase().includes(q)) || (h.city&&h.city.toLowerCase().includes(q)) || (h.description&&h.description.toLowerCase().includes(q));
        const mC = c === 'all' || (h.city&&h.city.toLowerCase() === c);
        return mQ && mC;
    });
    sortHospitals();
}

function sortHospitals() {
    const mode = document.getElementById('sort-select').value;
    const sorted = [...filteredData].sort((a,b) => {
        if (mode==='name')    return a.name.localeCompare(b.name);
        if (mode==='doctors') return parseInt(b.doctor_count||0)-parseInt(a.doctor_count||0);
        if (mode==='city')    return (a.city||'').localeCompare(b.city||'');
        return 0;
    });
    renderHospitals(sorted);
}

function setCityFilter(btn, city) {
    document.querySelectorAll('.filter-pill').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    activeCity = city;
    document.getElementById('hero-city').value = city;
    applyFilters();
}

function resetFilters() {
    searchQuery=''; activeCity='all';
    document.getElementById('hero-search').value='';
    document.getElementById('hero-city').value='all';
    document.querySelectorAll('.filter-pill').forEach(p=>p.classList.remove('active'));
    document.querySelector('.filter-pill[data-city="all"]').classList.add('active');
    applyFilters();
}

document.getElementById('hero-search').addEventListener('input', e => { searchQuery=e.target.value; applyFilters(); });
document.getElementById('hero-city').addEventListener('change', e => {
    activeCity=e.target.value;
    document.querySelectorAll('.filter-pill').forEach(p=>p.classList.toggle('active',p.dataset.city===activeCity));
    applyFilters();
});
document.getElementById('hero-search-btn').addEventListener('click', applyFilters);
document.getElementById('hero-search').addEventListener('keydown', e => { if(e.key==='Enter') applyFilters(); });

function openMapModal(name, url, evt) {
    if(evt) evt.stopPropagation();
    document.getElementById('mapModalTitle').textContent = name + ' - Location';
    const iframe = document.createElement('iframe');
    iframe.src=url; iframe.className='w-full h-full border-0'; iframe.allowFullscreen=true; iframe.referrerPolicy='no-referrer-when-downgrade';
    const content = document.getElementById('mapModalContent');
    content.innerHTML=''; content.appendChild(iframe);
    document.getElementById('mapModal').classList.remove('hidden');
}
function closeMapModal() {
    document.getElementById('mapModal').classList.add('hidden');
    document.getElementById('mapModalContent').innerHTML='';
}
document.getElementById('mapModal').addEventListener('click', e => { if(e.target===document.getElementById('mapModal')) closeMapModal(); });

const hospitalMap = {};
hospitalsData.forEach(h => { hospitalMap[h.id]=h; });

function openDocDrawer(id, evt) {
    if(evt) evt.stopPropagation();
    const h = hospitalMap[id];
    if(!h||!h.doctors) return;
    document.getElementById('drawerHospName').textContent = h.name + ' - Doctors';
    document.getElementById('drawerDocList').innerHTML = h.doctors.map(d =>
        '<a href="doctor-profile.php?id=' + d.id + '" class="flex items-center gap-4 p-3 rounded-2xl hover:bg-teal-50 border border-slate-100 hover:border-teal-200 transition">'
        + '<img src="' + (d.photo||'https://ui-avatars.com/api/?name='+encodeURIComponent(d.name)+'&background=14b8a6&color=fff&size=60') + '" class="w-12 h-12 rounded-xl object-cover flex-shrink-0 border-2 border-teal-100"'
        + ' onerror="this.src=\'https://ui-avatars.com/api/?name='+encodeURIComponent(d.name)+'&background=14b8a6&color=fff&size=60\'">'
        + '<div><p class="font-semibold text-slate-800 text-sm">' + d.name + '</p><p class="text-teal-600 text-xs font-medium">' + d.specialty + '</p></div>'
        + '<i class="fas fa-chevron-right text-slate-300 ml-auto text-xs"></i></a>'
    ).join('');
    document.getElementById('docDrawer').classList.remove('hidden');
}
function closeDrawer() { document.getElementById('docDrawer').classList.add('hidden'); }
document.getElementById('docDrawer').addEventListener('click', e => { if(e.target===document.getElementById('docDrawer')) closeDrawer(); });

const mobileMenuBtn = document.getElementById('mobile-menu-btn');
const mobileMenu = document.getElementById('mobile-menu');
if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
}

applyFilters();
</script>
</body>
</html>

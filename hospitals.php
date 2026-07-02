<?php
require_once __DIR__ . '/admin/inc/db.php';
$pdo = getPDO();

// Get unique cities for filter dropdown
$citiesStmt = $pdo->query("SELECT DISTINCT city FROM hospitals WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
$allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all hospitals
$hospStmt = $pdo->query("SELECT * FROM hospitals ORDER BY name ASC");
$dbHospitals = $hospStmt->fetchAll(PDO::FETCH_ASSOC);

// Map doctors to each hospital
foreach ($dbHospitals as &$h) {
    $docStmt = $pdo->prepare("
        SELECT d.id, d.name, d.specialty, d.photo 
        FROM doctors d 
        JOIN doctor_hospital dh ON d.id = dh.doctor_id 
        WHERE dh.hospital_id = ? AND d.status = 'active'
    ");
    $docStmt->execute([$h['id']]);
    $h['doctors'] = $docStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($h);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospitals & Clinics - DrMap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="index.css" />
    <style>
        /* Heart ECG Preloader styles */
        .ecg-preloader-wrapper {
            position: fixed;
            inset: 0;
            z-index: 10000;
            background: #0f172a; /* Premium Slate-900 background */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        .ecg-preloader-wrapper.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .ecg-svg {
            width: 300px;
            height: 150px;
        }
        .ecg-path {
            stroke: #14b8a6; /* teal-500 */
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw-ecg 2.5s linear infinite;
        }
        @keyframes draw-ecg {
            0% {
                stroke-dashoffset: 1000;
            }
            70% {
                stroke-dashoffset: 0;
            }
            100% {
                stroke-dashoffset: -1000;
            }
        }
        .ecg-pulse {
            animation: ecg-glow 1.5s ease-in-out infinite alternate;
        }
        @keyframes ecg-glow {
            from { filter: drop-shadow(0 0 2px rgba(20, 184, 166, 0.4)); }
            to { filter: drop-shadow(0 0 10px rgba(20, 184, 166, 0.9)); }
        }
        
        .hospital-card:hover {
            transform: translateY(-4px);
            border-color: rgba(20, 184, 166, 0.3);
            box-shadow: 0 12px 30px rgba(20, 184, 166, 0.15);
        }
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="open-sans antialiased bg-slate-50 text-slate-700">

    <!-- ECG Fullscreen Preloader (Req 13) -->
    <div class="ecg-preloader-wrapper" id="ecg-preloader">
        <div class="ecg-pulse">
            <svg class="ecg-svg" viewBox="0 0 300 100">
                <path class="ecg-path" d="M 0 50 L 50 50 L 70 50 L 80 15 L 90 85 L 100 50 L 110 50 L 115 35 L 120 65 L 125 50 L 140 50 L 200 50 L 220 50 L 230 15 L 240 85 L 250 50 L 260 50 L 265 35 L 270 65 L 275 50 L 300 50" />
            </svg>
        </div>
        <div class="text-teal-400 font-semibold tracking-wider text-sm mt-4 uppercase">Loading Hospitals...</div>
    </div>

    <!-- Header -->
    <header class="fixed top-4 left-4 right-4 z-50 rounded-2xl backdrop-blur-md bg-white/80 shadow-2xl border border-white/20">
        <nav class="container mx-auto px-6 py-4 max-w-7xl">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-3 min-w-max">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg">
                        <i class="fas fa-heartbeat text-lg"></i>
                    </div>
                    <div>
                        <span class="text-2xl font-bold bg-gradient-to-r from-teal-600 to-teal-500 bg-clip-text text-transparent">DrMap</span>
                        <p class="text-xs text-teal-600 font-medium">Healthcare Platform</p>
                    </div>
                </a>
                
                <!-- Navigation -->
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="index.php" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Home</a>
                    <a href="doctors.php" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Doctors</a>
                    <a href="hospitals.php" class="px-4 py-2 text-teal-600 font-bold bg-teal-50 rounded-lg transition duration-300 text-sm">Hospitals</a>
                    <a href="index.php#contact" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Contact</a>
                </div>

                <!-- CTA -->
                <div class="flex items-center space-x-3">
                    <!-- Whatsapp floating header link (Req 11) -->
                    <a href="https://wa.me/919999999999" target="_blank" class="w-10 h-10 rounded-full bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white text-lg shadow-lg hover:shadow-emerald-500/50 transition-all duration-300" title="Chat on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="index.php#contact" class="hidden md:flex items-center space-x-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-5 py-2 rounded-full hover:shadow-lg hover:shadow-teal-500/50 transition duration-300 text-sm font-semibold">
                        <span>Contact</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 pt-36 pb-24 px-6 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full blur-3xl"></div>
        </div>
        <div class="container mx-auto max-w-7xl relative z-10 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white mb-4">Partner <span class="bg-gradient-to-r from-teal-400 to-emerald-300 bg-clip-text text-transparent">Hospitals & Clinics</span></h1>
            <p class="text-teal-100 max-w-2xl mx-auto text-sm md:text-base">Search medical centers and discover doctors practicing in each location</p>
        </div>
    </section>

    <!-- Hospital Search Area -->
    <section class="py-4 px-6 bg-white/80 shadow-md rounded-2xl max-w-5xl mx-auto -mt-8 relative z-20 backdrop-blur-md border border-white/30">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row gap-3 items-center">
                <div class="w-full md:flex-1">
                    <div class="relative">
                        <input type="text" id="hospital-search" placeholder="Search clinics & hospitals by name, city, address..." class="w-full px-5 py-3 pl-11 text-sm rounded-full border-2 border-teal-100 focus:border-teal-500 focus:outline-none shadow-sm transition-all" />
                        <i class="fas fa-search absolute left-4 top-3.5 text-teal-400 text-sm"></i>
                    </div>
                </div>
                <div class="w-full md:w-56">
                    <select id="hospital-city-filter" class="w-full px-5 py-3 text-sm rounded-full border-2 border-teal-100 focus:border-teal-500 focus:outline-none shadow-sm transition-all">
                        <option value="all">All Cities</option>
                        <?php foreach ($allCities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <!-- Hospitals List Grid -->
    <section class="py-12 px-6 max-w-7xl mx-auto">
        <div id="hospitals-grid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Render cards via javascript based on DB fetch below -->
        </div>

        <!-- No Results -->
        <div id="hospital-no-results" class="hidden text-center py-20 bg-white rounded-3xl border border-slate-200 shadow-sm mt-8">
            <i class="fas fa-hospital-user text-6xl text-slate-300 mb-4"></i>
            <h3 class="text-2xl font-bold text-slate-700">No Hospitals Found</h3>
            <p class="text-slate-500 mt-1">Try resetting the search terms or filters</p>
        </div>
    </section>

    <!-- Map Modal -->
    <div id="mapModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-3xl w-full max-w-3xl shadow-2xl overflow-hidden animate-scale-in">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50">
                <h3 class="font-bold text-slate-800 text-lg" id="mapModalTitle">Location Map</h3>
                <button onclick="closeMapModal()" class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 hover:bg-slate-300 flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="aspect-video w-full" id="mapModalContent">
                <!-- Google Maps iframe will render here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden border-t border-slate-800">
        <div class="container mx-auto px-6 py-12 relative z-10 max-w-7xl">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <span class="text-2xl font-bold">DrMap</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-4">Connecting patients with verified healthcare specialists instantly.</p>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider mb-4 text-teal-400">Quick Links</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="index.php" class="hover:text-white transition">Home</a></li>
                        <li><a href="doctors.php" class="hover:text-white transition">Find Doctors</a></li>
                        <li><a href="hospitals.php" class="hover:text-white transition">Hospitals</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider mb-4 text-teal-400">Policy Terms (Req 29)</h4>
                    <div class="text-xs text-slate-400 space-y-2">
                        <p><strong>1. Data Protection:</strong> Patient records and enquiries are securely stored under medical privacy compliances.</p>
                        <p><strong>2. Profile Verification:</strong> All registered clinical data, addresses, and specialties are verified by the administration team.</p>
                        <p><strong>3. Consultation Policies:</strong> Digital consults and queries are fallback suggestions and do not replace emergency critical room services.</p>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider mb-4 text-teal-400">Support Care</h4>
                    <p class="text-xs text-slate-400">Support care details for helpline services:</p>
                    <p class="text-lg font-bold text-teal-300 mt-2"><i class="fas fa-phone mr-1 text-sm"></i> +91 99999 99999</p>
                </div>
            </div>
            <div class="border-t border-slate-800 pt-8 text-center text-xs text-slate-500">
                &copy; 2026 DrMap. All rights reserved. Built for medical care.
            </div>
        </div>
    </footer>

    <script>
        // Database hospitals payload
        const hospitalsData = <?php echo json_encode($dbHospitals); ?>;
        
        // Hide preloader (Req 13)
        window.addEventListener('load', () => {
            setTimeout(() => {
                document.getElementById('ecg-preloader').classList.add('hidden');
            }, 600);
        });

        // Guwahati defaults (Req 23)
        let defaultCity = 'guwahati';
        
        function renderHospitals(list) {
            const container = document.getElementById('hospitals-grid');
            const noResults = document.getElementById('hospital-no-results');
            container.innerHTML = '';
            
            if(list.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }
            noResults.classList.add('hidden');
            
            list.forEach(h => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-3xl shadow-md border border-slate-100 overflow-hidden flex flex-col hospital-card transition-all duration-300';
                
                let docBadges = '';
                if(h.doctors && h.doctors.length > 0) {
                    docBadges = `
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider mb-2">Practicing Doctors (${h.doctors.length})</p>
                            <div class="flex flex-wrap gap-2">
                                ${h.doctors.map(d => `
                                    <a href="doctor-profile.php?id=${d.id}" class="flex items-center gap-1.5 bg-slate-50 hover:bg-teal-50 px-2 py-1 rounded-full border border-slate-100 transition text-[11px] font-semibold text-slate-700">
                                        <img src="${d.photo}" class="w-4 h-4 rounded-full object-cover">
                                        <span>${d.name}</span>
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                    `;
                } else {
                    docBadges = `
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">No registered doctors</p>
                        </div>
                    `;
                }

                let mapBtn = '';
                if (h.map_embed_url && h.map_embed_url.trim() !== '') {
                    mapBtn = `
                        <button onclick="openMapModal('${h.name}', '${h.map_embed_url}')" class="mt-4 w-full bg-slate-100 hover:bg-teal-50 hover:text-teal-600 text-slate-700 font-bold py-2 rounded-xl text-xs flex items-center justify-center gap-1.5 transition-all">
                            <i class="fas fa-map-location-dot"></i> View on Map
                        </button>
                    `;
                }
                
                card.innerHTML = `
                    <div class="h-48 relative overflow-hidden bg-slate-100">
                        <img src="${h.image || 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&h=400&fit=crop'}" class="w-full h-full object-cover">
                        <span class="absolute top-4 right-4 bg-teal-500 text-white font-bold text-[10px] px-2.5 py-1 rounded-full uppercase tracking-wider shadow">${h.city}</span>
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="font-bold text-slate-800 text-lg leading-snug">${h.name}</h3>
                        <p class="text-xs text-slate-400 flex items-center gap-1.5 mt-1.5">
                            <i class="fas fa-map-marker-alt text-teal-500"></i> ${h.address}
                        </p>
                        <p class="text-xs text-slate-500 mt-3 flex-1">${h.description || ''}</p>
                        
                        <div class="mt-4 text-xs space-y-1 text-slate-600">
                            <p><i class="fas fa-phone text-slate-400 w-4"></i> ${h.phone || 'N/A'}</p>
                            <p><i class="fas fa-envelope text-slate-400 w-4"></i> ${h.email || 'N/A'}</p>
                        </div>
                        
                        ${mapBtn}
                        ${docBadges}
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Search & Filter Logic
        const searchInp = document.getElementById('hospital-search');
        const cityFilter = document.getElementById('hospital-city-filter');

        function filterHospitals() {
            const query = searchInp.value.toLowerCase();
            const city = cityFilter.value.toLowerCase();
            
            const matched = hospitalsData.filter(h => {
                const matchesSearch = h.name.toLowerCase().includes(query) || 
                                     h.address.toLowerCase().includes(query) || 
                                     (h.description && h.description.toLowerCase().includes(query));
                const matchesCity = city === 'all' || h.city.toLowerCase() === city;
                return matchesSearch && matchesCity;
            });
            renderHospitals(matched);
        }

        searchInp.addEventListener('input', filterHospitals);
        cityFilter.addEventListener('change', filterHospitals);

        // Geolocation setup for Guwahati default (Req 23)
        // Set default to Guwahati
        cityFilter.value = 'Guwahati';
        filterHospitals();

        // Modals
        function openMapModal(title, url) {
            document.getElementById('mapModalTitle').textContent = title + " - Location Map";
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = "w-full h-full border-0";
            iframe.allowFullscreen = true;
            iframe.referrerPolicy = "no-referrer-when-downgrade";
            
            const content = document.getElementById('mapModalContent');
            content.innerHTML = '';
            content.appendChild(iframe);
            
            document.getElementById('mapModal').classList.remove('hidden');
        }
        function closeMapModal() {
            document.getElementById('mapModal').classList.add('hidden');
            document.getElementById('mapModalContent').innerHTML = '';
        }
    </script>
</body>
</html>

<?php
// Fetch all active doctors from database
require_once __DIR__ . '/admin/inc/db.php';

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM doctors WHERE status = "active" ORDER BY IFNULL(`rank`, 999999) ASC, name ASC');
$stmt->execute();
$dbDoctors = $stmt->fetchAll();

// Fetch all registered practice cities from cities database table for frontend filter
try {
    $citiesStmt = $pdo->query('SELECT name FROM cities ORDER BY name ASC');
    $allCities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $allCities = [];
}

// Also include any distinct practice_city assigned to active doctors
foreach ($dbDoctors as $d) {
    $city = trim($d['practice_city'] ?? '');
    if ($city !== '' && !in_array($city, $allCities)) $allCities[] = $city;
}
sort($allCities);

// Fetch specialties from database dynamically (Req 17)
try {
    $specialtiesStmt = $pdo->prepare('SELECT * FROM specialties ORDER BY sort_order ASC, name ASC');
    $specialtiesStmt->execute();
    $specialtiesList = $specialtiesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback if table doesn't exist yet
    $specialtiesList = [];
    $fallbackStmt = $pdo->prepare('SELECT DISTINCT specialty FROM doctors WHERE status = "active" AND specialty IS NOT NULL AND specialty != "" ORDER BY specialty ASC');
    $fallbackStmt->execute();
    $fallbackSpecs = $fallbackStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($fallbackSpecs as $s) {
        $specialtiesList[] = [
            'name' => $s,
            'icon' => 'fa-user-doctor',
            'sort_order' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - MedCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="index.css" />
    <style>
        /* Heart ECG preloader animations (Req 13) */
        .preloader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #0f172a !important; /* Force Slate-900 */
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .preloader-wrapper.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(1.05);
        }
        .ecg-svg {
            width: 300px;
            height: 150px;
        }
        .ecg-path {
            stroke: #14b8a6;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 1000;
            stroke-dashoffset: 1000;
            animation: draw-ecg 2.5s linear infinite;
        }
        @keyframes draw-ecg {
            0% { stroke-dashoffset: 1000; }
            70% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -1000; }
        }
        .ecg-pulse {
            animation: ecg-glow 1.5s ease-in-out infinite alternate;
        }
        @keyframes ecg-glow {
            from { filter: drop-shadow(0 0 2px rgba(20, 184, 166, 0.4)); }
            to { filter: drop-shadow(0 0 10px rgba(20, 184, 166, 0.9)); }
        }
        .fa-x-twitter { font-weight: 400 !important; }
    </style>
</head>
<body class="open-sans antialiased bg-white">
    <!-- Preloader (Heart ECG) (Req 13) -->
    <div class="preloader-wrapper" id="preloader">
      <div class="ecg-pulse">
        <svg class="ecg-svg" viewBox="0 0 300 100">
          <path class="ecg-path" d="M 0 50 L 50 50 L 70 50 L 80 15 L 90 85 L 100 50 L 110 50 L 115 35 L 120 65 L 125 50 L 140 50 L 200 50 L 220 50 L 230 15 L 240 85 L 250 50 L 260 50 L 265 35 L 270 65 L 275 50 L 300 50" />
        </svg>
      </div>
      <div class="text-teal-400 font-semibold tracking-wider text-sm mt-4 uppercase">Loading DrMap...</div>
    </div>
    
        <!-- Header -->
        <header class="fixed top-4 left-4 right-4 z-50 rounded-2xl backdrop-blur-md bg-white/80 shadow-2xl border border-white/20">
            <nav class="container mx-auto px-6 py-4 max-w-7xl">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <a href="index.php" class="flex items-center space-x-3 min-w-max">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg">
                            <i class="fas fa-heartbeat text-lg"></i>
                        </div>
                        <div>
                            <span class="text-2xl font-bold bg-gradient-to-r from-teal-600 to-teal-500 bg-clip-text text-transparent">DrMap</span>
                            <p class="text-xs text-teal-600 font-medium">Healthcare Platform</p>
                        </div>
                    </a>
                    <!-- Desktop Navigation -->
                    <div class="hidden lg:flex items-center space-x-1">
                        <a href="index.php" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Home</a>
                        <a href="doctors.php" class="px-4 py-2 text-teal-600 font-bold bg-teal-50 rounded-lg transition duration-300 text-sm">Doctors</a>
                        <a href="hospitals.php" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Hospitals</a>
                        <a href="index.php#contact" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Contact</a>
                    </div>
                    <!-- CTA Button + Mobile Menu Toggle (Req 11) -->
                    <div class="flex items-center space-x-3">
                        <!-- Whatsapp message logo link -->
                        <a 
                          href="https://wa.me/919999999999" 
                          target="_blank" 
                          class="w-10 h-10 rounded-full bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white text-lg shadow-lg hover:shadow-emerald-500/50 transition duration-300"
                          title="Chat on WhatsApp"
                        >
                          <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="index.php#contact" class="hidden md:flex items-center space-x-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-5 py-2 rounded-full hover:shadow-lg hover:shadow-teal-500/50 transition duration-300 text-sm font-semibold">
                            <span>Contact</span>
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <!-- Mobile Menu Button -->
                        <button id="mobile-menu-btn" class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-teal-50 transition duration-300">
                            <i class="fas fa-bars text-xl text-gray-700"></i>
                        </button>
                    </div>
                </div>
                <!-- Mobile Menu -->
                <div id="mobile-menu" class="hidden lg:hidden mt-4 pt-4 pb-2 border-t border-teal-100/30">
                    <a href="index.php" class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Home</a>
                    <a href="doctors.php" class="block px-4 py-3 text-teal-600 font-bold bg-teal-50 rounded-lg transition duration-300 text-sm">Doctors</a>
                    <a href="hospitals.php" class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Hospitals</a>
                    <a href="index.php#contact" class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Contact</a>
                </div>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="light-teal-bg pt-28 pb-10 md:pt-32 md:pb-16 px-4 md:px-6 relative overflow-hidden">
            <div class="container mx-auto max-w-7xl relative z-10">
                <div class="text-center max-w-2xl mx-auto">
                    <h1 class="mt-2 text-2xl sm:text-3xl md:text-4xl font-extrabold text-gray-900 mb-2 tracking-tight">Our Expert <span class="bg-gradient-to-r from-teal-600 to-emerald-500 bg-clip-text text-transparent">Doctors</span></h1>
                    <p class="text-xs sm:text-sm md:text-base text-teal-700 mb-3 font-medium">Choose from our network of verified healthcare professionals</p>
                    <div class="inline-flex items-center justify-center space-x-2 bg-white/80 backdrop-blur-md px-4 py-1.5 rounded-full border border-teal-200/60 shadow-sm">
                        <i class="fas fa-user-md text-teal-600 text-sm"></i>
                        <span class="text-xs sm:text-sm font-bold text-gray-900" id="doctor-count">0</span>
                        <span class="text-xs text-gray-600 font-medium">Doctors Available</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Search & Filter Section -->
        <section class="py-4 px-4 sm:px-6 bg-white/95 shadow-xl rounded-2xl max-w-5xl mx-3 sm:mx-auto -mt-6 md:-mt-8 relative z-20 backdrop-blur-md border border-teal-100">
            <div class="container mx-auto">
                <div class="flex flex-col gap-3">
                    <!-- Geolocation Badge & Status -->
                    <div id="geo-location-badge" class="hidden items-center justify-between bg-teal-50/90 border border-teal-200/80 px-3.5 py-2 rounded-xl text-xs text-teal-800 font-medium">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-location-dot text-teal-600"></i>
                            <span id="geo-status-text">Detecting your location...</span>
                        </div>
                        <button type="button" onclick="requestUserLocation(true)" class="text-[11px] font-bold text-teal-700 hover:text-teal-900 underline">
                            Refresh Location
                        </button>
                    </div>

                    <!-- Search and City Filter Row -->
                    <div class="flex flex-col md:flex-row gap-3 items-center">
                        <div class="w-full md:flex-1">
                            <div class="relative">
                                <input type="text" id="search-input" placeholder="Search doctors by name or specialty..." class="form-input w-full px-4 py-2 pl-10 text-sm rounded-full border-2 border-teal-200 focus:border-teal-500 focus:outline-none shadow-sm" />
                                <i class="fas fa-search absolute left-3 top-2.5 text-teal-400 text-sm"></i>
                            </div>
                        </div>
                        <div class="w-full md:w-auto flex gap-2 items-center">
                            <select id="city-filter" class="form-input flex-1 md:w-48 px-4 py-2 text-sm rounded-full border-2 border-teal-200 focus:border-teal-500 focus:outline-none shadow-sm">
                                <option value="all">All Cities</option>
                                <?php foreach ($allCities as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="locate-me-btn" onclick="requestUserLocation(true)" title="Auto-detect my location" class="w-9 h-9 rounded-full bg-teal-50 border-2 border-teal-200 text-teal-600 hover:bg-teal-600 hover:text-white flex items-center justify-center transition shadow-sm shrink-0">
                                <i class="fas fa-crosshairs text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Specialties Grid (Req 17 & 25) -->
                    <div class="mt-2">
                        <label class="block text-xs font-bold text-teal-600 uppercase mb-2">Browse by Specialty</label>
                        <?php
                        ob_start();
                        ?>
                        <button class="specialty-pill active text-xs font-bold flex justify-center items-center py-2 px-3 rounded-lg border border-teal-100 transition hover:bg-teal-50" data-specialty="all">
                            <i class="fas fa-th-large mr-1.5"></i>All Specialties
                        </button>
                        <?php foreach ($specialtiesList as $spec): 
                            $specName = $spec['name'];
                            $icon = $spec['icon'] ?: 'fa-user-doctor';
                        ?>
                        <button class="specialty-pill text-xs font-bold flex justify-center items-center py-2 px-3 rounded-lg border border-teal-100 transition hover:bg-teal-50" data-specialty="<?php echo htmlspecialchars(strtolower($specName)); ?>">
                            <i class="fas <?php echo htmlspecialchars($icon); ?> mr-1.5"></i><?php echo htmlspecialchars($specName); ?>
                        </button>
                        <?php endforeach; ?>
                        <?php
                        $pillsHtml = ob_get_clean();
                        ?>
                        <div class="relative overflow-hidden w-full py-2 marquee-container">
                            <!-- Fade overlays -->
                            <div class="absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-white to-transparent pointer-events-none z-10"></div>
                            <div class="absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-white to-transparent pointer-events-none z-10"></div>
                            
                            <div class="flex gap-3 whitespace-nowrap overflow-hidden marquee-wrapper">
                                <div class="flex gap-3 shrink-0 animate-marquee-scroll">
                                    <?php echo $pillsHtml; ?>
                                </div>
                                <div class="flex gap-3 shrink-0 animate-marquee-scroll" aria-hidden="true">
                                    <?php echo $pillsHtml; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <style>
            .specialty-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
                font-weight: 500;
                white-space: nowrap;
                border-radius: 9999px;
                border: 2px solid #99f6e4;
                background: white;
                color: #0d9488;
                transition: all 0.3s ease;
                cursor: pointer;
            }
            
            .specialty-pill:hover {
                background: #f0fdfa;
                border-color: #5eead4;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(20, 184, 166, 0.2);
            }
            
            .specialty-pill.active {
                background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                color: white;
                border-color: #0d9488;
                box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
            }
            
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
            
            #specialty-scroll {
                scroll-behavior: smooth;
            }

            @keyframes marquee-scroll {
                0% {
                    transform: translateX(0);
                }
                100% {
                    transform: translateX(calc(-100% - 12px));
                }
            }
            
            .animate-marquee-scroll {
                animation: marquee-scroll 25s linear infinite;
            }
            
            .marquee-container:hover .animate-marquee-scroll,
            .marquee-wrapper:hover .animate-marquee-scroll,
            .animate-marquee-scroll:hover {
                animation-play-state: paused;
            }
        </style>

        <!-- Doctors Grid -->
        <section class="py-8 px-6">
            <div class="container mx-auto max-w-7xl">
                <div id="doctors-grid" class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Doctor cards will be dynamically inserted here -->
                </div>
                <!-- No Results Message -->
                <div id="no-results" class="hidden text-center py-16">
                    <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-bold text-gray-600 mb-2">No Doctors Found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter criteria</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden">
            <!-- Decorative Background Elements -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-80 h-80 bg-cyan-500 rounded-full blur-3xl"></div>
            </div>
            
            <!-- Accent Top Border -->
            <div class="h-1 bg-gradient-to-r from-teal-500 via-cyan-400 to-teal-500"></div>
            
            <div class="container mx-auto px-6 py-16 relative z-10">
                <div class="grid md:grid-cols-4 gap-12 mb-12">
                    <!-- Brand Section -->
                    <div class="md:col-span-1">
                        <div class="flex items-center space-x-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-teal-500/50">
                                <i class="fas fa-heartbeat text-xl"></i>
                            </div>
                            <span class="text-3xl font-bold bg-gradient-to-r from-white to-teal-200 bg-clip-text text-transparent">DrMap</span>
                        </div>
                        <p class="text-gray-300 leading-relaxed mb-6">
                            Your trusted platform for connecting with verified healthcare professionals. Quality care at your fingertips.
                        </p>
                        <div class="flex items-center space-x-2 text-teal-400">
                            <i class="fas fa-check-circle"></i>
                            <span class="text-sm font-semibold">500+ Verified Doctors</span>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="text-lg font-bold mb-6 flex items-center">
                            <span class="w-1 h-6 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-3"></span>
                            Quick Links
                        </h4>
                        <ul class="space-y-3">
                            <li>
                                <a href="index.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                    Home
                                </a>
                            </li>
                            <li>
                                <a href="doctors.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                    Find Doctors
                                </a>
                            </li>
                            <li>
                                <a href="index.php#about" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                    About Us
                                </a>
                            </li>
                            <li>
                                <a href="index.php#contact" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                    <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                    Contact
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Specialties in 2 columns -->
                    <div>
                        <h4 class="text-lg font-bold mb-6 flex items-center">
                            <span class="w-1 h-6 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-3"></span>
                            Specialties
                        </h4>
                        <ul class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs sm:text-sm">
                            <?php foreach ($specialtiesList as $spec): 
                                $specName = $spec['name'];
                                $icon = $spec['icon'] ?: 'fa-user-doctor';
                                $isImg = str_contains($icon, '/') || str_contains($icon, '.') || preg_match('/\.(png|jpg|jpeg|svg|webp|gif)$/i', $icon);
                                if (!$isImg && !str_contains($icon, 'fa-')) $icon = 'fa-' . $icon;
                            ?>
                            <li>
                                <a href="doctors.php?specialty=<?php echo urlencode($specName); ?>" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group truncate" title="<?php echo htmlspecialchars($specName); ?>">
                                    <?php if ($isImg): ?>
                                        <img src="<?php echo htmlspecialchars($icon); ?>" alt="icon" class="w-3.5 h-3.5 object-contain mr-2 shrink-0">
                                    <?php else: ?>
                                        <i class="fa-solid <?php echo htmlspecialchars($icon); ?> text-teal-400 mr-2 text-[11px] shrink-0 group-hover:scale-110 transition-transform"></i>
                                    <?php endif; ?>
                                    <span class="truncate"><?php echo htmlspecialchars($specName); ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Connect With Us -->
                    <div>
                        <h4 class="text-lg font-bold mb-6 flex items-center">
                            <span class="w-1 h-6 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-3"></span>
                            Connect With Us
                        </h4>
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center text-gray-300">
                                <i class="fas fa-phone text-teal-400 mr-3"></i>
                                <span class="text-sm">+1 (800) 123-4567</span>
                            </div>
                            <div class="flex items-center text-gray-300">
                                <i class="fas fa-envelope text-teal-400 mr-3"></i>
                                <span class="text-sm">support@drmap.com</span>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-lg hover:shadow-teal-500/50">
                                <i class="fab fa-facebook text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-lg hover:shadow-teal-500/50">
                                <i class="fab fa-twitter text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-lg hover:shadow-teal-500/50">
                                <i class="fab fa-instagram text-lg"></i>
                            </a>
                            <a href="#" class="w-10 h-10 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110 hover:shadow-lg hover:shadow-teal-500/50">
                                <i class="fab fa-linkedin text-lg"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="border-t border-slate-700/50 pt-8">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <p class="text-gray-400 text-sm">
                            &copy; 2026 DrMap. All rights reserved. Made with <i class="fas fa-heart text-teal-400 mx-1"></i> for better healthcare.
                        </p>
                        <div class="flex items-center space-x-6 text-sm">
                            <a href="privacy-policy.php" class="text-gray-400 hover:text-teal-400 transition duration-300">Privacy Policy</a>
                            <span class="text-gray-600">•</span>
                            <a href="terms-of-service.php" class="text-gray-400 hover:text-teal-400 transition duration-300">Terms of Service</a>
                            <span class="text-gray-600">•</span>
                            <a href="cookie-policy.php" class="text-gray-400 hover:text-teal-400 transition duration-300">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>

    <!-- Include doctors data -->
    <script src="doctors-data.js"></script>
    
    <script>
        // ============================================
        // DATABASE DOCTORS DATA (From PHP/Database)
        // ============================================
        
        // Create database doctors array from PHP
            const databaseDoctors = <?php 
            $doctorsForJS = array_map(function($doctor) {
                $locsDec = json_decode($doctor['locations'] ?? '[]', true);
                return [
                    'id' => (int)$doctor['id'],
                    'name' => $doctor['name'],
                    'specialty' => $doctor['specialty'],
                    'experience' => (int)$doctor['experience'],
                    'qualification' => $doctor['qualification'],
                    'rating' => (float)$doctor['rating'],
                    'photo' => $doctor['photo'],
                    'phone' => $doctor['phone'],
                    'email' => $doctor['email'],
                    'practice_city' => $doctor['practice_city'] ?? '',
                    'locations' => is_array($locsDec) ? $locsDec : [],
                    'reviews' => []
                ];
            }, $dbDoctors);
            echo json_encode($doctorsForJS);
        ?>;

        // CRITICAL: Override getAllDoctors to use database data
        function getAllDoctors() {
            console.log('✓ Using database doctors:', databaseDoctors.length, 'doctors loaded');
            return databaseDoctors;
        }

        // ============================================
        // MOBILE MENU
        // ============================================
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Get all doctors
        let allDoctors = getAllDoctors();
        let filteredDoctors = [...allDoctors];

        // Update doctor count
        document.getElementById('doctor-count').textContent = allDoctors.length;

        // Function to render doctor cards
        function renderDoctors(doctors) {
            const grid = document.getElementById('doctors-grid');
            const noResults = document.getElementById('no-results');
            
            grid.innerHTML = '';
            
            if (doctors.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }
            
            noResults.classList.add('hidden');
            
            doctors.forEach((doctor, index) => {
                const card = document.createElement('div');
                card.className = 'doctor-card bg-white rounded-3xl shadow-lg overflow-hidden border border-white/50 backdrop-blur-md hover:shadow-2xl hover:shadow-teal-500/20 transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col';
                
                card.innerHTML = `
                    <div class="overflow-hidden">
                        <img src="${doctor.photo}" 
                             alt="${doctor.name}" 
                             class="doctor-img w-full h-56 object-cover object-top">
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">${doctor.name}</h3>
                            <div class="flex items-center text-purple-600 mb-1">
                                <i class="fas fa-stethoscope mr-1.5 text-sm"></i>
                                <span class="font-semibold text-sm">${doctor.specialty}</span>
                            </div>
                            <div class="flex items-center text-gray-600 mb-1">
                                <i class="fas fa-graduation-cap mr-1.5 text-sm"></i>
                                <span class="text-xs">${doctor.qualification}</span>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="fas fa-briefcase mr-1.5 text-sm"></i>
                                <span class="text-xs">${doctor.experience} years experience</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                            <div class="flex items-center text-yellow-500 gap-0.5">
                                <i class="fas fa-star text-xs"></i>
                                <i class="fas fa-star text-xs"></i>
                                <i class="fas fa-star text-xs"></i>
                                <i class="fas fa-star text-xs"></i>
                                <i class="fas fa-star text-xs"></i>
                                <span class="ml-1 text-gray-600 text-xs">(${doctor.reviews.length})</span>
                            </div>
                        </div>
                        
                        <button onclick="viewProfile(${doctor.id})" 
                                class="w-full mt-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold py-2 px-4 text-sm rounded-full hover:from-teal-600 hover:to-teal-700 transition duration-300 transform hover:scale-105">
                            View Profile <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </button>
                    </div>
                `;
                
                // Make entire card clickable
                card.addEventListener('click', (e) => {
                    if (!e.target.closest('button')) {
                        viewProfile(doctor.id);
                    }
                });
                
                grid.appendChild(card);
            });
        }

        // Function to navigate to doctor profile
        function viewProfile(doctorId) {
            window.location.href = `doctor-profile.php?id=${doctorId}`;
        }

        // ============================================
        // GEOLOCATION TRACKING & DISTANCE CALCULATION
        // ============================================
        let userCoords = null;
        const dbCitiesList = <?php echo json_encode($allCities); ?>;

        const knownCityCoords = {
            'guwahati': { lat: 26.1445, lng: 91.7362 },
            'tezpur': { lat: 26.6338, lng: 92.8000 },
            'dibrugarh': { lat: 27.4728, lng: 94.9120 },
            'kolkata': { lat: 22.5726, lng: 88.3639 },
            'delhi': { lat: 28.6139, lng: 77.2090 },
            'nagaon': { lat: 26.3462, lng: 92.6840 },
            'silchar': { lat: 24.8333, lng: 92.7789 },
            'jorhat': { lat: 26.7509, lng: 94.2037 },
            'bongaigaon': { lat: 26.4769, lng: 90.5583 },
            'tinsukia': { lat: 27.4922, lng: 95.3558 }
        };

        function getDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of Earth in km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function getMinDoctorDistance(doctor, uLat, uLng) {
            if (!doctor.locations || !Array.isArray(doctor.locations) || doctor.locations.length === 0) {
                return 99999;
            }
            let minD = 99999;
            doctor.locations.forEach(loc => {
                const dLat = parseFloat(loc.lat);
                const dLng = parseFloat(loc.lng);
                if (!isNaN(dLat) && !isNaN(dLng)) {
                    const dist = getDistanceKm(uLat, uLng, dLat, dLng);
                    if (dist < minD) minD = dist;
                }
            });
            return minD;
        }

        // Find the closest DB city for user's GPS coordinates
        function findClosestDbCity(uLat, uLng, addressData = {}) {
            const fullStr = ((addressData.display_name || '') + ' ' + JSON.stringify(addressData.address || '')).toLowerCase();

            // 1. Check direct string match in reverse geocoded address
            for (let city of dbCitiesList) {
                const cLower = city.toLowerCase().trim();
                if (cLower && fullStr.includes(cLower)) {
                    return { city: city, method: 'name', distanceKm: 0 };
                }
            }

            // 2. Check closest doctor clinic GPS location
            let closestCity = '';
            let minDistance = 99999;

            allDoctors.forEach(doc => {
                if (doc.practice_city && doc.locations && Array.isArray(doc.locations)) {
                    doc.locations.forEach(loc => {
                        const dLat = parseFloat(loc.lat);
                        const dLng = parseFloat(loc.lng);
                        if (!isNaN(dLat) && !isNaN(dLng)) {
                            const dist = getDistanceKm(uLat, uLng, dLat, dLng);
                            if (dist < minDistance) {
                                minDistance = dist;
                                closestCity = doc.practice_city;
                            }
                        }
                    });
                }
            });

            // 3. Check preset city center coordinates fallback
            for (let city of dbCitiesList) {
                const key = city.toLowerCase().trim();
                if (knownCityCoords[key]) {
                    const dist = getDistanceKm(uLat, uLng, knownCityCoords[key].lat, knownCityCoords[key].lng);
                    if (dist < minDistance) {
                        minDistance = dist;
                        closestCity = city;
                    }
                }
            }

            return { city: closestCity, method: 'distance', distanceKm: minDistance };
        }

        // Request Browser Location Permission & Track Coords
        function requestUserLocation(userTriggered = false) {
            const badge = document.getElementById('geo-location-badge');
            const statusText = document.getElementById('geo-status-text');

            if (!navigator.geolocation) {
                if (userTriggered) alert('Geolocation is not supported by your browser.');
                return;
            }

            if (badge && statusText) {
                badge.classList.remove('hidden');
                badge.classList.add('flex');
                statusText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Requesting location permission...';
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    userCoords = { lat, lng };

                    // Reverse geocode via OpenStreetMap Nominatim to find city
                    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                        .then(res => res.json())
                        .then(data => {
                            const match = findClosestDbCity(lat, lng, data);

                            if (match.city && cityFilter) {
                                cityFilter.value = match.city;
                                if (statusText) {
                                    if (match.distanceKm > 0 && match.distanceKm < 999) {
                                        statusText.innerHTML = `📍 <strong>Auto-matched nearby city:</strong> ${match.city} (~${match.distanceKm.toFixed(1)} km away) — Filtered nearby doctors`;
                                    } else {
                                        statusText.innerHTML = `📍 <strong>Auto-detected city:</strong> ${match.city} — Filtered nearby doctors`;
                                    }
                                }
                            } else {
                                if (statusText) {
                                    statusText.innerHTML = `📍 <strong>Current Location:</strong> (${lat.toFixed(4)}, ${lng.toFixed(4)}) — Sorting doctors by closest distance`;
                                }
                            }

                            filterDoctors();
                        })
                        .catch(() => {
                            const match = findClosestDbCity(lat, lng, {});
                            if (match.city && cityFilter) {
                                cityFilter.value = match.city;
                                if (statusText) {
                                    statusText.innerHTML = `📍 <strong>Auto-matched city:</strong> ${match.city} — Filtered nearby doctors`;
                                }
                            }
                            filterDoctors();
                        });
                },
                (error) => {
                    console.warn('Geolocation permission error:', error.message);
                    if (statusText) {
                        if (error.code === error.PERMISSION_DENIED) {
                            statusText.innerHTML = '⚠️ Location permission denied. Select your city manually above.';
                        } else {
                            statusText.innerHTML = '⚠️ Location unavailable. Select your city manually above.';
                        }
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const cityFilter = document.getElementById('city-filter');
        const specialtyPills = document.querySelectorAll('.specialty-pill');
        let selectedSpecialty = 'all';

        function filterDoctors() {
            const searchTerm = searchInput.value.toLowerCase();
            const specialty = selectedSpecialty.toLowerCase();
            const city = (cityFilter ? cityFilter.value.toLowerCase() : 'all');

            filteredDoctors = allDoctors.filter(doctor => {
                const matchesSearch = (doctor.name || '').toLowerCase().includes(searchTerm) || 
                                     (doctor.specialty || '').toLowerCase().includes(searchTerm);
                const matchesSpecialty = specialty === 'all' || 
                                        (doctor.specialty || '').toLowerCase().includes(specialty);
                const matchesCity = city === 'all' || (doctor.practice_city || '').toLowerCase().includes(city);

                return matchesSearch && matchesSpecialty && matchesCity;
            });

            // If user coordinates available, sort doctors by closest GPS distance
            if (userCoords) {
                filteredDoctors.sort((a, b) => {
                    const distA = getMinDoctorDistance(a, userCoords.lat, userCoords.lng);
                    const distB = getMinDoctorDistance(b, userCoords.lat, userCoords.lng);
                    return distA - distB;
                });
            }
            
            renderDoctors(filteredDoctors);
        }

        // Handle specialty pill clicks
        specialtyPills.forEach(pill => {
            pill.addEventListener('click', function() {
                const specialty = this.dataset.specialty;
                // Update active state across all pills with same specialty (including duplicate marquee ones)
                specialtyPills.forEach(p => {
                    if (p.dataset.specialty === specialty) {
                        p.classList.add('active');
                    } else {
                        p.classList.remove('active');
                    }
                });
                // Update selected specialty
                selectedSpecialty = specialty;
                // Filter doctors
                filterDoctors();
            });
        });

        searchInput.addEventListener('input', filterDoctors);
        if (cityFilter) cityFilter.addEventListener('change', filterDoctors);

        // Check for specialty parameter in URL and auto-filter
        const urlParams = new URLSearchParams(window.location.search);
        const specialtyParam = urlParams.get('specialty');
        if (specialtyParam) {
            // Convert specialty parameter to lowercase for matching
            const specialtyLower = specialtyParam.toLowerCase();
            
            // Find matching specialty
            let targetSpecialty = null;
            specialtyPills.forEach(pill => {
                const pillSpecialty = pill.dataset.specialty.toLowerCase();
                const pillText = pill.textContent.toLowerCase();
                
                // Check if pill matches the specialty parameter
                if (specialtyLower.includes(pillSpecialty) || pillText.includes(specialtyLower) || 
                    specialtyParam.toLowerCase() === pill.textContent.toLowerCase().replace('all specialties', '').trim()) {
                    targetSpecialty = pill.dataset.specialty;
                }
            });
            
            if (targetSpecialty) {
                // Update active state across all pills with same specialty
                specialtyPills.forEach(p => {
                    if (p.dataset.specialty === targetSpecialty) {
                        p.classList.add('active');
                    } else {
                        p.classList.remove('active');
                    }
                });
                selectedSpecialty = targetSpecialty;
                filterDoctors();
            } else {
                // If no pill matched, try direct filter on specialty field
                selectedSpecialty = 'all';
                // Custom filter that checks if doctor specialty contains the parameter
                filteredDoctors = allDoctors.filter(doctor => {
                    return (doctor.specialty || '').toLowerCase().includes(specialtyLower);
                });
                renderDoctors(filteredDoctors);
            }
        } else {
            // Initial render without filters
            renderDoctors(allDoctors);
        }

        // Preloader functionality
        const preloader = document.getElementById("preloader");
        if (preloader) {
            window.addEventListener("load", () => {
                setTimeout(() => {
                    preloader.classList.add("hidden");
                }, 1000);
            });
        }

        // Trigger user location permission prompt automatically on page load
        document.addEventListener('DOMContentLoaded', () => {
            requestUserLocation(false);
        });

        // Scroll to top when clicking doctor cards
        window.viewProfile = viewProfile;
    </script>
</body>
</html>

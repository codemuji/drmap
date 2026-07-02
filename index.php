<?php
// Enable error reporting for debugging (remove after fixing)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Fetch approved reviews with doctor information for testimonials
require_once __DIR__ . '/admin/inc/db.php';

$pdo = getPDO();
$reviewsStmt = $pdo->prepare('
    SELECT r.*, d.name as doctor_name, d.specialty as doctor_specialty, d.photo as doctor_photo 
    FROM reviews r 
    JOIN doctors d ON r.doctor_id = d.id 
    WHERE r.status = "approved" 
    ORDER BY r.created_at DESC 
    LIMIT 20
');
$reviewsStmt->execute();
$approvedReviews = $reviewsStmt->fetchAll();

// Fetch active doctors (for homepage showcase) - Shuffled randomly on every page load (Req 2/Req 32)
$doctorsStmt = $pdo->prepare('
    SELECT id, name, specialty, experience, rating, photo, about, qualification 
    FROM doctors 
    WHERE status = "active"
');
$doctorsStmt->execute();
$topDoctors = $doctorsStmt->fetchAll();
shuffle($topDoctors);
$topDoctors = array_slice($topDoctors, 0, 12);

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DrMap - The Best Medical and Treatment Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="index.css" />
    <style>
        /* Heart ECG preloader animations (Req 13) */
        .preloader-wrapper {
            background: #0f172a !important; /* Force Slate-900 */
            flex-direction: column;
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
    <header
      class="fixed top-4 left-4 right-4 z-50 rounded-2xl backdrop-blur-md bg-white/80 shadow-2xl border border-white/20"
    >
      <nav class="container mx-auto px-6 py-4 max-w-7xl">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex items-center space-x-3 min-w-max">
            <div
              class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg"
            >
              <i class="fas fa-heartbeat text-lg"></i>
            </div>
            <div>
              <span
                class="text-2xl font-bold bg-gradient-to-r from-teal-600 to-teal-500 bg-clip-text text-transparent"
                >DrMap</span
              >
              <p class="text-xs text-teal-600 font-medium">
                Healthcare Platform
              </p>
            </div>
          </div>

          <!-- Desktop Navigation -->
          <div class="hidden lg:flex items-center space-x-1">
            <a
              href="#home"
              class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
              >Home</a
            >
            <a
              href="#about"
              class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
              >About</a
            >
            <a
              href="doctors.php"
              class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
              >Doctors</a
            >
            <a
              href="#services"
              class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
              >Features</a
            >
            <a
              href="#contact"
              class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
              >Contact</a
            >
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
            <a
              href="doctors.php"
              class="hidden md:flex items-center space-x-2 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-5 py-2 rounded-full hover:shadow-lg hover:shadow-teal-500/50 transition duration-300 text-sm font-semibold"
            >
              <span>Book Now</span>
              <i class="fas fa-arrow-right text-xs"></i>
            </a>

            <!-- Mobile Menu Button -->
            <button
              id="mobile-menu-btn"
              class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg hover:bg-teal-50 transition duration-300"
            >
              <i class="fas fa-bars text-xl text-gray-700"></i>
            </button>
          </div>
        </div>

        <!-- Mobile Menu -->
        <div
          id="mobile-menu"
          class="hidden lg:hidden mt-4 pt-4 pb-2 border-t border-teal-100/30"
        >
          <a
            href="#home"
            class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
            >Home</a
          >
          <a
            href="#about"
            class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
            >About</a
          >
          <a
            href="doctors.php"
            class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
            >Doctors</a
          >
          <a
            href="#services"
            class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
            >Features</a
          >
          <a
            href="#contact"
            class="block px-4 py-3 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium"
            >Contact</a
          >
          <a
              href="doctors.php"
            class="block mt-3 w-full bg-gradient-to-r from-teal-500 to-teal-600 text-white px-4 py-3 rounded-lg hover:shadow-lg transition duration-300 text-sm font-semibold text-center"
          >
            Book Consultation
          </a>
        </div>
      </nav>
    </header>

    <!-- Medical Professional Hero Section -->
    <section
      id="home"
      class="pt-32 pb-20 px-4 md:px-6 relative overflow-hidden"
      style="
        background: linear-gradient(135deg, #0f172a 0%, #0f2942 40%, #0d4b56 100%);
        position: relative;
      "
    >
      <!-- Background Medical Glowing Effects -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-96 h-96 bg-teal-500/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-10 right-10 w-[500px] h-[500px] bg-cyan-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
      </div>
      
      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="grid lg:grid-cols-12 gap-12 items-center min-h-[520px]">
          
          <!-- Left 7 Cols: Main Headline + Quick Search Bar -->
          <div class="lg:col-span-7 space-y-8 fade-in-up">
            <div>
              <div class="inline-flex items-center space-x-2 bg-teal-500/10 border border-teal-400/30 text-teal-300 px-4 py-2 rounded-full text-xs font-semibold uppercase tracking-wider mb-4">
                <i class="fas fa-stethoscope text-teal-400"></i>
                <span>Trusted Healthcare Network</span>
              </div>
              <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight">
                Find & Consult <br />
                <span class="bg-gradient-to-r from-teal-300 via-emerald-300 to-cyan-300 bg-clip-text text-transparent">
                  Top Doctor Specialists
                </span> Near You
              </h1>
              <p class="text-base md:text-lg text-slate-300 mt-4 max-w-xl leading-relaxed">
                Connect directly with certified medical experts across Assam & India. Browse verified timings, clinic locations, and patient reviews.
              </p>
            </div>

            <!-- Quick Search Widget Box -->
            <div class="bg-white/10 backdrop-blur-md p-4 md:p-5 rounded-2xl border border-white/20 shadow-2xl">
              <form action="doctors.php" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                
                <!-- Specialty Dropdown -->
                <div class="relative">
                  <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-teal-500">
                    <i class="fas fa-user-md"></i>
                  </div>
                  <select name="specialty" class="w-full pl-10 pr-4 py-3 bg-white text-slate-800 rounded-xl font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 appearance-none shadow-sm">
                    <option value="">All Specialties</option>
                    <?php foreach ($specialtiesList as $spec): ?>
                      <option value="<?php echo htmlspecialchars($spec['name']); ?>">
                        <?php echo htmlspecialchars($spec['name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- City Dropdown -->
                <?php
                $cityListStmt = $pdo->query("SELECT name FROM cities ORDER BY name ASC");
                $searchCities = $cityListStmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                <div class="relative">
                  <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-teal-500">
                    <i class="fas fa-location-dot"></i>
                  </div>
                  <select name="city" class="w-full pl-10 pr-4 py-3 bg-white text-slate-800 rounded-xl font-semibold text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 appearance-none shadow-sm">
                    <option value="">All Cities</option>
                    <?php foreach ($searchCities as $cName): ?>
                      <option value="<?php echo htmlspecialchars($cName); ?>">
                        <?php echo htmlspecialchars($cName); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg transition-all duration-300 hover:shadow-teal-500/50 flex items-center justify-center space-x-2">
                  <i class="fas fa-search"></i>
                  <span>Find Doctors</span>
                </button>
              </form>
            </div>

            <!-- Trust Badges Strip -->
            <div class="flex flex-wrap items-center gap-6 text-slate-300 text-xs md:text-sm font-medium pt-2">
              <div class="flex items-center space-x-2">
                <i class="fas fa-check-circle text-teal-400 text-base"></i>
                <span>Verified Doctors</span>
              </div>
              <div class="flex items-center space-x-2">
                <i class="fas fa-clock text-teal-400 text-base"></i>
                <span>Real-time Slot Schedules</span>
              </div>
              <div class="flex items-center space-x-2">
                <i class="fas fa-map-marked-alt text-teal-400 text-base"></i>
                <span>GPS Clinic Navigation</span>
              </div>
            </div>
          </div>

          <!-- Right 5 Cols: Medical Graphic Illustration + Pill Badges -->
          <div class="lg:col-span-5 relative fade-in-up delay-100 hidden lg:block">
            <div class="relative mx-auto max-w-md">
              <!-- Glow Aura -->
              <div class="absolute inset-0 bg-gradient-to-tr from-teal-400 to-cyan-500 rounded-3xl blur-2xl opacity-40"></div>
              
              <!-- Medical Hero Image Frame -->
              <div class="relative bg-gradient-to-b from-teal-800/60 to-slate-900/80 rounded-3xl p-3 border border-white/20 shadow-2xl overflow-hidden">
                <img
                  src="https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=600&h=700&fit=crop"
                  alt="Medical Specialist DrMap"
                  class="w-full h-auto rounded-2xl object-cover"
                />

                <!-- Floating Doctor Badge 1 -->
                <div class="absolute top-6 left-6 bg-slate-900/90 backdrop-blur-md border border-white/20 text-white p-3.5 rounded-2xl shadow-xl flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-teal-500 text-white flex items-center justify-center font-bold">
                    <i class="fas fa-user-shield"></i>
                  </div>
                  <div>
                    <div class="text-xs font-bold text-teal-300">Verified & Approved</div>
                    <div class="text-[11px] text-slate-300">100% Certified Doctors</div>
                  </div>
                </div>

                <!-- Floating Doctor Badge 2 -->
                <div class="absolute bottom-6 right-6 bg-slate-900/90 backdrop-blur-md border border-white/20 text-white p-3.5 rounded-2xl shadow-xl flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold">
                    <i class="fas fa-heart-pulse"></i>
                  </div>
                  <div>
                    <div class="text-xs font-bold text-emerald-300">Quick Appointment</div>
                    <div class="text-[11px] text-slate-300">No Waiting Time</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- About Section -->
    <section
      id="about"
      class="py-24 px-4 md:px-6 bg-white relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -right-40 w-80 h-80 bg-teal-100/40 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -left-40 w-80 h-80 bg-teal-50/40 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <!-- Image with enhanced effects -->
          <div class="fade-in-up">
            <div class="relative group">
              <!-- Glow effect -->
              <div
                class="absolute inset-0 bg-gradient-to-br from-teal-400/30 to-green-400/30 rounded-3xl blur-2xl group-hover:blur-3xl transition duration-700"
              ></div>

              <!-- Image frame -->
              <div class="relative">
                <img
                  src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&h=500&fit=crop"
                  alt="DrMap Platform"
                  class="w-full h-auto rounded-3xl shadow-2xl border-4 border-white/30 group-hover:shadow-teal-500/30 transition duration-500 group-hover:scale-105 transform"
                />
                <!-- Badge overlay -->
                <div
                  class="absolute top-8 left-8 bg-white/95 backdrop-blur-md text-teal-600 px-6 py-3 rounded-2xl shadow-xl border border-white/50 animate-float"
                >
                  <div class="flex items-center space-x-2">
                    <i class="fas fa-award text-lg"></i>
                    <span class="font-bold">Trusted Since 2020</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Content with enhanced styling -->
          <div class="fade-in-up delay-100">
            <div class="space-y-6">
              <div>
                <p class="text-teal-600 font-semibold mb-1 text-sm">
                  About DrMap
                </p>
                <h2 class="text-3xl font-bold text-gray-900 mb-3">
                  Why Choose
                  <span
                    class="bg-gradient-to-r from-teal-500 to-green-400 bg-clip-text text-transparent"
                    >DrMap</span
                  >
                </h2>
                <p class="text-gray-700 text-sm leading-relaxed mb-3">
                  DrMap is a leading telemedicine platform dedicated to connecting patients with verified healthcare professionals. Our mission is to make quality healthcare accessible, affordable, and convenient for everyone.
                </p>
                <p class="text-gray-600 text-xs leading-relaxed">
                  With a network of experienced doctors across multiple specialties, we ensure you receive personalized medical consultations from the comfort of your home.
                </p>
              </div>

              <!-- Enhanced Statistics -->
              <div class="grid grid-cols-3 gap-2 pt-2">
                <div class="group relative fade-in-up">
                  <div
                    class="absolute inset-0 bg-gradient-to-r from-teal-500 to-teal-600 rounded-xl blur-md opacity-40 group-hover:opacity-60 transition duration-500"
                  ></div>
                  <div
                    class="relative bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl p-3 text-white shadow-md border border-white/20 text-center hover:shadow-teal-500/40 transition duration-300"
                  >
                    <div class="stat-number text-xl font-bold mb-1">500+</div>
                    <div class="stat-text text-xs font-medium">
                      Verified Doctors
                    </div>
                  </div>
                </div>
                <div class="group relative fade-in-up delay-100">
                  <div
                    class="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl blur-md opacity-40 group-hover:opacity-60 transition duration-500"
                  ></div>
                  <div
                    class="relative bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-3 text-white shadow-md border border-white/20 text-center hover:shadow-blue-500/40 transition duration-300"
                  >
                    <div class="stat-number text-xl font-bold mb-1">50K+</div>
                    <div class="stat-text text-xs font-medium">
                      Happy Patients
                    </div>
                  </div>
                </div>
                <div class="group relative fade-in-up delay-200">
                  <div
                    class="absolute inset-0 bg-gradient-to-r from-green-500 to-green-600 rounded-xl blur-md opacity-40 group-hover:opacity-60 transition duration-500"
                  ></div>
                  <div
                    class="relative bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-3 text-white shadow-md border border-white/20 text-center hover:shadow-green-500/40 transition duration-300"
                  >
                    <div class="stat-number text-xl font-bold mb-1">30+</div>
                    <div class="stat-text text-xs font-medium">Specialties</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section
      id="how-it-works"
      class="pt-20 pb-12 px-4 md:px-6 bg-gradient-to-br from-white via-gray-50 to-white relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -right-40 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -left-40 w-80 h-80 bg-teal-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-3">
            Learn more about<br /><span class="text-gray-400">how it works</span>
          </h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Step 1: Browse Doctors -->
          <div class="group fade-in-up">
            <div class="relative h-full">
              <div
                class="relative bg-gradient-to-br from-teal-50 to-teal-100/50 rounded-3xl p-8 shadow-lg border border-teal-200/50 hover:shadow-xl transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center text-center"
              >
                <div class="mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-teal-600 text-white text-2xl font-bold">
                  1
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">
                  Browse Our Specialists
                </h3>
                <p class="text-gray-700 text-sm leading-relaxed">
                  Explore our network of verified doctors across multiple specialties and choose the one that fits your needs.
                </p>
                <div
                  class="flex justify-center mt-auto pt-4"
                >
                  <i class="fas fa-chevron-down text-teal-600 text-2xl"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Book Appointment -->
          <div class="group fade-in-up delay-100">
            <div class="relative h-full">
              <div
                class="relative bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-3xl p-8 shadow-lg border border-blue-200/50 hover:shadow-xl transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center text-center"
              >
                <div class="mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-blue-600 text-white text-2xl font-bold">
                  2
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">
                  Book Your Appointment
                </h3>
                <p class="text-gray-700 text-sm leading-relaxed">
                  Select your preferred date and time for a consultation. Book instantly without any waiting period or hidden fees.
                </p>
                <div
                  class="flex justify-center mt-auto pt-4"
                >
                  <i class="fas fa-chevron-down text-blue-600 text-2xl"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Have Consultation -->
          <div class="group fade-in-up delay-200">
            <div class="relative h-full">
              <div
                class="relative bg-gradient-to-br from-green-50 to-green-100/50 rounded-3xl p-8 shadow-lg border border-green-200/50 hover:shadow-xl transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center text-center"
              >
                <div class="mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-green-600 text-white text-2xl font-bold">
                  3
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">
                  Online Consultation
                </h3>
                <p class="text-gray-700 text-sm leading-relaxed">
                  Connect with your doctor via secure video call. Discuss your health concerns and receive professional medical advice.
                </p>
                <div
                  class="flex justify-center mt-auto pt-4"
                >
                  <i class="fas fa-chevron-down text-green-600 text-2xl"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Receive Treatment Plan -->
          <div class="group fade-in-up delay-300">
            <div class="relative h-full">
              <div
                class="relative bg-gradient-to-br from-purple-50 to-purple-100/50 rounded-3xl p-8 shadow-lg border border-purple-200/50 hover:shadow-xl transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center text-center"
              >
                <div class="mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-purple-600 text-white text-2xl font-bold">
                  4
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">
                  Get Your Plan & Follow-Up
                </h3>
                <p class="text-gray-700 text-sm leading-relaxed">
                  Receive personalized treatment recommendations, prescriptions, and follow-up care instructions delivered to your account.
                </p>
                <div
                  class="flex justify-center mt-auto pt-4"
                >
                  <i class="fas fa-chevron-down text-purple-600 text-2xl"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Specialties Browse Section -->
    <section
      id="specialties-browse"
      class="py-20 px-4 md:px-6 bg-white relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -right-40 w-80 h-80 bg-teal-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="text-center mb-12">
          <p class="text-teal-600 font-semibold text-sm mb-2">Find Your Doctor</p>
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
            Browse by
            <span class="bg-gradient-to-r from-teal-500 to-green-400 bg-clip-text text-transparent">
              Specialty
            </span>
          </h2>
          <p class="text-gray-600 text-sm max-w-2xl mx-auto">
            Connect with healthcare professionals in your area of need
          </p>
        </div>

        <!-- Specialties Fixed Block Grid (Accurate Icons & High Contrast) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 px-2">
          <?php 
          foreach ($specialtiesList as $index => $spec): 
            $specName = $spec['name'];
            $icon = $spec['icon'] ?: 'fa-user-doctor';
            $isImage = str_contains($icon, '/') || str_contains($icon, '.') || preg_match('/\.(png|jpg|jpeg|svg|webp|gif)$/i', $icon);
            if (!$isImage && !str_contains($icon, 'fa-')) {
                $icon = 'fa-' . $icon;
            }
            $encodedSpecialty = urlencode($specName);
          ?>
          <a href="doctors.php?specialty=<?php echo $encodedSpecialty; ?>" class="group">
            <div class="relative bg-slate-50 hover:bg-teal-600 rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-teal-600 hover:shadow-2xl transition-all duration-300 group-hover:-translate-y-1 transform text-center cursor-pointer h-full flex flex-col items-center justify-between">
              <div>
                <!-- Large High-Contrast Icon Circle -->
                <div class="w-16 h-16 rounded-2xl bg-teal-500 group-hover:bg-white text-white group-hover:text-teal-600 flex items-center justify-center mx-auto mb-4 shadow-md transition duration-300 p-2">
                  <?php if ($isImage): ?>
                    <img src="<?php echo htmlspecialchars($icon); ?>" alt="<?php echo htmlspecialchars($specName); ?>" class="w-10 h-10 object-contain filter drop-shadow">
                  <?php else: ?>
                    <i class="fa-solid <?php echo htmlspecialchars($icon); ?> text-2xl"></i>
                  <?php endif; ?>
                </div>
                <h3 class="text-sm md:text-base font-extrabold text-slate-900 group-hover:text-white mb-1 transition-colors leading-snug"><?php echo htmlspecialchars($specName); ?></h3>
              </div>
              <span class="text-xs font-semibold text-teal-600 group-hover:text-teal-100 mt-3 flex items-center justify-center gap-1">
                <span>Browse</span> <i class="fas fa-arrow-right text-[10px]"></i>
              </span>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="text-center mt-10">
          <a
            href="doctors.php"
            class="inline-flex items-center justify-center space-x-2 bg-white text-teal-600 px-6 py-3 rounded-xl shadow-md border border-teal-200 transition-all duration-300 hover:shadow-xl hover:bg-teal-50 hover:scale-105 active:scale-95 font-semibold text-sm group"
          >
            <span>View All Specialties</span>
            <i class="fas fa-arrow-right text-xs group-hover:translate-x-2 transition duration-300"></i>
          </a>
        </div>
      </div>
    </section>

    <!-- Specialists Section -->
    <section
      id="specialists"
      class="pt-12 pb-20 px-4 md:px-6 bg-gradient-to-br from-gray-50 via-white to-gray-50 relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -left-40 w-80 h-80 bg-teal-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -right-40 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="text-center mb-12">
          <p class="text-teal-600 font-semibold text-sm mb-2">Meet Our</p>
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">
            Expert
            <span
              class="bg-gradient-to-r from-teal-500 to-green-400 bg-clip-text text-transparent"
              >Doctors</span
            >
          </h2>
          <p class="text-gray-600 text-sm max-w-2xl mx-auto">
            Browse through our carefully selected team of medical professionals
            with years of expertise
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
          <?php 
          $colors = [
            ['from' => 'teal', 'to' => 'teal'],
            ['from' => 'blue', 'to' => 'blue'],
            ['from' => 'green', 'to' => 'green'],
            ['from' => 'purple', 'to' => 'purple'],
            ['from' => 'pink', 'to' => 'pink'],
            ['from' => 'indigo', 'to' => 'indigo'],
            ['from' => 'red', 'to' => 'red'],
            ['from' => 'orange', 'to' => 'orange']
          ];
          
          foreach ($topDoctors as $index => $doc): 
            $color = $colors[$index % count($colors)];
            $doctorName = htmlspecialchars($doc['name']);
            $doctorSpecialty = htmlspecialchars($doc['specialty']);
            $doctorExperience = (int)($doc['experience'] ?? 0);
            $doctorRating = floatval($doc['rating'] ?? 0);
            $doctorPhoto = htmlspecialchars($doc['photo'] ?? '');
            $doctorAbout = htmlspecialchars($doc['about'] ?? '');
            $doctorId = (int)$doc['id'];
            $delay = ($index % 4) * 100; // Stagger animation
          ?>
          <!-- Doctor <?php echo $index + 1; ?> -->
          <div class="group fade-in-up" style="animation-delay: <?php echo $delay; ?>ms;">
            <div class="doctor-card bg-white rounded-3xl shadow-lg overflow-hidden border border-white/50 backdrop-blur-md hover:shadow-2xl hover:shadow-teal-500/20 transition-all duration-300 transform h-full flex flex-col cursor-pointer" onclick="window.location.href='doctor-profile.php?id=<?php echo $doctorId; ?>'">
              <div class="overflow-hidden">
                <img 
                  src="<?php echo $doctorPhoto ?: 'https://ui-avatars.com/api/?name=' . urlencode($doctorName) . '&size=400&background=random'; ?>" 
                  alt="<?php echo $doctorName; ?>" 
                  onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($doctorName); ?>&size=400&background=random'"
                  class="doctor-img w-full h-56 object-cover object-top">
              </div>
              <div class="p-4">
                <div class="mb-3">
                  <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo $doctorName; ?></h3>
                  <div class="flex items-center text-purple-600 mb-1">
                    <i class="fas fa-stethoscope mr-1.5 text-sm"></i>
                    <span class="font-semibold text-sm"><?php echo $doctorSpecialty; ?></span>
                  </div>
                  <div class="flex items-center text-gray-600 mb-1">
                    <i class="fas fa-graduation-cap mr-1.5 text-sm"></i>
                    <span class="text-xs"><?php echo htmlspecialchars($doc['qualification'] ?? 'Medical Professional'); ?></span>
                  </div>
                  <div class="flex items-center text-gray-600">
                    <i class="fas fa-briefcase mr-1.5 text-sm"></i>
                    <span class="text-xs"><?php echo $doctorExperience; ?> years experience</span>
                  </div>
                </div>
                
                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                  <div class="flex items-center text-yellow-500 gap-0.5">
                    <?php 
                    $fullStars = floor($doctorRating);
                    $hasHalfStar = ($doctorRating - $fullStars) >= 0.5;
                    for ($i = 1; $i <= 5; $i++): 
                      if ($i <= $fullStars): 
                    ?>
                      <i class="fas fa-star text-xs"></i>
                    <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                      <i class="fas fa-star-half-alt text-xs"></i>
                    <?php else: ?>
                      <i class="far fa-star text-xs"></i>
                    <?php 
                      endif;
                    endfor; 
                    ?>
                    <span class="ml-1 text-gray-600 text-xs">(<?php echo $doctorRating > 0 ? number_format($doctorRating, 1) : '0'; ?>)</span>
                  </div>
                </div>
                
                <button 
                  onclick="event.stopPropagation(); window.location.href='doctor-profile.php?id=<?php echo $doctorId; ?>'" 
                  class="w-full mt-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold py-2 px-4 text-sm rounded-full hover:from-teal-600 hover:to-teal-700 transition duration-300 transform hover:scale-105">
                  View Profile <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="text-center">
          <a
            href="doctors.php"
            class="inline-flex items-center justify-center space-x-2 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white px-5 py-2 rounded-xl shadow-md border border-white/20 transition-all duration-300 hover:shadow-xl hover:shadow-teal-500/50 hover:scale-105 active:scale-95 font-bold text-sm group"
          >
            <span>See All Doctors</span>
            <i
              class="fas fa-arrow-right text-xs group-hover:translate-x-2 transition duration-300"
            ></i>
          </a>
        </div>
      </div>
    </section>

    <!-- Services Section -->
    <section
      id="services"
      class="py-24 px-4 md:px-6 bg-gradient-to-b from-white via-gray-50 to-white relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 right-0 w-80 h-80 bg-teal-100/40 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 left-0 w-80 h-80 bg-blue-100/40 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="text-center mb-12">
          <p class="text-teal-600 font-semibold text-sm mb-2">
            Why We Stand Out
          </p>
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            Our Key
            <span
              class="bg-gradient-to-r from-teal-500 to-green-400 bg-clip-text text-transparent"
              >Features</span
            >
          </h2>
          <p class="text-gray-600 text-sm max-w-2xl mx-auto">
            Everything you need for quality healthcare in one platform
          </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
          <!-- Feature 1 -->
          <div class="group fade-in-up">
            <div class="relative">
              <div
                class="absolute inset-0 bg-gradient-to-r from-teal-500 to-teal-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition duration-500"
              ></div>
              <div
                class="relative bg-gradient-to-br from-teal-50 to-teal-100/50 backdrop-blur-md p-6 rounded-2xl border border-teal-200/50 shadow-lg hover:shadow-xl hover:shadow-teal-500/20 transition-all duration-300 group-hover:scale-105 transform"
              >
                <div class="flex items-start space-x-4 mb-3">
                  <div
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 text-white flex items-center justify-center flex-shrink-0 shadow-lg"
                  >
                    <i class="fas fa-check-circle text-lg"></i>
                  </div>
                  <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                      Verified Doctors
                    </h3>
                    <p class="text-gray-700 text-sm leading-relaxed">
                      All our doctors are board-certified and verified
                      professionals with years of experience in their respective
                      medical specialties.
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center space-x-2 text-teal-600 font-semibold text-sm group-hover:translate-x-2 transition duration-300"
                >
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Feature 2 -->
          <div class="group fade-in-up delay-100">
            <div class="relative">
              <div
                class="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition duration-500"
              ></div>
              <div
                class="relative bg-gradient-to-br from-blue-50 to-blue-100/50 backdrop-blur-md p-6 rounded-2xl border border-blue-200/50 shadow-lg hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group-hover:scale-105 transform"
              >
                <div class="flex items-start space-x-4 mb-3">
                  <div
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-lg"
                  >
                    <i class="fas fa-calendar-alt text-lg"></i>
                  </div>
                  <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                      Easy Appointment
                    </h3>
                    <p class="text-gray-700 text-sm leading-relaxed">
                      Book appointments instantly with your preferred doctor at
                      a time that works best for you. Flexible scheduling for
                      your convenience.
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center space-x-2 text-blue-600 font-semibold text-sm group-hover:translate-x-2 transition duration-300"
                >
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Feature 3 -->
          <div class="group fade-in-up delay-200">
            <div class="relative">
              <div
                class="absolute inset-0 bg-gradient-to-r from-green-500 to-green-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition duration-500"
              ></div>
              <div
                class="relative bg-gradient-to-br from-green-50 to-green-100/50 backdrop-blur-md p-6 rounded-2xl border border-green-200/50 shadow-lg hover:shadow-xl hover:shadow-green-500/20 transition-all duration-300 group-hover:scale-105 transform"
              >
                <div class="flex items-start space-x-4 mb-3">
                  <div
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-green-500 to-green-600 text-white flex items-center justify-center flex-shrink-0 shadow-lg"
                  >
                    <i class="fas fa-comments text-lg"></i>
                  </div>
                  <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                      Personal Consultation
                    </h3>
                    <p class="text-gray-700 text-sm leading-relaxed">
                      Get one-on-one personalized consultations tailored to your
                      specific health needs with expert medical professionals.
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center space-x-2 text-green-600 font-semibold text-sm group-hover:translate-x-2 transition duration-300"
                >
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Feature 4 -->
          <div class="group fade-in-up delay-300">
            <div class="relative">
              <div
                class="absolute inset-0 bg-gradient-to-r from-purple-500 to-purple-600 rounded-3xl blur-xl opacity-0 group-hover:opacity-50 transition duration-500"
              ></div>
              <div
                class="relative bg-gradient-to-br from-purple-50 to-purple-100/50 backdrop-blur-md p-6 rounded-2xl border border-purple-200/50 shadow-lg hover:shadow-xl hover:shadow-purple-500/20 transition-all duration-300 group-hover:scale-105 transform"
              >
                <div class="flex items-start space-x-4 mb-3">
                  <div
                    class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 text-white flex items-center justify-center flex-shrink-0 shadow-lg"
                  >
                    <i class="fas fa-shield-alt text-lg"></i>
                  </div>
                  <div class="flex-grow">
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                      Secure & Private
                    </h3>
                    <p class="text-gray-700 text-sm leading-relaxed">
                      Your health information is protected with enterprise-grade
                      security and complete confidentiality guaranteed.
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center space-x-2 text-purple-600 font-semibold text-sm group-hover:translate-x-2 transition duration-300"
                >
                  <span>Learn More</span>
                  <i class="fas fa-arrow-right"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section
      id="testimonials"
      class="py-24 px-4 md:px-6 bg-white relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -right-40 w-80 h-80 bg-teal-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-100/30 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
            Real love from real
            <span class="text-gray-400">customers</span>
          </h2>
          <?php if (!empty($approvedReviews)): ?>
          <p class="text-gray-600 text-lg">
            <?php echo count($approvedReviews); ?> verified review<?php echo count($approvedReviews) != 1 ? 's' : ''; ?> from satisfied patients
          </p>
          <?php endif; ?>
        </div>

        <!-- Testimonials Carousel -->
        <div class="relative max-w-4xl mx-auto">
          <!-- Testimonial Container -->
          <div class="testimonials-container overflow-hidden">
            <div class="testimonials-wrapper flex transition-transform duration-500" id="testimonialsWrapper">
              <?php if (!empty($approvedReviews)): ?>
                <?php 
                $colors = [
                  ['from' => 'teal', 'to' => 'teal'],
                  ['from' => 'blue', 'to' => 'blue'],
                  ['from' => 'green', 'to' => 'green'],
                  ['from' => 'purple', 'to' => 'purple'],
                  ['from' => 'pink', 'to' => 'pink'],
                  ['from' => 'indigo', 'to' => 'indigo']
                ];
                foreach ($approvedReviews as $index => $review): 
                  $color = $colors[$index % count($colors)];
                  $customerName = htmlspecialchars($review['customer_name']);
                  $reviewText = htmlspecialchars($review['review_text'] ?: 'Great experience!');
                  $rating = intval($review['rating']);
                  $doctorName = htmlspecialchars($review['doctor_name']);
                  $doctorSpecialty = htmlspecialchars($review['doctor_specialty']);
                  $doctorId = intval($review['doctor_id']);
                ?>
              <!-- Review <?php echo $index + 1; ?> -->
              <div class="testimonials-slide flex-shrink-0 w-full px-4 md:px-8">
                <div class="bg-gradient-to-br from-<?php echo $color['from']; ?>-50 to-<?php echo $color['to']; ?>-100/50 rounded-3xl p-8 md:p-12 border border-<?php echo $color['from']; ?>-200/50 shadow-lg">
                  <p class="text-gray-700 text-lg md:text-xl italic leading-relaxed mb-6">
                    "<?php echo $reviewText; ?>"
                  </p>
                  <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                      <h4 class="text-gray-900 font-bold text-lg"><?php echo $customerName; ?></h4>
                      <p class="text-<?php echo $color['from']; ?>-600 font-semibold text-sm">
                        Patient of <a href="doctor-profile.php?id=<?php echo $doctorId; ?>" class="hover:underline">Dr. <?php echo $doctorName; ?></a>
                      </p>
                      <p class="text-gray-500 text-xs mt-1"><?php echo $doctorSpecialty; ?></p>
                    </div>
                    <div class="flex gap-1">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $rating): ?>
                          <i class="fas fa-star text-amber-400"></i>
                        <?php elseif ($i - 0.5 <= $rating): ?>
                          <i class="fas fa-star-half-alt text-amber-400"></i>
                        <?php else: ?>
                          <i class="far fa-star text-gray-300"></i>
                        <?php endif; ?>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php else: ?>
              <!-- Default Testimonial (No reviews yet) -->
              <div class="testimonials-slide flex-shrink-0 w-full px-4 md:px-8">
                <div class="bg-gradient-to-br from-teal-50 to-teal-100/50 rounded-3xl p-8 md:p-12 border border-teal-200/50 shadow-lg">
                  <p class="text-gray-700 text-lg md:text-xl italic leading-relaxed mb-6">
                    "I was skeptical about online consultations, but the experience was seamless. The doctor was professional and spent quality time understanding my condition. Highly recommended!"
                  </p>
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="text-gray-900 font-bold text-lg">Sarah Mitchell</h4>
                      <p class="text-teal-600 font-semibold text-sm">Verified Patient</p>
                    </div>
                    <div class="flex gap-1">
                      <i class="fas fa-star text-amber-400"></i>
                      <i class="fas fa-star text-amber-400"></i>
                      <i class="fas fa-star text-amber-400"></i>
                      <i class="fas fa-star text-amber-400"></i>
                      <i class="fas fa-star text-amber-400"></i>
                    </div>
                  </div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Carousel Controls -->
          <?php if (count($approvedReviews) > 1): ?>
          <div class="flex justify-center items-center gap-4 mt-8">
            <button
              class="testimonial-prev w-10 h-10 rounded-full bg-teal-600 hover:bg-teal-700 text-white flex items-center justify-center transition-all duration-300"
              onclick="previousTestimonial()"
            >
              <i class="fas fa-chevron-left"></i>
            </button>

            <!-- Dots -->
            <div class="flex gap-2" id="testimonialDots">
              <?php for ($i = 0; $i < count($approvedReviews); $i++): ?>
              <button
                class="testimonial-dot w-3 h-3 rounded-full <?php echo $i === 0 ? 'bg-teal-600' : 'bg-gray-300 hover:bg-gray-400'; ?> transition-all duration-300"
                onclick="goToTestimonial(<?php echo $i; ?>)"
              ></button>
              <?php endfor; ?>
            </div>

            <button
              class="testimonial-next w-10 h-10 rounded-full bg-teal-600 hover:bg-teal-700 text-white flex items-center justify-center transition-all duration-300"
              onclick="nextTestimonial()"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          <?php endif; ?>

          <!-- Disclaimer -->
          <p class="text-center text-gray-500 text-xs mt-8">
            Testimonials from real patients. Results may vary based on individual circumstances.
          </p>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section
      id="contact"
      class="py-24 px-4 md:px-6 bg-gradient-to-br from-gray-50 via-white to-gray-50 relative overflow-hidden"
    >
      <!-- Background decorative elements -->
      <div
        class="absolute -top-40 -left-40 w-80 h-80 bg-teal-100/40 rounded-full blur-3xl pointer-events-none"
      ></div>
      <div
        class="absolute -bottom-40 -right-40 w-80 h-80 bg-green-100/40 rounded-full blur-3xl pointer-events-none"
      ></div>

      <div class="container mx-auto max-w-7xl relative z-10">
        <div class="text-center mb-12">
          <p class="text-teal-600 font-semibold text-sm mb-2">Get In Touch</p>
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            We're Here to
            <span
              class="bg-gradient-to-r from-teal-500 to-green-400 bg-clip-text text-transparent"
              >Help You</span
            >
          </h2>
          <p class="text-gray-600 text-sm max-w-2xl mx-auto">
            Have any questions? Reach out to us through any of these channels
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Phone Card -->
          <div class="group fade-in-up">
            <div class="relative h-full">
              <!-- Card glow -->
              <div
                class="absolute inset-0 bg-gradient-to-br from-teal-500/20 to-teal-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-500 pointer-events-none"
              ></div>

              <!-- Card body -->
              <div
                onclick="window.location.href='tel:+919999999999'"
                class="relative bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-lg border border-teal-200/30 text-center hover:shadow-2xl hover:shadow-teal-500/20 transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center cursor-pointer"
              >
                <div class="relative mb-4">
                  <div
                    class="absolute inset-0 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full blur-lg opacity-50"
                  ></div>
                  <div
                    class="relative w-16 h-16 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 text-white flex items-center justify-center shadow-xl"
                  >
                    <i class="fas fa-phone text-2xl"></i>
                  </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Call Us</h3>
                <p class="text-gray-600 mb-4 text-xs">We're available 24/7</p>
                <a
                  href="tel:+919999999999"
                  class="text-teal-600 hover:text-teal-700 font-bold text-lg transition duration-300 group-hover:scale-110"
                  >+91 99999 99999</a
                >
                <p class="text-gray-500 text-xs mt-3">
                  Response time: Less than 5 minutes
                </p>
              </div>
            </div>
          </div>

          <!-- Email Card -->
          <div class="group fade-in-up delay-100">
            <div class="relative h-full">
              <div
                class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-500 pointer-events-none"
              ></div>
              <div
                onclick="window.location.href='mailto:support@drmap.com'"
                class="relative bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-lg border border-blue-200/30 text-center hover:shadow-2xl hover:shadow-blue-500/20 transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center cursor-pointer"
              >
                <div class="relative mb-4">
                  <div
                    class="absolute inset-0 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full blur-lg opacity-50"
                  ></div>
                  <div
                    class="relative w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white flex items-center justify-center shadow-xl"
                  >
                    <i class="fas fa-envelope text-2xl"></i>
                  </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Email Us</h3>
                <p class="text-gray-600 mb-4 text-xs">Send us a message</p>
                <a
                  href="mailto:support@drmap.com"
                  class="text-blue-600 hover:text-blue-700 font-bold text-sm transition duration-300 group-hover:scale-110 break-all"
                  >support@drmap.com</a
                >
                <p class="text-gray-500 text-xs mt-3">
                  Response time: Within 24 hours
                </p>
              </div>
            </div>
          </div>

          <!-- Location Card -->
          <div class="group fade-in-up delay-200">
            <div class="relative h-full">
              <div
                class="absolute inset-0 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-500 pointer-events-none"
              ></div>
              <div
                onclick="window.open('https://www.google.com/maps/search/?api=1&query=123+Health+Street,+Medical+City', '_blank')"
                class="relative bg-white/80 backdrop-blur-md p-6 rounded-2xl shadow-lg border border-green-200/30 text-center hover:shadow-2xl hover:shadow-green-500/20 transition-all duration-300 group-hover:scale-105 transform h-full flex flex-col items-center justify-center cursor-pointer"
              >
                <div class="relative mb-4">
                  <div
                    class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-600 rounded-full blur-lg opacity-50"
                  ></div>
                  <div
                    class="relative w-16 h-16 rounded-full bg-gradient-to-br from-green-500 to-green-600 text-white flex items-center justify-center shadow-xl"
                  >
                    <i class="fas fa-map-marker-alt text-2xl"></i>
                  </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Visit Us</h3>
                <p class="text-gray-600 mb-4 text-xs">Our office address</p>
                <a
                  href="https://www.google.com/maps/search/?api=1&query=123+Health+Street,+Medical+City"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-green-600 hover:text-green-700 font-bold text-sm transition duration-300 group-hover:scale-110"
                  >123 Health Street,<br />Medical City</a
                >
                <p class="text-gray-500 text-xs mt-3">
                  Mon - Fri: 9:00 AM - 6:00 PM
                </p>
              </div>
            </div>
          </div>
        </div>  
      </div>
    </section>

    <!-- Review Modal -->
    <div id="reviewModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Share Your Experience</h3>
            <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 transition duration-300">
              <i class="fas fa-times text-xl"></i>
            </button>
          </div>
          
          <div id="reviewFormContent">
            <p class="text-gray-600 mb-6">We'd love to hear about your experience with our doctors!</p>
            
            <form id="reviewForm" class="space-y-5">
              <!-- Hidden field for doctor_id (will be set dynamically or left as 0 for general review) -->
              <input type="hidden" name="doctor_id" value="0" id="reviewDoctorId">
              
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Your Name *</label>
                <input type="text" name="name" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none transition duration-300"
                       placeholder="John Doe">
              </div>
              
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Your Email *</label>
                <input type="email" name="email" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none transition duration-300"
                       placeholder="john@example.com">
              </div>
              
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Rating *</label>
                <div class="flex items-center space-x-2" id="starRating">
                  <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:scale-110 transition duration-300 star"></i>
                  <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:scale-110 transition duration-300 star"></i>
                  <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:scale-110 transition duration-300 star"></i>
                  <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:scale-110 transition duration-300 star"></i>
                  <i class="far fa-star text-3xl text-gray-300 cursor-pointer hover:scale-110 transition duration-300 star"></i>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
              </div>
              
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Your Review *</label>
                <textarea name="review_text" required rows="4"
                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none transition duration-300 resize-none"
                          placeholder="Tell us about your experience..."></textarea>
              </div>
              
              <div class="flex gap-3">
                <button type="button" onclick="maybeLaterReview()"
                        class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-full hover:bg-gray-50 transition duration-300">
                  Maybe Later
                </button>
                <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 text-white font-semibold rounded-full hover:shadow-lg hover:shadow-teal-500/50 transition duration-300">
                  Submit Review
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden border-t border-slate-800">
      <!-- Decorative Background Elements -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-cyan-500 rounded-full blur-3xl"></div>
      </div>
      
      <!-- Accent Top Border -->
      <div class="h-1 bg-gradient-to-r from-teal-500 via-cyan-400 to-teal-500"></div>
      
      <div class="container mx-auto px-6 py-16 relative z-10">
        <div class="grid md:grid-cols-6 gap-8 mb-12">
          <!-- Brand Section -->
          <div class="md:col-span-1">
            <div class="flex items-center space-x-3 mb-6">
              <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-teal-500/50">
                <i class="fas fa-heartbeat text-xl"></i>
              </div>
              <span class="text-3xl font-bold bg-gradient-to-r from-white to-teal-200 bg-clip-text text-transparent">DrMap</span>
            </div>
            <p class="text-gray-300 text-xs leading-relaxed mb-6">
              Your trusted platform for connecting with verified healthcare professionals. Quality care at your fingertips.
            </p>
            <div class="flex items-center space-x-2 text-teal-400">
              <i class="fas fa-check-circle text-xs"></i>
              <span class="text-xs font-semibold">500+ Verified Doctors</span>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="text-sm font-bold uppercase tracking-wider mb-6 flex items-center text-teal-400">
              <span class="w-1 h-4 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-2"></span>
              Quick Links
            </h4>
            <ul class="space-y-3 text-xs">
              <li>
                <a href="#home" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                  <i class="fas fa-chevron-right text-teal-500 mr-2 text-[10px] group-hover:translate-x-1 transition-transform"></i>
                  Home
                </a>
              </li>
              <li>
                <a href="doctors.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                  <i class="fas fa-chevron-right text-teal-500 mr-2 text-[10px] group-hover:translate-x-1 transition-transform"></i>
                  Find Doctors
                </a>
              </li>
              <li>
                <a href="hospitals.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                  <i class="fas fa-chevron-right text-teal-500 mr-2 text-[10px] group-hover:translate-x-1 transition-transform"></i>
                  Partner Hospitals
                </a>
              </li>
              <li>
                <a href="#about" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                  <i class="fas fa-chevron-right text-teal-500 mr-2 text-[10px] group-hover:translate-x-1 transition-transform"></i>
                  About Us
                </a>
              </li>
              <li>
                <a href="#contact" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                  <i class="fas fa-chevron-right text-teal-500 mr-2 text-[10px] group-hover:translate-x-1 transition-transform"></i>
                  Contact
                </a>
              </li>
            </ul>
          </div>

          <!-- Specialties in 2 columns (Req 3/Req 32) -->
          <div class="md:col-span-2">
            <h4 class="text-sm font-bold uppercase tracking-wider mb-6 flex items-center text-teal-400">
              <span class="w-1 h-4 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-2"></span>
              Specialties
            </h4>
            <ul class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
              <?php 
              // Output first 16 specialties in 2 columns
              $count = 0;
              foreach ($specialtiesList as $spec):
                if ($count++ >= 16) break;
                $specName = $spec['name'];
              ?>
              <li>
                <a href="doctors.php?specialty=<?php echo urlencode($specName); ?>" class="text-gray-300 hover:text-teal-400 transition truncate block">
                  <?php echo htmlspecialchars($specName); ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>

          <!-- Footer Policy Terms (Req 29) -->
          <div>
            <h4 class="text-sm font-bold uppercase tracking-wider mb-6 flex items-center text-teal-400">
              <span class="w-1 h-4 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-2"></span>
              Policy Terms
            </h4>
            <div class="text-[11px] text-gray-400 space-y-3 leading-relaxed">
              <p><strong>1. Data Protection:</strong> Patient records and enquiries are securely stored under medical privacy compliances.</p>
              <p><strong>2. Profile Verification:</strong> All registered clinical data, addresses, and specialties are verified by the administration team.</p>
              <p><strong>3. Consultation Policies:</strong> Digital consults and queries are fallback suggestions and do not replace emergency critical room services.</p>
            </div>
          </div>

          <!-- Connect With Us & helpline (Req 28) -->
          <div>
            <h4 class="text-sm font-bold uppercase tracking-wider mb-6 flex items-center text-teal-400">
              <span class="w-1 h-4 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-2"></span>
              Helplines
            </h4>
            <div class="space-y-4 mb-6 text-xs text-gray-300">
              <div class="flex items-center">
                <i class="fas fa-phone text-teal-400 mr-3"></i>
                <span>+91 99999 99999</span>
              </div>
              <div class="flex items-center">
                <i class="fas fa-envelope text-teal-400 mr-3"></i>
                <span>support@drmap.com</span>
              </div>
            </div>
            <!-- Twitter to X logo (Req 22) -->
            <div class="flex space-x-3">
              <a href="#" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110">
                <i class="fab fa-facebook text-sm"></i>
              </a>
              <a href="#" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110">
                <i class="fab fa-x-twitter text-sm"></i>
              </a>
              <a href="#" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110">
                <i class="fab fa-instagram text-sm"></i>
              </a>
              <a href="#" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-gradient-to-br hover:from-teal-500 hover:to-teal-600 flex items-center justify-center transition-all duration-300 hover:scale-110">
                <i class="fab fa-linkedin text-sm"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-slate-700/50 pt-8">
          <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-gray-400 text-xs">
              &copy; 2026 DrMap. All rights reserved. Made with <i class="fas fa-heart text-teal-400 mx-1"></i> for better healthcare.
            </p>
            <div class="flex items-center space-x-6 text-xs">
              <a href="#" class="text-gray-400 hover:text-teal-400 transition">Privacy Policy</a>
              <span class="text-gray-600">•</span>
              <a href="#" class="text-gray-400 hover:text-teal-400 transition">Terms of Service</a>
              <span class="text-gray-600">•</span>
              <a href="#" class="text-gray-400 hover:text-teal-400 transition">Cookie Policy</a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <script>
      // Preloader functionality
      const preloader = document.getElementById("preloader");

      // Hide preloader when page loads
      window.addEventListener("load", () => {
        setTimeout(() => {
          preloader.classList.add("hidden");
        }, 1000); // Show preloader for at least 1 second for visual appeal
      });

      // Mobile menu toggle
      const mobileMenuBtn = document.getElementById("mobile-menu-btn");
      const mobileMenu = document.getElementById("mobile-menu");

      mobileMenuBtn.addEventListener("click", () => {
        mobileMenu.classList.toggle("hidden");
      });

      // Smooth scroll for navigation links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
          const href = this.getAttribute("href");
          if (href !== "#") {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
              target.scrollIntoView({
                behavior: "smooth",
                block: "start",
              });
              // Close mobile menu if open
              mobileMenu.classList.add("hidden");
            }
          }
        });
      });

      // Form submission handler
      function handleSubmit(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        alert(
          "Request submitted! We will contact you soon at the provided phone number."
        );
        event.target.reset();
      }

      // Testimonials Carousel
      let currentTestimonialIndex = 0;
      const testimonialSlides = document.querySelectorAll(".testimonials-slide");
      const testimonialDots = document.querySelectorAll(".testimonial-dot");
      const testimonialsWrapper = document.getElementById("testimonialsWrapper");
      const totalTestimonials = testimonialSlides.length;
      let autoSlideInterval;

      function updateTestimonialSlide() {
        const offset = -currentTestimonialIndex * 100;
        testimonialsWrapper.style.transform = `translateX(${offset}%)`;

        // Update dots
        testimonialDots.forEach((dot, index) => {
          if (index === currentTestimonialIndex) {
            dot.classList.remove("bg-gray-300", "hover:bg-gray-400");
            dot.classList.add("bg-teal-600");
          } else {
            dot.classList.add("bg-gray-300", "hover:bg-gray-400");
            dot.classList.remove("bg-teal-600");
          }
        });

        // Reset auto-slide timer
        clearInterval(autoSlideInterval);
        startAutoSlide();
      }

      function nextTestimonial() {
        currentTestimonialIndex = (currentTestimonialIndex + 1) % totalTestimonials;
        updateTestimonialSlide();
      }

      function previousTestimonial() {
        currentTestimonialIndex =
          (currentTestimonialIndex - 1 + totalTestimonials) % totalTestimonials;
        updateTestimonialSlide();
      }

      function goToTestimonial(index) {
        currentTestimonialIndex = index;
        updateTestimonialSlide();
      }

      function startAutoSlide() {
        autoSlideInterval = setInterval(() => {
          nextTestimonial();
        }, 5000); // Change testimonial every 5 seconds
      }

      // Initialize carousel
      startAutoSlide();
      
      // ============================================
      // REVIEW MODAL SYSTEM
      // ============================================
      
      // Timer-based review modal with throttling
      let reviewModalInterval = null;
      
      function checkAndShowReviewModal() {
          const hasSubmittedEnquiry = localStorage.getItem('hasSubmittedEnquiry');
          const reviewSubmitted = localStorage.getItem('reviewSubmitted');
          const lastReviewPrompt = localStorage.getItem('lastReviewPrompt');
          const enquiryDoctorId = localStorage.getItem('enquiryDoctorId') || '0';
          const now = Date.now();
          const throttleTime = 5 * 60 * 1000; // Don't show again for 5 minutes after closing
          
          // Only set up timer if user has submitted enquiry and hasn't reviewed yet
          if (hasSubmittedEnquiry && !reviewSubmitted) {
              // Check throttle - don't show if we prompted recently
              if (lastReviewPrompt && (now - parseInt(lastReviewPrompt)) < throttleTime) {
                  return;
              }
              
              // Set the doctor ID in the hidden field
              document.getElementById('reviewDoctorId').value = enquiryDoctorId;
              
              // Show modal after 45 seconds initially
              setTimeout(() => {
                  if (!localStorage.getItem('reviewSubmitted')) {
                      document.getElementById('reviewModal').classList.remove('hidden');
                      localStorage.setItem('lastReviewPrompt', Date.now().toString());
                  }
              }, 45000);
              
              // Then show every 45 seconds if they keep closing it
              reviewModalInterval = setInterval(() => {
                  const currentReviewSubmitted = localStorage.getItem('reviewSubmitted');
                  const currentLastPrompt = localStorage.getItem('lastReviewPrompt');
                  const currentNow = Date.now();
                  
                  // Only show if not submitted and throttle time has passed
                  if (!currentReviewSubmitted && 
                      (!currentLastPrompt || (currentNow - parseInt(currentLastPrompt)) >= throttleTime)) {
                      document.getElementById('reviewModal').classList.remove('hidden');
                      localStorage.setItem('lastReviewPrompt', currentNow.toString());
                  }
              }, 45000);
          }
      }
      
      // Call on page load
      checkAndShowReviewModal();
      
      // Star rating interaction
      const starRating = document.getElementById('starRating');
      const ratingInput = document.getElementById('ratingInput');
      const stars = starRating.querySelectorAll('.star');
      let selectedRating = 0;
      
      stars.forEach((star, index) => {
          star.addEventListener('click', () => {
              selectedRating = index + 1;
              ratingInput.value = selectedRating;
              updateStars(selectedRating);
          });
          
          star.addEventListener('mouseenter', () => {
              updateStars(index + 1, true);
          });
      });
      
      starRating.addEventListener('mouseleave', () => {
          updateStars(selectedRating);
      });
      
      function updateStars(rating, isHover = false) {
          stars.forEach((star, index) => {
              if (index < rating) {
                  star.classList.remove('far');
                  star.classList.add('fas', 'text-yellow-400');
                  if (isHover && index >= selectedRating) {
                      star.style.opacity = '0.7';
                  } else {
                      star.style.opacity = '1';
                  }
              } else {
                  star.classList.remove('fas', 'text-yellow-400');
                  star.classList.add('far', 'text-gray-300');
                  star.style.opacity = '1';
              }
          });
      }
      
      // Close modal functions
      function closeReviewModal() {
          document.getElementById('reviewModal').classList.add('hidden');
          // Update last prompt time to throttle next appearance
          localStorage.setItem('lastReviewPrompt', Date.now().toString());
      }
      
      function maybeLaterReview() {
          closeReviewModal();
      }
      
      // Submit review
      document.getElementById('reviewForm').addEventListener('submit', async (e) => {
          e.preventDefault();
          
          const submitBtn = e.target.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
          
          const formData = new FormData(e.target);
          
          try {
              const response = await fetch('submit_review.php', {
                  method: 'POST',
                  body: formData
              });
              
              const result = await response.json();
              
              if (result.success) {
                  // Mark as submitted
                  localStorage.setItem('reviewSubmitted', 'true');
                  
                  // Stop the review modal interval
                  if (reviewModalInterval) {
                      clearInterval(reviewModalInterval);
                      reviewModalInterval = null;
                  }
                  
                  // Show success message
                  document.getElementById('reviewFormContent').innerHTML = `
                      <div class="text-center py-8">
                          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                              <i class="fas fa-check text-3xl text-green-600"></i>
                          </div>
                          <h3 class="text-2xl font-bold text-gray-900 mb-2">Thank You!</h3>
                          <p class="text-gray-600">${result.message}</p>
                          <button onclick="closeReviewModal()" class="mt-6 px-6 py-2 bg-teal-600 text-white rounded-full hover:bg-teal-700 transition duration-300">
                              Close
                          </button>
                      </div>
                  `;
              } else {
                  alert(result.message);
                  submitBtn.disabled = false;
                  submitBtn.innerHTML = originalText;
              }
          } catch (error) {
              console.error('Error:', error);
              alert('Failed to submit review. Please try again.');
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalText;
          }
      });

      // Specialties Carousel Ticker (Req 17 & Req 26)
      const specTrack = document.getElementById('specialties-slider-track');
      const specContainer = document.getElementById('specialties-slider-container');
      const prevBtn = document.getElementById('spec-prev-btn');
      const nextBtn = document.getElementById('spec-next-btn');

      if (specTrack && specContainer && prevBtn && nextBtn) {
          let scrollAmount = 0;
          const cardWidth = 280; // card width + gap (256 + 24)
          
          function scrollSlider(direction) {
              const maxScroll = specTrack.scrollWidth - specContainer.clientWidth;
              if (maxScroll <= 0) return;
              if (direction === 'next') {
                  scrollAmount += cardWidth;
                  if (scrollAmount > maxScroll) scrollAmount = 0; // wrap around
              } else {
                  scrollAmount -= cardWidth;
                  if (scrollAmount < 0) scrollAmount = maxScroll; // wrap around
              }
              specTrack.style.transform = `translateX(-${scrollAmount}px)`;
          }

          prevBtn.addEventListener('click', () => scrollSlider('prev'));
          nextBtn.addEventListener('click', () => scrollSlider('next'));

          // Auto-scrolling ticker behavior
          let specInterval = setInterval(() => scrollSlider('next'), 4000);
          specContainer.addEventListener('mouseenter', () => clearInterval(specInterval));
          specContainer.addEventListener('mouseleave', () => {
              specInterval = setInterval(() => scrollSlider('next'), 4000);
          });
      }
    </script>
  </body>
</html>

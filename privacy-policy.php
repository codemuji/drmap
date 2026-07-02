<?php
require_once __DIR__ . '/admin/inc/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - DrMap Healthcare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="index.css" />
</head>
<body class="open-sans antialiased bg-slate-50 text-slate-800 flex flex-col min-h-screen">

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
                    <a href="doctors.php" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Doctors</a>
                    <a href="index.php#contact" class="px-4 py-2 text-gray-700 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition duration-300 text-sm font-medium">Contact</a>
                </div>
                <!-- WhatsApp CTA -->
                <div class="flex items-center space-x-3">
                    <a href="https://wa.me/919999999999" target="_blank" class="w-10 h-10 rounded-full bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white text-lg shadow-lg hover:shadow-emerald-500/50 transition duration-300" title="Chat on WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Title Hero -->
    <section class="light-teal-bg pt-28 pb-12 px-4 md:px-6 relative overflow-hidden">
        <div class="container mx-auto max-w-4xl text-center relative z-10">
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-3">Privacy Policy</h1>
            <p class="text-teal-700 text-sm md:text-base font-medium max-w-2xl mx-auto">
                Your trust and privacy are paramount to us. Learn how DrMap collects, protects, and handles your data.
            </p>
            <div class="mt-4 text-xs font-semibold text-teal-600 bg-white/70 inline-block px-4 py-1.5 rounded-full border border-teal-200">
                Last Updated: July 2, 2026
            </div>
        </div>
    </section>

    <!-- Main Content Card -->
    <main class="container mx-auto max-w-4xl px-4 md:px-6 py-10 flex-1">
        <div class="bg-white rounded-3xl p-6 md:p-10 shadow-xl border border-slate-200/80 space-y-8 text-slate-700 text-sm md:text-base leading-relaxed">

            <!-- Introduction -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-shield-halved text-teal-600 mr-2.5"></i> 1. Introduction
                </h2>
                <p>
                    DrMap ("we", "our", or "us") operates the website and healthcare discovery platform located at 
                    <strong class="text-slate-900">drmap.com</strong>. This Privacy Policy outlines our policies regarding the collection, use, 
                    disclosure, and protection of personal data when you use our services. By accessing or using DrMap, you consent to the data practices described in this policy.
                </p>
            </div>

            <!-- Information We Collect -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-database text-teal-600 mr-2.5"></i> 2. Information We Collect
                </h2>
                <p class="mb-3">We collect several types of information to provide and improve our healthcare discovery services:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li><strong class="text-slate-900">Personal Identification Data:</strong> Name, email address, phone number, and city when you submit contact forms, leave reviews, or request appointments.</li>
                    <li><strong class="text-slate-900">Geolocation Data:</strong> With your explicit browser permission, we collect precise latitude and longitude coordinates to connect you with nearby doctors and clinics.</li>
                    <li><strong class="text-slate-900">Usage Data:</strong> Information on browser type, IP address, device model, pages visited, time spent, and referral sources.</li>
                    <li><strong class="text-slate-900">Doctor & Professional Data:</strong> Medical qualifications, council registration IDs, clinic addresses, photo credentials, and operating schedules provided for verification.</li>
                </ul>
            </div>

            <!-- How We Use Your Information -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-gears text-teal-600 mr-2.5"></i> 3. How We Use Your Information
                </h2>
                <ul class="list-disc pl-6 space-y-2">
                    <li>To match you with verified doctors based on your location and medical specialty needs.</li>
                    <li>To facilitate seamless communication between patients, clinics, and healthcare professionals.</li>
                    <li>To improve platform features, user interfaces, and search accuracy.</li>
                    <li>To prevent fraudulent activities and verify doctor credentials against medical registries.</li>
                </ul>
            </div>

            <!-- Geolocation Services -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-location-dot text-teal-600 mr-2.5"></i> 4. Geolocation Services
                </h2>
                <p>
                    Our platform utilizes HTML5 Browser Geolocation to calculate distance to medical practices. 
                    Geolocation access is strictly optional. If you choose not to share your location, you can manually select your city from the dropdown filter without restricting access to any doctor profiles.
                </p>
            </div>

            <!-- Data Security -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-lock text-teal-600 mr-2.5"></i> 5. Data Protection & Security
                </h2>
                <p>
                    We implement industry-standard administrative, technical, and physical security measures (including SSL encryption, secure database access, and regular vulnerability audits) to protect your information against unauthorized access, alteration, or destruction.
                </p>
            </div>

            <!-- Third-Party Services -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-handshake text-teal-600 mr-2.5"></i> 6. Third-Party Service Providers
                </h2>
                <p>
                    DrMap may employ third-party services (such as OpenStreetMap/Leaflet for map routing and Nominatim for reverse geocoding). These third parties have access to your information only to perform specific tasks on our behalf and are obligated not to disclose or use it for any other purpose.
                </p>
            </div>

            <!-- Contact Us -->
            <div class="pt-4 border-t border-slate-200">
                <h2 class="text-xl font-bold text-slate-900 mb-2 flex items-center">
                    <i class="fas fa-envelope text-teal-600 mr-2.5"></i> Contact Our Privacy Team
                </h2>
                <p>If you have questions or concerns regarding this Privacy Policy, please contact us at:</p>
                <div class="mt-3 bg-teal-50 border border-teal-200/80 rounded-xl p-4 text-teal-900 text-sm">
                    <p><strong>Email:</strong> privacy@drmap.com</p>
                    <p><strong>Phone:</strong> +1 (800) 123-4567</p>
                    <p><strong>Address:</strong> DrMap Healthcare Network, Medical Hub Center, Guwahati, Assam, India</p>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden py-10 mt-auto">
        <div class="container mx-auto px-6 max-w-7xl text-center">
            <p class="text-gray-400 text-sm">
                &copy; 2026 DrMap. All rights reserved. Made with <i class="fas fa-heart text-teal-400 mx-1"></i> for better healthcare.
            </p>
            <div class="flex items-center justify-center space-x-6 text-sm mt-4">
                <a href="privacy-policy.php" class="text-teal-400 font-semibold hover:underline">Privacy Policy</a>
                <span class="text-gray-600">•</span>
                <a href="terms-of-service.php" class="text-gray-400 hover:text-teal-400 transition">Terms of Service</a>
                <span class="text-gray-600">•</span>
                <a href="cookie-policy.php" class="text-gray-400 hover:text-teal-400 transition">Cookie Policy</a>
            </div>
        </div>
    </footer>

</body>
</html>

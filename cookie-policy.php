<?php
require_once __DIR__ . '/admin/inc/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Policy - DrMap Healthcare</title>
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
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-3">Cookie Policy</h1>
            <p class="text-teal-700 text-sm md:text-base font-medium max-w-2xl mx-auto">
                Learn how DrMap uses cookies, local storage, and tracking technologies to enhance your search experience.
            </p>
            <div class="mt-4 text-xs font-semibold text-teal-600 bg-white/70 inline-block px-4 py-1.5 rounded-full border border-teal-200">
                Last Updated: July 2, 2026
            </div>
        </div>
    </section>

    <!-- Main Content Card -->
    <main class="container mx-auto max-w-4xl px-4 md:px-6 py-10 flex-1">
        <div class="bg-white rounded-3xl p-6 md:p-10 shadow-xl border border-slate-200/80 space-y-8 text-slate-700 text-sm md:text-base leading-relaxed">

            <!-- What are Cookies -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-cookie-bite text-teal-600 mr-2.5"></i> 1. What Are Cookies?
                </h2>
                <p>
                    Cookies are small text files stored on your computer or mobile device when you visit web pages. They help websites remember your preferences, keep you logged in, and optimize platform speed and user experience.
                </p>
            </div>

            <!-- Types of Cookies We Use -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-layer-group text-teal-600 mr-2.5"></i> 2. How We Use Cookies & Local Storage
                </h2>
                <div class="space-y-4">
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <h3 class="font-bold text-slate-900 mb-1 flex items-center text-sm md:text-base">
                            <i class="fas fa-key text-teal-600 mr-2"></i> Essential System Cookies
                        </h3>
                        <p class="text-xs md:text-sm text-slate-600">
                            Required for core platform navigation, administrative session security (`PHPSESSID`), and preventing cross-site request forgery.
                        </p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <h3 class="font-bold text-slate-900 mb-1 flex items-center text-sm md:text-base">
                            <i class="fas fa-sliders text-teal-600 mr-2"></i> Preference & Filter Storage
                        </h3>
                        <p class="text-xs md:text-sm text-slate-600">
                            Remembers your active city selection (e.g. Guwahati, Delhi), specialty filters, and view preferences so you don't need to re-select them on every visit.
                        </p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <h3 class="font-bold text-slate-900 mb-1 flex items-center text-sm md:text-base">
                            <i class="fas fa-location-arrow text-teal-600 mr-2"></i> Geolocation Storage
                        </h3>
                        <p class="text-xs md:text-sm text-slate-600">
                            Temporarily caches your approved latitude and longitude coordinates to calculate distances to nearby doctor clinics without repeatedly triggering browser prompts.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Managing Cookies -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-toggle-on text-teal-600 mr-2.5"></i> 3. Managing Your Cookie Preferences
                </h2>
                <p class="mb-3">
                    You have the right to accept or decline cookies. Most web browsers automatically accept cookies, but you can usually modify your browser setting to decline cookies if you prefer:
                </p>
                <ul class="list-disc pl-6 space-y-2 text-xs md:text-sm">
                    <li><strong class="text-slate-900">Chrome:</strong> Settings &gt; Privacy and security &gt; Cookies and other site data.</li>
                    <li><strong class="text-slate-900">Firefox:</strong> Options &gt; Privacy &amp; Security &gt; Cookies and Site Data.</li>
                    <li><strong class="text-slate-900">Safari:</strong> Preferences &gt; Privacy &gt; Block all cookies.</li>
                </ul>
            </div>

            <!-- Contact Information -->
            <div class="pt-4 border-t border-slate-200">
                <h2 class="text-xl font-bold text-slate-900 mb-2 flex items-center">
                    <i class="fas fa-circle-info text-teal-600 mr-2.5"></i> Further Assistance
                </h2>
                <p class="text-sm">If you have questions about our Cookie Policy, please contact our technical team at <strong class="text-teal-700">support@drmap.com</strong>.</p>
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
                <a href="privacy-policy.php" class="text-gray-400 hover:text-teal-400 transition">Privacy Policy</a>
                <span class="text-gray-600">•</span>
                <a href="terms-of-service.php" class="text-gray-400 hover:text-teal-400 transition">Terms of Service</a>
                <span class="text-gray-600">•</span>
                <a href="cookie-policy.php" class="text-teal-400 font-semibold hover:underline">Cookie Policy</a>
            </div>
        </div>
    </footer>

</body>
</html>

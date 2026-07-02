<?php
require_once __DIR__ . '/admin/inc/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - DrMap Healthcare</title>
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
            <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-3">Terms of Service</h1>
            <p class="text-teal-700 text-sm md:text-base font-medium max-w-2xl mx-auto">
                Please read these terms carefully before accessing or using the DrMap healthcare platform.
            </p>
            <div class="mt-4 text-xs font-semibold text-teal-600 bg-white/70 inline-block px-4 py-1.5 rounded-full border border-teal-200">
                Effective Date: July 2, 2026
            </div>
        </div>
    </section>

    <!-- Main Content Card -->
    <main class="container mx-auto max-w-4xl px-4 md:px-6 py-10 flex-1">
        <div class="bg-white rounded-3xl p-6 md:p-10 shadow-xl border border-slate-200/80 space-y-8 text-slate-700 text-sm md:text-base leading-relaxed">

            <!-- Acceptance of Terms -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-file-contract text-teal-600 mr-2.5"></i> 1. Acceptance of Terms
                </h2>
                <p>
                    By creating an account, browsing doctor profiles, or using any service provided by DrMap, you agree to be bound by these Terms of Service. If you do not agree to all of these terms, you must discontinue platform usage immediately.
                </p>
            </div>

            <!-- Medical Disclaimer -->
            <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-2xl text-amber-900 text-sm">
                <h3 class="font-bold text-base mb-1 flex items-center">
                    <i class="fas fa-triangle-exclamation mr-2 text-amber-600"></i> Important Medical Emergency Notice
                </h3>
                <p>
                    DrMap is an information directory and doctor discovery portal. <strong>DrMap does not provide emergency medical treatment or emergency dispatch.</strong> If you are experiencing a life-threatening medical emergency, call emergency medical services (such as 108/911) or visit the nearest hospital emergency room immediately.
                </p>
            </div>

            <!-- Description of Services -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-stethoscope text-teal-600 mr-2.5"></i> 2. Description of Services
                </h2>
                <p class="mb-3">DrMap provides users with access to a digital directory of verified doctors, clinics, and hospitals, enabling users to:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Search doctors by specialty, practice city, and live geolocation distance.</li>
                    <li>View doctor qualifications, experience, operating hours, and verified clinic locations.</li>
                    <li>Access educational videos, patient reviews, and clinic contact information.</li>
                </ul>
            </div>

            <!-- User Conduct & Accounts -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-user-check text-teal-600 mr-2.5"></i> 3. User Responsibilities & Conduct
                </h2>
                <p class="mb-3">When using DrMap, you agree that you will not:</p>
                <ul class="list-disc pl-6 space-y-2">
                    <li>Submit false or misleading reviews or doctor information.</li>
                    <li>Attempt to gain unauthorized access to our administrative panel or database servers.</li>
                    <li>Scrape, duplicate, or re-publish doctor directory data without express written permission.</li>
                </ul>
            </div>

            <!-- Doctor Verification & Content Accuracy -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-user-doctor text-teal-600 mr-2.5"></i> 4. Doctor Verification & Listings
                </h2>
                <p>
                    While DrMap undertakes diligent efforts to verify credentials and licenses for doctors listed on the platform, doctor availability, consultation fees, and clinic schedules are managed dynamically by doctors and healthcare institutions. We strongly recommend verifying emergency availability directly with clinic staff before travel.
                </p>
            </div>

            <!-- Limitation of Liability -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-scale-balanced text-teal-600 mr-2.5"></i> 5. Limitation of Liability
                </h2>
                <p>
                    To the maximum extent permitted by law, DrMap and its operators shall not be liable for any indirect, incidental, special, or consequential damages resulting from your reliance on doctor listing information or third-party clinic services.
                </p>
            </div>

            <!-- Governing Law & Modifications -->
            <div>
                <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center">
                    <i class="fas fa-gavel text-teal-600 mr-2.5"></i> 6. Governing Law & Amendments
                </h2>
                <p>
                    These terms are governed by and construed in accordance with applicable medical directory laws. DrMap reserves the right to modify these terms at any time. Continued use of the service following updates constitutes acceptance of revised terms.
                </p>
            </div>

            <!-- Contact Information -->
            <div class="pt-4 border-t border-slate-200">
                <h2 class="text-xl font-bold text-slate-900 mb-2 flex items-center">
                    <i class="fas fa-headset text-teal-600 mr-2.5"></i> Questions About Terms?
                </h2>
                <p class="text-sm">For legal or terms inquiries, contact our legal department at <strong class="text-teal-700">legal@drmap.com</strong>.</p>
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
                <a href="terms-of-service.php" class="text-teal-400 font-semibold hover:underline">Terms of Service</a>
                <span class="text-gray-600">•</span>
                <a href="cookie-policy.php" class="text-gray-400 hover:text-teal-400 transition">Cookie Policy</a>
            </div>
        </div>
    </footer>

</body>
</html>

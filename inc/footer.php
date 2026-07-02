<!-- Footer (included) -->
<footer class="relative overflow-hidden py-12 px-6 mt-12 text-white" style="background: linear-gradient(315deg, #4FBED1 0%, #2AA6BB 50%, #0B6B81 100%);">
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-white/8 rounded-full blur-3xl pointer-events-none"></div>

    <div class="container mx-auto max-w-7xl relative z-10">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <div class="space-y-3">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <div>
                        <span id="footer-brand-title" class="text-2xl font-bold">DrMap</span>
                        <p id="footer-brand-subtitle" class="text-xs text-white/80">Healthcare Platform</p>
                    </div>
                </div>
                <p class="text-sm text-white/80 max-w-sm">Your trusted platform for connecting with verified healthcare professionals and compassionate care.</p>

                <!-- Dynamic Doctor Info (shown on doctor profile pages) -->
                <div id="footer-doctor-info" class="hidden mt-4 bg-white/5 p-3 rounded-lg border border-white/10">
                    <h4 id="footer-doctor-name" class="text-base font-bold text-white mb-0">Doctor Name</h4>
                    <p id="footer-doctor-specialty" class="text-xs text-white/80 mb-2">Specialty</p>
                    <p class="text-xs text-white/80">Call: <a id="footer-doctor-phone" href="tel:0000000000" class="font-semibold text-white/90">000-000-0000</a></p>
                    <a id="footer-doctor-whatsapp" href="#" class="mt-2 inline-block text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1 rounded-full hidden">Message on WhatsApp</a>
                </div>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 md:p-5 border border-white/10">
                <h4 class="text-lg font-bold mb-3">Quick Links</h4>
                <ul id="footer-quick-links" class="space-y-2 text-sm">
                    <li><a href="index.php" class="text-white/90 hover:text-white transition">Home</a></li>
                    <li><a href="doctors.php" class="text-white/90 hover:text-white transition">Doctors</a></li>
                    <li><a href="index.php#contact" class="text-white/90 hover:text-white transition">Contact</a></li>
                </ul>
            </div>

            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 md:p-5 border border-white/10">
                <h4 class="text-lg font-bold mb-3">About</h4>
                <div id="footer-related-info" class="text-sm text-white/90">
                    <p id="footer-related-text" class="mb-2">We connect you with trusted healthcare professionals.</p>
                    <a id="footer-related-link" href="#profile" class="text-xs underline text-white/90">View profile</a>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-lg font-bold mb-2">Follow Us</h4>
                <div class="flex items-center space-x-3">
                    <a href="#" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white hover:scale-105 transition"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white hover:scale-105 transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white hover:scale-105 transition"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white hover:scale-105 transition"><i class="fab fa-linkedin"></i></a>
                </div>

                <div class="mt-4 bg-white/10 backdrop-blur-sm rounded-2xl p-3 border border-white/10">
                    <p class="text-xs text-white/80">Need help? <a href="index.php#contact" class="underline">Contact us</a> or call <a href="tel:0000000000" class="font-semibold">000-000-0000</a></p>
                </div>
            </div>
        </div>

        <div class="border-t border-white/20 pt-6 text-sm text-white/80 flex flex-col md:flex-row items-center justify-between gap-3">
            <p>&copy; 2024 DrMap. All rights reserved.</p>
            <p class="text-xs">Designed for secure, patient-first healthcare experiences.</p>
        </div>
    </div>
</footer>

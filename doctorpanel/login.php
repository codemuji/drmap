<?php
require_once __DIR__ . '/inc/auth.php';

if (is_doctor_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (login_doctor($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DrMap - Doctor Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * { font-family: 'Inter', system-ui, sans-serif; }

        /* Keep the admin styles used for the premium login UI */
        /* Animated Background, Orbs, Glass card and inputs (trimmed for brevity) */
        .animated-bg { background: linear-gradient(-45deg, #0f172a, #1e3a5f, #0c4a6e, #164e63, #0f172a); background-size:400% 400%; animation: gradientShift 15s ease infinite; }
        @keyframes gradientShift { 0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%} }
        .orb { position:absolute; border-radius:50%; filter: blur(80px); opacity:0.5; animation: float 20s ease-in-out infinite; }
        .orb-1{width:600px;height:600px;background:linear-gradient(135deg,#0ea5e9,#06b6d4);top:-200px;left:-200px}
        .orb-2{width:500px;height:500px;background:linear-gradient(135deg,#8b5cf6,#a855f7);bottom:-150px;right:-150px}
        .orb-3{width:400px;height:400px;background:linear-gradient(135deg,#10b981,#14b8a6);top:50%;left:50%;transform:translate(-50%,-50%)}
        @keyframes float{0%,100%{transform:translate(0,0) scale(1)}25%{transform:translate(50px,-50px) scale(1.1)}50%{transform:translate(-30px,30px) scale(0.95)}75%{transform:translate(40px,40px) scale(1.05)}}
        .grid-pattern{background-image:linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);background-size:50px 50px}
        .noise{position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;opacity:0.02;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")}
        .glass-card{background:rgba(255,255,255,0.95);backdrop-filter:blur(40px);-webkit-backdrop-filter:blur(40px);border:1px solid rgba(255,255,255,0.2)}
        .glass-dark{background:rgba(15,23,42,0.6);backdrop-filter:blur(40px);-webkit-backdrop-filter:blur(40px);border:1px solid rgba(255,255,255,0.1)}
        .input-premium{transition:all .3s cubic-bezier(.4,0,.2,1)}.input-premium:focus{border-color:#0ea5e9;box-shadow:0 0 0 4px rgba(14,165,233,.1),0 1px 2px rgba(0,0,0,.05);outline:none}
        .input-group:focus-within .input-icon{color:#0ea5e9}.input-group:focus-within label{color:#0ea5e9}
        .floating-label{position:absolute;left:48px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none;transition:all .3s;font-size:15px;font-weight:500}
        .floating-input:focus ~ .floating-label,.floating-input:not(:placeholder-shown) ~ .floating-label{top:0;left:12px;transform:translateY(-50%) scale(.85);background:white;padding:0 8px;color:#0ea5e9;font-weight:600}
        .btn-primary{background:linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%);box-shadow:0 4px 15px rgba(14,165,233,.4),0 1px 3px rgba(0,0,0,.1);transition:all .3s;position:relative;overflow:hidden}
        .btn-primary::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);transition:left .5s}
        .btn-primary:hover::before{left:100%}.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(14,165,233,.5),0 4px 10px rgba(0,0,0,.1)}
        .checkbox-premium{appearance:none;width:20px;height:20px;border:2px solid #cbd5e1;border-radius:6px;cursor:pointer;transition:all .2s;position:relative;flex-shrink:0}
        .checkbox-premium:checked{background:linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%);border-color:#0ea5e9}
        .checkbox-premium:checked::after{content:'';position:absolute;left:6px;top:2px;width:5px;height:10px;border:solid white;border-width:0 2px 2px 0;transform:rotate(45deg)}
        .password-toggle{transition:all .2s}.password-toggle:hover{color:#0ea5e9}
        .divider{display:flex;align-items:center;gap:16px}.divider::before,.divider::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent)}
        .slide-in{animation:slideIn .8s cubic-bezier(.16,1,.3,1) forwards;opacity:0}@keyframes slideIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        .slide-in-delay-1{animation-delay:.1s}.slide-in-delay-2{animation-delay:.2s}.slide-in-delay-3{animation-delay:.3s}.slide-in-delay-4{animation-delay:.4s}.slide-in-delay-5{animation-delay:.5s}
        .spinner{width:20px;height:20px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .8s linear infinite}@keyframes spin{to{transform:rotate(360deg)}}
        .input-error{border-color:#ef4444 !important;animation:inputShake .4s ease}@keyframes inputShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
        .glow{box-shadow:0 0 60px rgba(14,165,233,.3)}
    </style>
</head>

<body class="min-h-screen antialiased">

    <!-- Background -->
    <div class="fixed inset-0 animated-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="absolute inset-0 grid-pattern"></div>
        <div class="noise"></div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
            <div class="particle" style="left: 20%; animation-delay: 2s;"></div>
            <div class="particle" style="left: 30%; animation-delay: 4s;"></div>
            <div class="particle" style="left: 40%; animation-delay: 1s;"></div>
            <div class="particle" style="left: 50%; animation-delay: 3s;"></div>
            <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
            <div class="particle" style="left: 70%; animation-delay: 2.5s;"></div>
            <div class="particle" style="left: 80%; animation-delay: 1.5s;"></div>
            <div class="particle" style="left: 90%; animation-delay: 3.5s;"></div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="relative min-h-screen flex">
        
        <!-- Left Side - Branding & Features (hidden on smaller screens) -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-[55%] flex-col justify-between p-12 xl:p-16">
            <div class="flex items-center justify-between slide-in">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/30 logo-icon">
                            <i class="fa-solid fa-heart-pulse text-white text-xl"></i>
                        </div>
                        <div class="pulse-ring rounded-2xl"></div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight">DrMap</h1>
                        <p class="text-xs text-primary-300 font-medium">Healthcare Platform</p>
                    </div>
                </div>
            </div>

            <div class="max-w-xl">
                <div class="slide-in slide-in-delay-1">
                    <span class="inline-flex items-center px-4 py-1.5 rounded-full bg-white/10 border border-white/20 text-sm font-medium text-white mb-6">
                        <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                        Trusted by 10,000+ Healthcare Professionals
                    </span>
                </div>
                
                <h2 class="text-4xl xl:text-5xl font-bold text-white leading-tight mb-6 slide-in slide-in-delay-2">
                    Transform Your <br>
                    <span class="bg-gradient-to-r from-primary-400 via-cyan-300 to-emerald-400 bg-clip-text text-transparent">
                        Healthcare Practice
                    </span>
                </h2>
                
                <p class="text-lg text-white/70 leading-relaxed mb-10 slide-in slide-in-delay-3">
                    The most advanced healthcare management platform designed for modern medical professionals. Streamline operations, enhance patient care, and grow your practice.
                </p>
            </div>

            <div class="slide-in slide-in-delay-5">
                <div class="flex items-center space-x-10 mb-8">
                    <div class="stat-item" style="animation-delay: 0.6s">
                        <p class="text-3xl font-bold text-white">50K+</p>
                        <p class="text-sm text-white/60">Active Doctors</p>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div class="stat-item" style="animation-delay: 0.7s">
                        <p class="text-3xl font-bold text-white">2M+</p>
                        <p class="text-sm text-white/60">Patients Served</p>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div class="stat-item" style="animation-delay: 0.8s">
                        <p class="text-3xl font-bold text-white">99.9%</p>
                        <p class="text-sm text-white/60">Uptime SLA</p>
                    </div>
                </div>

                <div class="glass-dark rounded-2xl p-6">
                    <div class="flex items-start space-x-4">
                        <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=100&h=100&fit=crop&crop=face" 
                             alt="Dr. Sarah" class="w-12 h-12 rounded-xl object-cover ring-2 ring-white/20">
                        <div>
                            <p class="text-white/80 italic mb-3">"DrMap has revolutionized how we manage our clinic. The efficiency gains are remarkable, and our patients love the seamless experience."</p>
                            <p class="font-semibold text-white">Dr. Sarah Mitchell</p>
                            <p class="text-sm text-white/60">Chief Medical Officer, HealthFirst</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-6 mt-6 trust-badge">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google" class="h-6 opacity-50 grayscale">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="Amazon" class="h-5 opacity-50 grayscale">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/44/Microsoft_logo.svg" alt="Microsoft" class="h-5 opacity-50 grayscale">
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 xl:w-[45%] flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                
                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center justify-center mb-8 slide-in">
                    <div class="flex items-center space-x-3">
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-heart-pulse text-white text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white">DrMap</h1>
                            <p class="text-xs text-primary-300">Doctor Panel</p>
                        </div>
                    </div>
                </div>

                <!-- Login Card -->
                <div class="glass-card rounded-3xl p-8 lg:p-10 shadow-2xl glow slide-in slide-in-delay-1">
                    
                    <!-- Header -->
                    <div class="text-center mb-8">
                        <h2 class="text-2xl lg:text-3xl font-bold text-dark-900 mb-2">Welcome back, Doctor</h2>
                        <p class="text-dark-500">Sign in to the Doctor Panel</p>
                    </div>

                    <!-- Login Form -->
                    <form id="loginForm" method="POST" class="space-y-5">
                        <!-- Email Input -->
                        <div class="input-group relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                <i class="fa-solid fa-envelope input-icon transition-colors"></i>
                            </div>
                            <input type="email" id="email" name="email" required placeholder=" "
                                class="floating-input w-full pl-12 pr-4 py-4 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                            <label class="floating-label">Email address</label>
                        </div>

                        <!-- Password Input -->
                        <div class="input-group relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-dark-400 z-10">
                                <i class="fa-solid fa-lock input-icon transition-colors"></i>
                            </div>
                            <input type="password" id="password" name="password" required placeholder=" "
                                class="floating-input w-full pl-12 pr-12 py-4 bg-dark-50 border-2 border-dark-200 rounded-xl input-premium text-dark-800 font-medium">
                            <label class="floating-label">Password</label>
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-400 password-toggle">
                                <i id="passwordIcon" class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center space-x-3 cursor-pointer group">
                                <input type="checkbox" class="checkbox-premium" id="remember">
                                <span class="text-sm text-dark-600 group-hover:text-dark-800 transition-colors">Remember me</span>
                            </label>
                            <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 transition-colors link-hover">
                                Forgot password?
                            </a>
                        </div>

                        <!-- Error Message -->
                        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                                </div>
                                <p class="text-sm text-red-700 font-medium" id="errorText">Invalid email or password</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn" class="btn-primary w-full py-4 rounded-xl font-semibold text-white text-base flex items-center justify-center space-x-2">
                            <span id="btnText">Sign in to Doctor Panel</span>
                            <i id="btnArrow" class="fa-solid fa-arrow-right"></i>
                            <div id="btnSpinner" class="spinner hidden"></div>
                        </button>
                    </form>

                    <!-- Sign Up Link -->
                    <p class="text-center text-dark-500 mt-6">
                        Don't have an account? 
                        <a href="#" class="font-semibold text-primary-600 hover:text-primary-700 transition-colors link-hover">
                            Request access
                        </a>
                    </p>

                    <!-- Security Badge -->
                    <div class="flex items-center justify-center space-x-2 mt-6 pt-6 border-t border-dark-100">
                        <i class="fa-solid fa-shield-halved text-emerald-500"></i>
                        <span class="text-xs text-dark-400">Protected by 256-bit SSL encryption</span>
                    </div>
                </div>

                <!-- Footer Links -->
                <div class="flex items-center justify-center space-x-6 mt-6">
                    <a href="#" class="text-sm text-white/60 hover:text-white transition-colors">Privacy Policy</a>
                    <span class="text-white/30">•</span>
                    <a href="#" class="text-sm text-white/60 hover:text-white transition-colors">Terms of Service</a>
                    <span class="text-white/30">•</span>
                    <a href="#" class="text-sm text-white/60 hover:text-white transition-colors">Help</a>
                </div>

                <!-- Copyright -->
                <p class="text-center text-white/40 text-xs mt-4">
                    © 2024 DrMap Healthcare. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <!-- Success Modal (kept from admin UI) -->
    <div id="successModal" class="fixed inset-0 bg-dark-900/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl transform scale-95 opacity-0 transition-all duration-300" id="successContent">
            <div class="w-20 h-20 mx-auto mb-6 relative">
                <svg class="w-20 h-20" viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" stroke="#10b981" stroke-width="2"/>
                    <path class="checkmark-check" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 16-16"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-dark-900 mb-2">Welcome back!</h3>
            <p class="text-dark-500 mb-6">Login successful. Redirecting to your dashboard...</p>
            <div class="flex items-center justify-center space-x-2">
                <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                <div class="w-2 h-2 bg-primary-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
            </div>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        function togglePassword() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            const icon = document.getElementById('passwordIcon');
            if (icon) icon.className = type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }

        function showError(msg) {
            if (!errorMessage || !errorText) return;
            errorText.textContent = msg;
            errorMessage.classList.remove('hidden');
            emailInput.classList.add('input-error');
            passwordInput.classList.add('input-error');
        }

        function hideError() {
            if (!errorMessage) return;
            errorMessage.classList.add('hidden');
            emailInput.classList.remove('input-error');
            passwordInput.classList.remove('input-error');
        }

        [emailInput, passwordInput].forEach(i => i && i.addEventListener('focus', hideError));

        const serverErrorElem = document.getElementById('serverError');
        if (serverErrorElem && serverErrorElem.dataset && serverErrorElem.dataset.msg) {
            showError(serverErrorElem.dataset.msg);
        }
    </script>
    <?php if (!empty($error)): ?>
    <div id="serverError" data-msg="<?= htmlspecialchars($error, ENT_QUOTES) ?>" style="display:none"></div>
    <?php endif; ?>
</body>
</html>

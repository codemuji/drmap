<?php
// Fetch doctor data from database
require_once __DIR__ . '/admin/inc/db.php';

$doctor_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$doctor_id) {
    header('Location: doctors.php');
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = ? AND status = "active" LIMIT 1');
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('HTTP/1.1 404 Not Found');
    exit('Doctor not found or is currently unavailable.');
}

// Fetch approved reviews for this doctor
$reviewsStmt = $pdo->prepare('SELECT * FROM reviews WHERE doctor_id = ? AND status = "approved" ORDER BY created_at DESC LIMIT 50');
$reviewsStmt->execute([$doctor_id]);
$approvedReviews = $reviewsStmt->fetchAll();

// Calculate average rating and satisfaction from approved reviews
$avgRating = 0;
$satisfactionRate = 100; // Default to 100% when no reviews
$totalReviews = count($approvedReviews);
if ($totalReviews > 0) {
    $sumRating = array_sum(array_column($approvedReviews, 'rating'));
    $avgRating = round($sumRating / $totalReviews, 1);
    
    // Calculate satisfaction as percentage of 4-5 star reviews
    $satisfiedCount = count(array_filter($approvedReviews, function($review) {
        return $review['rating'] >= 4;
    }));
    $satisfactionRate = round(($satisfiedCount / $totalReviews) * 100);
}

// Query suggested doctors from the same specialty (Req 14)
$suggestedStmt = $pdo->prepare('
    SELECT id, name, specialty, qualification, experience, photo 
    FROM doctors 
    WHERE status = "active" AND id != ? AND specialty = ? 
    LIMIT 3
');
$suggestedStmt->execute([$doctor_id, $doctor['specialty']]);
$suggestedDoctors = $suggestedStmt->fetchAll();

if (count($suggestedDoctors) < 3) {
    $needed = 3 - count($suggestedDoctors);
    $placeholders = count($suggestedDoctors) > 0 ? implode(',', array_column($suggestedDoctors, 'id')) : '0';
    $anyStmt = $pdo->prepare("
        SELECT id, name, specialty, qualification, experience, photo 
        FROM doctors 
        WHERE status = 'active' AND id != ? AND id NOT IN ($placeholders) 
        LIMIT $needed
    ");
    $anyStmt->execute([$doctor_id]);
    $extraDoctors = $anyStmt->fetchAll();
    $suggestedDoctors = array_merge($suggestedDoctors, $extraDoctors);
}

// Decode social links
$social = json_decode($doctor['social'] ?? '{}', true);
$doctorName = htmlspecialchars($doctor['name']);
$doctorSpecialty = htmlspecialchars($doctor['specialty']);
$doctorQualification = htmlspecialchars($doctor['qualification']);
$doctorAbout = htmlspecialchars($doctor['about']);
$doctorSpeech = htmlspecialchars($doctor['speech']);
$doctorPhoto = htmlspecialchars($doctor['photo']);
$doctorEmail = htmlspecialchars($doctor['email']);
$doctorPhone = htmlspecialchars($doctor['phone']);
$doctorWhatsapp = htmlspecialchars($doctor['whatsapp']);
$doctorTiming = htmlspecialchars($doctor['timing']);
$doctorExperience = intval($doctor['experience']);
$doctorRating = floatval($doctor['rating']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $doctorName; ?> - <?php echo $doctorSpecialty; ?> | DrMap</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 2s infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        shimmer: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.95)' },
                            '100%': { opacity: '1', transform: 'scale(1)' },
                        },
                    },
                }
            }
        }
    </script>
    
    <style>
        /* Base Styles */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            letter-spacing: -0.025em;
            overflow-x: hidden;
        }
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            letter-spacing: -0.035em;
            font-weight: 700;
        }
        
        h1 { letter-spacing: -0.045em; }
        
        p {
            line-height: 1.7;
            color: #475569;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #14b8a6, #0d9488);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #0d9488, #0f766e);
        }
        
        /* Glass Effect */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .glass-dark {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card:hover {
            border-color: #14b8a6;
            box-shadow: 0 20px 40px -12px rgba(20, 184, 166, 0.15);
            transform: translateY(-2px);
        }
        
        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        .btn-primary:hover {
            box-shadow: 0 10px 30px -5px rgba(20, 184, 166, 0.5);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: white;
            color: #0f766e;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-secondary:hover {
            border-color: #14b8a6;
            background: #f0fdfa;
            transform: translateY(-2px);
        }
        
        /* Badge Styles */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        
        .badge-primary {
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: white;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
        }
        
        /* Section Divider */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e2e8f0 20%, #e2e8f0 80%, transparent);
        }
        
        /* Hero Background Pattern */
        .hero-pattern {
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(20, 184, 166, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 40% 80%, rgba(13, 148, 136, 0.05) 0%, transparent 45%);
        }
        
        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        /* Floating Elements Animation */
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-delayed {
            animation: float 6s ease-in-out 2s infinite;
        }
        
        /* Focus States */
        *:focus-visible {
            outline: 2px solid #14b8a6;
            outline-offset: 2px;
        }
        
        /* Image Aspect Ratio Fix */
        .aspect-photo {
            aspect-ratio: 1/1;
            object-fit: cover;
        }
        
        /* Carousel Styles */
        .carousel-container {
            overflow: hidden;
            position: relative;
        }
        
        .carousel-track {
            display: flex;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .carousel-slide {
            flex-shrink: 0;
        }
        
        /* Video Card Overlay */
        .video-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
        }
        
        /* Review Card */
        .review-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            border: 1px solid #f1f5f9;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .review-card:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            transform: translateY(-4px);
        }
        
        /* Modal Backdrop */
        .modal-backdrop {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(8px);
        }
        
        /* Stat Card Gradient Borders */
        .stat-card {
            position: relative;
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #14b8a6, #06b6d4, #14b8a6);
        }
        
        /* Navigation Active State */
        .nav-link {
            position: relative;
            color: #475569;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .nav-link:hover {
            color: #0d9488;
            background: #f0fdfa;
        }
        
        .nav-link.active {
            color: #0d9488;
            background: #f0fdfa;
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 2px;
            background: #14b8a6;
            border-radius: 1px;
        }
        
        /* Icon Box */
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .icon-box-sm {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            font-size: 16px;
        }
        
        /* Pulse Dot */
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            position: relative;
        }
        
        .pulse-dot::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: #10b981;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }
        
        /* Input Styles */
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        
        .form-input:focus {
            border-color: #14b8a6;
            background: white;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
        }
        
        .form-input::placeholder {
            color: #94a3b8;
        }
        
        /* Section Header */
        .section-header {
            margin-bottom: 48px;
        }
        
        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            color: #0d9488;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
        }
        
        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 12px;
        }
        
        @media (min-width: 768px) {
            .section-title {
                font-size: 40px;
            }
        }
        
        .section-subtitle {
            font-size: 16px;
            color: #64748b;
            max-width: 600px;
        }

        /* Confetti Animation */
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotateZ(720deg);
                opacity: 0;
            }
        }

        @keyframes confetti-spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .confetti-piece {
            position: fixed;
            width: 8px;
            height: 8px;
            pointer-events: none;
            animation: confetti-fall 3s ease-in forwards;
        }

        .confetti-piece.spin {
            animation: confetti-spin 0.5s ease-out, confetti-fall 3s ease-in forwards;
        }

        /* Heart ECG preloader animations (Req 13) */
        .preloader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #0f172a !important; /* Force Slate-900 */
            z-index: 99999;
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
<body class="bg-white">
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
    <header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="mx-4 mt-4">
            <nav class="glass rounded-2xl shadow-lg shadow-black/5 border border-white/50 max-w-7xl mx-auto">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <!-- Logo -->
                        <a href="doctor-profile.php?id=<?php echo $doctor['id']; ?>" class="flex items-center gap-3 group">
                            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white shadow-lg shadow-primary-500/30 group-hover:shadow-primary-500/50 transition-shadow">
                                <i class="fas fa-heartbeat text-lg"></i>
                            </div>
                            <div class="hidden sm:block">
                                <span id="navbar-doctor-name" class="text-xl font-bold gradient-text"><?php echo $doctorName; ?></span>
                                <p class="text-[11px] text-slate-500 font-medium -mt-0.5">Doctor Directory</p>
                            </div>
                        </a>
                        
                        <!-- Desktop Navigation -->
                        <div class="hidden lg:flex items-center gap-1">
                            <a href="#profile" class="nav-link">Profile</a>
                            <a href="#credentials-section" class="nav-link">Credentials</a>
                            <a href="#why-choose-section" class="nav-link">About</a>
                            <a href="#videos-section" class="nav-link">Videos</a>
                            <a href="#gallery-section" class="nav-link">Gallery</a>
                            <a href="#reviews-section" class="nav-link">Reviews</a>
                        </div>
                        
                        <!-- CTA + Mobile Menu (Req 11) -->
                        <div class="flex items-center gap-3">
                            <!-- Whatsapp message logo link (Req 32) -->
                            <button 
                              onclick="openBookingModal(true)" 
                              class="w-10 h-10 rounded-xl bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white text-lg shadow-lg hover:shadow-emerald-500/50 transition duration-300"
                              title="Chat on WhatsApp"
                            >
                              <i class="fab fa-whatsapp"></i>
                            </button>
                            <button onclick="openBookingModal()" class="hidden sm:flex btn-primary text-sm py-2.5 px-5">
                                <i class="fas fa-calendar-plus mr-2"></i>
                                Book Now
                            </button>
                            
                            <button id="mobile-menu-btn" class="lg:hidden w-10 h-10 rounded-xl hover:bg-slate-100 flex items-center justify-center transition">
                                <i class="fas fa-bars text-slate-600 text-lg"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Mobile Menu -->
                    <div id="mobile-menu" class="hidden lg:hidden pt-4 pb-2 border-t border-slate-100 mt-4">
                        <div class="flex flex-col gap-1">
                            <a href="#profile" class="nav-link">Profile</a>
                            <a href="#credentials-section" class="nav-link">Credentials</a>
                            <a href="#why-choose-section" class="nav-link">About</a>
                            <a href="#videos-section" class="nav-link">Videos</a>
                            <a href="#gallery-section" class="nav-link">Gallery</a>
                            <a href="#reviews-section" class="nav-link">Reviews</a>
                            <button onclick="openBookingModal()" class="btn-primary text-sm py-3 mt-2">
                                <i class="fas fa-calendar-plus mr-2"></i>
                                Book Appointment
                            </button>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="profile" class="relative min-h-[85vh] pt-28 pb-16 px-4 overflow-hidden hero-pattern">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Gradient Orbs -->
            <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full bg-gradient-to-br from-primary-200/40 to-cyan-200/30 blur-3xl floating"></div>
            <div class="absolute -bottom-40 -left-40 w-[400px] h-[400px] rounded-full bg-gradient-to-tr from-primary-300/30 to-emerald-200/20 blur-3xl floating-delayed"></div>
            
            <!-- Decorative Elements -->
            <div class="absolute top-1/4 right-1/4 w-2 h-2 rounded-full bg-primary-400 floating"></div>
            <div class="absolute top-1/3 left-1/4 w-3 h-3 rounded-full bg-cyan-400/60 floating-delayed"></div>
            <div class="absolute bottom-1/4 right-1/3 w-2.5 h-2.5 rounded-full bg-emerald-400/50 floating"></div>
            
            <!-- Grid Pattern -->
            <div class="absolute inset-0 opacity-[0.02]" style="background-image: radial-gradient(#0d9488 1px, transparent 1px); background-size: 40px 40px;"></div>
        </div>
        
        <div class="container mx-auto max-w-7xl relative z-10">
            <!-- Back Button -->
            <a href="doctors.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-primary-600 transition-colors mb-5 text-sm font-medium group">
                <span class="w-8 h-8 rounded-lg bg-white shadow-sm border border-slate-200 flex items-center justify-center group-hover:border-primary-300 group-hover:bg-primary-50 transition-all">
                    <i class="fas fa-arrow-left text-xs group-hover:-translate-x-0.5 transition-transform"></i>
                </span>
                Back to Doctors
            </a>
            
            <!-- Main Content Grid -->
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-start">
                <!-- Left Column - Info -->
                <div class="space-y-6 animate-fade-in">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="badge badge-primary">
                            <i class="fas fa-check-circle text-xs"></i>
                            Board Certified
                        </span>
                        <span class="badge badge-success">
                            <span class="pulse-dot"></span>
                            Available Today
                        </span>
                    </div>
                    
                    <!-- Name & Title -->
                    <div class="space-y-3">
                        <h1 id="doctor-name" class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-[1.1]"><?php echo $doctorName; ?></h1>
                        <div class="flex items-center gap-3 text-lg md:text-xl text-slate-600">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                <i class="fas fa-stethoscope text-primary-600"></i>
                            </div>
                            <span id="doctor-specialty" class="font-semibold"><?php echo $doctorSpecialty; ?></span>
                        </div>
                    </div>
                    
                    <!-- About Preview -->
                    <div class="relative">
                        <p id="doctor-about-hero" class="doctor-about text-slate-600 text-base leading-relaxed"><?php echo nl2br(substr($doctorAbout, 0, 300)) . (strlen($doctorAbout) > 300 ? '...' : ''); ?></p>
                        <?php if (strlen($doctorAbout) > 300): ?>
                        <button id="aboutReadMoreBtn" onclick="toggleAbout()" class="mt-2 text-primary-600 font-semibold text-sm hover:text-primary-700 transition">
                            Read more <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button onclick="openBookingModal()" class="btn-primary inline-flex items-center justify-center gap-2">
                            <i class="fas fa-envelope-open-text"></i>
                            Send Enquiry
                        </button>
                        <a href="#reviews-section" class="btn-secondary inline-flex items-center justify-center gap-2">
                            <i class="fas fa-star text-amber-500"></i>
                            Read Reviews
                        </a>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-3 gap-4 pt-4">
                        <div class="stat-card p-4 text-center shadow-sm">
                            <p class="text-2xl md:text-3xl font-bold gradient-text exp-years"><?php echo $doctorExperience; ?></p>
                            <p class="text-xs md:text-sm text-slate-500 font-medium mt-1">Years Exp.</p>
                        </div>
                        <div class="stat-card p-4 text-center shadow-sm">
                            <p class="text-2xl md:text-3xl font-bold text-amber-500 rating-display"><?php echo number_format($doctorRating, 1); ?></p>
                            <p class="text-xs md:text-sm text-slate-500 font-medium mt-1">Rating</p>
                        </div>
                        <div class="stat-card p-4 text-center shadow-sm">
                            <p class="text-2xl md:text-3xl font-bold text-blue-600 review-count">0</p>
                            <p class="text-xs md:text-sm text-slate-500 font-medium mt-1">Reviews</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Photo -->
                <div class="relative flex flex-col items-center animate-slide-up" style="animation-delay: 0.2s;">
                    <!-- Photo Container -->
                    <div class="relative w-full max-w-md">
                        <!-- Glow Effect -->
                        <div class="absolute -inset-4 bg-gradient-to-br from-primary-400/20 via-cyan-400/10 to-emerald-400/20 rounded-[32px] blur-2xl opacity-60"></div>
                        
                        <!-- Main Photo Card -->
                        <div class="relative bg-gradient-to-br from-white to-slate-50 rounded-3xl p-1.5 shadow-2xl shadow-slate-200/50">
                            <div class="relative overflow-hidden rounded-[22px] group">
                                <!-- Photo -->
                                <img id="doctor-photo" src="<?php echo $doctorPhoto; ?>" alt="<?php echo $doctorName; ?>" class="w-full aspect-photo object-cover transform group-hover:scale-105 transition-transform duration-700">
                                
                                <!-- Overlay Badges -->
                                <div class="absolute top-4 left-4 right-4 flex justify-between">
                                    <span class="badge badge-primary shadow-lg">
                                        <i class="fas fa-award text-xs"></i>
                                        Certified
                                    </span>
                                    <span class="badge badge-success shadow-lg animate-pulse-slow">
                                        <span class="w-2 h-2 bg-white rounded-full"></span>
                                        Online
                                    </span>
                                </div>
                                
                                <!-- Bottom Gradient -->
                                <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                                
                                <!-- Social Links -->
                                <div id="social-links" class="absolute bottom-4 right-4 flex gap-2">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Cards -->
                    <div class="grid grid-cols-2 gap-3 mt-6 w-full max-w-md">
                        <div class="card p-4 flex items-center gap-3">
                                <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 self-center">
                                    <i class="fas fa-award text-lg leading-none"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">Award Winning</p>
                                    <p class="text-xs text-slate-500">Excellence in care</p>
                                </div>
                            </div>
                            <div class="card p-4 flex items-center gap-3">
                                <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 self-center">
                                    <i class="fas fa-microscope text-lg leading-none"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">Modern Tech</p>
                                    <p class="text-xs text-slate-500">Latest equipment</p>
                                </div>
                            </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Availability Card -->
            <div class="card p-6 mt-8 w-full max-w-7xl mx-auto animate-slide-up" style="animation-delay: 0.3s;">
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="icon-box bg-emerald-100 text-emerald-600">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Availability</p>
                            <h4 class="font-bold text-slate-700 text-sm">Working Hours</h4>
                        </div>
                    </div>
                    <div class="w-full border-t md:border-t-0 md:border-l border-slate-100 pt-4 md:pt-0 md:pl-6 flex-grow">
                        <div id="doctor-timing">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden md:flex flex-col items-center gap-2 text-slate-400 animate-bounce">
            <span class="text-xs font-medium">Scroll to explore</span>
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Credentials Section -->
    <section id="credentials-section" class="py-16 md:py-24 px-4 bg-white relative">
        <div class="container mx-auto max-w-6xl">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16">
                <!-- Left: Expertise -->
                <div>
                    <div class="section-header">
                        <span class="section-badge">
                            <i class="fas fa-graduation-cap"></i>
                            Qualifications
                        </span>
                        <h2 class="section-title">Professional Expertise</h2>
                        <p class="section-subtitle">Comprehensive qualifications and extensive experience</p>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Qualification -->
                        <div class="card p-5 flex gap-4">
                            <div class="icon-box bg-primary-100 text-primary-600">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Qualification</p>
                                <p id="doctor-qualification" class="font-semibold text-slate-800"><?php echo $doctorQualification; ?></p>
                            </div>
                        </div>
                        
                        <!-- Experience -->
                        <div class="card p-5 flex gap-4">
                            <div class="icon-box bg-blue-100 text-blue-600">
                                <i class="fas fa-briefcase-medical"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Experience</p>
                                <p id="doctor-experience" class="font-semibold text-slate-800"><?php echo $doctorExperience; ?> Years</p>
                            </div>
                        </div>
                        
                        
                        <!-- Specialties -->
                        <div class="card p-5 flex gap-4">
                            <div class="icon-box bg-purple-100 text-purple-600">
                                <i class="fas fa-stethoscope"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Specialties</p>
                                <p id="doctor-specialties" class="font-semibold text-slate-800"><?php echo $doctorSpecialty; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Key Facts + Contact -->
                <div class="space-y-8">
                    <!-- Key Facts -->
                    <div>
                        <div class="flex items-center gap-3 mb-6">
                            <span class="section-badge">
                                <i class="fas fa-star"></i>
                                Key Facts
                            </span>
                        </div>
                        <div id="quick-facts-list" class="space-y-3">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                    
                    <!-- Contact Cards -->
                    <div class="grid gap-4">
                        <!-- Visit Card -->
                        <div class="card p-5 bg-gradient-to-br from-primary-50 to-white">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="icon-box bg-primary-500 text-white shadow-lg shadow-primary-500/30">
                                    <i class="fas fa-location-dot"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800">Visit Our Clinic</h4>
                                    <p class="text-sm text-slate-500">Get directions and schedule a visit</p>
                                </div>
                            </div>
                            
                            <div id="doctor-addresses" class="space-y-2 mb-4 text-sm text-slate-600 max-h-20 overflow-y-auto">
                                <!-- Populated by JS -->
                            </div>
                            
                            <?php
                            $hasPhone = !empty($doctor['phone']) && trim($doctor['phone']) !== '';
                            $callHref = $hasPhone ? 'tel:' . htmlspecialchars(trim($doctor['phone'])) : '#';
                            ?>
                            <a id="call-now-btn" href="<?php echo $callHref; ?>" <?php if (!$hasPhone) { echo 'onclick="openBookingModal(); return false;"'; } ?> class="btn-primary w-full text-center text-sm py-3">
                                <i class="fas fa-phone mr-2"></i>
                                Call Now
                            </a>
                        </div>
                        
                        <!-- Map Card -->
                        <div class="card overflow-hidden">
                            <div id="contact-map" class="h-48 bg-slate-100">
                                <div class="w-full h-full flex items-center justify-center text-slate-400">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    Loading map...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Section -->
    <section id="why-choose-section" class="py-16 md:py-24 px-4 bg-gradient-to-b from-slate-50 to-white relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-bl from-primary-100/30 to-transparent rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-cyan-100/20 to-transparent rounded-full blur-3xl -translate-x-1/2 translate-y-1/2"></div>
        </div>
        
        <div class="container mx-auto max-w-6xl relative z-10">
            <!-- Section Header -->
            <div class="text-center section-header">
                <span class="section-badge mx-auto">
                    <i class="fas fa-heart"></i>
                    Why Choose Us
                </span>
                <h2 class="section-title">
                    Why Patients Trust 
                    <span id="doc-name-highlight" class="gradient-text"><?php echo $doctorName; ?></span>
                </h2>
                <p class="section-subtitle mx-auto">Excellence in care, proven expertise, and genuine commitment to your well-being</p>
            </div>
            
            <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Doctor's Message -->
                <div class="order-2 lg:order-1">
                    <div class="bg-gradient-to-br from-primary-600 to-primary-700 rounded-3xl p-8 lg:p-10 text-white relative overflow-hidden shadow-2xl shadow-primary-600/30">
                        <!-- Decorative -->
                        <div class="absolute -top-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-primary-400/20 rounded-full blur-2xl"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
                                    <i class="fas fa-quote-left text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">A Message from the Doctor</h3>
                                    <p class="text-primary-200 text-sm">Dedicated to your health</p>
                                </div>
                            </div>
                            
                            <blockquote id="doctor-speech" class="text-lg leading-relaxed text-primary-100 italic mb-8"><?php echo $doctorSpeech ?: 'Dedicated to providing the best medical care to our patients.'; ?></blockquote>
                            
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button onclick="openBookingModal()" class="inline-flex items-center justify-center gap-2 bg-white text-primary-700 px-6 py-3 rounded-xl font-bold hover:bg-primary-50 transition shadow-lg">
                                    <i class="fas fa-calendar-check"></i>
                                    Book Consultation
                                </button>
                                <a href="#reviews-section" class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur text-white px-6 py-3 rounded-xl font-semibold border border-white/20 hover:bg-white/20 transition">
                                    <i class="fas fa-star text-amber-300"></i>
                                    View Reviews
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features Grid -->
                <div class="order-1 lg:order-2 space-y-4">
                    <div class="card p-6 flex gap-5 group">
                        <div class="icon-box bg-gradient-to-br from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-flask-vial"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 mb-1">Evidence-Based Care</h4>
                            <p class="text-sm text-slate-500">Latest treatments tailored to your specific needs and conditions</p>
                        </div>
                    </div>
                    
                    <div class="card p-6 flex gap-5 group">
                        <div class="icon-box bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-people-group"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 mb-1">Expert Team</h4>
                            <p class="text-sm text-slate-500">Multidisciplinary professionals with decades of combined experience</p>
                        </div>
                    </div>
                    
                    <div class="card p-6 flex gap-5 group">
                        <div class="icon-box bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-heart-pulse"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 mb-1">Patient-Focused</h4>
                            <p class="text-sm text-slate-500">Your comfort and satisfaction are always our top priority</p>
                        </div>
                    </div>
                    
                    <div class="card p-6 flex gap-5 group">
                        <div class="icon-box bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg shadow-purple-500/30 group-hover:scale-110 transition-transform">
                            <i class="fas fa-award"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800 mb-1">Certified & Accredited</h4>
                            <p class="text-sm text-slate-500">Licensed professional with recognized qualifications</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Bar -->
            <div class="grid grid-cols-3 gap-6 mt-12 pt-8 border-t border-slate-200">
                <div class="text-center">
                    <p class="text-3xl md:text-4xl font-bold gradient-text exp-years">-</p>
                    <p class="text-sm text-slate-500 font-medium mt-1">Years Experience</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl md:text-4xl font-bold text-amber-500 rating-display">-</p>
                    <p class="text-sm text-slate-500 font-medium mt-1">Star Rating</p>
                </div>
                <div class="text-center">
                    <p class="text-3xl md:text-4xl font-bold text-blue-600 review-count">0</p>
                    <p class="text-sm text-slate-500 font-medium mt-1">Happy Patients</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section (Req 9 - Swapped Gallery to Top) -->
    <section id="gallery-section" class="py-16 md:py-24 px-4 bg-gradient-to-b from-slate-50 to-white relative">
        <div class="container mx-auto max-w-6xl">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fas fa-images"></i>
                    Gallery
                </span>
                <h2 class="section-title">Clinic Gallery</h2>
                <p class="section-subtitle">Explore our modern facilities and professional workspace</p>
            </div>
            
            <div class="relative">
                <!-- Carousel Container -->
                <div id="gallery-carousel" class="carousel-container rounded-2xl">
                    <div id="gallery-carousel-inner" class="carousel-track gap-6">
                        <!-- Gallery slides inserted by JS -->
                    </div>
                </div>
                
                <!-- Navigation -->
                <button id="galleryPrevBtn" onclick="prevGallery()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:text-primary-600 hover:border-primary-300 transition-all hover:shadow-xl">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="galleryNextBtn" onclick="nextGallery()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:text-primary-600 hover:border-primary-300 transition-all hover:shadow-xl">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Dots -->
                <div id="gallery-carousel-dots" class="flex justify-center gap-2 mt-6">
                    <!-- Dots inserted by JS -->
                </div>
            </div>
        </div>
    </section>

    <!-- Videos Section (Req 9 - Swapped Videos to Bottom) -->
    <section id="videos-section" class="py-16 md:py-24 px-4 bg-white relative">
        <div class="container mx-auto max-w-6xl">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fas fa-play-circle"></i>
                    Media
                </span>
                <h2 class="section-title">Video Gallery</h2>
                <p class="section-subtitle">Watch educational content, procedures, and clinic highlights</p>
            </div>
            
            <div class="relative">
                <!-- Carousel Container -->
                <div id="videos-carousel" class="carousel-container rounded-2xl">
                    <div id="videos-carousel-inner" class="carousel-track gap-6">
                        <!-- Videos inserted by JS -->
                    </div>
                </div>
                
                <!-- Navigation -->
                <button id="videoPrevBtn" onclick="prevVideo()" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:text-primary-600 hover:border-primary-300 transition-all hover:shadow-xl">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="videoNextBtn" onclick="nextVideo()" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:text-primary-600 hover:border-primary-300 transition-all hover:shadow-xl">
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Dots -->
                <div id="videos-carousel-dots" class="flex justify-center gap-2 mt-6">
                    <!-- Dots inserted by JS -->
                </div>
            </div>
        </div>
    </section>

    <!-- Reviews Section -->
    <section id="reviews-section" class="py-16 md:py-24 px-4 bg-white relative">
        <div class="container mx-auto max-w-6xl">
            <div class="section-header">
                <span class="section-badge">
                    <i class="fas fa-star"></i>
                    Testimonials
                </span>
                <h2 class="section-title">Patient Reviews</h2>
                <p class="section-subtitle">Real feedback from <?php echo $totalReviews; ?> satisfied patient<?php echo $totalReviews != 1 ? 's' : ''; ?></p>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                <div class="stat-card p-6 text-center shadow-sm">
                    <div class="flex justify-center mb-2">
                        <div class="flex gap-0.5 text-amber-400">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= floor($avgRating) ? '' : ($i <= ceil($avgRating) ? 'fa-star-half-alt' : 'far'); ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-800"><?php echo number_format($avgRating, 1); ?></p>
                    <p class="text-sm text-slate-500 mt-1">Average Rating</p>
                </div>
                <div class="stat-card p-6 text-center shadow-sm">
                    <p id="total-reviews" class="text-3xl font-bold text-blue-600"><?php echo $totalReviews; ?></p>
                    <p class="text-sm text-slate-500 mt-1">Total Reviews</p>
                </div>
                <div class="stat-card p-6 text-center shadow-sm">
                    <p class="text-3xl font-bold text-emerald-600"><?php echo $satisfactionRate; ?>%</p>
                    <p class="text-sm text-slate-500 mt-1">Satisfaction</p>
                </div>
                <div class="stat-card p-6 text-center shadow-sm">
                    <p class="text-3xl font-bold text-purple-600"><?php echo $doctorExperience; ?>+</p>
                    <p class="text-sm text-slate-500 mt-1">Years Experience</p>
                </div>
            </div>
            
            <!-- Verified Patient Reviews -->
            <div class="mb-12">
                <div class="flex items-center gap-2 mb-6">
                    <h3 class="text-2xl font-bold text-slate-800">Verified Patient Reviews</h3>
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                        <i class="fas fa-check-circle"></i>
                        Verified
                    </span>
                </div>
                <div id="reviews-container" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($approvedReviews)): ?>
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-comments text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg">No verified reviews yet. Be the first to share your experience!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($approvedReviews as $review): ?>
                        <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-lg transition border-l-4 border-l-emerald-500">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex gap-0.5 text-amber-400">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-sm <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-slate-600 text-sm leading-relaxed"><?php echo htmlspecialchars($review['review_text']); ?></p>
                            <div class="mt-3 pt-3 border-t border-slate-100">
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <i class="fas fa-shield-alt"></i>
                                    Verified Patient
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Featured Testimonials (Manual Reviews) -->
            <div id="featured-testimonials-section"></div>
        </div>
    </section>

    <!-- Suggested Doctors Section (Req 14) -->
    <section id="suggested-doctors-section" class="py-16 md:py-24 px-4 bg-slate-50 relative border-t border-slate-100">
        <div class="container mx-auto max-w-6xl">
            <div class="section-header text-center mb-12 flex flex-col items-center">
                <span class="section-badge inline-flex items-center gap-1.5 bg-teal-50 text-teal-600 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">
                    <i class="fas fa-user-md"></i>
                    Recommendations
                </span>
                <h2 class="text-3xl font-bold text-slate-800 mt-3">Suggested Specialists</h2>
                <p class="text-slate-500 text-sm mt-2">Other expert doctors practicing similar specialties</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <?php foreach ($suggestedDoctors as $sDoc): ?>
                <div onclick="window.location.href='doctor-profile.php?id=<?php echo $sDoc['id']; ?>'" class="bg-white rounded-2xl shadow-md border border-slate-100 p-5 hover:shadow-xl hover:scale-[1.02] transform transition-all duration-300 cursor-pointer flex flex-col justify-between">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo htmlspecialchars($sDoc['photo'] ?: 'https://via.placeholder.com/150'); ?>" alt="<?php echo htmlspecialchars($sDoc['name']); ?>" class="w-16 h-16 rounded-full object-cover border-2 border-teal-500/20" />
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($sDoc['name']); ?></h3>
                            <p class="text-xs text-primary-600 font-semibold"><?php echo htmlspecialchars($sDoc['specialty']); ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($sDoc['qualification']); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100 text-xs">
                        <span class="text-slate-500 font-medium"><?php echo htmlspecialchars($sDoc['experience']); ?> Years Exp</span>
                        <span class="text-primary-600 font-bold flex items-center gap-1">
                            View Profile <i class="fas fa-chevron-right text-[10px]"></i>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 md:py-24 px-4 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(white 1px, transparent 1px); background-size: 30px 30px;"></div>
        </div>
        
        <div class="container mx-auto max-w-4xl text-center relative z-10">
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur rounded-full text-white/90 text-sm font-medium mb-6">
                <i class="fas fa-calendar-check"></i>
                Ready to get started?
            </span>
            
            <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-6 leading-tight">
                Schedule Your <br class="hidden sm:block">Consultation Today
            </h2>
            
            <p class="text-lg text-primary-100 mb-8 max-w-2xl mx-auto">
                Take the first step towards better health. Book an appointment with our expert team.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="openBookingModal()" class="inline-flex items-center justify-center gap-2 bg-white text-primary-700 px-8 py-4 rounded-xl font-bold hover:bg-primary-50 transition shadow-xl shadow-black/20 text-lg">
                    <i class="fas fa-envelope-open-text"></i>
                    Send Enquiry
                </button>
                <a href="#profile" class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur text-white px-8 py-4 rounded-xl font-semibold border border-white/20 hover:bg-white/20 transition text-lg">
                    <i class="fas fa-user-doctor"></i>
                    View Profile
                </a>
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
        
        <div class="container mx-auto px-6 py-16 max-w-6xl relative z-10">
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
                            <a href="#profile" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                Profile
                            </a>
                        </li>
                        <li>
                            <a href="#credentials-section" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                Credentials
                            </a>
                        </li>
                        <li>
                            <a href="#videos-section" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                Videos
                            </a>
                        </li>
                        <li>
                            <a href="#reviews-section" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-teal-500 mr-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                                Reviews
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Navigation -->
                <div>
                    <h4 class="text-lg font-bold mb-6 flex items-center">
                        <span class="w-1 h-6 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-3"></span>
                        Navigate
                    </h4>
                    <ul class="space-y-3">
                        <li>
                            <a href="index.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-home text-teal-500 mr-2 text-xs group-hover:scale-110 transition-transform"></i>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="doctors.php" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-user-doctor text-teal-500 mr-2 text-xs group-hover:scale-110 transition-transform"></i>
                                Find Doctors
                            </a>
                        </li>
                        <li>
                            <a href="index.php#about" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-info-circle text-teal-500 mr-2 text-xs group-hover:scale-110 transition-transform"></i>
                                About Us
                            </a>
                        </li>
                        <li>
                            <a href="index.php#contact" class="text-gray-300 hover:text-teal-400 transition duration-300 flex items-center group">
                                <i class="fas fa-envelope text-teal-500 mr-2 text-xs group-hover:scale-110 transition-transform"></i>
                                Contact
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Doctor Contact Info -->
                <div id="footer-doctor-info">
                    <h4 class="text-lg font-bold mb-6 flex items-center">
                        <span class="w-1 h-6 bg-gradient-to-b from-teal-400 to-cyan-400 rounded-full mr-3"></span>
                        Contact Doctor
                    </h4>
                    <div class="space-y-4">
                        <p id="footer-doctor-name" class="font-semibold text-white text-lg"></p>
                        <p id="footer-doctor-specialty" class="text-gray-300 text-sm"></p>
                        <div class="space-y-3 mt-4">
                            <a id="footer-doctor-phone" href="#" class="flex items-center text-gray-300 hover:text-teal-400 transition duration-300 group">
                                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-teal-500/20 transition-colors">
                                    <i class="fas fa-phone text-teal-400 text-sm"></i>
                                </div>
                                <span class="text-sm"></span>
                            </a>
                            <a id="footer-doctor-whatsapp" href="#" target="_blank" class="flex items-center text-gray-300 hover:text-emerald-400 transition duration-300 group hidden">
                                <div class="w-8 h-8 rounded-lg bg-slate-800 flex items-center justify-center mr-3 group-hover:bg-emerald-500/20 transition-colors">
                                    <i class="fab fa-whatsapp text-emerald-400 text-sm"></i>
                                </div>
                                <span class="text-sm">WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-slate-700/50 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-400 text-sm">
                        &copy; 2024 DrMap. All rights reserved. Made with <i class="fas fa-heart text-teal-400 mx-1"></i> for better healthcare.
                    </p>
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
        </div>
    </footer>

    <!-- Booking Modal -->
    <div id="bookingModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-in max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fas fa-calendar-check text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">Send Enquiry</h2>
                        <p class="text-primary-200 text-xs">We'll get back to you soon</p>
                    </div>
                </div>
                <button onclick="closeBookingModal()" class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- WhatsApp reminder notice (Req 32) -->
                <div id="booking-whatsapp-notice" class="hidden p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-start gap-2.5 shadow-sm">
                    <i class="fab fa-whatsapp text-emerald-600 text-base mt-0.5 animate-pulse"></i>
                    <div>
                        <strong class="font-bold text-emerald-950 block mb-0.5">WhatsApp Verification</strong>
                        Please fill out and submit this quick form to initiate direct chat messaging with the doctor's office.
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <i class="fas fa-user text-primary-600 mr-2"></i>Full Name *
                    </label>
                    <input type="text" id="appointmentName" placeholder="John Doe" class="form-input" />
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <i class="fas fa-envelope text-primary-600 mr-2"></i>Email Address *
                    </label>
                    <input type="email" id="appointmentEmail" placeholder="john@example.com" class="form-input" />
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <i class="fas fa-phone text-primary-600 mr-2"></i>Phone Number *
                    </label>
                    <input type="tel" id="appointmentPhone" placeholder="+1 234 567 890" class="form-input" />
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <i class="fas fa-message text-primary-600 mr-2"></i>Message (Optional)
                    </label>
                    <textarea id="appointmentMessage" placeholder="Tell us about your concern..." rows="3" class="form-input resize-none"></textarea>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-slate-50 px-6 py-4 flex gap-3 border-t border-slate-200">
                <button onclick="closeBookingModal()" class="flex-1 btn-secondary text-sm py-3">
                    Cancel
                </button>
                <button onclick="submitBookingForm()" class="flex-1 btn-primary text-sm py-3">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Enquiry
                </button>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-4xl bg-black rounded-2xl overflow-hidden shadow-2xl animate-scale-in">
            <button onclick="closeVideoModal()" class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-black/50 backdrop-blur flex items-center justify-center text-white hover:bg-black/70 transition">
                <i class="fas fa-times"></i>
            </button>
            <div class="aspect-video">
                <iframe id="videoFrameFull" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>

    <!-- Success Modal with Confetti -->
    <div id="successModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md">
            <!-- Confetti Container -->
            <div id="confetti-container" class="fixed inset-0 pointer-events-none overflow-hidden"></div>
            
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden animate-scale-in relative">
                <!-- Success Header -->
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-8 text-center">
                    <div class="flex justify-center mb-4">
                        <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center animate-bounce">
                            <i class="fas fa-check text-4xl text-white"></i>
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-white">Appointment Requested!</h2>
                </div>
                
                <!-- Success Body -->
                <div class="px-6 py-8 text-center space-y-4">
                    <p class="text-slate-600 text-lg font-medium">Thank you for your enquiry</p>
                    <p class="text-slate-500">We've received your appointment request and will contact you shortly to confirm the details.</p>
                    
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mt-6">
                        <p class="text-sm text-emerald-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            You'll receive a confirmation via email
                        </p>
                    </div>
                </div>
                
                <!-- Success Footer -->
                <div class="bg-slate-50 px-6 py-4 text-center border-t border-slate-200">
                    <button onclick="closeSuccessModal()" class="w-full btn-primary py-3 text-sm">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="doctors-data.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <script>
        // ============================================
        // DATABASE DOCTOR DATA (From PHP/Database)
        // ============================================
        
        // Create a doctor object from the PHP data
        // Note: Data is NOT htmlspecialchars'd here - it's raw from DB to ensure proper JSON encoding
        const databaseDoctor = {
            id: <?php echo (int)$doctor['id']; ?>,
            name: <?php echo json_encode($doctor['name']); ?>,
            specialty: <?php echo json_encode($doctor['specialty']); ?>,
            specialties: <?php echo json_encode($doctor['specialty']); ?>,
            experience: <?php echo (int)$doctor['experience']; ?>,
            qualification: <?php echo json_encode($doctor['qualification']); ?>,
            rating: <?php echo (float)$doctor['rating']; ?>,
            photo: <?php echo json_encode($doctor['photo']); ?>,
            phone: <?php echo json_encode($doctor['phone']); ?>,
            email: <?php echo json_encode($doctor['email']); ?>,
            whatsapp: <?php echo json_encode($doctor['whatsapp']); ?>,
            timing: <?php
                $tRaw = $doctor['timing'] ?? '';
                $tRaw = trim($tRaw);
                $emitted = false;

                if ($tRaw === '') {
                    echo json_encode(new stdClass());
                    $emitted = true;
                }

                if (!$emitted) {
                    // Try primary decode
                    $tDec = json_decode($tRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($tDec) || is_object($tDec))) {
                        echo json_encode($tDec);
                        $emitted = true;
                    }
                }

                if (!$emitted) {
                    // Try stripslashes (in case of escaping)
                    $p2 = stripslashes($tRaw);
                    $tDec = json_decode($p2, true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($tDec) || is_object($tDec))) {
                        echo json_encode($tDec);
                        $emitted = true;
                    }
                }

                if (!$emitted) {
                    // Try double-encoded JSON (a JSON string containing JSON)
                    $step = json_decode($tRaw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_string($step)) {
                        $tDec = json_decode($step, true);
                        if (json_last_error() === JSON_ERROR_NONE && (is_array($tDec) || is_object($tDec))) {
                            echo json_encode($tDec);
                            $emitted = true;
                        }
                    }
                }

                if (!$emitted) {
                    // Try converting single-quoted keys/values to double quotes (best-effort)
                    $p3 = $tRaw;
                    // Normalize smart quotes
                    $p3 = str_replace(["\x{2018}", "\x{2019}", "\x{201C}", "\x{201D}"], ["'", "'", '"', '"'], $p3);
                    // Replace single-quoted keys: {'key': -> {"key":
                    $p3 = preg_replace("/([\{,\s])'([^']+?)'\s*:/u", '$1"$2":', $p3);
                    // Replace single-quoted values: : 'val', or : 'val'}
                    $p3 = preg_replace("/:\s*'([^']*?)'(\s*[,\}])/u", ': "$1"$2', $p3);
                    $tDec = json_decode($p3, true);
                    if (json_last_error() === JSON_ERROR_NONE && (is_array($tDec) || is_object($tDec))) {
                        echo json_encode($tDec);
                        $emitted = true;
                    }
                }

                if (!$emitted) {
                    // Emit the raw value as string so frontend can attempt tolerant parsing
                    echo json_encode($tRaw);
                }
            ?>,
            about: <?php echo json_encode($doctor['about']); ?>,
            speech: <?php echo json_encode($doctor['speech']); ?>,
            social: <?php echo json_encode(json_decode($doctor['social'] ?? '{}', true)); ?>,
            addresses: <?php
                $addrRaw = $doctor['addresses'] ?? '';
                $addrDec = json_decode($addrRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($addrDec)) {
                    echo json_encode($addrDec);
                } else {
                    $lines = array_map('trim', array_filter(preg_split('/\r\n|\r|\n/', $addrRaw)));
                    echo json_encode($lines);
                }
            ?>,
            // Structured fields: try JSON decode, otherwise derive from newline/text formats
            quickFacts: <?php
                $qRaw = $doctor['quick_facts'] ?? '';
                $qDec = json_decode($qRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($qDec)) {
                    // Ensure items are objects with label/value
                    $norm = array_map(function($item){
                        if (is_array($item)) return $item;
                        return ['label' => (string)$item, 'value' => ''];
                    }, $qDec);
                    echo json_encode($norm);
                } else {
                    $lines = array_map('trim', array_filter(preg_split('/\r\n|\r|\n/', $qRaw)));
                    $norm = array_map(function($l){ return ['label'=>$l, 'value'=>'']; }, $lines);
                    echo json_encode($norm);
                }
            ?>,
            videos: <?php
                $vRaw = $doctor['videos'] ?? '';
                $vDec = json_decode($vRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($vDec)) {
                    echo json_encode(array_values($vDec));
                } else {
                    $lines = array_map('trim', array_filter(preg_split('/\r\n|\r|\n/', $vRaw)));
                    echo json_encode($lines);
                }
            ?>,
            gallery: <?php
                $gRaw = $doctor['gallery'] ?? '';
                $gDec = json_decode($gRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($gDec)) {
                    echo json_encode(array_values($gDec));
                } else {
                    $lines = array_map('trim', array_filter(preg_split('/\r\n|\r|\n/', $gRaw)));
                    echo json_encode($lines);
                }
            ?>,
            reviews: <?php
                $rRaw = $doctor['reviews'] ?? '';
                $rDec = json_decode($rRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($rDec)) {
                    echo json_encode(array_values($rDec));
                } else {
                    echo json_encode([]);
                }
            ?>,
            locations: <?php
                $lRaw = $doctor['locations'] ?? '';
                $lDec = json_decode($lRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($lDec)) {
                    echo json_encode(array_values($lDec));
                } else {
                    echo json_encode([]);
                }
            ?>
        };

        // CRITICAL: Override getDoctorById to use database data
        const originalGetDoctorById = getDoctorById;
        function getDoctorById(id) {
            if (parseInt(id) === databaseDoctor.id) {
                // using database doctor data
                return databaseDoctor;
            }
            return originalGetDoctorById(id);
        }
        
        // ============================================
        // INITIALIZATION & GLOBAL VARIABLES
        // ============================================
        
        let videoCurrentIndex = 0;
        let videoAutoPlayInterval;
        let galleryCurrentIndex = 0;
        let galleryAutoPlayInterval;

        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        
        function getVideoId(url) {
            const u = url.trim();
            const ytMatch = u.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/);
            if (ytMatch && ytMatch[1]) return { type: 'youtube', id: ytMatch[1] };
            
            const vimeoMatch = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
            if (vimeoMatch && vimeoMatch[1]) return { type: 'vimeo', id: vimeoMatch[1] };
            
            if (/^[A-Za-z0-9_-]{6,}$/.test(u)) return { type: 'youtube', id: u };
            
            return null;
        }

        function getThumbnailUrl(videoUrl) {
            const video = getVideoId(videoUrl);
            if (!video) return null;
            if (video.type === 'youtube') return `https://img.youtube.com/vi/${video.id}/maxresdefault.jpg`;
            return null;
        }

        function getEmbedUrl(url) {
            if (!url) return url;
            try {
                const u = url.trim();
                
                // Match YouTube URLs (various formats)
                const ytMatch = u.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                if (ytMatch && ytMatch[1]) {
                    // Use youtube-nocookie.com for better privacy and fewer restrictions
                    return `https://www.youtube-nocookie.com/embed/${ytMatch[1]}?rel=0&modestbranding=1&enablejsapi=1`;
                }
                
                // Match Vimeo URLs
                const vimeoMatch = u.match(/vimeo\.com\/(?:video\/)?(\d+)/);
                if (vimeoMatch && vimeoMatch[1]) {
                    return `https://player.vimeo.com/video/${vimeoMatch[1]}?byline=0&portrait=0`;
                }
                
                // If already an embed URL, return as-is
                if (u.includes('youtube.com/embed') || u.includes('youtube-nocookie.com/embed') || u.includes('player.vimeo.com')) {
                    return u;
                }
                
                // If it looks like a video ID only
                if (/^[A-Za-z0-9_-]{11}$/.test(u)) {
                    return `https://www.youtube-nocookie.com/embed/${u}?rel=0&modestbranding=1&enablejsapi=1`;
                }
                
                // Otherwise return the original URL (might be a direct video file)
                return u;
            } catch (e) {
                console.error('Error parsing video URL:', e);
                return url;
            }
        }

        // Normalize image paths so uploads work when app is in a subfolder
        function normalizeImagePath(p) {
            if (!p) return p;
            const s = String(p).trim();
            if (s === '') return s;
            if (s.startsWith('http://') || s.startsWith('https://') || s.startsWith('//')) return s;
            // If path is absolute from server root (e.g. /uploads/...), prefix with the first path segment (app folder)
            if (s.startsWith('/')) {
                const parts = location.pathname.split('/').filter(Boolean);
                const appRoot = parts.length ? `/${parts[0]}` : '';
                return appRoot + s;
            }
            // Relative path (uploads/...) — make it relative to current pathname root
            if (!s.startsWith('./') && !s.startsWith('../')) {
                const parts = location.pathname.split('/').filter(Boolean);
                const appRoot = parts.length ? `/${parts[0]}` : '';
                return appRoot + '/' + s;
            }
            return s;
        }

        // ============================================
        // MOBILE MENU
        // ============================================
        
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn?.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.className = 'fas fa-bars text-slate-600 text-lg';
            } else {
                icon.className = 'fas fa-times text-slate-600 text-lg';
            }
        });

        // Close mobile menu when clicking a link
        mobileMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                mobileMenuBtn.querySelector('i').className = 'fas fa-bars text-slate-600 text-lg';
            });
        });

        // ============================================
        // HEADER SCROLL EFFECT
        // ============================================
        
        const header = document.getElementById('main-header');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.querySelector('nav').classList.add('shadow-xl');
            } else {
                header.querySelector('nav').classList.remove('shadow-xl');
            }
            
            lastScroll = currentScroll;
        });

        // ============================================
        // NAVIGATION ACTIVE STATE
        // ============================================
        
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop - 200;
                if (scrollY >= sectionTop) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // ============================================
        // GET DOCTOR DATA
        // ============================================
        
        const urlParams = new URLSearchParams(window.location.search);
        const doctorId = urlParams.get('id');

        if (!doctorId) {
            window.location.href = 'doctors.php';
        }

        const doctor = getDoctorById(doctorId);

        if (!doctor) {
            alert('Doctor not found!');
            window.location.href = 'doctors.php';
        }

        // ============================================
        // POPULATE DOCTOR PROFILE
        // ============================================
        
        function populateDoctorProfile() {
            // Page title
            document.title = `${doctor.name} - DrMap`;
            
            // Navbar
            document.getElementById('navbar-doctor-name').textContent = doctor.name;
            
            // Hero Section
            document.getElementById('doctor-photo').src = doctor.photo;
            document.getElementById('doctor-photo').alt = doctor.name;
            document.getElementById('doctor-name').textContent = doctor.name;
            document.getElementById('doc-name-highlight').textContent = doctor.name;
            document.getElementById('doctor-specialty').textContent = doctor.specialty;
            
            // About Preview
            const aboutHero = document.getElementById('doctor-about-hero');
            const aboutBtn = document.getElementById('aboutReadMoreBtn');
            const fullAbout = (doctor.about || '').trim();
            aboutHero.dataset.full = fullAbout;
            const maxLen = 200;
            const shortAbout = fullAbout.length > maxLen ? fullAbout.slice(0, maxLen).trim() + '...' : fullAbout;
            aboutHero.textContent = shortAbout;
            if (fullAbout.length > maxLen) {
                aboutBtn.classList.remove('hidden');
            }
            
            // Credentials
            document.getElementById('doctor-qualification').textContent = doctor.qualification;
            document.getElementById('doctor-experience').textContent = `${doctor.experience} years of experience`;
            
            // Format timing schedule for display (tolerant to malformed server strings)
            function parseTimingJSON(raw) {
                if (!raw) return null;
                if (typeof raw === 'object') return raw;
                if (typeof raw === 'string') {
                    try {
                        return JSON.parse(raw);
                    } catch(e) {
                        return null;
                    }
                }
                return null;
            }

            function convert24to12(time24) {
                if (!time24) return time24;
                const [hours, minutes] = time24.split(':');
                let h = parseInt(hours);
                let m = minutes || '00';
                const period = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                return `${h}:${m} ${period}`;
            }

            function renderTimingCalendar(raw) {
                const timing = parseTimingJSON(raw);
                if (!timing) {
                    if (raw && typeof raw === 'string') {
                        return `<p class="text-slate-700 font-medium text-sm">${raw}</p>`;
                    }
                    return '<p class="text-slate-600">Contact for details</p>';
                }

                const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                const labels = {
                    monday:'Monday', tuesday:'Tuesday', wednesday:'Wednesday', thursday:'Thursday', 
                    friday:'Friday', saturday:'Saturday', sunday:'Sunday'
                };

                let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4 md:gap-6">';
                
                days.forEach(day => {
                    const dayInfo = timing[day];
                    if (dayInfo && (dayInfo.enabled === true || dayInfo.enabled === 'true') && Array.isArray(dayInfo.slots) && dayInfo.slots.length > 0) {
                        // Show all slots for this day
                        const slots = dayInfo.slots.map(slot => {
                            if (slot.open && slot.close) {
                                return `${convert24to12(slot.open)} - ${convert24to12(slot.close)}`;
                            } else if (slot.open) {
                                return convert24to12(slot.open);
                            }
                            return null;
                        }).filter(s => s !== null);

                        if (slots.length > 0) {
                            html += `
                                <div class="space-y-2">
                                    <span class="inline-block px-3 py-1 bg-primary-50 text-primary-700 font-semibold text-sm rounded-lg">
                                        ${labels[day]}
                                    </span>
                                    <div class="flex flex-col gap-1">
                                        ${slots.map(slot => `<span class="text-slate-700 font-medium text-sm">${slot}</span>`).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    }
                });

                html += '</div>';
                return html;
            }

            const timingCalendarHTML = renderTimingCalendar(doctor.timing);
            document.getElementById('doctor-timing').innerHTML = timingCalendarHTML;

            // Specialties: prefer an explicit 'specialties' array, fall back to single 'specialty'
            const specialtiesEl = document.getElementById('doctor-specialties');
            if (specialtiesEl) {
                if (Array.isArray(doctor.specialties) && doctor.specialties.length) {
                    specialtiesEl.textContent = doctor.specialties.join(', ');
                } else if (typeof doctor.specialties === 'string' && doctor.specialties.trim()) {
                    specialtiesEl.textContent = doctor.specialties;
                } else if (doctor.specialty) {
                    specialtiesEl.textContent = doctor.specialty;
                } else {
                    specialtiesEl.textContent = '—';
                }
            }
            
            // Update all experience displays
            document.querySelectorAll('.exp-years').forEach(el => {
                el.textContent = `${doctor.experience}+`;
            });
            
            // Update all rating displays
            document.querySelectorAll('.rating-display').forEach(el => {
                el.textContent = typeof doctor.rating === 'number' ? doctor.rating.toFixed(1) : doctor.rating || '-';
            });
            
            // Update review counts
            document.querySelectorAll('.review-count').forEach(el => {
                el.textContent = doctor.reviews.length;
            });
            
            // Doctor Speech
            document.getElementById('doctor-speech').textContent = doctor.speech;
            
            // Phone
            const callBtn = document.getElementById('call-now-btn');
            if (callBtn) callBtn.href = `tel:${doctor.phone}`;
            
            // Addresses
            const addressesContainer = document.getElementById('doctor-addresses');
            if (addressesContainer) {
                addressesContainer.innerHTML = '';
                if (Array.isArray(doctor.addresses) && doctor.addresses.length) {
                    doctor.addresses.forEach(address => {
                        const p = document.createElement('p');
                        p.className = 'flex items-start gap-2';
                        p.innerHTML = `<i class="fas fa-map-marker-alt text-primary-500 mt-1 text-xs"></i><span>${address}</span>`;
                        addressesContainer.appendChild(p);
                    });
                } else {
                    addressesContainer.innerHTML = '<p class="text-slate-400">No clinic address listed</p>';
                }
            }
            
            // Map
            const contactMap = document.getElementById('contact-map');
            if (contactMap && Array.isArray(doctor.locations) && doctor.locations.length) {
                const loc = doctor.locations[0] || {};
                let embedUrl = '';

                // Prefer an explicit embed URL if it looks like an embed
                if (loc.mapEmbedUrl && typeof loc.mapEmbedUrl === 'string') {
                    const url = loc.mapEmbedUrl.trim();
                    // Try to extract lat/lng from common Google Maps URL formats so we can render a proper map
                    try {
                        const decoded = decodeURIComponent(url);
                        // Pattern: @lat,lng, e.g. /@12.3456,78.9012,17z
                        const atMatch = decoded.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
                        if (atMatch) {
                            if (!loc.lat) loc.lat = parseFloat(atMatch[1]);
                            if (!loc.lng) loc.lng = parseFloat(atMatch[2]);
                        }
                        // Pattern: !3dLAT!4dLNG sometimes present in map links
                        const dMatch = decoded.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/);
                        if (dMatch) {
                            if (!loc.lat) loc.lat = parseFloat(dMatch[1]);
                            if (!loc.lng) loc.lng = parseFloat(dMatch[2]);
                        }
                        // Pattern: q=lat,lng
                        const qMatch = decoded.match(/[?&]q=(-?\d+\.\d+),(-?\d+\.\d+)/);
                        if (qMatch) {
                            if (!loc.lat) loc.lat = parseFloat(qMatch[1]);
                            if (!loc.lng) loc.lng = parseFloat(qMatch[2]);
                        }
                    } catch (e) {
                        // ignore decode errors
                    }

                    if (url.includes('/maps/embed') || url.includes('output=embed') || url.includes('maps.google.com/maps?')) {
                        // only use it if it already contains an embed or output=embed
                        embedUrl = url;
                    }
                }

                // If no embed URL, but we have lat/lng, build a safe embed URL
                if (!embedUrl && loc.lat != null && loc.lng != null && loc.lat !== '' && loc.lng !== '') {
                    const lat = encodeURIComponent(String(loc.lat));
                    const lng = encodeURIComponent(String(loc.lng));
                    embedUrl = `https://maps.google.com/maps?q=${lat},${lng}&output=embed`;
                }

                if (embedUrl) {
                    // If embedUrl is a google/embed or other embed, try to extract lat/lng, otherwise fallback to iframe
                    // We'll prefer to render a Leaflet map using lat/lng if available
                    if (loc.lat != null && loc.lng != null && loc.lat !== '' && loc.lng !== '') {
                        const lat = parseFloat(loc.lat);
                        const lng = parseFloat(loc.lng);
                        if (!isNaN(lat) && !isNaN(lng) && typeof L !== 'undefined') {
                            contactMap.innerHTML = '';
                            const map = L.map(contactMap).setView([lat,lng], 14);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                            
                            // Collect all markers with valid coordinates
                            const markers = [];
                            (doctor.locations || []).forEach((l, idx) => {
                                const la = parseFloat(l.lat); 
                                const ln = parseFloat(l.lng);
                                if (!isNaN(la) && !isNaN(ln)) {
                                    const marker = L.marker([la, ln]).addTo(map);
                                    const popupContent = `<div class="p-2"><strong>Location ${idx + 1}</strong><br/>${l.address || 'No address provided'}</div>`;
                                    marker.bindPopup(popupContent);
                                    markers.push(marker);
                                }
                            });
                            
                            // Fit map bounds to show all markers
                            if (markers.length > 1) {
                                const group = new L.featureGroup(markers);
                                map.fitBounds(group.getBounds().pad(0.1));
                            } else if (markers.length === 1) {
                                map.setView(markers[0].getLatLng(), 14);
                            }
                        } else {
                            contactMap.innerHTML = `<iframe src="${embedUrl}" class="w-full h-full border-0" loading="lazy" referrerpolicy="no-referrer"></iframe>`;
                        }
                    } else {
                        contactMap.innerHTML = `<iframe src="${embedUrl}" class="w-full h-full border-0" loading="lazy" referrerpolicy="no-referrer"></iframe>`;
                    }
                } else if (loc.lat != null && loc.lng != null && loc.lat !== '' && loc.lng !== '' && typeof L !== 'undefined') {
                    const lat = parseFloat(loc.lat);
                    const lng = parseFloat(loc.lng);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        contactMap.innerHTML = '';
                        const map = L.map(contactMap).setView([lat,lng], 14);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
                        
                        // Collect all markers with valid coordinates
                        const markers = [];
                        (doctor.locations || []).forEach((l, idx) => {
                            const la = parseFloat(l.lat); 
                            const ln = parseFloat(l.lng);
                            if (!isNaN(la) && !isNaN(ln)) {
                                const marker = L.marker([la, ln]).addTo(map);
                                const popupContent = `<div class="p-2"><strong>Location ${idx + 1}</strong><br/>${l.address || 'No address provided'}</div>`;
                                marker.bindPopup(popupContent);
                                markers.push(marker);
                            }
                        });
                        
                        // Fit map bounds to show all markers
                        if (markers.length > 1) {
                            const group = new L.featureGroup(markers);
                            map.fitBounds(group.getBounds().pad(0.1));
                        } else if (markers.length === 1) {
                            map.setView(markers[0].getLatLng(), 14);
                        }
                    } else {
                        contactMap.innerHTML = '<p class="text-slate-400 p-4">Map not available</p>';
                    }
                } else if (loc.mapEmbedUrl && typeof loc.mapEmbedUrl === 'string' && loc.mapEmbedUrl.trim() !== '') {
                    const safe = loc.mapEmbedUrl.replace(/\"/g, '');
                    contactMap.innerHTML = `<div class="p-4"><a target="_blank" rel="noopener noreferrer" href="${safe}" class="text-primary-600 font-medium">Open map in new tab</a></div>`;
                } else {
                    contactMap.innerHTML = '<p class="text-slate-400 p-4">Map not available</p>';
                }
            }
            
            // Social Links
            const socialLinks = document.getElementById('social-links');
            socialLinks.innerHTML = '';
            
            const socialIcons = {
                linkedin: { icon: 'fab fa-linkedin-in', bg: 'from-blue-600 to-blue-700' },
                twitter: { icon: 'fab fa-twitter', bg: 'from-sky-400 to-sky-500' },
                facebook: { icon: 'fab fa-facebook-f', bg: 'from-blue-500 to-blue-600' }
            };
            
            Object.entries(doctor.social || {}).forEach(([platform, url]) => {
                if (url && socialIcons[platform]) {
                    const link = document.createElement('a');
                    link.href = url;
                    link.target = '_blank';
                    link.className = `w-9 h-9 rounded-lg bg-gradient-to-br ${socialIcons[platform].bg} flex items-center justify-center text-white text-sm shadow-lg hover:scale-110 transition-transform`;
                    link.innerHTML = `<i class="${socialIcons[platform].icon}"></i>`;
                    socialLinks.appendChild(link);
                }
            });
            
            // Quick Facts
            const quickFactsList = document.getElementById('quick-facts-list');
            if (quickFactsList && Array.isArray(doctor.quickFacts)) {
                quickFactsList.innerHTML = '';
                const icons = {
                    'Languages': 'fa-language',
                    'Consultation Fee': 'fa-dollar-sign',
                    'Special Interests': 'fa-heart',
                    'Experience': 'fa-award'
                };
                
                doctor.quickFacts.forEach(fact => {
                    const div = document.createElement('div');
                    div.className = 'card p-4 flex items-center gap-4';
                    div.innerHTML = `
                        <div class="w-9 h-9 flex items-center justify-center rounded-lg bg-teal-50 text-teal-600 flex-shrink-0">
                            <i class="fas ${icons[fact.label] || 'fa-info-circle'} text-lg leading-none"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase">${fact.label}</p>
                            <p class="font-semibold text-slate-800">${fact.value}</p>
                        </div>
                    `;
                    quickFactsList.appendChild(div);
                });
            }
            
            // Videos
            if (doctor.videos && doctor.videos.length > 0) {
                const videosCarouselInner = document.getElementById('videos-carousel-inner');
                
                doctor.videos.forEach(video => {
                    const embedSrc = getEmbedUrl(video);
                    if (!embedSrc) return;
                    
                    const thumbnailUrl = getThumbnailUrl(video);
                    const card = document.createElement('div');
                    card.className = 'carousel-slide w-full md:w-[400px]';
                    card.innerHTML = `
                        <div class="card overflow-hidden cursor-pointer group" onclick="openVideoModal('${embedSrc}')">
                            <div class="relative aspect-video bg-slate-900">
                                <img src="${thumbnailUrl}" alt="Video thumbnail" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" onerror="this.src='https://via.placeholder.com/640x360/1e293b/64748b?text=Video'" />
                                <div class="absolute inset-0 bg-black/30 group-hover:bg-black/50 transition-colors flex items-center justify-center">
                                    <div class="w-16 h-16 rounded-full bg-red-600/90 flex items-center justify-center group-hover:scale-110 transition-transform shadow-xl">
                                        <i class="fas fa-play text-white text-xl ml-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    videosCarouselInner.appendChild(card);
                });
                
                setTimeout(setupVideoCarousel, 100);
            } else {
                document.getElementById('videos-section').style.display = 'none';
            }
            
            // Gallery
            if (doctor.gallery && doctor.gallery.length > 0) {
                const galleryCarouselInner = document.getElementById('gallery-carousel-inner');
                
                doctor.gallery.forEach((img, index) => {
                    const src = normalizeImagePath(img);
                    const card = document.createElement('div');
                    card.className = 'carousel-slide w-full md:w-[400px]';
                    card.innerHTML = `
                        <div class="card overflow-hidden">
                            <img src="${src}" alt="Gallery ${index + 1}" class="w-full h-72 md:h-80 object-cover hover:scale-105 transition-transform duration-500" loading="lazy" onerror="this.src='https://via.placeholder.com/800x600?text=Image'" />
                        </div>
                    `;
                    galleryCarouselInner.appendChild(card);
                });
                
                setTimeout(setupGalleryCarousel, 100);
            } else {
                document.getElementById('gallery-section').style.display = 'none';
            }
            
            // Featured Testimonials (Manual Reviews from doctors table)
            if (doctor.reviews && doctor.reviews.length > 0) {
                const featuredSection = document.getElementById('featured-testimonials-section');
                
                const sectionHTML = `
                    <div class="pt-12 border-t border-slate-200">
                        <div class="flex items-center gap-2 mb-6">
                            <h3 class="text-2xl font-bold text-slate-800">Featured Testimonials</h3>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                <i class="fas fa-star"></i>
                                Featured
                            </span>
                        </div>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ${doctor.reviews.map(review => {
                                const stars = Array(5).fill(0).map((_, i) => 
                                    i < review.rating ? '<i class="fas fa-star text-amber-400"></i>' : '<i class="far fa-star text-slate-300"></i>'
                                ).join('');
                                
                                const date = new Date(review.date).toLocaleDateString('en-US', { 
                                    year: 'numeric', 
                                    month: 'short', 
                                    day: 'numeric' 
                                });
                                
                                return `
                                    <div class="bg-white rounded-2xl p-6 shadow-md hover:shadow-lg transition border-l-4 border-l-blue-500">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                                                    ${review.name.charAt(0)}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-800">${review.name}</p>
                                                    <p class="text-xs text-slate-500">${date}</p>
                                                </div>
                                            </div>
                                            <div class="flex gap-0.5 text-amber-400">
                                                ${stars}
                                            </div>
                                        </div>
                                        <p class="text-slate-600 text-sm leading-relaxed">${review.comment}</p>
                                        <div class="mt-3 pt-3 border-t border-slate-100">
                                            <span class="inline-flex items-center gap-1 text-xs text-blue-600 font-medium">
                                                <i class="fas fa-bookmark"></i>
                                                Featured Testimonial
                                            </span>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                
                featuredSection.innerHTML = sectionHTML;
            }
            
            // Footer
            const footerDoctorName = document.getElementById('footer-doctor-name');
            const footerDoctorSpecialty = document.getElementById('footer-doctor-specialty');
            const footerDoctorPhone = document.getElementById('footer-doctor-phone');
            const footerBrandTitle = document.getElementById('footer-brand-title');
            const footerBrandSubtitle = document.getElementById('footer-brand-subtitle');
            
            if (footerDoctorName) footerDoctorName.textContent = doctor.name;
            if (footerDoctorSpecialty) footerDoctorSpecialty.textContent = doctor.specialty;
            if (footerDoctorPhone) {
                footerDoctorPhone.href = `tel:${doctor.phone}`;
                footerDoctorPhone.querySelector('span').textContent = doctor.phone;
            }
            if (footerBrandTitle) footerBrandTitle.textContent = doctor.name;
            if (footerBrandSubtitle) footerBrandSubtitle.textContent = doctor.specialty;
        }

        // ============================================
        // TOGGLE ABOUT TEXT
        // ============================================
        
        function toggleAbout() {
            const el = document.getElementById('doctor-about-hero');
            const btn = document.getElementById('aboutReadMoreBtn');
            if (!el || !btn) return;
            
            const full = el.dataset.full || '';
            const maxLen = 200;
            const isExpanded = btn.dataset.expanded === 'true';
            
            if (isExpanded) {
                el.textContent = full.length > maxLen ? full.slice(0, maxLen).trim() + '...' : full;
                btn.innerHTML = 'Read more <i class="fas fa-chevron-down text-xs ml-1"></i>';
                btn.dataset.expanded = 'false';
            } else {
                el.textContent = full;
                btn.innerHTML = 'Show less <i class="fas fa-chevron-up text-xs ml-1"></i>';
                btn.dataset.expanded = 'true';
            }
        }

        // ============================================
        // CAROUSEL FUNCTIONS
        // ============================================
        
        function setupVideoCarousel() {
            updateVideoCarouselPosition();
            startVideoAutoPlay();
        }

        function updateVideoCarouselPosition() {
            const carousel = document.getElementById('videos-carousel-inner');
            const container = document.getElementById('videos-carousel');
            if (!carousel || !container) return;
            
            const slides = carousel.querySelectorAll('.carousel-slide');
            if (slides.length === 0) return;
            
            const gap = 24;
            const slideWidth = slides[0].getBoundingClientRect().width;
            const containerWidth = container.offsetWidth;
            const visibleCount = Math.max(1, Math.floor(containerWidth / (slideWidth + gap)));
            const maxIndex = Math.max(0, slides.length - visibleCount);

            videoCurrentIndex = Math.min(videoCurrentIndex, maxIndex);
            videoCurrentIndex = Math.max(0, videoCurrentIndex);

            // Prevent translating past the maximum scrollable offset (avoid empty space on the right)
            const totalScrollWidth = carousel.scrollWidth;
            const desiredOffset = videoCurrentIndex * (slideWidth + gap);
            const maxOffset = Math.max(0, totalScrollWidth - containerWidth);
            const offset = Math.min(desiredOffset, maxOffset);

            carousel.style.transform = `translateX(-${offset}px)`;
        }

        function nextVideo() {
            const carousel = document.getElementById('videos-carousel-inner');
            const container = document.getElementById('videos-carousel');
            if (!carousel || !container) return;
            
            const slides = carousel.querySelectorAll('.carousel-slide');
            const gap = 24;
            const slideWidth = slides[0]?.getBoundingClientRect().width || 400;
            const containerWidth = container.offsetWidth;
            const visibleCount = Math.max(1, Math.floor(containerWidth / (slideWidth + gap)));
            const maxIndex = Math.max(0, slides.length - visibleCount);
            
            const shift = Math.max(1, visibleCount);
            if (videoCurrentIndex < maxIndex) {
                videoCurrentIndex = Math.min(maxIndex, videoCurrentIndex + shift);
            } else {
                videoCurrentIndex = 0;
            }
            
            updateVideoCarouselPosition();
            resetVideoAutoPlay();
        }

        function prevVideo() {
            const carouselInner = document.getElementById('videos-carousel-inner');
            const slides = carouselInner?.querySelectorAll('.carousel-slide');
            const maxIndex = slides ? Math.max(0, slides.length - 1) : 0;

            const carouselContainer = document.getElementById('videos-carousel');
            const gap = 24;
            const slideWidth = carouselInner?.querySelector('.carousel-slide')?.getBoundingClientRect().width || 400;
            const containerWidth = carouselContainer ? carouselContainer.offsetWidth : window.innerWidth;
            const visibleCount = Math.max(1, Math.floor(containerWidth / (slideWidth + gap)));
            const shift = Math.max(1, visibleCount);

            if (videoCurrentIndex > 0) {
                videoCurrentIndex = Math.max(0, videoCurrentIndex - shift);
            } else {
                videoCurrentIndex = maxIndex;
            }

            updateVideoCarouselPosition();
            resetVideoAutoPlay();
        }

        function startVideoAutoPlay() {
            clearInterval(videoAutoPlayInterval);
            videoAutoPlayInterval = setInterval(nextVideo, 5000);
        }

        function resetVideoAutoPlay() {
            clearInterval(videoAutoPlayInterval);
            startVideoAutoPlay();
        }

        // Gallery Carousel
        function setupGalleryCarousel() {
            updateGalleryCarouselPosition();
            startGalleryAutoPlay();
        }

        function updateGalleryCarouselPosition() {
            const carousel = document.getElementById('gallery-carousel-inner');
            const container = document.getElementById('gallery-carousel');
            if (!carousel || !container) return;
            
            const slides = carousel.querySelectorAll('.carousel-slide');
            if (slides.length === 0) return;
            
            const gap = 24;
            const slideWidth = slides[0].getBoundingClientRect().width;
            const containerWidth = container.offsetWidth;
            const visibleCount = Math.max(1, Math.floor(containerWidth / (slideWidth + gap)));
            const maxIndex = Math.max(0, slides.length - visibleCount);

            galleryCurrentIndex = Math.min(galleryCurrentIndex, maxIndex);
            galleryCurrentIndex = Math.max(0, galleryCurrentIndex);

            const totalScrollWidth = carousel.scrollWidth;
            const desiredOffset = galleryCurrentIndex * (slideWidth + gap);
            const maxOffset = Math.max(0, totalScrollWidth - containerWidth);
            const offset = Math.min(desiredOffset, maxOffset);

            carousel.style.transform = `translateX(-${offset}px)`;
        }

        function nextGallery() {
            const carousel = document.getElementById('gallery-carousel-inner');
            const container = document.getElementById('gallery-carousel');
            if (!carousel || !container) return;
            
            const slides = carousel.querySelectorAll('.carousel-slide');
            const gap = 24;
            const slideWidth = slides[0]?.getBoundingClientRect().width || 400;
            const containerWidth = container.offsetWidth;
            const visibleCount = Math.max(1, Math.floor(containerWidth / (slideWidth + gap)));
            const maxIndex = Math.max(0, slides.length - visibleCount);
            
            const shift = Math.max(1, visibleCount);
            const lastIndex = Math.max(0, slides.length - 1);

            if (galleryCurrentIndex < maxIndex) {
                galleryCurrentIndex = Math.min(maxIndex, galleryCurrentIndex + shift);
            } else if (galleryCurrentIndex < lastIndex && maxIndex === 0) {
                galleryCurrentIndex = Math.min(lastIndex, galleryCurrentIndex + 1);
            } else {
                galleryCurrentIndex = 0;
            }
            
            updateGalleryCarouselPosition();
            resetGalleryAutoPlay();
        }

        function prevGallery() {
            const carousel = document.getElementById('gallery-carousel-inner');
            const slides = carousel?.querySelectorAll('.carousel-slide');
            const maxIndex = slides ? Math.max(0, slides.length - 1) : 0;
            
            const carouselEl = document.getElementById('gallery-carousel');
            const gap = 24;
            const cardWidth = carouselEl ? (carouselEl.querySelector('.carousel-slide')?.getBoundingClientRect().width || (window.innerWidth >= 768 ? 384 : window.innerWidth - 32)) : (window.innerWidth >= 768 ? 384 : window.innerWidth - 32);
            const containerWidth = carouselEl ? carouselEl.offsetWidth : window.innerWidth;
            const visibleCountPrev = Math.max(1, Math.floor(containerWidth / (cardWidth + gap)));
            const shiftPrev = Math.max(1, visibleCountPrev);

            if (galleryCurrentIndex > 0) {
                galleryCurrentIndex = Math.max(0, galleryCurrentIndex - shiftPrev);
            } else {
                galleryCurrentIndex = maxIndex;
            }
            
            updateGalleryCarouselPosition();
            resetGalleryAutoPlay();
        }

        function startGalleryAutoPlay() {
            clearInterval(galleryAutoPlayInterval);
            galleryAutoPlayInterval = setInterval(nextGallery, 6000);
        }

        function resetGalleryAutoPlay() {
            clearInterval(galleryAutoPlayInterval);
            startGalleryAutoPlay();
        }

        // Resize handler
        window.addEventListener('resize', () => {
            updateVideoCarouselPosition();
            updateGalleryCarouselPosition();
        });

        // Touch/Swipe Support
        function setupSwipe(carouselId, nextFn, prevFn) {
            const carousel = document.getElementById(carouselId);
            if (!carousel) return;
            
            let startX = 0;
            let endX = 0;
            
            carousel.addEventListener('touchstart', e => {
                startX = e.changedTouches[0].screenX;
            }, { passive: true });
            
            carousel.addEventListener('touchend', e => {
                endX = e.changedTouches[0].screenX;
                const diff = startX - endX;
                
                if (Math.abs(diff) > 50) {
                    if (diff > 0) nextFn();
                    else prevFn();
                }
            }, { passive: true });
        }

        setupSwipe('videos-carousel', nextVideo, prevVideo);
        setupSwipe('gallery-carousel', nextGallery, prevGallery);

        // Pause autoplay on hover
        const videosCarousel = document.getElementById('videos-carousel');
        const galleryCarousel = document.getElementById('gallery-carousel');

        videosCarousel?.addEventListener('mouseenter', () => clearInterval(videoAutoPlayInterval));
        videosCarousel?.addEventListener('mouseleave', startVideoAutoPlay);
        galleryCarousel?.addEventListener('mouseenter', () => clearInterval(galleryAutoPlayInterval));
        galleryCarousel?.addEventListener('mouseleave', startGalleryAutoPlay);

        // ============================================
        // MODAL FUNCTIONS
        // ============================================
        
        let isWhatsAppRequest = false;
        function openBookingModal(fromWhatsApp = false) {
            isWhatsAppRequest = fromWhatsApp;
            const notice = document.getElementById('booking-whatsapp-notice');
            if (fromWhatsApp) {
                if (notice) notice.classList.remove('hidden');
            } else {
                if (notice) notice.classList.add('hidden');
            }
            document.getElementById('bookingModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.body.style.overflow = '';
            document.getElementById('appointmentName').value = '';
            document.getElementById('appointmentEmail').value = '';
            document.getElementById('appointmentPhone').value = '';
            document.getElementById('appointmentMessage').value = '';
            const notice = document.getElementById('booking-whatsapp-notice');
            if (notice) notice.classList.add('hidden');
            isWhatsAppRequest = false;
        }

        function submitBookingForm() {
            const name = document.getElementById('appointmentName').value.trim();
            const email = document.getElementById('appointmentEmail').value.trim();
            const phone = document.getElementById('appointmentPhone').value.trim();
            const message = document.getElementById('appointmentMessage').value.trim();
            
            if (!name || !email || !phone) {
                alert('Please fill in all required fields');
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Get submit button for loading state
            const submitBtn = event.target;
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending...';
            
            // Create FormData
            const formData = new FormData();
            formData.append('doctor_id', databaseDoctor.id);
            formData.append('name', name);
            formData.append('email', email);
            formData.append('phone', phone);
            formData.append('message', message);
            
            // Send to server
            fetch('save_enquiry.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    // Track enquiry submission in localStorage (Req 32)
                    if (data.track_enquiry) {
                        localStorage.setItem('hasSubmittedEnquiry', 'true');
                        localStorage.setItem('enquiryDoctorId', databaseDoctor.id);
                        localStorage.setItem('enquiryName', name);
                        localStorage.setItem('enquiryEmail', email);
                    }
                    closeBookingModal();
                    
                    // Prepare WhatsApp redirect
                    let whatsappNumber = (databaseDoctor.whatsapp || '').replace(/\D/g, '');
                    
                    // Add country code if not present (default to India +91)
                    if (whatsappNumber && !whatsappNumber.startsWith('91') && whatsappNumber.length === 10) {
                        whatsappNumber = '91' + whatsappNumber;
                    }
                    
                    // Fallback to placeholder support contact number if missing (Req 32)
                    if (!whatsappNumber) {
                        whatsappNumber = '919999999999'; 
                    }
                    
                    // Create detailed WhatsApp message with all form data
                    const whatsappMessage = encodeURIComponent(
                        `Hello Dr. ${databaseDoctor.name},\n\n` +
                        `I have submitted an enquiry on DrMap.\n\n` +
                        `*My Details:*\n` +
                        `Name: ${name}\n` +
                        `Email: ${email}\n` +
                        `Phone: ${phone}\n\n` +
                        `*Message:*\n${message}\n\n` +
                        `Looking forward to your response.`
                    );
                    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${whatsappMessage}`;
                    
                    // Show brief success message then redirect
                    showSuccessModal();
                    setTimeout(() => {
                        window.location.href = whatsappUrl;
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                alert('An error occurred. Please try again.');
                console.error('Error:', error);
            });
        }

        function openVideoModal(videoUrl) {
            const modal = document.getElementById('videoModal');
            const frame = document.getElementById('videoFrameFull');
            
            // Add autoplay and other parameters
            let finalUrl = videoUrl;
            if (videoUrl.includes('youtube.com') || videoUrl.includes('youtube-nocookie.com')) {
                finalUrl = videoUrl.includes('?') ? videoUrl + '&autoplay=1&mute=0' : videoUrl + '?autoplay=1&mute=0';
            } else if (videoUrl.includes('vimeo.com')) {
                finalUrl = videoUrl.includes('?') ? videoUrl + '&autoplay=1' : videoUrl + '?autoplay=1';
            } else {
                finalUrl = videoUrl.includes('?') ? videoUrl + '&autoplay=1' : videoUrl + '?autoplay=1';
            }
            
            frame.src = finalUrl;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const frame = document.getElementById('videoFrameFull');
            frame.src = '';
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Success Modal Functions
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Trigger confetti animation
            createConfetti();
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            
            // Clear form fields
            document.getElementById('appointmentName').value = '';
            document.getElementById('appointmentEmail').value = '';
            document.getElementById('appointmentPhone').value = '';
            document.getElementById('appointmentMessage').value = '';
        }

        // Confetti Effect
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            if (!container) return;
            
            container.innerHTML = ''; // Clear previous confetti
            
            const colors = ['#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b'];
            const confettiCount = 60;
            
            for (let i = 0; i < confettiCount; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                piece.style.left = Math.random() * 100 + '%';
                piece.style.top = '-10px';
                piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                piece.style.animationDelay = (Math.random() * 0.5) + 's';
                piece.style.animationDuration = (2 + Math.random() * 1) + 's';
                
                container.appendChild(piece);
            }
        }

        // Close modals on backdrop click
        document.getElementById('bookingModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeBookingModal();
        });

        document.getElementById('videoModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeVideoModal();
        });

        document.getElementById('successModal')?.addEventListener('click', e => {
            if (e.target === e.currentTarget) closeSuccessModal();
        });

        // Close modals on Escape
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeBookingModal();
                closeVideoModal();
                closeSuccessModal();
            }
        });

        // Preloader functionality
        const preloader = document.getElementById("preloader");
        if (preloader) {
            window.addEventListener("load", () => {
                setTimeout(() => {
                    preloader.classList.add("hidden");
                }, 1000);
            });
        }

        // ============================================
        // INITIALIZE
        // ============================================
        
        populateDoctorProfile();

        // DrMap GPT Offline Chatbot Helper (Req 12)
        function toggleGptChat() {
            const chatWin = document.getElementById('gpt-chat-window');
            chatWin.classList.toggle('hidden');
        }

        function askPreset(type) {
            let userMsg = '';
            let botMsg = '';

            if (type === 'hours') {
                userMsg = "What are the doctor's working hours?";
                const parsed = doctor.timing;
                let scheduleList = '';
                if (parsed) {
                    if (typeof parsed === 'object') {
                        Object.entries(parsed).forEach(([day, info]) => {
                            if (info.enabled && info.slots && info.slots.length > 0) {
                                const slotsStr = info.slots.map(s => `${s.open} - ${s.close}`).join(', ');
                                scheduleList += `<br/>• <strong>${day.toUpperCase()}:</strong> ${slotsStr}`;
                            }
                        });
                    } else if (typeof parsed === 'string') {
                        scheduleList = `<br/>${parsed}`;
                    }
                }
                botMsg = `<strong>${doctor.name}</strong> is available during these hours:${scheduleList || '<br/>Contact for details.'}`;
            } else if (type === 'location') {
                userMsg = "Where is the clinic located?";
                let addrStr = '';
                if (Array.isArray(doctor.addresses)) {
                    doctor.addresses.forEach((a, idx) => {
                        addrStr += `<br/><strong>Location ${idx+1}:</strong> ${a}`;
                    });
                } else {
                    addrStr = `<br/>${doctor.addresses || 'Practice city: ' + (doctor.practice_city || 'Guwahati')}`;
                }
                botMsg = `<strong>${doctor.name}</strong> practices at the following locations:${addrStr}`;
            } else if (type === 'booking') {
                userMsg = "How do I book an appointment?";
                botMsg = `You can easily book an appointment by clicking the <strong>"Book Now"</strong> button at the top of the page, or by calling our helpline at <strong>+91 99999 99999</strong>.`;
            } else if (type === 'symptoms') {
                userMsg = "Can you help me check my symptoms?";
                botMsg = `Sure! Please write down your symptoms (for example: <i>fever, toothache, chest pain, skin rash</i>). I will analyze them and suggest the best type of doctor/specialist in <strong>${doctor.practice_city || 'Guwahati'}</strong>.`;
            }

            appendGptMessage('user', userMsg);
            setTimeout(() => appendGptMessage('bot', botMsg), 500);
        }

        function sendGptMessage() {
            const input = document.getElementById('gpt-input');
            const txt = input.value.trim();
            if (!txt) return;

            appendGptMessage('user', txt);
            input.value = '';

            // Simple offline rules matcher supporting symptom assessment (Req 12 & Req 32)
            let reply = '';
            const lower = txt.toLowerCase();
            if (lower.includes('hour') || lower.includes('time') || lower.includes('availab')) {
                reply = `You can see <strong>${doctor.name}</strong>'s schedule under 'Availability' card in the Profile section.`;
            } else if (lower.includes('locat') || lower.includes('where') || lower.includes('address')) {
                reply = `<strong>${doctor.name}</strong> practices in <strong>${doctor.practice_city || 'Guwahati'}</strong>. Please check the Gallery / Address card for visual coordinates.`;
            } else if (lower.includes('contact') || lower.includes('phone') || lower.includes('call')) {
                reply = `You can call our helpline support at <strong>+91 99999 99999</strong> for quick consultation assistance.`;
            } else if (lower.includes('fever') || lower.includes('cough') || lower.includes('cold') || lower.includes('flu') || lower.includes('headache')) {
                reply = `Symptoms like fever, cough, or cold usually suggest seeing a <strong>General Physician</strong>. Since Dr. ${doctor.name} is a specialist in <strong>${doctor.specialty}</strong>, please submit an enquiry or check our doctors list to find a general physician in <strong>${doctor.practice_city || 'Guwahati'}</strong>.`;
            } else if (lower.includes('tooth') || lower.includes('teeth') || lower.includes('gum') || lower.includes('dent')) {
                reply = `Dental pain, toothache, or gum bleeding require a <strong>Dentist</strong>. We suggest checking our dentists in <strong>${doctor.practice_city || 'Guwahati'}</strong> on the Doctors list page.`;
            } else if (lower.includes('heart') || lower.includes('chest') || lower.includes('cardio')) {
                reply = `Chest tightness or cardiac symptoms require immediate consultation with a <strong>Cardiologist</strong>. If this is a medical emergency, please visit the nearest emergency room immediately.`;
            } else if (lower.includes('skin') || lower.includes('rash') || lower.includes('itch') || lower.includes('acne')) {
                reply = `For skin issues, rashes, or acne, we recommend consulting a <strong>Dermatologist</strong> in <strong>${doctor.practice_city || 'Guwahati'}</strong>.`;
            } else if (lower.includes('bone') || lower.includes('joint') || lower.includes('fracture') || lower.includes('ortho')) {
                reply = `Joint pain or bone issues require an <strong>Orthopedics Specialist</strong>.`;
            } else if (lower.includes('symptom') || lower.includes('feel bad') || lower.includes('sick')) {
                reply = `Please tell me your symptoms (e.g. fever, toothache, chest pain, rash) so I can help suggest the right medical specialist type for you.`;
            } else {
                reply = `I am DrMap's offline helper. For custom enquiries or questions, you can click 'Send Enquiry' at the bottom of the page or call our support lines.`;
            }

            setTimeout(() => appendGptMessage('bot', reply), 600);
        }

        function appendGptMessage(sender, text) {
            const area = document.getElementById('gpt-messages-area');
            if (!area) return;
            const row = document.createElement('div');
            row.className = 'flex gap-2 ' + (sender === 'user' ? 'justify-end' : '');
            
            if (sender === 'user') {
                row.innerHTML = `
                    <div class="bg-teal-500 text-white rounded-2xl p-3 shadow-sm max-w-[80%] leading-relaxed font-semibold">
                        ${text}
                    </div>
                `;
            } else {
                row.innerHTML = `
                    <div class="w-6 h-6 rounded-full bg-teal-500 flex items-center justify-center text-white text-[10px] flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-slate-100 text-slate-700 leading-relaxed shadow-sm max-w-[80%]">
                        ${text}
                    </div>
                `;
            }
            area.appendChild(row);
            area.scrollTop = area.scrollHeight;
        }
    </script>

    <!-- Ask DrMap GPT Bubble (Req 12) -->
    <div id="gpt-chat-container" class="fixed bottom-6 right-6 z-50 flex flex-col items-end">
        <!-- Chat window (initially hidden) -->
        <div id="gpt-chat-window" class="hidden w-80 sm:w-96 h-[450px] bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden flex flex-col mb-4 transition-all duration-300">
            <!-- Header -->
            <div class="bg-gradient-to-r from-teal-500 to-teal-600 p-4 text-white flex items-center justify-between shadow-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-lg">
                        <i class="fas fa-robot text-teal-100"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm leading-none">DrMap GPT</h4>
                        <span class="text-[10px] text-teal-100 font-medium">Local AI Assistant</span>
                    </div>
                </div>
                <button onclick="toggleGptChat()" class="text-white hover:text-teal-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Messages area -->
            <div id="gpt-messages-area" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 text-xs">
                <!-- Bot initial message -->
                <div class="flex gap-2">
                    <div class="w-6 h-6 rounded-full bg-teal-500 flex items-center justify-center text-white text-[10px] flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white rounded-2xl p-3 border border-slate-100 text-slate-700 leading-relaxed shadow-sm max-w-[80%]">
                        Hello! I am DrMap GPT, your offline health assistant. How can I help you regarding <strong><?php echo $doctorName; ?></strong> today?
                    </div>
                </div>
            </div>
            <!-- Preset Questions -->
            <div class="p-3 bg-white border-t border-slate-100 flex flex-wrap gap-2">
                <button onclick="askPreset('hours')" class="preset-btn text-[10px] font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1.5 rounded-full transition">
                    🕒 Working Hours?
                </button>
                <button onclick="askPreset('location')" class="preset-btn text-[10px] font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1.5 rounded-full transition">
                    📍 Location?
                </button>
                <button onclick="askPreset('booking')" class="preset-btn text-[10px] font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1.5 rounded-full transition">
                    📅 How to Book?
                </button>
                <button onclick="askPreset('symptoms')" class="preset-btn text-[10px] font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 px-2.5 py-1.5 rounded-full transition">
                    🌡️ Check Symptoms?
                </button>
            </div>
            <!-- Custom Input -->
            <div class="p-3 bg-white border-t border-slate-100 flex gap-2">
                <input type="text" id="gpt-input" placeholder="Ask a question..." onkeydown="if(event.key==='Enter') sendGptMessage()" class="flex-1 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:border-teal-500" />
                <button onclick="sendGptMessage()" class="w-8 h-8 rounded-xl bg-teal-500 text-white flex items-center justify-center hover:bg-teal-600 transition shadow-md shadow-teal-500/20">
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Toggle Button -->
        <button onclick="toggleGptChat()" class="w-14 h-14 rounded-full bg-gradient-to-br from-teal-500 to-teal-600 text-white flex items-center justify-center shadow-xl shadow-teal-500/30 hover:scale-105 transform transition duration-300">
            <i class="fas fa-comment-medical text-2xl"></i>
        </button>
    </div>
    <!-- Rating & Review Pop-up Modal (Req 32) -->
    <div id="ratingPopupModal" class="hidden fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" style="z-index: 10000000000000000000;">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-in">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-5 flex items-center justify-between text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fas fa-star-half-alt text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold">Rate Your Experience</h2>
                        <p class="text-amber-100 text-xs">Share feedback for Dr. <?php echo htmlspecialchars($doctorName); ?></p>
                    </div>
                </div>
                <button onclick="closeRatingPopup()" class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Body -->
            <form id="ratingPopupForm" class="p-6 space-y-4" onsubmit="submitRatingPopupForm(event)">
                <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                
                <!-- Star Rating selector -->
                <div class="text-center py-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Your Rating *</label>
                    <div class="flex justify-center gap-2" id="popupStarsContainer">
                        <button type="button" data-val="1" class="text-3xl text-slate-300 hover:scale-110 transition"><i class="fas fa-star"></i></button>
                        <button type="button" data-val="2" class="text-3xl text-slate-300 hover:scale-110 transition"><i class="fas fa-star"></i></button>
                        <button type="button" data-val="3" class="text-3xl text-slate-300 hover:scale-110 transition"><i class="fas fa-star"></i></button>
                        <button type="button" data-val="4" class="text-3xl text-slate-300 hover:scale-110 transition"><i class="fas fa-star"></i></button>
                        <button type="button" data-val="5" class="text-3xl text-slate-300 hover:scale-110 transition"><i class="fas fa-star"></i></button>
                    </div>
                    <input type="hidden" id="popupRatingValue" name="rating" value="0">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Full Name *</label>
                    <input type="text" id="popupReviewName" name="name" required class="form-input" placeholder="e.g. John Doe" />
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address *</label>
                    <input type="email" id="popupReviewEmail" name="email" required class="form-input" placeholder="john@example.com" />
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Your Review *</label>
                    <textarea id="popupReviewText" name="review_text" required class="form-input min-h-[80px]" placeholder="Tell us about your consultation..."></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" id="popupReviewSubmitBtn" class="w-full btn-primary bg-gradient-to-r from-amber-500 to-amber-600 border-none hover:from-amber-600 hover:to-amber-700 text-white font-bold py-3 rounded-xl transition duration-300 flex items-center justify-center gap-2">
                        <span>Submit Review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rating Popup Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Interactive Stars
            const stars = document.querySelectorAll('#popupStarsContainer button');
            const ratingInput = document.getElementById('popupRatingValue');
            
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const val = parseInt(star.getAttribute('data-val'));
                    ratingInput.value = val;
                    updatePopupStars(val);
                });
                star.addEventListener('mouseover', () => {
                    const val = parseInt(star.getAttribute('data-val'));
                    updatePopupStars(val);
                });
            });

            document.getElementById('popupStarsContainer').addEventListener('mouseleave', () => {
                const currentRating = parseInt(ratingInput.value) || 0;
                updatePopupStars(currentRating);
            });

            function updatePopupStars(val) {
                stars.forEach((s, idx) => {
                    if (idx < val) {
                        s.classList.remove('text-slate-300');
                        s.classList.add('text-amber-400');
                    } else {
                        s.classList.remove('text-amber-400');
                        s.classList.add('text-slate-300');
                    }
                });
            }

            // Auto-trigger Popup after 5 seconds delay if conditions met (Req 32)
            const doctorId = "<?php echo $doctor_id; ?>";
            const hasSubmittedEnquiry = localStorage.getItem('hasSubmittedEnquiry') === 'true';
            const enquiryDocId = localStorage.getItem('enquiryDoctorId');
            const hasReviewed = localStorage.getItem('hasSubmittedReview_' + doctorId) === 'true';

            if (hasSubmittedEnquiry && String(enquiryDocId) === String(doctorId) && !hasReviewed) {
                setTimeout(() => {
                    openRatingPopup();
                }, 5000);
            }
        });

        function openRatingPopup() {
            // Pre-fill fields if values exist in localStorage
            const savedName = localStorage.getItem('enquiryName') || '';
            const savedEmail = localStorage.getItem('enquiryEmail') || '';
            
            if (savedName) document.getElementById('popupReviewName').value = savedName;
            if (savedEmail) document.getElementById('popupReviewEmail').value = savedEmail;

            document.getElementById('ratingPopupModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeRatingPopup() {
            document.getElementById('ratingPopupModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function submitRatingPopupForm(event) {
            event.preventDefault();
            
            const rating = parseInt(document.getElementById('popupRatingValue').value) || 0;
            if (rating < 1 || rating > 5) {
                alert('Please click on the stars to choose a rating.');
                return;
            }

            const form = document.getElementById('ratingPopupForm');
            const submitBtn = document.getElementById('popupReviewSubmitBtn');
            const origHtml = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';

            const formData = new FormData(form);
            
            fetch('submit_review.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
                if (data.success) {
                    // Mark as reviewed to prevent popup from opening again
                    localStorage.setItem('hasSubmittedReview_' + "<?php echo $doctor_id; ?>", 'true');
                    alert(data.message);
                    closeRatingPopup();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
                alert('An error occurred. Please try again.');
                console.error(err);
            });
        }
    </script>
</body>
</html>
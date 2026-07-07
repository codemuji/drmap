<!-- Sidebar -->
<style>
/* Sidebar-local styles (keeps sidebar self-contained) */
.sidebar-gradient {
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
}
.sidebar-link {
    color: rgba(148,163,184,1);
}
.sidebar-link:hover { color: #ffffff; }
.sidebar-active {
    /* make sure the anchor is positioned for the pseudo indicator */
    position: relative;
    background: linear-gradient(90deg, rgba(14,165,233,0.14) 0%, rgba(14,165,233,0.04) 100%);
}
.sidebar-active::before {
    /* left indicator that respects padding and border-radius */
    content: '';
    position: absolute;
    left: 0;
    top: 6px;
    bottom: 6px;
    width: 4px;
    background: linear-gradient(180deg, #38bdf8 0%, #0ea5e9 100%);
    border-radius: 0 6px 6px 0;
    pointer-events: none;
}
.sidebar-scroll { overflow-y: auto; }
.sidebar-badge { background: rgba(14,165,233,0.12); color: #7dd3fc; padding: 2px 8px; border-radius: 8px; font-weight:700; font-size:11px; }
.sidebar-profile .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Global panel styles (moved from index.php for consistent UI) */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
* { font-family: 'Inter', system-ui, sans-serif; }

/* Premium Scrollbar */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.3); border-radius: 100px; }
::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.5); }

/* Disable default transitions, enable custom ones */
*, *::before, *::after { transition-property: none; }
.transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4,0,0.2,1); transition-duration:200ms; }
.transition-transform { transition-property: transform; transition-timing-function: cubic-bezier(0.4,0,0.2,1); transition-duration:200ms; }
.transition-colors { transition-property: color, background-color, border-color; transition-timing-function: cubic-bezier(0.4,0,0.2,1); transition-duration:150ms; }
.transition-opacity { transition-property: opacity; transition-timing-function: cubic-bezier(0.4,0,0.2,1); transition-duration:200ms; }
.transition-shadow { transition-property: box-shadow; transition-timing-function: cubic-bezier(0.4,0,0.2,1); transition-duration:200ms; }

/* Glass Morphism */
.glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border:1px solid rgba(255,255,255,0.3); }
.glass-dark { background: rgba(15,23,42,0.8); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border:1px solid rgba(255,255,255,0.1); }

/* Gradient Mesh Background */
.mesh-gradient { background-color:#f8fafc; background-image: radial-gradient(at 40% 20%, rgba(14,165,233,0.08) 0px, transparent 50%), radial-gradient(at 80% 0%, rgba(124,58,237,0.06) 0px, transparent 50%), radial-gradient(at 0% 50%, rgba(16,185,129,0.06) 0px, transparent 50%), radial-gradient(at 80% 50%, rgba(245,158,11,0.04) 0px, transparent 50%), radial-gradient(at 0% 100%, rgba(14,165,233,0.06) 0px, transparent 50%); }

/* Premium Card Effects */
.card-premium { background:white; border:1px solid rgba(226,232,240,0.8); box-shadow:0 1px 3px rgba(0,0,0,0.02),0 4px 12px rgba(0,0,0,0.04); }
.card-premium:hover { border-color: rgba(14,165,233,0.3); box-shadow: 0 1px 3px rgba(0,0,0,0.02),0 8px 24px rgba(0,0,0,0.08),0 0 0 1px rgba(14,165,233,0.1); }

/* Stat Card Gradient Overlays */
.stat-gradient-blue { background: linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%); }
.stat-gradient-purple { background: linear-gradient(135deg,#8b5cf6 0%,#7c3aed 100%); }
.stat-gradient-emerald { background: linear-gradient(135deg,#10b981 0%,#059669 100%); }
.stat-gradient-amber { background: linear-gradient(135deg,#f59e0b 0%,#d97706 100%); }

/* Button Styles */
.btn-primary { background: linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%); box-shadow: 0 4px 14px rgba(14,165,233,0.35); }
.btn-primary:hover { box-shadow: 0 6px 20px rgba(14,165,233,0.45); transform: translateY(-1px); }
.btn-primary:active { transform: translateY(0); }

/* Input Focus Ring */
.input-premium:focus { border-color:#0ea5e9; box-shadow: 0 0 0 4px rgba(14,165,233,0.1); outline:none; }

/* Table Row Hover */
.table-row:hover { background: linear-gradient(90deg, rgba(14,165,233,0.02) 0%, rgba(14,165,233,0.05) 50%, rgba(14,165,233,0.02) 100%); }

/* Modal Animations */
.modal-overlay { opacity:0; transition: opacity 0.3s ease; }
.modal-overlay.active { opacity:1; }
.modal-content { opacity:0; transform:scale(0.95) translateY(10px); transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.modal-overlay.active .modal-content { opacity:1; transform: scale(1) translateY(0); }

/* Badge Pulse */
.badge-pulse { animation: pulse-ring 2s cubic-bezier(0.4,0,0.6,1) infinite; }
@keyframes pulse-ring { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

/* Skeleton Loading */
.skeleton { background: linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%); background-size:200% 100%; animation: skeleton-loading 1.5s infinite; }
@keyframes skeleton-loading { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }

/* Floating Label */
.floating-label { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8; pointer-events:none; transition: all 0.2s ease; font-size:14px; }
.floating-input:focus ~ .floating-label, .floating-input:not(:placeholder-shown) ~ .floating-label { top:0; transform:translateY(-50%) scale(0.85); background:white; padding:0 6px; color:#0ea5e9; font-weight:500; }

/* Avatar Ring */
.avatar-ring { box-shadow: 0 0 0 3px white, 0 0 0 5px rgba(14,165,233,0.2); }

/* Status Dot Animation */
.status-online::after { content: ''; position:absolute; width:100%; height:100%; border-radius:50%; background:inherit; animation: status-ping 1.5s cubic-bezier(0,0,0.2,1) infinite; }
@keyframes status-ping { 75%,100%{ transform:scale(2); opacity:0; } }

/* Tooltip */
.tooltip { position: relative; }
.tooltip::before { content: attr(data-tooltip); position:absolute; bottom:100%; left:50%; transform: translateX(-50%) translateY(-4px); background:#1e293b; color:white; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; white-space:nowrap; opacity:0; visibility:hidden; transition: all 0.2s ease; z-index:50; }
.tooltip::after { content:''; position:absolute; bottom:100%; left:50%; transform: translateX(-50%); border:5px solid transparent; border-top-color:#1e293b; opacity:0; visibility:hidden; transition: all 0.2s ease; }
.tooltip:hover::before, .tooltip:hover::after { opacity:1; visibility:visible; }

/* Chart Container */
.chart-container { position:relative; height:200px; }

/* Notification Toast */
.toast { transform: translateX(400px); transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1); }
.toast.show { transform: translateX(0); }

/* Premium Badge */
.premium-badge { background: linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%); box-shadow:0 2px 8px rgba(245,158,11,0.3); }

/* Action Button Group */
.action-btn { width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:10px; transition: all 0.2s ease; }
.action-btn:hover { transform: scale(1.1); }
.action-btn:active { transform: scale(0.95); }

/* Number Counter Animation */
.counter { display:inline-block; }

/* Shimmer Effect */
.shimmer { position:relative; overflow:hidden; }
.shimmer::after { content:''; position:absolute; top:0; right:0; bottom:0; left:0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); transform: translateX(-100%); animation: shimmer 2s infinite; }
@keyframes shimmer { 100%{ transform: translateX(100%); } }

/* Command Palette */
.command-palette { max-height:400px; overflow-y:auto; }

/* Focus Visible */
button:focus-visible, a:focus-visible, input:focus-visible { outline:2px solid #0ea5e9; outline-offset:2px; }

/* Micro-interaction for checkboxes */
.checkbox-premium { appearance:none; width:18px; height:18px; border:2px solid #cbd5e1; border-radius:5px; cursor:pointer; transition: all 0.2s ease; position:relative; }
.checkbox-premium:checked { background: linear-gradient(135deg,#0ea5e9 0%,#0284c7 100%); border-color:#0ea5e9; }
.checkbox-premium:checked::after { content:''; position:absolute; left:5px; top:2px; width:4px; height:8px; border: solid white; border-width:0 2px 2px 0; transform: rotate(45deg); }

/* Progress Ring */
.progress-ring { transform: rotate(-90deg); }
.progress-ring__circle { stroke-dasharray:100; stroke-dashoffset:100; transition: stroke-dashoffset 0.5s ease; }

/* Prevent horizontal overflow and ensure main scrolls vertically */
html, body { max-width:100%; overflow-x:hidden; }
main { min-height:100vh; overflow-y:auto; -webkit-overflow-scrolling: touch; box-sizing: border-box; }
@media (min-width:1024px) { main { margin-left:280px; } }

/* Make modal responsive on very small viewports */
.modal-content { max-width: calc(100% - 48px); width:100%; }

/* Compact table styles to reduce vertical space */
.card-premium table { font-size:13px; }
.card-premium thead th, .card-premium tbody td { padding:8px 10px; vertical-align: middle; }
.card-premium .table-row { min-height:44px; }
.card-premium table img { width:36px !important; height:36px !important; border-radius:8px !important; }
.card-premium .action-btn { width:30px; height:30px; border-radius:8px; font-size:12px; }
.card-premium td .space-y-1 { gap:2px; }
.card-premium td p, .card-premium td span, .card-premium td .truncate { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px; display:block; }
@media (max-width:1100px) { .card-premium thead th:nth-child(5), .card-premium tbody td:nth-child(5) { display:none; } .card-premium thead th:nth-child(4), .card-premium tbody td:nth-child(4) { display:none; } .card-premium thead th:nth-child(2), .card-premium tbody td:nth-child(2) { max-width:220px; } }
@media (max-width:760px) { .card-premium thead th:nth-child(6), .card-premium tbody td:nth-child(6) { display:none; } }
</style>

<aside class="w-[280px] sidebar-gradient text-white fixed h-full hidden lg:flex flex-col z-50">
    <!-- Logo -->
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/30">
                <i class="fa-solid fa-heart-pulse text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight">DrMap</h1>
                <p class="text-xs text-dark-400 font-medium">Healthcare Platform</p>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="px-4 py-4">
        <button onclick="openCommandPalette()" class="w-full flex items-center px-4 py-2.5 bg-white/5 hover:bg-white/10 rounded-xl text-left transition-colors group">
            <i class="fa-solid fa-magnifying-glass text-dark-400 mr-3 text-sm"></i>
            <span class="text-sm text-dark-400 flex-1">Search...</span>
            <kbd class="px-1.5 py-0.5 text-xs bg-white/10 text-dark-400 rounded font-medium">⌘K</kbd>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 space-y-1 overflow-y-auto">
        <div class="px-3 py-2 text-xs font-semibold text-dark-500 uppercase tracking-wider">Main Menu</div>
        
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        function getSidebarClass($page, $current) {
            return $page === $current 
                ? 'sidebar-active relative flex items-center px-4 py-3 rounded-xl font-medium text-white transition-colors' 
                : 'relative flex items-center px-4 py-3 rounded-xl font-medium text-dark-400 hover:text-white hover:bg-white/5 transition-colors';
        }
        function getIconClass($page, $current) {
            return $page === $current ? 'text-primary-400' : '';
        }
        ?>

        <a href="index.php" class="<?php echo getSidebarClass('index.php', $currentPage); ?>">
            <i class="fa-solid fa-grid-2 w-5 mr-3 <?php echo getIconClass('index.php', $currentPage); ?>"></i>
            <span>Dashboard</span>
        </a>
        <a href="doctors.php" class="<?php echo getSidebarClass('doctors.php', $currentPage); ?>">
            <i class="fa-solid fa-user-doctor w-5 mr-3 <?php echo getIconClass('doctors.php', $currentPage); ?>"></i>
            <span>Doctors</span>
        </a>

        <a href="specialties.php" class="<?php echo getSidebarClass('specialties.php', $currentPage); ?>">
            <i class="fa-solid fa-stethoscope w-5 mr-3 <?php echo getIconClass('specialties.php', $currentPage); ?>"></i>
            <span>Specialties</span>
        </a>

        <a href="cities.php" class="<?php echo getSidebarClass('cities.php', $currentPage); ?>">
            <i class="fa-solid fa-city w-5 mr-3 <?php echo getIconClass('cities.php', $currentPage); ?>"></i>
            <span>Cities</span>
        </a>

        <a href="hospitals.php" class="<?php echo getSidebarClass('hospitals.php', $currentPage); ?>">
            <i class="fa-solid fa-hospital w-5 mr-3 <?php echo getIconClass('hospitals.php', $currentPage); ?>"></i>
            <span>Hospitals</span>
        </a>

        <a href="enquiries.php" class="<?php echo getSidebarClass('enquiries.php', $currentPage); ?>">
            <i class="fa-solid fa-envelope w-5 mr-3 <?php echo getIconClass('enquiries.php', $currentPage); ?>"></i>
            <span>Enquiries</span>
        </a>

        <a href="reviews.php" class="<?php echo getSidebarClass('reviews.php', $currentPage); ?>">
            <i class="fa-solid fa-star w-5 mr-3 <?php echo getIconClass('reviews.php', $currentPage); ?>"></i>
            <span>Reviews</span>
        </a>
    </nav>

    

    <!-- User Profile -->
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face" 
                     class="w-10 h-10 rounded-xl object-cover">
                <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-dark-900 rounded-full status-online"></div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm truncate">DrMap Admin</p>
                <p class="text-xs text-dark-400 truncate">alex@drmap.com</p>
            </div>
            <a href="logout.php" class="w-8 h-8 rounded-lg hover:bg-red-500/20 flex items-center justify-center text-dark-400 hover:text-red-400 transition-colors tooltip" data-tooltip="Logout">
                <i class="fa-solid fa-sign-out"></i>
            </a>
        </div>
    </div>
</aside>

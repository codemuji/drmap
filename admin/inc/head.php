<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DrMap - Healthcare Management Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        display: ['SF Pro Display', 'Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'glow': '0 0 40px -10px rgba(14, 165, 233, 0.3)',
                        'glow-lg': '0 0 60px -15px rgba(14, 165, 233, 0.4)',
                        'inner-glow': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* Premium Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 100px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }

        /* Disable default transitions, enable custom ones */
        *, *::before, *::after {
            transition-property: none;
        }
        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
        .transition-transform {
            transition-property: transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
        .transition-colors {
            transition-property: color, background-color, border-color;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        .transition-opacity {
            transition-property: opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
        .transition-shadow {
            transition-property: box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Glass Morphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Gradient Mesh Background */
        .mesh-gradient {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 40% 20%, rgba(14, 165, 233, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(124, 58, 237, 0.06) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(16, 185, 129, 0.06) 0px, transparent 50%),
                radial-gradient(at 80% 50%, rgba(245, 158, 11, 0.04) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(14, 165, 233, 0.06) 0px, transparent 50%);
        }

        /* Sidebar styles moved to inc/sidebar.php */

        /* Premium Card Effects */
        .card-premium {
            background: white;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.02),
                0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .card-premium:hover {
            border-color: rgba(14, 165, 233, 0.3);
            box-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.02),
                0 8px 24px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(14, 165, 233, 0.1);
        }

        /* Stat Card Gradient Overlays */
        .stat-gradient-blue {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
        }
        .stat-gradient-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        .stat-gradient-emerald {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .stat-gradient-amber {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.35);
        }
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.45);
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }

        /* Input Focus Ring */
        .input-premium:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
            outline: none;
        }

        /* Table Row Hover */
        .table-row:hover {
            background: linear-gradient(90deg, rgba(14, 165, 233, 0.02) 0%, rgba(14, 165, 233, 0.05) 50%, rgba(14, 165, 233, 0.02) 100%);
        }

        /* Sidebar styles moved to inc/sidebar.php */

        /* Modal Animations */
        .modal-overlay {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
        }
        .modal-content {
            opacity: 0;
            transform: scale(0.95) translateY(10px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-overlay.active .modal-content {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        /* Badge Pulse */
        .badge-pulse {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Floating Label */
        .floating-label {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        .floating-input:focus ~ .floating-label,
        .floating-input:not(:placeholder-shown) ~ .floating-label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            background: white;
            padding: 0 6px;
            color: #0ea5e9;
            font-weight: 500;
        }

        /* Avatar Ring */
        .avatar-ring {
            box-shadow: 0 0 0 3px white, 0 0 0 5px rgba(14, 165, 233, 0.2);
        }

        /* Status Dot Animation */
        .status-online::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            animation: status-ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes status-ping {
            75%, 100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
        }
        .tooltip::before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-4px);
            background: #1e293b;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 50;
        }
        .tooltip::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1e293b;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }
        .tooltip:hover::before,
        .tooltip:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 200px;
        }

        /* Notification Toast */
        .toast {
            transform: translateX(400px);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .toast.show {
            transform: translateX(0);
        }

        /* Premium Badge */
        .premium-badge {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }

        /* Action Button Group */
        .action-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .action-btn:active {
            transform: scale(0.95);
        }

        /* Number Counter Animation */
        .counter {
            display: inline-block;
        }

        /* Shimmer Effect */
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        .shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }

        /* Command Palette */
        .command-palette {
            max-height: 400px;
            overflow-y: auto;
        }

        /* Focus Visible */
        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 2px solid #0ea5e9;
            outline-offset: 2px;
        }

        /* Micro-interaction for checkboxes */
        .checkbox-premium {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid #cbd5e1;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .checkbox-premium:checked {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            border-color: #0ea5e9;
        }
        .checkbox-premium:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Progress Ring */
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring__circle {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            transition: stroke-dashoffset 0.5s ease;
        }
        /* Prevent horizontal overflow and ensure main scrolls vertically */
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }

        main {
            min-height: 100vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
        }

        /* Make modal responsive on very small viewports */
        .modal-content {
            max-width: calc(100% - 48px);
            width: 100%;
        }

        /* Compact table styles to reduce vertical space */
        .card-premium table {
            font-size: 13px;
        }
        .card-premium thead th,
        .card-premium tbody td {
            padding: 8px 10px;
            vertical-align: middle;
        }
        .card-premium .table-row {
            min-height: 44px;
        }

        /* Smaller avatars inside table */
        .card-premium table img {
            width: 36px !important;
            height: 36px !important;
            border-radius: 8px !important;
        }

        /* Tighten action buttons */
        .card-premium .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            font-size: 12px;
        }

        /* Reduce gap and spacing in contact block */
        .card-premium td .space-y-1 { gap: 2px; }

        /* Prevent long text from wrapping and use ellipsis */
        .card-premium td p,
        .card-premium td span,
        .card-premium td .truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 220px;
            display: block;
        }

        /* Hide less important columns on narrow screens */
        @media (max-width: 1100px) {
            .card-premium thead th:nth-child(5), /* Contact */
            .card-premium tbody td:nth-child(5) {
                display: none;
            }
            .card-premium thead th:nth-child(4), /* Experience */
            .card-premium tbody td:nth-child(4) {
                display: none;
            }
            .card-premium thead th:nth-child(2),
            .card-premium tbody td:nth-child(2) {
                max-width: 220px;
            }
        }

        @media (max-width: 760px) {
            .card-premium thead th:nth-child(6), /* Rating */
            .card-premium tbody td:nth-child(6) {
                display: none;
            }
        }
    </style>
</head>
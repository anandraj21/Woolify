<?php
session_start();
require_once 'config/database.php';

// Initialize database connection
$database = Database::getInstance();

// Fetch real-time statistics
$stmt = $database->query("SELECT COUNT(*) as farm_count FROM farmers");
$farmCount = $stmt->fetch()['farm_count'];

$stmt = $database->query("SELECT SUM(quantity) as total_wool FROM wool_batches");
$totalWool = $stmt->fetch()['total_wool'];

$stmt = $database->query("SELECT COUNT(*) as batch_count FROM wool_batches");
$batchCount = $stmt->fetch()['batch_count'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woolify - Sustainable Wool Supply Chain Tracking</title>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dependencies -->
    <script src="https://unpkg.com/framer-motion@10.16.4/dist/framer-motion.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#F3F7F3',
                            100: '#E7EFE7',
                            200: '#C5D9C6',
                            300: '#A3C3A4',
                            400: '#81AD82',
                            500: '#5F975F',
                            600: '#4C794C',
                            700: '#395B3A',
                            800: '#263D27',
                            900: '#131F13'
                        },
                        accent: {
                            light: '#FFE5B4',
                            DEFAULT: '#FFD700',
                            dark: '#DAA520'
                        }
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        display: ['Inter', 'sans-serif']
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'shine': 'shine 1.5s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        shine: {
                            '0%': { backgroundPosition: '200% center' },
                            '100%': { backgroundPosition: '-200% center' }
                        }
                    },
                    backdropBlur: {
                        xs: '2px'
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --color-primary: #5F975F;
            --color-primary-light: #A3C3A4;
            --color-primary-dark: #4C794C;
            --color-accent: #FFD700;
            --color-accent-light: #FFE5B4;
            --color-accent-dark: #DAA520;
            --color-surface: #FFFFFF;
            --color-background: #F9FAF9;
            --color-text: #1A1A1A;
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
        }

        /* Modern Button Styles */
        .btn-primary {
            @apply relative overflow-hidden px-8 py-4 rounded-xl font-semibold text-white transition-all duration-500;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
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
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(95, 151, 95, 0.3);
        }

        /* Glass Card Effect */
        .glass-card {
            @apply backdrop-blur-lg bg-white/90 border border-white/20 shadow-xl;
            background: rgba(255, 255, 255, 0.95);
        }

        /* Animated Gradient Background */
        .gradient-animate {
            background: linear-gradient(
                270deg,
                var(--color-primary-light),
                var(--color-primary),
                var(--color-primary-dark)
            );
            background-size: 200% 200%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Scroll Progress Bar */
        .scroll-progress {
            @apply fixed top-0 left-0 w-full h-1 z-50;
            background: linear-gradient(to right, var(--color-primary), var(--color-accent));
            transform-origin: left;
        }

        /* Modern Navbar */
        .navbar {
            @apply fixed top-0 w-full z-40 transition-all duration-300;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            @apply shadow-lg;
            background: rgba(255, 255, 255, 0.95);
        }

        .nav-link {
            @apply relative font-medium text-gray-700 hover:text-primary-600 transition-colors;
            padding-bottom: 2px;
        }

        .nav-link::after {
            content: '';
            @apply absolute bottom-0 left-0 w-0 h-0.5 bg-primary-500 transition-all duration-300;
        }

        .nav-link:hover::after {
            @apply w-full;
        }

        .hero-section {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(44, 95, 45, 0.95), rgba(151, 188, 98, 0.85));
            z-index: 0;
        }

        .navbar {
            @apply fixed top-0 w-full z-50 transition-all duration-300;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            @apply shadow-lg;
        }

        .btn-primary {
            @apply px-8 py-4 rounded-xl font-semibold text-white transition-all duration-500;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
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
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(45, 90, 39, 0.3);
        }

        .btn-secondary {
            @apply px-8 py-4 rounded-xl font-semibold transition-all duration-300;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--color-surface);
            color: var(--color-surface);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .feature-card {
            @apply p-8 rounded-2xl transition-all duration-500;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            @apply p-8 rounded-2xl text-white relative overflow-hidden;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        }

        .journey-step {
            @apply relative p-8;
        }

        .journey-step::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 2px;
            height: 100%;
            background: var(--color-accent);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 1s ease;
        }

        .journey-step.active::before {
            transform: scaleY(1);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .scroll-indicator {
            @apply fixed right-4 top-1/2 transform -translate-y-1/2 z-40;
        }

        .scroll-dot {
            @apply w-2 h-2 rounded-full bg-gray-400 my-2 transition-all duration-300;
        }

        .scroll-dot.active {
            @apply bg-primary h-4;
        }

        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-primary);
            transform-origin: left;
            transform: scaleX(0);
            z-index: 1000;
        }

        .nav-link {
            @apply relative font-medium text-gray-700 hover:text-primary transition-colors;
            padding-bottom: 2px;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        @media (max-width: 768px) {
            .hero-section {
                background-attachment: scroll;
            }
        }

        /* Impact Stats Section */
        .impact-stats {
            position: relative;
            padding: 6rem 0;
            background-color: var(--color-primary);
            color: var(--color-light);
            overflow: hidden;
        }

        .impact-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/impact-pattern.svg');
            background-size: cover;
            opacity: 0.1;
            pointer-events: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Call to Action Section */
        .cta-section {
            position: relative;
            padding: 6rem 0;
            background-color: var(--color-secondary);
            color: var(--color-light);
            overflow: hidden;
        }

        .cta-pattern {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/cta-pattern.svg');
            background-size: cover;
            opacity: 0.1;
            pointer-events: none;
        }

        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background-color: #1a1a1a;
            color: var(--color-light);
            padding: 4rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-about {
            max-width: 300px;
        }

        .footer-logo {
            height: 40px;
            margin-bottom: 1rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-link {
            color: var(--color-light);
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .social-link:hover {
            color: var(--color-accent);
        }

        .footer-links h3,
        .footer-contact h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            font-family: 'Space Grotesk', sans-serif;
        }

        .footer-links ul,
        .footer-contact ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li,
        .footer-contact li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            color: var(--color-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--color-accent);
        }

        .footer-contact i {
            margin-right: 0.5rem;
            color: var(--color-accent);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 0.9rem;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .footer-about {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Scroll Indicator -->
    <div class="scroll-indicator hidden md:block">
        <div class="scroll-dot" data-section="hero"></div>
        <div class="scroll-dot" data-section="journey"></div>
        <div class="scroll-dot" data-section="impact"></div>
        <div class="scroll-dot" data-section="features"></div>
    </div>

    <!-- Scroll Progress Bar -->
    <div class="scroll-progress" id="scrollProgress"></div>

    <!-- Floating Chat Button -->
    <button class="fixed bottom-8 right-8 z-50 p-4 bg-primary-500 text-white rounded-full shadow-lg hover:shadow-2xl transition-all duration-300 group animate-pulse-slow">
        <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
    </button>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="fixed bottom-8 right-24 z-50 p-4 bg-white text-primary-500 rounded-full shadow-lg hover:shadow-2xl transition-all duration-300 opacity-0 translate-y-10">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4">
                    <a href="#" class="flex items-center space-x-3 group">
                        <img src="public/assets/images/logo.png" alt="Woolify" class="h-10 w-auto group-hover:scale-105 transition-transform rounded-3xl">
                        <span class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">Woolify</span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#journey" class="nav-link">Journey</a>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#impact" class="nav-link">Impact</a>
                    <div class="relative group">
                        <button class="nav-link flex items-center">
                            <a href="resources/index.php"> Resources</a>
                            <svg class="w-4 h-4 ml-1 transform group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="absolute top-full right-0 w-48 py-2 mt-2 bg-white rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top scale-95 group-hover:scale-100">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Documentation</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">API Reference</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Case Studies</a>
                        </div>
                    </div>
                    <a href="login.php" class="px-6 py-2 text-primary-600 font-medium hover:text-primary-700 transition-colors">Login</a>
                    <a href="register.php" class="btn-primary group">
                        Get Started
                        <span class="ml-2 group-hover:translate-x-1 transition-transform inline-block">→</span>
                    </a>
                </div>

                <button class="md:hidden" id="mobileMenuBtn">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="md:hidden hidden" id="mobileMenu">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
            <a href="#journey" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-lg">Journey</a>
            <a href="#features" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-lg">Features</a>
            <a href="#impact" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-lg">Impact</a>
            <a href="login.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-lg">Login</a>
            <a href="register.php" class="block px-3 py-2 text-base font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">Get Started</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-b from-primary-50 to-white">
        <!-- Background Video -->   
        <video class="absolute top-0 left-0 w-full h-full object-cover opacity-100" autoplay loop muted playsinline>
            <source src="public/assets/videos/hero-bg2.mp4" type="video/mp4">
        </video>

        <!-- Animated Background Shapes -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-1/2 -left-1/4 w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-float"></div>
            <div class="absolute -bottom-1/2 -right-1/4 w-96 h-96 bg-accent-light rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-float" style="animation-delay: -2s"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 relative z-10">
            <div class="text-center">
                <h1 class="text-5xl md:text-7xl font-bold mb-8 leading-tight">
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400" id="typewriter">
                        From Farm to Fabric
                    </span>
                    <span class="block mt-2 text-gray-900">
                        Transparent Wool Tracking
                    </span>
                </h1>
                
                <p class="text-xl md:text-2xl mb-12 text-gray-600 max-w-3xl mx-auto">
                    Experience the journey of sustainable wool production with complete transparency and traceability at every step.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-6">
                    <a href="register.php" class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl bg-primary-600 px-8 py-4 font-semibold text-white transition-all duration-300 hover:bg-primary-700">
                        <div class="absolute inset-0 w-full h-full transition-all duration-300 group-hover:bg-opacity-90"></div>
                        <div class="absolute bottom-0 right-0 mb-32 mr-4 hidden rotate-12 transform text-white sm:block">
                            <svg class="w-24 h-24 opacity-20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm0-15.5v5l4.4 2.2"/>
                            </svg>
                        </div>
                        <span class="relative">Start Tracking</span>
                        <svg class="relative ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    
                    <a href="#journey" class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl border-2 border-primary-200 bg-transparent px-8 py-4 font-semibold text-primary-600 transition-all duration-300 hover:border-primary-600 hover:bg-primary-50">
                        <span class="relative">Learn More</span>
                        <svg class="relative ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>

                <!-- Trust Badges -->
                <!-- <div class="mt-16 grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div class="flex items-center justify-center">
                        <img class="h-12 opacity-50 hover:opacity-100 transition-opacity" src="assets/images/trust-badge-1.svg" alt="Certification 1">
                    </div>
                    <div class="flex items-center justify-center">
                        <img class="h-12 opacity-50 hover:opacity-100 transition-opacity" src="assets/images/trust-badge-2.svg" alt="Certification 2">
                    </div>
                    <div class="flex items-center justify-center">
                        <img class="h-12 opacity-50 hover:opacity-100 transition-opacity" src="assets/images/trust-badge-3.svg" alt="Certification 3">
                    </div>
                    <div class="flex items-center justify-center">
                        <img class="h-12 opacity-50 hover:opacity-100 transition-opacity" src="assets/images/trust-badge-4.svg" alt="Certification 4">
                    </div>
                </div> -->
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 flex flex-col items-center animate-bounce">
            <span class="text-sm text-gray-500 mb-2">Scroll to explore</span>
            <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    <!-- Journey Section -->
    <section id="journey" class="py-24 relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-gradient-to-b from-white to-primary-50/30"></div>
        
        <!-- Animated Dots Pattern -->
        <div class="absolute inset-0" aria-hidden="true">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:14px_24px]"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6" data-aos="fade-up">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">
                        The Journey of Wool
                    </span>
                </h2>
                <p class="text-gray-600 text-lg" data-aos="fade-up" data-aos-delay="100">
                    Follow the sustainable path from farm to final product, ensuring quality and transparency at every step.
                </p>
            </div>

            <!-- Timeline Steps -->
            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-0.5 bg-gradient-to-b from-primary-200 via-primary-400 to-primary-600"></div>

                <!-- Timeline Steps -->
                <div class="space-y-24">
                    <!-- Step 1: Ethical Farming -->
                    <div class="relative" data-aos="fade-right">
                        <div class="flex items-center">
                            <div class="flex-1 pr-12 text-right">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">Ethical Farming</h3>
                                    <p class="text-gray-600 mb-6">Our partner farms prioritize animal welfare and sustainable practices, ensuring the highest quality wool.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center justify-end">
                                            <span>Ethical sheep farming</span>
                                            <svg class="w-5 h-5 ml-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                        <li class="flex items-center justify-end">
                                            <span>Sustainable practices</span>
                                            <svg class="w-5 h-5 ml-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                        <li class="flex items-center justify-end">
                                            <span>Quality assurance</span>
                                            <svg class="w-5 h-5 ml-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center w-12 h-12 rounded-full border-4 border-primary-200 bg-white shadow-xl">
                                <span class="text-lg font-bold text-primary-600">1</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Smart Processing -->
                    <div class="relative" data-aos="fade-left">
                        <div class="flex items-center">
                            <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center w-12 h-12 rounded-full border-4 border-primary-200 bg-white shadow-xl">
                                <span class="text-lg font-bold text-primary-600">2</span>
                            </div>
                            <div class="flex-1 pl-12">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">Smart Processing</h3>
                                    <p class="text-gray-600 mb-6">Advanced technology ensures efficient processing while maintaining wool quality and sustainability.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Eco-friendly processing</span>
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Quality control</span>
                                        </li>
                                        <li class="flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Minimal waste</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Distribution -->
                    <div class="relative" data-aos="fade-right">
                        <div class="flex items-center">
                            <div class="flex-1 pr-12 text-right">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center group-hover:bg-primary-100 transition-colors">
                                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">Transparent Distribution</h3>
                                    <p class="text-gray-600 mb-6">Track every step of your wool's journey with real-time updates and blockchain technology.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center justify-end">
                                            <span>Real-time tracking</span>
                                            <svg class="w-5 h-5 ml-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                        <li class="flex items-center justify-end">
                                            <span>Blockchain verified</span>
                                            <svg class="w-5 h-5 ml-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                        <li class="flex items-center justify-end">
                                            <span>Global reach</span>
                                            <svg class="w-5 h-5 ml-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center w-12 h-12 rounded-full border-4 border-primary-200 bg-white shadow-xl">
                                <span class="text-lg font-bold text-primary-600">3</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Impact Stats Section -->
    <section class="relative py-24 overflow-hidden bg-gradient-to-br from-primary-600 to-primary-800">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <defs>
                        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="currentColor" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)"/>
                </svg>
            </div>
        </div>

        <!-- Floating Particles -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="particle absolute w-4 h-4 bg-white rounded-full opacity-20" style="top: 20%; left: 10%; animation: float 8s infinite;"></div>
            <div class="particle absolute w-6 h-6 bg-white rounded-full opacity-20" style="top: 60%; left: 80%; animation: float 12s infinite;"></div>
            <div class="particle absolute w-3 h-3 bg-white rounded-full opacity-20" style="top: 80%; left: 30%; animation: float 10s infinite;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6" data-aos="fade-up">Our Impact</h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="100">
                    Transforming the wool industry with transparency and sustainability
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Sustainable Farms -->
                <div class="relative group" data-aos="zoom-in" data-aos-delay="100">
                    <div class="absolute inset-0 bg-white rounded-2xl opacity-5 group-hover:opacity-10 transition-opacity duration-300"></div>
                    <div class="relative p-8 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-xl bg-white/10 group-hover:bg-white/20 transition-colors">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                            </svg>
                        </div>
                        <div class="stat-number text-5xl font-bold text-white mb-4">
                            <span class="counter" data-target="500">0</span>+
                        </div>
                        <div class="stat-label text-lg text-white/80">Sustainable Farms</div>
                        <div class="mt-4 h-1 w-24 mx-auto bg-white/20 rounded">
                            <div class="h-1 w-0 bg-white rounded transition-all duration-1000 group-hover:w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Wool Tracked -->
                <div class="relative group" data-aos="zoom-in" data-aos-delay="200">
                    <div class="absolute inset-0 bg-white rounded-2xl opacity-5 group-hover:opacity-10 transition-opacity duration-300"></div>
                    <div class="relative p-8 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-xl bg-white/10 group-hover:bg-white/20 transition-colors">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="stat-number text-5xl font-bold text-white mb-4">
                            <span class="counter" data-target="25">0</span>k
                        </div>
                        <div class="stat-label text-lg text-white/80">Tons of Wool Tracked</div>
                        <div class="mt-4 h-1 w-24 mx-auto bg-white/20 rounded">
                            <div class="h-1 w-0 bg-white rounded transition-all duration-1000 group-hover:w-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Supply Chain Transparency -->
                <div class="relative group" data-aos="zoom-in" data-aos-delay="300">
                    <div class="absolute inset-0 bg-white rounded-2xl opacity-5 group-hover:opacity-10 transition-opacity duration-300"></div>
                    <div class="relative p-8 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-xl bg-white/10 group-hover:bg-white/20 transition-colors">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="stat-number text-5xl font-bold text-white mb-4">
                            <span class="counter" data-target="100">0</span>%
                        </div>
                        <div class="stat-label text-lg text-white/80">Supply Chain Transparency</div>
                        <div class="mt-4 h-1 w-24 mx-auto bg-white/20 rounded">
                            <div class="h-1 w-0 bg-white rounded transition-all duration-1000 group-hover:w-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 relative overflow-hidden bg-gradient-to-b from-white to-primary-50">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-[0.03]">
            <div class="absolute inset-0" style="background-image: radial-gradient(var(--color-primary) 1px, transparent 1px); background-size: 32px 32px;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-6" data-aos="fade-up">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">
                        Why Choose Woolify
                    </span>
                </h2>
                <p class="text-gray-600 text-lg" data-aos="fade-up" data-aos-delay="100">
                    Experience the future of wool supply chain management with our innovative features
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Transparency Card -->
                <div class="group" data-aos="fade-up" data-aos-delay="100">
                    <div class="relative h-full overflow-hidden rounded-2xl bg-white p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-primary-400 to-primary-600 transform origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100"></div>
                        
                        <div class="relative">
                            <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50 group-hover:bg-primary-100 transition-colors">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>

                            <h3 class="mb-4 text-xl font-semibold">Complete Transparency</h3>
                            <p class="mb-8 text-gray-600">Track every step of your wool's journey with real-time updates and detailed analytics.</p>
                            
                            <ul class="space-y-3">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Real-time tracking
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Detailed analytics
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Supply chain visibility
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Smart Processing Card -->
                <div class="group" data-aos="fade-up" data-aos-delay="200">
                    <div class="relative h-full overflow-hidden rounded-2xl bg-white p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-primary-400 to-primary-600 transform origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100"></div>
                        
                        <div class="relative">
                            <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50 group-hover:bg-primary-100 transition-colors">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>

                            <h3 class="mb-4 text-xl font-semibold">Smart Processing</h3>
                            <p class="mb-8 text-gray-600">Advanced technology ensures efficient processing while maintaining quality.</p>
                            
                            <ul class="space-y-3">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Automated quality control
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Efficient processing
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Minimal waste
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sustainability Card -->
                <div class="group" data-aos="fade-up" data-aos-delay="300">
                    <div class="relative h-full overflow-hidden rounded-2xl bg-white p-8 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-1">
                        <div class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-primary-400 to-primary-600 transform origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100"></div>
                        
                        <div class="relative">
                            <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50 group-hover:bg-primary-100 transition-colors">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                                </svg>
                            </div>

                            <h3 class="mb-4 text-xl font-semibold">Sustainability Focus</h3>
                            <p class="mb-8 text-gray-600">Environmentally conscious practices throughout the supply chain.</p>
                            
                            <ul class="space-y-3">
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Eco-friendly practices
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Carbon footprint tracking
                                </li>
                                <li class="flex items-center text-sm text-gray-600">
                                    <svg class="mr-2 h-5 w-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Sustainable partnerships
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-24 overflow-hidden">
        <!-- Background Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary-600 to-primary-800"></div>
        
        <!-- Animated Wave Background -->
        <div class="absolute inset-0 opacity-10">
            <svg class="absolute bottom-0 w-full" viewBox="0 0 1440 320" preserveAspectRatio="none">
                <path fill="currentColor" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,250.7C960,267,1056,245,1152,224C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        dur="10s"
                        values="0 0; -200 0; 0 0"
                        repeatCount="indefinite"
                    />
                </path>
            </svg>
            <svg class="absolute bottom-0 w-full" viewBox="0 0 1440 320" preserveAspectRatio="none">
                <path fill="currentColor" fill-opacity="0.8" d="M0,320L48,288C96,256,192,192,288,181.3C384,171,480,213,576,224C672,235,768,213,864,186.7C960,160,1056,128,1152,128C1248,128,1344,160,1392,176L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                    <animateTransform
                        attributeName="transform"
                        type="translate"
                        dur="15s"
                        values="200 0; 0 0; 200 0"
                        repeatCount="indefinite"
                    />
                </path>
            </svg>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6" data-aos="fade-up">
                    Ready to Transform Your Wool Supply Chain?
                </h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto mb-12" data-aos="fade-up" data-aos-delay="100">
                    Join hundreds of farms already benefiting from Woolify's innovative tracking system.
                </p>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-sm" data-aos="fade-up" data-aos-delay="200">
                        <div class="text-3xl font-bold text-white mb-2"><?php echo $farmCount; ?>+</div>
                        <div class="text-white/80">Active Farms</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-sm" data-aos="fade-up" data-aos-delay="300">
                        <div class="text-3xl font-bold text-white mb-2"><?php echo number_format($totalWool); ?>kg</div>
                        <div class="text-white/80">Wool Tracked</div>
                    </div>
                    <div class="p-6 rounded-2xl bg-white/10 backdrop-blur-sm" data-aos="fade-up" data-aos-delay="400">
                        <div class="text-3xl font-bold text-white mb-2"><?php echo $batchCount; ?>+</div>
                        <div class="text-white/80">Batches Processed</div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row justify-center gap-6" data-aos="fade-up" data-aos-delay="500">
                    <a href="register.php" class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl bg-white px-8 py-4 font-semibold text-primary-600 transition-all duration-300 hover:bg-opacity-90">
                        <div class="absolute inset-0 w-full h-full transition-all duration-300 group-hover:bg-opacity-90"></div>
                        <span class="relative">Get Started Today</span>
                        <svg class="relative ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                    
                    <a href="#features" class="group relative inline-flex items-center justify-center overflow-hidden rounded-xl border-2 border-white/30 bg-transparent px-8 py-4 font-semibold text-white transition-all duration-300 hover:bg-white/10">
                        <span class="relative">Learn More</span>
                        <svg class="relative ml-2 w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative bg-gray-900 text-white overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-[0.03]">
            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 24px 24px;"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <!-- Main Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 py-16">
                <!-- Company Info -->
                <div class="space-y-6">
                    <div class="flex items-center space-x-3">
                        <img src="public/assets/images/logo.png" alt="Woolify" class="h-8 w-auto rounded-3xl">
                        <span class="text-2xl font-bold">Woolify</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Revolutionizing wool supply chain management with transparency and sustainability at its core.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">LinkedIn</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">Twitter</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <span class="sr-only">GitHub</span>
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-6">Quick Links</h3>
                    <ul class="space-y-4">
                        <li>
                            <a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a>
                        </li>
                        <li>
                            <a href="#about" class="text-gray-400 hover:text-white transition-colors">About Us</a>
                        </li>
                        <li>
                            <a href="#contact" class="text-gray-400 hover:text-white transition-colors">Contact</a>
                        </li>
                        <li>
                            <a href="login.php" class="text-gray-400 hover:text-white transition-colors">Login</a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-semibold mb-6">Contact Us</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center text-gray-400">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            support@woolify.com
                        </li>
                        <li class="flex items-center text-gray-400">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            +1 (555) 123-4567
                        </li>
                        <li class="flex items-center text-gray-400">
                            <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            123 Wool Street, Farm City
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h3 class="text-lg font-semibold mb-6">Stay Updated</h3>
                    <p class="text-gray-400 text-sm mb-4">Subscribe to our newsletter for the latest updates and insights.</p>
                    <form class="space-y-4">
                        <div class="relative">
                            <input type="email" placeholder="Enter your email" class="w-full px-4 py-3 bg-gray-800 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-primary-500 hover:text-primary-400 transition-colors">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="border-t border-gray-800 py-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm">&copy; 2024 Woolify. All rights reserved.</p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Scroll to Top Button
        const scrollToTopBtn = document.getElementById('scrollToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                scrollToTopBtn.classList.remove('opacity-0', 'translate-y-10');
            } else {
                scrollToTopBtn.classList.add('opacity-0', 'translate-y-10');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Counter animation
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            let count = 0;
            const inc = target / speed;
            
            const updateCount = () => {
                if (count < target) {
                    count += inc;
                    counter.innerText = Math.ceil(count);
                    requestAnimationFrame(updateCount);
                } else {
                    counter.innerText = target;
                }
            };
            
            updateCount();
        };

        // Start counter animation when section is in view
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.counter');
                    counters.forEach(counter => animateCounter(counter));
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe the impact stats section
        const impactSection = document.querySelector('.impact-stats');
        if (impactSection) {
            observer.observe(impactSection);
        }

        // Scroll Progress Bar
        window.addEventListener('scroll', () => {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            scrollProgress.style.transform = `scaleX(${scrolled / 100})`;
        });

        // Enhanced Navbar Animation
        const navbar = document.getElementById('navbar');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            
            if (currentScroll <= 0) {
                navbar.classList.remove('scroll-up');
                return;
            }
            
            if (currentScroll > lastScroll && !navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-up');
                navbar.classList.add('scroll-down');
            } else if (currentScroll < lastScroll && navbar.classList.contains('scroll-down')) {
                navbar.classList.remove('scroll-down');
                navbar.classList.add('scroll-up');
            }
            
            lastScroll = currentScroll;
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
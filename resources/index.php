<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woolify - Resources Hub</title>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet">
    
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
                        }
                    },
                    fontFamily: {
                        sans: ['DM Sans', 'sans-serif'],
                        display: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* background: linear-gradient(to bottom, rgba(44, 95, 45, 0.85), rgba(151, 188, 98, 0.75)); */
            z-index: 1;
        }

        .resource-card {
            transition: all 0.3s ease;
            transform: translateY(0);
        }

        .resource-card:hover {
            transform: translateY(-10px);
        }

        .gradient-text {
            background: linear-gradient(135deg, #5F975F 0%, #4C794C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .search-bar {
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-white">
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section with Video -->
    <section class="relative min-h-screen flex items-center justify-center">
        <video class="hero-video" autoplay loop muted playsinline>
            <source src="../public/assets/videos/hero-bg.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 text-center">
            <h1 class="text-5xl md:text-7xl font-bold mb-8 text-white leading-tight">
                Resources & Documentation
                <span class="block mt-4 text-3xl md:text-4xl font-medium">
                    Everything you need to succeed with Woolify
                </span>
            </h1>
            
            <p class="text-xl md:text-2xl mb-12 text-white/90 max-w-3xl mx-auto">
                Explore our comprehensive guides, API documentation, and case studies to make the most of Woolify's platform.
            </p>

            <!-- Enhanced Search Bar -->
            <div class="max-w-2xl mx-auto">
                <div class="search-bar flex items-center p-2 rounded-full shadow-xl border border-white/20">
                    <input type="text" 
                           placeholder="Search resources..." 
                           class="w-full px-6 py-4 bg-transparent rounded-full text-white placeholder-white/70 focus:outline-none"
                    >
                    <button class="ml-2 p-4 bg-white text-primary-600 rounded-full hover:bg-primary-50 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
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
                        Explore the Resources
                    </span>
                </h2>
                <p class="text-gray-600 text-lg" data-aos="fade-up" data-aos-delay="100">
                    Discover how we support learning, transparency, and technical integration through a comprehensive set of resources.
                </p>
            </div>

            <!-- Timeline Steps -->
            <div class="relative">
                <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-0.5 bg-gradient-to-b from-primary-200 via-primary-400 to-primary-600"></div>
                <div class="space-y-24">

                    <!-- Step 1 -->
                    <div class="relative" data-aos="fade-right">
                        <div class="flex items-center">
                            <div class="flex-1 pr-12 text-right">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center">
                                        <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">Educational Documentation</h3>
                                    <p class="text-gray-600 mb-6">Detailed guides and explanations to help you understand the full lifecycle of wool—from ethical sourcing to sustainable design.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center justify-end">Lifecycle stages explained</li>
                                        <li class="flex items-center justify-end">Sustainable practices breakdown</li>
                                        <li class="flex items-center justify-end">Visual & downloadable resources</li>
                                    </ul>
                                    <a href="documentation.php" class="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700 transition-colors">
                                        <span class="font-medium">Explore Documentation</span>
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center w-12 h-12 rounded-full border-4 border-primary-200 bg-white shadow-xl">
                                <span class="text-lg font-bold text-primary-600">1</span>
                            </div>
                        </div>
                </div>
                
                    <!-- Step 2 -->
                    <div class="relative" data-aos="fade-left">
                        <div class="flex items-center">
                            <div class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center w-12 h-12 rounded-full border-4 border-primary-200 bg-white shadow-xl">
                                <span class="text-lg font-bold text-primary-600">2</span>
                            </div>
                            <div class="flex-1 pl-12">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center">
                                        <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">Case Studies</h3>
                                    <p class="text-gray-600 mb-6">Real-world insights from our partner farms and textile makers showing measurable impact and innovation.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center">Sustainable outcomes</li>
                                        <li class="flex items-center">Community impact</li>
                                        <li class="flex items-center">Challenges & lessons</li>
                                    </ul>
                                    <a href="case-studies.php" class="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700 transition-colors">
                                        <span class="font-medium">Browse Case Studies</span>
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative" data-aos="fade-right">
                        <div class="flex items-center">
                            <div class="flex-1 pr-12 text-right">
                                <div class="glass-card p-8 inline-block">
                                    <div class="relative w-16 h-16 mb-6 rounded-xl bg-primary-50 flex items-center justify-center">
                                        <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-2xl font-semibold mb-4">API References</h3>
                                    <p class="text-gray-600 mb-6">Integrate our platform with your system using well-documented and developer-friendly APIs.</p>
                                    <ul class="space-y-3 text-sm text-gray-600">
                                        <li class="flex items-center justify-end">Authentication guides</li>
                                        <li class="flex items-center justify-end">Data endpoints</li>
                                        <li class="flex items-center justify-end">Sandbox & testing tools</li>
                                    </ul>
                                    <a href="api-reference.php" class="mt-6 inline-flex items-center text-primary-600 hover:text-primary-700 transition-colors">
                                        <span class="font-medium">View API Reference</span>
                                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </a>
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

    <!-- Support Section -->
    <section class="py-24 bg-primary-600 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(rgba(255,255,255,0.2) 1px, transparent 1px); background-size: 24px 24px;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="text-center">
                <h2 class="text-4xl font-bold text-white mb-6">Need Help?</h2>
                <p class="text-xl text-white/80 max-w-3xl mx-auto mb-12">
                    Our support team is here to help you get the most out of Woolify
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Community Support -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                        <div class="h-16 w-16 bg-white/10 rounded-xl flex items-center justify-center mb-6 mx-auto">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-4">Community Forum</h3>
                        <p class="text-white/80 mb-6">Join our community of users and share experiences.</p>
                        <a href="#" class="text-white font-medium hover:text-white/90 transition-colors">Visit Forum →</a>
                    </div>

                    <!-- Technical Support -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                        <div class="h-16 w-16 bg-white/10 rounded-xl flex items-center justify-center mb-6 mx-auto">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-4">Technical Support</h3>
                        <p class="text-white/80 mb-6">Get help from our technical experts.</p>
                        <a href="#" class="text-white font-medium hover:text-white/90 transition-colors">Contact Support →</a>
    </div>

                    <!-- FAQ -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                        <div class="h-16 w-16 bg-white/10 rounded-xl flex items-center justify-center mb-6 mx-auto">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-4">FAQ</h3>
                        <p class="text-white/80 mb-6">Find answers to common questions.</p>
                        <a href="#" class="text-white font-medium hover:text-white/90 transition-colors">Browse FAQ →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
        
    <?php include 'includes/footer.php'; ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        // Animate resource cards on scroll
        gsap.utils.toArray('.resource-card').forEach(card => {
            gsap.from(card, {
                y: 60,
                opacity: 0,
                duration: 1,
                scrollTrigger: {
                    trigger: card,
                    start: 'top bottom-=100',
                    end: 'top center',
                    scrub: 1
                }
            });
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
<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woolify - Case Studies</title>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
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
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --color-primary: #5F975F;
            --color-primary-dark: #4C794C;
        }
        .nav-link {
            @apply relative font-medium text-gray-700 hover:text-primary-600 transition-colors;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar (same as index.php) -->
    <?php include 'includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">
                    Case Studies
                </span>
            </h1>
            <p class="text-xl text-gray-600">
                Real-world success stories from our partners using Woolify
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Case Study 1 -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg">
                <img src="../assets/images/case-study-1.jpg" alt="Mountain Valley Farms" class="w-full h-48 object-cover">
                <div class="p-8">
                    <h3 class="text-xl font-semibold mb-4">Mountain Valley Farms</h3>
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Sustainability</span>
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Efficiency</span>
                    </div>
                    <p class="text-gray-600 mb-6">How a traditional farm transformed their wool production with digital tracking.</p>
                    <a href="https://www.tripadvisor.in/Attraction_Review-g34929-d2559949-Reviews-Mountain_Valley_Farm-Ellijay_Gilmer_County_Georgia.html" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                        Read Case Study
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Case Study 2 -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg">
                <img src="../assets/images/case-study-2.jpg" alt="EcoWool Cooperative" class="w-full h-48 object-cover">
                <div class="p-8">
                    <h3 class="text-xl font-semibold mb-4">EcoWool Cooperative</h3>
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Traceability</span>
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Collaboration</span>
                    </div>
                    <p class="text-gray-600 mb-6">A cooperative's journey to full supply chain transparency.</p>
                    <a href="https://ecowool.com.my/product/" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                        Read Case Study
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Case Study 3 -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg">
                <img src="../assets/images/case-study-3.jpg" alt="Pure Wool Industries" class="w-full h-48 object-cover">
                <div class="p-8">
                    <h3 class="text-xl font-semibold mb-4">Pure Wool Industries</h3>
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Innovation</span>
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Scale</span>
                    </div>
                    <p class="text-gray-600 mb-6">Scaling wool processing while maintaining quality standards.</p>
                    <a href="http://jayashree-grasim.com/wool-production-process/" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                        Read Case Study
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Case Study 4 -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-lg">
                <img src="../assets/images/case-study-4.jpg" alt="Global Wool Trading" class="w-full h-48 object-cover">
                <div class="p-8">
                    <h3 class="text-xl font-semibold mb-4">Global Wool Trading</h3>
                    <div class="flex items-center space-x-4 mb-4">
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Global</span>
                        <span class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">Trade</span>
                    </div>
                    <p class="text-gray-600 mb-6">Streamlining international wool trade with blockchain technology.</p>
                    <a href="https://iwto.org/" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                        Read Case Study
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html> 
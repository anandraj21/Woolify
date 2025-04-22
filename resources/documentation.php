<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woolify - Documentation</title>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
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
        .process-step:hover .step-number {
            transform: scale(1.1);
            background-color: var(--color-primary);
            color: white;
        }
        .process-step:hover .step-content {
            transform: translateY(-5px);
        }
        .step-number, .step-content {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar (same as index.php) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-b from-primary-50 to-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6" data-aos="fade-up">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">
                        From Farm to Fabric
                    </span>
                </h1>
                <p class="text-xl text-gray-600 mb-8" data-aos="fade-up" data-aos-delay="100">
                    Discover the complete journey of wool processing and learn how we transform raw wool into beautiful fabrics
                </p>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Video Section -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-8 text-center" data-aos="fade-up">Wool Processing Journey</h2>
            <div class="aspect-w-16 aspect-h-9 rounded-2xl overflow-hidden shadow-xl" data-aos="zoom-in">
                <iframe class="w-full h-[500px]" src="https://www.youtube.com/embed/kH_b3Heo48I" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>

        <!-- Process Steps -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-12 text-center" data-aos="fade-up">The Complete Process</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Step 1: Shearing -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">1</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Shearing</h3>
                            <p class="text-gray-600">Skilled shearers carefully remove wool from sheep using specialized equipment. This process is typically done once per year in spring.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Sorting and Grading -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">2</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Sorting & Grading</h3>
                            <p class="text-gray-600">Wool is carefully sorted based on quality, length, strength, and fineness. Each grade is suitable for different end products.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Scouring -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">3</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Scouring</h3>
                            <p class="text-gray-600">Raw wool is cleaned to remove dirt, grease, and other impurities through a series of hot water and detergent baths.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Carding -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">4</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Carding</h3>
                            <p class="text-gray-600">Cleaned wool fibers are separated and aligned using rotating cylinders covered in small teeth, creating a continuous web.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Spinning -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">5</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Spinning</h3>
                            <p class="text-gray-600">The carded wool is spun into yarn. Different spinning techniques create yarns suitable for various end products.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Weaving/Knitting -->
                <div class="process-step bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-start mb-6">
                        <div class="step-number h-12 w-12 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 font-bold text-xl mr-4">6</div>
                        <div class="step-content">
                            <h3 class="text-xl font-semibold mb-4">Weaving/Knitting</h3>
                            <p class="text-gray-600">Yarn is transformed into fabric through weaving or knitting processes, creating different textures and patterns.</p>
                            <a href="#" class="mt-4 inline-block text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Standards Section -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold mb-8 text-center" data-aos="fade-up">Quality Standards</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-right">
                    <h3 class="text-xl font-semibold mb-4">Wool Grading System</h3>
                    <ul class="space-y-4 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-primary-500 mr-3"></i>
                            <span>Grade A: Ultra-fine Merino (≤ 18.5 microns)</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-primary-500 mr-3"></i>
                            <span>Grade B: Fine Wool (18.6-20.5 microns)</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-primary-500 mr-3"></i>
                            <span>Grade C: Medium Wool (20.6-22.5 microns)</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-left">
                    <h3 class="text-xl font-semibold mb-4">Testing Parameters</h3>
                    <ul class="space-y-4 text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-microscope text-primary-500 mr-3"></i>
                            <span>Fiber Diameter Testing</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-ruler text-primary-500 mr-3"></i>
                            <span>Staple Length Measurement</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-tint text-primary-500 mr-3"></i>
                            <span>Clean Wool Yield Assessment</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Additional Resources -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                    <i class="fas fa-book text-xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Detailed Guides</h3>
                <p class="text-gray-600 mb-4">Access comprehensive guides about wool processing and quality control.</p>
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">View Guides →</a>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                    <i class="fas fa-video text-xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Video Library</h3>
                <p class="text-gray-600 mb-4">Watch detailed videos about each step of the wool processing journey.</p>
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">Browse Videos →</a>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                    <i class="fas fa-certificate text-xl text-primary-600"></i>
                </div>
                <h3 class="text-xl font-semibold mb-4">Certification</h3>
                <p class="text-gray-600 mb-4">Learn about wool quality certifications and standards.</p>
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">Learn More →</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white relative overflow-hidden">
        <!-- Wave Pattern -->
        <div class="absolute top-0 left-0 w-full overflow-hidden">
            <svg class="relative block w-full h-[60px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-gray-50"></path>
            </svg>
        </div>

        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.05)_1px,transparent_0)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_at_center,black_70%,transparent_100%)]"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-12 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <!-- Company Info -->
                <div class="col-span-1">
                    <a href="../index.php" class="inline-block mb-6">
                        <img src="../public/assets/images/logo.png" alt="Woolify" class="h-12 w-auto rounded-lg">
                    </a>
                    <p class="text-gray-400 mb-6">Revolutionizing the wool industry through transparency, quality, and sustainable practices.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fab fa-github text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-6">Quick Links</h3>
                    <ul class="space-y-4">
                        <li><a href="../features.php" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
                        <li><a href="../about.php" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="../contact.php" class="text-gray-400 hover:text-white transition-colors">Contact</a></li>
                        <li><a href="../login.php" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-6">Contact</h3>
                    <ul class="space-y-4">
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-primary-500 mr-3"></i>
                            <a href="mailto:info@woolify.com" class="text-gray-400 hover:text-white transition-colors">info@woolify.com</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone text-primary-500 mr-3"></i>
                            <a href="tel:+1234567890" class="text-gray-400 hover:text-white transition-colors">+1 (234) 567-890</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">123 Wool Street<br>Textile District<br>Melbourne, Australia</span>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="col-span-1">
                    <h3 class="text-lg font-semibold mb-6">Newsletter</h3>
                    <p class="text-gray-400 mb-4">Subscribe to our newsletter for updates and insights.</p>
                    <form class="space-y-4">
                        <div class="relative">
                            <input type="email" placeholder="Enter your email" class="w-full px-4 py-3 bg-gray-800 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-primary-500 hover:text-primary-400">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 mt-8 border-t border-gray-800 text-center md:flex md:justify-between md:text-left">
                <p class="text-gray-400">&copy; 2024 Woolify. All rights reserved.</p>
                <div class="mt-4 md:mt-0 space-x-4">
                    <a href="../privacy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
                    <a href="../terms.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
                    <a href="../cookies.php" class="text-gray-400 hover:text-white transition-colors">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>
</body>
</html> 
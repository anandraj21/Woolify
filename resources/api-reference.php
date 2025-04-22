<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woolify - API Reference</title>
    
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dependencies -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/aos@next/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    
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
        .api-method {
            @apply px-3 py-1 rounded-full text-sm font-medium;
        }
        .api-method.get {
            @apply bg-green-100 text-green-700;
        }
        .api-method.post {
            @apply bg-blue-100 text-blue-700;
        }
        .api-method.put {
            @apply bg-yellow-100 text-yellow-700;
        }
        .api-method.delete {
            @apply bg-red-100 text-red-700;
        }
        .endpoint-card {
            @apply transition-all duration-300;
        }
        .endpoint-card:hover {
            @apply transform -translate-y-1 shadow-xl;
        }
        .code-block {
            @apply bg-gray-800 text-white p-4 rounded-lg text-sm font-mono mt-4;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="relative overflow-hidden bg-gradient-to-b from-primary-50 to-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6" data-aos="fade-up">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400">
                        API Reference
                    </span>
                </h1>
                <p class="text-xl text-gray-600 mb-8" data-aos="fade-up" data-aos-delay="100">
                    Comprehensive documentation for integrating with the Woolify platform
                </p>
                <div class="flex justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
                    <a href="#getting-started" class="bg-primary-600 text-white px-6 py-3 rounded-lg hover:bg-primary-700 transition-colors">
                        Get Started
                    </a>
                    <a href="#endpoints" class="bg-white text-primary-600 px-6 py-3 rounded-lg hover:bg-gray-50 transition-colors border border-primary-600">
                        View Endpoints
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Getting Started Section -->
        <section id="getting-started" class="mb-16">
            <h2 class="text-3xl font-bold mb-8" data-aos="fade-up">Getting Started</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-right">
                    <h3 class="text-xl font-semibold mb-4">Authentication</h3>
                    <p class="text-gray-600 mb-4">To use the Woolify API, you'll need to authenticate your requests using an API key. Include your API key in the header of each request:</p>
                    <div class="code-block">
                        <pre><code class="language-bash">Authorization: Bearer YOUR_API_KEY</code></pre>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-left">
                    <h3 class="text-xl font-semibold mb-4">Base URL</h3>
                    <p class="text-gray-600 mb-4">All API requests should be made to:</p>
                    <div class="code-block">
                        <pre><code class="language-bash">https://api.woolify.com/v1</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <!-- Endpoints Section -->
        <section id="endpoints" class="mb-16">
            <h2 class="text-3xl font-bold mb-8" data-aos="fade-up">API Endpoints</h2>

            <!-- Authentication -->
            <div class="mb-12">
                <h3 class="text-2xl font-semibold mb-6" data-aos="fade-up">Authentication</h3>
                <div class="space-y-6">
                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method post">POST</span>
                                <code class="text-sm">/auth/login</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">Authenticate user and retrieve access token</p>
                        <div class="code-block">
                            <pre><code class="language-json">{
  "email": "user@example.com",
  "password": "your_password"
}</code></pre>
                        </div>
                    </div>

                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method post">POST</span>
                                <code class="text-sm">/auth/register</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">Register a new user account</p>
                        <div class="code-block">
                            <pre><code class="language-json">{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password",
  "role": "FARMER"
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wool Tracking -->
            <div class="mb-12">
                <h3 class="text-2xl font-semibold mb-6" data-aos="fade-up">Wool Tracking</h3>
                <div class="space-y-6">
                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method get">GET</span>
                                <code class="text-sm">/batches</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">List all wool batches with optional filtering</p>
                        <div class="code-block">
                            <pre><code class="language-bash">GET /batches?grade=A&min_micron=18&max_micron=20</code></pre>
                        </div>
                    </div>

                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method post">POST</span>
                                <code class="text-sm">/batches</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">Create a new wool batch</p>
                        <div class="code-block">
                            <pre><code class="language-json">{
  "grade": "A",
  "micron": 18.5,
  "quantity": 100,
  "price_per_kg": 15.50,
  "farm_id": "123"
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics -->
            <div class="mb-12">
                <h3 class="text-2xl font-semibold mb-6" data-aos="fade-up">Analytics</h3>
                <div class="space-y-6">
                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method get">GET</span>
                                <code class="text-sm">/analytics/overview</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">Get overview statistics</p>
                        <div class="code-block">
                            <pre><code class="language-bash">GET /analytics/overview?period=last_30_days</code></pre>
                        </div>
                    </div>

                    <div class="endpoint-card bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-4">
                                <span class="api-method get">GET</span>
                                <code class="text-sm">/analytics/reports</code>
                            </div>
                            <button class="text-primary-600 hover:text-primary-700">Try it</button>
                        </div>
                        <p class="text-gray-600 mb-4">Generate custom reports</p>
                        <div class="code-block">
                            <pre><code class="language-bash">GET /analytics/reports?type=sales&start_date=2024-01-01&end_date=2024-12-31</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SDKs & Libraries -->
        <section class="mb-16">
            <h2 class="text-3xl font-bold mb-8" data-aos="fade-up">SDKs & Libraries</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up">
                    <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                        <i class="fab fa-js text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4">JavaScript</h3>
                    <p class="text-gray-600 mb-4">Official JavaScript SDK for seamless integration with Node.js and browser applications.</p>
                    <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">View Documentation →</a>
                </div>

                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="100">
                    <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                        <i class="fab fa-python text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4">Python</h3>
                    <p class="text-gray-600 mb-4">Python SDK with async support and comprehensive type hints.</p>
                    <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">View Documentation →</a>
                </div>

                <div class="bg-white rounded-2xl p-8 shadow-lg" data-aos="fade-up" data-aos-delay="200">
                    <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-xl bg-primary-50">
                        <i class="fab fa-php text-2xl text-primary-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4">PHP</h3>
                    <p class="text-gray-600 mb-4">PHP SDK with Laravel integration and modern PHP features.</p>
                    <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">View Documentation →</a>
                </div>
            </div>
        </section>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>
</body>
</html> 
<?php
// Determine the current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center space-x-4">
                <a href="../index.php" class="flex items-center space-x-3 group">
                    <img src="../public/assets/images/logo.png" alt="Woolify" class="h-10 w-auto group-hover:scale-105 transition-transform rounded-3xl">
                    <span class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">Woolify</span>
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-8">
                <a href="../index.php#journey" class="nav-link">Journey</a>
                <a href="../index.php#features" class="nav-link">Features</a>
                <a href="../index.php#impact" class="nav-link">Impact</a>
                <div class="relative group">
                    <button class="nav-link flex items-center">
                        Resources
                        <svg class="w-4 h-4 ml-1 transform group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="absolute top-full right-0 w-48 py-2 mt-2 bg-white rounded-xl shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top scale-95 group-hover:scale-100">
                        <a href="documentation.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Documentation</a>
                        <a href="api-reference.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">API Reference</a>
                        <a href="case-studies.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">Case Studies</a>
                    </div>
                </div>
                <a href="../login.php" class="px-6 py-2 text-primary-600 font-medium hover:text-primary-700 transition-colors">Login</a>
                <a href="../register.php" class="btn-primary group">
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

    <!-- Mobile menu -->
    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-4 pt-2 pb-3 space-y-1">
            <a href="../index.php#journey" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">Journey</a>
            <a href="../index.php#features" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">Features</a>
            <a href="../index.php#impact" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">Impact</a>
            <a href="documentation.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">Documentation</a>
            <a href="api-reference.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">API Reference</a>
            <a href="case-studies.php" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-primary-50 rounded-md">Case Studies</a>
            <div class="mt-4 space-y-2">
                <a href="../login.php" class="block px-3 py-2 text-base font-medium text-primary-600 hover:text-primary-700">Login</a>
                <a href="../register.php" class="block px-3 py-2 text-base font-medium bg-primary-600 text-white rounded-md hover:bg-primary-700">
                    Get Started
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.nav-link {
    @apply text-gray-700 hover:text-primary-600 transition-colors font-medium;
}

.btn-primary {
    @apply px-6 py-2 bg-primary-600 text-white font-medium rounded-md hover:bg-primary-700 transition-colors flex items-center;
}
</style>

<script>
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    document.getElementById('mobile-menu').classList.toggle('hidden');
});
</script> 
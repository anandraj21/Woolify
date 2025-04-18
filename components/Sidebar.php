<?php
function renderSidebar($active_page = 'dashboard') {
    $user_role = $_SESSION['user_role'] ?? '';
?>
    <div id="sidebar" class="fixed left-0 top-0 w-64 h-screen bg-white shadow-lg transform transition-transform duration-300 ease-in-out z-40">
        <div class="flex flex-col h-full">
            <!-- Logo Section -->
            <div class="flex items-center justify-center h-20 border-b border-gray-200">
                <a href="dashboard.php" class="flex items-center space-x-3 group">
                    <img src="assets/images/logo.svg" alt="Woolify" class="h-10 w-auto group-hover:scale-105 transition-transform">
                    <span class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">Woolify</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'dashboard' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="batches.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'batches' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <span>My Batches</span>
                </a>

                <a href="analytics.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'analytics' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Analytics</span>
                </a>

                <?php if ($user_role === 'admin'): ?>
                <a href="admin.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'admin' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <span>Admin Panel</span>
                </a>
                <?php endif; ?>

                <a href="support.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'support' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>Support</span>
                </a>

                <a href="settings.php" class="flex items-center px-4 py-3 text-gray-700 hover:bg-primary-50 rounded-lg transition-colors <?php echo $active_page === 'settings' ? 'bg-primary-50 text-primary-600' : ''; ?>">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Settings</span>
                </a>
            </nav>

            <!-- User Profile Section -->
            <div class="p-4 border-t border-gray-200">
                <a href="profile.php" class="flex items-center space-x-3 hover:bg-gray-50 rounded-lg p-2 transition-colors">
                    <img src="<?php echo $_SESSION['user_profile_image'] ?? 'assets/images/default-avatar.png'; ?>" alt="Profile" class="w-10 h-10 rounded-full">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            <?php echo $_SESSION['user_name'] ?? 'User'; ?>
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            <?php echo ucfirst($_SESSION['user_role'] ?? 'User'); ?>
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Sidebar Toggle Button (Mobile) -->
    <button id="sidebarToggle" class="fixed bottom-4 right-4 lg:hidden bg-primary-500 text-white p-3 rounded-full shadow-lg hover:bg-primary-600 transition-colors z-50">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>

    <script>
        // Sidebar Toggle Functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });

        // Close sidebar when clicking outside (mobile)
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.add('-translate-x-full');
            }
        });

        // GSAP Animations
        gsap.from("#sidebar", {
            duration: 0.5,
            x: -100,
            opacity: 0,
            ease: "power2.out"
        });

        // Animate nav items sequentially
        gsap.from("#sidebar nav a", {
            duration: 0.3,
            x: -20,
            opacity: 0,
            stagger: 0.1,
            ease: "power2.out"
        });
    </script>
<?php
}
?> 
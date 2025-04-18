<?php
require_once 'config/database.php';

function getUnreadNotificationsCount($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetch()['count'];
}

function renderHeader($title = '') {
    $user_id = $_SESSION['user_id'] ?? 0;
    $unread_count = getUnreadNotificationsCount($user_id);
?>
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-gray-200">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
            <!-- Left side - Title -->
            <div class="flex items-center">
                <h1 class="text-2xl font-semibold text-gray-900"><?php echo $title; ?></h1>
            </div>

            <!-- Right side - Actions -->
            <div class="flex items-center space-x-4">
                <!-- Search -->
                <div class="hidden md:block">
                    <div class="relative">
                        <input type="text" id="globalSearch" placeholder="Search..." class="w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="relative">
                    <button id="notificationsButton" class="relative p-2 text-gray-600 hover:text-primary-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <?php if ($unread_count > 0): ?>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notifications Panel -->
                    <div id="notificationsPanel" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                        </div>
                        <div class="max-h-96 overflow-y-auto" id="notificationsList">
                            <!-- Notifications will be loaded here via AJAX -->
                            <div class="p-4 text-center text-gray-500">
                                Loading notifications...
                            </div>
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="notifications.php" class="block text-center text-sm text-primary-600 hover:text-primary-700">View all notifications</a>
                        </div>
                    </div>
                </div>

                <!-- Theme Toggle -->
                <button id="themeToggle" class="p-2 text-gray-600 hover:text-primary-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <script>
        // Notifications Panel Toggle
        const notificationsButton = document.getElementById('notificationsButton');
        const notificationsPanel = document.getElementById('notificationsPanel');
        let isNotificationsPanelOpen = false;

        notificationsButton.addEventListener('click', function() {
            isNotificationsPanelOpen = !isNotificationsPanelOpen;
            if (isNotificationsPanelOpen) {
                notificationsPanel.classList.remove('hidden');
                loadNotifications();
                // Animate panel opening
                gsap.fromTo(notificationsPanel, 
                    { opacity: 0, y: -10 },
                    { opacity: 1, y: 0, duration: 0.3, ease: "power2.out" }
                );
            } else {
                // Animate panel closing
                gsap.to(notificationsPanel, {
                    opacity: 0,
                    y: -10,
                    duration: 0.3,
                    ease: "power2.in",
                    onComplete: () => notificationsPanel.classList.add('hidden')
                });
            }
        });

        // Close notifications panel when clicking outside
        document.addEventListener('click', function(event) {
            if (isNotificationsPanelOpen && 
                !notificationsButton.contains(event.target) && 
                !notificationsPanel.contains(event.target)) {
                isNotificationsPanelOpen = false;
                gsap.to(notificationsPanel, {
                    opacity: 0,
                    y: -10,
                    duration: 0.3,
                    ease: "power2.in",
                    onComplete: () => notificationsPanel.classList.add('hidden')
                });
            }
        });

        // Load notifications via AJAX
        function loadNotifications() {
            fetch('api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationsList = document.getElementById('notificationsList');
                    if (data.length === 0) {
                        notificationsList.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                No new notifications
                            </div>
                        `;
                        return;
                    }

                    notificationsList.innerHTML = data.map(notification => `
                        <div class="p-4 border-b border-gray-200 hover:bg-gray-50 transition-colors ${notification.is_read ? '' : 'bg-primary-50'}" 
                             data-notification-id="${notification.id}">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    ${getNotificationIcon(notification.type)}
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        ${notification.title}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500">
                                        ${notification.message}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        ${timeAgo(notification.created_at)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    `).join('');

                    // Animate notifications
                    gsap.from("#notificationsList > div", {
                        opacity: 0,
                        y: 20,
                        duration: 0.3,
                        stagger: 0.1,
                        ease: "power2.out"
                    });
                })
                .catch(error => {
                    console.error('Error loading notifications:', error);
                    document.getElementById('notificationsList').innerHTML = `
                        <div class="p-4 text-center text-red-500">
                            Error loading notifications
                        </div>
                    `;
                });
        }

        // Helper function to get notification icon based on type
        function getNotificationIcon(type) {
            const icons = {
                success: `<svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                         </svg>`,
                warning: `<svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                         </svg>`,
                error: `<svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                       </svg>`,
                info: `<svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>`
            };
            return icons[type] || icons.info;
        }

        // Helper function to format time ago
        function timeAgo(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);

            if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
            if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            return 'Just now';
        }

        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        let isDarkMode = localStorage.getItem('darkMode') === 'true';

        function updateTheme() {
            if (isDarkMode) {
                document.documentElement.classList.add('dark');
                themeToggle.innerHTML = `
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                `;
            } else {
                document.documentElement.classList.remove('dark');
                themeToggle.innerHTML = `
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                `;
            }
        }

        themeToggle.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            localStorage.setItem('darkMode', isDarkMode);
            updateTheme();

            // Animate theme toggle
            gsap.from(themeToggle, {
                rotate: 360,
                duration: 0.5,
                ease: "power2.out"
            });
        });

        // Initialize theme
        updateTheme();

        // Global Search Functionality
        const globalSearch = document.getElementById('globalSearch');
        let searchTimeout;

        globalSearch.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = e.target.value.trim();
                if (searchTerm.length >= 2) {
                    performSearch(searchTerm);
                }
            }, 300);
        });

        function performSearch(term) {
            fetch(`api/search.php?q=${encodeURIComponent(term)}`)
                .then(response => response.json())
                .then(data => {
                    // Handle search results
                    console.log('Search results:', data);
                })
                .catch(error => {
                    console.error('Error performing search:', error);
                });
        }
    </script>
<?php
}
?> 
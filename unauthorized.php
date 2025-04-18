<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - Woolify</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center bg-white p-10 rounded-lg shadow-md">
            <div>
                <svg class="mx-auto h-12 w-auto text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>                      
                <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                    Unauthorized Access
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    You do not have permission to access the requested page.
                </p>
            </div>
            <div class="mt-5">
                <a href="dashboard.php" 
                   class="inline-flex items-center justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Go to Dashboard
                </a>
                 <a href="logout.php" 
                   class="ml-3 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html> 
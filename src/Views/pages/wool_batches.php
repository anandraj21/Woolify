<?php
session_start();
require_once 'config/database.php';
require_once 'batch_operations.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$batches = getRecentBatches($userId, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wool Batches - Woolify</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed left-0 top-0 h-screen w-64 bg-indigo-600 text-white p-6">
        <div class="flex items-center mb-8">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="ml-2 text-2xl font-bold">Woolify</span>
        </div>
        <nav>
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg mb-2 hover:bg-white hover:bg-opacity-10 transition-colors">
                Dashboard
            </a>
            <a href="wool_batches.php" class="block py-3 px-4 rounded-lg mb-2 bg-white bg-opacity-10">
                Wool Batches
            </a>
            <a href="processing.php" class="block py-3 px-4 rounded-lg mb-2 hover:bg-white hover:bg-opacity-10 transition-colors">
                Processing
            </a>
            <a href="analytics.php" class="block py-3 px-4 rounded-lg mb-2 hover:bg-white hover:bg-opacity-10 transition-colors">
                Analytics
            </a>
            <a href="settings.php" class="block py-3 px-4 rounded-lg mb-2 hover:bg-white hover:bg-opacity-10 transition-colors">
                Settings
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Wool Batches</h1>
            <button onclick="openAddBatchModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add New Batch
            </button>
        </div>

        <!-- Batch List -->
        <div class="glass-card rounded-2xl p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="pb-3">Batch Number</th>
                            <th class="pb-3">Farm</th>
                            <th class="pb-3">Weight (kg)</th>
                            <th class="pb-3">Quality</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Created At</th>
                            <th class="pb-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                        <tr class="border-b">
                            <td class="py-3"><?php echo htmlspecialchars($batch['batch_number']); ?></td>
                            <td class="py-3"><?php echo htmlspecialchars($batch['farm_name']); ?></td>
                            <td class="py-3"><?php echo htmlspecialchars($batch['weight_kg']); ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded-full text-xs <?php
                                    echo match($batch['quality_grade']) {
                                        'A' => 'bg-green-100 text-green-800',
                                        'B' => 'bg-blue-100 text-blue-800',
                                        'C' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-red-100 text-red-800'
                                    };
                                ?>">
                                    Grade <?php echo htmlspecialchars($batch['quality_grade']); ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded-full text-xs <?php
                                    echo match($batch['status']) {
                                        'processed' => 'bg-green-100 text-green-800',
                                        'in_processing' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $batch['status'])); ?>
                                </span>
                            </td>
                            <td class="py-3"><?php echo date('Y-m-d H:i', strtotime($batch['created_at'])); ?></td>
                            <td class="py-3">
                                <button onclick="viewBatchDetails('<?php echo $batch['id']; ?>')" class="text-indigo-600 hover:text-indigo-800 mr-3">View</button>
                                <button onclick="editBatch('<?php echo $batch['id']; ?>')" class="text-green-600 hover:text-green-800 mr-3">Edit</button>
                                <button onclick="deleteBatch('<?php echo $batch['id']; ?>')" class="text-red-600 hover:text-red-800">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Batch Modal -->
    <div id="addBatchModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl p-8 w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6">Add New Batch</h2>
            <form id="addBatchForm" onsubmit="submitBatch(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Farm</label>
                        <select name="farm_id" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                            <?php
                            $stmt = $conn->prepare("SELECT id, farm_name FROM farms WHERE user_id = ?");
                            $stmt->execute([$userId]);
                            while ($farm = $stmt->fetch()) {
                                echo "<option value='" . $farm['id'] . "'>" . htmlspecialchars($farm['farm_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                        <input type="number" name="weight_kg" step="0.01" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quality Grade</label>
                        <select name="quality_grade" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-600 focus:border-transparent">
                            <option value="A">Grade A</option>
                            <option value="B">Grade B</option>
                            <option value="C">Grade C</option>
                            <option value="D">Grade D</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddBatchModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Add Batch
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddBatchModal() {
            document.getElementById('addBatchModal').classList.remove('hidden');
            document.getElementById('addBatchModal').classList.add('flex');
        }

        function closeAddBatchModal() {
            document.getElementById('addBatchModal').classList.add('hidden');
            document.getElementById('addBatchModal').classList.remove('flex');
        }

        // Form submission
        async function submitBatch(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('api/add_batch.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    closeAddBatchModal();
                    location.reload();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }

        // Batch operations
        function viewBatchDetails(batchId) {
            window.location.href = `batch_details.php?id=${batchId}`;
        }

        function editBatch(batchId) {
            window.location.href = `edit_batch.php?id=${batchId}`;
        }

        async function deleteBatch(batchId) {
            if (confirm('Are you sure you want to delete this batch?')) {
                try {
                    const response = await fetch(`api/delete_batch.php?id=${batchId}`);
                    const result = await response.json();
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.error);
                    }
                } catch (error) {
                    alert('An error occurred. Please try again.');
                }
            }
        }

        // GSAP Animations
        gsap.from('.glass-card', {
            duration: 1,
            y: 30,
            opacity: 0,
            ease: 'power3.out'
        });
    </script>
</body>
</html> 
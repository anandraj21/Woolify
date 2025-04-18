<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get batch ID from URL
$batch_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$batch_id) {
    header("Location: dashboard.php");
    exit();
}

// Get batch details with farm information
$stmt = $conn->prepare("
    SELECT wb.*, f.farm_name, f.location as farm_location, f.registration_number,
           u.name as farmer_name
    FROM wool_batches wb
    JOIN farms f ON wb.farm_id = f.id
    JOIN users u ON f.user_id = u.id
    WHERE wb.id = ?
");
$stmt->execute([$batch_id]);
$batch = $stmt->fetch();

if (!$batch) {
    header("Location: dashboard.php");
    exit();
}

// Get quality checks
$stmt = $conn->prepare("
    SELECT qc.*, u.name as checked_by_name
    FROM quality_checks qc
    JOIN users u ON qc.checked_by = u.id
    WHERE qc.batch_id = ?
    ORDER BY qc.check_date DESC
");
$stmt->execute([$batch_id]);
$quality_checks = $stmt->fetchAll();

// Get processing records
$stmt = $conn->prepare("
    SELECT pr.*, pf.facility_name, u.name as processor_name
    FROM processing_records pr
    JOIN processing_facilities pf ON pr.facility_id = pf.id
    JOIN users u ON pf.user_id = u.id
    WHERE pr.batch_id = ?
    ORDER BY pr.start_date DESC
");
$stmt->execute([$batch_id]);
$processing_records = $stmt->fetchAll();

// Get transportation records
$stmt = $conn->prepare("
    SELECT *
    FROM transportation_records
    WHERE batch_id = ?
    ORDER BY departure_date DESC
");
$stmt->execute([$batch_id]);
$transportation_records = $stmt->fetchAll();

// Get tracking history
$stmt = $conn->prepare("
    SELECT bth.*, u.name as action_by_name
    FROM batch_tracking_history bth
    JOIN users u ON bth.action_by = u.id
    WHERE bth.batch_id = ?
    ORDER BY bth.action_date DESC
");
$stmt->execute([$batch_id]);
$tracking_history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch #<?php echo htmlspecialchars($batch['batch_number']); ?> - Woolify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex items-center">
                            <span class="text-2xl font-bold text-indigo-600">Woolify</span>
                        </a>
                    </div>
                    <div>
                        <a href="dashboard.php" class="text-gray-600 hover:text-indigo-600 px-3 py-2">Dashboard</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Batch Overview -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Batch #<?php echo htmlspecialchars($batch['batch_number']); ?>
                        </h3>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            $statusColors = [
                                'at_farm' => 'bg-blue-100 text-blue-800',
                                'in_processing' => 'bg-yellow-100 text-yellow-800',
                                'processed' => 'bg-green-100 text-green-800',
                                'in_transit' => 'bg-purple-100 text-purple-800',
                                'delivered' => 'bg-gray-100 text-gray-800'
                            ];
                            echo $statusColors[$batch['status']] ?? '';
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $batch['status'])); ?>
                        </span>
                    </div>
                </div>
                <div class="border-t border-gray-200">
                    <dl>
                        <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Farm Details</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <?php echo htmlspecialchars($batch['farm_name']); ?><br>
                                Location: <?php echo htmlspecialchars($batch['farm_location']); ?><br>
                                Registration: <?php echo htmlspecialchars($batch['registration_number']); ?><br>
                                Farmer: <?php echo htmlspecialchars($batch['farmer_name']); ?>
                            </dd>
                        </div>
                        <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">Batch Details</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                Shearing Date: <?php echo date('F j, Y', strtotime($batch['shearing_date'])); ?><br>
                                Weight: <?php echo htmlspecialchars($batch['weight_kg']); ?> kg<br>
                                Quality Grade: <?php echo htmlspecialchars($batch['quality_grade']); ?><br>
                                <?php if ($batch['notes']): ?>
                                    Notes: <?php echo htmlspecialchars($batch['notes']); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button class="tab-button border-indigo-500 text-indigo-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="quality">
                            Quality Checks
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="processing">
                            Processing
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="transport">
                            Transportation
                        </button>
                        <button class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" data-tab="history">
                            History
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab Contents -->
            <div id="quality" class="tab-content">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Quality Checks</h3>
                        <?php if ($_SESSION['user_role'] === 'processor'): ?>
                            <a href="add_quality_check.php?batch_id=<?php echo $batch_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                New Check
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-gray-200">
                        <?php if (empty($quality_checks)): ?>
                            <p class="p-4 text-gray-500 text-sm">No quality checks recorded yet.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scores</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($quality_checks as $check): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo date('M j, Y', strtotime($check['check_date'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($check['checked_by_name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    Cleanliness: <?php echo $check['cleanliness_score']; ?>/10<br>
                                                    Strength: <?php echo $check['strength_score']; ?>/10<br>
                                                    Color: <?php echo $check['color_uniformity_score']; ?>/10
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $check['overall_grade']; ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($check['notes'] ?? ''); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="processing" class="tab-content hidden">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Processing Records</h3>
                        <?php if ($_SESSION['user_role'] === 'processor'): ?>
                            <a href="start_processing.php?batch_id=<?php echo $batch_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                Start Processing
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-gray-200">
                        <?php if (empty($processing_records)): ?>
                            <p class="p-4 text-gray-500 text-sm">No processing records yet.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Process</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Output Weight</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($processing_records as $record): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo ucfirst($record['process_type']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($record['facility_name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($record['start_date'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $record['end_date'] ? date('M j, Y', strtotime($record['end_date'])) : 'In Progress'; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $record['output_weight_kg'] ? $record['output_weight_kg'] . ' kg' : '-'; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if ($record['quality_check_passed'] === null): ?>
                                                        Pending
                                                    <?php else: ?>
                                                        <?php echo $record['quality_check_passed'] ? 'Passed' : 'Failed'; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="transport" class="tab-content hidden">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Transportation Records</h3>
                        <?php if ($_SESSION['user_role'] === 'distributor'): ?>
                            <a href="new_shipment.php?batch_id=<?php echo $batch_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                                New Shipment
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="border-t border-gray-200">
                        <?php if (empty($transportation_records)): ?>
                            <p class="p-4 text-gray-500 text-sm">No transportation records yet.</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ETA</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($transportation_records as $transport): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo ucfirst($transport['from_location_type']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo ucfirst($transport['to_location_type']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($transport['departure_date'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('M j, Y', strtotime($transport['estimated_arrival_date'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        <?php
                                                        $transportStatusColors = [
                                                            'scheduled' => 'bg-yellow-100 text-yellow-800',
                                                            'in_transit' => 'bg-blue-100 text-blue-800',
                                                            'delivered' => 'bg-green-100 text-green-800',
                                                            'delayed' => 'bg-red-100 text-red-800'
                                                        ];
                                                        echo $transportStatusColors[$transport['status']] ?? '';
                                                        ?>">
                                                        <?php echo ucfirst($transport['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo $transport['tracking_number'] ?? '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="history" class="tab-content hidden">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Tracking History</h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                <?php foreach ($tracking_history as $index => $event): ?>
                                    <li>
                                        <div class="relative pb-8">
                                            <?php if ($index !== count($tracking_history) - 1): ?>
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-check text-white"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($event['details']); ?>
                                                            <span class="font-medium text-gray-900">
                                                                by <?php echo htmlspecialchars($event['action_by_name']); ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <?php echo date('M j, Y H:i', strtotime($event['action_date'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');

            function switchTab(tabId) {
                // Update tab buttons
                tabs.forEach(tab => {
                    if (tab.dataset.tab === tabId) {
                        tab.classList.add('border-indigo-500', 'text-indigo-600');
                        tab.classList.remove('border-transparent', 'text-gray-500');
                    } else {
                        tab.classList.remove('border-indigo-500', 'text-indigo-600');
                        tab.classList.add('border-transparent', 'text-gray-500');
                    }
                });

                // Update content visibility
                contents.forEach(content => {
                    if (content.id === tabId) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    switchTab(tab.dataset.tab);
                });
            });
        });
    </script>
</body>
</html> 
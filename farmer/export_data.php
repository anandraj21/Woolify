<?php
// No HTML output before headers
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/WoolBatch.php';
require_once __DIR__ . '/../models/Farmer.php'; // Needed to get farmer ID

$auth->requireRole('FARMER');

// Get farmer ID
$user = $auth->getUser();
$userId = $user['id'];
$farmerModel = new Farmer();
$farmerData = $farmerModel->findByUserId($userId);
if (!$farmerData) {
    // Handle error appropriately - perhaps redirect with an error message
    // For a direct download script, echoing an error might be confusing.
    // Logging the error and exiting is safer.
    error_log("Export Error: Farmer record not found for user ID: {$userId}");
    exit('Error: Farmer record not found.'); 
}
$farmerId = $farmerData['id'];

// What data to export? For now, default to batches.
$exportType = $_GET['type'] ?? 'batches'; 

if ($exportType === 'batches') {
    // Fetch all batches for this farmer
    $woolBatchModel = new WoolBatch();
    $batches = [];
    try {
        // Use the existing method, fetch all statuses
        $batches = $woolBatchModel->getBatchesByFarmer($farmerId, null); 
    } catch (Exception $e) {
        error_log("Export Error: Could not fetch batches for farmer {$farmerId}: " . $e->getMessage());
        exit('Error: Could not retrieve batch data for export.');
    }

    // Set headers for CSV download
    $filename = "wool_batches_export_" . date('Ymd') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open output stream
    $output = fopen('php://output', 'w');
    if ($output === false) {
         error_log("Export Error: Could not open php://output stream for farmer {$farmerId}");
        exit('Error: Could not open output stream.');
    }

    // Write header row
    if (!empty($batches)) {
        // Get headers from the keys of the first batch
        fputcsv($output, array_keys($batches[0]));
    } else {
        // Write empty state message or just headers
         fputcsv($output, ['id', 'farmer_id', 'quantity', 'micron', 'grade', 'status', 'price', 'created_at', 'updated_at']); // Example headers
         fputcsv($output, ['No batch data found for export.']);
    }

    // Write data rows
    foreach ($batches as $batch) {
        fputcsv($output, $batch);
    }

    // Close the output stream (important!)
    fclose($output);
    exit; // Stop script execution after sending file

} else {
    // Handle other export types or show an error/message page
    // For now, just exit if type is not batches
    // We need a regular HTML page for the user to select what to export.
    // This script should *only* handle the download itself.
    // Redirecting back to a UI page.
    header('Location: dashboard.php?error=invalid_export_type'); // Redirect to dashboard or a dedicated export UI
    exit('Invalid export type specified.');
}

?> 
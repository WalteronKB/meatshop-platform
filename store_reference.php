<?php
// Suppress all PHP errors/warnings to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Clear any previous output
ob_clean();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    
    if (isset($_POST['transaction_id'])) {
        $_SESSION['gcash_transaction_id'] = $_POST['transaction_id'];
        $success = true;
    }
    
    if (isset($_POST['reference_number'])) {
        $_SESSION['gcash_reference'] = $_POST['reference_number'];
        $success = true;
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data provided']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
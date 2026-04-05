<?php
include '../connection.php';
session_start();

// Set the timezone to Philippines time
date_default_timezone_set('Asia/Manila');

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
$current_admin_shop_id = null;
if ($current_user_id > 0) {
    $shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
    $shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
    if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
        $shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
        $current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
    }

    if (($current_admin_shop_id === null || $current_admin_shop_id <= 0) && !$is_super_admin) {
        $fallback_shop_query = "SELECT approved_shop_id FROM approved_shops WHERE user_id = {$current_user_id} ORDER BY (shop_status = 'active') DESC, updated_at DESC, approved_shop_id DESC LIMIT 1";
        $fallback_shop_result = mysqli_query($conn, $fallback_shop_query);
        if ($fallback_shop_result && mysqli_num_rows($fallback_shop_result) > 0) {
            $fallback_shop_row = mysqli_fetch_assoc($fallback_shop_result);
            $current_admin_shop_id = isset($fallback_shop_row['approved_shop_id']) ? (int)$fallback_shop_row['approved_shop_id'] : null;
        }
    }
}

header('Content-Type: application/json'); // Set the response content type to JSON

// Log the POST data for debugging
file_put_contents('debug.log', print_r($_POST, true), FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get message data
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, trim($_POST['message'])) : '';
    
    if (empty($user_id) || $message === '') {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    if (!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)) {
        echo json_encode(['success' => false, 'error' => 'No shop assigned to this admin']);
        exit;
    }

    $message_shop_id = 0;
    if ($is_super_admin) {
        $user_shop_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$user_id} LIMIT 1";
        $user_shop_result = mysqli_query($conn, $user_shop_query);
        if ($user_shop_result && mysqli_num_rows($user_shop_result) > 0) {
            $user_shop_row = mysqli_fetch_assoc($user_shop_result);
            $message_shop_id = isset($user_shop_row['shop_id']) ? (int)$user_shop_row['shop_id'] : 0;
        }
    } else {
        $message_shop_id = (int)$current_admin_shop_id;
    }

    if ($message_shop_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Cannot determine message shop context']);
        exit;
    }
    
    // Current timestamp for MySQL using correct timezone
    $currentTime = date('Y-m-d H:i:s');
    
    // Insert message into database with explicit timestamp
    $query = "INSERT INTO mrb_messages (user_id, message, message_type, message_datesent, shop_id) 
              VALUES ('{$user_id}', '{$message}', 'admin', '{$currentTime}', '{$message_shop_id}')";
    
    if (mysqli_query($conn, $query)) {
        // Log successful message insertion with timestamp
        file_put_contents('debug.log', 
            "Message sent successfully at: " . date('Y-m-d H:i:s') . 
            "\nUser ID: $user_id" . 
            "\nTimestamp: $currentTime\n\n", 
            FILE_APPEND);
            
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
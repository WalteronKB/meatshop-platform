<?php
session_start();
include '../connection.php';

header('Content-Type: application/json');

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
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

$message_shop_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $message_shop_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        echo json_encode(['success' => false, 'error' => 'No shop assigned to this admin']);
        exit;
    }
}

// Get user_id from POST request
if(!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User ID not provided']);
    exit;
}

$user_id = mysqli_real_escape_string($conn, $_POST['user_id']);

// Update all user-chat messages from this user to mark as seen by admin
$update_query = "UPDATE mrb_messages 
                 SET seen_byadmin = 1 
                 WHERE user_id = '$user_id' 
                 AND message_type = 'user-chat' 
                 AND seen_byadmin = 0{$message_shop_scope_condition}";

if(mysqli_query($conn, $update_query)) {
    $affected_rows = mysqli_affected_rows($conn);
    
    // Get remaining unseen messages count
    $remaining_query = "SELECT COUNT(*) AS remaining_count FROM mrb_messages 
                        WHERE message_type = 'user-chat' AND seen_byadmin = 0{$message_shop_scope_condition}";
    $remaining_result = mysqli_query($conn, $remaining_query);
    $remaining_count = 0;
    if($remaining_result) {
        $remaining_row = mysqli_fetch_assoc($remaining_result);
        $remaining_count = $remaining_row['remaining_count'];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Messages marked as seen',
        'affected_rows' => $affected_rows,
        'remaining_count' => $remaining_count
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
}
?>

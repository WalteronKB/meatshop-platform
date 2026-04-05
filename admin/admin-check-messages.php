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

$message_shop_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $message_shop_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        echo json_encode(['success' => false, 'error' => 'No shop assigned to this admin']);
        exit;
    }
}

header('Content-Type: application/json'); // Set the response content type to JSON

// Debug log
file_put_contents('debug.log', "Admin message check at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Get parameters
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$lastTime = isset($_GET['last_time']) ? intval($_GET['last_time']) : 0;

// If no user_id is provided, return all users for the user list
if (empty($userId)) {
    $query = "SELECT user_id, first_name, last_name, email, user_type, profile_picture 
              FROM mrb_account 
              ORDER BY first_name, last_name";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        file_put_contents('debug.log', "Query error: " . mysqli_error($conn) . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
        exit;
    }
    
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = [
            'user_id' => $row['user_id'],
            'first_name' => htmlspecialchars($row['first_name']),
            'last_name' => htmlspecialchars($row['last_name']),
            'email' => htmlspecialchars($row['email']),
            'user_type' => htmlspecialchars($row['user_type']),
            'profile_picture' => $row['profile_picture'] ? htmlspecialchars($row['profile_picture']) : null
        ];
    }
    
    file_put_contents('debug.log', "Returning " . count($users) . " users\n", FILE_APPEND);
    echo json_encode($users);
    exit;
}

// Debug log for parameters
file_put_contents('debug.log', "Checking messages for user ID: $userId, since timestamp: $lastTime\n", FILE_APPEND);

// Convert Unix timestamp to MySQL datetime format
$lastTimeFormatted = date('Y-m-d H:i:s', $lastTime);

// Find messages newer than the last timestamp
$query = "SELECT * FROM mrb_messages 
          WHERE user_id = $userId{$message_shop_scope_condition} 
          AND UNIX_TIMESTAMP(message_datesent) > $lastTime 
          ORDER BY message_datesent ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    file_put_contents('debug.log', "Query error: " . mysqli_error($conn) . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    exit;
}

$messages = [];
$latestTimestamp = $lastTime;

while ($row = mysqli_fetch_assoc($result)) {
    $messageTimestamp = strtotime($row['message_datesent']);
    
    // Format the timestamp for display
    $formattedTime = date('M j, g:i a', $messageTimestamp);
    
    // Prepare message HTML based on sender type
    if ($row['message_type'] == 'admin') {
        // Admin message (right-aligned)
        $html = "
            <div class='message admin-message d-flex justify-content-end mb-2'>
                <div class='message-content bg-primary text-white p-2 rounded' style='max-width: 75%; word-wrap: break-word;'>
                    <div>" . htmlspecialchars($row['message']) . "</div>
                    <div class='text-end'><small class='text-light'>{$formattedTime}</small></div>
                </div>
            </div>";
    } else {
        // User message (left-aligned)
        $html = "
            <div class='message user-message d-flex justify-content-start mb-2'>
                <div class='message-content bg-light p-2 rounded' style='max-width: 75%; word-wrap: break-word;'>
                    <div>" . htmlspecialchars($row['message']) . "</div>
                    <div class='text-end'><small class='text-muted'>{$formattedTime}</small></div>
                </div>
            </div>";
    }
    
    $messages[] = [
        'id' => $row['message_id'],
        'type' => $row['message_type'],
        'html' => $html,
        'timestamp' => $messageTimestamp
    ];
    
    // Keep track of the latest timestamp
    if ($messageTimestamp > $latestTimestamp) {
        $latestTimestamp = $messageTimestamp;
    }
}

// Log number of messages found
file_put_contents('debug.log', "Found " . count($messages) . " new messages\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'latest_timestamp' => $latestTimestamp
]);
?>
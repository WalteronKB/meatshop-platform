<?php
session_start();
include 'connection.php';

// Set the timezone to Philippines time to match other files
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get the last message timestamp the user has
$user_id = $_SESSION['user_id'];
$last_time = isset($_GET['last_time']) ? intval($_GET['last_time']) : 0;
$chat_shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;
$shop_scope = '';
if ($chat_shop_id > 0) {
    $shop_scope = " AND shop_id = $chat_shop_id";
}

// Query for new messages - look for ALL messages after the timestamp, not just admin messages
$query = "SELECT * FROM mrb_messages 
          WHERE user_id = '$user_id' 
          AND UNIX_TIMESTAMP(message_datesent) > $last_time  
          $shop_scope
          AND message_type = 'admin'
          ORDER BY message_datesent ASC";

// Add debug logging after variables and query are defined
file_put_contents('debug_check.log', 
    "Request at: " . date('Y-m-d H:i:s') . 
    "\nLast time: $last_time" . 
    "\nUser ID: $user_id" . 
    "\nQuery: $query\n\n", 
    FILE_APPEND);
          
$result = mysqli_query($conn, $query);

$new_messages = [];
$latest_timestamp = $last_time;

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $message = htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
        $dateSent = date('h:i A', strtotime($row['message_datesent']));
        $timestamp = strtotime($row['message_datesent']);
        $messageType = $row['message_type'];
        
        // Debug the actual timestamp from database for this message
        file_put_contents('timestamp_debug.log',
            "Message ID: {$row['message_id']}" .
            "\nMessage type: $messageType" .
            "\nRaw date: {$row['message_datesent']}" .
            "\nParsed timestamp: $timestamp" .
            "\nCurrent latest: $latest_timestamp\n\n",
            FILE_APPEND);
        
        if ($timestamp > $latest_timestamp) {
            $latest_timestamp = $timestamp;
        }
        
        // Determine the CSS class based on message type
        $messageClass = ($messageType == 'user-chat') ? 'chat-message' : 'chat-reply';
        
        $new_messages[] = [
            'id' => $row['message_id'], // Include message ID to prevent duplicates
            'message' => $message,
            'time' => $dateSent,
            'timestamp' => $timestamp,
            'type' => $messageType,
            'html' => "<div class='$messageClass'><p class='message'>{$message}</p></div><span class='chat-time message-time white-text'>{$dateSent}</span>"
        ];
    }
}

// Add response debugging
file_put_contents('timestamp_debug.log',
    "Response at: " . date('Y-m-d H:i:s') .
    "\nMessages found: " . count($new_messages) . 
    "\nLatest timestamp: $latest_timestamp\n\n",
    FILE_APPEND);

echo json_encode([
    'success' => true,
    'messages' => $new_messages,
    'latest_timestamp' => $latest_timestamp
]);
?>
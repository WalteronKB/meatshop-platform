<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Count unseen orders (new or updated)
$query = "SELECT COUNT(*) as count FROM mrb_orders 
          WHERE user_id = '$user_id' 
          AND seen_byuser = 'false' 
          AND order_status != 'Cancelled'";

$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

echo json_encode(['count' => (int)$row['count']]);
?>

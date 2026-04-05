<?php
session_start();
include 'connection.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Check if the request method is POST and location is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['location'])) {
    $newLocation = mysqli_real_escape_string($conn, $_POST['location']);
    
    // Update session variable
    $_SESSION['user_location'] = $newLocation;
    
    // If user is logged in, also update the database
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $updateQuery = "UPDATE mrb_users SET user_location = '$newLocation' WHERE user_id = '$userId'";
        $result = mysqli_query($conn, $updateQuery);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update database']);
        }
    } else {
        // For guest users, just update session
        echo json_encode(['success' => true, 'message' => 'Session location updated']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
?>
<?php
// filepath: c:\xampp\htdocs\MRBfireex\update_address.php
session_start();
include '../connection.php';

if (!isset($_POST['user_id'])) {
    header("Location: landpage.php");
    exit;
}

// Check if at least location1 city is provided
if(empty($_POST['location1_city'])) {
    header("Location: account-admin.php?user_id=".$_POST['user_id']."&error=true&msg=".urlencode("Primary location is required"));
    exit;
}

$user_id = $_POST['user_id'];

// Concatenate Location 1 (City, Barangay, Street)
$loc1_parts = array_filter([
    $_POST['location1_city'] ?? '',
    $_POST['location1_barangay'] ?? '',
    $_POST['location1_street'] ?? ''
]);
$location1 = mysqli_real_escape_string($conn, implode(', ', $loc1_parts));

// Concatenate Location 2 (City, Barangay, Street) - optional
$location2 = '';
if(!empty($_POST['location2_city'])) {
    $loc2_parts = array_filter([
        $_POST['location2_city'] ?? '',
        $_POST['location2_barangay'] ?? '',
        $_POST['location2_street'] ?? ''
    ]);
    $location2 = mysqli_real_escape_string($conn, implode(', ', $loc2_parts));
}

// Concatenate Location 3 (City, Barangay, Street) - optional
$location3 = '';
if(!empty($_POST['location3_city'])) {
    $loc3_parts = array_filter([
        $_POST['location3_city'] ?? '',
        $_POST['location3_barangay'] ?? '',
        $_POST['location3_street'] ?? ''
    ]);
    $location3 = mysqli_real_escape_string($conn, implode(', ', $loc3_parts));
}

$update_query = "UPDATE mrb_users SET 
                user_location = ?, 
                user_location2 = ?, 
                user_location3 = ? 
                WHERE user_id = ?";

$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssi", $location1, $location2, $location3, $user_id);

if($stmt->execute()) {
    header("Location: account-admin.php?updated=true&address=updated");
} else {
    header("Location: account-admin.php?error=true&msg=".urlencode("Failed to update addresses"));
}
exit;
?>
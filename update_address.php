<?php
// filepath: c:\xampp\htdocs\MRBfireex\update_address.php
session_start();
include 'connection.php';

function is_cavite_location(string $addressValue, string $cityValue = ''): bool {
    $caviteCities = [
        'Cavite City', 'Bacoor', 'Dasmarinas', 'Dasmariñas', 'Imus', 'Tagaytay', 'Trece Martires',
        'General Trias', 'Silang', 'Naic', 'Tanza', 'Rosario', 'Noveleta',
        'Kawit', 'Carmona', 'GMA', 'General Mariano Alvarez', 'Alfonso',
        'Amadeo', 'Indang', 'Magallanes', 'Maragondon', 'Mendez', 'Ternate'
    ];

    $addressLower = strtolower(trim($addressValue));
    $cityLower = strtolower(trim($cityValue));
    if ($addressLower !== '' && strpos($addressLower, 'cavite') !== false) {
        return true;
    }

    foreach ($caviteCities as $city) {
        if ($cityLower !== '' && $cityLower === strtolower($city)) {
            return true;
        }
        if ($addressLower !== '' && strpos($addressLower, strtolower($city)) !== false) {
            return true;
        }
    }

    return false;
}

if (!isset($_POST['user_id'])) {
    header("Location: landpage.php");
    exit;
}

// Accept either manual city/barangay/street fields or exact map full address.
$location1_full_address = trim($_POST['location1_full_address'] ?? '');

if ($location1_full_address === '' && empty($_POST['location1_city'])) {
    header("Location: usersetting.php?user_id=".$_POST['user_id']."&error=true&msg=".urlencode("Primary location is required"));
    exit;
}

$user_id = $_POST['user_id'];

// Use exact map address when available, otherwise fallback to manual fields.
if ($location1_full_address !== '') {
    $location1 = mysqli_real_escape_string($conn, $location1_full_address);
} else {
    $loc1_parts = array_filter([
        $_POST['location1_city'] ?? '',
        $_POST['location1_barangay'] ?? '',
        $_POST['location1_street'] ?? ''
    ]);
    $location1 = mysqli_real_escape_string($conn, implode(', ', $loc1_parts));
}

if (!is_cavite_location($location1_full_address !== '' ? $location1_full_address : $location1, $_POST['location1_city'] ?? '')) {
    header("Location: usersetting.php?user_id=$user_id&error=true&msg=".urlencode("Location 1 must be within Cavite only."));
    exit;
}

// Concatenate Location 2 (City, Barangay, Street) - optional
$location2 = '';
$location2_full_address = trim($_POST['location2_full_address'] ?? '');
if ($location2_full_address !== '') {
    $location2 = mysqli_real_escape_string($conn, $location2_full_address);
} elseif(!empty($_POST['location2_city'])) {
    $loc2_parts = array_filter([
        $_POST['location2_city'] ?? '',
        $_POST['location2_barangay'] ?? '',
        $_POST['location2_street'] ?? ''
    ]);
    $location2 = mysqli_real_escape_string($conn, implode(', ', $loc2_parts));
}

if ($location2 !== '' && !is_cavite_location($location2_full_address !== '' ? $location2_full_address : $location2, $_POST['location2_city'] ?? '')) {
    header("Location: usersetting.php?user_id=$user_id&error=true&msg=".urlencode("Location 2 must be within Cavite only."));
    exit;
}

// Concatenate Location 3 (City, Barangay, Street) - optional
$location3 = '';
$location3_full_address = trim($_POST['location3_full_address'] ?? '');
if ($location3_full_address !== '') {
    $location3 = mysqli_real_escape_string($conn, $location3_full_address);
} elseif(!empty($_POST['location3_city'])) {
    $loc3_parts = array_filter([
        $_POST['location3_city'] ?? '',
        $_POST['location3_barangay'] ?? '',
        $_POST['location3_street'] ?? ''
    ]);
    $location3 = mysqli_real_escape_string($conn, implode(', ', $loc3_parts));
}

if ($location3 !== '' && !is_cavite_location($location3_full_address !== '' ? $location3_full_address : $location3, $_POST['location3_city'] ?? '')) {
    header("Location: usersetting.php?user_id=$user_id&error=true&msg=".urlencode("Location 3 must be within Cavite only."));
    exit;
}

$update_query = "UPDATE mrb_users SET 
                user_location = ?, 
                user_location2 = ?, 
                user_location3 = ? 
                WHERE user_id = ?";

$stmt = $conn->prepare($update_query);
$stmt->bind_param("sssi", $location1, $location2, $location3, $user_id);

if($stmt->execute()) {
    header("Location: usersetting.php?user_id=$user_id&updated=true&address=updated");
} else {
    header("Location: usersetting.php?user_id=$user_id&error=true&msg=".urlencode("Failed to update addresses"));
}
exit;
?>
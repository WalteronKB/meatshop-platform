<?php
session_start();
include '../connection.php';

// Get user ID from session and validate
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Verify the admin is logged in
if (!isset($user_id) || !isset($_SESSION['admin_logged_in'])) {
    header("Location: ../mrbloginpage.php");
    exit;
}

// Now proceed with the update using the validated session user_id
$name = mysqli_real_escape_string($conn, $_POST['edit_name']);
$mname = mysqli_real_escape_string($conn, $_POST['edit_mname']);
$lname = mysqli_real_escape_string($conn, $_POST['edit_lname']);
$current_role = $_SESSION['user_type'] ?? '';
$is_super_admin = $current_role === 'super_admin';

if ($is_super_admin) {
    $email = trim($_POST['edit_email'] ?? '');
    $phone = trim($_POST['edit_phone'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: account_super_admin.php?updated=false&password_msg=" . urlencode("Invalid email format."));
        exit;
    }

    $update_query = "UPDATE mrb_users SET 
                    user_name = ?,
                    user_mname = ?,
                    user_lname = ?,
                    user_email = ?,
                    user_contactnum = ?
                    WHERE user_id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssi", $name, $mname, $lname, $email, $phone, $user_id);
} else {
    $update_query = "UPDATE mrb_users SET 
                    user_name = ?,
                    user_mname = ?,
                    user_lname = ?
                    WHERE user_id = ?";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $name, $mname, $lname, $user_id);
}

$basic_update = $stmt->execute();

// Password update section
if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password from database for comparison
    $check_pwd = "SELECT user_password FROM mrb_users WHERE user_id = ?";
    $stmt = $conn->prepare($check_pwd);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Verify current password (handle both hashed and plain text passwords)
    $current_password_valid = false;
    if (password_verify($current_password, $user['user_password'])) {
        // Password is hashed and matches
        $current_password_valid = true;
    } elseif ($current_password === $user['user_password']) {
        // Legacy plain text password matches
        $current_password_valid = true;
    }
    
    if ($current_password_valid) {
        if ($new_password == $confirm_password) {
            // Hash the new password before storing
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pwd = "UPDATE mrb_users SET user_password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_pwd);
            $stmt->bind_param("si", $hashed_new_password, $user_id);
            $pwd_update = $stmt->execute();
            $password_message = "Password updated successfully.";
        } else {
            $password_message = "New passwords do not match.";
        }
    } else {
        $password_message = "Current password is incorrect.";
    }
}

// Profile picture upload section
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_pic']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (in_array(strtolower($ext), $allowed)) {
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_dir = '../Images/profile_pics/';
        
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
            // Store the path relative to the site root for consistency
            $pic_path = str_replace('../', '', $destination);
            $update_pic = "UPDATE mrb_users SET user_pic = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_pic);
            $stmt->bind_param("si", $pic_path, $user_id);
            $pic_update = $stmt->execute();
        }
    }
}

// Redirect back to role-appropriate account page with appropriate messages
$redirect_page = 'account-admin.php';
if ($current_role === 'super_admin') {
    $redirect_page = 'account_super_admin.php';
} elseif (!in_array($current_role, ['admin', 'super_admin'], true)) {
    $redirect_page = 'account-staff.php';
}

header("Location: {$redirect_page}?updated=true" .
    (isset($password_message) ? "&password_msg=" . urlencode($password_message) : ""));
exit();
?>
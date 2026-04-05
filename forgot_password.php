<?php

session_start();
include 'connection.php';

if (isset($_POST['forgot-submit'])) {
    $email = trim($_POST['reset_email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_error'] = "Please enter a valid email address.";
        header("Location: mrbloginpage.php");
        exit();
    }
    
    // Check if user exists
    $user_query = "SELECT user_id, user_name FROM mrb_users WHERE user_email = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $user_name = $user['user_name'];
        
        // Generate secure token
                // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Extended to 24 hours instead of 1 hour
        
        // Delete any existing tokens for this user
        $delete_query = "DELETE FROM password_reset_tokens WHERE user_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        
        // Insert new token
        $insert_query = "INSERT INTO password_reset_tokens (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isss", $user_id, $email, $token, $expires_at);
        
        if ($insert_stmt->execute()) {
            // Create reset link
            $reset_link = "http://localhost/meatshop/MRBfireex/reset_password.php?token=" . $token;
            
            // Return success with token data for EmailJS to handle
            $_SESSION['forgot_success'] = "Password reset link generated successfully.";
            $_SESSION['reset_data'] = json_encode([
                'user_name' => $user_name,
                'user_email' => $email,
                'reset_link' => $reset_link,
                'expires_at' => $expires_at
            ]);
            $_SESSION['send_email'] = true;
        } else {
            $_SESSION['forgot_error'] = "Failed to generate reset link. Please try again.";
        }
    } else {
        // For security, don't reveal if email exists or not
        $_SESSION['forgot_success'] = "If an account with that email exists, a password reset link has been sent.";
    }
    
    header("Location: mrbloginpage.php");
    exit();
}
?>
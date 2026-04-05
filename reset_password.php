<?php
// filepath: c:\xampp\htdocs\MRBfireex\reset_password.php
session_start();
include 'connection.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_data = null;

if (!empty($token)) {
    // Verify token
    $token_query = "SELECT pt.*, u.user_name, u.user_email 
                    FROM password_reset_tokens pt 
                    JOIN mrb_users u ON pt.user_id = u.user_id 
                    WHERE pt.token = ? AND pt.expires_at > NOW() AND pt.used = FALSE";
    $stmt = $conn->prepare($token_query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $valid_token = true;
        $user_data = $result->fetch_assoc();
    }
}

// Handle password reset submission
if (isset($_POST['reset-submit']) && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE mrb_users SET user_password = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $hashed_password, $user_data['user_id']);
        
        if ($update_stmt->execute()) {
            // Mark token as used
            $mark_used_query = "UPDATE password_reset_tokens SET used = TRUE WHERE token = ?";
            $mark_stmt = $conn->prepare($mark_used_query);
            $mark_stmt->bind_param("s", $token);
            $mark_stmt->execute();
            
            $success = "Password has been reset successfully. You can now log in with your new password.";
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Meat Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="mrbstyle.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h3>Reset Password</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!$valid_token): ?>
                            <div class="alert alert-danger">
                                <h5>Invalid or Expired Link</h5>
                                <p>This password reset link is invalid or has expired. Please request a new one.</p>
                                <a href="mrbloginpage.php" class="btn btn-primary">Back to Login</a>
                            </div>
                        <?php elseif (isset($success)): ?>
                            <div class="alert alert-success">
                                <h5>Success!</h5>
                                <p><?php echo $success; ?></p>
                                <a href="mrbloginpage.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php else: ?>
                            <p>Enter your new password for: <strong><?php echo htmlspecialchars($user_data['user_email']); ?></strong></p>
                            
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <form method="post">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="reset-submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <a href="mrbloginpage.php" class="text-decoration-none">Back to Login</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
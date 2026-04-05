<?php
session_start();
include '../../connection.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $_SESSION['error'] = 'Not authenticated';
    header('Location: ../payroll-admin.php');
    exit;
}

// Get form data
$work_start_time = mysqli_real_escape_string($conn, $_POST['work_start_time'] ?? '08:00:00');
$late_threshold = intval($_POST['late_threshold'] ?? 15);
$deduction_type = mysqli_real_escape_string($conn, $_POST['deduction_type'] ?? 'fixed');
$deduction_per_minute = floatval($_POST['deduction_per_minute'] ?? 10.00);
$fixed_deduction = floatval($_POST['fixed_deduction'] ?? 50.00);
$working_days = intval($_POST['working_days'] ?? 26);

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
}

$employee_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $employee_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        $_SESSION['error'] = 'No shop assigned. Cannot update attendance settings.';
        header('Location: ../payroll-admin.php');
        exit;
    }
}

// Deactivate all current settings
$deactivate_query = "UPDATE mrb_attendance_settings SET is_active = 0";
mysqli_query($conn, $deactivate_query);

// Insert new settings
$insert_query = "INSERT INTO mrb_attendance_settings 
                (work_start_time, late_threshold_minutes, deduction_per_minute, 
                 fixed_late_deduction, deduction_type, working_days_per_month, is_active) 
                 VALUES 
                ('$work_start_time', $late_threshold, $deduction_per_minute, 
                 $fixed_deduction, '$deduction_type', $working_days, 1)";

if(mysqli_query($conn, $insert_query)) {
    // Update daily rates for all employees based on new working days
    $update_rates = "UPDATE mrb_employees SET daily_rate = salary / $working_days WHERE status = 'Active'{$employee_scope_condition}";
    mysqli_query($conn, $update_rates);
    
    $_SESSION['success_message'] = 'Attendance settings updated successfully';
} else {
    $_SESSION['error'] = 'Failed to update settings: ' . mysqli_error($conn);
}

header('Location: ../payroll-admin.php');
exit;
?>

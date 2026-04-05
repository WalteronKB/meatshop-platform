<?php
session_start();
include '../../connection.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../mrbloginpage.php");
    exit;
}

// Get employee ID
if(!isset($_GET['emp_id']) || empty($_GET['emp_id'])) {
    $_SESSION['error'] = 'Employee ID not provided';
    header("Location: ../payroll-admin.php");
    exit;
}

$emp_id = mysqli_real_escape_string($conn, $_GET['emp_id']);

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
        $_SESSION['error'] = 'No shop assigned. Cannot delete employee.';
        header("Location: ../payroll-admin.php");
        exit;
    }
}

// Get employee info before deleting
$emp_query = "SELECT emp_number, emp_first_name, emp_last_name FROM mrb_employees WHERE emp_id = '$emp_id'{$employee_scope_condition}";
$emp_result = mysqli_query($conn, $emp_query);
$emp_data = mysqli_fetch_assoc($emp_result);

if ($emp_data) {
    $emp_name = $emp_data['emp_first_name'] . ' ' . $emp_data['emp_last_name'];
    $emp_number = $emp_data['emp_number'];
    
    // Delete employee
    $delete_query = "DELETE FROM mrb_employees WHERE emp_id = '$emp_id'{$employee_scope_condition}";
    
    if(mysqli_query($conn, $delete_query)) {
        // Log activity
        $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
        $activity_desc = "Employee '{$emp_name}' (Employee #: {$emp_number}) was deleted from payroll by {$admin_name}";
        $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
        $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'payroll', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['success'] = 'Employee deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete employee: ' . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = 'Employee not found';
}

header("Location: ../payroll-admin.php");
exit;
?>

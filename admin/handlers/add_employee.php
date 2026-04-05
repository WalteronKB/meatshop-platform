<?php
session_start();
include('../../connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $staff_role_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_employees LIKE 'staff_role'");
    if ($staff_role_column_check && mysqli_num_rows($staff_role_column_check) === 0) {
        @mysqli_query($conn, "ALTER TABLE mrb_employees ADD COLUMN staff_role VARCHAR(30) NULL AFTER department");
    }

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

    if (!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)) {
        $_SESSION['error_message'] = 'No shop assigned. Cannot add employee.';
        header('Location: ../payroll-admin.php');
        exit;
    }

    $target_shop_id = $is_super_admin ? (int)($_POST['shop_id'] ?? 0) : (int)$current_admin_shop_id;
    if ($is_super_admin && $target_shop_id <= 0 && $current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $target_shop_id = (int)$current_admin_shop_id;
    }
    if ($target_shop_id <= 0) {
        $_SESSION['error_message'] = 'Invalid shop selected.';
        header('Location: ../payroll-admin.php');
        exit;
    }

    // Sanitize and validate input
    $emp_number = mysqli_real_escape_string($conn, trim($_POST['emp_number']));
    $emp_first_name = mysqli_real_escape_string($conn, trim($_POST['emp_first_name']));
    $emp_middle_name = isset($_POST['emp_middle_name']) ? mysqli_real_escape_string($conn, trim($_POST['emp_middle_name'])) : '';
    $emp_last_name = mysqli_real_escape_string($conn, trim($_POST['emp_last_name']));
    $position = mysqli_real_escape_string($conn, trim($_POST['position']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $staff_role = mysqli_real_escape_string($conn, trim($_POST['staff_role'] ?? ''));
    $salary = floatval($_POST['salary']);
        $allowed_staff_roles = ['butcher', 'cashier', 'finance', 'rider'];
        if (!in_array($staff_role, $allowed_staff_roles, true)) {
            $_SESSION['error_message'] = 'Please select a valid system role for the employee.';
            header('Location: ../payroll-admin.php');
            exit;
        }

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $contact_number = mysqli_real_escape_string($conn, trim($_POST['contact_number']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $hire_date = mysqli_real_escape_string($conn, $_POST['hire_date']);

    // Check if employee number already exists
    $check_query = "SELECT emp_id FROM mrb_employees WHERE emp_number = '$emp_number'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error_message'] = 'Employee number already exists! Please use a different number.';
        header('Location: ../payroll-admin.php');
        exit;
    }

    // Check if email already exists
    $check_email = "SELECT emp_id FROM mrb_employees WHERE email = '$email'";
    $email_result = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($email_result) > 0) {
        $_SESSION['error_message'] = 'Email already exists! Please use a different email.';
        header('Location: ../payroll-admin.php');
        exit;
    }

    // Insert new employee
    $insert_query = "INSERT INTO mrb_employees 
                    (emp_number, emp_first_name, emp_middle_name, emp_last_name, position, department, staff_role, salary, email, contact_number, status, hire_date, created_at, updated_at, shop_id)
                    VALUES ('$emp_number', '$emp_first_name', '$emp_middle_name', '$emp_last_name', '$position', '$department', '$staff_role', $salary, '$email', '$contact_number', '$status', '$hire_date', NOW(), NOW(), '{$target_shop_id}')";

    if (mysqli_query($conn, $insert_query)) {
        // Log activity
        $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
        $emp_name = $emp_first_name . ' ' . $emp_last_name;
        $activity_desc = "Employee '{$emp_name}' (Employee #: {$emp_number}) was added to payroll by {$admin_name}";
        $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
        $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'payroll', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['success_message'] = 'Employee added successfully!';
        header('Location: ../payroll-admin.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'Error adding employee: ' . mysqli_error($conn);
        header('Location: ../payroll-admin.php');
        exit;
    }
} else {
    header('Location: ../payroll-admin.php');
    exit;
}
?>

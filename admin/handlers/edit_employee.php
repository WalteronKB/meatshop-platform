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

    $employee_scope_condition = "";
    if (!$is_super_admin) {
        if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
            $employee_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        } else {
            $_SESSION['error_message'] = 'No shop assigned. Cannot edit employee.';
            header('Location: ../payroll-admin.php');
            exit;
        }
    }

    // Get and sanitize input
    $emp_id = mysqli_real_escape_string($conn, $_POST['emp_id']);
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $position = mysqli_real_escape_string($conn, trim($_POST['position']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $staff_role = mysqli_real_escape_string($conn, trim($_POST['staff_role'] ?? ''));
    $salary = floatval($_POST['salary']);
        $allowed_staff_roles = ['butcher', 'cashier', 'finance', 'rider'];
        if (!in_array($staff_role, $allowed_staff_roles, true)) {
            $_SESSION['error_message'] = 'Please select a valid system role.';
            header('Location: ../payroll-admin.php');
            exit;
        }

    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $contact_number = mysqli_real_escape_string($conn, trim($_POST['contact_number']));

    // Get employee info before updating for activity log
    $emp_query = "SELECT emp_number, emp_first_name, emp_last_name FROM mrb_employees WHERE emp_id = '$emp_id'{$employee_scope_condition}";
    $emp_result = mysqli_query($conn, $emp_query);
    $emp_data = mysqli_fetch_assoc($emp_result);
    
    if (!$emp_data) {
        $_SESSION['error_message'] = 'Employee not found!';
        header('Location: ../payroll-admin.php');
        exit;
    }
    
    $emp_number = $emp_data['emp_number'];
    $old_emp_name = $emp_data['emp_first_name'] . ' ' . $emp_data['emp_last_name'];

    // Check if email already exists for another employee
    $check_email = "SELECT emp_id FROM mrb_employees WHERE email = '$email' AND emp_id != '$emp_id'";
    $email_result = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($email_result) > 0) {
        $_SESSION['error_message'] = 'Email already exists for another employee!';
        header('Location: ../payroll-admin.php');
        exit;
    }

    // Update employee
    $update_query = "UPDATE mrb_employees 
                    SET emp_first_name = '$first_name', 
                        emp_last_name = '$last_name', 
                        position = '$position', 
                        department = '$department', 
                        staff_role = '$staff_role', 
                        salary = $salary, 
                        email = '$email', 
                        contact_number = '$contact_number',
                        updated_at = NOW()
                    WHERE emp_id = '$emp_id'{$employee_scope_condition}";

    if (mysqli_query($conn, $update_query)) {
        // Log activity
        $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
        $new_emp_name = $first_name . ' ' . $last_name;
        $activity_desc = "Employee '{$old_emp_name}' (Employee #: {$emp_number}) was updated to '{$new_emp_name}' by {$admin_name}";
        $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
        $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'payroll', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['success_message'] = 'Employee updated successfully!';
        header('Location: ../payroll-admin.php');
        exit;
    } else {
        $_SESSION['error_message'] = 'Error updating employee: ' . mysqli_error($conn);
        header('Location: ../payroll-admin.php');
        exit;
    }
} else {
    header('Location: ../payroll-admin.php');
    exit;
}
?>

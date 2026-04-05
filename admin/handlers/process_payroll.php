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
if(!isset($_POST['period_start']) || !isset($_POST['period_end'])) {
    $_SESSION['error'] = 'Missing payroll period dates';
    header('Location: ../payroll-admin.php');
    exit;
}

$period_start = mysqli_real_escape_string($conn, $_POST['period_start']);
$period_end = mysqli_real_escape_string($conn, $_POST['period_end']);

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

$scope_shop_id = null;
if ($is_super_admin) {
    $scope_shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : null;
    if (($scope_shop_id === null || $scope_shop_id <= 0) && $current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $scope_shop_id = (int)$current_admin_shop_id;
    }
    if ($scope_shop_id === null || $scope_shop_id <= 0) {
        $_SESSION['error'] = 'Super admin must select a shop before processing payroll';
        header('Location: ../payroll-admin.php');
        exit;
    }
} else {
    if ($current_admin_shop_id === null || $current_admin_shop_id <= 0) {
        $_SESSION['error'] = 'No shop assigned to this admin';
        header('Location: ../payroll-admin.php');
        exit;
    }
    $scope_shop_id = (int)$current_admin_shop_id;
}

// Validate dates
if(strtotime($period_end) < strtotime($period_start)) {
    $_SESSION['error'] = 'End date must be after start date';
    header('Location: ../payroll-admin.php');
    exit;
}

// Check if payroll already exists for this period
$check_query = "SELECT * FROM mrb_payroll 
                WHERE payroll_period_start = '$period_start' 
                AND payroll_period_end = '$period_end'
                AND shop_id = '{$scope_shop_id}'";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    $_SESSION['error'] = 'Payroll already exists for this period';
    header('Location: ../payroll-admin.php');
    exit;
}

// Get all active employees
$emp_query = "SELECT * FROM mrb_employees WHERE status = 'Active' AND shop_id = '{$scope_shop_id}' ORDER BY emp_id";
$emp_result = mysqli_query($conn, $emp_query);

if(mysqli_num_rows($emp_result) === 0) {
    $_SESSION['error'] = 'No active employees found';
    header('Location: ../payroll-admin.php');
    exit;
}

// Get attendance settings
$settings_query = "SELECT * FROM mrb_attendance_settings WHERE is_active = 1 LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

if(!$settings) {
    $settings = ['working_days_per_month' => 26];
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    $total_gross = 0;
    $total_deductions = 0;
    $total_net = 0;
    $total_employees = 0;
    
    // Array to store employee payroll details
    $payroll_details = [];
    
    // Process each employee
    while($employee = mysqli_fetch_assoc($emp_result)) {
        $emp_id = $employee['emp_id'];
        $monthly_salary = $employee['salary'];
        $daily_rate = $employee['daily_rate'] ?? ($monthly_salary / $settings['working_days_per_month']);
        
        // Get attendance records for this employee in the payroll period
        $attendance_query = "SELECT 
                                COUNT(*) as days_worked,
                                SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as days_late,
                                SUM(late_deduction) as total_late_deductions,
                                SUM(daily_pay) as total_daily_pay,
                                SUM(net_pay) as total_net_pay
                             FROM mrb_attendance 
                             WHERE emp_id = '$emp_id' 
                             AND shop_id = '{$scope_shop_id}'
                             AND work_date BETWEEN '$period_start' AND '$period_end'
                             AND check_out_time IS NOT NULL";
        
        $attendance_result = mysqli_query($conn, $attendance_query);
        $attendance_data = mysqli_fetch_assoc($attendance_result);
        
        $days_worked = $attendance_data['days_worked'] ?? 0;
        $days_late = $attendance_data['days_late'] ?? 0;
        $late_deductions = $attendance_data['total_late_deductions'] ?? 0;
        
        // Calculate pay based on attendance
        if($days_worked > 0) {
            // Attendance-based pay
            $gross_salary = $days_worked * $daily_rate;
            $deductions = $late_deductions;
            $net_salary = $gross_salary - $deductions;
            
            // Store employee payroll detail
            $payroll_details[] = [
                'emp_id' => $emp_id,
                'emp_name' => $employee['emp_first_name'] . ' ' . $employee['emp_last_name'],
                'gross_salary' => $gross_salary,
                'deductions' => $deductions,
                'net_salary' => $net_salary,
                'days_worked' => $days_worked,
                'days_late' => $days_late,
                'total_late_deductions' => $late_deductions,
                'attendance_pay' => $gross_salary
            ];
            
            $total_gross += $gross_salary;
            $total_deductions += $deductions;
            $total_net += $net_salary;
            $total_employees++;
        }
    }
    
    if($total_employees === 0) {
        throw new Exception('No attendance records found for the selected period');
    }
    
    // Insert main payroll record
    $insert_payroll = "INSERT INTO mrb_payroll 
                      (payroll_period_start, payroll_period_end, total_employees, 
                       gross_amount, total_deductions, net_amount, status, processed_date, shop_id) 
                       VALUES 
                      ('$period_start', '$period_end', '$total_employees', 
                       '$total_gross', '$total_deductions', '$total_net', 'Processed', NOW(), '{$scope_shop_id}')";
    
    if(!mysqli_query($conn, $insert_payroll)) {
        throw new Exception('Failed to create payroll record: ' . mysqli_error($conn));
    }
    
    $payroll_id = mysqli_insert_id($conn);
    
    // Insert payroll details for each employee
    foreach($payroll_details as $detail) {
        $insert_detail = "INSERT INTO mrb_payroll_details 
                         (payroll_id, emp_id, gross_salary, deductions, net_salary, 
                          days_worked, days_late, total_late_deductions, attendance_pay, shop_id) 
                          VALUES 
                         ('$payroll_id', '{$detail['emp_id']}', '{$detail['gross_salary']}', 
                          '{$detail['deductions']}', '{$detail['net_salary']}', 
                          '{$detail['days_worked']}', '{$detail['days_late']}', 
                          '{$detail['total_late_deductions']}', '{$detail['attendance_pay']}', '{$scope_shop_id}')";
        
        if(!mysqli_query($conn, $insert_detail)) {
            throw new Exception('Failed to create payroll detail for employee: ' . mysqli_error($conn));
        }
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Log activity
    $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
    $activity_desc = "Payroll processed for period {$period_start} to {$period_end}: {$total_employees} employee(s), Net amount: ₱" . number_format($total_net, 2) . " by {$admin_name}";
    $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
    $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'payroll', NOW())";
    mysqli_query($conn, $log_query);
    
    $_SESSION['success_message'] = "Payroll processed successfully! Total: ₱" . number_format($total_net, 2) . " for {$total_employees} employee(s)";
    header('Location: ../payroll-admin.php');
    
} catch(Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../payroll-admin.php');
}

mysqli_close($conn);
?>

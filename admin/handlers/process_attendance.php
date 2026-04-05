<?php
session_start();
include '../../connection.php';

header('Content-Type: application/json');

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get QR data
if(!isset($_POST['qr_data']) || empty($_POST['qr_data'])) {
    echo json_encode(['success' => false, 'message' => 'No QR data received']);
    exit;
}

$qr_data = $_POST['qr_data'];

// Parse QR data (format: EMP:emp_id:emp_number)
$parts = explode(':', $qr_data);

if(count($parts) !== 3 || $parts[0] !== 'EMP') {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code format']);
    exit;
}

$emp_id = mysqli_real_escape_string($conn, $parts[1]);
$emp_number = mysqli_real_escape_string($conn, $parts[2]);

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

$shop_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $shop_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        echo json_encode(['success' => false, 'message' => 'No shop assigned to this admin']);
        exit;
    }
}

// Verify employee exists and is active
$emp_query = "SELECT * FROM mrb_employees WHERE emp_id = '$emp_id' AND emp_number = '$emp_number'{$shop_scope_condition}";
$emp_result = mysqli_query($conn, $emp_query);

if(mysqli_num_rows($emp_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

$employee = mysqli_fetch_assoc($emp_result);

if($employee['status'] !== 'Active') {
    echo json_encode(['success' => false, 'message' => 'Employee is not active']);
    exit;
}

// Get attendance settings
$settings_query = "SELECT * FROM mrb_attendance_settings WHERE is_active = 1 LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);

// Use default values if settings not found
if(!$settings) {
    $settings = [
        'work_start_time' => '08:00:00',
        'late_threshold_minutes' => 15,
        'deduction_per_minute' => 10.00,
        'fixed_late_deduction' => 50.00,
        'deduction_type' => 'fixed',
        'working_days_per_month' => 26
    ];
}

// Calculate daily rate if not set
$daily_rate = $employee['daily_rate'] ?? ($employee['salary'] / $settings['working_days_per_month']);

// Calculate daily rate if not set
$daily_rate = $employee['daily_rate'] ?? ($employee['salary'] / $settings['working_days_per_month']);

// Check if employee already has attendance for today
$today = date('Y-m-d');
$check_query = "SELECT * FROM mrb_attendance 
                WHERE emp_id = '$emp_id' 
                AND work_date = '$today'{$shop_scope_condition}";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    // Employee already checked in, now checking out
    $attendance = mysqli_fetch_assoc($check_result);
    
    if($attendance['check_out_time']) {
        echo json_encode(['success' => false, 'message' => 'Already checked out for today']);
        exit;
    }
    
    // Check if at least 15 minutes have passed since check-in
    $check_in_time = strtotime($attendance['check_in_time']);
    $current_time = time();
    $time_diff_minutes = ($current_time - $check_in_time) / 60;
    
    if($time_diff_minutes < 15) {
        $remaining_minutes = ceil(15 - $time_diff_minutes);
        echo json_encode([
            'success' => false, 
            'message' => "Please wait {$remaining_minutes} more minute(s) before checking out. Minimum work time is 15 minutes."
        ]);
        exit;
    }
    
    // Update check-out time and calculate final pay
    $checkout_time = date('Y-m-d H:i:s');
    
    // Calculate net pay (daily pay - late deduction)
    $net_pay = $attendance['daily_pay'] - $attendance['late_deduction'];
    
    $update_query = "UPDATE mrb_attendance 
                     SET check_out_time = '$checkout_time',
                         net_pay = '$net_pay'
                     WHERE attendance_id = '{$attendance['attendance_id']}'";
    
    if(mysqli_query($conn, $update_query)) {
        $late_msg = $attendance['is_late'] ? " (Late deduction: ₱" . number_format($attendance['late_deduction'], 2) . ")" : "";
        echo json_encode([
            'success' => true, 
            'message' => "Check-out successful! {$employee['emp_first_name']} {$employee['emp_last_name']} - " . 
                        date('h:i A', strtotime($checkout_time)) . 
                        $late_msg
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    // New check-in - Calculate if late
    $checkin_time = date('Y-m-d H:i:s');
    $checkin_time_only = date('H:i:s', strtotime($checkin_time));
    $work_start_time = $settings['work_start_time'];
    
    // Convert times to timestamps for comparison
    $checkin_timestamp = strtotime($today . ' ' . $checkin_time_only);
    $start_timestamp = strtotime($today . ' ' . $work_start_time);
    
    $is_late = 0;
    $minutes_late = 0;
    $late_deduction = 0.00;
    $status = 'Present';
    
    // Calculate if late (beyond threshold)
    if($checkin_timestamp > ($start_timestamp + ($settings['late_threshold_minutes'] * 60))) {
        $is_late = 1;
        $minutes_late = floor(($checkin_timestamp - $start_timestamp) / 60);
        $status = 'Late';
        
        // Calculate deduction based on settings
        if($settings['deduction_type'] === 'per_minute') {
            $late_deduction = $minutes_late * $settings['deduction_per_minute'];
        } else {
            $late_deduction = $settings['fixed_late_deduction'];
        }
        
        // Ensure deduction doesn't exceed daily pay
        if($late_deduction > $daily_rate) {
            $late_deduction = $daily_rate;
        }
    }
    
    $insert_query = "INSERT INTO mrb_attendance 
                    (emp_id, check_in_time, work_date, is_late, minutes_late, 
                     late_deduction, daily_pay, status, shop_id) 
                     VALUES 
                    ('$emp_id', '$checkin_time', '$today', '$is_late', '$minutes_late', 
                     '$late_deduction', '$daily_rate', '$status', '{$employee['shop_id']}')";
    
    if(mysqli_query($conn, $insert_query)) {
        $late_msg = $is_late ? " ⚠️ Late by {$minutes_late} minutes. Deduction: ₱" . number_format($late_deduction, 2) : " ✓ On time!";
        echo json_encode([
            'success' => true, 
            'message' => "Check-in successful! Welcome {$employee['emp_first_name']} {$employee['emp_last_name']} - " . 
                        date('h:i A', strtotime($checkin_time)) . 
                        $late_msg
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
}
?>

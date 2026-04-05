<?php
session_start();
include '../../connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../mrbloginpage.php");
    exit;
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

$payroll_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $payroll_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        $_SESSION['error_message'] = "No shop assigned. Cannot review payroll.";
        header("Location: ../finances-admin.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payroll_id = mysqli_real_escape_string($conn, $_POST['payroll_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $rejection_reason = mysqli_real_escape_string($conn, $_POST['rejection_reason'] ?? '');
    $admin_id = $_SESSION['user_id'];

    if ($action === 'approve') {
        // Get payroll info before approving
        $get_payroll = mysqli_query($conn, "SELECT payroll_period_start, payroll_period_end, net_amount FROM mrb_payroll WHERE payroll_id = '$payroll_id'{$payroll_scope_condition}");
        if (!$get_payroll || mysqli_num_rows($get_payroll) === 0) {
            $_SESSION['error_message'] = "Payroll not found for your shop.";
            header("Location: ../finances-admin.php");
            exit;
        }
        $payroll_info = mysqli_fetch_assoc($get_payroll);

        $query = "UPDATE mrb_payroll 
                  SET finance_status = 'Approved', 
                      finance_reviewed_by = '$admin_id', 
                      finance_reviewed_date = NOW() 
                  WHERE payroll_id = '$payroll_id'{$payroll_scope_condition}";
        
        if (mysqli_query($conn, $query)) {
            // Log activity
            $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
            $formatted_amount = number_format($payroll_info['net_amount'], 2);
            $activity_desc = mysqli_real_escape_string($conn, "Payroll for period {$payroll_info['payroll_period_start']} to {$payroll_info['payroll_period_end']} (Amount: ₱{$formatted_amount}) was approved by {$admin_name}");
            $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'finance', NOW())";
            mysqli_query($conn, $activity_log);

            $_SESSION['success_message'] = "Payroll approved successfully!";
        } else {
            $_SESSION['error_message'] = "Error approving payroll: " . mysqli_error($conn);
        }
    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $_SESSION['error_message'] = "Please provide a rejection reason.";
        } else {
            // Get payroll info before rejecting
            $get_payroll = mysqli_query($conn, "SELECT payroll_period_start, payroll_period_end, net_amount FROM mrb_payroll WHERE payroll_id = '$payroll_id'{$payroll_scope_condition}");
            if (!$get_payroll || mysqli_num_rows($get_payroll) === 0) {
                $_SESSION['error_message'] = "Payroll not found for your shop.";
                header("Location: ../finances-admin.php");
                exit;
            }
            $payroll_info = mysqli_fetch_assoc($get_payroll);

            $query = "UPDATE mrb_payroll 
                      SET finance_status = 'Rejected', 
                          finance_rejection_reason = '$rejection_reason',
                          finance_reviewed_by = '$admin_id', 
                          finance_reviewed_date = NOW() 
                      WHERE payroll_id = '$payroll_id'{$payroll_scope_condition}";
            
            if (mysqli_query($conn, $query)) {
                // Log activity
                $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
                $formatted_amount = number_format($payroll_info['net_amount'], 2);
                $activity_desc = mysqli_real_escape_string($conn, "Payroll for period {$payroll_info['payroll_period_start']} to {$payroll_info['payroll_period_end']} (Amount: ₱{$formatted_amount}) was rejected by {$admin_name}");
                $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'finance', NOW())";
                mysqli_query($conn, $activity_log);

                $_SESSION['success_message'] = "Payroll rejected successfully!";
            } else {
                $_SESSION['error_message'] = "Error rejecting payroll: " . mysqli_error($conn);
            }
        }
    }
}

header("Location: ../finances-admin.php");
exit;
?>

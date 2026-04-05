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

$po_scope_condition = "";
$po_update_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $po_scope_condition = " AND po.shop_id = {$current_admin_shop_id}";
        $po_update_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        $_SESSION['error_message'] = "No shop assigned. Cannot review purchase orders.";
        header("Location: ../finances-admin.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $po_id = mysqli_real_escape_string($conn, $_POST['po_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $rejection_reason = mysqli_real_escape_string($conn, $_POST['rejection_reason'] ?? '');
    $admin_id = $_SESSION['user_id'];

    if ($action === 'approve') {
        // Get PO info before approving
        $get_po = mysqli_query($conn, "SELECT po.po_number, s.company_name, s.supplier_number FROM mrb_purchase_orders po JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = '$po_id'{$po_scope_condition}");
        if (!$get_po || mysqli_num_rows($get_po) === 0) {
            $_SESSION['error_message'] = "Purchase order not found for your shop.";
            header("Location: ../finances-admin.php");
            exit;
        }
        $po_info = mysqli_fetch_assoc($get_po);

        $query = "UPDATE mrb_purchase_orders 
                  SET finance_status = 'Approved', 
                      finance_reviewed_by = '$admin_id', 
                      finance_reviewed_date = NOW() 
                  WHERE po_id = '$po_id'{$po_update_scope_condition}";
        
        if (mysqli_query($conn, $query)) {
            // Log activity
            $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
            $activity_desc = mysqli_real_escape_string($conn, "Purchase Order {$po_info['po_number']} for supplier '{$po_info['company_name']}' ({$po_info['supplier_number']}) was approved by {$admin_name}");
            $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'finance', NOW())";
            mysqli_query($conn, $activity_log);

            $_SESSION['success_message'] = "Purchase order approved successfully!";
        } else {
            $_SESSION['error_message'] = "Error approving purchase order: " . mysqli_error($conn);
        }
    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $_SESSION['error_message'] = "Please provide a rejection reason.";
        } else {
            // Get PO info before rejecting
            $get_po = mysqli_query($conn, "SELECT po.po_number, s.company_name, s.supplier_number FROM mrb_purchase_orders po JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id WHERE po.po_id = '$po_id'{$po_scope_condition}");
            if (!$get_po || mysqli_num_rows($get_po) === 0) {
                $_SESSION['error_message'] = "Purchase order not found for your shop.";
                header("Location: ../finances-admin.php");
                exit;
            }
            $po_info = mysqli_fetch_assoc($get_po);

            $query = "UPDATE mrb_purchase_orders 
                      SET finance_status = 'Rejected', 
                          finance_rejection_reason = '$rejection_reason',
                          finance_reviewed_by = '$admin_id', 
                          finance_reviewed_date = NOW() 
                      WHERE po_id = '$po_id'{$po_update_scope_condition}";
            
            if (mysqli_query($conn, $query)) {
                // Log activity
                $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
                $activity_desc = mysqli_real_escape_string($conn, "Purchase Order {$po_info['po_number']} for supplier '{$po_info['company_name']}' ({$po_info['supplier_number']}) was rejected by {$admin_name}");
                $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'finance', NOW())";
                mysqli_query($conn, $activity_log);

                $_SESSION['success_message'] = "Purchase order rejected successfully!";
            } else {
                $_SESSION['error_message'] = "Error rejecting purchase order: " . mysqli_error($conn);
            }
        }
    }
}

header("Location: ../finances-admin.php");
exit;
?>

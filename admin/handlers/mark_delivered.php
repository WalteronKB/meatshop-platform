<?php
session_start();
include("../../connection.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    if (!$is_super_admin) {
        if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
            $po_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        } else {
            $_SESSION['error_message'] = 'No shop assigned. Cannot update purchase order.';
            header("location:../suppliers-admin.php");
            exit();
        }
    }

    $po_id = mysqli_real_escape_string($conn, $_POST['po_id']);
    $actual_delivery_date = date('Y-m-d'); // Today's date

    // Update PO with delivery date and status
    $update_query = "UPDATE mrb_purchase_orders SET 
                    status = 'Delivered',
                    actual_delivery_date = '$actual_delivery_date'
                    WHERE po_id = '$po_id'{$po_scope_condition}";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "Purchase order marked as delivered!";
        header("location:../suppliers-admin.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating purchase order: " . mysqli_error($conn);
        header("location:../suppliers-admin.php");
        exit();
    }
} else {
    header("location:../suppliers-admin.php");
    exit();
}
?>

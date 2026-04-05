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

    $supplier_scope_condition = "";
    if (!$is_super_admin) {
        if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
            $supplier_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        } else {
            $_SESSION['error_message'] = 'No shop assigned. Cannot restore supplier.';
            header('Location: ../suppliers-admin.php');
            exit;
        }
    }

    // Get supplier ID
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);

    // Get supplier info before restoring
    $get_supplier = mysqli_query($conn, "SELECT company_name, supplier_number FROM mrb_suppliers WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}");
    $supplier_info = mysqli_fetch_assoc($get_supplier);
    if (!$supplier_info) {
        $_SESSION['error_message'] = "Supplier not found for your shop.";
        header("location:../suppliers-admin.php");
        exit();
    }

    // Restore supplier (update status back to 'Active')
    $restore_query = "UPDATE mrb_suppliers SET status = 'Active', updated_at = CURRENT_TIMESTAMP WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}";

    if (mysqli_query($conn, $restore_query)) {
        // Log activity
        $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
        $activity_desc = mysqli_real_escape_string($conn, "Supplier '{$supplier_info['company_name']}' ({$supplier_info['supplier_number']}) was restored by {$admin_name}");
        $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'suppliers', NOW())";
        mysqli_query($conn, $activity_log);

        $_SESSION['success_message'] = "Supplier restored successfully!";
        header("location:../suppliers-admin.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error restoring supplier: " . mysqli_error($conn);
        header("location:../suppliers-admin.php");
        exit();
    }
} else {
    header("location:../suppliers-admin.php");
    exit();
}
?>

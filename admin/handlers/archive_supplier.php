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
            $_SESSION['error_message'] = 'No shop assigned. Cannot archive supplier.';
            header('Location: ../suppliers-admin.php');
            exit;
        }
    }

    // Get supplier ID
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);

    // Get supplier info before archiving
    $get_query = "SELECT supplier_number, company_name FROM mrb_suppliers WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}";
    $get_result = mysqli_query($conn, $get_query);
    $supplier = mysqli_fetch_assoc($get_result);

    // Archive supplier (update status to 'Archived')
    $archive_query = "UPDATE mrb_suppliers SET status = 'Archived', updated_at = CURRENT_TIMESTAMP WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}";

    if (mysqli_query($conn, $archive_query)) {
        // Log activity
        if ($supplier) {
            $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
            $activity_desc = "Supplier '{$supplier['company_name']}' ({$supplier['supplier_number']}) was archived by {$admin_name}";
            $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
            $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'suppliers', NOW())";
            mysqli_query($conn, $log_query);
        }
        $_SESSION['success_message'] = "Supplier archived successfully!";
        header("location:../suppliers-admin.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error archiving supplier: " . mysqli_error($conn);
        header("location:../suppliers-admin.php");
        exit();
    }
} else {
    header("location:../suppliers-admin.php");
    exit();
}
?>

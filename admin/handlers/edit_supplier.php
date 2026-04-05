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
            $_SESSION['error_message'] = 'No shop assigned. Cannot edit supplier.';
            header('Location: ../suppliers-admin.php');
            exit;
        }
    }

    // Get form data
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $product_category = mysqli_real_escape_string($conn, $_POST['product_category']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Get supplier info before updating
    $get_query = "SELECT supplier_number, company_name FROM mrb_suppliers WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}";
    $get_result = mysqli_query($conn, $get_query);
    $old_supplier = mysqli_fetch_assoc($get_result);

    // Update supplier in database
    $update_query = "UPDATE mrb_suppliers SET 
                    company_name = '$company_name',
                    contact_person = '$contact_person',
                    product_category = '$product_category',
                    email = '$email',
                    phone = '$phone',
                    address = '$address'
                    WHERE supplier_id = '$supplier_id'{$supplier_scope_condition}";

    if (mysqli_query($conn, $update_query)) {
        // Log activity
        if ($old_supplier) {
            $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
            $supplier_number = $old_supplier['supplier_number'];
            $activity_desc = "Supplier '{$company_name}' ({$supplier_number}) was updated by {$admin_name}";
            $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
            $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'suppliers', NOW())";
            mysqli_query($conn, $log_query);
        }
        
        $_SESSION['success_message'] = "Supplier updated successfully!";
        header("location:../suppliers-admin.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating supplier: " . mysqli_error($conn);
        header("location:../suppliers-admin.php");
        exit();
    }
} else {
    header("location:../suppliers-admin.php");
    exit();
}
?>

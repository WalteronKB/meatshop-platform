<?php
session_start();
include('../../connection.php');

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

    $target_shop_id = $is_super_admin ? (int)($_POST['shop_id'] ?? 0) : (int)$current_admin_shop_id;
    if ($is_super_admin && $target_shop_id <= 0 && $current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $target_shop_id = (int)$current_admin_shop_id;
    }
    if ($target_shop_id <= 0) {
        $_SESSION['error_message'] = 'No shop assigned. Cannot add supplier.';
        header('Location: ../suppliers-admin.php');
        exit;
    }

    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $product_category = mysqli_real_escape_string($conn, $_POST['product_category']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $status = 'Active';

    // Generate supplier number
    $sup_num_query = "SELECT supplier_number FROM mrb_suppliers ORDER BY supplier_id DESC LIMIT 1";
    $sup_num_result = mysqli_query($conn, $sup_num_query);
    $last_sup = mysqli_fetch_assoc($sup_num_result);
    $last_sup_num = isset($last_sup['supplier_number']) ? intval(substr($last_sup['supplier_number'], 3)) : 0;
    $new_sup_num = 'SUP' . str_pad($last_sup_num + 1, 3, '0', STR_PAD_LEFT);

    $insert_query = "INSERT INTO mrb_suppliers 
                    (supplier_number, company_name, contact_person, product_category, email, phone, address, status, shop_id)
                    VALUES ('$new_sup_num', '$company_name', '$contact_person', '$product_category', '$email', '$contact_number', '$address', '$status', '{$target_shop_id}')";

    if (mysqli_query($conn, $insert_query)) {
        // Log activity
        $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
        $activity_desc = "Supplier '{$company_name}' ({$new_sup_num}) was added by {$admin_name}";
        $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
        $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'suppliers', NOW())";
        mysqli_query($conn, $log_query);
        
        $_SESSION['success_message'] = 'Supplier added successfully!';
        header('Location: ../suppliers-admin.php');
    } else {
        $_SESSION['error_message'] = 'Error adding supplier: ' . mysqli_error($conn);
        header('Location: ../suppliers-admin.php');
    }
} else {
    header('Location: ../suppliers-admin.php');
}
?>

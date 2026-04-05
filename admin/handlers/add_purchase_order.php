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
            $_SESSION['error_message'] = 'No shop assigned. Cannot create purchase order.';
            header('Location: ../suppliers-admin.php');
            exit;
        }
    }

    // Get form data
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);
    $item_description = mysqli_real_escape_string($conn, $_POST['item_description']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    $unit_price = mysqli_real_escape_string($conn, $_POST['unit_price']);
    $delivery_date = mysqli_real_escape_string($conn, $_POST['delivery_date']);
    $total_amount = floatval($unit_price) * floatval(preg_replace('/[^0-9]/', '', $quantity));

    // Generate PO Number (PO-YYYYMMDD-XXXXX)
    $po_number = "PO-" . date("Ymd") . "-" . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Get supplier info for logging
    $supplier_query = "SELECT supplier_number, company_name, shop_id FROM mrb_suppliers WHERE supplier_id = '$supplier_id'{$po_scope_condition}";
    $supplier_result = mysqli_query($conn, $supplier_query);
    $supplier = mysqli_fetch_assoc($supplier_result);
    if (!$supplier) {
        $_SESSION['error_message'] = 'Supplier not found for your shop.';
        header('Location: ../suppliers-admin.php');
        exit;
    }

    $po_shop_id = (int)($supplier['shop_id'] ?? 0);
    if ($po_shop_id <= 0) {
        $_SESSION['error_message'] = 'Supplier has no shop assigned. Please update supplier first.';
        header('Location: ../suppliers-admin.php');
        exit;
    }

    // Insert purchase order into database
    $insert_query = "INSERT INTO mrb_purchase_orders (po_number, supplier_id, item_description, quantity, unit_price, total_amount, expected_delivery_date, status, shop_id)
                    VALUES ('$po_number', '$supplier_id', '$item_description', '$quantity', '$unit_price', '$total_amount', '$delivery_date', 'Pending', '{$po_shop_id}')";

    if (mysqli_query($conn, $insert_query)) {
        // Log activity
        if ($supplier) {
            $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
            $activity_desc = "Purchase Order {$po_number} created for supplier '{$supplier['company_name']}' ({$supplier['supplier_number']}): {$item_description}, Amount: ₱" . number_format($total_amount, 2) . " by {$admin_name}";
            $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
            $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'suppliers', NOW())";
            mysqli_query($conn, $log_query);
        }
        
        $_SESSION['success_message'] = "Purchase order created successfully! PO#: $po_number";
        header("location:../suppliers-admin.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error creating purchase order: " . mysqli_error($conn);
        header("location:../suppliers-admin.php");
        exit();
    }
} else {
    header("location:../suppliers-admin.php");
    exit();
}
?>

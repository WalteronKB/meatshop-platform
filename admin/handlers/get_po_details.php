<?php
session_start();
include("../../connection.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['po_id'])) {
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
            $po_scope_condition = " AND po.shop_id = {$current_admin_shop_id}";
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No shop assigned']);
            exit;
        }
    }

    $po_id = mysqli_real_escape_string($conn, $_GET['po_id']);
    
    $query = "SELECT po.*, s.company_name FROM mrb_purchase_orders po 
              LEFT JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id 
              WHERE po.po_id = '$po_id'{$po_scope_condition}";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $po = mysqli_fetch_assoc($result);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'po' => $po]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Purchase order not found']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>

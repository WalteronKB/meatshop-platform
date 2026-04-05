<?php
session_start();
include("../../connection.php");

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get supplier ID
    $supplier_id = mysqli_real_escape_string($conn, $_POST['supplier_id']);

    // Get supplier info before archiving
    $get_supplier = mysqli_query($conn, "SELECT company_name, supplier_number FROM mrb_suppliers WHERE supplier_id = '$supplier_id'");
    $supplier_info = mysqli_fetch_assoc($get_supplier);

    // Archive supplier instead of deleting (update status to 'Archived')
    $archive_query = "UPDATE mrb_suppliers SET status = 'Archived' WHERE supplier_id = '$supplier_id'";

    if (mysqli_query($conn, $archive_query)) {
        // Log activity
        $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
        $activity_desc = mysqli_real_escape_string($conn, "Supplier '{$supplier_info['company_name']}' ({$supplier_info['supplier_number']}) was archived by {$admin_name}");
        $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'suppliers', NOW())";
        mysqli_query($conn, $activity_log);

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

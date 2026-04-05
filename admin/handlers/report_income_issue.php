<?php
session_start();
include '../../connection.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../../mrbloginpage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $income_source = mysqli_real_escape_string($conn, $_POST['income_source']);
    $reported_amount = mysqli_real_escape_string($conn, $_POST['reported_amount']);
    $expected_amount = mysqli_real_escape_string($conn, $_POST['expected_amount']);
    $issue_description = mysqli_real_escape_string($conn, $_POST['issue_description']);
    $admin_id = $_SESSION['user_id'];

    $query = "INSERT INTO mrb_income_issues 
              (income_source, reported_amount, expected_amount, issue_description, reported_by) 
              VALUES ('$income_source', '$reported_amount', '$expected_amount', '$issue_description', '$admin_id')";
    
    if (mysqli_query($conn, $query)) {
        // Log activity
        $admin_name = mysqli_real_escape_string($conn, $_SESSION['user_name']);
        $formatted_reported = number_format($reported_amount, 2);
        $formatted_expected = number_format($expected_amount, 2);
        $activity_desc = mysqli_real_escape_string($conn, "Income issue reported for '{$income_source}' - Reported: ₱{$formatted_reported}, Expected: ₱{$formatted_expected} by {$admin_name}");
        $activity_log = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc', 'finance', NOW())";
        mysqli_query($conn, $activity_log);

        $_SESSION['success_message'] = "Income issue reported successfully!";
    } else {
        $_SESSION['error_message'] = "Error reporting issue: " . mysqli_error($conn);
    }
}

header("Location: ../finances-admin.php");
exit;
?>

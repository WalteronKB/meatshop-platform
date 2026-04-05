<?php
session_start();
include('../connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bank_name = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $account_number = mysqli_real_escape_string($conn, $_POST['account_number']);
    $account_name = mysqli_real_escape_string($conn, $_POST['account_name']);
    $balance = floatval($_POST['balance']);
    $status = 'Active';

    $insert_query = "INSERT INTO mrb_bank_accounts 
                    (bank_name, account_number, account_name, balance, status)
                    VALUES ('$bank_name', '$account_number', '$account_name', $balance, '$status')";

    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = 'Bank account added successfully!';
        header('Location: ../finances-admin.php');
    } else {
        $_SESSION['error_message'] = 'Error adding bank account: ' . mysqli_error($conn);
        header('Location: ../finances-admin.php');
    }
} else {
    header('Location: ../finances-admin.php');
}
?>

<?php
session_start();
include('../connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $amount = floatval($_POST['amount']);
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $insert_query = "INSERT INTO mrb_expenses 
                    (category, amount, expense_date, notes)
                    VALUES ('$category', $amount, '$expense_date', '$notes')";

    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = 'Expense recorded successfully!';
        header('Location: ../finances-admin.php');
    } else {
        $_SESSION['error_message'] = 'Error recording expense: ' . mysqli_error($conn);
        header('Location: ../finances-admin.php');
    }
} else {
    header('Location: ../finances-admin.php');
}
?>

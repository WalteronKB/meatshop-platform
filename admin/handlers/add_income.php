<?php
session_start();
include('../connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $income_source = mysqli_real_escape_string($conn, $_POST['income_source']);
    $amount = floatval($_POST['amount']);
    $income_date = mysqli_real_escape_string($conn, $_POST['income_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    $insert_query = "INSERT INTO mrb_income 
                    (income_source, amount, income_date, notes)
                    VALUES ('$income_source', $amount, '$income_date', '$notes')";

    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['success_message'] = 'Income recorded successfully!';
        header('Location: ../finances-admin.php');
    } else {
        $_SESSION['error_message'] = 'Error recording income: ' . mysqli_error($conn);
        header('Location: ../finances-admin.php');
    }
} else {
    header('Location: ../finances-admin.php');
}
?>

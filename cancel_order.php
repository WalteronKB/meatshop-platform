<?php

    $order_id = $_GET['order_id'];
    session_start();

    include 'connection.php';

    $query = "UPDATE mrb_orders SET order_status = 'Cancelled' WHERE order_id = '$order_id'";
    $result = mysqli_query($conn, $query);
    if ($result) {
        unset($_SESSION['order_id']);
        
        header("Location: userorders.php?order_sort=All&message=Order cancelled successfully");
    } else {
        header("Location: userorders.php?order_sort=All&error=Failed to cancel order");
    }


?>
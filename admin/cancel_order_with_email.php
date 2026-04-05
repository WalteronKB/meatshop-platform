<?php
include '../connection.php';
function cancelOrderWithEmail($order_id) {
    global $conn;
    
    try {
        // Get order details including customer email
        $query = "SELECT mrb_orders.order_id,
                        mrb_orders.order_status,
                        mrb_users.user_email,
                        mrb_users.user_name,
                        mrb_fireex.prod_name
                 FROM mrb_orders
                 JOIN mrb_users ON mrb_orders.user_id = mrb_users.user_id
                 JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
                 WHERE mrb_orders.order_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $order_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            return ['success' => false, 'message' => 'Failed to retrieve order details'];
        }
        
        $result = mysqli_stmt_get_result($stmt);
        $order_data = mysqli_fetch_assoc($result);
        
        if (!$order_data) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        // Check if order can be cancelled
        if ($order_data['order_status'] === 'cancelled') {
            return ['success' => false, 'message' => 'Order is already cancelled'];
        }
        
        if ($order_data['order_status'] === 'delivered') {
            return ['success' => false, 'message' => 'Cannot cancel delivered order'];
        }
        
        // Update order status to cancelled
        $update_query = "UPDATE mrb_orders SET order_status = 'cancelled' WHERE order_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "i", $order_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            return ['success' => false, 'message' => 'Failed to update order status'];
        }
        
        // Prepare email data
        $email_data = [
            'customer_email' => $order_data['user_email'],
            'customer_name' => $order_data['user_name'],
            'product_name' => $order_data['prod_name'],
            'order_id' => $order_data['order_id'],
            'cancellation_message' => "Your {$order_data['prod_name']} order is cancelled"
        ];
        
        return [
            'success' => true, 
            'message' => 'Order cancelled successfully',
            'email_data' => $email_data
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

// Only handle direct requests when accessed as standalone file
if(basename($_SERVER['SCRIPT_NAME']) === 'cancel_order_with_email.php') {
    // Start session only for standalone access
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Handle AJAX request for order cancellation
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
        header('Content-Type: application/json');
        
        $order_id = intval($_POST['cancel_order_id']);
        $result = cancelOrderWithEmail($order_id);
        
        echo json_encode($result);
        exit;
    }

    // Handle GET request (for direct cancellation from user orders page)
    if(isset($_GET['order_id'])) {
        $order_id = intval($_GET['order_id']);
        $result = cancelOrderWithEmail($order_id);
        
        if($result['success']) {
            // Redirect with success message and email data for frontend to handle
            $email_data = urlencode(json_encode($result['email_data']));
            header("Location: ../userorders.php?order_sort=All&message=Order cancelled successfully&email_data=" . $email_data);
        } else {
            header("Location: ../userorders.php?order_sort=All&error=" . urlencode($result['message']));
        }
        exit;
    }
}
?>
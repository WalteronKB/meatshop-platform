<?php
// Suppress all PHP errors/warnings to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

include 'connection.php';
session_start();

// Clear any previous output
ob_clean();

// Set content type for JSON response
header('Content-Type: application/json');

function send_json_response($payload, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
    exit;
}

set_exception_handler(function ($e) {
    send_json_response([
        'success' => false,
        'message' => 'Server error while processing order. Please try again.'
    ], 200);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(200);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server error while processing order. Please try again.'
        ]);
    }
});

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_json_response([
        'success' => false, 
        'message' => 'User not logged in. Please log in to place an order.'
    ]);
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response([
        'success' => false, 
        'message' => 'Invalid request method.'
    ]);
}

// Get POST data
$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$location = isset($_POST['location']) ? mysqli_real_escape_string($conn, $_POST['location']) : '';
$payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : 'cash';

// Get meat-specific fields
$cut_preference = isset($_POST['cut_preference']) ? mysqli_real_escape_string($conn, $_POST['cut_preference']) : '';
$delivery_date = isset($_POST['delivery_date']) ? mysqli_real_escape_string($conn, $_POST['delivery_date']) : '';
$preferred_weight = isset($_POST['preferred_weight']) ? floatval($_POST['preferred_weight']) : 0;
$processing_options = isset($_POST['processing_options']) ? mysqli_real_escape_string($conn, $_POST['processing_options']) : '';
$special_instructions = isset($_POST['special_instructions']) ? mysqli_real_escape_string($conn, $_POST['special_instructions']) : '';

// Get reference number from session if it exists (for GCash payments)
$reference_number = '';
if ($payment_method === 'gcash') {
    $session_reference = isset($_SESSION['gcash_reference']) ? trim((string)$_SESSION['gcash_reference']) : '';
    $session_transaction = isset($_SESSION['gcash_transaction_id']) ? trim((string)$_SESSION['gcash_transaction_id']) : '';

    if ($session_reference === '' || $session_transaction === '') {
        send_json_response([
            'success' => false,
            'message' => 'Missing PayMongo payment reference. Please start GCash checkout again and complete the payment first.'
        ]);
    }

    $reference_number = mysqli_real_escape_string($conn, $session_transaction);
}

// Validate input data
if ($product_id <= 0) {
    send_json_response([
        'success' => false, 
        'message' => 'Invalid product ID.'
    ]);
}

if ($quantity <= 0) {
    send_json_response([
        'success' => false, 
        'message' => 'Invalid quantity.'
    ]);
}

if (empty($location)) {
    send_json_response([
        'success' => false, 
        'message' => 'Please select a delivery location.'
    ]);
}

// Validate meat-specific required fields
if (empty($cut_preference)) {
    send_json_response([
        'success' => false, 
        'message' => 'Please select a cut preference.'
    ]);
}

if (empty($delivery_date)) {
    send_json_response([
        'success' => false, 
        'message' => 'Please select a delivery date.'
    ]);
}

// Validate delivery date is not in the past
$selected_date = strtotime($delivery_date);
$tomorrow = strtotime('+1 day', strtotime(date('Y-m-d')));
if ($selected_date < $tomorrow) {
    send_json_response([
        'success' => false, 
        'message' => 'Delivery date must be at least 1 day in advance.'
    ]);
}

// Check if product exists and get stock quantity + shop context
$product_check_query = "SELECT prod_quantity, prod_name, shop_id FROM mrb_fireex WHERE prod_id = $product_id";
$product_result = mysqli_query($conn, $product_check_query);

if (!$product_result || mysqli_num_rows($product_result) == 0) {
    send_json_response([
        'success' => false, 
        'message' => 'Product not found.'
    ]);
}

$product_data = mysqli_fetch_assoc($product_result);
$available_stock = $product_data['prod_quantity'];
$product_name = $product_data['prod_name'];
$shop_id = isset($product_data['shop_id']) ? intval($product_data['shop_id']) : 0;

if ($shop_id <= 0) {
    send_json_response([
        'success' => false,
        'message' => 'Unable to determine shop for this product.'
    ]);
}

// Check if requested quantity is available
if ($quantity > $available_stock) {
    send_json_response([
        'success' => false, 
        'message' => "Only $available_stock items available in stock."
    ]);
}

// Idempotency guard for GCash: avoid duplicate orders when duplicate requests arrive.
if ($payment_method === 'gcash' && $reference_number !== '') {
    $duplicate_query = "SELECT order_id FROM mrb_orders WHERE user_id = '$user_id' AND order_paymentmethod = 'gcash' AND gcash_referencenum = '$reference_number' LIMIT 1";
    try {
        $duplicate_result = mysqli_query($conn, $duplicate_query);
    } catch (Throwable $e) {
        send_json_response([
            'success' => false,
            'message' => 'Unable to validate duplicate GCash payment right now. Please try again.'
        ]);
    }
    if ($duplicate_result && mysqli_num_rows($duplicate_result) > 0) {
        $existing_order = mysqli_fetch_assoc($duplicate_result);
        unset($_SESSION['gcash_reference']);
        unset($_SESSION['gcash_transaction_id']);
        unset($_SESSION['paymongo_pending_order']);
        unset($_SESSION['paymongo_ready_to_place_order']);
        unset($_SESSION['paymongo_source_id']);
        send_json_response([
            'success' => true,
            'message' => 'GCash payment already recorded. Using existing order.',
            'order_id' => (int)$existing_order['order_id'],
            'product_name' => $product_name,
            'quantity' => $quantity,
            'location' => $location
        ]);
    }
}

// Get the next order ID
$order_id_query = "SELECT COALESCE(MAX(order_id), 0) + 1 as next_id FROM mrb_orders";
$order_id_result = mysqli_query($conn, $order_id_query);
if ($order_id_result) {
    $order_id_row = mysqli_fetch_assoc($order_id_result);
    $next_order_id = $order_id_row['next_id'];
} else {
    $next_order_id = 1;
}

// Insert order into database
$order_date = date('Y-m-d H:i:s');
$order_status = 'Pending';

$insert_query = "INSERT INTO mrb_orders (
    order_id, user_id, product_id, order_quantity, order_dateordered, order_status, 
    order_location, order_paymentmethod, gcash_referencenum, 
    cut_preference, delivery_date, preferred_weight, processing_options, special_instructions,
    seen_byuser, seen_byadmin, shop_id
) VALUES (
    '$next_order_id', '$user_id', '$product_id', '$quantity', '$order_date', '$order_status', 
    '$location', '$payment_method', '$reference_number',
    '$cut_preference', '$delivery_date', '$preferred_weight', '$processing_options', '$special_instructions',
    'false', 'false', '$shop_id'
)";

if (mysqli_query($conn, $insert_query)) {
    // Update product quantity
    $new_quantity = $available_stock - $quantity;
    $update_query = "UPDATE mrb_fireex SET prod_quantity = '$new_quantity' WHERE prod_id = '$product_id'";
    
    if (mysqli_query($conn, $update_query)) {
        if ($payment_method === 'gcash') {
            unset($_SESSION['gcash_reference']);
            unset($_SESSION['gcash_transaction_id']);
            unset($_SESSION['paymongo_pending_order']);
            unset($_SESSION['paymongo_ready_to_place_order']);
            unset($_SESSION['paymongo_source_id']);
        }
        send_json_response([
            'success' => true, 
            'message' => "Order placed successfully! Order ID: #$next_order_id",
            'order_id' => $next_order_id,
            'product_name' => $product_name,
            'quantity' => $quantity,
            'location' => $location
        ]);
    } else {
        send_json_response([
            'success' => false, 
            'message' => 'Order placed but failed to update stock quantity.'
        ]);
    }
} else {
    send_json_response([
        'success' => false, 
        'message' => 'Failed to place order. Please try again.'
    ]);
}

mysqli_close($conn);
?>
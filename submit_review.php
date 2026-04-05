<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if this is a review submission
if (isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    
    $user_id = $_SESSION['user_id'];
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $rating = intval($_POST['rating']);
    $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
    
    // Validate inputs
    if (empty($order_id) || $rating < 1 || $rating > 5 || empty($review_text)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    // Verify that the order belongs to the logged-in user and is delivered
    $verify_query = "SELECT mrb_orders.order_id, mrb_orders.user_id, mrb_orders.order_status, mrb_orders.product_id,
                     mrb_fireex.prod_name
                     FROM mrb_orders 
                     JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
                     WHERE mrb_orders.order_id = '$order_id' 
                     AND mrb_orders.user_id = '$user_id' 
                     AND mrb_orders.order_status = 'delivered'";
    
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (mysqli_num_rows($verify_result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not eligible for review']);
        exit;
    }
    
    $order_data = mysqli_fetch_assoc($verify_result);
    $product_id = $order_data['product_id'];
    $product_name = $order_data['prod_name'];
    
    // Check if review already exists for this order
    $existing_review_query = "SELECT comment_id FROM mrb_comments WHERE order_id = '$order_id' AND user_id = '$user_id'";
    $existing_result = mysqli_query($conn, $existing_review_query);
    
    if (mysqli_num_rows($existing_result) > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this order']);
        exit;
    }
    
    $query = "SELECT MAX(comment_id) AS max_comment_id FROM mrb_comments";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $max_comment_id = ($row && $row['max_comment_id']) ? $row['max_comment_id'] + 1 : 1;

    // Handle photo upload if provided
    $photo_path = null;
    // Only process if file is actually uploaded (not empty and no error except UPLOAD_ERR_NO_FILE)
    if (isset($_FILES['review_photo']) && 
        $_FILES['review_photo']['error'] !== UPLOAD_ERR_NO_FILE && 
        $_FILES['review_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['review_photo'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.']);
            exit;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = 'Images/review_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'review_' . $max_comment_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $photo_path = $upload_path;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload photo']);
            exit;
        }
    }

    // Insert the review into database
    $insert_query = "INSERT INTO mrb_comments (comment_id, order_id, product_id, user_id, user_comment, comment_dateadded, rating, comments_pic) 
                     VALUES ('$max_comment_id','$order_id', '$product_id', '$user_id', '$review_text', NOW(), $rating, " . ($photo_path ? "'$photo_path'" : "''") . ")";
    
    if (mysqli_query($conn, $insert_query)) {
        // Get user name for response
        $user_query = "SELECT user_name FROM mrb_users WHERE user_id = '$user_id'";
        $user_result = mysqli_query($conn, $user_query);
        $user_data = mysqli_fetch_assoc($user_result);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Review submitted successfully',
            'data' => [
                'order_id' => $order_id,
                'rating' => $rating,
                'review_text' => $review_text,
                'product_name' => $product_name,
                'customer_name' => $user_data['user_name'],
                'review_date' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

mysqli_close($conn);
<?php
// filepath: c:\xampp\htdocs\MRBfireex\process_chat.php
session_start();
include 'connection.php';

// Set the timezone to Philippines time
date_default_timezone_set('Asia/Manila');

// Debug incoming data
file_put_contents('chat_debug.log', "Request at " . date('Y-m-d H:i:s') . "\n" . 
                 "POST data: " . print_r($_POST, true) . "\n\n", FILE_APPEND);

// Allow both AJAX requests and traditional form submissions
if (isset($_POST['chat-text-field'])) {
    $chatText = $_POST['chat-text-field']; 
    $chatText = htmlspecialchars($chatText, ENT_QUOTES, 'UTF-8');
    $chatText = trim($chatText);
    $chatShopId = isset($_POST['chat-shop-id']) ? (int)$_POST['chat-shop-id'] : 0;
    $chatProductId = isset($_POST['chat-product-id']) ? (int)$_POST['chat-product-id'] : 0;
    $referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';
    $isShopScopedPage = (strpos($referer, 'indiv.php') !== false || strpos($referer, 'shop.php') !== false);
    
    if (isset($_SESSION['user_id']) && !empty($chatText)) {
        $user_id = $_SESSION['user_id'];

        // Backup context from session for indiv/shop pages when hidden fields are missing.
        if ($isShopScopedPage && $chatShopId <= 0 && isset($_SESSION['chat_context_shop_id'])) {
            $chatShopId = (int)$_SESSION['chat_context_shop_id'];
        }
        if ($isShopScopedPage && $chatProductId <= 0 && isset($_SESSION['chat_context_product_id'])) {
            $chatProductId = (int)$_SESSION['chat_context_product_id'];
        }

        // If shop id is missing from the client, derive it from the product on indiv page.
        if ($chatShopId <= 0 && $chatProductId > 0) {
            $product_shop_sql = "SELECT shop_id FROM mrb_fireex WHERE prod_id = ? LIMIT 1";
            $product_shop_stmt = mysqli_prepare($conn, $product_shop_sql);
            if ($product_shop_stmt) {
                mysqli_stmt_bind_param($product_shop_stmt, 'i', $chatProductId);
                mysqli_stmt_execute($product_shop_stmt);
                $product_shop_result = mysqli_stmt_get_result($product_shop_stmt);
                if ($product_shop_result && mysqli_num_rows($product_shop_result) > 0) {
                    $product_shop_row = mysqli_fetch_assoc($product_shop_result);
                    $chatShopId = isset($product_shop_row['shop_id']) ? (int)$product_shop_row['shop_id'] : 0;
                }
                mysqli_stmt_close($product_shop_stmt);
            }
        }

        // Validate requested shop id before saving so message reaches an existing shop.
        if ($chatShopId > 0) {
            $shop_check_sql = "SELECT approved_shop_id FROM approved_shops WHERE approved_shop_id = ? LIMIT 1";
            $shop_check_stmt = mysqli_prepare($conn, $shop_check_sql);
            if ($shop_check_stmt) {
                mysqli_stmt_bind_param($shop_check_stmt, 'i', $chatShopId);
                mysqli_stmt_execute($shop_check_stmt);
                $shop_check_result = mysqli_stmt_get_result($shop_check_stmt);
                if (!$shop_check_result || mysqli_num_rows($shop_check_result) === 0) {
                    $chatShopId = 0;
                }
                mysqli_stmt_close($shop_check_stmt);
            } else {
                $chatShopId = 0;
            }
        }
        
        // Current timestamp for MySQL using correct timezone
        $currentTime = date('Y-m-d H:i:s');
        $chatShopIdSql = $chatShopId > 0 ? (string)$chatShopId : "0";
        
        $query = "INSERT INTO mrb_messages (user_id, message, message_datesent, message_type, seen_byadmin, shop_id) 
                 VALUES ('$user_id', '$chatText', '$currentTime', 'user-chat', 0, $chatShopIdSql)";
        
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            // Format the time for display (12-hour format with AM/PM)
            $formattedTime = date('h:i A', strtotime($currentTime));
            
            // Send success response with properly formatted time
            echo json_encode([
                'success' => true,
                'message' => $chatText,
                'time' => $formattedTime,
                'id' => mysqli_insert_id($conn)  // Add the message ID
            ]);
            exit;
        }
    }
}

// If we get here, something went wrong
file_put_contents('chat_debug.log', "Error at " . date('Y-m-d H:i:s') . 
                 "\nSession user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . 
                 "\nPOST data: " . print_r($_POST, true) . "\n\n", FILE_APPEND);

echo json_encode(['success' => false, 'error' => 'Could not process message']);
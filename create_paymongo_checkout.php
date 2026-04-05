<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
session_start();
ob_clean();

header('Content-Type: application/json');

function json_response($payload, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
    }
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Invalid request method.'], 405);
}

if (!isset($_SESSION['user_id'])) {
    json_response(['success' => false, 'message' => 'Please log in before paying with GCash.'], 401);
}

include 'connection.php';
require_once __DIR__ . '/paymongo_integration/paymongo.php';

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$location = isset($_POST['location']) ? trim((string)$_POST['location']) : '';
$cutPreference = isset($_POST['cut_preference']) ? trim((string)$_POST['cut_preference']) : '';
$deliveryDate = isset($_POST['delivery_date']) ? trim((string)$_POST['delivery_date']) : '';
$preferredWeight = isset($_POST['preferred_weight']) ? (float)$_POST['preferred_weight'] : 0;
$processing = isset($_POST['processing_options']) ? trim((string)$_POST['processing_options']) : '';
$specialInstructions = isset($_POST['special_instructions']) ? trim((string)$_POST['special_instructions']) : '';

if ($productId <= 0 || $quantity <= 0 || $location === '' || $cutPreference === '' || $deliveryDate === '') {
    json_response(['success' => false, 'message' => 'Missing required order details.']);
}

$productSql = 'SELECT prod_id, prod_name, prod_newprice, shop_id FROM mrb_fireex WHERE prod_id = ? LIMIT 1';
$productStmt = mysqli_prepare($conn, $productSql);
if (!$productStmt) {
    json_response(['success' => false, 'message' => 'Unable to prepare product lookup.']);
}
mysqli_stmt_bind_param($productStmt, 'i', $productId);
mysqli_stmt_execute($productStmt);
$productResult = mysqli_stmt_get_result($productStmt);
$product = $productResult ? mysqli_fetch_assoc($productResult) : null;
mysqli_stmt_close($productStmt);

if (!$product) {
    json_response(['success' => false, 'message' => 'Product not found.']);
}

$pricePerUnit = isset($product['prod_newprice']) ? (float)$product['prod_newprice'] : 0;
$weightOrQty = $preferredWeight > 0 ? $preferredWeight : $quantity;
$amountPhp = round($pricePerUnit * $weightOrQty, 2);

if ($amountPhp < 100) {
    json_response([
        'success' => false,
        'message' => 'PayMongo GCash requires a minimum amount of P100. Please increase quantity.'
    ]);
}

$userSql = 'SELECT user_name, user_email, user_contactnum FROM mrb_users WHERE user_id = ? LIMIT 1';
$userStmt = mysqli_prepare($conn, $userSql);
if (!$userStmt) {
    json_response(['success' => false, 'message' => 'Unable to prepare user lookup.']);
}
mysqli_stmt_bind_param($userStmt, 'i', $userId);
mysqli_stmt_execute($userStmt);
$userResult = mysqli_stmt_get_result($userStmt);
$user = $userResult ? mysqli_fetch_assoc($userResult) : null;
mysqli_stmt_close($userStmt);

$userName = trim((string)($user['user_name'] ?? 'Meatshop Customer'));
$userEmail = trim((string)($user['user_email'] ?? 'customer@example.com'));
$userPhone = trim((string)($user['user_contactnum'] ?? '09123456789'));

if (!preg_match('/^\+?63\d{10}$/', $userPhone) && !preg_match('/^09\d{9}$/', $userPhone)) {
    $userPhone = '09123456789';
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$returnUrl = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
$successUrl = $returnUrl . '/paymongo_return.php?status=success';
$failedUrl = $returnUrl . '/paymongo_return.php?status=failed';

$_SESSION['paymongo_pending_order'] = [
    'productId' => (int)$product['prod_id'],
    'productName' => (string)($product['prod_name'] ?? ''),
    'shopId' => (int)($product['shop_id'] ?? 0),
    'quantity' => $quantity,
    'location' => $location,
    'paymentMethod' => 'gcash',
    'amount' => $amountPhp,
    'cutPreference' => $cutPreference,
    'deliveryDate' => $deliveryDate,
    'preferredWeight' => $preferredWeight,
    'processing' => $processing,
    'specialInstructions' => $specialInstructions,
    'createdAt' => time()
];
$_SESSION['paymongo_ready_to_place_order'] = false;

$metadata = [
    'user_id' => (string)$userId,
    'product_id' => (string)$productId,
    'quantity' => (string)$quantity
];

$source = createGcashSource($amountPhp, $userName, $userEmail, $userPhone, $successUrl, $failedUrl, $metadata);

$checkoutUrl = $source['data']['attributes']['redirect']['checkout_url'] ?? '';
$sourceId = $source['data']['id'] ?? '';
$errorMessage = '';

if (isset($source['errors'][0]['detail'])) {
    $errorMessage = (string)$source['errors'][0]['detail'];
}

if ($checkoutUrl === '') {
    json_response([
        'success' => false,
        'message' => $errorMessage !== '' ? $errorMessage : 'Unable to start PayMongo checkout right now.'
    ]);
}

if ($sourceId !== '') {
    $_SESSION['paymongo_source_id'] = $sourceId;
}

json_response([
    'success' => true,
    'checkout_url' => $checkoutUrl
]);

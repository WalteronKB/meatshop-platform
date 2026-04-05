<?php
session_start();
require_once __DIR__ . '/paymongo_integration/paymongo.php';

$pendingOrder = isset($_SESSION['paymongo_pending_order']) && is_array($_SESSION['paymongo_pending_order'])
    ? $_SESSION['paymongo_pending_order']
    : null;

$targetProductId = $pendingOrder && isset($pendingOrder['productId']) ? (int)$pendingOrder['productId'] : 0;
$status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'failed';

if (!$pendingOrder || $targetProductId <= 0) {
    $_SESSION['paymongo_return'] = [
        'status' => 'error',
        'message' => 'No pending PayMongo order found. Please try again.'
    ];
    header('Location: landpage.php');
    exit;
}

if ($status !== 'success') {
    $_SESSION['paymongo_ready_to_place_order'] = false;
    $_SESSION['paymongo_return'] = [
        'status' => 'error',
        'message' => 'GCash payment was not completed.'
    ];
    header('Location: indiv.php?prod_id=' . $targetProductId);
    exit;
}

$sourceId = '';
if (isset($_GET['source_id']) && trim((string)$_GET['source_id']) !== '') {
    $sourceId = trim((string)$_GET['source_id']);
} elseif (isset($_GET['id']) && trim((string)$_GET['id']) !== '') {
    $sourceId = trim((string)$_GET['id']);
} elseif (isset($_SESSION['paymongo_source_id']) && trim((string)$_SESSION['paymongo_source_id']) !== '') {
    $sourceId = trim((string)$_SESSION['paymongo_source_id']);
}

if ($sourceId === '') {
    $_SESSION['paymongo_ready_to_place_order'] = false;
    $_SESSION['paymongo_return'] = [
        'status' => 'error',
        'message' => 'Missing PayMongo source ID. Please try the payment again.'
    ];
    header('Location: indiv.php?prod_id=' . $targetProductId);
    exit;
}

$amountPhp = isset($pendingOrder['amount']) ? (float)$pendingOrder['amount'] : 0;
if ($amountPhp <= 0) {
    $_SESSION['paymongo_ready_to_place_order'] = false;
    $_SESSION['paymongo_return'] = [
        'status' => 'error',
        'message' => 'Invalid payment amount. Please try again.'
    ];
    header('Location: indiv.php?prod_id=' . $targetProductId);
    exit;
}

$description = 'Meatshop order payment for product #' . $targetProductId;
$payment = createPayment($amountPhp, $sourceId, $description);
$paymentId = isset($payment['data']['id']) ? trim((string)$payment['data']['id']) : '';

if ($paymentId === '') {
    $details = '';
    if (isset($payment['errors'][0]['detail'])) {
        $details = trim((string)$payment['errors'][0]['detail']);
    }
    $_SESSION['paymongo_ready_to_place_order'] = false;
    $_SESSION['paymongo_return'] = [
        'status' => 'error',
        'message' => $details !== '' ? $details : 'PayMongo payment capture failed.'
    ];
    header('Location: indiv.php?prod_id=' . $targetProductId);
    exit;
}

$_SESSION['gcash_reference'] = $paymentId;
$_SESSION['gcash_transaction_id'] = $sourceId;
$_SESSION['paymongo_ready_to_place_order'] = true;
$_SESSION['paymongo_return'] = [
    'status' => 'success',
    'message' => 'PayMongo payment received. Finalizing your order...'
];

header('Location: indiv.php?prod_id=' . $targetProductId . '&paymongo=1');
exit;

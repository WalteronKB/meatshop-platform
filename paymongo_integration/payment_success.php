<?php
require_once 'paymongo.php';

$sourceId = $_GET['source_id'] ?? null;

if ($sourceId) {
    $payment = createPayment(100, $sourceId, 'Order Payment');
    echo "<h2>Payment Successful! 🎉</h2>";
    echo "<p>Payment ID: " . $payment['data']['id'] . "</p>";
} else {
    echo "<h2>Something went wrong.</h2>";
}
?>
<?php
require_once 'paymongo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $phone  = $_POST['phone'];

    $source = createGcashSource($amount, $name, $email, $phone);
    $checkoutUrl = $source['data']['attributes']['redirect']['checkout_url'];

    header('Location: ' . $checkoutUrl);
    exit;
}
?>

<form method="POST" action="">
    <input type="text"   name="name"   placeholder="Full Name" required /><br>
    <input type="email"  name="email"  placeholder="Email" required /><br>
    <input type="text"   name="phone"  placeholder="Phone (e.g. 09123456789)" required /><br>
    <input type="number" name="amount" placeholder="Amount in PHP (min. 100)" required /><br>
    <button type="submit">Pay with GCash</button>
</form>
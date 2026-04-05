<?php
require_once 'config.php';

function createGcashSource($amount, $name, $email, $phone, $successUrl = null, $failedUrl = null, $metadata = []) {
    $secretKey = base64_encode(PAYMONGO_SECRET_KEY . ':');

    if ($successUrl === null || trim((string)$successUrl) === '') {
        $successUrl = 'http://localhost/your-project/payment_success.php';
    }
    if ($failedUrl === null || trim((string)$failedUrl) === '') {
        $failedUrl = 'http://localhost/your-project/payment_failed.php';
    }

    $safeMetadata = [];
    if (is_array($metadata)) {
        foreach ($metadata as $key => $value) {
            $safeMetadata[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value);
        }
    }

    $data = [
        'data' => [
            'attributes' => [
                'amount'   => $amount * 100, // convert to cents
                'currency' => 'PHP',
                'type'     => 'gcash',
                'redirect' => [
                    'success' => $successUrl,
                    'failed'  => $failedUrl,
                ],
                'billing' => [
                    'name'  => $name,
                    'email' => $email,
                    'phone' => $phone,
                ],
            ],
        ],
    ];

    if (!empty($safeMetadata)) {
        $data['data']['attributes']['metadata'] = $safeMetadata;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYMONGO_BASE_URL . '/sources');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $secretKey,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function createPayment($amount, $sourceId, $description = 'GCash Payment') {
    $secretKey = base64_encode(PAYMONGO_SECRET_KEY . ':');

    $data = [
        'data' => [
            'attributes' => [
                'amount'      => $amount * 100,
                'currency'    => 'PHP',
                'description' => $description,
                'source'      => [
                    'id'   => $sourceId,
                    'type' => 'source',
                ],
            ],
        ],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYMONGO_BASE_URL . '/payments');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $secretKey,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
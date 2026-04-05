<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: mrbloginpage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: shop_application.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$store_name = trim($_POST['store_name'] ?? '');
$store_description = trim($_POST['store_description'] ?? '');
$store_address = trim($_POST['store_address'] ?? '');
$address_iframe = trim($_POST['address_iframe'] ?? '');
$business_permit_no = trim($_POST['business_permit_no'] ?? '');
$tin_no = trim($_POST['tin_no'] ?? '');
$operating_hours = trim($_POST['operating_hours'] ?? '');
$delivery_areas = trim($_POST['delivery_areas'] ?? '');

$business_email = '';
$business_phone = '';

$user_contact_sql = "SELECT user_email, user_contactnum FROM mrb_users WHERE user_id = ? LIMIT 1";
$user_contact_stmt = mysqli_prepare($conn, $user_contact_sql);
if ($user_contact_stmt) {
    mysqli_stmt_bind_param($user_contact_stmt, 'i', $user_id);
    mysqli_stmt_execute($user_contact_stmt);
    $user_contact_result = mysqli_stmt_get_result($user_contact_stmt);
    if ($user_contact_result && mysqli_num_rows($user_contact_result) > 0) {
        $user_contact_row = mysqli_fetch_assoc($user_contact_result);
        $business_email = trim($user_contact_row['user_email'] ?? '');
        $business_phone = trim($user_contact_row['user_contactnum'] ?? '');
    }
    mysqli_stmt_close($user_contact_stmt);
}

if (
    $store_name === '' ||
    $store_description === '' ||
    $store_address === '' ||
    $business_permit_no === '' ||
    $operating_hours === ''
) {
    header("Location: shop_application.php?error=" . urlencode("Please complete all required fields."));
    exit;
}

if (stripos($store_address, 'cavite') === false) {
    header("Location: shop_application.php?error=" . urlencode("Only Cavite-based shop addresses are allowed."));
    exit;
}

if (!isset($_FILES['store_logo']) || $_FILES['store_logo']['error'] !== UPLOAD_ERR_OK) {
    header("Location: shop_application.php?error=" . urlencode("Store logo upload failed."));
    exit;
}

if (!isset($_FILES['business_permit_file']) || $_FILES['business_permit_file']['error'] !== UPLOAD_ERR_OK) {
    header("Location: shop_application.php?error=" . urlencode("Business permit file upload failed."));
    exit;
}

$create_applications_table_sql = "
CREATE TABLE IF NOT EXISTS shop_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    store_description TEXT NOT NULL,
    store_address VARCHAR(255) NOT NULL,
    address_iframe TEXT NOT NULL,
    business_email VARCHAR(120) NOT NULL,
    business_phone VARCHAR(40) NOT NULL,
    business_permit_no VARCHAR(80) NOT NULL,
    tin_no VARCHAR(80) DEFAULT NULL,
    operating_hours TEXT NOT NULL,
    delivery_areas TEXT DEFAULT NULL,
    store_logo_path VARCHAR(255) NOT NULL,
    business_permit_path VARCHAR(255) NOT NULL,
    gcash_qr_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!mysqli_query($conn, $create_applications_table_sql)) {
    header("Location: shop_application.php?error=" . urlencode("Unable to prepare shop application table."));
    exit;
}

$create_approved_shops_table_sql = "
CREATE TABLE IF NOT EXISTS approved_shops (
    approved_shop_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    store_description TEXT NOT NULL,
    store_address VARCHAR(255) NOT NULL,
    address_iframe TEXT NOT NULL,
    business_email VARCHAR(120) NOT NULL,
    business_phone VARCHAR(40) NOT NULL,
    business_permit_no VARCHAR(80) NOT NULL,
    tin_no VARCHAR(80) DEFAULT NULL,
    operating_hours TEXT NOT NULL,
    delivery_areas TEXT DEFAULT NULL,
    store_logo_path VARCHAR(255) NOT NULL,
    business_permit_path VARCHAR(255) NOT NULL,
    gcash_qr_path VARCHAR(255) DEFAULT NULL,
    shop_status ENUM('active','inactive','suspended') DEFAULT 'active',
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_approved_shops_user_id (user_id),
    UNIQUE KEY uniq_approved_shops_application_id (application_id),
    INDEX idx_approved_shops_shop_status (shop_status),
    CONSTRAINT fk_approved_shops_application_id
        FOREIGN KEY (application_id) REFERENCES shop_applications(application_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if (!mysqli_query($conn, $create_approved_shops_table_sql)) {
    header("Location: shop_application.php?error=" . urlencode("Unable to prepare approved shops table."));
    exit;
}

mysqli_query($conn, "ALTER TABLE shop_applications ADD COLUMN IF NOT EXISTS address_iframe TEXT NOT NULL AFTER store_address");
mysqli_query($conn, "ALTER TABLE approved_shops ADD COLUMN IF NOT EXISTS address_iframe TEXT NOT NULL AFTER store_address");
mysqli_query($conn, "ALTER TABLE shop_applications ADD COLUMN IF NOT EXISTS gcash_qr_path VARCHAR(255) DEFAULT NULL AFTER business_permit_path");
mysqli_query($conn, "ALTER TABLE approved_shops ADD COLUMN IF NOT EXISTS gcash_qr_path VARCHAR(255) DEFAULT NULL AFTER business_permit_path");
mysqli_query($conn, "ALTER TABLE shop_applications MODIFY COLUMN operating_hours TEXT NOT NULL");
mysqli_query($conn, "ALTER TABLE approved_shops MODIFY COLUMN operating_hours TEXT NOT NULL");
mysqli_query($conn, "ALTER TABLE shop_applications DROP COLUMN IF EXISTS business_type");
mysqli_query($conn, "ALTER TABLE approved_shops DROP COLUMN IF EXISTS business_type");

$pending_check_sql = "SELECT application_id FROM shop_applications WHERE user_id = ? AND status = 'pending' LIMIT 1";
$pending_stmt = mysqli_prepare($conn, $pending_check_sql);
mysqli_stmt_bind_param($pending_stmt, 'i', $user_id);
mysqli_stmt_execute($pending_stmt);
$pending_result = mysqli_stmt_get_result($pending_stmt);
if ($pending_result && mysqli_num_rows($pending_result) > 0) {
    mysqli_stmt_close($pending_stmt);
    header("Location: shop_application.php?error=" . urlencode("You still have an ongoing application."));
    exit;
}
mysqli_stmt_close($pending_stmt);

$rejected_check_sql = "
    SELECT application_id, updated_at, submitted_at
    FROM shop_applications
    WHERE user_id = ? AND status = 'rejected'
    ORDER BY updated_at DESC, submitted_at DESC
    LIMIT 1
";
$rejected_stmt = mysqli_prepare($conn, $rejected_check_sql);
if ($rejected_stmt) {
    mysqli_stmt_bind_param($rejected_stmt, 'i', $user_id);
    mysqli_stmt_execute($rejected_stmt);
    $rejected_result = mysqli_stmt_get_result($rejected_stmt);
    $last_rejected = $rejected_result ? mysqli_fetch_assoc($rejected_result) : null;
    mysqli_stmt_close($rejected_stmt);

    if ($last_rejected) {
        $reference_time = $last_rejected['updated_at'] ?? $last_rejected['submitted_at'] ?? null;
        if ($reference_time) {
            $elapsed_seconds = time() - strtotime($reference_time);
            $cooldown_seconds = 24 * 60 * 60;
            if ($elapsed_seconds < $cooldown_seconds) {
                $remaining_seconds = $cooldown_seconds - $elapsed_seconds;
                $remaining_hours = (int) ceil($remaining_seconds / 3600);
                $cooldown_message = "You can submit a new shop application after 24 hours from rejection. Please try again in about {$remaining_hours} hour(s).";
                header("Location: shop_application.php?error=" . urlencode($cooldown_message));
                exit;
            }
        }
    }
}

$base_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'shop_applications' . DIRECTORY_SEPARATOR . $user_id;
if (!is_dir($base_upload_dir) && !mkdir($base_upload_dir, 0777, true)) {
    header("Location: shop_application.php?error=" . urlencode("Unable to create upload directory."));
    exit;
}

function saveUploadedFile(array $file, array $allowed_extensions, int $max_size, string $prefix, string $target_dir): ?string
{
    if (!isset($file['name'], $file['tmp_name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if ($file['size'] > $max_size) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions, true)) {
        return null;
    }

    $safe_name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $target_dir . DIRECTORY_SEPARATOR . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return null;
    }

    return 'uploads/shop_applications/' . basename($target_dir) . '/' . $safe_name;
}

$logo_path = saveUploadedFile(
    $_FILES['store_logo'],
    ['jpg', 'jpeg', 'png', 'webp'],
    3 * 1024 * 1024,
    'logo',
    $base_upload_dir
);

if ($logo_path === null) {
    header("Location: shop_application.php?error=" . urlencode("Invalid store logo. Allowed types: JPG, JPEG, PNG, WEBP. Max size: 3MB."));
    exit;
}

$permit_path = saveUploadedFile(
    $_FILES['business_permit_file'],
    ['pdf', 'jpg', 'jpeg', 'png'],
    5 * 1024 * 1024,
    'permit',
    $base_upload_dir
);

if ($permit_path === null) {
    header("Location: shop_application.php?error=" . urlencode("Invalid business permit file. Allowed types: PDF, JPG, JPEG, PNG. Max size: 5MB."));
    exit;
}

$gcash_qr_path = null;
if (isset($_FILES['gcash_qr_file']) && $_FILES['gcash_qr_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['gcash_qr_file']['error'] !== UPLOAD_ERR_OK) {
        header("Location: shop_application.php?error=" . urlencode("GCash QR upload failed."));
        exit;
    }

    $gcash_qr_path = saveUploadedFile(
        $_FILES['gcash_qr_file'],
        ['jpg', 'jpeg', 'png', 'webp'],
        3 * 1024 * 1024,
        'gcash_qr',
        $base_upload_dir
    );

    if ($gcash_qr_path === null) {
        header("Location: shop_application.php?error=" . urlencode("Invalid GCash QR image. Allowed types: JPG, JPEG, PNG, WEBP. Max size: 3MB."));
        exit;
    }
}

$insert_sql = "
INSERT INTO shop_applications (
    user_id, store_name, store_description, store_address, business_email, business_phone,
    business_permit_no, tin_no, operating_hours, delivery_areas, address_iframe,
    store_logo_path, business_permit_path, gcash_qr_path, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
";

$insert_stmt = mysqli_prepare($conn, $insert_sql);
if (!$insert_stmt) {
    header("Location: shop_application.php?error=" . urlencode("Unable to submit application right now."));
    exit;
}

mysqli_stmt_bind_param(
    $insert_stmt,
    'isssssssssssss',
    $user_id,
    $store_name,
    $store_description,
    $store_address,
    $business_email,
    $business_phone,
    $business_permit_no,
    $tin_no,
    $operating_hours,
    $delivery_areas,
    $address_iframe,
    $logo_path,
    $permit_path,
    $gcash_qr_path
);

if (!mysqli_stmt_execute($insert_stmt)) {
    mysqli_stmt_close($insert_stmt);
    header("Location: shop_application.php?error=" . urlencode("Failed to submit your application."));
    exit;
}

mysqli_stmt_close($insert_stmt);
header("Location: shop_application.php?success=true");
exit;

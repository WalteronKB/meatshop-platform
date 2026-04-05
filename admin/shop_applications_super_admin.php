<?php
include '../connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../mrbloginpage.php");
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
    header("Location: analytics-admin.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: ../mrbloginpage.php");
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

function sendShopRejectionEmail(string $to_email, string $applicant_name, string $store_name, string $notes_content): array
{
    if ($to_email === '' || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid recipient email address.'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail_host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $mail_port = (int) (getenv('MAIL_PORT') ?: 587);
        $mail_username = getenv('MAIL_USERNAME') ?: 'arondolar321@gmail.com';
        $mail_password = getenv('MAIL_PASSWORD') ?: 'isjhlzqyjugzcoxf';
        $mail_secure = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: 'tls'));
        $mail_from = getenv('MAIL_FROM_ADDRESS') ?: $mail_username;
        $mail_from_name = getenv('MAIL_FROM_NAME') ?: 'Meat Shop';

        $mail->isSMTP();
        $mail->Host = $mail_host;
        $mail->Port = $mail_port > 0 ? $mail_port : 587;
        $mail->SMTPAuth = true;
        $mail->Username = $mail_username;
        $mail->Password = str_replace(' ', '', $mail_password);
        if ($mail_secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        if ($mail->Username === '') {
            return ['success' => false, 'error' => 'MAIL_USERNAME is required (for Gmail use your full Gmail address).'];
        }

        if ($mail_from === '' || !filter_var($mail_from, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'MAIL_FROM_ADDRESS is invalid or missing.'];
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($mail_from, $mail_from_name);
        $mail->addAddress($to_email, $applicant_name !== '' ? $applicant_name : $to_email);
        $mail->isHTML(false);
        $mail->Subject = 'Shop Application Rejected - ' . $store_name;
        $mail->Body = $notes_content;

        $mail->send();
        return ['success' => true, 'error' => ''];
    } catch (Exception $e) {
        $error = trim((string) $mail->ErrorInfo);
        if ($error === '') {
            $error = $e->getMessage();
        }
        return ['success' => false, 'error' => $error !== '' ? $error : 'Unknown mailer error.'];
    }
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
mysqli_query($conn, $create_applications_table_sql);

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
mysqli_query($conn, $create_approved_shops_table_sql);
mysqli_query($conn, "ALTER TABLE shop_applications ADD COLUMN IF NOT EXISTS gcash_qr_path VARCHAR(255) DEFAULT NULL AFTER business_permit_path");
mysqli_query($conn, "ALTER TABLE approved_shops ADD COLUMN IF NOT EXISTS gcash_qr_path VARCHAR(255) DEFAULT NULL AFTER business_permit_path");
mysqli_query($conn, "ALTER TABLE shop_applications MODIFY COLUMN operating_hours TEXT NOT NULL");
mysqli_query($conn, "ALTER TABLE approved_shops MODIFY COLUMN operating_hours TEXT NOT NULL");

$shop_id_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_users LIKE 'shop_id'");
if (!$shop_id_column_check || mysqli_num_rows($shop_id_column_check) === 0) {
    mysqli_query($conn, "ALTER TABLE mrb_users ADD COLUMN shop_id INT DEFAULT NULL");
}

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $application_id = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if ($application_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        $message = 'Invalid action request.';
        $message_type = 'danger';
    } elseif ($action === 'reject' && $admin_notes === '') {
        $message = 'Please provide notes. Notes are required and will be sent to the applicant email.';
        $message_type = 'warning';
    } else {
        $fetch_sql = "
            SELECT sa.*, u.user_email AS account_email, u.user_name, u.user_mname, u.user_lname
            FROM shop_applications sa
            LEFT JOIN mrb_users u ON sa.user_id = u.user_id
            WHERE sa.application_id = ?
            LIMIT 1
        ";
        $fetch_stmt = mysqli_prepare($conn, $fetch_sql);
        mysqli_stmt_bind_param($fetch_stmt, 'i', $application_id);
        mysqli_stmt_execute($fetch_stmt);
        $application_result = mysqli_stmt_get_result($fetch_stmt);
        $application = $application_result ? mysqli_fetch_assoc($application_result) : null;
        mysqli_stmt_close($fetch_stmt);

        if (!$application) {
            $message = 'Application not found.';
            $message_type = 'danger';
        } elseif ($application['status'] !== 'pending') {
            $message = 'Only pending applications can be updated.';
            $message_type = 'warning';
        } else {
            $gcash_qr_path = $application['gcash_qr_path'] ?? null;
            if (isset($_FILES['gcash_qr_file']) && $_FILES['gcash_qr_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['gcash_qr_file']['error'] !== UPLOAD_ERR_OK) {
                    $message = 'GCash QR upload failed.';
                    $message_type = 'danger';
                    $application = null;
                } else {
                    $base_upload_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'shop_applications' . DIRECTORY_SEPARATOR . (int) $application['user_id'];
                    if (!is_dir($base_upload_dir) && !mkdir($base_upload_dir, 0777, true)) {
                        $message = 'Unable to create upload directory for GCash QR.';
                        $message_type = 'danger';
                        $application = null;
                    } else {
                        $new_gcash_qr_path = saveUploadedFile(
                            $_FILES['gcash_qr_file'],
                            ['jpg', 'jpeg', 'png', 'webp'],
                            3 * 1024 * 1024,
                            'gcash_qr',
                            $base_upload_dir
                        );

                        if ($new_gcash_qr_path === null) {
                            $message = 'Invalid GCash QR image. Allowed types: JPG, JPEG, PNG, WEBP. Max size: 3MB.';
                            $message_type = 'danger';
                            $application = null;
                        } else {
                            $gcash_qr_path = $new_gcash_qr_path;
                        }
                    }
                }
            }

            if (!$application) {
                // Keep message from upload validation failure.
            } else {
            mysqli_begin_transaction($conn);

            $new_status = $action === 'approve' ? 'approved' : 'rejected';
            $update_sql = "UPDATE shop_applications SET status = ?, admin_notes = ?, gcash_qr_path = ?, updated_at = NOW() WHERE application_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, 'sssi', $new_status, $admin_notes, $gcash_qr_path, $application_id);
            $update_ok = mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            $approved_ok = true;
            $role_update_ok = true;
            if ($update_ok && $action === 'approve') {
                $upsert_sql = "
                    INSERT INTO approved_shops (
                        application_id, user_id, store_name, store_description, store_address, address_iframe,
                        business_email, business_phone, business_permit_no, tin_no, operating_hours,
                        delivery_areas, store_logo_path, business_permit_path, gcash_qr_path, shop_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE
                        application_id = VALUES(application_id),
                        store_name = VALUES(store_name),
                        store_description = VALUES(store_description),
                        store_address = VALUES(store_address),
                        address_iframe = VALUES(address_iframe),
                        business_email = VALUES(business_email),
                        business_phone = VALUES(business_phone),
                        business_permit_no = VALUES(business_permit_no),
                        tin_no = VALUES(tin_no),
                        operating_hours = VALUES(operating_hours),
                        delivery_areas = VALUES(delivery_areas),
                        store_logo_path = VALUES(store_logo_path),
                        business_permit_path = VALUES(business_permit_path),
                        gcash_qr_path = VALUES(gcash_qr_path),
                        shop_status = 'active',
                        updated_at = NOW()
                ";
                $upsert_stmt = mysqli_prepare($conn, $upsert_sql);
                mysqli_stmt_bind_param(
                    $upsert_stmt,
                    'iisssssssssssss',
                    $application['application_id'],
                    $application['user_id'],
                    $application['store_name'],
                    $application['store_description'],
                    $application['store_address'],
                    $application['address_iframe'],
                    $application['business_email'],
                    $application['business_phone'],
                    $application['business_permit_no'],
                    $application['tin_no'],
                    $application['operating_hours'],
                    $application['delivery_areas'],
                    $application['store_logo_path'],
                    $application['business_permit_path'],
                    $gcash_qr_path
                );
                $approved_ok = mysqli_stmt_execute($upsert_stmt);
                mysqli_stmt_close($upsert_stmt);

                if ($approved_ok) {
                    $shop_id = null;
                    $shop_lookup_sql = "SELECT approved_shop_id FROM approved_shops WHERE user_id = ? LIMIT 1";
                    $shop_lookup_stmt = mysqli_prepare($conn, $shop_lookup_sql);
                    mysqli_stmt_bind_param($shop_lookup_stmt, 'i', $application['user_id']);
                    mysqli_stmt_execute($shop_lookup_stmt);
                    $shop_lookup_result = mysqli_stmt_get_result($shop_lookup_stmt);
                    if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
                        $shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
                        $shop_id = (int) $shop_lookup_row['approved_shop_id'];
                    }
                    mysqli_stmt_close($shop_lookup_stmt);

                    $promote_sql = "UPDATE mrb_users SET user_type = 'admin', shop_id = ? WHERE user_id = ?";
                    $promote_stmt = mysqli_prepare($conn, $promote_sql);
                    mysqli_stmt_bind_param($promote_stmt, 'ii', $shop_id, $application['user_id']);
                    $role_update_ok = mysqli_stmt_execute($promote_stmt);
                    mysqli_stmt_close($promote_stmt);
                }
            }

            if ($update_ok && $approved_ok && $role_update_ok) {
                mysqli_commit($conn);
                if ($action === 'reject') {
                    $recipient_email = trim((string) ($application['account_email'] ?? ''));
                    if ($recipient_email === '') {
                        $recipient_email = trim((string) ($application['business_email'] ?? ''));
                    }

                    $applicant_name = trim((string) (($application['user_name'] ?? '') . ' ' . ($application['user_mname'] ?? '') . ' ' . ($application['user_lname'] ?? '')));
                    $notes_email_content = $admin_notes;
                    $mail_result = sendShopRejectionEmail(
                        $recipient_email,
                        $applicant_name,
                        (string) ($application['store_name'] ?? 'Your Store'),
                        $notes_email_content
                    );

                    $message = ($mail_result['success'] ?? false)
                        ? 'Application rejected and rejection notes have been emailed to the applicant.'
                        : 'Application rejected, but the email could not be sent. Mailer error: ' . ($mail_result['error'] ?? 'Unknown error');
                    $message_type = ($mail_result['success'] ?? false) ? 'success' : 'warning';
                } else {
                    $message = 'Application approved, shop added to approved shops, and user promoted to admin with shop access.';
                    $message_type = 'success';
                }
            } else {
                mysqli_rollback($conn);
                $message = 'Unable to update application status. Please try again.';
                $message_type = 'danger';
            }
            }
        }
    }
}

$filter = $_GET['status'] ?? 'all';
$allowed_filters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$where_clause = '';
if ($filter !== 'all') {
    $safe_filter = mysqli_real_escape_string($conn, $filter);
    $where_clause = "WHERE sa.status = '$safe_filter'";
}

$list_sql = "
    SELECT sa.*, u.user_name, u.user_mname, u.user_lname, u.user_email AS account_email, u.user_contactnum AS account_contact
    FROM shop_applications sa
    LEFT JOIN mrb_users u ON sa.user_id = u.user_id
    $where_clause
    ORDER BY sa.submitted_at DESC
";
$list_result = mysqli_query($conn, $list_sql);

$stats_query = "
    SELECT
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        COUNT(*) AS total_count
    FROM shop_applications
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = $stats_result ? mysqli_fetch_assoc($stats_result) : [
    'pending_count' => 0,
    'approved_count' => 0,
    'rejected_count' => 0,
    'total_count' => 0
];

$user_pic = '';
$pic_query = "SELECT user_pic FROM mrb_users WHERE user_id = '" . (int) $_SESSION['user_id'] . "'";
$pic_result = mysqli_query($conn, $pic_query);
if ($pic_result && mysqli_num_rows($pic_result) > 0) {
    $pic_row = mysqli_fetch_assoc($pic_result);
    $user_pic = $pic_row['user_pic'];
} else {
    $user_pic = 'Images/anonymous.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Shop Applications</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="admin.css">
    <style>
        .status-pill {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #842029; }
        .table td, .table th { vertical-align: middle; }
        .notes-box { min-width: 220px; }
    </style>
</head>
<body>
<section id="sidebar" class="sidebar">
    <a href="#" class="brand">
        <i class='bx bxs-restaurant'></i>
        <span class="text">Meat Shop</span>
    </a>
    <ul class="side-menu top" style="padding: 0px;">
        <li>
            <a href="analytics-admin.php">
                <i class='bx bxs-bar-chart-alt-2'></i>
                <span class="text">Analytics</span>
            </a>
        </li>
        <li>
            <a href="super_admin.php">
                <i class='bx bxs-shield'></i>
                <span class="text">Activity Logs</span>
            </a>
        </li>
        <li class="active">
            <a href="shop_applications_super_admin.php">
                <i class='bx bxs-store'></i>
                <span class="text">Shop Applications</span>
            </a>
        </li>
        <li>
            <a href="shops-admin.php">
                <i class='bx bxs-buildings'></i>
                <span class="text">Manage Shops</span>
            </a>
        </li>
        <li>
            <a href="messages-admin.php">
                <i class='bx bxs-user-account'></i>
                <span class="text">Accounts</span>
            </a>
        </li>
    </ul>

    <ul class="side-menu" style="padding: 0px;">
        <li>
            <a href="account_super_admin.php">
                <i class="bx bxs-user-circle"></i>
                <span class="text">My Account</span>
            </a>
        </li>
        <li>
            <a href="javascript:void(0);" onclick="confirmLogout()">
                <i class='bx bx-power-off'></i>
                <span class="text">Logout</span>
            </a>
        </li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <a href="#" class="nav-link">Super Admin</a>
        <form action="#" type="hidden">
            <div class="form-input">
                <input type="hidden" placeholder="Search...">
                <button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search'></i></button>
            </div>
        </form>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>

        <a href="account_super_admin.php" class="profile">
            <img src="../<?php echo htmlspecialchars($user_pic); ?>">
        </a>
    </nav>

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Shop Applications</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Shop Applications</a></li>
                </ul>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm"><div class="card-body"><small>Total</small><h5 class="mb-0"><?php echo (int) ($stats['total_count'] ?? 0); ?></h5></div></div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm"><div class="card-body"><small>Pending</small><h5 class="mb-0 text-warning"><?php echo (int) ($stats['pending_count'] ?? 0); ?></h5></div></div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm"><div class="card-body"><small>Approved</small><h5 class="mb-0 text-success"><?php echo (int) ($stats['approved_count'] ?? 0); ?></h5></div></div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="card shadow-sm"><div class="card-body"><small>Rejected</small><h5 class="mb-0 text-danger"><?php echo (int) ($stats['rejected_count'] ?? 0); ?></h5></div></div>
            </div>
        </div>

        <div class="mb-3 d-flex gap-2 flex-wrap">
            <a class="btn <?php echo $filter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>" href="shop_applications_super_admin.php?status=all">All</a>
            <a class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>" href="shop_applications_super_admin.php?status=pending">Pending</a>
            <a class="btn <?php echo $filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>" href="shop_applications_super_admin.php?status=approved">Approved</a>
            <a class="btn <?php echo $filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>" href="shop_applications_super_admin.php?status=rejected">Rejected</a>
        </div>

        <div class="table-data">
            <div class="order">
                <div class="head">
                    <h3><i class='bx bxs-store'></i> Manage Applications</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Applicant</th>
                                <th>Store</th>
                                <th>Contact</th>
                                <th>Permit</th>
                                <th>Files</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th style="min-width: 320px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list_result && mysqli_num_rows($list_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($list_result)): ?>
                                    <?php
                                        $full_name = trim(($row['user_name'] ?? '') . ' ' . ($row['user_mname'] ?? '') . ' ' . ($row['user_lname'] ?? ''));
                                        $status_class = 'status-pending';
                                        if ($row['status'] === 'approved') {
                                            $status_class = 'status-approved';
                                        } elseif ($row['status'] === 'rejected') {
                                            $status_class = 'status-rejected';
                                        }
                                    ?>
                                    <tr>
                                        <td>#<?php echo (int) $row['application_id']; ?></td>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($full_name !== '' ? $full_name : 'Unknown User'); ?></strong></div>
                                            <small><?php echo htmlspecialchars($row['account_email'] ?? 'No email'); ?></small>
                                        </td>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($row['store_name']); ?></strong></div>
                                            <small><?php echo htmlspecialchars($row['store_address']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['business_email']); ?></div>
                                            <small><?php echo htmlspecialchars($row['business_phone']); ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['business_permit_no']); ?></div>
                                            <small>TIN: <?php echo htmlspecialchars($row['tin_no'] !== '' ? $row['tin_no'] : 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary mb-1" target="_blank" href="../<?php echo htmlspecialchars($row['store_logo_path']); ?>">Logo</a>
                                            <a class="btn btn-sm btn-outline-secondary mb-1" target="_blank" href="../<?php echo htmlspecialchars($row['business_permit_path']); ?>">Permit</a>
                                            <?php if (!empty($row['gcash_qr_path'])): ?>
                                                <a class="btn btn-sm btn-outline-success" target="_blank" href="../<?php echo htmlspecialchars($row['gcash_qr_path']); ?>">GCash QR</a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">No GCash QR</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-pill <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['submitted_at']))); ?></td>
                                        <td>
                                            <div class="mb-2">
                                                <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?php echo (int) $row['application_id']; ?>" aria-expanded="false">
                                                    View Details
                                                </button>
                                            </div>
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <form method="post" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-start">
                                                    <input type="hidden" name="application_id" value="<?php echo (int) $row['application_id']; ?>">
                                                    <textarea class="form-control form-control-sm notes-box" name="admin_notes" rows="2" placeholder="Optional admin notes"></textarea>
                                                    <div>
                                                        <input class="form-control form-control-sm" type="file" name="gcash_qr_file" accept=".jpg,.jpeg,.png,.webp">
                                                        <small class="text-muted">Optional: Replace GCash QR</small>
                                                    </div>
                                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this application?');">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted">Application already processed.</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="details-<?php echo (int) $row['application_id']; ?>">
                                        <td colspan="9">
                                            <div class="p-3 bg-light border rounded">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($row['store_description'])); ?></p>
                                                        <p class="mb-1"><strong>Operating Hours:</strong> <?php echo htmlspecialchars($row['operating_hours']); ?></p>
                                                        <p class="mb-1"><strong>Delivery Areas:</strong> <?php echo htmlspecialchars($row['delivery_areas'] !== '' ? $row['delivery_areas'] : 'N/A'); ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Map Embed:</strong></p>
                                                        <input type="text" readonly class="form-control form-control-sm mb-2" value="<?php echo htmlspecialchars($row['address_iframe']); ?>">
                                                        <?php if (!empty($row['gcash_qr_path'])): ?>
                                                            <p class="mb-1"><strong>GCash QR:</strong></p>
                                                            <a class="btn btn-sm btn-outline-success mb-2" target="_blank" href="../<?php echo htmlspecialchars($row['gcash_qr_path']); ?>">Open GCash QR</a>
                                                        <?php else: ?>
                                                            <p class="mb-2"><strong>GCash QR:</strong> N/A</p>
                                                        <?php endif; ?>
                                                        <p class="mb-1"><strong>Admin Notes:</strong> <?php echo htmlspecialchars($row['admin_notes'] !== '' ? $row['admin_notes'] : 'N/A'); ?></p>
                                                        <p class="mb-0"><strong>Last Updated:</strong> <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['updated_at']))); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No shop applications found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
<script>
function confirmLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = '../logout.php';
    }
}
</script>
<script src="script.js"></script>
</body>
</html>

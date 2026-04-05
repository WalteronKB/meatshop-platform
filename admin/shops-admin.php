<?php
include '../connection.php';
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

$feedback = '';
$feedback_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shop_status'])) {
    $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;
    $shop_status = isset($_POST['shop_status']) ? trim($_POST['shop_status']) : '';
    $allowed_status = ['active', 'inactive', 'suspended'];

    if ($shop_id <= 0 || !in_array($shop_status, $allowed_status, true)) {
        $feedback = 'Invalid shop update request.';
        $feedback_type = 'danger';
    } else {
        $update_stmt = mysqli_prepare($conn, "UPDATE approved_shops SET shop_status = ?, updated_at = NOW() WHERE approved_shop_id = ? LIMIT 1");
        mysqli_stmt_bind_param($update_stmt, 'si', $shop_status, $shop_id);
        if (mysqli_stmt_execute($update_stmt)) {
            $feedback = 'Shop status updated successfully.';
            $feedback_type = 'success';
        } else {
            $feedback = 'Failed to update shop status.';
            $feedback_type = 'danger';
        }
        mysqli_stmt_close($update_stmt);
    }
}

$shop_rows = [];
$shops_query = "SELECT s.approved_shop_id, s.store_name, s.store_address, s.business_email, s.business_phone,
                      s.shop_status, s.updated_at, s.user_id,
                      u.user_name, u.user_lname,
                      COUNT(CASE WHEN f.prod_type != 'deleted' AND (f.is_hidden IS NULL OR f.is_hidden != 'true') THEN 1 END) AS total_products
               FROM approved_shops s
               LEFT JOIN mrb_users u ON s.user_id = u.user_id
               LEFT JOIN mrb_fireex f ON s.approved_shop_id = f.shop_id
               GROUP BY s.approved_shop_id, s.store_name, s.store_address, s.business_email, s.business_phone, s.shop_status, s.updated_at, s.user_id, u.user_name, u.user_lname
               ORDER BY s.updated_at DESC, s.approved_shop_id DESC";
$shops_result = mysqli_query($conn, $shops_query);
if ($shops_result) {
    while ($row = mysqli_fetch_assoc($shops_result)) {
        $shop_rows[] = $row;
    }
}

$user_pic = 'Images/anonymous.jpg';
$pic_query = "SELECT user_pic FROM mrb_users WHERE user_id = " . (int)$_SESSION['user_id'] . " LIMIT 1";
$pic_result = mysqli_query($conn, $pic_query);
if ($pic_result && mysqli_num_rows($pic_result) > 0) {
    $pic_row = mysqli_fetch_assoc($pic_result);
    if (!empty($pic_row['user_pic'])) {
        $user_pic = $pic_row['user_pic'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Manage Shops</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="admin.css">
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
        <li>
            <a href="shop_applications_super_admin.php">
                <i class='bx bxs-store'></i>
                <span class="text">Shop Applications</span>
            </a>
        </li>
        <li class="active">
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
                <h1>Manage Shops</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Manage Shops</a></li>
                </ul>
            </div>
        </div>

        <?php if ($feedback !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_type); ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <div class="table-data">
            <div class="order">
                <div class="head">
                    <h3>Approved Shops</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Shop</th>
                                <th>Owner</th>
                                <th>Contact</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($shop_rows)): ?>
                                <?php foreach ($shop_rows as $row): ?>
                                    <?php
                                        $status_badge = 'bg-success';
                                        if ($row['shop_status'] === 'inactive') {
                                            $status_badge = 'bg-secondary';
                                        } elseif ($row['shop_status'] === 'suspended') {
                                            $status_badge = 'bg-danger';
                                        }
                                        $owner_name = trim(($row['user_name'] ?? '') . ' ' . ($row['user_lname'] ?? ''));
                                        if ($owner_name === '') {
                                            $owner_name = 'Unknown';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($row['store_name']); ?></div>
                                            <small class="text-muted">#<?php echo (int)$row['approved_shop_id']; ?> | <?php echo htmlspecialchars($row['store_address']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($owner_name); ?>
                                            <div class="small text-muted">User ID: <?php echo (int)$row['user_id']; ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['business_email']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['business_phone']); ?></small>
                                        </td>
                                        <td><?php echo number_format((int)$row['total_products']); ?></td>
                                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars(ucfirst($row['shop_status'])); ?></span></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($row['updated_at']))); ?></td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <form method="post" class="d-flex gap-2">
                                                    <input type="hidden" name="shop_id" value="<?php echo (int)$row['approved_shop_id']; ?>">
                                                    <select name="shop_status" class="form-select form-select-sm" style="width: 120px;">
                                                        <option value="active" <?php echo $row['shop_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $row['shop_status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                        <option value="suspended" <?php echo $row['shop_status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                    </select>
                                                    <button type="submit" name="update_shop_status" class="btn btn-sm btn-dark">Save</button>
                                                </form>
                                                <a href="../shop.php?shop_id=<?php echo (int)$row['approved_shop_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No shops found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
<script>
function confirmLogout() {
    if (confirm('Are you sure you want to log out?')) {
        window.location.href = '../logout.php';
    }
}
</script>
</body>
</html>

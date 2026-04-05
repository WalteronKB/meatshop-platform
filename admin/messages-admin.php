<?php
include '../connection.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../mrbloginpage.php");
    exit;
}

$current_user_role = $_SESSION['user_type'] ?? '';
if (!in_array($current_user_role, ['super_admin', 'admin'], true)) {
    if ($current_user_role === 'butcher') {
        header("Location: products-admin.php");
    } elseif ($current_user_role === 'cashier') {
        header("Location: orders-admin.php");
    } elseif ($current_user_role === 'rider') {
        header("Location: orders-admin.php");
    } elseif ($current_user_role === 'finance') {
        header("Location: finances-admin.php");
    } else {
        header("Location: ../landpage.php");
    }
    exit;
}

$is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$current_admin_shop_id = null;
if ($current_user_id > 0) {
    $shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
    $shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
    if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
        $shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
        $current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
    }
}

$account_scope_condition = "";
$order_scope_condition = "";
$message_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $account_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        $order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        $message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
        $account_scope_condition = " AND 1 = 0";
        $order_scope_condition = " AND 1 = 0";
        $message_scope_condition = " AND 1 = 0";
    }
}

$feedback = '';
$feedback_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff_user'])) {
    $feedback = 'Staff account creation has been moved to HR & Payroll. Register the employee first, then create the account from payroll.';
    $feedback_type = 'warning';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hide_user'])) {
    $target_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;
    if ($target_user_id <= 0) {
        $feedback = 'Invalid account.';
        $feedback_type = 'danger';
    } elseif ($target_user_id === $current_user_id) {
        $feedback = 'You cannot hide your own account.';
        $feedback_type = 'warning';
    } else {
        $scope_sql = $is_super_admin ? '' : $account_scope_condition;
        $user_query = "SELECT user_id, user_name, user_email, user_type FROM mrb_users WHERE user_id = ? AND user_type != 'deleted'{$scope_sql} LIMIT 1";
        $user_stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($user_stmt, 'i', $target_user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $target_user = $user_result ? mysqli_fetch_assoc($user_result) : null;
        mysqli_stmt_close($user_stmt);

        if (!$target_user) {
            $feedback = 'Account not found in your allowed scope.';
            $feedback_type = 'danger';
        } elseif ($target_user['user_type'] === 'super_admin') {
            $feedback = 'Super admin accounts cannot be hidden here.';
            $feedback_type = 'warning';
        } elseif (!$is_super_admin && !in_array($target_user['user_type'], ['user', 'butcher', 'cashier', 'finance', 'rider'], true)) {
            $feedback = 'You can only hide user or staff accounts from your shop.';
            $feedback_type = 'warning';
        } else {
            $hide_stmt = mysqli_prepare($conn, "UPDATE mrb_users SET user_type = 'deleted' WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($hide_stmt, 'i', $target_user_id);
            if (mysqli_stmt_execute($hide_stmt)) {
                $admin_name = isset($_SESSION['user_name']) ? mysqli_real_escape_string($conn, $_SESSION['user_name']) : 'Admin';
                $target_name = mysqli_real_escape_string($conn, $target_user['user_name'] ?? 'Unknown');
                $target_email = mysqli_real_escape_string($conn, $target_user['user_email'] ?? '');
                $activity_desc = "User account '{$target_name}' ({$target_email}) was hidden by {$admin_name}";
                $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
                mysqli_query($conn, "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('{$activity_desc_escaped}', 'accounts', NOW())");

                $feedback = 'Account hidden successfully.';
                $feedback_type = 'success';
            } else {
                $feedback = 'Failed to hide account.';
                $feedback_type = 'danger';
            }
            mysqli_stmt_close($hide_stmt);
        }
    }
}

$user_pic = 'Images/anonymous.jpg';
$pic_query = "SELECT user_pic FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
$pic_result = mysqli_query($conn, $pic_query);
if ($pic_result && mysqli_num_rows($pic_result) > 0) {
    $pic_row = mysqli_fetch_assoc($pic_result);
    if (!empty($pic_row['user_pic'])) {
        $user_pic = $pic_row['user_pic'];
    }
}

$total_accounts = 0;
$total_admins = 0;
$total_finance = 0;
$total_users = 0;

$stats_query = "SELECT
    COUNT(*) AS total_accounts,
    SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) AS total_admins,
    SUM(CASE WHEN user_type = 'finance' THEN 1 ELSE 0 END) AS total_finance,
    SUM(CASE WHEN user_type = 'user' THEN 1 ELSE 0 END) AS total_users
    FROM mrb_users
    WHERE user_type != 'deleted'";
if (!$is_super_admin) {
    $stats_query .= " AND user_type != 'super_admin'{$account_scope_condition}";
}
$stats_result = mysqli_query($conn, $stats_query);
if ($stats_result && mysqli_num_rows($stats_result) > 0) {
    $stats_row = mysqli_fetch_assoc($stats_result);
    $total_accounts = (int)($stats_row['total_accounts'] ?? 0);
    $total_admins = (int)($stats_row['total_admins'] ?? 0);
    $total_finance = (int)($stats_row['total_finance'] ?? 0);
    $total_users = (int)($stats_row['total_users'] ?? 0);
}

$accounts_query = "SELECT user_id, user_name, user_mname, user_lname, user_email, user_contactnum, user_pic, user_type, user_dateadded, shop_id
                  FROM mrb_users
                  WHERE user_type != 'deleted'";
if (!$is_super_admin) {
    $accounts_query .= " AND user_type != 'super_admin'{$account_scope_condition}";
}
$accounts_query .= " ORDER BY FIELD(user_type, 'super_admin', 'admin', 'finance', 'cashier', 'rider', 'butcher', 'user'), user_name, user_lname";
$accounts_result = mysqli_query($conn, $accounts_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="admin.css">
    <title>Accounts Management</title>
</head>
<body>
<section id="sidebar" class="sidebar">
    <a href="#" class="brand">
        <i class='bx bxs-restaurant'></i>
        <span class="text">Meat Shop</span>
    </a>
    <ul class="side-menu top" style="padding: 0px;">
    <?php if ($is_super_admin): ?>
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
        <li>
            <a href="shops-admin.php">
                <i class='bx bxs-buildings'></i>
                <span class="text">Manage Shops</span>
            </a>
        </li>
        <li class="active">
            <a href="messages-admin.php">
                <i class='bx bxs-user-account'></i>
                <span class="text">Accounts</span>
            </a>
        </li>
    <?php else: ?>
        <li>
            <a href="analytics-admin.php">
                <i class='bx bxs-bar-chart-alt-2'></i>
                <span class="text">Analytics</span>
            </a>
        </li>
        <li>
            <a href="products-admin.php">
                <i class='bx bxs-cart'></i>
                <span class="text">Products</span>
            </a>
        </li>
        <li>
            <a href="orders-admin.php">
                <i class='bx bxs-package'></i>
                <?php
                    $all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
                    $all_unseen_result = mysqli_query($conn, $all_unseen_query);
                    $all_unseen_count = (int)(mysqli_fetch_assoc($all_unseen_result)['all_unseen'] ?? 0);
                ?>
                <span class="text">Orders<?php if ($all_unseen_count > 0) { echo "<span class='ms-2 badge bg-danger'>{$all_unseen_count}</span>"; } ?></span>
            </a>
        </li>
        <li class="active">
            <a href="messages-admin.php">
                <i class='bx bxs-user-account'></i>
                <span class="text">Accounts</span>
            </a>
        </li>
        <li>
            <a href="chat-admin.php">
                <i class='bx bxs-chat'></i>
                <?php
                    $messages_unseen_query = "SELECT COUNT(*) AS messages_unseen FROM mrb_messages WHERE message_type = 'user-chat' AND seen_byadmin = 0{$message_scope_condition}";
                    $messages_unseen_result = mysqli_query($conn, $messages_unseen_query);
                    $messages_unseen_count = (int)(mysqli_fetch_assoc($messages_unseen_result)['messages_unseen'] ?? 0);
                ?>
                <span class="text">Messages<?php if ($messages_unseen_count > 0) { echo "<span class='ms-2 badge bg-danger'>{$messages_unseen_count}</span>"; } ?></span>
            </a>
        </li>
        <li>
            <a href="team_admin.php">
                <i class='bx bxs-group'></i>
                <span class="text">Our Team</span>
            </a>
        </li>
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'finance'): ?>
        <li>
            <a href="payroll-admin.php">
                <i class='bx bxs-wallet'></i>
                <span class="text">HR & Payroll</span>
            </a>
        </li>
        <li>
            <a href="suppliers-admin.php">
                <i class='bx bxs-truck'></i>
                <span class="text">Suppliers</span>
            </a>
        </li>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'finance'): ?>
        <li>
            <a href="finances-admin.php">
                <i class='bx bxs-coin'></i>
                <span class="text">Finances</span>
            </a>
        </li>
        <?php endif; ?>
    <?php endif; ?>
    </ul>

    <ul class="side-menu" style="padding: 0px;">
        <li>
            <a href="<?php echo $is_super_admin ? 'account_super_admin.php' : 'account-admin.php'; ?>">
                <i class="bx bxs-user-circle"></i>
                <span class="text"><?php echo $is_super_admin ? 'My Account' : 'My Shop'; ?></span>
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
        <a href="#" class="nav-link"><?php echo $is_super_admin ? 'Super Admin' : 'Accounts'; ?></a>
        <form action="#" type="hidden">
            <div class="form-input">
                <input type="hidden" placeholder="Search...">
                <button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search'></i></button>
            </div>
        </form>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
        <a href="<?php echo $is_super_admin ? 'account_super_admin.php' : 'account-admin.php'; ?>" class="profile">
            <img src="../<?php echo htmlspecialchars($user_pic); ?>">
        </a>
    </nav>

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Accounts Management</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Accounts</a></li>
                </ul>
            </div>
        </div>

        <?php if (!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)): ?>
            <div class="alert alert-warning">No shop assigned to this account yet.</div>
        <?php endif; ?>

        <?php if ($feedback !== ''): ?>
            <div class="alert alert-<?php echo htmlspecialchars($feedback_type); ?>"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <ul class="box-info">
            <li style="background-color:#e6f7ff;">
                <i class='bx bx-user'></i>
                <span class="text">
                    <h3><?php echo number_format($total_accounts); ?></h3>
                    <p>Total Accounts</p>
                </span>
            </li>
            <li style="background-color:#fffbe6;">
                <i class='bx bx-shield'></i>
                <span class="text">
                    <h3><?php echo number_format($total_admins); ?></h3>
                    <p>Admin Accounts</p>
                </span>
            </li>
            <li style="background-color:#f9f0ff;">
                <i class='bx bxs-coin'></i>
                <span class="text">
                    <h3><?php echo number_format($total_finance); ?></h3>
                    <p>Finance Accounts</p>
                </span>
            </li>
            <li style="background-color:#f0fff4;">
                <i class='bx bx-user-check'></i>
                <span class="text">
                    <h3><?php echo number_format($total_users); ?></h3>
                    <p>User Accounts</p>
                </span>
            </li>
        </ul>

        <div class="table-data">
            <div class="order">
                <div class="head">
                    <h3>All Accounts</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Shop ID</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($accounts_result && mysqli_num_rows($accounts_result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($accounts_result)): ?>
                                <?php
                                    $display_name = trim(($row['user_name'] ?? '') . ' ' . ($row['user_mname'] ?? '') . ' ' . ($row['user_lname'] ?? ''));
                                    if ($display_name === '') {
                                        $display_name = 'Unnamed User';
                                    }
                                    $badge_class = 'bg-success';
                                    $role_label = ucfirst($row['user_type']);
                                    if ($row['user_type'] === 'super_admin') {
                                        $badge_class = 'bg-warning text-dark';
                                        $role_label = 'Super Admin';
                                    } elseif ($row['user_type'] === 'admin') {
                                        $badge_class = 'bg-danger';
                                    } elseif ($row['user_type'] === 'finance') {
                                        $badge_class = 'bg-info text-white';
                                    } elseif ($row['user_type'] === 'cashier') {
                                        $badge_class = 'bg-primary';
                                        $role_label = 'Cashier';
                                    } elseif ($row['user_type'] === 'rider') {
                                        $badge_class = 'bg-dark';
                                        $role_label = 'Rider';
                                    } elseif ($row['user_type'] === 'butcher') {
                                        $badge_class = 'bg-secondary';
                                        $role_label = 'Butcher';
                                    }
                                    $can_hide = ($row['user_type'] !== 'super_admin') && ((int)$row['user_id'] !== $current_user_id) && ($is_super_admin || in_array($row['user_type'], ['user', 'butcher', 'cashier', 'finance', 'rider'], true));
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="../<?php echo htmlspecialchars(!empty($row['user_pic']) ? $row['user_pic'] : 'Images/anonymous.jpg'); ?>" alt="avatar" class="rounded-circle" style="width:38px; height:38px; object-fit:cover;">
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($display_name); ?></div>
                                                <small class="text-muted">ID: <?php echo (int)$row['user_id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></td>
                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($role_label); ?></span></td>
                                    <td><?php echo isset($row['shop_id']) && (int)$row['shop_id'] > 0 ? (int)$row['shop_id'] : '-'; ?></td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['user_dateadded']))); ?></td>
                                    <td>
                                        <?php if ($can_hide): ?>
                                            <form method="post" onsubmit="return confirm('Hide this account?');">
                                                <input type="hidden" name="target_user_id" value="<?php echo (int)$row['user_id']; ?>">
                                                <button type="submit" name="hide_user" class="btn btn-sm btn-warning text-dark">Hide Account</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">No action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No accounts found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
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

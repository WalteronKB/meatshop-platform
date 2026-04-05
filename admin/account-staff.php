<?php
include '../connection.php';
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../mrbloginpage.php");
    exit;
}

$current_user_role = $_SESSION['user_type'] ?? '';
$allowed_staff_roles = ['butcher', 'cashier', 'finance', 'rider'];
if (!in_array($current_user_role, $allowed_staff_roles, true)) {
    if ($current_user_role === 'admin' || $current_user_role === 'super_admin') {
        header("Location: account-admin.php");
    } else {
        header("Location: ../landpage.php");
    }
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../mrbloginpage.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$user_sql = "SELECT user_id, user_name, user_mname, user_lname, user_email, user_contactnum, user_pic, user_type FROM mrb_users WHERE user_id = ? LIMIT 1";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$row = $user_result ? mysqli_fetch_assoc($user_result) : null;
mysqli_stmt_close($user_stmt);

if (!$row) {
    echo "User not found.";
    exit;
}

$user_pic = !empty($row['user_pic']) ? $row['user_pic'] : 'Images/anonymous.jpg';
$role_label = ucfirst((string)($row['user_type'] ?? 'staff'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../usersetting.css">
</head>
<body>
<section id="sidebar" class="sidebar">
    <a href="#" class="brand">
        <i class='bx bxs-restaurant'></i>
        <span class="text">Meat Shop</span>
    </a>
    <ul class="side-menu top" style="padding: 0px;">
        <?php if ($current_user_role === 'butcher'): ?>
            <li><a href="products-admin.php"><i class='bx bxs-cart'></i><span class="text">Products</span></a></li>
            <li><a href="suppliers-admin.php"><i class='bx bxs-truck'></i><span class="text">Suppliers</span></a></li>
        <?php elseif ($current_user_role === 'cashier'): ?>
            <li><a href="orders-admin.php"><i class='bx bxs-package'></i><span class="text">Orders</span></a></li>
        <?php elseif ($current_user_role === 'rider'): ?>
            <li><a href="orders-admin.php"><i class='bx bxs-package'></i><span class="text">Orders</span></a></li>
            <li><a href="chat-admin.php"><i class='bx bxs-chat'></i><span class="text">Messages</span></a></li>
        <?php elseif ($current_user_role === 'finance'): ?>
            <li><a href="finances-admin.php"><i class='bx bxs-coin'></i><span class="text">Finances</span></a></li>
        <?php endif; ?>
    </ul>

    <ul class="side-menu" style="padding: 0px;">
        <li class="active"><a href="account-staff.php"><i class="bx bxs-user-circle"></i><span class="text">My Account</span></a></li>
        <li><a href="javascript:void(0);" onclick="confirmLogout()"><i class='bx bx-power-off'></i><span class="text">Logout</span></a></li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <a href="#" class="nav-link">My Account</a>
        <form action="#" type="hidden"><div class="form-input"><input type="hidden"><button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search'></i></button></div></form>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
        <a href="account-staff.php" class="profile"><img src="../<?php echo htmlspecialchars($user_pic); ?>"></a>
    </nav>

    <main style="padding: 0px;">
        <div class="container" style="margin-top: 20px;">
            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Account updated successfully.
                    <?php if (isset($_GET['password_msg'])): ?>
                        <div><?php echo htmlspecialchars((string)$_GET['password_msg']); ?></div>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <img src="../<?php echo htmlspecialchars($user_pic); ?>" class="img-fluid rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 5px solid #f1f1f1;">
                            <h5 class="mt-3 mb-1"><?php echo htmlspecialchars(trim(($row['user_name'] ?? '') . ' ' . ($row['user_mname'] ?? '') . ' ' . ($row['user_lname'] ?? ''))); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars((string)($row['user_email'] ?? '')); ?></p>
                            <span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($role_label); ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-3 red-text">Edit Account</h6>
                            <form method="post" action="update_profile.php" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">First Name</label>
                                        <input type="text" required class="form-control" name="edit_name" value="<?php echo htmlspecialchars((string)($row['user_name'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" name="edit_mname" value="<?php echo htmlspecialchars((string)($row['user_mname'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" required class="form-control" name="edit_lname" value="<?php echo htmlspecialchars((string)($row['user_lname'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" readonly class="form-control" value="<?php echo htmlspecialchars((string)($row['user_email'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Contact</label>
                                        <input type="text" readonly class="form-control" value="<?php echo htmlspecialchars((string)($row['user_contactnum'] ?? '')); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Profile Picture</label>
                                        <input type="file" class="form-control" name="profile_pic" accept=".jpg,.jpeg,.png,.gif">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm_password">
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn red-bg text-light">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
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

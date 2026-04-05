<?php
	include '../connection.php';
?>
<?php
	session_start();
	if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: ../mrbloginpage.php");
		exit;
	}
?>
<?php
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../mrbloginpage.php");
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $action_error = '';

    $user_sql = "SELECT user_id, user_name, user_mname, user_lname, user_email, user_pic, user_type, shop_id FROM mrb_users WHERE user_id = ? LIMIT 1";
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

    $current_shop_id = isset($row['shop_id']) ? (int) $row['shop_id'] : 0;
    $current_user_role = $row['user_type'] ?? '';
    $is_super_admin = isset($row['user_type']) && $row['user_type'] === 'super_admin';
	$is_admin = $current_user_role === 'admin';
	$is_finance = $current_user_role === 'finance';
	$is_butcher = $current_user_role === 'butcher';
	$is_cashier = $current_user_role === 'cashier';
	$is_rider = $current_user_role === 'rider';
	$account_page = $is_admin ? 'account-admin.php' : 'account-staff.php';
	$order_scope_condition = "";
	$message_scope_condition = "";
	if (!$is_super_admin) {
		if ($current_shop_id > 0) {
			$order_scope_condition = " AND shop_id = {$current_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_shop_id}";
		} else {
			$order_scope_condition = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
		}
	}

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_shop'])) {
        $requested_shop_id = isset($_POST['shop_id']) ? (int) $_POST['shop_id'] : 0;

		if (!$is_admin) {
			$action_error = 'Only shop owner admin can close the shop.';
		} elseif ($is_super_admin) {
            $action_error = 'Super admin shop cannot be closed from this page.';
        } elseif ($current_shop_id <= 0 || $requested_shop_id !== $current_shop_id) {
            $action_error = 'Invalid shop request.';
        } else {
            mysqli_begin_transaction($conn);

            $close_shop_ok = true;
            $downgrade_ok = true;

            $close_sql = "UPDATE approved_shops SET shop_status = 'inactive', updated_at = NOW() WHERE approved_shop_id = ?";
            $close_stmt = mysqli_prepare($conn, $close_sql);
            if ($close_stmt) {
                mysqli_stmt_bind_param($close_stmt, 'i', $current_shop_id);
                $close_shop_ok = mysqli_stmt_execute($close_stmt);
                mysqli_stmt_close($close_stmt);
            } else {
                $close_shop_ok = false;
            }

            $downgrade_sql = "UPDATE mrb_users SET user_type = 'user', shop_id = NULL WHERE shop_id = ? AND user_type IN ('admin','finance')";
            $downgrade_stmt = mysqli_prepare($conn, $downgrade_sql);
            if ($downgrade_stmt) {
                mysqli_stmt_bind_param($downgrade_stmt, 'i', $current_shop_id);
                $downgrade_ok = mysqli_stmt_execute($downgrade_stmt);
                mysqli_stmt_close($downgrade_stmt);
            } else {
                $downgrade_ok = false;
            }

            if ($close_shop_ok && $downgrade_ok) {
                mysqli_commit($conn);
                $_SESSION['user_type'] = 'user';
                $_SESSION['admin_logged_in'] = false;
                header("Location: ../usersetting.php?shop_closed=true");
                exit;
            }

            mysqli_rollback($conn);
            $action_error = 'Failed to close shop. Please try again.';
        }
    }

    $shop = null;
    if ($current_shop_id > 0) {
        $shop_sql = "SELECT * FROM approved_shops WHERE approved_shop_id = ? LIMIT 1";
        $shop_stmt = mysqli_prepare($conn, $shop_sql);
        mysqli_stmt_bind_param($shop_stmt, 'i', $current_shop_id);
        mysqli_stmt_execute($shop_stmt);
        $shop_result = mysqli_stmt_get_result($shop_stmt);
        $shop = $shop_result ? mysqli_fetch_assoc($shop_result) : null;
        mysqli_stmt_close($shop_stmt);
    }

    if (!$shop) {
        $fallback_shop_sql = "SELECT * FROM approved_shops WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1";
        $fallback_stmt = mysqli_prepare($conn, $fallback_shop_sql);
        mysqli_stmt_bind_param($fallback_stmt, 'i', $user_id);
        mysqli_stmt_execute($fallback_stmt);
        $fallback_result = mysqli_stmt_get_result($fallback_stmt);
        $shop = $fallback_result ? mysqli_fetch_assoc($fallback_result) : null;
        mysqli_stmt_close($fallback_stmt);
    }

    $user_pic = !empty($row['user_pic']) ? $row['user_pic'] : 'Images/anonymous.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
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
		<?php if ($is_cashier): ?>
			<li>
				<a href="orders-admin.php">
				<i class='bx bxs-package'></i>
				<?php
					$all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
					$all_unseen_result = mysqli_query($conn, $all_unseen_query);
					$all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];
				?>
				<span class="text">Orders<?php if($all_unseen_count > 0) { echo "<span class='ms-2 badge bg-danger'>$all_unseen_count</span>"; } ?></span>
				</a>
			</li>
		<?php elseif ($is_rider): ?>
			<li>
				<a href="orders-admin.php">
				<i class='bx bxs-package'></i>
				<?php
					$all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
					$all_unseen_result = mysqli_query($conn, $all_unseen_query);
					$all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];
				?>
				<span class="text">Orders<?php if($all_unseen_count > 0) { echo "<span class='ms-2 badge bg-danger'>$all_unseen_count</span>"; } ?></span>
				</a>
			</li>
			<li>
				<a href="chat-admin.php">
				<i class='bx bxs-chat'></i>
				<?php
					$messages_unseen_query = "SELECT COUNT(*) AS messages_unseen FROM mrb_messages WHERE message_type = 'user-chat' AND seen_byadmin = 0{$message_scope_condition}";
					$messages_unseen_result = mysqli_query($conn, $messages_unseen_query);
					$messages_unseen_count = mysqli_fetch_assoc($messages_unseen_result)['messages_unseen'];
				?>
				<span class="text">Messages<?php if($messages_unseen_count > 0) { echo "<span class='ms-2 badge bg-danger'>$messages_unseen_count</span>"; } ?></span>
				</a>
			</li>
		<?php elseif ($is_butcher): ?>
			<li>
				<a href="products-admin.php">
				<i class='bx bxs-cart'></i>
				<span class="text">Products</span>
				</a>
			</li>
			<li>
				<a href="suppliers-admin.php">
				<i class='bx bxs-truck'></i>
				<span class="text">Suppliers</span>
				</a>
			</li>
		<?php elseif ($is_finance): ?>
			<li>
				<a href="finances-admin.php">
				<i class='bx bxs-coin'></i>
				<span class="text">Finances</span>
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
			<li >
				<a href="orders-admin.php">
				<i class='bx bxs-package'></i>
				<?php 
					// Get unseen counts for each status
					$all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
					$all_unseen_result = mysqli_query($conn, $all_unseen_query);
					$all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];
					
				?>
				<span class="text">Orders<?php
					if($all_unseen_count > 0) {
							echo "<span class='ms-2 badge bg-danger'>$all_unseen_count</span>";
						}
				?></span>
				</a>
			</li>
			<li>
				<a href="messages-admin.php">
				<i class='bx bxs-user-account'></i>
				<span class="text">Accounts</span>
				</a>
			</li>
			<li>
				<a href="chat-admin.php">
				<i class='bx bxs-chat'></i>
				<?php 
					// Get unseen messages count
					$messages_unseen_query = "SELECT COUNT(*) AS messages_unseen FROM mrb_messages WHERE message_type = 'user-chat' AND seen_byadmin = 0{$message_scope_condition}";
					$messages_unseen_result = mysqli_query($conn, $messages_unseen_query);
					$messages_unseen_count = mysqli_fetch_assoc($messages_unseen_result)['messages_unseen'];
				?>
				<span class="text">Messages<?php
					if($messages_unseen_count > 0) {
						echo "<span class='ms-2 badge bg-danger'>$messages_unseen_count</span>";
					}
				?></span>
				</a>
			</li>
			<li>
				<a href="team_admin.php">
				<i class='bx bxs-group'></i>
				<span class="text">Our Team</span>
				</a>
			</li>
			<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'finance'): ?>
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
			<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'finance'): ?>
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
			<li class="active">
				<a href="<?php echo $account_page; ?>">
				<i class="bx bxs-user-circle"></i>
				<span class="text"><?php echo $is_admin ? 'My Shop' : 'My Account'; ?></span>
				</a>
			</li>
			<li>
				<a href="javascript:void(0);" onclick="confirmLogout()">
				<i class='bx bx-power-off'></i>
				<span class="text">Logout</span>
				</a>
			</li>
		</ul>
		</ul>
	</section>
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link">Categories</a>
			<form action="#" type="hidden">
				<div class="form-input">
					<input type="hidden"  placeholder="Search...">
					<button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search' ></i></button>
				</div>
			</form>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			
			<a href="<?php echo $account_page; ?>" class="profile">
				<img src="../<?php echo $user_pic?>">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		 
		<main style="padding: 0px;">
			<div class="container" style="margin-top: 20px;">
				<?php if ($action_error !== ''): ?>
					<div class="alert alert-danger alert-dismissible fade show" role="alert">
						<?php echo htmlspecialchars($action_error); ?>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				<?php endif; ?>

				<?php if ($shop): ?>
					<div class="row g-3">
						<div class="col-lg-4">
							<div class="card h-100">
								<div class="card-body text-center">
									<?php $logo_path = !empty($shop['store_logo_path']) ? '../' . ltrim($shop['store_logo_path'], '/') : '../Images/anonymous.jpg'; ?>
									<img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Store Logo" class="img-fluid rounded-circle" style="width: 130px; height: 130px; object-fit: cover; border: 5px solid #f1f1f1;">
									<h5 class="mt-3 mb-1"><?php echo htmlspecialchars($shop['store_name']); ?></h5>
									<p class="text-muted mb-2">Shop ID: <?php echo (int) $shop['approved_shop_id']; ?></p>
									<?php
										$status = strtolower((string) ($shop['shop_status'] ?? 'inactive'));
										$badge_class = $status === 'active' ? 'bg-success' : ($status === 'suspended' ? 'bg-warning text-dark' : 'bg-secondary');
									?>
									<span class="badge <?php echo $badge_class; ?> fs-6 text-uppercase"><?php echo htmlspecialchars($status); ?></span>

									<?php if ($status === 'active' && !$is_super_admin): ?>
										<form method="post" class="mt-4" onsubmit="return confirm('Close this shop? This will remove admin and finance roles for this store.');">
											<input type="hidden" name="shop_id" value="<?php echo (int) $shop['approved_shop_id']; ?>">
											<button type="submit" name="close_shop" class="btn btn-danger w-100">Close Shop</button>
										</form>
									<?php else: ?>
										<div class="alert alert-info mt-3 mb-0 small">
											Shop is inactive. You can apply again from user settings to reopen it.
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<div class="col-lg-8">
							<div class="card h-100">
								<div class="card-body">
									<h6 class="mb-3 red-text">Shop Details</h6>
									<div class="row g-3">
										<div class="col-md-6">
											<label class="form-label">Business Email</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars($shop['business_email']); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">Business Phone</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars($shop['business_phone']); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">Business Permit No.</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars($shop['business_permit_no']); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">TIN No.</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars((string) ($shop['tin_no'] ?? '')); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">Operating Hours</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars($shop['operating_hours']); ?>">
										</div>
										<div class="col-md-6">
											<label class="form-label">Delivery Areas</label>
											<input type="text" readonly class="form-control" value="<?php echo htmlspecialchars((string) ($shop['delivery_areas'] ?? '')); ?>">
										</div>
										<div class="col-12">
											<label class="form-label">Store Address</label>
											<textarea readonly class="form-control" rows="2"><?php echo htmlspecialchars($shop['store_address']); ?></textarea>
										</div>
										<div class="col-12">
											<label class="form-label">Store Description</label>
											<textarea readonly class="form-control" rows="3"><?php echo htmlspecialchars($shop['store_description']); ?></textarea>
										</div>
									</div>

									<?php if (!empty($shop['address_iframe'])): ?>
										<?php
											$iframe_src = trim((string) $shop['address_iframe']);
											if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $iframe_src, $matches)) {
												$iframe_src = $matches[1];
											}
										?>
										<div class="mt-3">
											<label class="form-label">Map Preview</label>
											<iframe src="<?php echo htmlspecialchars($iframe_src); ?>" width="100%" height="280" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				<?php else: ?>
					<div class="alert alert-warning">
						No shop details found for this account. Apply again from user settings to open or reopen your shop.
					</div>
				<?php endif; ?>
			</div>
		</main>
	</section>
	
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const modals = document.querySelectorAll('.modal');
			const sidebar = document.getElementById('sidebar');
			
			modals.forEach(modal => {
				modal.addEventListener('show.bs.modal', function () {
				// On mobile, hide sidebar when modal opens
				if (window.innerWidth <= 768) {
					sidebar.classList.add('hide');
				}
				});
			});
			});
	</script>
	<script>
		function confirmLogout() {
			if (confirm("Are you sure you want to log out?")) {
				window.location.href = "../logout.php";
			}
		}
	</script>
	
	<script src="script.js"></script>

	<?php include 'toast-notification.php'; ?>
</body>
</html>
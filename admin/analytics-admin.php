<?php
	include '../connection.php';
?>
<?php
	session_start();
	if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: ../mrbloginpage.php");
		exit;
	}

	$current_user_role = $_SESSION['user_type'] ?? '';
	if (!in_array($current_user_role, ['super_admin', 'admin'], true)) {
		if ($current_user_role === 'butcher') {
			header("Location: products-admin.php");
		} elseif ($current_user_role === 'cashier') {
			header("Location: orders-admin.php");
		} elseif ($current_user_role === 'finance') {
			header("Location: finances-admin.php");
		} else {
			header("Location: ../landpage.php");
		}
		exit;
	}

	$is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
	$current_admin_shop_id = null;
	$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
	if ($current_user_id > 0) {
		$shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
		$shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
		if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
			$shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
			$current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
		}
	}

	$order_scope_condition = "";
	$message_scope_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
		} else {
			$order_scope_condition = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
	<link rel="stylesheet" href="admin.css">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<title>Analytics Dashboard - Meat Shop Admin</title>
	<style>
		.analytics-card {
			background: #fff;
			border-radius: 10px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			padding: 20px;
			margin-bottom: 20px;
		}
		.metric-value {
			font-size: 2rem;
			font-weight: bold;
			color: #C42F01;
		}
		.metric-label {
			color: #666;
			font-size: 0.9rem;
		}
		.chart-container {
			position: relative;
			height: 300px;
		}
		.status-badge {
			padding: 4px 8px;
			border-radius: 15px;
			font-size: 0.8rem;
			font-weight: bold;
		}
		.badge-pending { background: #fff3cd; color: #856404; }
		.badge-confirmed { background: #d1ecf1; color: #0c5460; }
		.badge-delivered { background: #d4edda; color: #155724; }
		.badge-cancelled { background: #f8d7da; color: #721c24; }
		.dss-panel {
			background: linear-gradient(135deg, #fff7f2 0%, #ffffff 100%);
			border: 1px solid #f0d8cc;
			border-left: 5px solid #C42F01;
			border-radius: 12px;
			padding: 16px;
			margin-bottom: 24px;
		}
		.dss-title {
			font-size: 1.15rem;
			font-weight: 700;
			margin-bottom: 12px;
			color: #7d260b;
		}
		.dss-subcard {
			background: #fff;
			border: 1px solid #eee;
			border-radius: 10px;
			padding: 12px;
			height: 100%;
		}
		.dss-subtitle {
			font-size: 0.9rem;
			font-weight: 700;
			margin-bottom: 8px;
			color: #333;
		}
		.dss-list {
			margin: 0;
			padding-left: 1rem;
		}
		.dss-list li {
			margin-bottom: 6px;
			font-size: 0.92rem;
		}
	</style>
</head>
<body>
	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-restaurant'></i>
			<span class="text">Meat Shop</span>
		</a>
		<?php if ($is_super_admin): ?>
		<ul class="side-menu top" style="padding: 0px;">
			<li class="active">
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
			<li class="">
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
		<?php else: ?>
		<ul class="side-menu top" style="padding: 0px;">
			<li class="active">
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
			<li class="">
				<a href="orders-admin.php">
				<i class='bx bxs-package'></i>
				<?php 
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
			<li class="">
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
		</ul>

		<ul class="side-menu" style="padding: 0px;">
			<li>
				<a href="account-admin.php">
				<i class="bx bxs-user-circle"></i>
				<span class="text">My Shop</span>
				</a>
			</li>
			<li>
				<a href="javascript:void(0);" onclick="confirmLogout()">
				<i class='bx bx-power-off'></i>
				<span class="text">Logout</span>
				</a>
			</li>
		</ul>
		<?php endif; ?>
	</section>

	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu'></i>
			<a href="#" class="nav-link">Analytics Dashboard</a>
			<form action="#" type="hidden">
				<div class="form-input">
					<input type="hidden" placeholder="Search...">
					<button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search'></i></button>
				</div>
			</form>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			
			<?php 
				$user_pic = '';
				$query = "SELECT user_pic FROM mrb_users WHERE user_id = '{$_SESSION['user_id']}'";
				$result = mysqli_query($conn, $query);
				if ($result && mysqli_num_rows($result) > 0) {
					$row = mysqli_fetch_assoc($result);
					$user_pic = "../{$row['user_pic']}";
				} else {
					$user_pic = '../Images/anonymous.jpg';
				}
			?>
			<a href="<?php echo $is_super_admin ? 'account_super_admin.php' : 'account-admin.php'; ?>" class="profile">
				<img src="<?php echo $user_pic?>">
			</a>
		</nav>

		<!-- MAIN -->
		<main>
			<div class="head-title">
				<div class="left">
					<h1>Analytics Dashboard</h1>
					<ul class="breadcrumb">
						<li><a href="#">Dashboard</a></li>
						<li><i class='bx bx-chevron-right'></i></li>
						<li><a class="active" href="#">Analytics</a></li>
					</ul>
				</div>
			</div>

			
		<?php
			$welcome_admin_name = trim((string)($_SESSION['user_name'] ?? 'Admin'));
			if ($welcome_admin_name === '') {
				$welcome_admin_name = 'Admin';
			}
		?>
		<div class="alert alert-light border d-flex align-items-center gap-2 mb-3" style="border-left: 4px solid #C42F01 !important;">
			<i class='bx bxs-user-circle' style="color:#C42F01; font-size: 1.3rem;"></i>
			<div><strong>Welcome, Admin <?php echo htmlspecialchars($welcome_admin_name); ?>!</strong></div>
		</div>


			<?php if(!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)): ?>
			<div class="alert alert-warning">No shop assigned to this admin yet.</div>
			<?php endif; ?>

			<?php
			// Analytics data (shop-scoped for admin, global for super admin)
			$order_scope = "";
			$order_scope_on = "";
			$product_scope = "";
			$rating_scope = "";
			if (!$is_super_admin) {
				if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
					$order_scope = " AND mo.shop_id = {$current_admin_shop_id}";
					$order_scope_on = " AND mo.shop_id = {$current_admin_shop_id}";
					$product_scope = " AND mf.shop_id = {$current_admin_shop_id}";
					$rating_scope = " AND mf.shop_id = {$current_admin_shop_id}";
				} else {
					$order_scope = " AND 1 = 0";
					$order_scope_on = " AND 1 = 0";
					$product_scope = " AND 1 = 0";
					$rating_scope = " AND 1 = 0";
				}
			}

			$total_orders_query = "SELECT COUNT(*) as total_orders FROM mrb_orders mo WHERE 1=1 {$order_scope}";
			$total_orders_result = mysqli_query($conn, $total_orders_query);
			$total_orders = (int)(mysqli_fetch_assoc($total_orders_result)['total_orders'] ?? 0);

			$revenue_query = "SELECT SUM(mf.prod_newprice * mo.order_quantity) as total_revenue
						  FROM mrb_orders mo
						  JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
						  WHERE mo.order_status IN ('confirmed', 'delivered')
						  AND mf.prod_type != 'deleted' {$order_scope}{$product_scope}";
			$revenue_result = mysqli_query($conn, $revenue_query);
			$total_revenue = (float)(mysqli_fetch_assoc($revenue_result)['total_revenue'] ?? 0);

			$avg_order_query = "SELECT AVG(mf.prod_newprice * mo.order_quantity) as avg_order_value
						   FROM mrb_orders mo
						   JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
						   WHERE mo.order_status IN ('confirmed', 'delivered')
						   AND mf.prod_type != 'deleted' {$order_scope}{$product_scope}";
			$avg_order_result = mysqli_query($conn, $avg_order_query);
			$avg_order_value = (float)(mysqli_fetch_assoc($avg_order_result)['avg_order_value'] ?? 0);

			$total_users = 0;
			if ($is_super_admin) {
				$total_users_query = "SELECT COUNT(*) as total_users FROM mrb_users WHERE user_type = 'user'";
				$total_users_result = mysqli_query($conn, $total_users_query);
				$total_users = (int)(mysqli_fetch_assoc($total_users_result)['total_users'] ?? 0);
			}

			$avg_rating_query = "SELECT AVG(mc.rating) as avg_rating
						FROM mrb_comments mc
						JOIN mrb_fireex mf ON mc.product_id = mf.prod_id
						WHERE mc.rating > 0
						AND mf.prod_type != 'deleted' {$rating_scope}";
			$avg_rating_result = mysqli_query($conn, $avg_rating_query);
			$avg_rating = (float)(mysqli_fetch_assoc($avg_rating_result)['avg_rating'] ?? 0);

			$order_status_query = "SELECT mo.order_status, COUNT(*) as count FROM mrb_orders mo WHERE 1=1 {$order_scope} GROUP BY mo.order_status";
			$order_status_result = mysqli_query($conn, $order_status_query);
			$order_status_data = [];
			while($row = mysqli_fetch_assoc($order_status_result)) {
				$order_status_data[$row['order_status']] = (int)$row['count'];
			}

			$payment_method_query = "SELECT mo.order_paymentmethod, COUNT(*) as count FROM mrb_orders mo WHERE 1=1 {$order_scope} GROUP BY mo.order_paymentmethod";
			$payment_method_result = mysqli_query($conn, $payment_method_query);
			$payment_method_data = [];
			while($row = mysqli_fetch_assoc($payment_method_result)) {
				$payment_method_data[$row['order_paymentmethod']] = (int)$row['count'];
			}

			$category_performance_query = "SELECT mf.prod_type,
								  COUNT(mo.order_id) as total_orders,
								  SUM(mo.order_quantity) as total_quantity_sold,
								  SUM(mf.prod_newprice * mo.order_quantity) as category_revenue
								  FROM mrb_fireex mf
								  LEFT JOIN mrb_orders mo ON mf.prod_id = mo.product_id
								  AND mo.order_status IN ('confirmed', 'delivered') {$order_scope_on}
								  WHERE mf.prod_type != 'deleted' {$product_scope}
								  GROUP BY mf.prod_type";
			$category_performance_result = mysqli_query($conn, $category_performance_query);
			$category_data = [];
			while($row = mysqli_fetch_assoc($category_performance_result)) {
				$total_category_orders = (int)($row['total_orders'] ?? 0);
				$total_category_revenue = (float)($row['category_revenue'] ?? 0);
				if ($total_category_orders > 0 || $total_category_revenue > 0) {
					$category_data[] = $row;
				}
			}

			$daily_revenue_query = "SELECT DATE(mo.order_dateordered) as order_date,
							   SUM(mf.prod_newprice * mo.order_quantity) as daily_revenue
							   FROM mrb_orders mo
							   JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
							   WHERE mo.order_status IN ('confirmed', 'delivered')
							   AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
							   AND mf.prod_type != 'deleted' {$order_scope}
							   GROUP BY DATE(mo.order_dateordered)
							   ORDER BY order_date";
			$daily_revenue_result = mysqli_query($conn, $daily_revenue_query);
			$daily_revenue_data = [];
			while($row = mysqli_fetch_assoc($daily_revenue_result)) {
				$daily_revenue_data[] = $row;
			}

			$geographic_data = [];
			if ($is_super_admin) {
				$geographic_query = "SELECT user_location, COUNT(*) as user_count
								FROM mrb_users
								WHERE user_location IS NOT NULL AND user_location != ''
								AND user_type = 'user'
								GROUP BY user_location
								ORDER BY user_count DESC
								LIMIT 10";
				$geographic_result = mysqli_query($conn, $geographic_query);
				while($row = mysqli_fetch_assoc($geographic_result)) {
					$geographic_data[] = $row;
				}
			}

			// DSS Insights
			$dss_inventory_alerts = [];
			$dss_sales_insights = [];
			$dss_reorder_suggestions = [];
			$dss_pricing_insights = [];
			$dss_system_insights = [];

			$low_stock_query = "SELECT mf.prod_name, mf.prod_quantity
								FROM mrb_fireex mf
								WHERE mf.prod_type != 'deleted' {$product_scope}
								AND mf.prod_quantity <= 5
								ORDER BY mf.prod_quantity ASC
								LIMIT 5";
			$low_stock_result = mysqli_query($conn, $low_stock_query);
			if ($low_stock_result) {
				while ($low = mysqli_fetch_assoc($low_stock_result)) {
					$dss_inventory_alerts[] = $low['prod_name'] . " is below " . number_format((float)$low['prod_quantity'], 2) . "kg - reorder soon.";
				}
			}

			$top_selling_query = "SELECT mf.prod_name, SUM(mo.order_quantity) AS sold_qty
									FROM mrb_orders mo
									JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
									WHERE mo.order_status IN ('confirmed', 'delivered')
									AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
									AND mf.prod_type != 'deleted' {$order_scope}{$product_scope}
									GROUP BY mo.product_id, mf.prod_name
									ORDER BY sold_qty DESC
									LIMIT 1";
			$top_selling_result = mysqli_query($conn, $top_selling_query);
			$top_selling_row = $top_selling_result ? mysqli_fetch_assoc($top_selling_result) : null;
			if ($top_selling_row && !empty($top_selling_row['prod_name'])) {
				$dss_sales_insights[] = $top_selling_row['prod_name'] . " is your top-selling item.";
			}

			$peak_hour_query = "SELECT HOUR(mo.order_dateordered) AS hour_slot, COUNT(*) AS total_orders
								 FROM mrb_orders mo
								 WHERE mo.order_status IN ('confirmed', 'delivered')
								 AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
								 {$order_scope}
								 GROUP BY HOUR(mo.order_dateordered)
								 ORDER BY total_orders DESC
								 LIMIT 1";
			$peak_hour_result = mysqli_query($conn, $peak_hour_query);
			$peak_hour_row = $peak_hour_result ? mysqli_fetch_assoc($peak_hour_result) : null;
			if ($peak_hour_row && isset($peak_hour_row['hour_slot'])) {
				$hour_slot = (int)$peak_hour_row['hour_slot'];
				$hour_suffix = $hour_slot >= 12 ? 'PM' : 'AM';
				$hour_display = $hour_slot % 12;
				if ($hour_display === 0) {
					$hour_display = 12;
				}
				$dss_system_insights[] = "Peak sales time is " . $hour_display . $hour_suffix . ".";
			}

			$declining_category_query = "SELECT mf.prod_type,
										SUM(CASE WHEN mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN mo.order_quantity ELSE 0 END) AS recent_qty,
										SUM(CASE WHEN mo.order_dateordered < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
												 AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
												 THEN mo.order_quantity ELSE 0 END) AS previous_qty
									 FROM mrb_orders mo
									 JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
									 WHERE mo.order_status IN ('confirmed', 'delivered')
									 AND mf.prod_type != 'deleted' {$order_scope}{$product_scope}
									 GROUP BY mf.prod_type
									 HAVING SUM(CASE WHEN mo.order_dateordered < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
															AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
															THEN mo.order_quantity ELSE 0 END) > 0
										AND SUM(CASE WHEN mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
															THEN mo.order_quantity ELSE 0 END)
											< SUM(CASE WHEN mo.order_dateordered < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
															  AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
															  THEN mo.order_quantity ELSE 0 END)
									 ORDER BY (
										SUM(CASE WHEN mo.order_dateordered < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
												 AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
												 THEN mo.order_quantity ELSE 0 END)
										-
										SUM(CASE WHEN mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
												 THEN mo.order_quantity ELSE 0 END)
									 ) DESC
									 LIMIT 1";
			$declining_result = mysqli_query($conn, $declining_category_query);
			$declining_row = $declining_result ? mysqli_fetch_assoc($declining_result) : null;
			if ($declining_row && !empty($declining_row['prod_type'])) {
				$dss_system_insights[] = ucfirst($declining_row['prod_type']) . " sales are declining.";
			}

			$reorder_query = "SELECT mf.prod_name,
									SUM(mo.order_quantity) AS week_demand,
									CEIL(SUM(mo.order_quantity) * 1.20) AS suggested_order_qty
								 FROM mrb_orders mo
								 JOIN mrb_fireex mf ON mo.product_id = mf.prod_id
								 WHERE mo.order_status IN ('confirmed', 'delivered')
								 AND mo.order_dateordered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
								 AND mf.prod_type != 'deleted' {$order_scope}{$product_scope}
								 GROUP BY mo.product_id, mf.prod_name
								 ORDER BY week_demand DESC
								 LIMIT 2";
			$reorder_result = mysqli_query($conn, $reorder_query);
			if ($reorder_result) {
				$reorder_summary_items = [];
				while ($reorder_row = mysqli_fetch_assoc($reorder_result)) {
					$dss_reorder_suggestions[] = "Order " . (int)$reorder_row['suggested_order_qty'] . "kg of " . $reorder_row['prod_name'] . " based on last 7 days demand.";
					$reorder_summary_items[] = (int)$reorder_row['suggested_order_qty'] . "kg " . $reorder_row['prod_name'];
				}
				if (!empty($reorder_summary_items)) {
					$dss_system_insights[] = "Suggested reorder: " . implode(', ', $reorder_summary_items) . ".";
				}
			}

			$pricing_query = "SELECT mf.prod_name, mf.prod_newprice, p.avg_price
								 FROM mrb_fireex mf
								 JOIN (
									 SELECT prod_type, AVG(prod_newprice) AS avg_price
									 FROM mrb_fireex
									 WHERE prod_type != 'deleted'" . (!$is_super_admin && $current_admin_shop_id !== null && $current_admin_shop_id > 0 ? " AND shop_id = {$current_admin_shop_id}" : "") . "
									 GROUP BY prod_type
								 ) p ON p.prod_type = mf.prod_type
								 WHERE mf.prod_type != 'deleted' {$product_scope}
								 AND mf.prod_newprice > (p.avg_price * 1.10)
								 ORDER BY (mf.prod_newprice - p.avg_price) DESC
								 LIMIT 1";
			$pricing_result = mysqli_query($conn, $pricing_query);
			$pricing_row = $pricing_result ? mysqli_fetch_assoc($pricing_result) : null;
			if ($pricing_row && !empty($pricing_row['prod_name'])) {
				$dss_pricing_insights[] = $pricing_row['prod_name'] . " price is higher than average. Consider lowering from PHP " . number_format((float)$pricing_row['prod_newprice'], 2) . ".";
			}

			if (!empty($dss_inventory_alerts)) {
				$dss_system_insights[] = $dss_inventory_alerts[0];
			}
			?>

			<?php if($total_orders === 0): ?>
			<div class="alert alert-info">No data yet.</div>
			<?php endif; ?>

			<!-- Summary Cards -->
			<div class="row mb-4">
				<div class="col-md-3">
					<div class="analytics-card text-center">
						<div class="metric-value"><?php echo number_format($total_orders); ?></div>
						<div class="metric-label">Total Orders</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="analytics-card text-center">
						<div class="metric-value">₱<?php echo number_format($total_revenue, 2); ?></div>
						<div class="metric-label">Total Revenue</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="analytics-card text-center">
						<div class="metric-value">₱<?php echo number_format($avg_order_value, 2); ?></div>
						<div class="metric-label">Average Order Value</div>
					</div>
				</div>
				<?php if($is_super_admin): ?>
				<div class="col-md-3">
					<div class="analytics-card text-center">
						<div class="metric-value"><?php echo number_format($total_users); ?></div>
						<div class="metric-label">Registered Users</div>
					</div>
				</div>
				<?php else: ?>
				<div class="col-md-3">
					<div class="analytics-card text-center">
						<div class="metric-value"><?php echo number_format(array_sum($order_status_data)); ?></div>
						<div class="metric-label">Total Tracked Orders</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="row mb-4">
				<div class="col-md-12">
					<div class="analytics-card text-center">
						<div class="metric-value"><?php echo number_format($avg_rating, 1); ?>/5.0 ⭐</div>
						<div class="metric-label">Average Product Rating</div>
					</div>
				</div>
			</div>

			<div class="dss-panel">
				<div class="dss-title">System Insights</div>
				<div class="row g-3">
					<div class="col-md-6 col-lg-3">
						<div class="dss-subcard">
							<div class="dss-subtitle">Inventory Alerts</div>
							<ul class="dss-list">
								<?php if(!empty($dss_inventory_alerts)): ?>
									<?php foreach($dss_inventory_alerts as $insight): ?>
										<li><?php echo htmlspecialchars($insight); ?></li>
									<?php endforeach; ?>
								<?php else: ?>
									<li>No low stock alerts right now.</li>
								<?php endif; ?>
							</ul>
						</div>
					</div>

					<div class="col-md-6 col-lg-3">
						<div class="dss-subcard">
							<div class="dss-subtitle">Sales-Based DSS</div>
							<ul class="dss-list">
								<?php if(!empty($dss_sales_insights)): ?>
									<?php foreach($dss_sales_insights as $insight): ?>
										<li><?php echo htmlspecialchars($insight); ?></li>
									<?php endforeach; ?>
								<?php else: ?>
									<li>Insufficient sales data yet.</li>
								<?php endif; ?>
							</ul>
						</div>
					</div>

					<div class="col-md-6 col-lg-3">
						<div class="dss-subcard">
							<div class="dss-subtitle">Reorder Recommendation</div>
							<ul class="dss-list">
								<?php if(!empty($dss_reorder_suggestions)): ?>
									<?php foreach($dss_reorder_suggestions as $insight): ?>
										<li><?php echo htmlspecialchars($insight); ?></li>
									<?php endforeach; ?>
								<?php else: ?>
									<li>No reorder recommendations yet.</li>
								<?php endif; ?>
							</ul>
						</div>
					</div>

					<div class="col-md-6 col-lg-3">
						<div class="dss-subcard">
							<div class="dss-subtitle">Pricing Insights</div>
							<ul class="dss-list">
								<?php if(!empty($dss_pricing_insights)): ?>
									<?php foreach($dss_pricing_insights as $insight): ?>
										<li><?php echo htmlspecialchars($insight); ?></li>
									<?php endforeach; ?>
								<?php else: ?>
									<li>Pricing levels are currently within expected range.</li>
								<?php endif; ?>
							</ul>
						</div>
					</div>
				</div>

				<div class="dss-subcard mt-3">
					<div class="dss-subtitle">System Insights</div>
					<ul class="dss-list mb-0">
						<?php if(!empty($dss_system_insights)): ?>
							<?php foreach($dss_system_insights as $insight): ?>
								<li><?php echo htmlspecialchars($insight); ?></li>
							<?php endforeach; ?>
						<?php else: ?>
							<li>No system insights available yet.</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>

			<!-- Charts Section -->
			<div class="row">
				<!-- Order Status Breakdown -->
				<div class="col-md-6">
					<div class="analytics-card">
						<h5>Order Status Breakdown</h5>
						<div class="chart-container">
							<?php if(!empty($order_status_data)): ?>
							<canvas id="orderStatusChart"></canvas>
							<?php else: ?>
							<div class="text-muted">No data yet</div>
							<?php endif; ?>
						</div>
						<div class="mt-3">
							<?php foreach($order_status_data as $status => $count): ?>
								<span class="status-badge badge-<?php echo strtolower($status); ?> me-2">
									<?php echo ucfirst($status); ?>: <?php echo $count; ?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Payment Method Distribution -->
				<div class="col-md-6">
					<div class="analytics-card">
						<h5>Payment Method Distribution</h5>
						<div class="chart-container">
							<?php if(!empty($payment_method_data)): ?>
							<canvas id="paymentMethodChart"></canvas>
							<?php else: ?>
							<div class="text-muted">No data yet</div>
							<?php endif; ?>
						</div>
						<div class="mt-3">
							<?php foreach($payment_method_data as $method => $count): ?>
								<div class="d-flex justify-content-between">
									<span><?php echo $method; ?></span>
									<span><strong><?php echo $count; ?> orders</strong></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<!-- Product Category Performance -->
				<div class="col-md-8">
					<div class="analytics-card">
						<h5>Product Performance by Category</h5>
						<div class="chart-container">
							<?php if(!empty($category_data)): ?>
							<canvas id="categoryPerformanceChart"></canvas>
							<?php else: ?>
							<div class="text-muted">No data yet</div>
							<?php endif; ?>
						</div>
						<div class="mt-3">
							<div class="row">
								<?php foreach($category_data as $category): ?>
									<div class="col-md-4">
										<div class="card bg-light">
											<div class="card-body text-center">
												<h6><?php echo $category['prod_type']; ?></h6>
												<p class="mb-1"><strong><?php echo $category['total_orders']; ?></strong> orders</p>
												<p class="mb-1"><strong><?php echo $category['total_quantity_sold']; ?></strong> items sold</p>
												<p class="mb-0">₱<?php echo number_format($category['category_revenue'], 2); ?></p>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Daily Revenue Trend -->
				<div class="col-md-4">
					<div class="analytics-card">
						<h5>Daily Revenue (Last 7 Days)</h5>
						<div class="chart-container">
							<?php if(!empty($daily_revenue_data)): ?>
							<canvas id="dailyRevenueChart"></canvas>
							<?php else: ?>
							<div class="text-muted">No data yet</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<?php if($is_super_admin): ?>
			<!-- Geographic Distribution -->
			<div class="row">
				<div class="col-md-12">
					<div class="analytics-card">
						<h5>Geographic Distribution - Top User Locations</h5>
						<div class="chart-container">
							<?php if(!empty($geographic_data)): ?>
							<canvas id="geographicChart"></canvas>
							<?php else: ?>
							<div class="text-muted">No data yet</div>
							<?php endif; ?>
						</div>
						<div class="mt-3">
							<div class="row">
								<?php foreach($geographic_data as $location): ?>
									<div class="col-md-2 text-center mb-3">
										<div class="card bg-light">
											<div class="card-body">
												<h6><?php echo htmlspecialchars($location['user_location']); ?></h6>
												<p class="mb-0"><strong><?php echo $location['user_count']; ?></strong> users</p>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

		</main>
	</section>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
	<script src="script.js"></script>
	
	<script>
		const orderStatusCanvas = document.getElementById('orderStatusChart');
		if (orderStatusCanvas) {
			new Chart(orderStatusCanvas.getContext('2d'), {
				type: 'doughnut',
				data: {
					labels: <?php echo json_encode(array_keys($order_status_data)); ?>,
					datasets: [{
						data: <?php echo json_encode(array_values($order_status_data)); ?>,
						backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
						borderWidth: 2
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom' } }
				}
			});
		}

		const paymentMethodCanvas = document.getElementById('paymentMethodChart');
		if (paymentMethodCanvas) {
			new Chart(paymentMethodCanvas.getContext('2d'), {
				type: 'pie',
				data: {
					labels: <?php echo json_encode(array_keys($payment_method_data)); ?>,
					datasets: [{
						data: <?php echo json_encode(array_values($payment_method_data)); ?>,
						backgroundColor: ['#C42F01', '#ff6b35'],
						borderWidth: 2
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { position: 'bottom' } }
				}
			});
		}

		const categoryCanvas = document.getElementById('categoryPerformanceChart');
		if (categoryCanvas) {
			new Chart(categoryCanvas.getContext('2d'), {
				type: 'bar',
				data: {
					labels: <?php echo json_encode(array_column($category_data, 'prod_type')); ?>,
					datasets: [{
						label: 'Revenue (₱)',
						data: <?php echo json_encode(array_column($category_data, 'category_revenue')); ?>,
						backgroundColor: 'rgba(196, 47, 1, 0.7)',
						borderColor: '#C42F01',
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function(value) {
									return '₱' + value.toLocaleString();
								}
							}
						}
					}
				}
			});
		}

		const dailyRevenueCanvas = document.getElementById('dailyRevenueChart');
		if (dailyRevenueCanvas) {
			new Chart(dailyRevenueCanvas.getContext('2d'), {
				type: 'line',
				data: {
					labels: <?php echo json_encode(array_column($daily_revenue_data, 'order_date')); ?>,
					datasets: [{
						label: 'Daily Revenue',
						data: <?php echo json_encode(array_column($daily_revenue_data, 'daily_revenue')); ?>,
						borderColor: '#C42F01',
						backgroundColor: 'rgba(196, 47, 1, 0.1)',
						tension: 0.4,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function(value) {
									return '₱' + value.toLocaleString();
								}
							}
						}
					}
				}
			});
		}

		const geographicCanvas = document.getElementById('geographicChart');
		if (geographicCanvas) {
			new Chart(geographicCanvas.getContext('2d'), {
				type: 'bar',
				data: {
					labels: <?php echo json_encode(array_column($geographic_data, 'user_location')); ?>,
					datasets: [{
						label: 'Number of Users',
						data: <?php echo json_encode(array_column($geographic_data, 'user_count')); ?>,
						backgroundColor: 'rgba(196, 47, 1, 0.7)',
						borderColor: '#C42F01',
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					indexAxis: 'y',
					scales: { x: { beginAtZero: true } }
				}
			});
		}

		// Logout confirmation function
		function confirmLogout() {
			if(confirm('Are you sure you want to logout?')) {
				window.location.href = '../logout.php';
			}
		}
	</script>
</body>
</html>
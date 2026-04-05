<?php
	include '../connection.php';
	session_start();
	
	// Check if admin is logged in
	if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: ../mrbloginpage.php");
		exit;
	}
	
	// Check if user is Super Admin
	if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
		// Redirect non-super admins to analytics page
		header("Location: analytics-admin.php");
		exit;
	}
	
	// Additional security: Verify session is still valid
	if(!isset($_SESSION['user_id'])) {
		session_destroy();
		header("Location: ../mrbloginpage.php");
		exit;
	}

	function redactConfidentialIds($text) {
		$sanitized = (string)$text;
		$sanitized = preg_replace('/#\d+\b/', '#[CONFIDENTIAL]', $sanitized);
		$sanitized = preg_replace('/\b(id|order\s*id|user\s*id|shop\s*id|account\s*id|employee\s*id)\s*[:#-]?\s*\d+\b/i', '$1: [CONFIDENTIAL]', $sanitized);
		$sanitized = preg_replace('/\b\d{6,}\b/', '[CONFIDENTIAL]', $sanitized);
		return $sanitized;
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
	<title>Super Admin - Activity Logs</title>
	<style>
		.nav-tabs .nav-link {
			color: #6B3410;
			border: none;
			border-bottom: 3px solid transparent;
		}
		
		.nav-tabs .nav-link:hover {
			border-bottom: 3px solid #6B3410;
			color: #6B3410;
		}
		
		.nav-tabs .nav-link.active {
			border-bottom: 3px solid #6B3410;
			color: #6B3410;
			font-weight: 600;
			background-color: transparent;
		}
		
		.activity-log-item {
			padding: 15px;
			border-left: 4px solid #6B3410;
			margin-bottom: 15px;
			background: #f8f9fa;
			border-radius: 4px;
		}
		
		.activity-log-item .timestamp {
			color: #6c757d;
			font-size: 0.875rem;
		}
		
		.activity-log-item .action {
			font-weight: 600;
			color: #6B3410;
		}
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
			<li class="active">
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
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link">Super Admin</a>
			<form action="#" type="hidden">
				<div class="form-input">
					<input type="hidden"  placeholder="Search...">
					<button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search' ></i></button>
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
					$user_pic =  "{$row['user_pic']}";
				} else {
					$user_pic = 'Images/anonymous.jpg';
				}
			
			?>

			<a href="account-admin.php" class="profile">
				<img src="../<?php echo $user_pic?>">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		 
		<main>
		<div class="head-title">
			<div class="left">
			<h1>Activity Logs</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Super Admin</a></li>
			</ul>
			</div>
		</div>

		<!-- Nav Tabs -->
		<ul class="nav nav-tabs mb-4" id="activityTabs" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="true">
					<i class='bx bxs-cart'></i> Products
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="accounts-tab" data-bs-toggle="tab" data-bs-target="#accounts" type="button" role="tab" aria-controls="accounts" aria-selected="false">
					<i class='bx bxs-user-account'></i> Accounts
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="payroll-tab" data-bs-toggle="tab" data-bs-target="#payroll" type="button" role="tab" aria-controls="payroll" aria-selected="false">
					<i class='bx bxs-wallet'></i> HR & Payroll
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="suppliers-tab" data-bs-toggle="tab" data-bs-target="#suppliers" type="button" role="tab" aria-controls="suppliers" aria-selected="false">
					<i class='bx bxs-truck'></i> Suppliers
				</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance" type="button" role="tab" aria-controls="finance" aria-selected="false">
					<i class='bx bxs-coin'></i> Finance
				</button>
			</li>
		</ul>

		<!-- Tab Content -->	
		<div class="tab-content" id="activityTabsContent">
			
			<!-- PRODUCTS TAB -->
			<div class="tab-pane fade show active" id="products" role="tabpanel" aria-labelledby="products-tab">
				<div class="table-data">
					<div class="order">
						<div class="head">
							<h3><i class='bx bxs-cart'></i> Product Activity Logs</h3>
						</div>
						<?php
							$products_query = "SELECT * FROM mrb_activity_log WHERE activity_type = 'products' ORDER BY id DESC LIMIT 50";
							$products_result = mysqli_query($conn, $products_query);
							
							if (mysqli_num_rows($products_result) > 0) {
								while ($log = mysqli_fetch_assoc($products_result)) {
								// Check if log is about rejection or report
								$is_red = (stripos($log['activity_desc'], 'rejected') !== false || stripos($log['activity_desc'], 'reported') !== false);
								$item_class = $is_red ? "activity-log-item" : "activity-log-item";
								$border_color = $is_red ? "border-left: 4px solid #dc3545;" : "";
								$bg_color = $is_red ? "background: #ffe6e6;" : "";
								$safe_activity_desc = redactConfidentialIds((string)$log['activity_desc']);
							$formatted_date = date('F j, Y g:i A', strtotime($log['created_at']));
							echo "<div class='{$item_class}' style='{$border_color} {$bg_color}'>
									<div class='d-flex justify-content-between align-items-start'>
										<div class='flex-grow-1'>
												<p class='mb-1'>{$safe_activity_desc}</p>
											<small class='timestamp'><i class='bx bx-time-five'></i> {$formatted_date}</small>
											</div>
										</div>
									</div>";
								}
							} else {
								echo "<div class='alert alert-info'>No activity logs found for Products.</div>";
							}
						?>
					</div>
				</div>
			</div>

			<!-- ACCOUNTS TAB -->
			<div class="tab-pane fade" id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
				<div class="table-data">
					<div class="order">
						<div class="head">
							<h3><i class='bx bxs-user-account'></i> Account Activity Logs</h3>
						</div>
						<?php
							$accounts_query = "SELECT * FROM mrb_activity_log WHERE activity_type = 'accounts' ORDER BY id DESC LIMIT 50";
							$accounts_result = mysqli_query($conn, $accounts_query);
							
							if (mysqli_num_rows($accounts_result) > 0) {
								while ($log = mysqli_fetch_assoc($accounts_result)) {
								// Check if log is about rejection or report
								$is_red = (stripos($log['activity_desc'], 'rejected') !== false || stripos($log['activity_desc'], 'reported') !== false);
								$item_class = $is_red ? "activity-log-item" : "activity-log-item";
								$border_color = $is_red ? "border-left: 4px solid #dc3545;" : "";
								$bg_color = $is_red ? "background: #ffe6e6;" : "";
								$safe_activity_desc = redactConfidentialIds((string)$log['activity_desc']);
							$formatted_date = date('F j, Y g:i A', strtotime($log['created_at']));
							echo "<div class='{$item_class}' style='{$border_color} {$bg_color}'>
									<div class='d-flex justify-content-between align-items-start'>
										<div class='flex-grow-1'>
												<p class='mb-1'>{$safe_activity_desc}</p>
											<small class='timestamp'><i class='bx bx-time-five'></i> {$formatted_date}</small>
											</div>
										</div>
									</div>";
								}
							} else {
								echo "<div class='alert alert-info'>No activity logs found for Accounts.</div>";
							}
						?>
					</div>
				</div>
			</div>

			<!-- HR & PAYROLL TAB -->
			<div class="tab-pane fade" id="payroll" role="tabpanel" aria-labelledby="payroll-tab">
				<div class="table-data">
					<div class="order">
						<div class="head">
							<h3><i class='bx bxs-wallet'></i> HR & Payroll Activity Logs</h3>
						</div>
						<?php
							$payroll_query = "SELECT * FROM mrb_activity_log WHERE activity_type = 'payroll' ORDER BY id DESC LIMIT 50";
							$payroll_result = mysqli_query($conn, $payroll_query);
							
							if (mysqli_num_rows($payroll_result) > 0) {
								while ($log = mysqli_fetch_assoc($payroll_result)) {
								// Check if log is about rejection or report
								$is_red = (stripos($log['activity_desc'], 'rejected') !== false || stripos($log['activity_desc'], 'reported') !== false);
								$item_class = $is_red ? "activity-log-item" : "activity-log-item";
								$border_color = $is_red ? "border-left: 4px solid #dc3545;" : "";
								$bg_color = $is_red ? "background: #ffe6e6;" : "";
								$safe_activity_desc = redactConfidentialIds((string)$log['activity_desc']);
							$formatted_date = date('F j, Y g:i A', strtotime($log['created_at']));
							echo "<div class='{$item_class}' style='{$border_color} {$bg_color}'>
									<div class='d-flex justify-content-between align-items-start'>
										<div class='flex-grow-1'>
												<p class='mb-1'>{$safe_activity_desc}</p>
											<small class='timestamp'><i class='bx bx-time-five'></i> {$formatted_date}</small>
											</div>
										</div>
									</div>";
								}
							} else {
								echo "<div class='alert alert-info'>No activity logs found for HR & Payroll.</div>";
							}
						?>
					</div>
				</div>
			</div>

			<!-- SUPPLIERS TAB -->
			<div class="tab-pane fade" id="suppliers" role="tabpanel" aria-labelledby="suppliers-tab">
				<div class="table-data">
					<div class="order">
						<div class="head">
							<h3><i class='bx bxs-truck'></i> Supplier Activity Logs</h3>
						</div>
						<?php
							$suppliers_query = "SELECT * FROM mrb_activity_log WHERE activity_type = 'suppliers' ORDER BY id DESC LIMIT 50";
							$suppliers_result = mysqli_query($conn, $suppliers_query);
							
							if (mysqli_num_rows($suppliers_result) > 0) {
								while ($log = mysqli_fetch_assoc($suppliers_result)) {
								// Check if log is about rejection or report
								$is_red = (stripos($log['activity_desc'], 'rejected') !== false || stripos($log['activity_desc'], 'reported') !== false);
								$item_class = $is_red ? "activity-log-item" : "activity-log-item";
								$border_color = $is_red ? "border-left: 4px solid #dc3545;" : "";
								$bg_color = $is_red ? "background: #ffe6e6;" : "";
								$safe_activity_desc = redactConfidentialIds((string)$log['activity_desc']);
							$formatted_date = date('F j, Y g:i A', strtotime($log['created_at']));
							echo "<div class='{$item_class}' style='{$border_color} {$bg_color}'>
									<div class='d-flex justify-content-between align-items-start'>
										<div class='flex-grow-1'>
												<p class='mb-1'>{$safe_activity_desc}</p>
											<small class='timestamp'><i class='bx bx-time-five'></i> {$formatted_date}</small>
											</div>
										</div>
									</div>";
								}
							} else {
								echo "<div class='alert alert-info'>No activity logs found for Suppliers.</div>";
							}
						?>
					</div>
				</div>
			</div>

			<!-- FINANCE TAB -->
			<div class="tab-pane fade" id="finance" role="tabpanel" aria-labelledby="finance-tab">
				<div class="table-data">
					<div class="order">
						<div class="head">
							<h3><i class='bx bxs-coin'></i> Finance Activity Logs</h3>
						</div>
						<?php
							$finance_query = "SELECT * FROM mrb_activity_log WHERE activity_type = 'finance' ORDER BY id DESC LIMIT 50";
							$finance_result = mysqli_query($conn, $finance_query);
							
							if (mysqli_num_rows($finance_result) > 0) {
								while ($log = mysqli_fetch_assoc($finance_result)) {
								// Check if log is about rejection or report
								$is_red = (stripos($log['activity_desc'], 'rejected') !== false || stripos($log['activity_desc'], 'reported') !== false);
								$item_class = $is_red ? "activity-log-item" : "activity-log-item";
								$border_color = $is_red ? "border-left: 4px solid #dc3545;" : "";
								$bg_color = $is_red ? "background: #ffe6e6;" : "";
								$safe_activity_desc = redactConfidentialIds((string)$log['activity_desc']);
							$formatted_date = date('F j, Y g:i A', strtotime($log['created_at']));
							echo "<div class='{$item_class}' style='{$border_color} {$bg_color}'>
									<div class='d-flex justify-content-between align-items-start'>
										<div class='flex-grow-1'>
												<p class='mb-1'>{$safe_activity_desc}</p>
											<small class='timestamp'><i class='bx bx-time-five'></i> {$formatted_date}</small>
											</div>
										</div>
									</div>";
								}
							} else {
								echo "<div class='alert alert-info'>No activity logs found for Finance.</div>";
							}
						?>
					</div>
				</div>
			</div>

		</div>

		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC2pM8ODewa9r" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
		function confirmLogout() {
			if (confirm("Are you sure you want to log out?")) {
				window.location.href = "../logout.php";
			}
		}
	</script>
	
	<script src="script.js"></script>
</body>
</html>
<?php
	include '../connection.php';
?>
<?php
	session_start();
	// Check if user is logged in and has finance access
	if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: ../mrbloginpage.php");
		exit;
	}

	$current_user_role = $_SESSION['user_type'] ?? '';
	$account_page = ($current_user_role === 'admin' || $current_user_role === 'super_admin') ? 'account-admin.php' : 'account-staff.php';
	if (!in_array($current_user_role, ['super_admin', 'admin', 'finance'], true)) {
		if ($current_user_role === 'butcher') {
			header("Location: products-admin.php");
		} elseif ($current_user_role === 'cashier') {
			header("Location: orders-admin.php");
		} else {
			header("Location: ../landpage.php");
		}
		exit;
	}

	$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
	$is_finance = $current_user_role === 'finance';
	$is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
	$current_admin_shop_id = null;
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
	$payroll_scope_condition = "";
	$po_scope_condition = "";
	$po_scope_alias_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$payroll_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$po_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$po_scope_alias_condition = " AND po.shop_id = {$current_admin_shop_id}";
		} else {
			$order_scope_condition = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
			$payroll_scope_condition = " AND 1 = 0";
			$po_scope_condition = " AND 1 = 0";
			$po_scope_alias_condition = " AND 1 = 0";
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
	<style>
		.modal-backdrop {
		z-index: 1040 !important;
		}

		.modal {
		z-index: 1050 !important;
		}

		#sidebar {
		z-index: 1030 !important;
		}

		.modal-content {
		box-shadow: 0 5px 15px rgba(0,0,0,.5);
		border: 1px solid rgba(0,0,0,.2);
		z-index: 1050 !important;
		}

		.stat-card {
			border-left: 4px solid #6B3410;
		}

		.stat-card.income {
			border-left-color: #28a745;
		}

		.stat-card.expense {
			border-left-color: #dc3545;
		}

		.stat-card.balance {
			border-left-color: #007bff;
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
			<?php if ($is_finance): ?>
			<li class="active">
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
			<li>
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
			<li class="active">
				<a href="finances-admin.php">
				<i class='bx bxs-coin'></i>
				<span class="text">Finances</span>
				</a>
			</li>
			<?php endif; ?>
			</ul>

			<ul class="side-menu" style="padding: 0px;">
			<li>
				<a href="<?php echo $account_page; ?>">
				<i class="bx bxs-user-circle"></i>
				<span class="text"><?php echo $current_user_role === 'admin' ? 'My Shop' : 'My Account'; ?></span>
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
			<a href="#" class="nav-link">Finances</a>
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

			<a href="<?php echo $account_page; ?>" class="profile">
				<img src="../<?php echo $user_pic?>">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		 
		<main>
		<div class="head-title">
			<div class="left">
			<h1>Financial Management</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Finances</a></li>
			</ul>
			</div>
		</div>

		<?php
		// Display success/error messages
		if (isset($_SESSION['success_message'])) {
			echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>{$_SESSION['success_message']}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
			unset($_SESSION['success_message']);
		}
		if (isset($_SESSION['error_message'])) {
			echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>{$_SESSION['error_message']}<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
			unset($_SESSION['error_message']);
		}
		?>

		<!-- 1. PAYROLL APPROVALS - MOST IMPORTANT -->
		<div class="table-data">
			<div class="order">
			<div class="head">
				<h3><i class='bx bxs-wallet'></i> Payroll Approvals</h3>
				<?php
				$pending_payroll_query = "SELECT COUNT(*) as pending FROM mrb_payroll WHERE status = 'Processed' AND finance_status = 'Pending'{$payroll_scope_condition}";
				$pending_payroll_result = mysqli_query($conn, $pending_payroll_query);
				$pending_payroll_count = mysqli_fetch_assoc($pending_payroll_result)['pending'];
				if ($pending_payroll_count > 0) {
					echo "<span class='badge bg-warning text-dark'>{$pending_payroll_count} Pending</span>";
				}
				?>
			</div>
			<table>
				<thead>
					<tr>
						<th>Payroll ID</th>
						<th>Period</th>
						<th>Employees</th>
						<th>Gross Amount</th>
						<th>Net Amount</th>
						<th>HR Status</th>
						<th>Finance Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$payroll_query = "SELECT * FROM mrb_payroll WHERE status = 'Processed'{$payroll_scope_condition} ORDER BY finance_status = 'Pending' DESC, payroll_id DESC";
						$payroll_result = mysqli_query($conn, $payroll_query);
						
						if (mysqli_num_rows($payroll_result) > 0) {
							while ($payroll = mysqli_fetch_assoc($payroll_result)) {
								// Handle both old and new column names for backwards compatibility
								$employee_count = $payroll['employee_count'] ?? $payroll['total_employees'] ?? 0;
								
								$period_start = date('M j, Y', strtotime($payroll['payroll_period_start']));
								$period_end = date('M j, Y', strtotime($payroll['payroll_period_end']));
								
								// Status badges with icons
								$finance_status_color = '';
								$finance_status_icon = '';
								switch($payroll['finance_status']) {
									case 'Pending':
										$finance_status_color = 'bg-warning text-dark';
										$finance_status_icon = '<i class="bx bx-time-five"></i>';
										break;
									case 'Approved':
										$finance_status_color = 'bg-success';
										$finance_status_icon = '<i class="bx bx-check-circle"></i>';
										break;
									case 'Rejected':
										$finance_status_color = 'bg-danger';
										$finance_status_icon = '<i class="bx bx-x-circle"></i>';
										break;
								}
								
								echo "<tr>
									<td><strong>#{$payroll['payroll_id']}</strong></td>
									<td>{$period_start} - {$period_end}</td>
									<td>{$employee_count} employees</td>
									<td>₱" . number_format($payroll['gross_amount'], 2) . "</td>
									<td>₱" . number_format($payroll['net_amount'], 2) . "</td>
									<td><span class=\"badge bg-info\">{$payroll['status']}</span></td>
									<td><span class=\"badge {$finance_status_color}\">{$finance_status_icon} {$payroll['finance_status']}</span></td>
									<td>";
								
								if ($payroll['finance_status'] === 'Pending') {
									echo "<button class=\"btn btn-sm btn-success me-1\" onclick=\"approvePayroll({$payroll['payroll_id']})\"><i class='bx bx-check'></i> Approve</button>
										  <button class=\"btn btn-sm btn-danger\" onclick=\"rejectPayroll({$payroll['payroll_id']})\"><i class='bx bx-x'></i> Reject</button>";
								} else {
									echo "<button class=\"btn btn-sm btn-info\" data-bs-toggle=\"modal\" data-bs-target=\"#viewPayrollModal{$payroll['payroll_id']}\"><i class='bx bx-show'></i> View</button>";
								
								// Generate view modal for this payroll
								echo "
								<div class='modal fade' id='viewPayrollModal{$payroll['payroll_id']}' tabindex='-1' aria-hidden='true'>
									<div class='modal-dialog modal-lg'>
										<div class='modal-content'>
											<div class='modal-header bg-info text-white'>
												<h5 class='modal-title'><i class='bx bx-show'></i> Payroll Details - #{$payroll['payroll_id']}</h5>
												<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
											</div>
											<div class='modal-body'>
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>Payroll Period:</strong><br>
														{$period_start} - {$period_end}
													</div>
													<div class='col-md-6'>
														<strong>Number of Employees:</strong><br>
														{$employee_count} employees
													</div>
												</div>
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>Gross Amount:</strong><br>
														₱" . number_format($payroll['gross_amount'], 2) . "
													</div>
													<div class='col-md-6'>
														<strong>Net Amount:</strong><br>
														₱" . number_format($payroll['net_amount'], 2) . "
													</div>
												</div>
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>HR Status:</strong><br>
														<span class='badge bg-info'>{$payroll['status']}</span>
													</div>
													<div class='col-md-6'>
														<strong>Finance Status:</strong><br>
														<span class='badge {$finance_status_color}'>{$finance_status_icon} {$payroll['finance_status']}</span>
													</div>
												</div>";
								
								if ($payroll['finance_status'] === 'Rejected' && !empty($payroll['finance_rejection_reason'])) {
									echo "
												<div class='alert alert-danger'>
													<strong><i class='bx bx-x-circle'></i> Rejection Reason:</strong><br>
													{$payroll['finance_rejection_reason']}
												</div>";
								}
								
								if (!empty($payroll['finance_reviewed_by'])) {
									$reviewed_date = !empty($payroll['finance_reviewed_date']) ? date('M j, Y g:i A', strtotime($payroll['finance_reviewed_date'])) : 'N/A';
									echo "
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>Reviewed By:</strong><br>
														Admin ID: {$payroll['finance_reviewed_by']}
													</div>
													<div class='col-md-6'>
														<strong>Reviewed Date:</strong><br>
														{$reviewed_date}
													</div>
												</div>";
								}
								
								echo "
											</div>
											<div class='modal-footer'>
												<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
											</div>
										</div>
									</div>
								</div>";
							}
							
							echo "</td>
							</tr>";
						}
					} else {
						echo "<tr><td colspan='8' class='text-center'>No payroll records found</td></tr>";
						}
					?>
				</tbody>
			</table>
			</div>
		</div>

		<!-- 2. PRODUCT INCOME DISPLAY -->
		<div class="table-data" style="margin-top: 30px;">
			<div class="order">
			<div class="head">
				<h3><i class='bx bx-money'></i> Product Income</h3>
				<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#reportIssueModal">
					<i class='bx bx-error'></i> Report Issue
				</button>
			</div>
			<table>
				<thead>
					<tr>
						<th>Month</th>
						<th>Total Orders</th>
						<th>Total Income</th>
						<th>Average Order Value</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php
						// Calculate product income from orders for last 6 months
						for ($i = 0; $i < 6; $i++) {
							$month_start = date('Y-m-01', strtotime("-{$i} months"));
							$month_end = date('Y-m-t', strtotime("-{$i} months"));
							$month_name = date('F Y', strtotime($month_start));
							
							$orders_query = "SELECT COUNT(mrb_orders.order_id) as order_count, 
											SUM(mrb_fireex.prod_newprice * mrb_orders.order_quantity) as total_income 
											FROM mrb_orders 
											JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
											WHERE mrb_orders.order_status = 'delivered' 
											AND mrb_orders.order_dateordered BETWEEN '{$month_start}' AND '{$month_end}'";
							$orders_result = mysqli_query($conn, $orders_query);
							$orders_data = mysqli_fetch_assoc($orders_result);
							
							$order_count = $orders_data['order_count'] ?? 0;
							$total_income = $orders_data['total_income'] ?? 0;
							$avg_order = ($order_count > 0) ? ($total_income / $order_count) : 0;
							
							echo "<tr>
								<td><strong>{$month_name}</strong></td>
								<td>{$order_count} orders</td>
								<td>₱" . number_format($total_income, 2) . "</td>
								<td>₱" . number_format($avg_order, 2) . "</td>
								<td><span class=\"badge bg-success\"><i class='bx bx-check'></i> Verified</span></td>
							</tr>";
						}
					?>
				</tbody>
			</table>
			</div>
		</div>

		<!-- 3. SUPPLIER RESTOCK ORDERS -->
		<div class="table-data" style="margin-top: 30px;">
			<div class="order">
			<div class="head">
				<h3><i class='bx bxs-truck'></i> Supplier Purchase Orders</h3>
				<?php
				$pending_po_query = "SELECT COUNT(*) as pending FROM mrb_purchase_orders WHERE status = 'Pending' AND finance_status = 'Pending'{$po_scope_condition}";
				$pending_po_result = mysqli_query($conn, $pending_po_query);
				$pending_po_count = mysqli_fetch_assoc($pending_po_result)['pending'];
				if ($pending_po_count > 0) {
					echo "<span class='badge bg-warning text-dark'>{$pending_po_count} Pending</span>";
				}
				?>
			</div>
			<table>
				<thead>
					<tr>
						<th>PO Number</th>
						<th>Supplier</th>
						<th>Item Description</th>
						<th>Quantity</th>
						<th>Total Amount</th>
						<th>Delivery Date</th>
						<th>Finance Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$po_query = "SELECT po.*, s.company_name 
									FROM mrb_purchase_orders po
									LEFT JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id
										WHERE 1=1{$po_scope_alias_condition}
									ORDER BY po.finance_status = 'Pending' DESC, po.po_id DESC";
						$po_result = mysqli_query($conn, $po_query);
						
						if (mysqli_num_rows($po_result) > 0) {
							while ($po = mysqli_fetch_assoc($po_result)) {
								$delivery_date = !empty($po['expected_delivery_date']) ? date('M j, Y', strtotime($po['expected_delivery_date'])) : 'Not set';
								
								// Status badges with icons
								$finance_status_color = '';
								$finance_status_icon = '';
								switch($po['finance_status']) {
									case 'Pending':
										$finance_status_color = 'bg-warning text-dark';
										$finance_status_icon = '<i class="bx bx-time-five"></i>';
										break;
									case 'Approved':
										$finance_status_color = 'bg-success';
										$finance_status_icon = '<i class="bx bx-check-circle"></i>';
										break;
									case 'Rejected':
										$finance_status_color = 'bg-danger';
										$finance_status_icon = '<i class="bx bx-x-circle"></i>';
										break;
								}
								
								echo "<tr>
									<td><strong>{$po['po_number']}</strong></td>
									<td>{$po['company_name']}</td>
									<td>" . substr($po['item_description'], 0, 50) . "...</td>
									<td>{$po['quantity']}</td>
									<td>₱" . number_format($po['total_amount'], 2) . "</td>
									<td>{$delivery_date}</td>
									<td><span class=\"badge {$finance_status_color}\">{$finance_status_icon} {$po['finance_status']}</span></td>
									<td>";
								
								if ($po['finance_status'] === 'Pending') {
									echo "<button class=\"btn btn-sm btn-success me-1\" onclick=\"approvePO({$po['po_id']})\"><i class='bx bx-check'></i> Approve</button>
										  <button class=\"btn btn-sm btn-danger\" onclick=\"rejectPO({$po['po_id']})\"><i class='bx bx-x'></i> Reject</button>";
								} else {
									echo "<button class=\"btn btn-sm btn-info\" data-bs-toggle=\"modal\" data-bs-target=\"#viewPOModal{$po['po_id']}\"><i class='bx bx-show'></i> View</button>";
								
								// Generate view modal for this purchase order
								echo "
								<div class='modal fade' id='viewPOModal{$po['po_id']}' tabindex='-1' aria-hidden='true'>
									<div class='modal-dialog modal-lg'>
										<div class='modal-content'>
											<div class='modal-header bg-info text-white'>
												<h5 class='modal-title'><i class='bx bx-show'></i> Purchase Order Details - {$po['po_number']}</h5>
												<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
											</div>
											<div class='modal-body'>
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>PO Number:</strong><br>
														{$po['po_number']}
													</div>
													<div class='col-md-6'>
														<strong>Supplier:</strong><br>
														{$po['company_name']}
													</div>
												</div>
												<div class='mb-3'>
													<strong>Item Description:</strong><br>
													{$po['item_description']}
												</div>
												<div class='row mb-3'>
													<div class='col-md-4'>
														<strong>Quantity:</strong><br>
														{$po['quantity']}
													</div>
													<div class='col-md-4'>
														<strong>Total Amount:</strong><br>
														₱" . number_format($po['total_amount'], 2) . "
													</div>
													<div class='col-md-4'>
														<strong>Expected Delivery:</strong><br>
														{$delivery_date}
													</div>
												</div>
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>Order Status:</strong><br>
														<span class='badge bg-secondary'>{$po['status']}</span>
													</div>
													<div class='col-md-6'>
														<strong>Finance Status:</strong><br>
														<span class='badge {$finance_status_color}'>{$finance_status_icon} {$po['finance_status']}</span>
													</div>
												</div>";
								
								if ($po['finance_status'] === 'Rejected' && !empty($po['finance_rejection_reason'])) {
									echo "
												<div class='alert alert-danger'>
													<strong><i class='bx bx-x-circle'></i> Rejection Reason:</strong><br>
													{$po['finance_rejection_reason']}
												</div>";
								}
								
								if (!empty($po['finance_reviewed_by'])) {
									$po_reviewed_date = !empty($po['finance_reviewed_date']) ? date('M j, Y g:i A', strtotime($po['finance_reviewed_date'])) : 'N/A';
									echo "
												<div class='row mb-3'>
													<div class='col-md-6'>
														<strong>Reviewed By:</strong><br>
														Admin ID: {$po['finance_reviewed_by']}
													</div>
													<div class='col-md-6'>
														<strong>Reviewed Date:</strong><br>
														{$po_reviewed_date}
													</div>
												</div>";
								}
								
								echo "
											</div>
											<div class='modal-footer'>
												<button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
											</div>
										</div>
									</div>
								</div>";
							}
							
							echo "</td>
							</tr>";
						}
					} else {
						echo "<tr><td colspan='8' class='text-center'>No purchase orders found</td></tr>";
						}
					?>
				</tbody>
			</table>
			</div>
		</div>

		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->

	<!-- Reject Payroll Modal -->
	<div class="modal fade" id="rejectPayrollModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title"><i class='bx bx-x-circle'></i> Reject Payroll</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
				</div>
				<form action="handlers/process_payroll_finance.php" method="POST">
					<div class="modal-body">
						<input type="hidden" name="payroll_id" id="rejectPayrollId">
						<input type="hidden" name="action" value="reject">
						<div class="mb-3">
							<label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
							<textarea class="form-control" name="rejection_reason" rows="4" required placeholder="Please provide a detailed reason for rejecting this payroll..."></textarea>
						</div>
						<div class="alert alert-warning">
							<i class='bx bx-info-circle'></i> The HR department will be notified of this rejection.
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-danger"><i class='bx bx-x'></i> Reject Payroll</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Approve Payroll Form -->
	<form id="approvePayrollForm" action="handlers/process_payroll_finance.php" method="POST" style="display: none;">
		<input type="hidden" name="payroll_id" id="approvePayrollId">
		<input type="hidden" name="action" value="approve">
	</form>

	<!-- Reject Purchase Order Modal -->
	<div class="modal fade" id="rejectPOModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title"><i class='bx bx-x-circle'></i> Reject Purchase Order</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
				</div>
				<form action="handlers/process_purchase_order_finance.php" method="POST">
					<div class="modal-body">
						<input type="hidden" name="po_id" id="rejectPOId">
						<input type="hidden" name="action" value="reject">
						<div class="mb-3">
							<label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
							<textarea class="form-control" name="rejection_reason" rows="4" required placeholder="Please provide a detailed reason for rejecting this purchase order..."></textarea>
						</div>
						<div class="alert alert-warning">
							<i class='bx bx-info-circle'></i> The supplier department will be notified of this rejection.
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-danger"><i class='bx bx-x'></i> Reject Order</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Approve Purchase Order Form -->
	<form id="approvePOForm" action="handlers/process_purchase_order_finance.php" method="POST" style="display: none;">
		<input type="hidden" name="po_id" id="approvePOId">
		<input type="hidden" name="action" value="approve">
	</form>

	<!-- Report Income Issue Modal -->
	<div class="modal fade" id="reportIssueModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title"><i class='bx bx-error'></i> Report Income Issue</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
				</div>
				<form action="handlers/report_income_issue.php" method="POST">
					<div class="modal-body">
						<div class="mb-3">
							<label class="form-label">Income Source <span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="income_source" required placeholder="e.g., Product Sales - January 2026">
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label class="form-label">Reported Amount <span class="text-danger">*</span></label>
								<input type="number" class="form-control" name="reported_amount" step="0.01" required placeholder="0.00">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label">Expected Amount <span class="text-danger">*</span></label>
								<input type="number" class="form-control" name="expected_amount" step="0.01" required placeholder="0.00">
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label">Issue Description <span class="text-danger">*</span></label>
							<textarea class="form-control" name="issue_description" rows="5" required placeholder="Describe the discrepancy or issue you've identified..."></textarea>
						</div>
						<div class="alert alert-info">
							<i class='bx bx-info-circle'></i> This issue will be logged for investigation and review.
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-danger"><i class='bx bx-send'></i> Submit Report</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
		// Payroll approval functions
		function approvePayroll(payrollId) {
			if (confirm('Are you sure you want to APPROVE this payroll?')) {
				document.getElementById('approvePayrollId').value = payrollId;
				document.getElementById('approvePayrollForm').submit();
			}
		}

		function rejectPayroll(payrollId) {
			document.getElementById('rejectPayrollId').value = payrollId;
			const rejectModal = new bootstrap.Modal(document.getElementById('rejectPayrollModal'));
			rejectModal.show();
		}

		// Purchase Order approval functions
		function approvePO(poId) {
			if (confirm('Are you sure you want to APPROVE this purchase order?')) {
				document.getElementById('approvePOId').value = poId;
				document.getElementById('approvePOForm').submit();
			}
		}

		function rejectPO(poId) {
			document.getElementById('rejectPOId').value = poId;
			const rejectModal = new bootstrap.Modal(document.getElementById('rejectPOModal'));
			rejectModal.show();
		}

		// Modal and sidebar handling
		const myModal = document.getElementById('myModal');
		if (myModal) {
			const myInput = document.getElementById('myInput');
			myModal.addEventListener('shown.bs.modal', () => {
				if (myInput) myInput.focus();
			});
		}

		document.addEventListener('DOMContentLoaded', function() {
			const modals = document.querySelectorAll('.modal');
			const sidebar = document.getElementById('sidebar');
			
			modals.forEach(modal => {
				modal.addEventListener('show.bs.modal', function () {
					if (window.innerWidth <= 768) {
						sidebar.classList.add('hide');
					}
				});
			});
		});

		function confirmLogout() {
			if (confirm("Are you sure you want to log out?")) {
				window.location.href = "../logout.php";
			}
		}
	</script>
	
	<script src="script.js"></script>
</body>
</html>

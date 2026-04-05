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
	$account_page = ($current_user_role === 'admin' || $current_user_role === 'super_admin') ? 'account-admin.php' : 'account-staff.php';
	$is_butcher = $current_user_role === 'butcher';
	if (!in_array($current_user_role, ['super_admin', 'admin', 'butcher'], true)) {
		if ($current_user_role === 'cashier') {
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

	$order_scope_condition = "";
	$message_scope_condition = "";
	$supplier_scope_condition = "";
	$supplier_scope_alias_condition = "";
	$po_scope_condition = "";
	$po_scope_alias_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$supplier_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$supplier_scope_alias_condition = " AND s.shop_id = {$current_admin_shop_id}";
			$po_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$po_scope_alias_condition = " AND po.shop_id = {$current_admin_shop_id}";
		} else {
			$order_scope_condition = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
			$supplier_scope_condition = " AND 1 = 0";
			$supplier_scope_alias_condition = " AND 1 = 0";
			$po_scope_condition = " AND 1 = 0";
			$po_scope_alias_condition = " AND 1 = 0";
		}
	}
	
	// Check if viewing archived suppliers
	$view_archived = isset($_GET['view']) && $_GET['view'] == 'archived';
	
	// Get archived suppliers count for badge
	$archived_count_query = "SELECT COUNT(*) as archived FROM mrb_suppliers WHERE status = 'Archived'{$supplier_scope_condition}";
	$archived_count_result = mysqli_query($conn, $archived_count_query);
	$archived_count = mysqli_fetch_assoc($archived_count_result)['archived'];
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
	</style>
</head>
<body>

	
<section id="sidebar" class="sidebar">
<a href="#" class="brand">
			<i class='bx bxs-restaurant'></i>
			<span class="text">Meat Shop</span>
		</a>
		<ul class="side-menu top" style="padding: 0px;">
			<?php if ($is_butcher): ?>
			<li>
				<a href="products-admin.php">
				<i class='bx bxs-cart'></i>
				<span class="text">Products</span>
				</a>
			</li>
			<li class="active">
				<a href="suppliers-admin.php">
				<i class='bx bxs-truck'></i>
				<span class="text">Suppliers</span>
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
			<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'finance'): ?>
			<li>
				<a href="payroll-admin.php">
				<i class='bx bxs-wallet'></i>
				<span class="text">HR & Payroll</span>
				</a>
			</li>
			<li class="active">
				<a href="suppliers-admin.php">
				<i class='bx bxs-truck'></i>
				<span class="text">Suppliers</span>
				</a>
			</li>
			<?php endif; ?>
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
			<a href="#" class="nav-link">Suppliers</a>
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
		<?php
			// Display success or error messages (only for supplier-related actions)
			if (isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'Supplier') !== false || 
			    isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'Purchase order') !== false ||
			    isset($_SESSION['success_message']) && strpos($_SESSION['success_message'], 'account') !== false) {
				echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
					' . $_SESSION['success_message'] . '
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
				unset($_SESSION['success_message']);
			} else if (isset($_SESSION['success_message'])) {
				// Clear non-supplier messages without displaying
				unset($_SESSION['success_message']);
			}
			
			if (isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'Supplier') !== false || 
			    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'Purchase order') !== false ||
			    isset($_SESSION['error_message']) && strpos($_SESSION['error_message'], 'account') !== false) {
				echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
					' . $_SESSION['error_message'] . '
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';
				unset($_SESSION['error_message']);
			} else if (isset($_SESSION['error_message'])) {
				// Clear non-supplier messages without displaying
				unset($_SESSION['error_message']);
			}
		?>
		<div class="head-title">
			<div class="left">
			<h1>Supplier Management</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Suppliers</a></li>
			</ul>
			</div>
		</div>

		<!-- Summary Cards -->
		<ul class="box-info">
			<?php
				// Get supplier stats
				$sup_count_query = "SELECT COUNT(*) as total FROM mrb_suppliers WHERE status = 'Active'{$supplier_scope_condition}";
				$sup_count_result = mysqli_query($conn, $sup_count_query);
				$sup_count = mysqli_fetch_assoc($sup_count_result)['total'];

				$pending_po_query = "SELECT COUNT(*) as pending FROM mrb_purchase_orders WHERE finance_status = 'Pending'{$po_scope_condition}";
				$pending_po_result = mysqli_query($conn, $pending_po_query);
				$pending_po = mysqli_fetch_assoc($pending_po_result)['pending'];

				$supplies_value_query = "SELECT SUM(total_amount) as value FROM mrb_purchase_orders WHERE 1=1{$po_scope_condition}";
				$supplies_value_result = mysqli_query($conn, $supplies_value_query);
				$supplies_value = mysqli_fetch_assoc($supplies_value_result)['value'] ?? 0;
			?>
			<li style="background-color:#e6f7ff;">
			<i class='bx bxs-truck'></i>
			<span class="text">
				<h3><?php echo $sup_count; ?></h3>
				<p>Active Suppliers</p>
			</span>
			</li>
			<li style="background-color:#f0f5ff;">
			<i class='bx bx-time'></i>
			<span class="text">
				<h3><?php echo $pending_po; ?></h3>
				<p>Pending Orders</p>
			</span>
			</li>
			<li style="background-color:#fffbe6;">
			<i class='bx bx-package'></i>
			<span class="text">
				<h3>₱<?php echo number_format($supplies_value, 0); ?></h3>
				<p>Total Supplies Value</p>
			</span>
			</li>
		</ul>

		<!-- Low Stock Alerts -->
		<?php
			if (!$view_archived) {
				// Define threshold for low stock
				$low_stock_threshold = 20;
			
			// Query products below threshold with their suppliers
			$low_stock_query = "
				SELECT 
					f.prod_id,
					f.prod_name,
					f.prod_quantity,
					f.prod_type,
					s.supplier_id,
					s.supplier_number,
					s.company_name,
					s.product_category
				FROM mrb_fireex f
				LEFT JOIN mrb_suppliers s ON f.prod_type = s.product_category
				WHERE f.prod_quantity < $low_stock_threshold 
				AND f.prod_type != 'deleted'
				AND s.status = 'Active'{$supplier_scope_alias_condition}
				ORDER BY f.prod_quantity ASC
			";
			$low_stock_result = mysqli_query($conn, $low_stock_query);
			
			if (mysqli_num_rows($low_stock_result) > 0) {
				echo '<div class="table-data" style="margin-bottom: 20px;">
					<div class="order">
						<div class="head">
							<h3><i class="bx bx-error-circle" style="color: #ff4444;"></i> Low Stock Alerts</h3>
							<span class="badge bg-danger">' . mysqli_num_rows($low_stock_result) . ' items</span>
						</div>
						<div style="padding: 15px;">';
				
				while ($item = mysqli_fetch_assoc($low_stock_result)) {
					$alert_class = $item['prod_quantity'] == 0 ? 'danger' : 'warning';
					$stock_text = $item['prod_quantity'] == 0 ? 'OUT OF STOCK' : 'LOW STOCK: ' . $item['prod_quantity'] . ' units remaining';
					
					echo '<div class="alert alert-' . $alert_class . ' alert-dismissible fade show d-flex align-items-center justify-content-between" role="alert" style="margin-bottom: 10px;">
						<div>
							<i class="bx bx-package" style="font-size: 20px; margin-right: 10px;"></i>
							<strong>' . htmlspecialchars($item['prod_name']) . '</strong> - ' . $stock_text . '
							<br>
							<small class="text-muted">Category: ' . htmlspecialchars($item['prod_type']) . '</small>
						</div>
						<div>';
					
					if ($item['supplier_id']) {
						echo '<a href="#supplier-' . $item['supplier_id'] . '" class="btn btn-sm btn-primary" onclick="highlightSupplier(' . $item['supplier_id'] . ')">
							<i class="bx bx-truck"></i> Contact ' . htmlspecialchars($item['company_name']) . '
						</a>';
					} else {
						echo '<span class="badge bg-secondary">No supplier assigned</span>';
					}
					
					echo '<button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					</div>';
				}
				
				echo '</div>
					</div>
				</div>';
			}
		} // End of if (!$view_archived)
		?>

		<!-- Suppliers Table -->
		<div class="table-data">
			<div class="order">
			<div class="head">
				<h3>Supplier List</h3>
				<div>
					<button type="button" class="btn btn-secondary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#archivedSuppliersModal">
						<i class='bx bx-archive'></i> View Archived 
						<?php if ($archived_count > 0) { echo '<span class="badge bg-danger ms-1">' . $archived_count . '</span>'; } ?>
					</button>
					<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
						<i class='bx bx-plus'></i> Add Supplier
					</button>
				</div>
			</div>
			<table id="suppliersTable">
				<thead>
					<tr>
						<th>Supplier ID</th>
						<th>Company Name</th>
						<th>Contact Person</th>
						<th>Product Category</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						// Always show only non-archived suppliers
						$sup_query = "SELECT * FROM mrb_suppliers WHERE status != 'Archived'{$supplier_scope_condition} ORDER BY supplier_id DESC";
						$sup_result = mysqli_query($conn, $sup_query);
						
						if (mysqli_num_rows($sup_result) > 0) {
							while ($sup = mysqli_fetch_assoc($sup_result)) {
								$status_class = ($sup['status'] == 'Active') ? 'completed' : 'pending';
								echo "<tr id=\"supplier-{$sup['supplier_id']}\" data-supplier-id=\"{$sup['supplier_id']}\">
									<td>{$sup['supplier_number']}</td>
									<td>{$sup['company_name']}</td>
									<td>{$sup['contact_person']}</td>
									<td>{$sup['product_category']}</td>
									<td><span class=\"status {$status_class}\">{$sup['status']}</span></td>
									<td>
										<button class=\"btn btn-sm btn-primary\" data-bs-toggle=\"modal\" data-bs-target=\"#editSupplierModal\" onclick=\"editSupplier({$sup['supplier_id']}, '{$sup['company_name']}', '{$sup['contact_person']}', '{$sup['product_category']}', '{$sup['email']}', '{$sup['phone']}', '{$sup['address']}')\">Edit</button>
										<button class=\"btn btn-sm btn-warning\" onclick=\"archiveSupplier({$sup['supplier_id']})\">Archive</button>
									</td>
								</tr>";
							}
						} else {
							echo "<tr><td colspan='6' class='text-center'>No suppliers found</td></tr>";
						}
					?>
				</tbody>
			</table>
			</table>
			</div>
		</div>

		<!-- Recent Orders -->
		<div class="table-data" style="margin-top: 30px;">
			<div class="order">
			<div class="head">
				<h3>Recent Purchase Orders</h3>
				<button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#newPOModal">
					<i class='bx bx-plus'></i> New Order
				</button>
			</div>
			<table>
				<thead>
					<tr>
						<th>PO Number</th>
						<th>Supplier</th>
						<th>Item</th>
						<th>Quantity</th>
						<th>Amount</th>
						<th>Delivery Date</th>
						<th>Status</th>
						<th>Finance Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$po_query = "SELECT po.*, s.company_name FROM mrb_purchase_orders po 
									 LEFT JOIN mrb_suppliers s ON po.supplier_id = s.supplier_id 
									 WHERE 1=1{$po_scope_alias_condition}
									 ORDER BY po.po_id DESC LIMIT 30";
						$po_result = mysqli_query($conn, $po_query);
						
						if (mysqli_num_rows($po_result) > 0) {
							while ($po = mysqli_fetch_assoc($po_result)) {
								$status_class = ($po['status'] == 'Delivered') ? 'completed' : 'pending';
								$delivery_date = date('M j, Y', strtotime($po['expected_delivery_date']));
								$today = date('Y-m-d');
								$is_late = (strtotime($po['expected_delivery_date']) < strtotime($today) && $po['status'] != 'Delivered');							
							// Finance status with icons
							$finance_status = $po['finance_status'] ?? 'Pending';
							$finance_status_color = '';
							$finance_status_icon = '';
							switch($finance_status) {
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
							}								$button_html = '';
								
								if ($po['status'] != 'Delivered') {
									if ($is_late) {
										$button_html = '<button class="btn btn-sm btn-secondary" disabled>Late</button>';
									} else {
										$button_html = '<form method="POST" action="handlers/mark_delivered.php" style="display:inline;">
											<input type="hidden" name="po_id" value="' . $po['po_id'] . '">
											<button type="submit" class="btn btn-sm btn-success">Delivered</button>
										</form>';
									}
								} else {
									$button_html = '<button class="btn btn-sm btn-info" onclick="viewPO(' . $po['po_id'] . ')">View</button>';
								}
								
								echo "<tr>
									<td>{$po['po_number']}</td>
									<td>{$po['company_name']}</td>
									<td>{$po['item_description']}</td>
									<td>{$po['quantity']}</td>
									<td>₱" . number_format($po['total_amount'], 0) . "</td>
									<td>{$delivery_date}</td>
									<td><span class=\"status {$status_class}\">" . ($is_late ? 'Late' : $po['status']) . "</span></td>								<td><span class=\"badge {$finance_status_color}\">{$finance_status_icon} {$finance_status}</span></td>									<td>{$button_html}</td>
								</tr>";
							}
						} else {
							echo "<tr><td colspan='9' class='text-center'>No purchase orders found</td></tr>";
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

	<!-- Add Supplier Modal -->
	<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title" id="addSupplierModalLabel">Add New Supplier</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/add_supplier.php" method="POST">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="companyName" class="form-label">Company Name</label>
								<input type="text" class="form-control" id="companyName" name="company_name" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="contactPerson" class="form-label">Contact Person</label>
								<input type="text" class="form-control" id="contactPerson" name="contact_person" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="email" class="form-label">Email</label>
								<input type="email" class="form-control" id="email" name="email" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="phone" class="form-label">Phone</label>
								<input type="tel" class="form-control" id="phone" name="contact_number" required>
							</div>
						</div>
						<div class="mb-3">
							<label for="productCategory" class="form-label">Product Category</label>
							<select class="form-select" id="productCategory" name="product_category" required>
								<option value="">Select Category</option>
							<option value="Pork Products">Pork Products</option>
							<option value="Chicken Products">Chicken Products</option>
							<option value="Beef Products">Beef Products</option>
							<option value="Fish Products">Fish Products</option>
								<option value="Other">Other</option>
							</select>
						</div>
						<div class="mb-3">
							<label for="address" class="form-label">Address</label>
							<textarea class="form-control" id="address" name="address" rows="2" required></textarea>
						</div>
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn red-bg text-white">Add Supplier</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Edit Supplier Modal -->
	<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/edit_supplier.php" method="POST">
						<input type="hidden" id="editSupplierId" name="supplier_id">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editCompanyName" class="form-label">Company Name</label>
								<input type="text" class="form-control" id="editCompanyName" name="company_name" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editContactPerson" class="form-label">Contact Person</label>
								<input type="text" class="form-control" id="editContactPerson" name="contact_person" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmail" class="form-label">Email</label>
								<input type="email" class="form-control" id="editEmail" name="email" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editPhone" class="form-label">Phone</label>
								<input type="tel" class="form-control" id="editPhone" name="phone" required>
							</div>
						</div>
						<div class="mb-3">
							<label for="editProductCategory" class="form-label">Product Category</label>
							<select class="form-select" id="editProductCategory" name="product_category" required>
								<option value="">Select Category</option>
							<option value="Pork Products">Pork Products</option>
							<option value="Chicken Products">Chicken Products</option>
							<option value="Beef Products">Beef Products</option>
							<option value="Fish Products">Fish Products</option>
								<option value="Other">Other</option>
							</select>
						</div>
						<div class="mb-3">
							<label for="editAddress" class="form-label">Address</label>
							<textarea class="form-control" id="editAddress" name="address" rows="2" required></textarea>
						</div>
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn red-bg text-white">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- New Purchase Order Modal -->
	<div class="modal fade" id="newPOModal" tabindex="-1" aria-labelledby="newPOModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title" id="newPOModalLabel">Create Purchase Order</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/add_purchase_order.php" method="POST">
						<div class="mb-3">
							<label for="poSupplier" class="form-label">Supplier</label>
							<select class="form-select" id="poSupplier" name="supplier_id" required>
								<option value="">Select Supplier</option>
								<?php
									$supplier_list = "SELECT supplier_id, company_name FROM mrb_suppliers WHERE status = 'Active'{$supplier_scope_condition} ORDER BY company_name";
									$supplier_list_result = mysqli_query($conn, $supplier_list);
									if (mysqli_num_rows($supplier_list_result) > 0) {
										while ($sup = mysqli_fetch_assoc($supplier_list_result)) {
											echo "<option value='{$sup['supplier_id']}'>{$sup['company_name']}</option>";
										}
									}
								?>
							</select>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="poItem" class="form-label">Item</label>
								<input type="text" class="form-control" id="poItem" name="item_description" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="poQuantity" class="form-label">Quantity</label>
								<input type="text" class="form-control" id="poQuantity" name="quantity" placeholder="e.g., 50 kg" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="poUnitPrice" class="form-label">Unit Price</label>
								<input type="number" class="form-control" id="poUnitPrice" name="unit_price" step="0.01" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="poDeliveryDate" class="form-label">Expected Delivery</label>
								<input type="date" class="form-control" id="poDeliveryDate" name="delivery_date" required>
							</div>
						</div>
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-success">Create Order</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- View Purchase Order Modal -->
	<div class="modal fade" id="viewPOModal" tabindex="-1" aria-labelledby="viewPOModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title" id="viewPOModalLabel">Purchase Order Details</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label fw-bold">PO Number</label>
							<p id="viewPONumber" class="form-control-plaintext"></p>
						</div>
						<div class="col-md-6">
							<label class="form-label fw-bold">Supplier</label>
							<p id="viewSupplier" class="form-control-plaintext"></p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-12">
							<label class="form-label fw-bold">Item Description</label>
							<p id="viewItem" class="form-control-plaintext"></p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label fw-bold">Quantity</label>
							<p id="viewQuantity" class="form-control-plaintext"></p>
						</div>
						<div class="col-md-6">
							<label class="form-label fw-bold">Unit Price</label>
							<p id="viewUnitPrice" class="form-control-plaintext"></p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label fw-bold">Total Amount</label>
							<p id="viewTotalAmount" class="form-control-plaintext"></p>
						</div>
						<div class="col-md-6">
							<label class="form-label fw-bold">Status</label>
							<p id="viewStatus" class="form-control-plaintext"></p>
						</div>
					</div>
					<div class="row mb-3">
						<div class="col-md-6">
							<label class="form-label fw-bold">Expected Delivery Date</label>
							<p id="viewExpectedDate" class="form-control-plaintext"></p>
						</div>
						<div class="col-md-6">
							<label class="form-label fw-bold">Actual Delivery Date</label>
							<p id="viewActualDate" class="form-control-plaintext"></p>
						</div>
					</div>
					<div class="d-flex justify-content-end">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Archived Suppliers Modal -->
	<div class="modal fade" id="archivedSuppliersModal" tabindex="-1" aria-labelledby="archivedSuppliersModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="archivedSuppliersModalLabel">
						<i class='bx bx-archive'></i> Archived Suppliers
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<?php
						$archived_query = "SELECT * FROM mrb_suppliers WHERE status = 'Archived'{$supplier_scope_condition} ORDER BY updated_at DESC";
						$archived_result = mysqli_query($conn, $archived_query);
						
						if (mysqli_num_rows($archived_result) > 0) {
							echo '<div class="table-responsive">
								<table class="table table-hover">
									<thead>
										<tr>
											<th>Supplier ID</th>
											<th>Company Name</th>
											<th>Contact Person</th>
											<th>Product Category</th>
											<th>Archived Date</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>';
							
							while ($archived = mysqli_fetch_assoc($archived_result)) {
								$archived_date = date('M d, Y', strtotime($archived['updated_at']));
								echo "<tr>
									<td>{$archived['supplier_number']}</td>
									<td>{$archived['company_name']}</td>
									<td>{$archived['contact_person']}</td>
									<td><span class='badge bg-secondary'>{$archived['product_category']}</span></td>
									<td><small class='text-muted'>{$archived_date}</small></td>
									<td>
										<button class='btn btn-sm btn-success' onclick='restoreSupplier({$archived['supplier_id']})'>
											<i class='bx bx-undo'></i> Restore
										</button>
									</td>
								</tr>";
							}
							
							echo '</tbody>
								</table>
							</div>';
						} else {
							echo '<div class="alert alert-info mb-0">
								<i class="bx bx-info-circle"></i> No archived suppliers found.
							</div>';
						}
					?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
		const myModal = document.getElementById('myModal')
		const myInput = document.getElementById('myInput')

		myModal.addEventListener('shown.bs.modal', () => {
		myInput.focus()
		})

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

		// Edit Supplier Function - Populate modal with data
		function editSupplier(id, company, contact, category, email, phone, address) {
			document.getElementById('editSupplierId').value = id;
			document.getElementById('editCompanyName').value = company;
			document.getElementById('editContactPerson').value = contact;
			document.getElementById('editProductCategory').value = category;
			document.getElementById('editEmail').value = email;
			document.getElementById('editPhone').value = phone;
			document.getElementById('editAddress').value = address;
		}

		// Archive Supplier Function
		function archiveSupplier(id) {
			if (confirm('Are you sure you want to archive this supplier? You can restore them later from the Archived Suppliers view.')) {
				const form = document.createElement('form');
				form.method = 'POST';
				form.action = 'handlers/archive_supplier.php';
				form.innerHTML = '<input type="hidden" name="supplier_id" value="' + id + '">';
				document.body.appendChild(form);
				form.submit();
			}
		}

		// Restore Supplier Function
		function restoreSupplier(id) {
			if (confirm('Are you sure you want to restore this supplier?')) {
				const form = document.createElement('form');
				form.method = 'POST';
				form.action = 'handlers/restore_supplier.php';
				form.innerHTML = '<input type="hidden" name="supplier_id" value="' + id + '">';
				document.body.appendChild(form);
				form.submit();
			}
		}

		// View PO Function - Display purchase order details
		function viewPO(poId) {
			// Fetch PO details via AJAX
			fetch('handlers/get_po_details.php?po_id=' + poId)
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						const po = data.po;
						document.getElementById('viewPONumber').textContent = po.po_number;
						document.getElementById('viewSupplier').textContent = po.company_name;
						document.getElementById('viewItem').textContent = po.item_description;
						document.getElementById('viewQuantity').textContent = po.quantity;
						document.getElementById('viewUnitPrice').textContent = '₱' + parseFloat(po.unit_price).toLocaleString('en-PH', {minimumFractionDigits: 2});
						document.getElementById('viewTotalAmount').textContent = '₱' + parseFloat(po.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 0});
						document.getElementById('viewExpectedDate').textContent = new Date(po.expected_delivery_date).toLocaleDateString('en-PH', {year: 'numeric', month: 'long', day: 'numeric'});
						document.getElementById('viewActualDate').textContent = po.actual_delivery_date ? new Date(po.actual_delivery_date).toLocaleDateString('en-PH', {year: 'numeric', month: 'long', day: 'numeric'}) : 'Not yet delivered';
						document.getElementById('viewStatus').textContent = po.status;
						
						// Show modal
						const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
						modal.show();
					} else {
						alert('Error loading purchase order details');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					alert('Error loading purchase order details');
				});
		}

		function confirmLogout() {
			if (confirm("Are you sure you want to log out?")) {
				window.location.href = "../logout.php";
			}
		}

		// Highlight supplier row when navigating from low stock alert
		function highlightSupplier(supplierId) {
			// Remove any existing highlights
			document.querySelectorAll('tr[data-supplier-id]').forEach(row => {
				row.style.backgroundColor = '';
				row.style.transition = '';
			});

			// Scroll to and highlight the supplier
			const supplierRow = document.getElementById('supplier-' + supplierId);
			if (supplierRow) {
				// Smooth scroll to element
				supplierRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
				
				// Apply highlight effect
				setTimeout(() => {
					supplierRow.style.transition = 'background-color 0.3s ease';
					supplierRow.style.backgroundColor = '#fff3cd';
					
					// Remove highlight after 3 seconds
					setTimeout(() => {
						supplierRow.style.backgroundColor = '';
					}, 3000);
				}, 500);
			}
		}

		// Check if we need to highlight a supplier on page load (from products page)
		document.addEventListener('DOMContentLoaded', function() {
			const highlightId = localStorage.getItem('highlightSupplier');
			if (highlightId) {
				localStorage.removeItem('highlightSupplier');
				setTimeout(() => {
					highlightSupplier(parseInt(highlightId));
				}, 500);
			}
		});
	</script>
	
	<script src="script.js"></script>
<?php include 'toast-notification.php'; ?>
</body>
</html>

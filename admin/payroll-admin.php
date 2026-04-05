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

	$employee_scope_condition = "";
	$employee_scope_alias_condition = "";
	$attendance_scope_condition = "";
	$attendance_scope_alias_condition = "";
	$payroll_scope_condition = "";
	$order_scope_condition = "";
	$message_scope_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$employee_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$employee_scope_alias_condition = " AND e.shop_id = {$current_admin_shop_id}";
			$attendance_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$attendance_scope_alias_condition = " AND a.shop_id = {$current_admin_shop_id}";
			$payroll_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
		} else {
			$employee_scope_condition = " AND 1 = 0";
			$employee_scope_alias_condition = " AND 1 = 0";
			$attendance_scope_condition = " AND 1 = 0";
			$attendance_scope_alias_condition = " AND 1 = 0";
			$payroll_scope_condition = " AND 1 = 0";
			$order_scope_condition = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
		}
	}

	$employee_staff_role_column = mysqli_query($conn, "SHOW COLUMNS FROM mrb_employees LIKE 'staff_role'");
	if ($employee_staff_role_column && mysqli_num_rows($employee_staff_role_column) === 0) {
		@mysqli_query($conn, "ALTER TABLE mrb_employees ADD COLUMN staff_role VARCHAR(30) NULL AFTER department");
	}

	$allowed_staff_roles = ['butcher', 'cashier', 'finance', 'rider'];
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_employee_account'])) {
		$emp_id = isset($_POST['emp_id']) ? (int)$_POST['emp_id'] : 0;
		$temp_password = $_POST['staff_password'] ?? '';

		if ($emp_id <= 0 || $temp_password === '') {
			$_SESSION['error_message'] = 'Please choose a valid employee and password.';
			header('Location: payroll-admin.php');
			exit;
		}

		$employee_lookup_query = "SELECT emp_id, emp_first_name, emp_last_name, email, contact_number, shop_id, staff_role FROM mrb_employees WHERE emp_id = {$emp_id}{$employee_scope_condition} LIMIT 1";
		$employee_lookup_result = mysqli_query($conn, $employee_lookup_query);
		$employee_row = ($employee_lookup_result && mysqli_num_rows($employee_lookup_result) > 0) ? mysqli_fetch_assoc($employee_lookup_result) : null;

		if (!$employee_row) {
			$_SESSION['error_message'] = 'Employee not found or outside your shop scope.';
			header('Location: payroll-admin.php');
			exit;
		}

		$employee_email = trim((string)($employee_row['email'] ?? ''));
		$employee_contact = trim((string)($employee_row['contact_number'] ?? ''));
		$employee_shop_id = (int)($employee_row['shop_id'] ?? 0);
		$staff_role = trim((string)($employee_row['staff_role'] ?? ''));

		if ($employee_email === '' || $employee_shop_id <= 0 || !in_array($staff_role, $allowed_staff_roles, true)) {
			$_SESSION['error_message'] = 'Employee must have a valid email, shop assignment, and registered staff role before creating an account.';
			header('Location: payroll-admin.php');
			exit;
		}

		$email_check_stmt = mysqli_prepare($conn, "SELECT user_id FROM mrb_users WHERE user_email = ? AND user_type != 'deleted' LIMIT 1");
		mysqli_stmt_bind_param($email_check_stmt, 's', $employee_email);
		mysqli_stmt_execute($email_check_stmt);
		$email_check_result = mysqli_stmt_get_result($email_check_stmt);
		$email_exists = $email_check_result && mysqli_num_rows($email_check_result) > 0;
		mysqli_stmt_close($email_check_stmt);

		if ($email_exists) {
			$_SESSION['error_message'] = 'An active account with this employee email already exists.';
			header('Location: payroll-admin.php');
			exit;
		}

		$next_id_result = mysqli_query($conn, "SELECT COALESCE(MAX(user_id), 0) + 1 AS next_user_id FROM mrb_users");
		$next_user_id = 0;
		if ($next_id_result && mysqli_num_rows($next_id_result) > 0) {
			$next_id_row = mysqli_fetch_assoc($next_id_result);
			$next_user_id = isset($next_id_row['next_user_id']) ? (int)$next_id_row['next_user_id'] : 0;
		}

		if ($next_user_id <= 0) {
			$_SESSION['error_message'] = 'Unable to allocate user ID. Please try again.';
			header('Location: payroll-admin.php');
			exit;
		}

		$hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
		$default_pic = 'Images/profile_pics/anonymous.jpg';
		$employee_first_name = (string)($employee_row['emp_first_name'] ?? '');
		$employee_last_name = (string)($employee_row['emp_last_name'] ?? '');
		$insert_user_sql = "INSERT INTO mrb_users (user_id, user_name, user_mname, user_lname, user_contactnum, user_email, user_password, user_pic, user_dateadded, user_type, shop_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)";
		$insert_user_stmt = mysqli_prepare($conn, $insert_user_sql);
		$empty_middle_name = '';
		mysqli_stmt_bind_param(
			$insert_user_stmt,
			'issssssssi',
			$next_user_id,
			$employee_first_name,
			$empty_middle_name,
			$employee_last_name,
			$employee_contact,
			$employee_email,
			$hashed_password,
			$default_pic,
			$staff_role,
			$employee_shop_id
		);

		if (mysqli_stmt_execute($insert_user_stmt)) {
			$_SESSION['success_message'] = 'Employee account created successfully.';
		} else {
			$_SESSION['error_message'] = 'Failed to create employee account. Please try again.';
		}
		mysqli_stmt_close($insert_user_stmt);

		header('Location: payroll-admin.php');
		exit;
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
	<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
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
		
		#qr-reader {
			width: 100%;
			max-width: 500px;
			margin: 0 auto;
		}
		
		.qr-code-img {
			width: 150px;
			height: 150px;
			border: 2px solid #ddd;
			padding: 10px;
			border-radius: 8px;
		}
		
		.attendance-card {
			transition: all 0.3s ease;
		}
		
		.attendance-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
			<li class="active">
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
	</section>
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link">HR & Payroll</a>
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
		 <?php
			// Display success or error messages with toast
			if (isset($_SESSION['success_message'])) {
				echo '<script>document.addEventListener("DOMContentLoaded", function() { showAdminToast("Success", "' . addslashes($_SESSION['success_message']) . '", "success"); });</script>';
				unset($_SESSION['success_message']);
			}
			if (isset($_SESSION['error_message'])) {
				echo '<script>document.addEventListener("DOMContentLoaded", function() { showAdminToast("Error", "' . addslashes($_SESSION['error_message']) . '", "error", 5000); });</script>';
				unset($_SESSION['error_message']);
			}
			if (isset($_SESSION['success'])) {
				echo '<script>document.addEventListener("DOMContentLoaded", function() { showAdminToast("Success", "' . addslashes($_SESSION['success']) . '", "success"); });</script>';
				unset($_SESSION['success']);
			}
			if (isset($_SESSION['error'])) {
				echo '<script>document.addEventListener("DOMContentLoaded", function() { showAdminToast("Error", "' . addslashes($_SESSION['error']) . '", "error", 5000); });</script>';
				unset($_SESSION['error']);
			}
		?>
		<div class="head-title">
			<div class="left">
			<h1>HR & Payroll Management</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">HR & Payroll</a></li>
			</ul>
			</div>
		</div>

		<!-- Summary Cards -->
		<ul class="box-info">
			<?php
				// Get employee count and payroll stats
				$emp_count_query = "SELECT COUNT(*) as total FROM mrb_employees WHERE status != 'Inactive'{$employee_scope_condition}";
				$emp_count_result = mysqli_query($conn, $emp_count_query);
				$emp_count = mysqli_fetch_assoc($emp_count_result)['total'];

				$payroll_query = "SELECT SUM(salary) as total_payroll FROM mrb_employees WHERE status = 'Active'{$employee_scope_condition}";
				$payroll_result = mysqli_query($conn, $payroll_query);
				$payroll_amount = mysqli_fetch_assoc($payroll_result)['total_payroll'] ?? 0;

				$pending_query = "SELECT COUNT(*) as pending FROM mrb_payroll WHERE status = 'Processed' AND finance_status = 'Pending'{$payroll_scope_condition}";
				$pending_result = mysqli_query($conn, $pending_query);
				$pending_count = mysqli_fetch_assoc($pending_result)['pending'];
				
				// Get today's attendance count
				$today = date('Y-m-d');
				$attendance_query = "SELECT COUNT(DISTINCT emp_id) as present FROM mrb_attendance WHERE work_date = '$today'{$attendance_scope_condition}";
				$attendance_result = mysqli_query($conn, $attendance_query);
				$attendance_count = mysqli_fetch_assoc($attendance_result)['present'] ?? 0;
			?>
			<li style="background-color:#e6f7ff;">
			<i class='bx bx-user'></i>
			<span class="text">
				<h3><?php echo $emp_count; ?></h3>
				<p>Total Employees</p>
			</span>
			</li>
			<li style="background-color:#f0f5ff;">
			<i class='bx bxs-wallet'></i>
			<span class="text">
				<h3>₱<?php echo number_format($payroll_amount, 0); ?></h3>
				<p>Monthly Payroll</p>
			</span>
			</li>
			<li style="background-color:#fffbe6;">
			<i class='bx bx-calendar'></i>
			<span class="text">
				<h3><?php echo $pending_count; ?></h3>
				<p>Pending Approvals</p>
			</span>
			</li>
			<li style="background-color:#e6ffe6;">
			<i class='bx bx-check-circle'></i>
			<span class="text">
				<h3><?php echo $attendance_count; ?> / <?php echo $emp_count; ?></h3>
				<p>Present Today</p>
			</span>
			</li>
		</ul>

		<!-- Attendance Section -->
		<div class="table-data">
			<div class="order">
				<div class="head">
					<h3>QR Attendance Scanner</h3>
					<div>
						<button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#attendanceSettingsModal">
							<i class='bx bx-cog'></i> Settings
						</button>
						<button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#attendanceModal">
							<i class='bx bx-qr-scan'></i> Scan Attendance
						</button>
					</div>
				</div>
				
				<!-- Today's Attendance -->
				<div class="mt-3">
					<h5>Today's Attendance (<?php echo date('F j, Y'); ?>)</h5>
					<table class="table table-sm">
						<thead>
							<tr>
								<th>Employee</th>
								<th>Check In</th>
								<th>Check Out</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php
								$today_attendance_query = "SELECT a.*, e.emp_first_name, e.emp_last_name, e.emp_number 
														   FROM mrb_attendance a 
														   JOIN mrb_employees e ON a.emp_id = e.emp_id 
													   WHERE a.work_date = '$today'{$attendance_scope_alias_condition}{$employee_scope_alias_condition} 
														   ORDER BY a.check_in_time DESC";
								$today_attendance_result = mysqli_query($conn, $today_attendance_query);
								
								if (mysqli_num_rows($today_attendance_result) > 0) {
									while ($att = mysqli_fetch_assoc($today_attendance_result)) {
										$check_in = date('h:i A', strtotime($att['check_in_time']));
										$check_out = $att['check_out_time'] ? date('h:i A', strtotime($att['check_out_time'])) : '-';
										
										// Determine status with late indicator
										if($att['check_out_time']) {
											$status = 'Completed';
											$status_class = 'completed';
										} else {
											$status = 'Active';
											$status_class = 'process';
										}
										
										if($att['is_late']) {
											$status .= ' (Late)';
											$status_class = 'pending';
										}
										
										$late_info = $att['is_late'] ? " <small class='text-danger'>(-₱" . number_format($att['late_deduction'], 2) . ")</small>" : "";
										
										echo "<tr>
											<td>{$att['emp_number']} - {$att['emp_first_name']} {$att['emp_last_name']}</td>
											<td>{$check_in}{$late_info}</td>
											<td>{$check_out}</td>
											<td><span class=\"status {$status_class}\">{$status}</span></td>
										</tr>";
									}
								} else {
									echo "<tr><td colspan='4' class='text-center'>No attendance records for today</td></tr>";
								}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Employees Table -->
		<div class="table-data">
			<div class="order">
			<div class="head">
				<h3>Employee List</h3>
				<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
					<i class='bx bx-plus'></i> Add Employee
				</button>
			</div>
			<table>
				<thead>
					<tr>
						<th>Employee ID</th>
						<th>Name</th>
						<th>Position</th>
						<th>Department</th>
						<th>Status</th>
						<th>QR Code</th>
						<th>Account</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$emp_query = "SELECT mrb_employees.*,
							(SELECT u.user_id FROM mrb_users u WHERE u.user_email = mrb_employees.email AND u.shop_id = mrb_employees.shop_id AND u.user_type IN ('butcher', 'cashier', 'finance', 'rider', 'admin', 'super_admin') AND u.user_type != 'deleted' ORDER BY u.user_id DESC LIMIT 1) AS linked_user_id,
							(SELECT u.user_type FROM mrb_users u WHERE u.user_email = mrb_employees.email AND u.shop_id = mrb_employees.shop_id AND u.user_type IN ('butcher', 'cashier', 'finance', 'rider', 'admin', 'super_admin') AND u.user_type != 'deleted' ORDER BY u.user_id DESC LIMIT 1) AS linked_user_role
							FROM mrb_employees
							WHERE 1=1{$employee_scope_condition}
							ORDER BY emp_id DESC";
						$emp_result = mysqli_query($conn, $emp_query);
						
						if (mysqli_num_rows($emp_result) > 0) {
							while ($emp = mysqli_fetch_assoc($emp_result)) {
								$status_class = ($emp['status'] == 'Active') ? 'completed' : 'pending';
								$linked_user_id = isset($emp['linked_user_id']) ? (int)$emp['linked_user_id'] : 0;
								$linked_user_role = trim((string)($emp['linked_user_role'] ?? ''));
								$display_role = $linked_user_role !== '' ? ucfirst($linked_user_role) : '';
								$safe_first_name = htmlspecialchars((string)$emp['emp_first_name'], ENT_QUOTES);
								$safe_last_name = htmlspecialchars((string)$emp['emp_last_name'], ENT_QUOTES);
								$safe_position = htmlspecialchars((string)$emp['position'], ENT_QUOTES);
								$safe_department = htmlspecialchars((string)$emp['department'], ENT_QUOTES);
								$safe_staff_role = htmlspecialchars((string)($emp['staff_role'] ?? ''), ENT_QUOTES);
								$safe_email = htmlspecialchars((string)$emp['email'], ENT_QUOTES);
								$safe_contact = htmlspecialchars((string)$emp['contact_number'], ENT_QUOTES);
								echo "<tr>
									<td>{$emp['emp_number']}</td>
									<td>{$emp['emp_first_name']} {$emp['emp_last_name']}</td>
									<td>{$emp['position']}</td>
									<td>{$emp['department']}</td>
									<td><span class=\"status {$status_class}\">{$emp['status']}</span></td>
									<td>
										<button class=\"btn btn-sm btn-info\" data-bs-toggle=\"modal\" data-bs-target=\"#qrModal{$emp['emp_id']}\">
											<i class='bx bx-qr'></i> View QR
										</button>
									</td>
									<td>";
								if ($linked_user_id > 0) {
									echo "<span class='badge bg-success'>Account Created" . ($display_role !== '' ? " ({$display_role})" : '') . "</span>";
								} else {
									echo "<button class='btn btn-sm btn-outline-dark' type='button' data-bs-toggle='modal' data-bs-target='#createEmployeeAccountModal' onclick=\"openCreateAccountModal({$emp['emp_id']}, '{$safe_first_name}', '{$safe_last_name}', '{$safe_email}', '{$safe_staff_role}')\">Create Account</button>";
								}
								echo "</td>
									<td>
										<button class=\"btn btn-sm btn-primary\" data-bs-toggle=\"modal\" data-bs-target=\"#editEmployeeModal\" onclick=\"editEmployee({$emp['emp_id']}, '{$safe_first_name}', '{$safe_last_name}', '{$safe_position}', '{$safe_department}', '{$safe_staff_role}', {$emp['salary']}, '{$safe_email}', '{$safe_contact}')\">Edit</button>
										<button class=\"btn btn-sm btn-danger\" onclick=\"deleteEmployee({$emp['emp_id']})\">Delete</button>
									</td>
								</tr>";
							}
						} else {
							echo "<tr><td colspan='8' class='text-center'>No employees found</td></tr>";
						}
					?>
				</tbody>
			</table>
			</div>
		</div>

		<!-- Payroll Summary -->
		<div class="table-data" style="margin-top: 30px;">
			<div class="order">
			<div class="head">
				<h3>Payroll Summary</h3>
				<button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#processPayrollModal">
					<i class='bx bx-check'></i> Process Payroll
				</button>
			</div>
			<table>
				<thead>
					<tr>
						<th>Payroll Period</th>
						<th>Total Employees</th>
						<th>Gross Amount</th>
						<th>Deductions</th>
						<th>Net Amount</th>
						<th>HR Status</th>
						<th>Finance Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$payroll_records = "SELECT * FROM mrb_payroll WHERE 1=1{$payroll_scope_condition} ORDER BY payroll_period_start DESC LIMIT 20";
						$payroll_rec_result = mysqli_query($conn, $payroll_records);
						
						if (mysqli_num_rows($payroll_rec_result) > 0) {
							while ($payroll = mysqli_fetch_assoc($payroll_rec_result)) {
								// Handle both old and new column names for backwards compatibility
								$employee_count = $payroll['employee_count'] ?? $payroll['total_employees'] ?? 0;
								$deductions_amount = $payroll['deductions_amount'] ?? $payroll['total_deductions'] ?? 0;
								
								$period = date('M j', strtotime($payroll['payroll_period_start'])) . ' - ' . date('M j, Y', strtotime($payroll['payroll_period_end']));
								$status_class = ($payroll['status'] == 'Processed') ? 'completed' : 'pending';
								
								// Finance status with icons
								$finance_status = $payroll['finance_status'] ?? 'Pending';
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
								}
								
								echo "<tr>
									<td>{$period}</td>
									<td>{$employee_count}</td>
									<td>₱" . number_format($payroll['gross_amount'], 0) . "</td>
									<td>₱" . number_format($deductions_amount, 0) . "</td>
									<td>₱" . number_format($payroll['net_amount'], 0) . "</td>
									<td><span class=\"status {$status_class}\">{$payroll['status']}</span></td>
									<td><span class=\"badge {$finance_status_color}\">{$finance_status_icon} {$finance_status}</span></td>
									<td><button class=\"btn btn-sm btn-info\" onclick=\"viewPayroll({$payroll['payroll_id']})\">View</button></td>
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

		</main>
		<!-- MAIN -->
	</section>
	<!-- CONTENT -->

	<!-- Add Employee Modal -->
	<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="addEmployeeModalLabel">Add New Employee</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/add_employee.php" method="POST">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="empNumber" class="form-label">Employee Number</label>
								<input type="text" class="form-control" id="empNumber" name="emp_number" placeholder="e.g., EMP-001" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empStatus" class="form-label">Employment Status</label>
								<select class="form-select" id="empStatus" name="status" required>
									<option value="Active">Active</option>
									<option value="Inactive">Inactive</option>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="empFirstName" class="form-label">First Name</label>
								<input type="text" class="form-control" id="empFirstName" name="emp_first_name" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empLastName" class="form-label">Last Name</label>
								<input type="text" class="form-control" id="empLastName" name="emp_last_name" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="empPosition" class="form-label">Position</label>
								<input type="text" class="form-control" id="empPosition" name="position" placeholder="e.g., Meat Cutter" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empDepartment" class="form-label">Department</label>
								<select class="form-select" id="empDepartment" name="department" required>
									<option value="Production">Production</option>
									<option value="Sales">Sales</option>
									<option value="Logistics">Logistics</option>
									<option value="Administration">Administration</option>
								</select>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empStaffRole" class="form-label">System Role</label>
								<select class="form-select" id="empStaffRole" name="staff_role" required>
									<option value="" selected disabled>Select system role</option>
									<option value="butcher">Butcher</option>
									<option value="cashier">Cashier</option>
									<option value="rider">Rider</option>
									<option value="finance">Finance</option>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="empSalary" class="form-label">Monthly Salary</label>
								<input type="number" class="form-control" id="empSalary" name="salary" placeholder="0" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empHireDate" class="form-label">Hire Date</label>
								<input type="date" class="form-control" id="empHireDate" name="hire_date" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="empEmail" class="form-label">Email Address</label>
								<input type="email" class="form-control" id="empEmail" name="email" placeholder="employee@example.com" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="empPhone" class="form-label">Contact Number</label>
								<input type="tel" class="form-control" id="empPhone" name="contact_number" placeholder="09XX-XXX-XXXX" required>
							</div>
						</div>
						<div class="d-flex justify-content-end gap-2 mt-3">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn text-white" style="background-color:#C42F01;">Add Employee</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Edit Employee Modal -->
	<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="editEmployeeModalLabel">Edit Employee</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="editEmployeeForm" action="handlers/edit_employee.php" method="POST">
						<input type="hidden" id="editEmpId" name="emp_id">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpFirstName" class="form-label">First Name</label>
								<input type="text" class="form-control" id="editEmpFirstName" name="first_name" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpLastName" class="form-label">Last Name</label>
								<input type="text" class="form-control" id="editEmpLastName" name="last_name" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpPosition" class="form-label">Position</label>
								<input type="text" class="form-control" id="editEmpPosition" name="position" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpDept" class="form-label">Department</label>
								<input type="text" class="form-control" id="editEmpDept" name="department" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpStaffRole" class="form-label">System Role</label>
								<select class="form-select" id="editEmpStaffRole" name="staff_role" required>
									<option value="butcher">Butcher</option>
									<option value="cashier">Cashier</option>
									<option value="rider">Rider</option>
									<option value="finance">Finance</option>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpSalary" class="form-label">Monthly Salary</label>
								<input type="number" class="form-control" id="editEmpSalary" name="salary" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpEmail" class="form-label">Email</label>
								<input type="email" class="form-control" id="editEmpEmail" name="email" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12 mb-3">
								<label for="editEmpPhone" class="form-label">Contact Number</label>
								<input type="tel" class="form-control" id="editEmpPhone" name="contact_number" required>
							</div>
						</div>
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn text-white" style="background-color:#C42F01;">Save Changes</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Create Employee Account Modal -->
	<div class="modal fade" id="createEmployeeAccountModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="createEmployeeAccountTitle">Create Employee Account</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="POST">
					<div class="modal-body">
						<input type="hidden" name="create_employee_account" value="1">
						<input type="hidden" name="emp_id" id="accountEmpId">
						<p class="mb-2"><strong>Employee:</strong> <span id="accountEmpName"></span></p>
						<p class="mb-3"><strong>Email:</strong> <span id="accountEmpEmail"></span></p>
						<p class="mb-3"><strong>Registered Role:</strong> <span id="accountEmpRole"></span></p>
						<div class="mb-1">
							<label class="form-label">Temporary Password</label>
							<input type="password" name="staff_password" class="form-control" minlength="6" required>
						</div>
						<small class="text-muted">Only registered employees in this system can have staff accounts.</small>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-dark">Create Account</button>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- Attendance Scanner Modal -->
	<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="attendanceModalLabel">Scan QR Code for Attendance</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div id="qr-reader" class="mb-3"></div>
					<div id="scan-result" class="alert d-none"></div>
					<div class="text-center mt-3">
						<button type="button" class="btn btn-secondary" id="stopScanBtn" style="display:none;">Stop Scanner</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Attendance Settings Modal -->
	<?php
		// Get current attendance settings
		$settings_query = "SELECT * FROM mrb_attendance_settings WHERE is_active = 1 LIMIT 1";
		$settings_result = mysqli_query($conn, $settings_query);
		$current_settings = mysqli_fetch_assoc($settings_result);
		
		// Set defaults if no settings exist
		if(!$current_settings) {
			$current_settings = [
				'work_start_time' => '08:00:00',
				'late_threshold_minutes' => 15,
				'deduction_per_minute' => 10.00,
				'fixed_late_deduction' => 50.00,
				'deduction_type' => 'fixed',
				'working_days_per_month' => 26
			];
		}
	?>
	<div class="modal fade" id="attendanceSettingsModal" tabindex="-1" aria-labelledby="attendanceSettingsModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="attendanceSettingsModalLabel">Attendance & Late Policy Settings</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/update_attendance_settings.php" method="POST">
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="workStartTime" class="form-label">Work Start Time</label>
								<input type="time" class="form-control" id="workStartTime" name="work_start_time" 
									value="<?php echo substr($current_settings['work_start_time'], 0, 5); ?>" required>
								<small class="text-muted">Official start of work day</small>
							</div>
							<div class="col-md-6 mb-3">
								<label for="lateThreshold" class="form-label">Late Threshold (minutes)</label>
								<input type="number" class="form-control" id="lateThreshold" name="late_threshold" 
									value="<?php echo $current_settings['late_threshold_minutes']; ?>" min="0" max="60" required>
								<small class="text-muted">Grace period before considered late</small>
							</div>
						</div>
						
						<div class="mb-3">
							<label for="deductionType" class="form-label">Deduction Type</label>
							<select class="form-select" id="deductionType" name="deduction_type" required>
								<option value="fixed" <?php echo $current_settings['deduction_type'] == 'fixed' ? 'selected' : ''; ?>>
									Fixed Amount
								</option>
								<option value="per_minute" <?php echo $current_settings['deduction_type'] == 'per_minute' ? 'selected' : ''; ?>>
									Per Minute Late
								</option>
							</select>
						</div>
						
						<div class="row">
							<div class="col-md-6 mb-3" id="fixedDeductionDiv">
								<label for="fixedDeduction" class="form-label">Fixed Late Deduction (₱)</label>
								<input type="number" step="0.01" class="form-control" id="fixedDeduction" name="fixed_deduction" 
									value="<?php echo $current_settings['fixed_late_deduction']; ?>" min="0" required>
								<small class="text-muted">Amount deducted when late</small>
							</div>
							<div class="col-md-6 mb-3" id="perMinuteDiv">
								<label for="deductionPerMinute" class="form-label">Deduction Per Minute (₱)</label>
								<input type="number" step="0.01" class="form-control" id="deductionPerMinute" name="deduction_per_minute" 
									value="<?php echo $current_settings['deduction_per_minute']; ?>" min="0" required>
								<small class="text-muted">Amount per minute late</small>
							</div>
						</div>
						
						<div class="mb-3">
							<label for="workingDays" class="form-label">Working Days Per Month</label>
							<input type="number" class="form-control" id="workingDays" name="working_days" 
								value="<?php echo $current_settings['working_days_per_month']; ?>" min="20" max="31" required>
							<small class="text-muted">Used to calculate daily rate from monthly salary</small>
						</div>
						
						<div class="alert alert-info">
							<strong><i class='bx bx-info-circle'></i> How it works:</strong>
							<ul class="mb-0 mt-2">
								<li>Employees checking in after <strong><?php echo substr($current_settings['work_start_time'], 0, 5); ?></strong> + 
									<strong><?php echo $current_settings['late_threshold_minutes']; ?></strong> minutes grace period are marked late</li>
								<li>Daily Rate = Monthly Salary ÷ <?php echo $current_settings['working_days_per_month']; ?> days</li>
								<li>Daily Pay - Late Deduction = Net Pay per Day</li>
							</ul>
						</div>
						
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn text-white" style="background-color:#C42F01;">Save Settings</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Process Payroll Modal -->
	<div class="modal fade" id="processPayrollModal" tabindex="-1" aria-labelledby="processPayrollModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="processPayrollModalLabel">Process Payroll</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form action="handlers/process_payroll.php" method="POST">
						<div class="mb-3">
							<label for="payrollPeriodStart" class="form-label">Payroll Period Start</label>
							<input type="date" class="form-control" id="payrollPeriodStart" name="period_start" required>
						</div>
						<div class="mb-3">
							<label for="payrollPeriodEnd" class="form-label">Payroll Period End</label>
							<input type="date" class="form-control" id="payrollPeriodEnd" name="period_end" required>
						</div>
						<div class="alert alert-info">
							<strong>Info:</strong> This will calculate payroll based on attendance records for active employees during the selected period.
							<br><small>Payment = (Days Worked × Daily Rate) - Late Deductions</small>
						</div>
						<div class="d-flex justify-content-end gap-2">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-success">Process Payroll</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- QR Code Modals for Each Employee -->
	<?php
		$emp_query2 = "SELECT * FROM mrb_employees WHERE 1=1{$employee_scope_condition} ORDER BY emp_id DESC";
		$emp_result2 = mysqli_query($conn, $emp_query2);
		
		if (mysqli_num_rows($emp_result2) > 0) {
			while ($emp = mysqli_fetch_assoc($emp_result2)) {
				$qr_data = "EMP:{$emp['emp_id']}:{$emp['emp_number']}";
				$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
				
				echo "
				<div class='modal fade' id='qrModal{$emp['emp_id']}' tabindex='-1' aria-hidden='true'>
					<div class='modal-dialog modal-dialog-centered'>
						<div class='modal-content'>
							<div class='modal-header'>
								<h5 class='modal-title'>QR Code - {$emp['emp_first_name']} {$emp['emp_last_name']}</h5>
								<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
							</div>
							<div class='modal-body text-center'>
								<p><strong>Employee ID:</strong> {$emp['emp_number']}</p>
								<img src='{$qr_url}' alt='QR Code' class='qr-code-img'>
								<p class='mt-3 text-muted'>Scan this QR code for attendance</p>
								<a href='{$qr_url}' download='QR_{$emp['emp_number']}.png' class='btn btn-sm btn-primary mt-2'>
									<i class='bx bx-download'></i> Download QR Code
								</a>
							</div>
						</div>
					</div>
				</div>";
			}
		}
	?>

	<!-- View Payroll Details Modal -->
	<div class="modal fade" id="viewPayrollModal" tabindex="-1" aria-labelledby="viewPayrollModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-xl">
			<div class="modal-content">
				<div class="modal-header" style="background-color:#C42F01;">
					<h5 class="modal-title text-white" id="viewPayrollModalLabel">Payroll Details</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div id="payrollDetailsContent">
						<div class="text-center">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading...</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
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
		
		function editEmployee(id, firstName, lastName, position, department, staffRole, salary, email, phone) {
			document.getElementById('editEmpId').value = id;
			document.getElementById('editEmpFirstName').value = firstName;
			document.getElementById('editEmpLastName').value = lastName;
			document.getElementById('editEmpPosition').value = position;
			document.getElementById('editEmpDept').value = department;
			document.getElementById('editEmpStaffRole').value = staffRole || 'butcher';
			document.getElementById('editEmpSalary').value = salary;
			document.getElementById('editEmpEmail').value = email;
			document.getElementById('editEmpPhone').value = phone;
		}
		
		function deleteEmployee(empId) {
			if (confirm('Are you sure you want to delete this employee?')) {
				window.location.href = 'handlers/delete_employee.php?emp_id=' + empId;
			}
		}

		function openCreateAccountModal(empId, firstName, lastName, email, staffRole) {
			document.getElementById('accountEmpId').value = empId;
			document.getElementById('accountEmpName').textContent = (firstName + ' ' + lastName).trim();
			document.getElementById('accountEmpEmail').textContent = email;
			document.getElementById('accountEmpRole').textContent = (staffRole || '').trim() || 'Not set';
			document.getElementById('createEmployeeAccountTitle').textContent = 'Create Account for ' + (firstName + ' ' + lastName).trim();
		}
		
		function viewPayroll(payrollId) {
		// Show modal
		const modal = new bootstrap.Modal(document.getElementById('viewPayrollModal'));
		modal.show();
		
		// Fetch payroll details
		fetch('handlers/get_payroll_details.php?payroll_id=' + payrollId)
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					displayPayrollDetails(data);
				} else {
					document.getElementById('payrollDetailsContent').innerHTML = 
						'<div class="alert alert-danger">' + data.message + '</div>';
				}
			})
			.catch(error => {
				document.getElementById('payrollDetailsContent').innerHTML = 
					'<div class="alert alert-danger">Error loading payroll details</div>';
			});
	}
	
	function displayPayrollDetails(data) {
		const payroll = data.payroll;
		const employees = data.employees;
		
		let html = `
			<div class="row mb-4">
				<div class="col-md-12">
					<h6>Payroll Period: ${payroll.period_start} - ${payroll.period_end}</h6>
					<p class="text-muted">Processed: ${payroll.processed_date} | Status: <span class="badge bg-success">${payroll.status}</span></p>
				</div>
			</div>
			
			<div class="row mb-4">
				<div class="col-md-4">
					<div class="card">
						<div class="card-body text-center">
							<h6 class="text-muted">Gross Amount</h6>
							<h4 class="text-success">₱${payroll.gross_amount}</h4>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="card">
						<div class="card-body text-center">
							<h6 class="text-muted">Total Deductions</h6>
							<h4 class="text-danger">₱${payroll.deductions_amount}</h4>
						</div>
					</div>
				</div>
				<div class="col-md-4">
					<div class="card">
						<div class="card-body text-center">
							<h6 class="text-muted">Net Amount</h6>
							<h4 class="text-primary">₱${payroll.net_amount}</h4>
						</div>
					</div>
				</div>
			</div>
			
<h6 class="mb-3">Employee Breakdown (${payroll.employee_count} employees)</h6>
		<div class="row mb-4">`;
	
	employees.forEach(emp => {
		html += `
			<div class="col-md-6 col-lg-4 mb-3">
				<div class="card h-100 shadow-sm">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-start mb-2">
							<div>
								<h6 class="card-title mb-0">${emp.name}</h6>
								<small class="text-muted">${emp.emp_number} • ${emp.position}</small>
							</div>
							${emp.days_late > 0 ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i>Frequently Late</span>' : '<span class="badge bg-success"><i class="fas fa-check"></i>Always On Time</span>'}
						</div>
						<div class="mb-2">
							<small class="text-muted d-block"><strong>SSS:</strong> ${emp.sss_number}</small>
							<small class="text-muted d-block"><strong>Pag-IBIG:</strong> ${emp.pagibig_number}</small>
							<small class="text-muted d-block"><strong>PhilHealth:</strong> ${emp.philhealth_number}</small>
							<small class="text-muted d-block"><strong>TIN:</strong> ${emp.tin_number}</small>
						</div>
						<hr>
						<div class="row text-center mb-2">
							<div class="col-6">
								<small class="text-muted d-block">Days Worked</small>
								<strong class="text-primary">${emp.days_worked}</strong>
							</div>
							<div class="col-6">
								<small class="text-muted d-block">Days Late</small>
								<strong class="text-warning">${emp.days_late}</strong>
							</div>
						</div>
						<hr>
						<div class="mb-2">
							<div class="d-flex justify-content-between">
								<small>Gross Pay:</small>
								<strong class="text-success">₱${emp.gross_salary}</strong>
							</div>
							<div class="d-flex justify-content-between">
								<small>Late Deductions:</small>
								<strong class="text-danger">${emp.late_deductions > 0 ? '-₱' + emp.late_deductions : '₱0.00'}</strong>
							</div>
						</div>
						<div class="d-flex justify-content-between pt-2 border-top">
							<strong>Net Pay:</strong>
							<strong class="text-primary fs-5">₱${emp.net_salary}</strong>
						</div>
					</div>
				</div>
			</div>`;
	});
	
	html += `
			</div>`;
		
		document.getElementById('payrollDetailsContent').innerHTML = html;
	}
		
	// Attendance Settings - Deduction Type Toggle
		const deductionTypeSelect = document.getElementById('deductionType');
		const fixedDeductionDiv = document.getElementById('fixedDeductionDiv');
		const perMinuteDiv = document.getElementById('perMinuteDiv');
		
		function toggleDeductionFields() {
			if (deductionTypeSelect) {
				const selectedType = deductionTypeSelect.value;
				if (selectedType === 'fixed') {
					fixedDeductionDiv.style.display = 'block';
					perMinuteDiv.style.display = 'none';
					document.getElementById('deductionPerMinute').required = false;
					document.getElementById('fixedDeduction').required = true;
				} else {
					fixedDeductionDiv.style.display = 'none';
					perMinuteDiv.style.display = 'block';
					document.getElementById('fixedDeduction').required = false;
					document.getElementById('deductionPerMinute').required = true;
				}
			}
		}
		
		if (deductionTypeSelect) {
			deductionTypeSelect.addEventListener('change', toggleDeductionFields);
			toggleDeductionFields(); // Initialize on page load
		}
	</script>
	
<?php include 'toast-notification.php'; ?>

	<!-- Edit Employee Modal -->
	<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header red-bg text-white">
					<h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpName" class="form-label">Full Name</label>
								<input type="text" class="form-control" id="editEmpName" value="Juan dela Cruz" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpPosition" class="form-label">Position</label>
								<input type="text" class="form-control" id="editEmpPosition" value="Meat Cutter" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpDept" class="form-label">Department</label>
								<select class="form-select" id="editEmpDept" required>
									<option selected>Production</option>
									<option>Sales</option>
									<option>Logistics</option>
									<option>Administration</option>
								</select>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpSalary" class="form-label">Monthly Salary</label>
								<input type="number" class="form-control" id="editEmpSalary" value="35000" required>
							</div>
						</div>
						<div class="row">
							<div class="col-md-6 mb-3">
								<label for="editEmpEmail" class="form-label">Email</label>
								<input type="email" class="form-control" id="editEmpEmail" value="juan@example.com" required>
							</div>
							<div class="col-md-6 mb-3">
								<label for="editEmpPhone" class="form-label">Contact Number</label>
								<input type="tel" class="form-control" id="editEmpPhone" value="09XX-XXX-XXXX" required>
							</div>
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

	<!-- QR Code Modals for Each Employee -->
	<?php
		$emp_query2 = "SELECT * FROM mrb_employees ORDER BY emp_id DESC";
		$emp_result2 = mysqli_query($conn, $emp_query2);
		
		if (mysqli_num_rows($emp_result2) > 0) {
			while ($emp = mysqli_fetch_assoc($emp_result2)) {
				$qr_data = "EMP:{$emp['emp_id']}:{$emp['emp_number']}";
				$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);
				
				echo "
				<div class='modal fade' id='qrModal{$emp['emp_id']}' tabindex='-1' aria-hidden='true'>
					<div class='modal-dialog modal-dialog-centered'>
						<div class='modal-content'>
							<div class='modal-header'>
								<h5 class='modal-title'>QR Code - {$emp['emp_first_name']} {$emp['emp_last_name']}</h5>
								<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
							</div>
							<div class='modal-body text-center'>
								<p><strong>Employee ID:</strong> {$emp['emp_number']}</p>
								<img src='{$qr_url}' alt='QR Code' class='qr-code-img'>
								<p class='mt-3 text-muted'>Scan this QR code for attendance</p>
								<a href='{$qr_url}' download='QR_{$emp['emp_number']}.png' class='btn btn-sm btn-primary mt-2'>
									<i class='bx bx-download'></i> Download QR Code
								</a>
							</div>
						</div>
					</div>
				</div>";
			}
		}
	?>

<script>
	let html5QrCode;
	const attendanceModal = document.getElementById('attendanceModal');

	if (attendanceModal) {
		attendanceModal.addEventListener('shown.bs.modal', function () {
			startScanner();
		});
		
		attendanceModal.addEventListener('hidden.bs.modal', function () {
			stopScanner();
		});
	}
	
	function startScanner() {
		html5QrCode = new Html5Qrcode("qr-reader");
		
		html5QrCode.start(
			{ facingMode: "environment" },
			{
				fps: 10,
				qrbox: { width: 250, height: 250 }
			},
			(decodedText, decodedResult) => {
				// Process the scanned QR code
				processAttendance(decodedText);
			},
			(errorMessage) => {
				// Handle scan errors silently
			}
		).then(() => {
			document.getElementById('stopScanBtn').style.display = 'inline-block';
		}).catch((err) => {
			showResult('Failed to start camera: ' + err, 'danger');
		});
	}

function stopScanner() {
	if (html5QrCode) {
		html5QrCode.stop().then(() => {
			document.getElementById('stopScanBtn').style.display = 'none';
		}).catch((err) => {
			console.error('Failed to stop scanner:', err);
		});
	}
}
	
	function processAttendance(qrData) {
		// Stop scanner temporarily
		if (html5QrCode) {
			html5QrCode.pause();
		}
		
		// Send to server
		fetch('handlers/process_attendance.php', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: 'qr_data=' + encodeURIComponent(qrData)
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showResult(data.message, 'success');
				setTimeout(() => {
					location.reload();
				}, 2000);
			} else {
				showResult(data.message, 'danger');
				setTimeout(() => {
					if (html5QrCode) {
						html5QrCode.resume();
					}
				}, 2000);
			}
		})
		.catch(error => {
			showResult('Error processing attendance: ' + error, 'danger');
			setTimeout(() => {
				if (html5QrCode) {
					html5QrCode.resume();
				}
			}, 2000);
		});
	}
	
	function showResult(message, type) {
		const resultDiv = document.getElementById('scan-result');
		resultDiv.className = 'alert alert-' + type;
		resultDiv.textContent = message;
		resultDiv.classList.remove('d-none');
	}
	
	const stopBtn = document.getElementById('stopScanBtn');
	if (stopBtn && attendanceModal) {
		stopBtn.addEventListener('click', stopScanner);
	}
</script>

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

			function confirmLogout() {
				if (confirm("Are you sure you want to log out?")) {
					window.location.href = "../logout.php";
				}
			}
	</script>
	
	<script src="script.js"></script>
</body>
</html>

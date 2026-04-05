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
	$is_cashier = $current_user_role === 'cashier';
	$is_rider = $current_user_role === 'rider';
	if (!in_array($current_user_role, ['super_admin', 'admin', 'cashier', 'rider'], true)) {
		if ($current_user_role === 'butcher') {
			header("Location: products-admin.php");
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
	$order_scope_condition_mrb = "";
	$message_scope_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$order_scope_condition_mrb = " AND mrb_orders.shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
		} else {
			$order_scope_condition = " AND 1 = 0";
			$order_scope_condition_mrb = " AND 1 = 0";
			$message_scope_condition = " AND 1 = 0";
		}
	}

	if ($is_rider) {
		$_GET['order'] = 'picked up';
	}

	$assigned_rider_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_orders LIKE 'assigned_rider_id'");
	if ($assigned_rider_column_check && mysqli_num_rows($assigned_rider_column_check) === 0) {
		@mysqli_query($conn, "ALTER TABLE mrb_orders ADD COLUMN assigned_rider_id INT NULL AFTER shop_id");
	}

	$delivery_proof_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_orders LIKE 'delivery_proof_image'");
	if ($delivery_proof_column_check && mysqli_num_rows($delivery_proof_column_check) === 0) {
		@mysqli_query($conn, "ALTER TABLE mrb_orders ADD COLUMN delivery_proof_image VARCHAR(255) NULL AFTER gcash_referencenum");
	}

	$delivered_at_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_orders LIKE 'delivered_at'");
	if ($delivered_at_column_check && mysqli_num_rows($delivered_at_column_check) === 0) {
		@mysqli_query($conn, "ALTER TABLE mrb_orders ADD COLUMN delivered_at DATETIME NULL AFTER delivery_proof_image");
	}

	$delivered_by_column_check = mysqli_query($conn, "SHOW COLUMNS FROM mrb_orders LIKE 'delivered_by_rider_id'");
	if ($delivered_by_column_check && mysqli_num_rows($delivered_by_column_check) === 0) {
		@mysqli_query($conn, "ALTER TABLE mrb_orders ADD COLUMN delivered_by_rider_id INT NULL AFTER delivered_at");
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
	<script type="text/javascript" src="https://cdn.emailjs.com/dist/email.min.js"></script>
	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
	<script type="text/javascript">
	(function() {
		emailjs.init("ngVuTALHVeNP-vWuZ");
	})();
	</script>
    <style>
        .order-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e3e6ea;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg,rgb(176, 61, 26), #AB2A02);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-color: #007bff;
        }

        .order-card .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .order-card .row:last-child {
            margin-bottom: 0;
        }

        .order-card .col {
            flex: 1;
            margin-right: 15px;
            min-width: 200px;
        }

        .order-card .col:last-child {
            margin-right: 0;
        }

        .order-card p {
            margin: 0;
            font-size: 14px;
            color: #495057;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-card p strong {
            color: #212529;
            font-weight: 600;
            min-width: 80px;
        }

        .order-card h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #007bff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-header {
            background: linear-gradient(135deg, rgb(176, 61, 26) 0%, #AB2A02 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-date {
            font-size: 12px;
            opacity: 0.9;
        }

		.order-header-meta {
			display: flex;
			flex-direction: column;
			align-items: flex-end;
			gap: 6px;
		}

		.order-header-badges {
			display: flex;
			gap: 6px;
			flex-wrap: wrap;
			justify-content: flex-end;
		}

        .payment-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-cash {
            background-color: #e8f5e8;
            color: #2d5a2d;
            border: 1px solid #b3d9b3;
        }

        .payment-gcash {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .info-icon {
            width: 16px;
            height: 16px;
            background: #007bff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }

        .quantity-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .location-text {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #007bff;
            font-style: italic;
        }

        .reference-number {
            font-family: 'Courier New', monospace;
            background: #f1f3f4;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .order-card .row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-card .col {
                margin-right: 0;
                margin-bottom: 8px;
                min-width: unset;
                width: 100%;
            }
            
            .order-header {
                flex-direction: column;
                text-align: center;
                gap: 5px;
            }
        }

        .orders-container {
            max-height: 70vh;
            min-height: 300px; /* Add minimum height to prevent cramped appearance */
            overflow-y: auto;
            padding-right: 10px;
        }

        .orders-container::-webkit-scrollbar {
            width: 6px;
        }

        .orders-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .orders-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .orders-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Email Notification Styles */
        .email-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            min-width: 300px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            transform: translateX(500px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .email-notification.show {
            transform: translateX(0);
        }

        .email-notification.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-left: 4px solid #155724;
        }

        .email-notification.error {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
            border-left: 4px solid #721c24;
        }

        .email-notification.sending {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-left: 4px solid #004085;
        }

        .notification-icon {
            font-size: 20px;
            min-width: 20px;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .notification-message {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.3;
        }

        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.8;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notification-close:hover {
            opacity: 1;
        }

        /* Progress bar for sending notification */
        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .notification-progress::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            width: 0%;
            animation: progressBar 3s ease-in-out;
        }

        @keyframes progressBar {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
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
		<?php if ($is_cashier): ?>
			<li  class="active">
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
		<?php elseif ($is_rider): ?>
			<li class="active">
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
			<li>
				<a href="chat-admin.php">
				<i class='bx bxs-chat'></i>
				<?php 
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
			<li  class="active">
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
			<a href="#" class="nav-link">Categories</a>
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
					$user_pic = 'Images/anonymous.jpg'; // Default image if no user picture is found
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
			<h1>Orders</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Orders</a></li>
			</ul>
			</div>
		</div>	
			
		
		<div class="table-data">
			<div class="order">
			<div class="head mb-0">
				<h3>Orders</h3>
			</div>
            <div class="orders-container">
				<div class="dropdown mb-3">
				<?php
					// Get unseen counts for each status
					$all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
					$all_unseen_result = mysqli_query($conn, $all_unseen_query);
					$all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];

					$delivered_unseen_query = "SELECT COUNT(*) AS delivered_unseen FROM mrb_orders WHERE order_status = 'delivered' AND seen_byadmin = 'false'{$order_scope_condition}";
					$delivered_unseen_result = mysqli_query($conn, $delivered_unseen_query);
					$delivered_unseen_count = mysqli_fetch_assoc($delivered_unseen_result)['delivered_unseen'];

					$confirmed_unseen_query = "SELECT COUNT(*) AS confirmed_unseen FROM mrb_orders WHERE order_status = 'confirmed' AND seen_byadmin = 'false'{$order_scope_condition}";
					$confirmed_unseen_result = mysqli_query($conn, $confirmed_unseen_query);
					$confirmed_unseen_count = mysqli_fetch_assoc($confirmed_unseen_result)['confirmed_unseen'];

					$pending_unseen_query = "SELECT COUNT(*) AS pending_unseen FROM mrb_orders WHERE order_status = 'pending' AND seen_byadmin = 'false'{$order_scope_condition}";
					$pending_unseen_result = mysqli_query($conn, $pending_unseen_query);
					$pending_unseen_count = mysqli_fetch_assoc($pending_unseen_result)['pending_unseen'];

					$packed_unseen_query = "SELECT COUNT(*) AS packed_unseen FROM mrb_orders WHERE order_status = 'packed' AND seen_byadmin = 'false'{$order_scope_condition}";
					$packed_unseen_result = mysqli_query($conn, $packed_unseen_query);
					$packed_unseen_count = mysqli_fetch_assoc($packed_unseen_result)['packed_unseen'];

					$pickedup_unseen_query = "SELECT COUNT(*) AS pickedup_unseen FROM mrb_orders WHERE order_status = 'picked up' AND seen_byadmin = 'false'{$order_scope_condition}";
					$pickedup_unseen_result = mysqli_query($conn, $pickedup_unseen_query);
					$pickedup_unseen_count = mysqli_fetch_assoc($pickedup_unseen_result)['pickedup_unseen'];

					// Mark orders as seen when viewing specific status pages
					if(isset($_GET['order'])) {
						$status = $_GET['order'];
						if($status === 'delivered') {
							$mark_seen_query = "UPDATE mrb_orders SET seen_byadmin = 'true' WHERE order_status = 'delivered' AND seen_byadmin = 'false'{$order_scope_condition}";
							mysqli_query($conn, $mark_seen_query);
						} else if($status === 'confirmed') {
							$mark_seen_query = "UPDATE mrb_orders SET seen_byadmin = 'true' WHERE order_status = 'confirmed' AND seen_byadmin = 'false'{$order_scope_condition}";
							mysqli_query($conn, $mark_seen_query);
						} else if($status === 'pending') {
							$mark_seen_query = "UPDATE mrb_orders SET seen_byadmin = 'true' WHERE order_status = 'pending' AND seen_byadmin = 'false'{$order_scope_condition}";
							mysqli_query($conn, $mark_seen_query);
						} else if($status === 'packed') {
							$mark_seen_query = "UPDATE mrb_orders SET seen_byadmin = 'true' WHERE order_status = 'packed' AND seen_byadmin = 'false'{$order_scope_condition}";
							mysqli_query($conn, $mark_seen_query);
						} else if($status === 'picked up') {
							$mark_seen_query = "UPDATE mrb_orders SET seen_byadmin = 'true' WHERE order_status = 'picked up' AND seen_byadmin = 'false'{$order_scope_condition}";
							mysqli_query($conn, $mark_seen_query);
						}
					}
					
					if($is_rider) {
						echo"<button class='btn' type='button'>
							<i class='bx bxs-filter-alt'></i>Picked Up
						</button>";
					} else if(!isset($_GET['order']) ||  $_GET['order'] === 'all') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i> All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$all_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}else if(isset($_GET['order']) && $_GET['order'] === 'delivered') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$delivered_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}else if(isset($_GET['order']) && $_GET['order'] === 'confirmed') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$confirmed_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}else if(isset($_GET['order']) && $_GET['order'] === 'pending') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$pending_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}
					else if(isset($_GET['order']) && $_GET['order'] === 'cancelled') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Cancelled
						</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
					 if($confirmed_unseen_count > 0) {
						 echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
					 }
					 echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
					 if($pending_unseen_count > 0) {
						 echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
					 }
					 echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
					 if($packed_unseen_count > 0) {
						 echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
					 }
					 echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
					 if($pickedup_unseen_count > 0) {
						 echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
					 }
					 echo "</a></li>
						</ul>";
					}
					else if(isset($_GET['order']) && $_GET['order'] === 'packed') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$packed_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=picked up'>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pickedup_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}
					else if(isset($_GET['order']) && $_GET['order'] === 'picked up') {
						echo"<button class='btn dropdown-toggle' type='button' id='dropdownMenuButton1' data-bs-toggle='dropdown' aria-expanded='false'>
							<i class='bx bxs-filter-alt'></i>Picked Up";
						if($pickedup_unseen_count > 0) {
							echo " <span class='badge bg-danger'>$pickedup_unseen_count</span>";
						}
						echo "</button>

						<ul class='dropdown-menu'>
							<li><a class='dropdown-item position-relative' href='orders-admin.php'>All";
						if($all_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$all_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=delivered'>Delivered";
						if($delivered_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$delivered_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=confirmed'>Confirmed";
						if($confirmed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$confirmed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=pending'>Pending";
						if($pending_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$pending_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item position-relative' href='orders-admin.php?order=packed'>Packed";
						if($packed_unseen_count > 0) {
							echo " <span class='badge bg-danger ms-2'>$packed_unseen_count</span>";
						}
						echo "</a></li>
							<li><a class='dropdown-item' href='orders-admin.php?order=cancelled'>Cancelled</a></li>
						</ul>";
					}
				
				?>
				
						
				</div>
            <?php

					// Handle order status updates
					if(!$is_rider && isset($_POST['assign_rider_submit'])) {
						$assign_order_id = intval($_POST['assign_order_id'] ?? 0);
						$assign_rider_id = intval($_POST['assign_rider_id'] ?? 0);

						if ($assign_order_id <= 0 || $assign_rider_id <= 0) {
							echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Please select a rider first.', 'error'); });</script>";
						} else {
							$order_lookup_query = "SELECT order_status, shop_id FROM mrb_orders WHERE order_id = '{$assign_order_id}'{$order_scope_condition} LIMIT 1";
							$order_lookup_result = mysqli_query($conn, $order_lookup_query);
							if (!$order_lookup_result || mysqli_num_rows($order_lookup_result) === 0) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Order is not in your store.', 'error'); });</script>";
							} else {
								$order_lookup = mysqli_fetch_assoc($order_lookup_result);
								$order_shop_id = isset($order_lookup['shop_id']) ? (int)$order_lookup['shop_id'] : 0;
								$order_status_text = strtolower((string)($order_lookup['order_status'] ?? ''));

								if ($order_status_text !== 'packed') {
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Rider can only be assigned when the order is packed.', 'error'); });</script>";
								} else {
									$rider_check_query = "SELECT user_id FROM mrb_users WHERE user_id = '{$assign_rider_id}' AND user_type = 'rider' AND shop_id = '{$order_shop_id}' LIMIT 1";
									$rider_check_result = mysqli_query($conn, $rider_check_query);

									if (!$rider_check_result || mysqli_num_rows($rider_check_result) === 0) {
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Selected rider is invalid for this shop.', 'error'); });</script>";
									} else {
										$assign_update_query = "UPDATE mrb_orders SET assigned_rider_id = '{$assign_rider_id}', seen_byuser = 'false', seen_byadmin = 'false' WHERE order_id = '{$assign_order_id}'{$order_scope_condition}";
										if (mysqli_query($conn, $assign_update_query)) {
											echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Rider assigned successfully.', 'success'); setTimeout(() => window.location.href='orders-admin.php?order=packed', 1200); });</script>";
										}
									}
								}
							}
						}
					}

					if($is_rider && isset($_POST['mark_delivered_submit'])) {
						$deliver_order_id = intval($_POST['deliver_order_id'] ?? 0);

						if ($deliver_order_id <= 0) {
							echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Invalid order selected.', 'error'); });</script>";
						} elseif (!isset($_FILES['delivery_proof']) || (int)($_FILES['delivery_proof']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
							echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Please upload a delivery proof photo.', 'error'); });</script>";
						} else {
							$proof_tmp = $_FILES['delivery_proof']['tmp_name'];
							$proof_size = (int)($_FILES['delivery_proof']['size'] ?? 0);
							$allowed_mimes = [
								'image/jpeg' => 'jpg',
								'image/png' => 'png',
								'image/webp' => 'webp'
							];

							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							$proof_mime = $finfo ? finfo_file($finfo, $proof_tmp) : '';
							if ($finfo) {
								finfo_close($finfo);
							}

							if (!isset($allowed_mimes[$proof_mime])) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Invalid image format. Use JPG, PNG, or WEBP.', 'error'); });</script>";
							} elseif ($proof_size > (5 * 1024 * 1024)) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Image is too large. Max size is 5MB.', 'error'); });</script>";
							} else {
								$order_check_query = "SELECT order_status, assigned_rider_id FROM mrb_orders WHERE order_id = '{$deliver_order_id}'{$order_scope_condition} LIMIT 1";
								$order_check_result = mysqli_query($conn, $order_check_query);

								if (!$order_check_result || mysqli_num_rows($order_check_result) === 0) {
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Order is not in your store.', 'error'); });</script>";
								} else {
									$order_check_row = mysqli_fetch_assoc($order_check_result);
									$current_status = strtolower((string)($order_check_row['order_status'] ?? ''));
									$assigned_rider_id_check = (int)($order_check_row['assigned_rider_id'] ?? 0);

									if ($current_status !== 'picked up') {
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Only picked up orders can be marked delivered.', 'error'); });</script>";
									} elseif ($assigned_rider_id_check !== $current_user_id) {
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'You are not assigned to this order.', 'error'); });</script>";
									} else {
										$proof_dir_absolute = __DIR__ . '/../uploads/delivery_proofs';
										if (!is_dir($proof_dir_absolute)) {
											@mkdir($proof_dir_absolute, 0775, true);
										}

										$proof_ext = $allowed_mimes[$proof_mime];
										$proof_file_name = 'delivery_' . $deliver_order_id . '_' . $current_user_id . '_' . time() . '.' . $proof_ext;
										$proof_target_absolute = $proof_dir_absolute . '/' . $proof_file_name;
										$proof_db_path = 'uploads/delivery_proofs/' . $proof_file_name;

										if (!move_uploaded_file($proof_tmp, $proof_target_absolute)) {
											echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Failed to upload delivery proof image.', 'error'); });</script>";
										} else {
											$proof_db_path_escaped = mysqli_real_escape_string($conn, $proof_db_path);
											$deliver_update_query = "UPDATE mrb_orders
												SET order_status = 'delivered',
													delivery_proof_image = '{$proof_db_path_escaped}',
													delivered_at = NOW(),
													delivered_by_rider_id = '{$current_user_id}',
													seen_byuser = 'false',
													seen_byadmin = 'false'
												WHERE order_id = '{$deliver_order_id}'{$order_scope_condition}";

											if (mysqli_query($conn, $deliver_update_query)) {
												echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Order marked as delivered with proof photo.', 'success'); setTimeout(() => window.location.href='orders-admin.php?order=picked%20up', 1400); });</script>";
											} else {
												@unlink($proof_target_absolute);
												echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Failed to update delivered status.', 'error'); });</script>";
											}
										}
									}
								}
							}
						}
					}

					if(isset($_GET['order_id']) && isset($_GET['action'])) {
						$order_id = intval($_GET['order_id']);
						$action = $_GET['action'];
						if ($is_rider) {
							$action = '';
						}
						if (!$is_super_admin) {
							$scope_check_query = "SELECT order_id FROM mrb_orders WHERE order_id = '{$order_id}'{$order_scope_condition} LIMIT 1";
							$scope_check_result = mysqli_query($conn, $scope_check_query);
							if (!$scope_check_result || mysqli_num_rows($scope_check_result) === 0) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Order is not in your store.', 'error'); setTimeout(() => window.location.href='orders-admin.php', 1500); });</script>";
								$action = '';
							}
						}
						
						if($action === 'confirmed') {
							$update_query = "UPDATE mrb_orders SET order_status = 'confirmed', seen_byuser = 'false', seen_byadmin = 'false'  WHERE order_id = '$order_id'{$order_scope_condition}";
							if(mysqli_query($conn, $update_query)) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Order confirmed successfully!', 'success'); setTimeout(() => window.location.href='orders-admin.php', 1500); });</script>";
							}
						} else if($action === 'packed') {
							$update_query = "UPDATE mrb_orders SET order_status = 'packed', seen_byuser = 'false', seen_byadmin = 'false' WHERE order_id = '$order_id'{$order_scope_condition}";
							if(mysqli_query($conn, $update_query)) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Order marked as packed!', 'success'); setTimeout(() => window.location.href='orders-admin.php', 1500); });</script>";
							}
						} else if($action === 'picked up') {
							$assignment_check_query = "SELECT assigned_rider_id FROM mrb_orders WHERE order_id = '$order_id'{$order_scope_condition} LIMIT 1";
							$assignment_check_result = mysqli_query($conn, $assignment_check_query);
							$assignment_row = $assignment_check_result ? mysqli_fetch_assoc($assignment_check_result) : null;
							$assigned_rider_id = isset($assignment_row['assigned_rider_id']) ? (int)$assignment_row['assigned_rider_id'] : 0;

							if ($assigned_rider_id <= 0) {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Assign a rider first before marking as picked up.', 'error'); setTimeout(() => window.location.href='orders-admin.php?order=packed', 1400); });</script>";
							} else {
								$update_query = "UPDATE mrb_orders SET order_status = 'picked up', seen_byuser = 'false', seen_byadmin = 'false' WHERE order_id = '$order_id'{$order_scope_condition}";
								if(mysqli_query($conn, $update_query)) {
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Order marked as picked up!', 'success'); setTimeout(() => window.location.href='orders-admin.php', 1500); });</script>";
								}
							}
						} else if($action === 'delivered') {
							echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Info', 'Only riders can mark orders as delivered using proof photo upload.', 'info'); });</script>";
						} else if($action === 'cancelled') {
							include 'cancel_order_with_email.php';
							$result = cancelOrderWithEmail($order_id);
							
							if($result['success']) {								// Don't call the function immediately - let DOMContentLoaded handle it
								// The email data will be processed by the JavaScript at the bottom
							} else {
								echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Failed to cancel order: " . addslashes($result['message']) . "', 'error'); setTimeout(() => window.location.href='orders-admin.php', 2000); });</script>";
							}
						}
					}

					$order_status_filter = '';
					if ($is_rider) {
						$order_status_filter = " AND mrb_orders.order_status = 'picked up' AND mrb_orders.assigned_rider_id = {$current_user_id}";
					} else if(isset($_GET['order']) && $_GET['order'] === 'delivered') {
						$order_status_filter = " AND mrb_orders.order_status = 'delivered'";
					} else if(isset($_GET['order']) && $_GET['order'] === 'pending') {
						$order_status_filter = " AND mrb_orders.order_status = 'pending'";
					} else if(isset($_GET['order']) && $_GET['order'] === 'confirmed') {
						$order_status_filter = " AND mrb_orders.order_status = 'confirmed'";
					} else if(isset($_GET['order']) && $_GET['order'] === 'cancelled') {
						$order_status_filter = " AND mrb_orders.order_status = 'cancelled'";
					} else if(isset($_GET['order']) && $_GET['order'] === 'packed') {
						$order_status_filter = " AND mrb_orders.order_status = 'packed'";
					} else if(isset($_GET['order']) && $_GET['order'] === 'picked up') {
						$order_status_filter = " AND mrb_orders.order_status = 'picked up'";
					}

					$query = "SELECT mrb_orders.order_id,
									mrb_users.user_name,
									mrb_users.user_mname,
									mrb_users.user_lname,
									mrb_users.user_email,
									mrb_fireex.prod_name,
									mrb_orders.order_quantity,
									mrb_orders.order_status,
									mrb_orders.order_location,
									mrb_orders.order_dateordered,
									mrb_orders.order_paymentmethod,
									mrb_orders.gcash_referencenum,
									mrb_orders.cut_preference,
									mrb_orders.delivery_date,
									mrb_orders.preferred_weight,
									mrb_orders.processing_options,
									mrb_orders.special_instructions,
									mrb_orders.delivery_proof_image,
									mrb_orders.delivered_at,
									mrb_orders.shop_id,
									mrb_orders.assigned_rider_id,
									assigned_rider.user_name AS assigned_rider_fname,
									assigned_rider.user_mname AS assigned_rider_mname,
									assigned_rider.user_lname AS assigned_rider_lname,
									assigned_rider.user_contactnum AS assigned_rider_contact
								 FROM mrb_orders
								 JOIN mrb_users ON mrb_orders.user_id = mrb_users.user_id
								 JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
								 LEFT JOIN mrb_users AS assigned_rider ON mrb_orders.assigned_rider_id = assigned_rider.user_id
								 WHERE 1 = 1{$order_scope_condition_mrb}{$order_status_filter}
								 ORDER BY mrb_orders.order_dateordered DESC";

					
                    $result = mysqli_query($conn, $query);
					if(!$result || mysqli_num_rows($result) === 0) {
						if(isset($_GET['order']) && $_GET['order'] !== 'all') {
							echo "<p class='text-center'>No {$_GET['order']} orders found.</p>";
						} else {
							echo "<p class='text-center'>No orders found.</p>";
						}
					}
					$riders_by_shop = [];
					while ($row = mysqli_fetch_assoc($result)) {
                        $order_id = $row['order_id'];
                        $receiver = $row['user_name']." ".$row['user_mname']." ".$row['user_lname'];
                        $product = $row['prod_name'];
                        $quantity = $row['order_quantity'];
                        $location = $row['order_location'];
                        $date_ordered = date('F j, Y', strtotime($row['order_dateordered']));
                        $time_ordered = date('g:i A', strtotime($row['order_dateordered']));
                        $payment_method = $row['order_paymentmethod'];
                        $reference_number = $row['gcash_referencenum'];
                        
                        // Meat-specific fields
                        $cut_preference = $row['cut_preference'] ?? 'N/A';
                        $delivery_date = $row['delivery_date'] ? date('F j, Y', strtotime($row['delivery_date'])) : 'N/A';
                        $preferred_weight = $row['preferred_weight'] ? $row['preferred_weight'] . ' kg' : 'N/A';
                        $processing_options = $row['processing_options'] ?? 'None';
                        $special_instructions = $row['special_instructions'] ?? 'None';
						$shop_id = isset($row['shop_id']) ? (int)$row['shop_id'] : 0;
						$assigned_rider_id = isset($row['assigned_rider_id']) ? (int)$row['assigned_rider_id'] : 0;
						$assigned_rider_name = trim(implode(' ', array_filter([
							$row['assigned_rider_fname'] ?? '',
							$row['assigned_rider_mname'] ?? '',
							$row['assigned_rider_lname'] ?? ''
						])));
						$assigned_rider_name = $assigned_rider_name !== '' ? $assigned_rider_name : 'Unassigned';
						$assigned_rider_contact = trim((string)($row['assigned_rider_contact'] ?? ''));
						$delivery_proof_image = trim((string)($row['delivery_proof_image'] ?? ''));
						$delivered_at_text = !empty($row['delivered_at']) ? date('F j, Y g:i A', strtotime($row['delivered_at'])) : 'N/A';

						if(!$is_rider && $shop_id > 0 && !isset($riders_by_shop[$shop_id])) {
							$riders_by_shop[$shop_id] = [];
							$riders_query = "SELECT user_id, user_name, user_mname, user_lname FROM mrb_users WHERE user_type = 'rider' AND shop_id = '{$shop_id}' ORDER BY user_name, user_lname";
							$riders_result = mysqli_query($conn, $riders_query);
							if($riders_result) {
								while($rider_row = mysqli_fetch_assoc($riders_result)) {
									$rider_full_name = trim(implode(' ', array_filter([
										$rider_row['user_name'] ?? '',
										$rider_row['user_mname'] ?? '',
										$rider_row['user_lname'] ?? ''
									])));
									$riders_by_shop[$shop_id][] = [
										'user_id' => (int)$rider_row['user_id'],
										'name' => $rider_full_name
									];
								}
							}
						}
                        
						// Determine payment badge class
                        $payment_class = $payment_method === 'gcash' ? 'payment-gcash' : 'payment-cash';
						$status = strtolower($row['order_status']);

						$status_badge_html = "<span class='badge bg-secondary'>" . htmlspecialchars(ucfirst($row['order_status'])) . "</span>";
						if($status === 'pending') {
							$status_badge_html = "<span class='badge bg-warning text-dark'>Pending</span>";
						} else if($status === 'confirmed') {
							$status_badge_html = "<span class='badge bg-info text-dark'>Confirmed</span>";
						} else if($status === 'packed') {
							$status_badge_html = "<span class='badge bg-primary'>Packed</span>";
						} else if($status === 'picked up') {
							$status_badge_html = "<span class='badge bg-warning text-dark'>Picked Up</span>";
						} else if($status === 'delivered') {
							$status_badge_html = "<span class='badge bg-success'>Delivered</span>";
						} else if($status === 'cancelled') {
							$status_badge_html = "<span class='badge bg-secondary'>Cancelled</span>";
						}

						$rider_assignment_badge_html = "";
						if($status === 'packed' || $status === 'picked up') {
							if($assigned_rider_id > 0) {
								$rider_assignment_badge_html = "<span class='badge bg-success-subtle text-success border border-success-subtle'>Rider Assigned</span>";
							} else {
								$rider_assignment_badge_html = "<span class='badge bg-danger-subtle text-danger border border-danger-subtle'>Rider Not Assigned</span>";
							}
						}
                    
                        echo"
                            <div class='order-card'>
                                <div class='order-header'>
                                    <div class='order-id'>
                                        <span class='info-icon'>#</span>
                                        Order #$order_id
                                    </div>
									<div class='order-header-meta'>
										<div class='order-date'>$date_ordered</div>
										<div class='order-header-badges'>{$status_badge_html}{$rider_assignment_badge_html}</div>
									</div>
                                </div>
                                
                                <div class='row'>
                                    <div class='col d-flex justify-content-center flex-column'>
                                        <p style='height:50px;'><strong>Customer:</strong> $receiver</p>
                                        <p style='height:50px;'><strong>Payment:</strong> <span class='payment-badge $payment_class'>$payment_method</span></p>
                                    </div>
                                    <div class='col'>
                                        <p><strong>Product:</strong> $product<span class='quantity-badge'>$quantity</span></p>
                                    </div>
                                </div>
                                
                                <div class='row'>
                                    <div class='col'>
                                        <p><strong>Location:</strong></p>
                                        <div class='location-text'>$location</div>
                                    </div>
                                    " . ($reference_number ? "
                                    <div class='col'>
                                        <p><strong>Reference:</strong></p>
                                        <div class='reference-number'>$reference_number</div>
                                    </div>
                                    " : "") . "
                                </div>
                                
                                <div class='row mt-3'>
                                    <div class='col-12'>
                                        <div style='background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 15px; border-radius: 8px; border-left: 4px solid #8B0000;'>
                                            <h6 style='color: #8B0000; margin-bottom: 10px; font-weight: 600;'><i class='bi bi-card-checklist'></i> Meat Order Details</h6>
                                            <div class='row'>
                                                <div class='col-md-6'>
                                                    <p class='mb-2'><strong>Cut Preference:</strong> <span style='color: #495057;'>$cut_preference</span></p>
                                                    <p class='mb-2'><strong>Preferred Weight:</strong> <span style='color: #495057;'>$preferred_weight</span></p>
                                                    <p class='mb-2'><strong>Delivery Date:</strong> <span style='color: #495057;'>$delivery_date</span></p>
													<p class='mb-2'><strong>Assigned Rider:</strong> <span style='color: #495057;'>" . htmlspecialchars($assigned_rider_name) . "</span></p>
                                                </div>
                                                <div class='col-md-6'>
                                                    <p class='mb-2'><strong>Processing:</strong> <span style='color: #495057;'>$processing_options</span></p>
													<p class='mb-2'><strong>Rider Contact:</strong> <span style='color: #495057;'>" . htmlspecialchars($assigned_rider_contact !== '' ? $assigned_rider_contact : 'N/A') . "</span></p>
													<p class='mb-2'><strong>Delivered At:</strong> <span style='color: #495057;'>" . htmlspecialchars($delivered_at_text) . "</span></p>
                                                    <p class='mb-2'><strong>Special Instructions:</strong></p>
                                                    <div style='background-color: white; padding: 8px; border-radius: 4px; min-height: 40px; font-size: 0.9em; color: #495057;'>$special_instructions</div>
                                                </div>
                                            </div>
											" . (!empty($delivery_proof_image) ? "
											<div class='mt-3'>
												<p class='mb-2'><strong>Delivery Proof:</strong></p>
												<a href='../" . htmlspecialchars($delivery_proof_image, ENT_QUOTES) . "' target='_blank' class='text-decoration-none'>
													<img src='../" . htmlspecialchars($delivery_proof_image, ENT_QUOTES) . "' alt='Delivery Proof' style='max-width: 220px; max-height: 160px; border-radius: 8px; border: 1px solid #ddd; object-fit: cover;'>
												</a>
											</div>" : "") . "
                                        </div>
                                    </div>
                                </div>
                                <div class='row'>
                                    <div class='col text-center'>";
                                    
									
									if($status === 'pending') {
                                        echo "<div class='d-flex gap-2 justify-content-center'>";
                                        echo "<a href='orders-admin.php?order_id={$order_id}&action=confirmed' class='btn btn-success btn-sm text-dark' onclick='return confirm(\"Confirm this order?\")'>Confirm</a>";
                                        echo "<button type='button' class='btn btn-danger btn-sm text-dark' data-bs-toggle='modal' data-bs-target='#cancelModal{$order_id}'>Cancel</button>";
                                        echo "</div>";
                                    } else if($status === 'confirmed') {
                                        echo "<div class='d-flex gap-2 justify-content-center'>";
                                        echo "<a href='orders-admin.php?order_id={$order_id}&action=packed' class='btn btn-info btn-sm text-dark' onclick='return confirm(\"Mark this order as packed?\")'>Mark as Packed</a>";
                                        echo "<button type='button' class='btn btn-danger btn-sm text-dark' data-bs-toggle='modal' data-bs-target='#cancelModal{$order_id}'>Cancel</button>";
                                        echo "</div>";
                                    } else if($status === 'packed') {
                                        echo "<div class='d-flex gap-2 justify-content-center'>";
											if($assigned_rider_id > 0) {
												echo "<a href='orders-admin.php?order_id={$order_id}&action=picked up' class='btn btn-warning btn-sm text-dark' onclick='return confirm(\"Mark this order as picked up?\")'>Mark as Picked Up</a>";
											} else {
												echo "<button type='button' class='btn btn-warning btn-sm text-dark' disabled title='Assign rider first'>Mark as Picked Up</button>";
											}
                                        echo "<button type='button' class='btn btn-danger btn-sm text-dark' data-bs-toggle='modal' data-bs-target='#cancelModal{$order_id}'>Cancel</button>";
                                        echo "</div>";

											if(!$is_rider) {
												echo "<div class='mt-2 d-flex flex-wrap gap-2 justify-content-center align-items-center'>";
												if(!empty($riders_by_shop[$shop_id])) {
													echo "<form method='POST' class='d-flex gap-2 flex-wrap justify-content-center align-items-center'>
														<input type='hidden' name='assign_rider_submit' value='1'>
														<input type='hidden' name='assign_order_id' value='{$order_id}'>
														<select name='assign_rider_id' class='form-select form-select-sm' style='min-width: 220px;' required>
															<option value=''>Select rider...</option>";
													foreach($riders_by_shop[$shop_id] as $rider_option) {
														$selected = ((int)$rider_option['user_id'] === $assigned_rider_id) ? " selected" : "";
														echo "<option value='" . (int)$rider_option['user_id'] . "'{$selected}>" . htmlspecialchars($rider_option['name']) . "</option>";
													}
													echo "</select>
														<button type='submit' class='btn btn-outline-primary btn-sm'>Assign Rider</button>
													</form>";
												} else {
													echo "<span class='badge bg-secondary'>No rider account found for this shop.</span>";
												}
												echo "</div>";
											}
									} else if($status === 'picked up') {
										if($is_rider && $assigned_rider_id === $current_user_id) {
											echo "<form method='POST' enctype='multipart/form-data' class='d-flex gap-2 flex-wrap justify-content-center align-items-center'>
												<input type='hidden' name='mark_delivered_submit' value='1'>
												<input type='hidden' name='deliver_order_id' value='{$order_id}'>
												<input type='file' name='delivery_proof' class='form-control form-control-sm' accept='image/jpeg,image/png,image/webp' style='max-width: 260px;' required>
												<button type='submit' class='btn btn-primary btn-sm text-dark' onclick='return confirm(\"Upload proof and mark this order as delivered?\")'>Mark as Delivered</button>
											</form>";
										} else if($is_rider) {
											echo "<span class='badge bg-warning text-dark'>Assigned to another rider</span>";
										} else {
											echo "<span class='badge bg-secondary'>Waiting for rider delivery confirmation</span>";
										}
                                    } else if($status === 'delivered') {
                                        echo "<span class='badge bg-success'>Order Delivered</span>";
                                    } else if($status === 'cancelled') {
                                        echo "<span class='badge bg-secondary'>Order Cancelled</span>";
                                    }
                                    
                                echo"		
                                    </div>
                                </div>
                            </div>
                        ";

                        // Add cancellation modal for each order
						if(!$is_rider && ($status === 'pending' || $status === 'confirmed' || $status === 'packed')) {
                            echo "
                            <div class='modal fade' id='cancelModal{$order_id}' tabindex='-1' aria-labelledby='cancelModalLabel{$order_id}' aria-hidden='true'>
                                <div class='modal-dialog'>
                                    <div class='modal-content'>
                                        <div class='modal-header bg-danger text-white'>
                                            <h1 class='modal-title fs-5' id='cancelModalLabel{$order_id}'>Cancel Order #{$order_id}</h1>
                                            <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                        </div>
                                        <div class='modal-body'>
                                            <div class='mb-3'>
                                                <h6>Order Details:</h6>
                                                <p><strong>Customer:</strong> {$receiver}</p>
                                                <p><strong>Product:</strong> {$product}</p>
                                                <p><strong>Quantity:</strong> {$quantity}</p>
                                                <p><strong>Email:</strong> {$row['user_email']}</p>
                                            </div>
                                            <form id='cancelForm{$order_id}'>
                                                <input type='hidden' value='{$order_id}' name='cancel_order_id' id='cancel_order_id{$order_id}'>
                                                <input type='hidden' value='{$row['user_email']}' name='cancel_email' id='cancel_email{$order_id}'>
                                                <input type='hidden' value='{$receiver}' name='cancel_customer_name' id='cancel_customer_name{$order_id}'>
                                                <input type='hidden' value='{$product}' name='cancel_product_name' id='cancel_product_name{$order_id}'>
                                                
                                                <div class='mb-3'>
                                                    <label for='cancelReason{$order_id}' class='form-label'>Cancellation Reason <span class='text-danger'>*</span></label>
                                                    <select class='form-select' id='cancelReason{$order_id}' name='cancel_reason' required>
                                                        <option value=''>Select a reason...</option>
                                                        <option value='Out of stock'>Out of stock</option>
                                                        <option value='Customer request'>Customer request</option>
                                                        <option value='Payment issues'>Payment issues</option>
                                                        <option value='Delivery issues'>Delivery issues</option>
                                                        <option value='Quality concerns'>Quality concerns</option>
                                                        <option value='Administrative error'>Administrative error</option>
                                                        <option value='Other'>Other</option>
                                                    </select>
                                                </div>
                                                
                                                <div class='mb-3'>
                                                    <label for='cancelMessage{$order_id}' class='form-label'>Additional Message (Optional)</label>
                                                    <textarea class='form-control' id='cancelMessage{$order_id}' name='cancel_message' rows='3' placeholder='Add any additional details about the cancellation...'></textarea>
                                                </div>
                                                
                                                <div class='form-check mb-3'>
                                                    <input class='form-check-input' type='checkbox' id='sendEmailNotification{$order_id}' name='send_email' checked>
                                                    <label class='form-check-label' for='sendEmailNotification{$order_id}'>
                                                        Send email notification to customer
                                                    </label>
                                                </div>
                                                
                                                <div class='d-flex justify-content-end'>
                                                    <button type='button' class='btn btn-secondary me-2' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' onclick='cancelOrder({$order_id})' class='btn btn-danger'>Cancel Order</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>";
                        }
                    }
				
            
            ?>
            </div>
			</div>
		</div>


		
		</main>
	</section>
	<script src="script.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
		const myModal = document.getElementById('myModal');
		const myInput = document.getElementById('myInput');

		if (myModal && myInput) {
			myModal.addEventListener('shown.bs.modal', () => {
				myInput.focus();
			});
		}
	</script>
	<script>
		// Define all functions first, before any calls to them
		function sendCancellationEmail(emailData) {
			console.log("Sending cancellation email:", emailData);
			console.log("Starting sendCancellationEmail function");
			console.log("Email data received:", emailData);
			
			// Add this debug alert to see if function is being called
			alert("Debug: sendCancellationEmail function called!");
			
			// Show sending notification
			showEmailNotification('sending', 'Sending Email...', `Sending cancellation notice to ${emailData.customer_email}`);
			
			// Create template parameters for cancellation email
			const templateParams = {
				to_email: emailData.customer_email,
				recipient_email: emailData.customer_email,
				to_name: emailData.customer_name,
				from_name: "Meat Shops",
				message: emailData.cancellation_message,
				subject: "Order Cancellation Notice - Meat Shops",
				reply_to: "meatshop.platform@gmail.com"
			};
			
			console.log("Cancellation email template parameters:", templateParams);

			// Send email using EmailJS
			emailjs.send('service_tn9ghw1', 'template_hzahf9h', templateParams)
				.then(function(response) {
					console.log("Cancellation email sent successfully:", response);
					
					// Show success notification
					showEmailNotification('success', 'Email Sent Successfully!', 
						`Cancellation notice sent to ${emailData.customer_email}. Message: "${emailData.cancellation_message}"`);
					
					// Display toast for successful email sending
					showAdminToast('Order Cancelled', `Order cancelled successfully! Cancellation email has been sent to ${emailData.customer_email}.`, 'success');
					
					// Redirect after showing notification
					setTimeout(() => {
						window.location.href = 'orders-admin.php';
					}, 3000);
				})
				.catch(function(error) {
					console.error('Failed to send cancellation email:', error);
					
					// Show error notification
					showEmailNotification('error', 'Email Failed', 
						`Failed to send cancellation notice to ${emailData.customer_email}. Order was still cancelled.`);
					
					// Display toast for email failure
					showAdminToast('Order Cancelled', `Order was cancelled but failed to send email notification to ${emailData.customer_email}. Please contact the customer manually.`, 'warning', 5000);
					
					// Still redirect even if email fails
					setTimeout(() => {
						window.location.href = 'orders-admin.php';
					}, 4000);
				});
		}
		
		// Email notification system
		function showEmailNotification(type, title, message) {
			// Remove existing notifications
			const existingNotifications = document.querySelectorAll('.email-notification');
			existingNotifications.forEach(notification => {
				notification.remove();
			});
			
			// Create notification element
			const notification = document.createElement('div');
			notification.className = `email-notification ${type}`;
			
			// Determine icon based on type
			let icon = '';
			switch(type) {
				case 'success':
					icon = '✉️';
					break;
				case 'error':
					icon = '❌';
					break;
				case 'sending':
					icon = '📤';
					break;
			}
			
			notification.innerHTML = `
				<div class="notification-icon">${icon}</div>
				<div class="notification-content">
					<div class="notification-title">${title}</div>
					<div class="notification-message">${message}</div>
				</div>
				<button class="notification-close" onclick="closeEmailNotification(this)">×</button>
				${type === 'sending' ? '<div class="notification-progress"></div>' : ''}
			`;
			
			// Add to page
			document.body.appendChild(notification);
			
			// Show notification with animation
			setTimeout(() => {
				notification.classList.add('show');
			}, 100);
			
			// Auto-hide after delay (except for sending notifications)
			if (type !== 'sending') {
				setTimeout(() => {
					closeEmailNotification(notification.querySelector('.notification-close'));
				}, type === 'error' ? 6000 : 4000);
			}
		}
		
		function closeEmailNotification(closeButton) {
			const notification = closeButton.closest('.email-notification');
			notification.classList.remove('show');
			setTimeout(() => {
				if (notification.parentNode) {
					notification.remove();
				}
			}, 300);
		}
		
		function confirmLogout() {
			if (confirm("Are you sure you want to log out?")) {
				window.location.href = "../logout.php";
			}
		}

		// New cancelOrder function similar to sendEmail in messages-admin.php
		function cancelOrder(orderId) {
			// Get form values
			const orderEmail = document.getElementById('cancel_email' + orderId).value;
			const customerName = document.getElementById('cancel_customer_name' + orderId).value;
			const productName = document.getElementById('cancel_product_name' + orderId).value;
			const cancelReason = document.getElementById('cancelReason' + orderId).value;
			const cancelMessage = document.getElementById('cancelMessage' + orderId).value;
			const sendEmail = document.getElementById('sendEmailNotification' + orderId).checked;
			
			// Validate required fields
			if (!cancelReason.trim()) {
				showAdminToast('Missing Information', 'Please select a cancellation reason.', 'warning');
				return;
			}
			
			// Get the cancel button and show loading state
			const cancelButton = document.getElementById('cancelModal' + orderId).querySelector('button.btn-danger');
			const originalText = cancelButton.innerHTML;
			cancelButton.innerHTML = 'Cancelling...';
			cancelButton.disabled = true;
			
			// First, cancel the order in the database
			const cancelData = new FormData();
			cancelData.append('cancel_order_id', orderId); // Fixed: changed from 'order_id' to 'cancel_order_id'
			cancelData.append('action', 'cancel_order');
			
			fetch('cancel_order_with_email.php', {
				method: 'POST',
				body: cancelData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					console.log('Order cancelled successfully in database');
					
					// If email notification is enabled, send the email
					if (sendEmail) {
						// Create comprehensive cancellation message
						let fullMessage = `Dear ${customerName},\n\n`;
						fullMessage += `We regret to inform you that your order has been cancelled.\n\n`;
						fullMessage += `Order Details:\n`;
						fullMessage += `- Product: ${productName}\n`;
						fullMessage += `- Reason: ${cancelReason}\n\n`;
						
						if (cancelMessage.trim()) {
							fullMessage += `Additional Information:\n${cancelMessage}\n\n`;
						}
						
						fullMessage += `We apologize for any inconvenience this may cause. If you have any questions or concerns, please don't hesitate to contact us.\n\n`;
						fullMessage += `Best regards,\nMeat Shops Platform`;
						
						// Show sending notification
						showEmailNotification('sending', 'Sending Email...', `Sending cancellation notice to ${orderEmail}`);
						
						// Create template parameters for cancellation email
						const templateParams = {
							to_email: orderEmail,
							recipient_email: orderEmail,
							to_name: customerName,
							from_name: "Meat Shops Platform",
							message: fullMessage,
							subject: `Order Cancellation Notice - Order #${orderId}`,
							reply_to: "meatshop.platform@gmail.com"
						};
						
						console.log("Sending cancellation email with params:", templateParams);
						
						// Send email using EmailJS
						emailjs.send('service_tn9ghw1', 'template_hzahf9h', templateParams)
							.then(function(response) {
								console.log("Cancellation email sent successfully:", response);
								
								// Show success notification
								showEmailNotification('success', 'Order Cancelled & Email Sent!', 
									`Order #${orderId} has been cancelled and notification sent to ${orderEmail}`);
								
								// Display toast for successful cancellation and email
								showAdminToast('Success', `Order #${orderId} cancelled successfully! Cancellation email has been sent to ${orderEmail}.`, 'success', 3000);
								
								// Reset button and close modal
								cancelButton.innerHTML = originalText;
								cancelButton.disabled = false;
								
								// Close the modal
								const modalElement = document.getElementById('cancelModal' + orderId);
								const modal = bootstrap.Modal.getInstance(modalElement);
								if (modal) {
									modal.hide();
								}
								
								// Redirect after short delay
								setTimeout(() => {
									window.location.href = 'orders-admin.php';
								}, 2000);
							})
							.catch(function(error) {
								console.error('Failed to send cancellation email:', error);
								
								// Show warning notification (order was cancelled but email failed)
								showEmailNotification('error', 'Order Cancelled (Email Failed)', 
									`Order #${orderId} was cancelled but email notification failed. Please contact customer manually.`);
								
								// Display toast for email failure
								showAdminToast('Warning', `Order #${orderId} was cancelled successfully, but failed to send email notification to ${orderEmail}. Please contact the customer manually.`, 'warning', 5000);
								
								// Reset button and close modal
								cancelButton.innerHTML = originalText;
								cancelButton.disabled = false;
								
								// Close the modal
								const modalElement = document.getElementById('cancelModal' + orderId);
								const modal = bootstrap.Modal.getInstance(modalElement);
								if (modal) {
									modal.hide();
								}
								
								// Still redirect
								setTimeout(() => {
									window.location.href = 'orders-admin.php';
								}, 3000);
							});
					} else {
						// No email notification requested
						showAdminToast('Order Cancelled', `Order #${orderId} cancelled successfully! No email notification was sent.`, 'success', 2500);
						
						// Reset button and close modal
						cancelButton.innerHTML = originalText;
						cancelButton.disabled = false;
						
						// Close the modal
						const modalElement = document.getElementById('cancelModal' + orderId);
						const modal = bootstrap.Modal.getInstance(modalElement);
						if (modal) {
							modal.hide();
						}
						
						// Redirect
						setTimeout(() => {
						 window.location.href = 'orders-admin.php';
						}, 1000);
					}
				} else {
					// Database cancellation failed
					showAdminToast('Error', 'Failed to cancel order: ' + (data.message || 'Unknown error occurred'), 'error');
					
					// Reset button
					cancelButton.innerHTML = originalText;
					cancelButton.disabled = false;
				}
			})
			.catch(error => {
				console.error('Error cancelling order:', error);
				showAdminToast('Error', 'Failed to cancel order. Please try again.', 'error');
				
				// Reset button
				cancelButton.innerHTML = originalText;
				cancelButton.disabled = false;
			});
		}
		// Wait for document to be ready before calling any functions
		document.addEventListener('DOMContentLoaded', function() {
			// Check if we need to send cancellation email
			<?php
			if(isset($_GET['order_id']) && isset($_GET['action']) && $_GET['action'] === 'cancelled') {
				if(isset($result) && $result['success']) {
					$email_data = json_encode($result['email_data']);
					echo "
					console.log('About to call sendCancellationEmail');
					console.log('Email data from PHP:', " . $email_data . ");
					const emailData = " . $email_data . ";
					sendCancellationEmail(emailData);
					";
				}
			}
			?>
		});
	</script>
<?php include 'toast-notification.php'; ?>
</body>
</html>
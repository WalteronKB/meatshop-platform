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
	$current_admin_shop_id = null;
	$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
	$activity_actor_name = trim((string)($_SESSION['user_name'] ?? ''));
	if ($current_user_id > 0) {
		$shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
		$shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
		if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
			$shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
			$current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
		}

		if ($activity_actor_name === '') {
			$actor_lookup_query = "SELECT user_name, user_mname, user_lname, first_name, last_name, user_email FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
			$actor_lookup_result = mysqli_query($conn, $actor_lookup_query);
			if ($actor_lookup_result && mysqli_num_rows($actor_lookup_result) > 0) {
				$actor_lookup_row = mysqli_fetch_assoc($actor_lookup_result);
				$name_parts = array_filter([
					trim((string)($actor_lookup_row['user_name'] ?? '')),
					trim((string)($actor_lookup_row['user_mname'] ?? '')),
					trim((string)($actor_lookup_row['user_lname'] ?? '')),
				]);
				$activity_actor_name = trim(implode(' ', $name_parts));
				if ($activity_actor_name === '') {
					$fallback_parts = array_filter([
						trim((string)($actor_lookup_row['first_name'] ?? '')),
						trim((string)($actor_lookup_row['last_name'] ?? '')),
					]);
					$activity_actor_name = trim(implode(' ', $fallback_parts));
				}
				if ($activity_actor_name === '') {
					$activity_actor_name = trim((string)($actor_lookup_row['user_email'] ?? ''));
				}
			}
		}
	}
	if ($activity_actor_name === '') {
		$activity_actor_name = 'Admin';
	}

	$product_scope_where = "";
	$product_scope_where_alias_f = "";
	$supplier_scope_where = "";
	$order_scope_condition = "";
	$message_scope_condition = "";
	if (!$is_super_admin) {
		if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
			$product_scope_where = " AND shop_id = {$current_admin_shop_id}";
			$product_scope_where_alias_f = " AND f.shop_id = {$current_admin_shop_id}";
			$supplier_scope_where = " AND shop_id = {$current_admin_shop_id}";
			$order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
			$message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
		} else {
			$product_scope_where = " AND 1=0";
			$product_scope_where_alias_f = " AND 1=0";
			$supplier_scope_where = " AND 1=0";
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
</head>
<body>
	
	
<section id="sidebar" class="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-restaurant'></i>
			<span class="text">Meat Shop</span>
		</a>
		<ul class="side-menu top" style="padding: 0px;">
			<?php if ($is_butcher): ?>
			<li class="active">
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
			<?php else: ?>
			<li>
				<a href="analytics-admin.php">
				<i class='bx bxs-bar-chart-alt-2'></i>
				<span class="text">Analytics</span>
				</a>
			</li>
			<li class="active">
				<a href="products-admin.php">
				<i class='bx bxs-cart'></i>
				<span class="text">Products</span>
				</a>
			</li>
			<li class="">
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
			<h1>Products</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Products</a></li>
			</ul>
			</div>
		</div>

		<?php if(!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)): ?>
		<div class="alert alert-warning">No shop assigned to this admin yet.</div>
		<?php endif; ?>

		<?php
			$category_values = [];

			$product_category_query = "SELECT DISTINCT prod_type FROM mrb_fireex WHERE prod_type != 'deleted' AND prod_type IS NOT NULL AND TRIM(prod_type) != ''{$product_scope_where} ORDER BY prod_type ASC";
			$product_category_result = mysqli_query($conn, $product_category_query);
			if ($product_category_result) {
				while ($category_row = mysqli_fetch_assoc($product_category_result)) {
					$category_name = trim((string)($category_row['prod_type'] ?? ''));
					if ($category_name !== '' && !in_array($category_name, $category_values, true)) {
						$category_values[] = $category_name;
					}
				}
			}

			$supplier_category_query = "SELECT DISTINCT product_category FROM mrb_suppliers WHERE product_category IS NOT NULL AND TRIM(product_category) != ''{$supplier_scope_where} ORDER BY product_category ASC";
			$supplier_category_result = mysqli_query($conn, $supplier_category_query);
			if ($supplier_category_result) {
				while ($supplier_row = mysqli_fetch_assoc($supplier_category_result)) {
					$category_name = trim((string)($supplier_row['product_category'] ?? ''));
					if ($category_name !== '' && !in_array($category_name, $category_values, true)) {
						$category_values[] = $category_name;
					}
				}
			}

			$core_meat_categories = ['Pork Products', 'Chicken Products', 'Fish Products', 'Beef Products'];
			foreach ($core_meat_categories as $core_category) {
				if (!in_array($core_category, $category_values, true)) {
					$category_values[] = $core_category;
				}
			}

			if (empty($category_values)) {
				$category_values = ['Pork Products', 'Chicken Products', 'Fish Products', 'Beef Products'];
			}

			sort($category_values);
			$category_count = count($category_values);

			$category_options_add = "<option value='' selected disabled>Select product type</option>";
			foreach ($category_values as $category_name) {
				$safe_category_name = htmlspecialchars($category_name, ENT_QUOTES);
				$category_options_add .= "<option value='" . $safe_category_name . "'>" . $safe_category_name . "</option>";
			}
		?>

		<!-- Summary Cards -->
		<ul class="box-info" style="padding: 0px;">
			<li style="background-color:#e6f7ff;">
			<i class='bx bx-book-content'></i>
			<span class="text">
				<h3><?php 
					$query = "SELECT COUNT(*) as total FROM mrb_fireex WHERE NOT prod_type = 'deleted'{$product_scope_where}";
					$result = mysqli_query($conn, $query);
					if ($result) {
						$row = mysqli_fetch_assoc($result);
						echo $row['total'];
					} else {
						echo "Error: " . mysqli_error($conn);
					}
				?></h3>
				<p>Total Products</p>
			</span>
			</li>
			<li style="background-color:#fffbe6;">
			<i class='bx bx-category'></i>
			<span class="text">
				<h3><?php echo (int)$category_count; ?></h3>
				<p>Categories</p>
			</span>
			</li>
		</ul>

		<!-- Low Stock Alerts -->
		<?php
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
				{$product_scope_where_alias_f}
				AND s.status = 'Active'
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
						echo '<a href="suppliers-admin.php#supplier-' . $item['supplier_id'] . '" class="btn btn-sm btn-primary" onclick="localStorage.setItem(\'highlightSupplier\', ' . $item['supplier_id'] . ')">
							<i class="bx bx-truck"></i> Contact ' . htmlspecialchars($item['company_name']) . '
						</a>';
					} else {
						echo '<a href="suppliers-admin.php" class="btn btn-sm btn-secondary">
							<i class="bx bx-plus"></i> Add Supplier
						</a>';
					}
					
					echo '<button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					</div>';
				}
				
				echo '</div>
					</div>
				</div>';
			}
		?>

		<!-- Quick Actions -->
		<div class="quick-actions my-2" style="margin:20px 0;">
            <button class="bg-blue-600 text-dark px-4 py-2 rounded-lg hover:bg-blue-700 mr-3"
			 data-bs-toggle="modal" data-bs-target="#addModal">
                + Add New Product
            </button>
			<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
					<div class="modal-header">
						<h1 class="modal-title fs-5" id="exampleModalLabel">New Product</h1>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						<?php
						
							echo"
									<form method='post' action='products-admin.php' enctype='multipart/form-data'>
										<input type='hidden' name='prod_id'>
										<div class='mb-3'>
											<label class='form-label'>Product Images (Up to 5):</label>
											<div class='mb-2'>
												<label for='mainImage' class='form-label'>Main Product Image:</label>
												<input type='file' required class='form-control' name='main_image' id='mainImage'>
												<div class='form-text'>Main product image</div>
											</div>
											
											<div class='row'>
												<div class='col-md-6 mb-2'>
													<label for='additionalImage1' class='form-label'>Image 2:</label>
													<input type='file'  class='form-control' name='additional_images[]' id='additionalImage1'>
												</div>
												<div class='col-md-6 mb-2'>
													<label for='additionalImage2' class='form-label'>Image 3:</label>
													<input type='file' class='form-control' name='additional_images[]' id='additionalImage2'>
												</div>
												<div class='col-md-6 mb-2'>
													<label for='additionalImage3' class='form-label'>Image 4:</label>
													<input type='file' class='form-control' name='additional_images[]' id='additionalImage3'>
												</div>
												<div class='col-md-6 mb-2'>
													<label for='additionalImage4' class='form-label'>Image 5:</label>
													<input type='file' class='form-control' name='additional_images[]' id='additionalImage4'>
												</div>
											</div>

											<div class='form-text'>Leave empty fields to skip additional images</div>
										</div>
										<div class='mb-3'>
											<label for='new_prod_name' class='form-label'>Product Name:</label>
											<input type='text' required class='form-control' id='new_prod_name' name='new_prod_name' placeholder='Enter product name:'>
										</div>
										<div class='mb-3'>
											<label for='new_prod_desc' class='form-label'>Products Description:</label>
											<textarea class='form-control' required name='new_prod_desc' id='new_prod_desc' rows='3'></textarea>
										</div>
										<div class='mb-3'>
											<label for='new_prod_type' class='form-label'>Product Type:</label>
											<select class='form-select' id='new_prod_type' name='new_prod_type' required>
												" . $category_options_add . "
											</select>
										</div>
										<div class='mb-3 input-group'>
											<span class='input-group-text'>₱</span>
											<input type='number' class='form-control' id='new_prod_oldprice' name='new_prod_oldprice' placeholder='Enter old price:'>
										</div>
										<div class='mb-3 input-group'>
											<span class='input-group-text'>₱</span>
											<input type='number' class='form-control' required id='new_prod_newprice' name='new_prod_newprice' placeholder='Enter new price:'>
										</div>
										<div class='mb-3'>
											<label for='new_prod_quantity' class='form-label'>Product Quantity:</label>
											<input type='number' class='form-control' required  id='new_prod_quantity' name='new_prod_quantity'>
										</div>
										<div class='d-flex justify-content-end'>
											<button type='button' class='btn btn-secondary me-2 text-dark' data-bs-dismiss='modal'>Cancel</button>
											<button type='submit' name='add_prod' class='btn btn-primary text-dark'>Add Product</button>
										</div>
									</form>";

									if(isset($_POST['add_prod'])){
										if (!$is_super_admin && ($current_admin_shop_id === null || $current_admin_shop_id <= 0)) {
											echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'No shop assigned. Cannot add products yet.', 'error'); });</script>";
										} else {
										$query = "SELECT MAX(prod_id) as max_id FROM mrb_fireex";
										$result = mysqli_query($conn, $query);
										if ($result) {
											$row = mysqli_fetch_assoc($result);
											$max_id = $row['max_id'] + 1;
										} else {
											$max_id = 101;
										}
										$prod_id = $max_id;
										$prod_name = mysqli_real_escape_string($conn, $_POST['new_prod_name']);
										$prod_desc = mysqli_real_escape_string($conn, $_POST['new_prod_desc']);
										$prod_oldprice = mysqli_real_escape_string($conn, $_POST['new_prod_oldprice']);
										$prod_newprice = mysqli_real_escape_string($conn, $_POST['new_prod_newprice']);
										$prod_type = mysqli_real_escape_string($conn, $_POST['new_prod_type']);
										$prod_quantity = mysqli_real_escape_string($conn, $_POST['new_prod_quantity']);
										$prod_dateadded = date('Y-m-d H:i:s'); 

										// Process main image
										$main_image_path = NULL;
										if(!empty($_FILES['main_image']['name'])) {
											$main_file_name = time() . '_' . $_FILES['main_image']['name']; // Add timestamp to avoid name conflicts
											$main_file_temp = $_FILES['main_image']['tmp_name'];
											$main_image_path = 'Images/product_img/' . $main_file_name; // Database path - for root level access
											$main_upload_path = '../Images/product_img/' . $main_file_name; // Physical path - going up to root
											
											// Ensure directory exists
											$dir = dirname($main_upload_path);
											if (!is_dir($dir)) {
												mkdir($dir, 0755, true);
											}
											
											// Move main image file
											move_uploaded_file($main_file_temp, $main_upload_path);
										}
										
										// Process additional images
										$additional_image_paths = array(NULL, NULL, NULL, NULL); // Initialize with NULL
										
										if(!empty($_FILES['additional_images']['name'])) {
											$file_count = count($_FILES['additional_images']['name']);
											
											for($i = 0; $i < $file_count && $i < 4; $i++) {
												if(!empty($_FILES['additional_images']['name'][$i])) {
													$add_file_name = time() . '_add_' . $i . '_' . $_FILES['additional_images']['name'][$i];
													$add_file_temp = $_FILES['additional_images']['tmp_name'][$i];
													$additional_image_paths[$i] = 'Images/product_img/' . $add_file_name; // Database path - for root level access
													$add_upload_path = '../Images/product_img/' . $add_file_name; // Physical path - going up to root
													
													// Move additional image file
													move_uploaded_file($add_file_temp, $add_upload_path);
												}
											}
										}
										
										// Insert product with shop ownership
										$new_product_shop_id_sql = ($current_admin_shop_id !== null && $current_admin_shop_id > 0) ? (string)$current_admin_shop_id : "NULL";
										$query = "INSERT INTO mrb_fireex (prod_id, prod_name, prod_desc, prod_oldprice, prod_newprice, 
												prod_quantity, prod_type, prod_dateadded, prod_mainpic, prod_pic1, prod_pic2, prod_pic3, prod_pic4, shop_id) 
												VALUES ('$prod_id', '$prod_name', '$prod_desc', '$prod_oldprice', '$prod_newprice', 
												'$prod_quantity', '$prod_type', '$prod_dateadded', '$main_image_path', '$additional_image_paths[0]', '$additional_image_paths[1]', 
												'$additional_image_paths[2]', '$additional_image_paths[3]', $new_product_shop_id_sql)";
										$result = mysqli_query($conn, $query);
										if($result) {
											// Log activity
											$activity_desc = "Product '<strong>{$prod_name}</strong>' (ID: {$prod_id}) was added to products by {$activity_actor_name}";
											$activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
											$log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'products', NOW())";
											mysqli_query($conn, $log_query);
											
											echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Product added successfully with images.', 'success'); setTimeout(() => window.location.href = 'products-admin.php', 1500); });</script>";
										} else {
											echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Error adding product: " . addslashes(mysqli_error($conn)) . "', 'error'); });</script>";
										}
										}
									}
						
						?>
					</div>
					</div>
				</div>
				</div>
            
        </div>


		<!-- Editable Article Table -->
		<div class="table-data">
			<div class="order">
			<div class="head">
				<h3>Edit Products</h3>
			</div>
			<div class="row g-4">
				<?php
					
					$query = "SELECT * FROM mrb_fireex WHERE NOT prod_type = 'deleted'{$product_scope_where} ORDER BY prod_type, prod_id ";
					$result = mysqli_query($conn, $query);
					if (!$result) {
						die("Query failed: " . mysqli_error($conn));
					}

					if (mysqli_num_rows($result) === 0) {
						echo "<div class='col-12'><div class='alert alert-info text-center w-100'>No products</div></div>";
					}
					
					while ($row = mysqli_fetch_assoc($result)) {
// Calculate discount percentage
$discount = 0;
if ($row['prod_oldprice'] > 0) {
	$discount = round((($row['prod_oldprice'] - $row['prod_newprice']) / $row['prod_oldprice']) * 100);
}

// Determine stock status
$stockStatus = '';
$stockClass = '';
if ($row['prod_quantity'] == 0) {
	$stockStatus = 'Out of Stock';
	$stockClass = 'bg-danger';
} elseif ($row['prod_quantity'] <= 5) {
	$stockStatus = 'Low Stock';
	$stockClass = 'bg-warning';
} else {
	$stockStatus = 'In Stock';
	$stockClass = 'bg-success';
}

$category_options_edit = "";
$modal_categories = $category_values;
if (!in_array($row['prod_type'], $modal_categories, true)) {
	$modal_categories[] = $row['prod_type'];
}
sort($modal_categories);
foreach ($modal_categories as $category_name) {
	$safe_category_name = htmlspecialchars($category_name, ENT_QUOTES);
	$selected_attr = ($row['prod_type'] === $category_name) ? 'selected' : '';
	$category_options_edit .= "<option value='" . $safe_category_name . "' " . $selected_attr . ">" . $safe_category_name . "</option>";
}

echo "<div class='col-12 col-md-6 col-lg-4'>
		<div class='card h-100 shadow-sm'>
			<div class='position-relative'>
				<img src='../{$row['prod_mainpic']}' class='card-img-top' alt='Product Image' style='height: 200px; object-fit: cover;'>
				" . ($discount > 0 ? "<span class='badge bg-danger position-absolute top-0 end-0 m-2'>-{$discount}%</span>" : "") . "
			</div>
			<div class='card-body d-flex flex-column'>
				<div class='mb-2'>
					<span class='badge bg-primary mb-2'>{$row['prod_type']}</span>
					<small class='text-muted d-block'>Product #{$row['prod_id']}</small>
				</div>
				<h5 class='card-title'>{$row['prod_name']}</h5>
				<p class='card-text text-muted small' style='height: 60px; overflow: hidden;'>{$row['prod_desc']}</p>
				
				<div class='mb-3'>
					<div class='d-flex align-items-center mb-2'>
						<span class='h5 text-success mb-0 me-2'>₱" . number_format($row['prod_newprice'], 2) . "</span>
						" . ($row['prod_oldprice'] > $row['prod_newprice'] ? "<span class='text-muted text-decoration-line-through'>₱" . number_format($row['prod_oldprice'], 2) . "</span>" : "") . "
					</div>
					<div class='d-flex justify-content-between align-items-center'>
						<span class='badge {$stockClass} text-white'>{$stockStatus}</span>
						<small class='text-muted'>Qty: {$row['prod_quantity']}</small>
					</div>
				</div>
				
				<div class='mt-auto'>
					<div class='d-grid gap-2'>
						<button type='button' class='btn btn-primary text-dark' data-bs-toggle='modal' data-bs-target='#editModal{$row['prod_id']}'>
							<i class='bx bx-edit'></i> Edit Product
						</button>
						<button type='button' class='btn btn-outline-danger' data-bs-toggle='modal' data-bs-target='#delModal{$row['prod_id']}'>
							<i class='bx bx-trash'></i> Delete
						</button>
					</div>
				</div>
			</div>
			<div class='card-footer bg-light'>
				<small class='text-muted'>
					<i class='bx bx-calendar'></i> Added: " . date('M d, Y', strtotime($row['prod_dateadded'])) . "
				</small>
			</div>
		</div>
	  </div>".
	  
	  "<div class='modal fade' id='editModal{$row['prod_id']}' tabindex='-1' aria-labelledby='editModalLabel{$row['prod_id']}' aria-hidden='true'>
		<div class='modal-dialog modal-xl'>
			<div class='modal-content'>
			<div class='modal-header'>
				<h1 class='modal-title fs-5' id='editModalLabel{$row['prod_id']}'>Edit Product: {$row['prod_name']}</h1>
				<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
			</div>
			<div class='modal-body'>
				<div class='row'>
				<!-- LEFT SIDE - IMAGES -->
				<div class='col-md-6'>
					<h5 class='mb-3'><i class='bx bx-image'></i> Current Images</h5>
					<div class='card mb-3'>
						<div class='card-header bg-primary text-white'>
							<i class='bx bx-image-alt'></i> Main Product Image
						</div>
						<div class='card-body text-center'>
							<img src='../{$row['prod_mainpic']}' class='img-fluid rounded shadow' style='max-height: 200px; object-fit: contain;'>
						</div>
					</div>
					
					<div class='row'>
					<!-- ADDITIONAL IMAGES -->
					" . (!empty($row['prod_pic1']) ? "
					<div class='col-md-6 mb-3'>
						<div class='card h-100'>
							<div class='card-header bg-light'>
								<small><i class='bx bx-image'></i> Image 2</small>
							</div>
							<div class='card-body text-center p-2'>
								<img src='../{$row['prod_pic1']}' class='img-fluid rounded' style='height: 80px; object-fit: contain;'>
							</div>
						</div>
					</div>" : "") . "
					
					" . (!empty($row['prod_pic2']) ? "
					<div class='col-md-6 mb-3'>
						<div class='card h-100'>
							<div class='card-header bg-light'>
								<small><i class='bx bx-image'></i> Image 3</small>
							</div>
							<div class='card-body text-center p-2'>
								<img src='../{$row['prod_pic2']}' class='img-fluid rounded' style='height: 80px; object-fit: contain;'>
							</div>
						</div>
					</div>" : "") . "
					
					" . (!empty($row['prod_pic3']) ? "
					<div class='col-md-6 mb-3'>
						<div class='card h-100'>
							<div class='card-header bg-light'>
								<small><i class='bx bx-image'></i> Image 4</small>
							</div>
							<div class='card-body text-center p-2'>
								<img src='../{$row['prod_pic3']}' class='img-fluid rounded' style='height: 80px; object-fit: contain;'>
							</div>
						</div>
					</div>" : "") . "
					
					" . (!empty($row['prod_pic4']) ? "
					<div class='col-md-6 mb-3'>
						<div class='card h-100'>
							<div class='card-header bg-light'>
								<small><i class='bx bx-image'></i> Image 5</small>
							</div>
							<div class='card-body text-center p-2'>
								<img src='../{$row['prod_pic4']}' class='img-fluid rounded' style='height: 80px; object-fit: contain;'>
							</div>
						</div>
					</div>" : "") . "
					</div>
					
					<!-- CURRENT PRODUCT INFO -->
					<div class='card bg-light mt-3'>
						<div class='card-header'>
							<h6 class='mb-0'><i class='bx bx-info-circle'></i> Current Product Info</h6>
						</div>
						<div class='card-body'>
							<div class='row'>
								<div class='col-6'>
									<small class='text-muted'>Type:</small>
									<p class='mb-2'><span class='badge bg-primary'>{$row['prod_type']}</span></p>
								</div>
								<div class='col-6'>
									<small class='text-muted'>Stock:</small>
									<p class='mb-2'><span class='badge {$stockClass}'>{$stockStatus}</span></p>
								</div>
							</div>
							<div class='row'>
								<div class='col-6'>
									<small class='text-muted'>Old Price:</small>
									<p class='mb-2'>₱" . number_format($row['prod_oldprice'], 2) . "</p>
								</div>
								<div class='col-6'>
									<small class='text-muted'>Current Price:</small>
									<p class='mb-2 text-success fw-bold'>₱" . number_format($row['prod_newprice'], 2) . "</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- RIGHT SIDE - FORM -->
				<div class='col-md-6'>
					<h5 class='mb-3'><i class='bx bx-edit'></i> Update Product</h5>
					<form method='post' action='products-admin.php' enctype='multipart/form-data'>
					<input type='hidden' value='{$row['prod_id']}' name='prod_id'>
					
					<!-- Image Update Fields -->
					<div class='card mb-3'>
						<div class='card-header bg-light'>
							<h6 class='mb-0'><i class='bx bx-image'></i> Update Images</h6>
						</div>
						<div class='card-body'>
							<div class='mb-3'>
								<label for='mainImage_{$row['prod_id']}' class='form-label'>Main Image:</label>
								<input type='file' class='form-control' name='main_image' id='mainImage_{$row['prod_id']}' accept='image/*'>
							</div>
							
							<div class='row'>
								<div class='col-md-6 mb-2'>
									<label for='additionalImage1_{$row['prod_id']}' class='form-label'>Image 2:</label>
									<input type='file' class='form-control' name='additional_images[]' id='additionalImage1_{$row['prod_id']}' accept='image/*'>
								</div>
								<div class='col-md-6 mb-2'>
									<label for='additionalImage2_{$row['prod_id']}' class='form-label'>Image 3:</label>
									<input type='file' class='form-control' name='additional_images[]' id='additionalImage2_{$row['prod_id']}' accept='image/*'>
								</div>
								<div class='col-md-6 mb-2'>
									<label for='additionalImage3_{$row['prod_id']}' class='form-label'>Image 4:</label>
									<input type='file' class='form-control' name='additional_images[]' id='additionalImage3_{$row['prod_id']}' accept='image/*'>
								</div>
								<div class='col-md-6 mb-2'>
									<label for='additionalImage4_{$row['prod_id']}' class='form-label'>Image 5:</label>
									<input type='file' class='form-control' name='additional_images[]' id='additionalImage4_{$row['prod_id']}' accept='image/*'>
								</div>
							</div>
							
							<div class='form-text'>Leave empty to keep current images</div>
						</div>
					</div>
					
					<!-- Product Details Fields -->
					<div class='mb-3'>
						<label for='prod_name_{$row['prod_id']}' class='form-label'>
							<i class='bx bx-tag'></i> Product Name:
						</label>
						<input type='text' class='form-control' id='prod_name_{$row['prod_id']}' value='{$row['prod_name']}' name='prod_name'>
					</div>
					
					<div class='mb-3'>
						<label for='prod_desc_{$row['prod_id']}' class='form-label'>
							<i class='bx bx-text'></i> Description:
						</label>
						<textarea class='form-control' name='prod_desc' id='prod_desc_{$row['prod_id']}' rows='3'>{$row['prod_desc']}</textarea>
					</div>
					
					<div class='mb-3'>
						<label for='prod_type_{$row['prod_id']}' class='form-label'>
							<i class='bx bx-category'></i> Product Type:
						</label>
						<select class='form-select' id='prod_type_{$row['prod_id']}' name='prod_type'>
							" . $category_options_edit . "
						</select>
					</div>

					<div class='row'>
						<div class='col-md-6 mb-3'>
							<label for='prod_oldprice_{$row['prod_id']}' class='form-label'>
								<i class='bx bx-money'></i> Old Price:
							</label>
							<div class='input-group'>
								<span class='input-group-text'>₱</span>
								<input type='number' step='0.01' class='form-control' id='prod_oldprice_{$row['prod_id']}' value='{$row['prod_oldprice']}' name='prod_oldprice'>
							</div>
						</div>
						
						<div class='col-md-6 mb-3'>
							<label for='prod_newprice_{$row['prod_id']}' class='form-label'>
								<i class='bx bx-money'></i> Current Price:
							</label>
							<div class='input-group'>
								<span class='input-group-text'>₱</span>
								<input type='number' step='0.01' class='form-control' id='prod_newprice_{$row['prod_id']}' value='{$row['prod_newprice']}' name='prod_newprice'>
							</div>
						</div>
					</div>
					
					<div class='mb-3'>
						<label for='prod_quantity_{$row['prod_id']}' class='form-label'>
							<i class='bx bx-package'></i> Stock Quantity:
						</label>
						<input type='number' class='form-control' id='prod_quantity_{$row['prod_id']}' value='{$row['prod_quantity']}' name='prod_quantity' min='0'>
					</div>
					
					<div class='d-flex justify-content-end gap-2 mt-4'>
						<button type='button' class='btn btn-outline-secondary' data-bs-dismiss='modal'>
							<i class='bx bx-x'></i> Cancel
						</button>
						<button type='submit' name='submit' class='btn btn-primary text-dark'>
							<i class='bx bx-save'></i> Update Product
						</button>
					</div>
					</form>
				</div>
				</div>
			</div>
			</div>
		</div>
		</div>";

	echo "<div class='modal fade' id='delModal{$row['prod_id']}' tabindex='-1' aria-labelledby='delModalLabel{$row['prod_id']}' aria-hidden='true'>
		<div class='modal-dialog'>
			<div class='modal-content'>
				<div class='modal-header bg-danger text-white'>
					<h1 class='modal-title fs-5' id='delModalLabel{$row['prod_id']}'>
						<i class='bx bx-trash'></i> Delete Product
					</h1>
					<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
				</div>
				<div class='modal-body text-center'>
					<div class='mb-3'>
						<img src='../{$row['prod_mainpic']}' class='img-fluid rounded' style='max-height: 150px; object-fit: contain;'>
					</div>
					<h5>{$row['prod_name']}</h5>
					<p class='text-muted'>Product #{$row['prod_id']}</p>
					<div class='alert alert-danger'>
						<i class='bx bx-warning'></i>
						<strong>Warning!</strong> This action cannot be undone. Are you sure you want to delete this product?
					</div>
					
					<form method='post'>
						<input type='hidden' value='{$row['prod_id']}' name='del_id'>
						<div class='d-flex justify-content-center gap-2'>
							<button type='button' class='btn btn-outline-secondary' data-bs-dismiss='modal'>
								<i class='bx bx-x'></i> Cancel
							</button>
							<button type='submit' name='del-prod' class='btn btn-danger text-dark'>
								<i class='bx bx-trash'></i> Delete Product
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>";

							}

							if(isset($_POST['submit'])){
								$prod_id = $_POST['prod_id'];
								$prod_name = mysqli_real_escape_string($conn, $_POST['prod_name']);
								$prod_desc = mysqli_real_escape_string($conn, $_POST['prod_desc']);
								$prod_type = mysqli_real_escape_string($conn, $_POST['prod_type']);
								$prod_oldprice = mysqli_real_escape_string($conn, $_POST['prod_oldprice']);
								$prod_newprice = mysqli_real_escape_string($conn, $_POST['prod_newprice']);
								$prod_quantity = mysqli_real_escape_string($conn, $_POST['prod_quantity']);
								
								$query = "UPDATE mrb_fireex SET ";
								$updateParts = array();

								if(!empty($prod_name)) {
									$updateParts[] = "prod_name = '$prod_name'";
								}
								if(!empty($prod_desc)) {
									$updateParts[] = "prod_desc = '$prod_desc'";
								}
								if(!empty($prod_oldprice)) {
									$updateParts[] = "prod_oldprice = '$prod_oldprice'";
								}
								if(!empty($prod_newprice)) {
									$updateParts[] = "prod_newprice = '$prod_newprice'";
								}
								if(!empty($prod_quantity)) {
									$updateParts[] = "prod_quantity = '$prod_quantity'";
								}
								if(!empty($prod_type)) {
									$updateParts[] = "prod_type = '$prod_type'";
								}

								// Check if main image was uploaded
								if(!empty($_FILES['main_image']['name'])) {
									$main_file_name = time() . '_' . $_FILES['main_image']['name'];
									$main_file_temp = $_FILES['main_image']['tmp_name'];
									$main_image_path = 'Images/product_img/' . $main_file_name; // Database path - for root level access
									$main_upload_path = '../Images/product_img/' . $main_file_name; // Physical path - going up to root
									
									$dir = dirname($main_upload_path);
									if (!is_dir($dir)) {
										mkdir($dir, 0755, true);
									}
									
									if(move_uploaded_file($main_file_temp, $main_upload_path)) {
										$updateParts[] = "prod_mainpic = '$main_image_path'";
									}
								}
								
								// Check if additional images were uploaded
								if(!empty($_FILES['additional_images']['name'][0])) {
									$add_file_name = time() . '_add_1_' . $_FILES['additional_images']['name'][0];
									$add_file_temp = $_FILES['additional_images']['tmp_name'][0];
									$add_image_path = 'Images/product_img/' . $add_file_name; // Database path - for root level access
									$add_upload_path = '../Images/product_img/' . $add_file_name; // Physical path - going up to root
									
									if(move_uploaded_file($add_file_temp, $add_upload_path)) {
										$updateParts[] = "prod_pic1 = '$add_image_path'";
									}
								}
								
								if(!empty($_FILES['additional_images']['name'][1])) {
									$add_file_name = time() . '_add_2_' . $_FILES['additional_images']['name'][1];
									$add_file_temp = $_FILES['additional_images']['tmp_name'][1];
									$add_image_path = 'Images/product_img/' . $add_file_name; // Database path - for root level access
									$add_upload_path = '../Images/product_img/' . $add_file_name; // Physical path - going up to root
									
									if(move_uploaded_file($add_file_temp, $add_upload_path)) {
										$updateParts[] = "prod_pic2 = '$add_image_path'";
									}
								}
								
								if(!empty($_FILES['additional_images']['name'][2])) {
									$add_file_name = time() . '_add_3_' . $_FILES['additional_images']['name'][2];
									$add_file_temp = $_FILES['additional_images']['tmp_name'][2];
									$add_image_path = 'Images/product_img/' . $add_file_name; // Database path - for root level access
									$add_upload_path = '../Images/product_img/' . $add_file_name; // Physical path - going up to root
									
									if(move_uploaded_file($add_file_temp, $add_upload_path)) {
										$updateParts[] = "prod_pic3 = '$add_image_path'";
									}
								}
								
								if(!empty($_FILES['additional_images']['name'][3])) {
									$add_file_name = time() . '_add_4_' . $_FILES['additional_images']['name'][3];
									$add_file_temp = $_FILES['additional_images']['tmp_name'][3];
									$add_image_path = 'Images/product_img/' . $add_file_name; // Database path - for root level access
									$add_upload_path = '../Images/product_img/' . $add_file_name; // Physical path - going up to root
									
									if(move_uploaded_file($add_file_temp, $add_upload_path)) {
										$updateParts[] = "prod_pic4 = '$add_image_path'";
									}
								}
								
								// Only proceed if there are fields to update
								if(!empty($updateParts)) {
									$query .= implode(", ", $updateParts);
									$query .= " WHERE prod_id = $prod_id{$product_scope_where}";
									
									$result = mysqli_query($conn, $query);
									
								if($result) {
									// Log activity
									$activity_desc = "Product '<strong>{$prod_name}</strong>' (ID: {$prod_id}) was updated by {$activity_actor_name}";
									$activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
									$log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'products', NOW())";
									mysqli_query($conn, $log_query);
									
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Product updated successfully.', 'success'); setTimeout(() => window.location.href = 'products-admin.php', 1500); });</script>";
									} else {
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Database query failed: " . addslashes(mysqli_error($conn)) . "', 'error'); });</script>";
									}
								} else {
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Info', 'No changes were made.', 'info'); });</script>";
								}
							}

							if(isset($_POST['del-prod'])){
								$del_id = $_POST['del_id'];
								
								// Get product name before deleting for activity log
								$prod_query = "SELECT prod_name FROM mrb_fireex WHERE prod_id = $del_id{$product_scope_where}";
								$prod_result = mysqli_query($conn, $prod_query);
								$prod_data = $prod_result ? mysqli_fetch_assoc($prod_result) : null;
								if (!$prod_data) {
									echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Product not found for your shop.', 'error'); });</script>";
								} else {
									$deleted_prod_name = mysqli_real_escape_string($conn, $prod_data['prod_name']);
								
									$deleteQuery = "UPDATE mrb_fireex SET prod_type = 'deleted', is_hidden = 'true' WHERE prod_id = $del_id{$product_scope_where}";
									$deleteResult = mysqli_query($conn, $deleteQuery);
								
									if($deleteResult) {
									// Log activity
									$activity_desc = "Product '<strong>{$deleted_prod_name}</strong>' (ID: {$del_id}) was deleted by {$activity_actor_name}";
									$activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
									$log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'products', NOW())";
									mysqli_query($conn, $log_query);
									
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Success', 'Product deleted successfully.', 'success'); setTimeout(() => window.location.href = 'products-admin.php', 1500); });</script>";
									} else {
										echo "<script>document.addEventListener('DOMContentLoaded', function() { showAdminToast('Error', 'Error deleting product: " . addslashes(mysqli_error($conn)) . "', 'error'); });</script>";
									}
								}
							}
				
				?>			</div>
		</div>
		</main>
	</section>
	
	<script src="script.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
	<script>
		const myModal = document.getElementById('myModal')
		const myInput = document.getElementById('myInput')

		myModal.addEventListener('shown.bs.modal', () => {
		myInput.focus()
		})

		function confirmLogout() {
				if (confirm("Are you sure you want to log out?")) {
					window.location.href = "../logout.php";
				}
			}
	</script>
<?php include 'toast-notification.php'; ?>
</body>
</html>
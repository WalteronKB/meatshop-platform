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

  $order_scope_condition = "";
  $message_scope_condition = "";
  $employee_scope_condition = "";
  if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
      $order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
      $message_scope_condition = " AND shop_id = {$current_admin_shop_id}";
      $employee_scope_condition = " AND shop_id = {$current_admin_shop_id}";
    } else {
      $order_scope_condition = " AND 1 = 0";
      $message_scope_condition = " AND 1 = 0";
      $employee_scope_condition = " AND 1 = 0";
    }
  }

  $user_pic = 'Images/anonymous.jpg';
  $user_pic_query = "SELECT user_pic FROM mrb_users WHERE user_id = '{$current_user_id}' LIMIT 1";
  $user_pic_result = mysqli_query($conn, $user_pic_query);
  if ($user_pic_result && mysqli_num_rows($user_pic_result) > 0) {
    $user_pic_row = mysqli_fetch_assoc($user_pic_result);
    if (!empty($user_pic_row['user_pic'])) {
      $user_pic = $user_pic_row['user_pic'];
    }
  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">

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
      <li class="active">
        <a href="team_admin.php" class="active">
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
  </section>



  <section id="content">
    <nav>
      <i class='bx bx-menu'></i>
      <a href="javascript:void(0);" class="nav-link" onclick="toggleSidebar()">Categories</a>

      <form action="#" type="hidden">
        <div class="form-input">
          <input type="hidden" placeholder="Search...">
          <button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search'></i></button>
        </div>
      </form>
      <input type="checkbox" id="switch-mode" hidden>
      <label for="switch-mode" class="switch-mode"></label>

      <a href="account-admin.php" class="profile">
        <img src="../<?php echo $user_pic?>">
      </a>
    </nav>

    <main>
      <h1 class="page-header" style="margin-bottom: 2rem;">Meet Our Team</h1>

      <div class="team-container" style="display: flex; flex-wrap: wrap; gap: 2.5rem; justify-content: start;">
		<?php
      $team_query = "SELECT emp_first_name, emp_middle_name, emp_last_name, position, department, status, email "
        . "FROM mrb_employees "
        . "WHERE status = 'Active'{$employee_scope_condition} "
        . "ORDER BY emp_last_name ASC, emp_first_name ASC";
			$team_result = mysqli_query($conn, $team_query);

			if ($team_result && mysqli_num_rows($team_result) > 0) {
				while ($emp = mysqli_fetch_assoc($team_result)) {
					$full_name = trim($emp['emp_first_name'] . ' ' . ($emp['emp_middle_name'] ?? '') . ' ' . $emp['emp_last_name']);
					$position = $emp['position'] !== '' ? $emp['position'] : 'Employee';
					$department = $emp['department'] !== '' ? $emp['department'] : 'General';
					$email = $emp['email'] !== '' ? $emp['email'] : 'No email provided';
					echo "
					<div class='team-member' style='flex: 1 1 280px; background: #fff; padding: 1.5rem; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); text-align: center;'>
						<img src='../Images/anonymous.jpg' alt='" . htmlspecialchars($full_name, ENT_QUOTES) . "' style='width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;' />
						<h3 style='margin-bottom: 0.3rem;'>" . htmlspecialchars($full_name, ENT_QUOTES) . "</h3>
						<p class='role' style='font-weight: 600; color: #AB2A02; margin-bottom: 0.4rem;'>" . htmlspecialchars($position, ENT_QUOTES) . "</p>
						<p class='description' style='color: #555; font-size: 0.95rem; line-height: 1.4; margin-bottom: 0.3rem;'>Department: " . htmlspecialchars($department, ENT_QUOTES) . "</p>
						<p class='description' style='color: #555; font-size: 0.95rem; line-height: 1.4;'>" . htmlspecialchars($email, ENT_QUOTES) . "</p>
					</div>";
				}
			} else {
				echo "<div style='background:#fff; padding:1.5rem; border-radius:10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); width:100%; text-align:center; color:#666;'>No employees found for this shop.</div>";
			}
		?>
      </div>
     
    </main>
  </section>

  <script src="script.js"></script>
  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('collapsed');
    }
    function confirmLogout() {
				if (confirm("Are you sure you want to log out?")) {
					window.location.href = "../logout.php";
				}
			}
  </script>
</body>

</html>
<?php
session_start();
include '../connection.php';

// Super Admin Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: mrbloginpage.php");
    exit();
}

// Strict Super Admin Only Access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'super_admin') {
    header("Location: account-admin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Super Admin User Data
$query = "SELECT * FROM mrb_users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Super Admin Account Management</title>
	
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
            <li>
                <a href="analytics-admin.php">
                <i class='bx bxs-bar-chart-alt-2'></i>
                <span class="text">Analytics</span>
                </a>
            </li>
			<li >
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
			<li class="active">
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
			<i class='bx bx-menu'></i>
			<a href="#" class="brand">
				<i class='bx bxs-restaurant'></i>
			</a>
			<form action="#">
				<div class="form-input">
				</div>
			</form>
			<a href="#" class="profile">
				<img src="../<?php echo $row['user_pic']; ?>">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		 
		<main style="padding: 0px;">
		<div class="container" style="margin-top: 20px;">
        <div class="head-title">
            <div class="left">
                <h1>Super Admin Account</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Account Management</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Super Admin Profile</a></li>
                </ul>
            </div>
        </div>

        <?php if(isset($_GET['updated']) && $_GET['updated'] == 'true'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Profile updated successfully!
                <?php if(isset($_GET['password_msg'])): ?>
                <div><?php echo htmlspecialchars($_GET['password_msg']); ?></div>
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['address']) && $_GET['address'] == 'success'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Addresses updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

        <div class="row gutters d-flex">
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="account-settings">
                            <div class="user-profile">
                                <div class="user-avatar">
                                    <img src="../<?php echo $row['user_pic']; ?>" class='img-fluid rounded-circle' style='width: 120px; height: 120px; object-fit: cover; border: 5px solid #f1f1f1;'>
                                </div>
                                <h5 class="user-name"><?php echo $row['user_name']." ". $row['user_mname']." ". $row['user_lname']; ?></h5>
                                <h6 class="user-email"><?php echo $row['user_email']; ?></h6>
                                <span class="badge bg-danger mt-2">
                                    <i class='bx bxs-shield-alt-2'></i> Super Administrator
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="row gutters">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                <h6 class="mb-3 red-text">Personal Details</h6>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="fullName">First Name</label>
                                    <input type="text" readonly class="form-control" id="fullName" value="<?php echo $row['user_name']; ?>">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="middleName">Middle Name</label>
                                    <input type="text" readonly class="form-control" id="middleName" value="<?php echo $row['user_mname']; ?>">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" readonly class="form-control" id="lastName" value="<?php echo $row['user_lname']; ?>">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="eMail">Email</label>
                                    <input type="email" readonly class="form-control" id="eMail" value="<?php echo $row['user_email']; ?>">
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="text" readonly class="form-control" id="phone" value="<?php echo $row['user_contactnum']; ?>"> 
                                </div>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-12">
                                <div class="form-group">
                                    <label for="website">Password</label>
                                    <input type="password" readonly class="form-control" id="password" value="<?php echo $row['user_password']; ?>">
                                </div>
                            </div>
                            <div class="row gutters">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                    <div class="text-right">
                                        <a type="button" id="submit" name="submit" class="btn red-bg text-light"
                                         data-bs-toggle='modal' data-bs-target='#editmodal'>Edit Information</a>
                                    </div>
                                    <div class='modal fade' id='editmodal' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                                        <div class='modal-dialog modal-lg'>
                                            <div class='modal-content'>
                                            <div class='modal-header red-bg text-white'>
                                                <h1 class='modal-title fs-5' id='exampleModalLabel'>
                                                    <i class='bx bxs-shield-alt-2'></i> Edit Super Admin Profile: <?php echo $row['user_name']; ?>
                                                </h1>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                            </div>
                                            <div class='modal-body'>

                                            <form method="post" action="update_profile.php" enctype="multipart/form-data">
                                                <div class='row'>
                                                    <!-- Left column - User Avatar -->
                                                    <div class='col-md-4 text-center mb-4'>
                                                    <div class='avatar-container mb-3 position-relative'>
                                                        <img src='../<?php echo $row['user_pic']; ?>' alt='User Avatar' class='img-fluid rounded-circle' style='width: 150px; height: 150px; object-fit: cover; border: 5px solid #f1f1f1;'>
                                                        <div class="position-absolute bottom-0 end-0">
                                                        <label for="profile_pic" class="btn btn-sm  red-bg rounded-circle" style="width: 32px; height: 32px; padding: 0; line-height: 32px;">
                                                            <i class="bx bx-camera"></i>
                                                        </label>
                                                        <input type="file" id="profile_pic" name="profile_pic" class="d-none">
                                                        </div>
                                                    </div>
                                                    <p class="text-muted small">Click the button to change profile picture</p>
                                                    </div>
                                                    
                                                    <!-- Right column - User Form Fields -->
                                                    <div class='col-md-8'>
                                                    <div class='card mb-4'>
                                                        <div class='card-header bg-light'>
                                                        <h5 class='mb-0'>Personal Information</h5>
                                                        </div>
                                                        <div class='card-body'>
                                                        <div class="mb-3">
                                                            <label for="edit_name" class="form-label">First Name</label>
                                                            <input type="text" required class="form-control" id="edit_name" name="edit_name" value="<?php echo $row['user_name']; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="edit_mname" class="form-label">Middle Name</label>
                                                            <input type="text" required class="form-control" id="edit_mname" name="edit_mname" value="<?php echo $row['user_mname']; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="edit_lname" class="form-label">Last Name</label>
                                                            <input type="text" required class="form-control" id="edit_lname" name="edit_lname" value="<?php echo $row['user_lname']; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="edit_email" class="form-label">Email Address</label>
                                                            <input type="email" required class="form-control" id="edit_email" name="edit_email" value="<?php echo $row['user_email']; ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="edit_phone" class="form-label">Phone Number</label>
                                                            <input type="text" required class="form-control" id="edit_phone" name="edit_phone" value="<?php echo $row['user_contactnum']; ?>">
                                                        </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class='card mb-4'>
                                                        <div class='card-header bg-light'>
                                                        <h5 class='mb-0'>Change Password</h5>
                                                        </div>
                                                        <div class='card-body'>
                                                        <div class="mb-3">
                                                            <label for="current_password" class="form-label">Current Password</label>
                                                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="new_password" class="form-label">New Password</label>
                                                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                                                            <div class="form-text">Leave password fields empty if you don't want to change it</div>
                                                        </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-end">
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn red-bg text-dark">Save Changes</button>
                                                    </div>
                                                    </div>
                                                </div>
                                                </form>
                                            </div>
                                            </div>
                                        </div>
                                        </div>
                                </div>
                            </div>
                            </div>
                            <div class="row gutters">
                                <div class="w-100">
                                    <h6 class="mb-3 red-text">Address</h6>
                                </div>
                                <div class="w-100">
                                    <div class="form-group">
                                        <label for="Street">Location 1:</label>
                                        <input type="name" class="form-control" id="Street" value="<?php echo $row['user_location']; ?>" readonly>
                                    </div>
                                    <?php
                                        if (!empty($row['user_location2'])) {
                                            echo '<div class="form-group">';
                                            echo '<label for="Street2">Location 2:</label>';
                                            echo '<input type="name" class="form-control" id="Street2" value="' . $row['user_location2'] . '" readonly>';
                                            echo '</div>';
                                        }
                                        if (!empty($row['user_location3'])) {
                                            echo '<div class="form-group">';
                                            echo '<label for="Street3">Location 3:</label>';
                                            echo '<input type="name" class="form-control" id="Street3" value="' . $row['user_location3'] . '" readonly>';
                                            echo '</div>';
                                        }
                                    ?>
                                </div>
                                <div class="row gutters">
                                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                                        <div class="text-right">
                                            <a type="button" class="btn red-bg text-light" data-bs-toggle="modal" data-bs-target="#addressModal">Manage Address</a>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                            <div class="modal-header red-bg text-white">
                                                <h5 class="modal-title" id="addressModalLabel">Manage Addresses</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="update_address.php" method="post">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                    
                                                    <!-- Location 1 -->
                                                    <h6 class="mb-2">Location 1 *</h6>
                                                    <div class="mb-3">
                                                        <label for="location1_city" class="form-label">City/Municipality</label>
                                                        <select class="form-select" id="location1_city" name="location1_city" required onchange="updateBarangays(1)">
                                                            <option value="">Select City</option>
                                                            <optgroup label="NCR (Metro Manila)">
                                                                <option value="Manila">Manila</option>
                                                                <option value="Quezon City">Quezon City</option>
                                                                <option value="Makati">Makati</option>
                                                                <option value="Pasig">Pasig</option>
                                                                <option value="Taguig">Taguig</option>
                                                                <option value="Marikina">Marikina</option>
                                                                <option value="Paranaque">Paranaque</option>
                                                                <option value="Las Pinas">Las Piñas</option>
                                                                <option value="Muntinlupa">Muntinlupa</option>
                                                                <option value="Caloocan">Caloocan</option>
                                                                <option value="Navotas">Navotas</option>
                                                                <option value="Malabon">Malabon</option>
                                                                <option value="Valenzuela">Valenzuela</option>
                                                                <option value="San Juan">San Juan</option>
                                                                <option value="Mandaluyong">Mandaluyong</option>
                                                            </optgroup>
                                                            <optgroup label="CALABARZON">
                                                                <option value="Cavite City">Cavite City</option>
                                                                <option value="Dasmariñas">Dasmariñas</option>
                                                                <option value="Tagaytay">Tagaytay</option>
                                                                <option value="Santa Rosa">Santa Rosa</option>
                                                                <option value="Biñan">Biñan</option>
                                                                <option value="San Pedro">San Pedro</option>
                                                                <option value="Kawit">Kawit</option>
                                                                <option value="Bacoor">Bacoor</option>
                                                                <option value="Batangas City">Batangas City</option>
                                                                <option value="Lipa">Lipa</option>
                                                                <option value="Rosario">Rosario</option>
                                                                <option value="Antipolo">Antipolo</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location1_barangay" class="form-label">Barangay</label>
                                                        <select class="form-select" id="location1_barangay" name="location1_barangay" required>
                                                            <option value="">Select Barangay</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location1_street" class="form-label">Street/Building Address</label>
                                                        <input type="text" class="form-control" id="location1_street" name="location1_street" placeholder="e.g., 123 Main Street, Unit 5B" required>
                                                    </div>

                                                    <hr>
                                                    
                                                    <!-- Location 2 -->
                                                    <h6 class="mb-2">Location 2 (Optional)</h6>
                                                    <div class="mb-3">
                                                        <label for="location2_city" class="form-label">City/Municipality</label>
                                                        <select class="form-select" id="location2_city" name="location2_city" onchange="updateBarangays(2)">
                                                            <option value="">Select City</option>
                                                            <optgroup label="NCR (Metro Manila)">
                                                                <option value="Manila">Manila</option>
                                                                <option value="Quezon City">Quezon City</option>
                                                                <option value="Makati">Makati</option>
                                                                <option value="Pasig">Pasig</option>
                                                                <option value="Taguig">Taguig</option>
                                                                <option value="Marikina">Marikina</option>
                                                                <option value="Paranaque">Paranaque</option>
                                                                <option value="Las Pinas">Las Piñas</option>
                                                                <option value="Muntinlupa">Muntinlupa</option>
                                                                <option value="Caloocan">Caloocan</option>
                                                                <option value="Navotas">Navotas</option>
                                                                <option value="Malabon">Malabon</option>
                                                                <option value="Valenzuela">Valenzuela</option>
                                                                <option value="San Juan">San Juan</option>
                                                                <option value="Mandaluyong">Mandaluyong</option>
                                                            </optgroup>
                                                            <optgroup label="CALABARZON">
                                                                <option value="Cavite City">Cavite City</option>
                                                                <option value="Dasmariñas">Dasmariñas</option>
                                                                <option value="Tagaytay">Tagaytay</option>
                                                                <option value="Santa Rosa">Santa Rosa</option>
                                                                <option value="Biñan">Biñan</option>
                                                                <option value="San Pedro">San Pedro</option>
                                                                <option value="Kawit">Kawit</option>
                                                                <option value="Bacoor">Bacoor</option>
                                                                <option value="Batangas City">Batangas City</option>
                                                                <option value="Lipa">Lipa</option>
                                                                <option value="Rosario">Rosario</option>
                                                                <option value="Antipolo">Antipolo</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location2_barangay" class="form-label">Barangay</label>
                                                        <select class="form-select" id="location2_barangay" name="location2_barangay">
                                                            <option value="">Select Barangay</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location2_street" class="form-label">Street/Building Address</label>
                                                        <input type="text" class="form-control" id="location2_street" name="location2_street" placeholder="e.g., 123 Main Street, Unit 5B">
                                                    </div>

                                                    <hr>
                                                    
                                                    <!-- Location 3 -->
                                                    <h6 class="mb-2">Location 3 (Optional)</h6>
                                                    <div class="mb-3">
                                                        <label for="location3_city" class="form-label">City/Municipality</label>
                                                        <select class="form-select" id="location3_city" name="location3_city" onchange="updateBarangays(3)">
                                                            <option value="">Select City</option>
                                                            <optgroup label="NCR (Metro Manila)">
                                                                <option value="Manila">Manila</option>
                                                                <option value="Quezon City">Quezon City</option>
                                                                <option value="Makati">Makati</option>
                                                                <option value="Pasig">Pasig</option>
                                                                <option value="Taguig">Taguig</option>
                                                                <option value="Marikina">Marikina</option>
                                                                <option value="Paranaque">Paranaque</option>
                                                                <option value="Las Pinas">Las Piñas</option>
                                                                <option value="Muntinlupa">Muntinlupa</option>
                                                                <option value="Caloocan">Caloocan</option>
                                                                <option value="Navotas">Navotas</option>
                                                                <option value="Malabon">Malabon</option>
                                                                <option value="Valenzuela">Valenzuela</option>
                                                                <option value="San Juan">San Juan</option>
                                                                <option value="Mandaluyong">Mandaluyong</option>
                                                            </optgroup>
                                                            <optgroup label="CALABARZON">
                                                                <option value="Cavite City">Cavite City</option>
                                                                <option value="Dasmariñas">Dasmariñas</option>
                                                                <option value="Tagaytay">Tagaytay</option>
                                                                <option value="Santa Rosa">Santa Rosa</option>
                                                                <option value="Biñan">Biñan</option>
                                                                <option value="San Pedro">San Pedro</option>
                                                                <option value="Kawit">Kawit</option>
                                                                <option value="Bacoor">Bacoor</option>
                                                                <option value="Batangas City">Batangas City</option>
                                                                <option value="Lipa">Lipa</option>
                                                                <option value="Rosario">Rosario</option>
                                                                <option value="Antipolo">Antipolo</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location3_barangay" class="form-label">Barangay</label>
                                                        <select class="form-select" id="location3_barangay" name="location3_barangay">
                                                            <option value="">Select Barangay</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="location3_street" class="form-label">Street/Building Address</label>
                                                        <input type="text" class="form-control" id="location3_street" name="location3_street" placeholder="e.g., 123 Main Street, Unit 5B">
                                                    </div>
                                                    
                                                    <div class="d-flex justify-content-end mt-4">
                                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn red-bg text-dark">Save Addresses</button>
                                                    </div>
                                                </form>
                                                
                                                <script>
                                                    const barangayData = {
                                                        'Manila': ['Binondo', 'Tondo', 'Quiapo', 'San Nicolas', 'Sampaloc', 'Santa Ana', 'Intramuros', 'Malate', 'Ermita', 'Pandacan'],
                                                        'Quezon City': ['Barangka', 'Batasan Hills', 'Culiat', 'Diliman', 'Fairview', 'Holy Spirit', 'Kamuning', 'Laging Handa', 'Marulas', 'Masambong'],
                                                        'Makati': ['Bangkal', 'Bel-Air', 'Cembo', 'Comembo', 'Guadalupe Nuevo', 'Karangalan', 'Magallanes', 'Palanan', 'Pembo', 'Rizal'],
                                                        'Pasig': ['Bagong Ilog', 'Bagong Pagasa', 'Bambang', 'Caniogan', 'Cataasan', 'Doña Soledad', 'Kalawaan', 'Malinao', 'Masinag', 'Pinagbuhatan'],
                                                        'Taguig': ['Bagumbuhay', 'Bambang', 'Barangka', 'Bonifacio', 'Cembo', 'East Rembo', 'Fort Bonifacio', 'Guacharo', 'Hagonoy', 'Ibayo-Tipas'],
                                                        'Marikina': ['Barangka', 'Barangka-Ibaba', 'Calentayan', 'Concepcion', 'Dalandanan', 'Malanday', 'Manggahan', 'Nangka', 'Parang', 'San Roque'],
                                                        'Paranaque': ['Baclaran', 'Bongabon', 'Caltabellota', 'Kabihasnan', 'Magallanes', 'Maharlika', 'Malibay', 'Tambo', 'Tunasan', 'Ypati'],
                                                        'Las Pinas': ['Almanza', 'Amang Santos', 'Ayala Boulevard', 'Baclaran', 'Banga', 'Barbosa', 'Comungaro', 'Dacanlao', 'Dasmariñas', 'Guysuran'],
                                                        'Muntinlupa': ['Alabang', 'Aliwaan', 'Bangkal', 'Bayog', 'Cavite', 'Cupang', 'Filinvest', 'Ilalim', 'Karangalan', 'Maybunga'],
                                                        'Caloocan': ['Bagbag', 'Balintawak', 'Bukal', 'Caloocan East', 'Dalandanan', 'Deparo', 'Llano', 'Maypajo', 'Patayan', 'San Juan'],
                                                        'Navotas': ['Bangculasi', 'Daanghari', 'East Navotas', 'Kaunlaran', 'Maharlika', 'Malayo', 'Maunlad', 'North Bay Boulevard', 'Sipac-Almacen', 'South Bay Boulevard'],
                                                        'Malabon': ['Catmon', 'Dampalit', 'Flores', 'Ibaba', 'Nangka', 'Pili', 'Potrero', 'Sabang', 'San Agustin', 'Tangos'],
                                                        'Valenzuela': ['Barangka', 'Bisig', 'Canumay', 'Coloong', 'Dalandanan', 'Karuhatan', 'Malinta', 'Marulas', 'Parada', 'Paso de Blas'],
                                                        'San Juan': ['Barangka', 'Ikot Daan', 'Kanluran', 'Komunidad', 'Marilag', 'Masagana', 'Progreso', 'Salapan', 'Sampaguita', 'Santa Lucia'],
                                                        'Mandaluyong': ['Addition Hills', 'Barangka', 'Bangkusay', 'Buangon', 'Hagdan-Zabarte', 'Hausman', 'Hinatuaran', 'Langagay', 'Mabolo', 'Malanday'],
                                                        'Cavite City': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5'],
                                                        'Dasmariñas': ['Burol', 'Kaybagal', 'Mataas na Lupa', 'Pook ng Bayan', 'Sampalukan'],
                                                        'Tagaytay': ['Bagong Pook', 'Calajon', 'Maguyam', 'Paho', 'Silang'],
                                                        'Santa Rosa': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4'],
                                                        'Biñan': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5'],
                                                        'San Pedro': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
                                                        'Kawit': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
                                                        'Bacoor': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4'],
                                                        'Batangas City': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4'],
                                                        'Lipa': ['Barangay 1', 'Barangay 2', 'Barangay 3'],
                                                        'Rosario': ['Barangay 1', 'Barangay 2'],
                                                        'Antipolo': ['Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4']
                                                    };

                                                    function updateBarangays(locationNum) {
                                                        const citySelect = document.getElementById('location' + locationNum + '_city');
                                                        const barangaySelect = document.getElementById('location' + locationNum + '_barangay');
                                                        const selectedCity = citySelect.value;

                                                        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';

                                                        if (selectedCity && barangayData[selectedCity]) {
                                                            barangayData[selectedCity].forEach(barangay => {
                                                                const option = document.createElement('option');
                                                                option.value = barangay;
                                                                option.textContent = barangay;
                                                                barangaySelect.appendChild(option);
                                                            });
                                                        }
                                                    }
                                                </script>

                                                <?php if(isset($_GET['error']) && $_GET['error'] == 'true'): ?>
                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function() {
                                                            showAdminToast('Error', '<?php echo addslashes($_GET['msg']); ?>', 'error');
                                                        });
                                                    </script>
                                                <?php endif; ?>

                                            </div>
                                            </div>
                                        </div>
                                        </div>
				</div>
				</div>
				
			</div>
		</div>
	</div>
            </div>
        </div>
	</section>
	
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
				// On mobile, hide sidebar when modal opens
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
	<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="update_profile.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Email validation
            const emailInput = document.getElementById('edit_email');
            
            
            // Password validation
            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (currentPassword.value || newPassword.value || confirmPassword.value) {
                if (!currentPassword.value) {
                    e.preventDefault();
                    showAdminToast('Missing Information', 'Please enter your current password', 'error');
                    return;
                }
                
                if (!newPassword.value) {
                    e.preventDefault();
                    showAdminToast('Missing Information', 'Please enter a new password', 'error');
                    return;
                }
                
                if (!confirmPassword.value) {
                    e.preventDefault();
                    showAdminToast('Missing Information', 'Please confirm your new password', 'error');
                    return;
                }
                
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    showAdminToast('Password Mismatch', 'New passwords do not match', 'error');
                    return;
                }
                
                if (newPassword.value.length < 6) {
                    e.preventDefault();
                    showAdminToast('Invalid Password', 'Password must be at least 6 characters', 'error');
                    return;
                }
            }
        });
    }
    
    
    // Preview profile picture before upload
    const profilePicInput = document.getElementById('profile_pic');
    if (profilePicInput) {
        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImg = document.querySelector('.avatar-container img');
                    if (previewImg) {
                        previewImg.src = e.target.result;
                    }
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>
	
	<script src="script.js"></script>

	<?php include 'toast-notification.php'; ?>
</body>
</html>

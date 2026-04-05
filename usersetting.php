<?php
    include 'connection.php';
    session_start();
    
    // Redirect to login if not logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: mrbloginpage.php");
        exit;
    }
    
    // Always use the session user_id instead of GET parameter
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM mrb_users WHERE user_id = $user_id";

    $result = mysqli_query($conn, $query);
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
    } else {
        echo "User not found.";
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
    <div class="container">
    <?php if(isset($_GET['updated']) && $_GET['updated'] == 'true'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Profile updated successfully!
        <?php if(isset($_GET['password_msg'])): ?>
        <div><?php echo htmlspecialchars($_GET['password_msg']); ?></div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if(isset($_GET['shop_closed']) && $_GET['shop_closed'] == 'true'): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        Your shop has been closed and your account is now a regular user account. You can apply again to reopen your store.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="usersetting.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

</head>
<body>
<div class="container">
<div class="row gutters d-flex">
    <div class="row my-3 px-4">
        <button onclick="window.location.href='landpage.php';" class="btn red-bg text-light" style="width: 200px;">Back</button>
    </div>
    <div class="col-lg-4">
		<div class="card">
			<div class="card-body">
				<div class="account-settings">
					<div class="user-profile">
						<div class="user-avatar">
							<img src=<?php echo $row['user_pic']; ?> class='img-fluid rounded-circle' style='width: 120px; height: 120px; object-fit: cover; border: 5px solid #f1f1f1;'>
						</div>
						<h5 class="user-name"><?php echo $row['user_name']." ". $row['user_mname']." ". $row['user_lname']; ?></h5>
						<h6 class="user-email"><?php echo $row['user_email']; ?></h6>

                        <button onclick="window.location.href='logout.php';" class="btn red-bg text-light" style="width: 200px;">Logout</button>

                        <button onclick="window.location.href='shop_application.php';" class="btn red-bg text-light mt-2" style="width: 200px;">Apply Shop</button>
					</div>
				</div>
			</div>
		</div>
        <a href="userorders.php?order_sort=All" style="text-decoration: none;">
        <div class="card">
			<div class="card-body">
                <?php
                    $count_query = "SELECT COUNT(*) AS unseen_all FROM mrb_orders WHERE user_id = '$user_id' AND seen_byuser = 'false'";
                    $count_result = mysqli_query($conn, $count_query);
                    $unseen_count = mysqli_fetch_assoc($count_result);
                ?>
				<div class="account-settings text-center d-flex align-items-center justify-content-center" style="position: relative;">
                    <?php if ($unseen_count['unseen_all'] > 0){
                        echo "<span class='badge bg-danger position-absolute' style='top: 10px; right: 10px;'>" . $unseen_count['unseen_all'] . "</span>";
                    } ?>  
                    
                
                    <i class='bx bxs-cart-alt bx-lg red-text'></i>
                    <h5 class="user-name">My Orders</h5>
				</div>
			</div>
		</div>
        </a>
        
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
							<button type="button" id="submit" name="submit" class="btn red-bg text-light"
                             data-bs-toggle='modal' data-bs-target='#editmodal'>Edit Information</button>
						</div>
                        <div class='modal fade' id='editmodal' tabindex='-1' aria-labelledby='exampleModalLabel' aria-hidden='true'>
                            <div class='modal-dialog modal-lg'>
                                <div class='modal-content'>
                                <div class='modal-header red-bg text-white'>
                                    <h1 class='modal-title fs-5' id='exampleModalLabel'>Edit Profile: <?php echo $row['user_name']; ?></h1>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                </div>
                                <div class='modal-body'>

                                <form method="post" action="update_profile.php" enctype="multipart/form-data">
                                    <div class='row'>
                                        <!-- Left column - User Avatar -->
                                        <div class='col-md-4 text-center mb-4'>
                                        <div class='avatar-container mb-3 position-relative'>
                                            <img src='<?php echo $row['user_pic']; ?>' alt='User Avatar' class='img-fluid rounded-circle' style='width: 150px; height: 150px; object-fit: cover; border: 5px solid #f1f1f1;'>
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
                                                <label for="edit_email" class="form-label">Email Address (not editable)</label>
                                                <input type="email" readonly class="form-control" id="edit_email" name="edit_email" value="<?php echo $row['user_email']; ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="edit_phone" class="form-label">Phone Number (not editable)</label>
                                                <input type="text" readonly class="form-control" id="edit_phone" name="edit_phone" value="<?php echo $row['user_contactnum']; ?>">
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
                                            <button type="submit" class="btn red-bg text-white">Save Changes</button>
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
                            <button type="button" class="btn red-bg text-light" data-bs-toggle="modal" data-bs-target="#addressModal">Manage Address</button>
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
                                <form action="update_address.php" method="post" id="addressForm">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    
                                    <!-- Location 1 -->
                                    <h6 class="mb-2">Location 1 *</h6>
                                    <div class="mb-3">
                                        <label for="location1_search" class="form-label">Find Address On Map (Exact)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="location1_search" placeholder="Search place, street, or landmark">
                                            <button type="button" class="btn btn-outline-secondary" id="location1_search_btn">Search</button>
                                            <button type="button" class="btn btn-outline-secondary" id="location1_geolocate_btn">Use My Location</button>
                                        </div>
                                        <div id="location1_search_results" class="list-group mt-2" style="max-height: 180px; overflow-y: auto; display: none;"></div>
                                        <div id="location1_map" style="height: 260px; border: 1px solid #dee2e6; border-radius: 8px;" class="mt-2"></div>
                                        <small class="text-muted d-block mt-2" id="location1_selected_address_text">Move the pin or search an address to set exact delivery point.</small>
                                        <input type="hidden" id="location1_full_address" name="location1_full_address" value="">
                                        <input type="hidden" id="location1_lat" name="location1_lat" value="">
                                        <input type="hidden" id="location1_lng" name="location1_lng" value="">
                                    </div>

                                    <div class="mb-3">
                                        <label for="location1_city" class="form-label">City/Municipality</label>
                                        <select class="form-select" id="location1_city" name="location1_city">
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
                                        <input type="text" class="form-control" id="location1_barangay" name="location1_barangay" placeholder="e.g., Salitran, San Agustin">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="location1_street" class="form-label">Street/Building Address</label>
                                        <input type="text" class="form-control" id="location1_street" name="location1_street" placeholder="e.g., 123 Main Street, Unit 5B">
                                    </div>

                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="showLocation2Btn">Add another location</button>
                                    </div>

                                    <div id="location2Section" style="display: none;">
                                    <hr>
                                    
                                    <!-- Location 2 -->
                                    <h6 class="mb-2">Location 2 (Optional)</h6>
                                    <div class="mb-3">
                                        <label for="location2_search" class="form-label">Find Address On Map (Exact)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="location2_search" placeholder="Search place, street, or landmark">
                                            <button type="button" class="btn btn-outline-secondary" id="location2_search_btn">Search</button>
                                            <button type="button" class="btn btn-outline-secondary" id="location2_geolocate_btn">Use My Location</button>
                                        </div>
                                        <div id="location2_search_results" class="list-group mt-2" style="max-height: 180px; overflow-y: auto; display: none;"></div>
                                        <div id="location2_map" style="height: 260px; border: 1px solid #dee2e6; border-radius: 8px;" class="mt-2"></div>
                                        <small class="text-muted d-block mt-2" id="location2_selected_address_text">Move the pin or search an address to set exact delivery point.</small>
                                        <input type="hidden" id="location2_full_address" name="location2_full_address" value="">
                                        <input type="hidden" id="location2_lat" name="location2_lat" value="">
                                        <input type="hidden" id="location2_lng" name="location2_lng" value="">
                                    </div>

                                    <div class="mb-3">
                                        <label for="location2_city" class="form-label">City/Municipality</label>
                                        <select class="form-select" id="location2_city" name="location2_city">
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
                                        <input type="text" class="form-control" id="location2_barangay" name="location2_barangay" placeholder="e.g., Salitran, San Agustin">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="location2_street" class="form-label">Street/Building Address</label>
                                        <input type="text" class="form-control" id="location2_street" name="location2_street" placeholder="e.g., 123 Main Street, Unit 5B">
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="showLocation3Btn">Add another location</button>
                                    </div>
                                    </div>

                                    <div id="location3Section" style="display: none;">
                                    <hr>
                                    
                                    <!-- Location 3 -->
                                    <h6 class="mb-2">Location 3 (Optional)</h6>
                                    <div class="mb-3">
                                        <label for="location3_search" class="form-label">Find Address On Map (Exact)</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="location3_search" placeholder="Search place, street, or landmark">
                                            <button type="button" class="btn btn-outline-secondary" id="location3_search_btn">Search</button>
                                            <button type="button" class="btn btn-outline-secondary" id="location3_geolocate_btn">Use My Location</button>
                                        </div>
                                        <div id="location3_search_results" class="list-group mt-2" style="max-height: 180px; overflow-y: auto; display: none;"></div>
                                        <div id="location3_map" style="height: 260px; border: 1px solid #dee2e6; border-radius: 8px;" class="mt-2"></div>
                                        <small class="text-muted d-block mt-2" id="location3_selected_address_text">Move the pin or search an address to set exact delivery point.</small>
                                        <input type="hidden" id="location3_full_address" name="location3_full_address" value="">
                                        <input type="hidden" id="location3_lat" name="location3_lat" value="">
                                        <input type="hidden" id="location3_lng" name="location3_lng" value="">
                                    </div>

                                    <div class="mb-3">
                                        <label for="location3_city" class="form-label">City/Municipality</label>
                                        <select class="form-select" id="location3_city" name="location3_city">
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
                                        <input type="text" class="form-control" id="location3_barangay" name="location3_barangay" placeholder="e.g., Salitran, San Agustin">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="location3_street" class="form-label">Street/Building Address</label>
                                        <input type="text" class="form-control" id="location3_street" name="location3_street" placeholder="e.g., 123 Main Street, Unit 5B">
                                    </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn red-bg text-white">Save Addresses</button>
                                    </div>
                                </form>
                                
                                <script>
                                    window.savedLocations = {
                                        location1: '<?php echo isset($row['user_location']) ? addslashes($row['user_location']) : ''; ?>',
                                        location2: '<?php echo isset($row['user_location2']) ? addslashes($row['user_location2']) : ''; ?>',
                                        location3: '<?php echo isset($row['user_location3']) ? addslashes($row['user_location3']) : ''; ?>'
                                    };
                                </script>

                                <?php if(isset($_GET['error']) && $_GET['error'] == 'true'): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($_GET['msg']); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                    <script>
                                        // Optional: Also show as an alert for immediate attention
                                        alert('<?php echo addslashes($_GET['msg']); ?>');
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
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
                    alert('Please enter your current password');
                    return;
                }
                
                if (!newPassword.value) {
                    e.preventDefault();
                    alert('Please enter a new password');
                    return;
                }
                
                if (!confirmPassword.value) {
                    e.preventDefault();
                    alert('Please confirm your new password');
                    return;
                }
                
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('New passwords do not match');
                    return;
                }
                
                if (newPassword.value.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters');
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

    const addressModal = document.getElementById('addressModal');
    const addressForm = document.getElementById('addressForm');
    const location2Section = document.getElementById('location2Section');
    const location3Section = document.getElementById('location3Section');
    const showLocation2Btn = document.getElementById('showLocation2Btn');
    const showLocation3Btn = document.getElementById('showLocation3Btn');

    const defaultLat = 14.2825;
    const defaultLng = 120.8663;
    const locationMaps = {};
    const locationMarkers = {};
    const locationMapInitialized = {};

    const savedLocationsData = window.savedLocations || {
        location1: '',
        location2: '',
        location3: ''
    };

    const caviteCities = [
        'Alfonso', 'Amadeo', 'Bacoor', 'Carmona', 'Cavite City', 'Dasmarinas', 'General Mariano Alvarez',
        'General Trias', 'Imus', 'Indang', 'Kawit', 'Magallanes', 'Maragondon', 'Mendez', 'Naic',
        'Noveleta', 'Rosario', 'Silang', 'Tagaytay', 'Tanza', 'Ternate', 'Trece Martires', 'GMA'
    ];

    function normalizeText(value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function isCaviteCity(cityValue) {
        const normalizedCity = normalizeText(cityValue);
        return caviteCities.some(function(city) {
            return normalizeText(city) === normalizedCity;
        });
    }

    function isCaviteAddress(displayName, addressParts) {
        const address = addressParts || {};
        const haystack = [
            displayName || '',
            address.state || '',
            address.province || '',
            address.county || '',
            address.region || '',
            address.city || '',
            address.town || '',
            address.municipality || ''
        ].join(' ').toLowerCase();

        if (haystack.includes('cavite')) {
            return true;
        }

        return caviteCities.some(function(city) {
            return haystack.includes(normalizeText(city));
        });
    }

    function ensureCaviteCityOptions(locationNum) {
        const citySelect = document.getElementById('location' + locationNum + '_city');
        if (!citySelect) {
            return;
        }

        caviteCities.forEach(function(city) {
            const hasOption = Array.from(citySelect.options).some(function(opt) {
                return normalizeText(opt.value) === normalizeText(city);
            });
            if (!hasOption) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            }
        });
    }

    function restrictCitySelectToCavite(locationNum) {
        const citySelect = document.getElementById('location' + locationNum + '_city');
        if (!citySelect) {
            return;
        }

        Array.from(citySelect.options).forEach(function(option) {
            if (!option.value) {
                return;
            }

            const isAllowed = isCaviteCity(option.value);
            option.disabled = !isAllowed;
            option.hidden = !isAllowed;
        });
    }

    function getLocationElements(locationNum) {
        return {
            searchInput: document.getElementById('location' + locationNum + '_search'),
            searchBtn: document.getElementById('location' + locationNum + '_search_btn'),
            geolocateBtn: document.getElementById('location' + locationNum + '_geolocate_btn'),
            searchResults: document.getElementById('location' + locationNum + '_search_results'),
            addressText: document.getElementById('location' + locationNum + '_selected_address_text'),
            fullAddress: document.getElementById('location' + locationNum + '_full_address'),
            lat: document.getElementById('location' + locationNum + '_lat'),
            lng: document.getElementById('location' + locationNum + '_lng'),
            city: document.getElementById('location' + locationNum + '_city'),
            barangay: document.getElementById('location' + locationNum + '_barangay'),
            street: document.getElementById('location' + locationNum + '_street'),
            mapContainer: document.getElementById('location' + locationNum + '_map')
        };
    }

    function parseMapAddressParts(displayName, addressParts) {
        const cityCandidates = [
            addressParts.city,
            addressParts.town,
            addressParts.municipality,
            addressParts.village,
            addressParts.county
        ].filter(Boolean);

        const barangayCandidates = [
            addressParts.suburb,
            addressParts.neighbourhood,
            addressParts.quarter,
            addressParts.hamlet
        ].filter(Boolean);

        const streetCandidates = [
            addressParts.road,
            addressParts.house_number,
            addressParts.building,
            addressParts.amenity
        ].filter(Boolean);

        return {
            city: cityCandidates[0] || '',
            barangay: barangayCandidates[0] || '',
            street: streetCandidates.join(' ').trim() || displayName
        };
    }

    function setLocationFromMap(locationNum, lat, lng, displayName, addressParts) {
        const elements = getLocationElements(locationNum);
        if (!elements.fullAddress || !elements.lat || !elements.lng || !elements.addressText) {
            return;
        }

        elements.lat.value = String(lat);
        elements.lng.value = String(lng);
        elements.fullAddress.value = displayName || '';
        elements.addressText.textContent = displayName
            ? ('Selected exact address: ' + displayName)
            : 'Move the pin or search an address to set exact delivery point.';

        if (displayName && elements.searchInput) {
            elements.searchInput.value = displayName;
        }

        const parsed = parseMapAddressParts(displayName || '', addressParts || {});

        if (elements.city && parsed.city) {
            for (let i = 0; i < elements.city.options.length; i++) {
                if (normalizeText(elements.city.options[i].value) === normalizeText(parsed.city)) {
                    elements.city.value = elements.city.options[i].value;
                    break;
                }
            }
        }

        if (elements.barangay && parsed.barangay) {
            elements.barangay.value = parsed.barangay;
        }

        if (elements.street && parsed.street) {
            elements.street.value = parsed.street;
        }
    }

    function reverseGeocode(locationNum, lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng))
            .then(function(response) { return response.json(); })
            .then(function(data) {
                const displayName = data && data.display_name ? data.display_name : '';
                const address = data && data.address ? data.address : {};
                if (!isCaviteAddress(displayName, address)) {
                    alert('Only addresses within Cavite are allowed.');
                    return;
                }
                setLocationFromMap(locationNum, lat, lng, displayName, address);
            })
            .catch(function() {
                setLocationFromMap(locationNum, lat, lng, 'Pinned location (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')', {});
            });
    }

    function initLocationMap(locationNum) {
        const elements = getLocationElements(locationNum);
        if (!window.L || !elements.mapContainer) {
            return;
        }

        if (locationMapInitialized[locationNum]) {
            setTimeout(function() {
                locationMaps[locationNum].invalidateSize();
            }, 100);
            return;
        }

        locationMaps[locationNum] = L.map('location' + locationNum + '_map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(locationMaps[locationNum]);

        locationMarkers[locationNum] = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(locationMaps[locationNum]);
        locationMarkers[locationNum].on('dragend', function(e) {
            const markerPos = e.target.getLatLng();
            reverseGeocode(locationNum, markerPos.lat, markerPos.lng);
        });

        locationMaps[locationNum].on('click', function(e) {
            locationMarkers[locationNum].setLatLng(e.latlng);
            reverseGeocode(locationNum, e.latlng.lat, e.latlng.lng);
        });

        const savedAddress = savedLocationsData['location' + locationNum] || '';
        if (savedAddress && elements.fullAddress && elements.addressText && elements.searchInput) {
            elements.fullAddress.value = savedAddress;
            elements.addressText.textContent = 'Current saved address: ' + savedAddress;
            elements.searchInput.value = savedAddress;
        }

        locationMapInitialized[locationNum] = true;
        setTimeout(function() {
            locationMaps[locationNum].invalidateSize();
        }, 120);
    }

    function renderSearchResults(locationNum, items) {
        const elements = getLocationElements(locationNum);
        if (!elements.searchResults) {
            return;
        }

        elements.searchResults.innerHTML = '';
        if (!items || !items.length) {
            elements.searchResults.style.display = 'none';
            return;
        }

        items.forEach(function(item) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.textContent = item.display_name;
            button.addEventListener('click', function() {
                if (!isCaviteAddress(item.display_name, item.address || {})) {
                    alert('Only addresses within Cavite are allowed.');
                    return;
                }
                const lat = parseFloat(item.lat);
                const lng = parseFloat(item.lon);
                if (locationMaps[locationNum] && locationMarkers[locationNum]) {
                    locationMaps[locationNum].setView([lat, lng], 16);
                    locationMarkers[locationNum].setLatLng([lat, lng]);
                }
                setLocationFromMap(locationNum, lat, lng, item.display_name, item.address || {});
                elements.searchResults.style.display = 'none';
            });
            elements.searchResults.appendChild(button);
        });

        elements.searchResults.style.display = 'block';
    }

    function searchAddress(locationNum) {
        const elements = getLocationElements(locationNum);
        const query = elements.searchInput ? elements.searchInput.value.trim() : '';
        if (!query) {
            renderSearchResults(locationNum, []);
            return;
        }

        const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=5&countrycodes=ph&q=' + encodeURIComponent(query);
        fetch(url)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                renderSearchResults(locationNum, Array.isArray(data) ? data : []);
            })
            .catch(function() {
                renderSearchResults(locationNum, []);
            });
    }

    function setupLocationControls(locationNum) {
        const elements = getLocationElements(locationNum);
        if (elements.searchBtn) {
            elements.searchBtn.addEventListener('click', function() {
                searchAddress(locationNum);
            });
        }

        if (elements.searchInput) {
            elements.searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchAddress(locationNum);
                }
            });
        }

        if (!elements.geolocateBtn) {
            return;
        }

        elements.geolocateBtn.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }

            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                if (locationMaps[locationNum] && locationMarkers[locationNum]) {
                    locationMaps[locationNum].setView([lat, lng], 16);
                    locationMarkers[locationNum].setLatLng([lat, lng]);
                }
                reverseGeocode(locationNum, lat, lng);
            }, function() {
                alert('Unable to fetch your current location. Please allow location permission.');
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        });
    }

    [1, 2, 3].forEach(ensureCaviteCityOptions);
    [1, 2, 3].forEach(setupLocationControls);
    [1, 2, 3].forEach(restrictCitySelectToCavite);

    function showLocation2() {
        if (!location2Section) {
            return;
        }
        location2Section.style.display = 'block';
        if (showLocation2Btn) {
            showLocation2Btn.style.display = 'none';
        }
        initLocationMap(2);
    }

    function showLocation3() {
        if (!location3Section) {
            return;
        }
        location3Section.style.display = 'block';
        if (showLocation3Btn) {
            showLocation3Btn.style.display = 'none';
        }
        initLocationMap(3);
    }

    if (showLocation2Btn) {
        showLocation2Btn.addEventListener('click', showLocation2);
    }

    if (showLocation3Btn) {
        showLocation3Btn.addEventListener('click', showLocation3);
    }

    if (addressModal) {
        addressModal.addEventListener('shown.bs.modal', function() {
            initLocationMap(1);
            [1, 2, 3].forEach(ensureCaviteCityOptions);
            [1, 2, 3].forEach(restrictCitySelectToCavite);
            if ((savedLocationsData.location2 || '').trim() !== '') {
                showLocation2();
            }
            if ((savedLocationsData.location3 || '').trim() !== '') {
                showLocation2();
                showLocation3();
            }
        });
    }

    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            const location1FullAddress = (document.getElementById('location1_full_address') || {}).value || '';
            const city = (document.getElementById('location1_city') || {}).value || '';
            const street = (document.getElementById('location1_street') || {}).value || '';

            if (!location1FullAddress.trim() && (!city.trim() || !street.trim())) {
                e.preventDefault();
                alert('Please select an exact location on the map or complete City and Street for Location 1.');
                return;
            }

            if (city.trim() && !isCaviteCity(city.trim())) {
                e.preventDefault();
                alert('Location 1 must be within Cavite only.');
                return;
            }

            const location2City = (document.getElementById('location2_city') || {}).value || '';
            const location3City = (document.getElementById('location3_city') || {}).value || '';

            if (location2City.trim() && !isCaviteCity(location2City.trim())) {
                e.preventDefault();
                alert('Location 2 must be within Cavite only.');
                return;
            }

            if (location3City.trim() && !isCaviteCity(location3City.trim())) {
                e.preventDefault();
                alert('Location 3 must be within Cavite only.');
            }
        });
    }
});
</script>
</body>
</html>
<?php

    include 'connection.php';
    session_start();
    if(isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $query = "SELECT * FROM mrb_users WHERE user_id = $user_id";

        $result = mysqli_query($conn, $query);
        if(mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
        } else {
            echo "User not found.";
            exit;
        }
    }else{
        echo "
        <script>
            alert('Please log in to view your orders.');
            window.location.href = 'mrbloginpage.php';
        </script>
        ";
        exit;
    }

    // Mark pending orders as seen when user navigates to pending orders filter
    if(isset($_GET['order_sort']) && $_GET['order_sort'] == 'Pending') {
        $update_seen_query = "UPDATE mrb_orders SET seen_byuser = 'true' WHERE user_id = '$user_id' AND order_status = 'pending' AND seen_byuser = 'false'";
        mysqli_query($conn, $update_seen_query);
    }

    if(isset($_GET['order_sort']) && $_GET['order_sort'] == 'Confirmed') {
        $update_seen_query = "UPDATE mrb_orders SET seen_byuser = 'true' WHERE user_id = '$user_id' AND order_status IN ('confirmed', 'packed', 'picked up') AND seen_byuser = 'false'";
        mysqli_query($conn, $update_seen_query);
    }

    if(isset($_GET['order_sort']) && $_GET['order_sort'] == 'Delivered') {
        $update_seen_query = "UPDATE mrb_orders SET seen_byuser = 'true' WHERE user_id = '$user_id' AND order_status = 'delivered' AND seen_byuser = 'false'";
        mysqli_query($conn, $update_seen_query);
    }

    // Check for cancel message and show toast via JavaScript
    $showCancelToast = false;
    if(isset($_GET['message']) && $_GET['message'] == 'Order cancelled successfully') {
        $showCancelToast = true;
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
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="usersetting.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .product-name-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        
        .tooltip {
            z-index: 1050;
        }

        /* Star Rating Styles */
        .rating {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 5px;
        }

        .rating i {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .rating i:hover {
            color: #ffc107;
        }

        .rating i.selected {
            color: #ffc107;
        }

        .rating i.filled {
            color: #ffc107;
        }

        /* Hover effect for all stars up to the hovered one */
        .rating:hover i {
            color: #ddd;
        }

        .rating i:hover,
        .rating i:hover ~ i {
            color: #ddd;
        }

        .rating i:hover,
        .rating i.hover {
            color: #ffc107;
        }

        /* Show rating value */
        .rating-value {
            margin-left: 10px;
            font-weight: bold;
            color: #666;
        }

        .order-card .card {
            border-radius: 14px;
        }

        .compact-order-body {
            padding: 14px;
        }

        .order-topline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }

        .order-product-title {
            font-size: 0.78rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .order-product-name {
            font-weight: 600;
            margin: 0;
            max-width: 360px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .order-action-note {
            text-align: right;
            min-width: 170px;
        }

        .order-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(130px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .metric-chip {
            background: #f8f9fa;
            border: 1px solid #eceff3;
            border-radius: 10px;
            padding: 8px 10px;
            line-height: 1.2;
        }

        .metric-chip .metric-label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .metric-chip .metric-value {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: #212529;
        }

        .metric-chip .metric-sub {
            display: block;
            font-size: 0.76rem;
            color: #6c757d;
            margin-top: 2px;
        }

        .rider-strip {
            border-top: 1px dashed #dee2e6;
            padding-top: 8px;
            margin-top: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .rider-strip .rider-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            margin-right: 6px;
        }

        @media (max-width: 768px) {
            .order-topline {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-action-note {
                text-align: left;
                min-width: auto;
                width: 100%;
            }

            .order-metrics {
                grid-template-columns: 1fr;
            }

            .rider-strip {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    
</head>
<body>
<div class="container">
<div class="row gutters d-flex">
    <div class="row my-3 px-4">
        <button onclick="window.location.href='landpage.php';" class="btn red-bg text-light" style="width: 200px;">Back</button>
    </div>
    <div class="col-lg-4">
    <a href="usersetting.php?user_id=<?php echo $row['user_id']?>" class="card-anchor" style="color:black; text-decoration: none;" class="card-anchor">
          <div class="card">
			<div class="card-body">
				<div class="account-settings">
					<div class="user-profile">
                        <i class='bx bxs-user-circle bx-lg red-text'></i>
						<h5 class="user-name">My account</h5>
					</div>
				</div>
			</div>
		</div>
    </a>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="reviewToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class='bx bx-check-circle me-2' id="toastIcon"></i>
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Message here
            </div>
        </div>
    </div>

        <div class="card" style="min-height: 348px;">
                <div class="card-body">
                    <div class="account-settings">
                        <div class="user-profile">
                            <div class="account-settings text-center d-flex flex-column align-items-center justify-content-center">
                                <i class='bx bxs-cart-alt bx-lg red-text'></i>
                                <h5 class="user-name">My Orders</h5>
                            </div>
                        </div>
                        <div class="order-sorting">
                            <?php
                                $count_query = "SELECT COUNT(*) AS unseen_all FROM mrb_orders WHERE user_id = '$user_id' AND seen_byuser = 'false'";
                                $count_result = mysqli_query($conn, $count_query);
                                $unseen_count = mysqli_fetch_assoc($count_result);

                                // Get unseen counts for each status instead of total counts
                                $pending_unseen_query = "SELECT COUNT(*) AS pending_unseen FROM mrb_orders WHERE user_id = '$user_id' AND order_status = 'pending' AND seen_byuser = 'false'";
                                $pending_unseen_result = mysqli_query($conn, $pending_unseen_query);
                                $pending_unseen_count = mysqli_fetch_assoc($pending_unseen_result)['pending_unseen'];

$confirmed_unseen_query = "SELECT COUNT(*) AS confirmed_unseen FROM mrb_orders WHERE user_id = '$user_id' AND order_status IN ('confirmed', 'packed', 'picked up') AND seen_byuser = 'false'";
                                $confirmed_unseen_result = mysqli_query($conn, $confirmed_unseen_query);
                                $confirmed_unseen_count = mysqli_fetch_assoc($confirmed_unseen_result)['confirmed_unseen'];

                                $delivered_unseen_query = "SELECT COUNT(*) AS delivered_unseen FROM mrb_orders WHERE user_id = '$user_id' AND order_status = 'delivered' AND seen_byuser = 'false'";
                                $delivered_unseen_result = mysqli_query($conn, $delivered_unseen_query);
                                $delivered_unseen_count = mysqli_fetch_assoc($delivered_unseen_result)['delivered_unseen'];

                                $total_query = "SELECT COUNT(*) AS total_count FROM mrb_orders WHERE user_id = '$user_id'";
                                $total_result = mysqli_query($conn, $total_query);
                                $total_count = mysqli_fetch_assoc($total_result)['total_count'];
                            ?>
                            <div class="mt-3 px-3">
                                <div class="btn-group d-flex flex-wrap gap-2" role="group">
                                    <a href="userorders.php?order_sort=All" class="btn btn-sm <?php echo (!isset($_GET['order_sort']) || $_GET['order_sort'] == 'All') ? 'btn-primary' : 'btn-outline-primary'; ?> position-relative">
                                        All Orders
                                        <?php if($unseen_count['unseen_all'] > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?php echo $unseen_count['unseen_all']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary ms-1"><?php echo $total_count; ?></span>
                                    </a>
                                    
                                    <a href="userorders.php?order_sort=Pending" class="btn btn-sm <?php echo (isset($_GET['order_sort']) && $_GET['order_sort'] == 'Pending') ? 'btn-warning text-dark' : 'btn-outline-warning'; ?> position-relative">
                                        Pending
                                        <?php if($pending_unseen_count > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?php echo $pending_unseen_count; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <a href="userorders.php?order_sort=Confirmed" class="btn btn-sm <?php echo (isset($_GET['order_sort']) && $_GET['order_sort'] == 'Confirmed') ? 'btn-info text-dark' : 'btn-outline-info'; ?> position-relative">
                                        Confirmed
                                        <?php if($confirmed_unseen_count > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?php echo $confirmed_unseen_count; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                    
                                    <a href="userorders.php?order_sort=Delivered" class="btn btn-sm <?php echo (isset($_GET['order_sort']) && $_GET['order_sort'] == 'Delivered') ? 'btn-success' : 'btn-outline-success'; ?> position-relative">
                                        Delivered
                                        <?php if($delivered_unseen_count > 0): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                                <?php echo $delivered_unseen_count; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        

	</div>
	<div class="col-lg">
		<div class="card h-100">
			<div class="card-body" style="max-height: 650px; overflow-y: auto;">
                <div class="orders-container">
                    <?php
                        $user_id = $_SESSION['user_id'];

                        $order_filter = '';
                        if(isset($_GET['order_sort']) && $_GET['order_sort']=='Delivered'){
                            $order_filter = " AND mrb_orders.order_status = 'delivered'";
                        } else if(isset($_GET['order_sort']) && $_GET['order_sort']=='Confirmed'){
                            $order_filter = " AND mrb_orders.order_status IN ('confirmed', 'packed', 'picked up')";
                        } else if(isset($_GET['order_sort']) && $_GET['order_sort']=='Pending') {
                            $order_filter = " AND mrb_orders.order_status = 'pending'";
                        }

                        $query = "SELECT mrb_orders.order_id,
                                         mrb_orders.order_dateordered,
                                         mrb_fireex.prod_name,
                                         mrb_orders.order_quantity,
                                         mrb_orders.preferred_weight,
                                         mrb_orders.order_status,
                                         mrb_fireex.prod_newprice,
                                         (
                                             SELECT CONCAT_WS(' ', ru.user_name, ru.user_mname, ru.user_lname)
                                             FROM mrb_users ru
                                             WHERE ru.shop_id = mrb_orders.shop_id
                                               AND ru.user_type = 'rider'
                                             ORDER BY ru.user_id ASC
                                             LIMIT 1
                                         ) AS rider_name,
                                         (
                                             SELECT ru.user_contactnum
                                             FROM mrb_users ru
                                             WHERE ru.shop_id = mrb_orders.shop_id
                                               AND ru.user_type = 'rider'
                                             ORDER BY ru.user_id ASC
                                             LIMIT 1
                                         ) AS rider_contact
                                  FROM mrb_orders
                                  JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
                                  WHERE mrb_orders.user_id = '$user_id'{$order_filter}
                                  ORDER BY mrb_orders.order_dateordered DESC";
                        
                        $result = mysqli_query($conn, $query);
                        if(mysqli_num_rows($result) > 0) {
                            while($order = mysqli_fetch_assoc($result)) {
                                $order_id = $order['order_id'];
                                $date_ordered = date('F j, Y', strtotime($order['order_dateordered']));
                                $status = strtolower($order['order_status']);
                                $unit_price = isset($order['prod_newprice']) ? (float)$order['prod_newprice'] : 0;
                                $kilos_value = isset($order['preferred_weight']) && is_numeric($order['preferred_weight']) && (float)$order['preferred_weight'] > 0
                                    ? (float)$order['preferred_weight']
                                    : (float)$order['order_quantity'];
                                $kilos_display = rtrim(rtrim(number_format($kilos_value, 2, '.', ''), '0'), '.') . ' kg';
                                $estimated_total = $unit_price * $kilos_value;
                                $rider_name = trim((string)($order['rider_name'] ?? ''));
                                $rider_contact = trim((string)($order['rider_contact'] ?? ''));
                                $rider_display = $rider_name !== '' ? htmlspecialchars($rider_name) : 'Not assigned yet';
                                $rider_contact_display = $rider_contact !== '' ? htmlspecialchars($rider_contact) : 'No contact available';
                                
                                // Determine status badge class and display text
                                $status_class = '';
                                $status_text = '';
                                switch($status) {
                                    case 'pending':
                                        $status_class = 'bg-warning text-dark';
                                        $status_text = 'Pending';
                                        break;
                                    case 'confirmed':
                                        $status_class = 'bg-info text-dark';
                                        $status_text = 'Confirmed';
                                        break;
                                    case 'packed':
                                        $status_class = 'bg-info text-dark';
                                        $status_text = 'Order is Packed and ready to ship';
                                        break;
                                    case 'picked up':
                                        $status_class = 'bg-info text-dark';
                                        $status_text = 'Order is picked up by the courier';
                                        break;
                                    case 'delivered':
                                        $status_class = 'bg-success';
                                        $status_text = 'Delivered';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-secondary';
                                        $status_text = 'Cancelled';
                                        break;
                                    default:
                                        $status_class = 'bg-light text-dark';
                                        $status_text = ucfirst($order['order_status']);
                                }

                                $action_html = "<small class='text-muted'>No action available</small>";
                                $review_modal_html = '';

                                if($status == 'pending') {
                                    $action_html = "<small class='text-muted'>You can cancel this order below.</small>";
                                } else if($status == 'confirmed') {
                                    $action_html = "<small class='text-muted'>Order already confirmed.</small>";
                                } else if($status == 'delivered') {
                                    $existing_review_query = "SELECT comment_id FROM mrb_comments WHERE order_id = '$order_id' AND user_id = '$user_id'";
                                    $existing_result = mysqli_query($conn, $existing_review_query);

                                    if (mysqli_num_rows($existing_result) > 0) {
                                        $action_html = "<small class='text-muted'>Review already submitted</small>";
                                    } else {
                                        $action_html = "<button type='button'
                                            class='btn btn-primary btn-sm'
                                            data-bs-toggle='modal'
                                            data-bs-target='#reviewModal{$order_id}'
                                            data-bs-tooltip='tooltip'
                                            data-bs-placement='top'
                                            title='Share your experience with this product'>
                                            <i class='bi bi-chat-square-heart'></i><small> Review</small>
                                        </button>";

                                        $review_modal_html = "<div class='modal fade' id='reviewModal{$order_id}' tabindex='-1'>
                                        <div class='modal-dialog'>
                                            <div class='modal-content'>
                                                <div class='modal-header text-white' style='background-color: #C42F01;'>
                                                    <h5 class='modal-title'>Review Order #{$order_id}</h5>
                                                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body'>
                                                    <p><strong>Product:</strong> " . htmlspecialchars($order['prod_name']) . "</p>

                                                    <div class='mb-3'>
                                                        <label class='form-label'
                                                                data-bs-toggle='tooltip'
                                                                title='Rate from 1 (poor) to 5 (excellent)'>
                                                            Rating
                                                        </label>
                                                        <div class='rating' data-rating='0' id='rating{$order_id}'>
                                                            <i class='bi bi-star-fill' data-rating='1'></i>
                                                            <i class='bi bi-star-fill' data-rating='2'></i>
                                                            <i class='bi bi-star-fill' data-rating='3'></i>
                                                            <i class='bi bi-star-fill' data-rating='4'></i>
                                                            <i class='bi bi-star-fill' data-rating='5'></i>
                                                            <span class='rating-value' id='ratingValue{$order_id}'>0/5</span>
                                                        </div>
                                                        <input type='hidden' id='selectedRating{$order_id}' value='0'>
                                                    </div>

                                                    <div class='mb-3'>
                                                        <label class='form-label'
                                                                data-bs-toggle='tooltip'
                                                                title='Share your honest experience to help other customers'>
                                                            Your Review
                                                        </label>
                                                        <textarea class='form-control' id='reviewText{$order_id}' rows='4' required
                                                                placeholder='Tell us about your experience with this product...'></textarea>
                                                    </div>
                                                    <div class='mb-3'>
                                                        <label class='form-label'
                                                                data-bs-toggle='tooltip'
                                                                title='Upload a photo of your product (optional)'>
                                                            <i class='bi bi-camera'></i> Add Photo (Optional)
                                                        </label>
                                                        <input type='file' class='form-control' id='reviewPhoto{$order_id}'
                                                               accept='image/jpeg,image/jpg,image/png,image/webp'
                                                               onchange='previewReviewPhoto(this, {$order_id})'>
                                                        <small class='text-muted'>Max file size: 5MB. Supported formats: JPG, PNG, WEBP</small>
                                                        <div id='photoPreview{$order_id}' class='mt-2' style='display:none;'>
                                                            <img id='previewImage{$order_id}' src='' alt='Preview'
                                                                 style='max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #ddd;'>
                                                            <button type='button' class='btn btn-sm btn-danger ms-2' onclick='removeReviewPhoto({$order_id})'>
                                                                <i class='bi bi-x'></i> Remove
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class='modal-footer'>
                                                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                                                    <button type='button' style='background-color: #C42F01;'
                                                            class='btn text-white'
                                                            id='submitReviewBtn{$order_id}'
                                                            data-bs-toggle='tooltip'
                                                            title='Submit your review for other customers to see'
                                                            onclick='submitReview({$order_id}, this)'>
                                                            Submit Review
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>";
                                    }
                                }
                                
                                echo "
                                <div class='order-card mb-3'>
                                    <div class='card border-0 shadow-sm'>
                                        <div class='card-header border-0 d-flex text-light justify-content-between align-items-center' style='background-color: #C42F01;'>
                                            <div style='color: white;'>
                                                <h6 class='mb-0 text-light' style='color: white;'>Order #{$order_id}</h6>
                                                <small class=''>{$date_ordered}</small>
                                            </div>
                                            <span class='badge {$status_class}'>" . ucfirst($order['order_status']) . "</span>
                                        </div>
                                        <div class='card-body compact-order-body'>
                                            <div class='order-topline'>
                                                <div>
                                                    <div class='order-product-title'>Product</div>
                                                    <p class='order-product-name' title='" . htmlspecialchars($order['prod_name']) . "'>" . htmlspecialchars($order['prod_name']) . "</p>
                                                </div>
                                                <div class='order-action-note'>{$action_html}</div>
                                            </div>

                                            <div class='order-metrics'>
                                                <div class='metric-chip'>
                                                    <span class='metric-label'>Kilos</span>
                                                    <span class='metric-value'>" . htmlspecialchars($kilos_display) . "</span>
                                                </div>
                                                <div class='metric-chip'>
                                                    <span class='metric-label'>Price Per Kg</span>
                                                    <span class='metric-value'>PHP " . number_format($unit_price, 2) . "</span>
                                                </div>
                                                <div class='metric-chip'>
                                                    <span class='metric-label'>Estimated Total</span>
                                                    <span class='metric-value'>PHP " . number_format($estimated_total, 2) . "</span>
                                                    <span class='metric-sub'>Based on preferred kilos</span>
                                                </div>
                                            </div>

                                            <div class='rider-strip'>
                                                <div>
                                                    <span class='rider-title'>Rider</span>
                                                    <strong>{$rider_display}</strong>
                                                </div>
                                                <div>
                                                    <span class='rider-title'>Contact</span>
                                                    <span>{$rider_contact_display}</span>
                                                </div>
                                            </div>

                                            " . ($status == 'pending' ? "
                                            <div class='d-flex justify-content-end mt-3'>
                                                <button class='btn btn-outline-danger btn-sm px-3' onclick='confirmCancelOrder({$order_id})' data-bs-toggle='tooltip' data-bs-placement='top' title='Cancel order'>
                                                    <i class='bi bi-x-circle-fill me-1'></i>Cancel Order
                                                </button>
                                            </div>
                                            " : "") . "
                                        </div>
                                    </div>
                                </div>";

                                echo $review_modal_html;
                            }
                        } else {
                            echo "
                            <div class='text-center py-5'>
                                <i class='bx bx-package bx-lg text-muted mb-3'></i>
                                <h5 class='text-muted'>No orders found</h5>
                                <p class='text-muted'>You haven't placed any orders yet.</p>
                                <a href='landpage.php' class='btn btn-primary'>Start Shopping</a>
                            </div>";
                        }
                    ?>
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
                    showToast('Missing Information', 'Please enter your current password', 'error');
                    return;
                }
                
                if (!newPassword.value) {
                    e.preventDefault();
                    showToast('Missing Information', 'Please enter a new password', 'error');
                    return;
                }
                
                if (!confirmPassword.value) {
                    e.preventDefault();
                    showToast('Missing Information', 'Please confirm your new password', 'error');
                    return;
                }
                
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    showToast('Password Mismatch', 'New passwords do not match', 'error');
                    return;
                }
                
                if (newPassword.value.length < 6) {
                    e.preventDefault();
                    showToast('Invalid Password', 'Password must be at least 6 characters', 'error');
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
<script>
    function initializeStarRating() {
        const ratingContainers = document.querySelectorAll('.rating');
        
        ratingContainers.forEach(container => {
            const stars = container.querySelectorAll('i[data-rating]');
            const ratingValue = container.nextElementSibling?.querySelector('.rating-value') || 
                               container.querySelector('.rating-value');
            const hiddenInput = container.parentNode.querySelector('input[type="hidden"]');
            
            stars.forEach((star, index) => {
                // Click event - set rating
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    setRating(container, rating, ratingValue, hiddenInput);
                });
                
                // Hover effect - preview rating
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    previewRating(container, rating);
                });
            });
            
            // Reset to selected rating when mouse leaves
            container.addEventListener('mouseleave', function() {
                const selectedRating = parseInt(container.getAttribute('data-rating')) || 0;
                setRating(container, selectedRating, ratingValue, hiddenInput, false);
            });
        });
    }

    function setRating(container, rating, ratingValueElement, hiddenInput, updateValue = true) {
        const stars = container.querySelectorAll('i[data-rating]');
        
        // Update container data attribute
        if (updateValue) {
            container.setAttribute('data-rating', rating);
        }
        
        // Update hidden input
        if (hiddenInput && updateValue) {
            hiddenInput.value = rating;
        }
        
        // Update rating value display
        if (ratingValueElement && updateValue) {
            ratingValueElement.textContent = `${rating}/5`;
            
            // Add color coding for rating value
            ratingValueElement.className = 'rating-value';
            if (rating >= 4) {
                ratingValueElement.style.color = '#28a745'; // Green for good ratings
            } else if (rating >= 2) {
                ratingValueElement.style.color = '#ffc107'; // Yellow for average ratings
            } else if (rating > 0) {
                ratingValueElement.style.color = '#dc3545'; // Red for poor ratings
            } else {
                ratingValueElement.style.color = '#666'; // Gray for no rating
            }
        }
        
        // Update star colors
        stars.forEach((star, index) => {
            star.classList.remove('selected', 'filled');
            if (index < rating) {
                star.classList.add('selected');
            }
        });
    }

    function previewRating(container, rating) {
        const stars = container.querySelectorAll('i[data-rating]');
        
        stars.forEach((star, index) => {
            star.classList.remove('hover');
            if (index < rating) {
                star.classList.add('hover');
            }
        });
    }

    // Photo preview function
    window.previewReviewPhoto = function(input, orderId) {
        const file = input.files[0];
        if (file) {
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File Too Large', 'File size must be less than 5MB', 'error');
                input.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showToast('Invalid File Type', 'Only JPG, PNG, and WEBP images are allowed', 'error');
                input.value = '';
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(`previewImage${orderId}`).src = e.target.result;
                document.getElementById(`photoPreview${orderId}`).style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    };
    
    // Remove photo function
    window.removeReviewPhoto = function(orderId) {
        document.getElementById(`reviewPhoto${orderId}`).value = '';
        document.getElementById(`photoPreview${orderId}`).style.display = 'none';
        document.getElementById(`previewImage${orderId}`).src = '';
    };
    
    // Submit review function
    window.submitReview = function(orderId, buttonElement) {
        const rating = document.getElementById(`selectedRating${orderId}`).value;
        const reviewText = document.getElementById(`reviewText${orderId}`).value.trim();
        const photoInput = document.getElementById(`reviewPhoto${orderId}`);
        
        // Validation
        if (rating === '0' || rating === '') {
            showToast('Missing Rating', 'Please select a rating before submitting your review.', 'error');
            return;
        }
        
        if (reviewText === '') {
            showToast('Missing Review', 'Please write a review before submitting.', 'error');
            return;
        }
        
        if (reviewText.length < 10) {
            showToast('Review Too Short', 'Please write a more detailed review (at least 10 characters).', 'error');
            return;
        }
        
        // Show loading state
        const submitBtn = buttonElement;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Submitting...';
        submitBtn.disabled = true;
        
        // Create FormData to send to PHP
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('rating', rating);
        formData.append('review_text', reviewText);
        formData.append('action', 'submit_review');
        
        // Add photo if selected
        if (photoInput.files.length > 0) {
            formData.append('review_photo', photoInput.files[0]);
        }
        
        // Send data to PHP script
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Get the response text first to debug
            return response.text().then(text => {
                console.log('Raw server response:', text);
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Server returned invalid JSON. Response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            if (data.success) {
                showToast('Success!', 'Review submitted successfully!', 'success');
                
                // Reset form
                document.getElementById(`selectedRating${orderId}`).value = '0';
                document.getElementById(`reviewText${orderId}`).value = '';
                
                // Clear photo if any
                const photoInput = document.getElementById(`reviewPhoto${orderId}`);
                if (photoInput) {
                    photoInput.value = '';
                    const photoPreview = document.getElementById(`photoPreview${orderId}`);
                    if (photoPreview) photoPreview.style.display = 'none';
                }
                
                const ratingContainer = document.getElementById(`rating${orderId}`);
                setRating(ratingContainer, 0, document.getElementById(`ratingValue${orderId}`), 
                         document.getElementById(`selectedRating${orderId}`));
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById(`reviewModal${orderId}`));
                modal.hide();
                
                // Optionally reload the page to reflect changes
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast('Error', 'Error submitting review: ' + (data.message || 'Unknown error'), 'error');
            }
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Failed to submit review. Please try again.', 'error');
            
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    };

    // Function to show toast notifications
    function showToast(title, message, type) {
        const toastEl = document.getElementById('reviewToast');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        const toastHeader = toastEl.querySelector('.toast-header');
        
        // Set title and message
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        
        // Set icon and color based on type
        if (type === 'success') {
            toastIcon.className = 'bx bx-check-circle me-2 text-success';
            toastHeader.style.backgroundColor = '#d1e7dd';
            toastHeader.style.color = '#0f5132';
        } else {
            toastIcon.className = 'bx bx-error-circle me-2 text-danger';
            toastHeader.style.backgroundColor = '#f8d7da';
            toastHeader.style.color = '#842029';
        }
        
        // Show toast
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }

    // Function to confirm and cancel order
    function confirmCancelOrder(orderId) {
        // Show confirmation using Bootstrap modal or toast
        if (confirm('Are you sure you want to cancel this order?')) {
            // Send cancel request
            fetch('cancel_order.php?order_id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    showToast('Success', 'Order cancelled successfully', 'success');
                    // Reload page after short delay
                    setTimeout(() => {
                        window.location.href = 'userorders.php?order_sort=All';
                    }, 1500);
                })
                .catch(error => {
                    showToast('Error', 'Failed to cancel order. Please try again.', 'error');
                });
        }
    }

    // Show cancel toast if redirected after cancellation
    <?php if($showCancelToast): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showToast('Success', 'Order cancelled successfully', 'success');
        // Clean URL
        window.history.replaceState({}, document.title, 'userorders.php?order_sort=All');
    });
    <?php endif; ?>

    // Initialize star ratings after DOM is loaded
    initializeStarRating();
    
    // Re-initialize star ratings when modals are shown
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function() {
            initializeStarRating();
        });
    });

</script>
</body>
</html>
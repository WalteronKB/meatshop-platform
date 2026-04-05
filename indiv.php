<?php

    session_start();
?>
<?php

    include 'connection.php';
    
    // Fetch user data at the top so it's available throughout the page
    if(isset($_SESSION['user_id'])) {
        $user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
        $user_query = "SELECT user_id, user_name, user_location, user_location2, user_location3 FROM mrb_users WHERE user_id = '$user_id'";
        $user_result = mysqli_query($conn, $user_query);
        
        if($user_result && mysqli_num_rows($user_result) > 0){
            $user = mysqli_fetch_assoc($user_result);
            
            // Debug: Check what keys exist in the array
            echo "<!-- Debug: Keys in user array: " . implode(', ', array_keys($user)) . " -->";
            
            $_SESSION['user_location'] = $user['user_location'];
        } else {
            // Set default values for guest or missing user
            $user = array(
                'user_name' => 'Guest User',
                'user_location' => 'Carmona, Cavite',
                'user_location2' => '',
                'user_location3' => ''
            );
            $_SESSION['user_location'] = 'Carmona, Cavite';
        }
    } else {
        // Set default values for non-logged in users
        $user = array(
            'user_name' => 'Guest User',
            'user_location' => isset($_SESSION['user_location']) ? $_SESSION['user_location'] : 'Carmona, Cavite',
            'user_location2' => '',
            'user_location3' => ''
        );
        if(!isset($_SESSION['user_location'])) {
            $_SESSION['user_location'] = 'Carmona, Cavite';
        }
    }
    
    if(isset($_GET['prod_id'])){
        $prod_id = $_GET['prod_id'];
        $query = "SELECT * FROM mrb_fireex WHERE prod_id = '$prod_id'";
        $result = mysqli_query($conn, $query);
        
        if($result && mysqli_num_rows($result) > 0){
            $product = mysqli_fetch_assoc($result);
        } else {
            echo "<script>alert('Product not found.');</script>";
            exit;
        }
    } else {
        echo "<script>alert('No product ID provided.');</script>";
        header("Location: landpage.php");
    }

    $product_shop = null;
    if (isset($product['shop_id']) && (int)$product['shop_id'] > 0) {
        $shop_id_lookup = (int)$product['shop_id'];
        $shop_lookup_sql = "SELECT approved_shop_id, store_name, store_logo_path
                            FROM approved_shops
                            WHERE approved_shop_id = ?
                            LIMIT 1";
        $shop_lookup_stmt = mysqli_prepare($conn, $shop_lookup_sql);
        if ($shop_lookup_stmt) {
            mysqli_stmt_bind_param($shop_lookup_stmt, 'i', $shop_id_lookup);
            mysqli_stmt_execute($shop_lookup_stmt);
            $shop_lookup_result = mysqli_stmt_get_result($shop_lookup_stmt);
            if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
                $product_shop = mysqli_fetch_assoc($shop_lookup_result);
            }
            mysqli_stmt_close($shop_lookup_stmt);
        }
    }

    $current_chat_shop_id = 0;
    $current_chat_product_id = isset($product['prod_id']) ? (int)$product['prod_id'] : 0;
    $dynamic_footer_iframe_src = '';
    if (isset($product['shop_id']) && (int)$product['shop_id'] > 0) {
        $current_chat_shop_id = (int)$product['shop_id'];
        $shop_map_sql = "SELECT address_iframe FROM approved_shops WHERE approved_shop_id = ? LIMIT 1";
        $shop_map_stmt = mysqli_prepare($conn, $shop_map_sql);
        if ($shop_map_stmt) {
            mysqli_stmt_bind_param($shop_map_stmt, 'i', $current_chat_shop_id);
            mysqli_stmt_execute($shop_map_stmt);
            $shop_map_result = mysqli_stmt_get_result($shop_map_stmt);
            if ($shop_map_result && mysqli_num_rows($shop_map_result) > 0) {
                $shop_map_row = mysqli_fetch_assoc($shop_map_result);
                $dynamic_footer_iframe_src = trim((string)($shop_map_row['address_iframe'] ?? ''));
                if ($dynamic_footer_iframe_src !== '' && preg_match('/src\\s*=\\s*["\']([^"\']+)["\']/i', $dynamic_footer_iframe_src, $iframe_match)) {
                    $dynamic_footer_iframe_src = $iframe_match[1];
                }
            }
            mysqli_stmt_close($shop_map_stmt);
        }
    }

    $_SESSION['chat_context_shop_id'] = $current_chat_shop_id;
    $_SESSION['chat_context_product_id'] = $current_chat_product_id;

    $paymongo_return = null;
    if (isset($_SESSION['paymongo_return']) && is_array($_SESSION['paymongo_return'])) {
        $paymongo_return = $_SESSION['paymongo_return'];
        unset($_SESSION['paymongo_return']);
    }

    $paymongo_pending_order = null;
    if (
        isset($_SESSION['paymongo_ready_to_place_order']) &&
        $_SESSION['paymongo_ready_to_place_order'] === true &&
        isset($_SESSION['paymongo_pending_order']) &&
        is_array($_SESSION['paymongo_pending_order'])
    ) {
        $pending_product_id = (int)($_SESSION['paymongo_pending_order']['productId'] ?? 0);
        if ($pending_product_id === (int)$prod_id) {
            $paymongo_pending_order = $_SESSION['paymongo_pending_order'];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="mrbstyle.css">
    <style>
        .Login-btn {
            padding: 12px 40px;
            background-color: #6C757D;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .Login-btn:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
        }
        .Login-btn i {
            font-size: 1.1rem;
        }
    </style>
</head>
<body class="d-flex flex-column" style="background-color: #F6F6F6;">
    
    <?php
    
        if(isset($_SESSION['user_id'])) {
            include 'headerinned.php';
        } else {
            include 'headerout.php';
        }
    
    ?>

     <div class="container mt-4">
        <div class="row align-items-center">
          
        <?php
          include 'mininavbar.php';
        ?>
        
    <!-- NAVBARRRRRRRRRRRRRRRRRRRRRRRRRRRRRR -->
    <!-- PRODUCTSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->

   

    <div class="indiv-product-page-container container-fluid justify-content-between d-flex row mt-4">
        
        <div class="col-lg-10 mb-5 px-1">
            <div class="indiv-product-container row px-0 pt-5 pb-3 mb-5">
                <div class="col d-flex flex-column justify-content-center align-items-center">
                    <img src="<?php echo $product['prod_mainpic'] ?>" alt="" style="object-fit:cover;" class="main-img">
                    <div class="other-product-images d-flex flex-row w-100 py-2 justify-content-center">
                        <?php
                        
                        // Display other images if they exist
                        if(!empty($product['prod_pic1'])) {
                            echo '<img src="'.$product['prod_pic1'].'" alt="" class="other-img">';
                        }
                        if(!empty($product['prod_pic2'])) {
                            echo '<img src="'.$product['prod_pic2'].'" alt="" class="other-img">';
                        }
                        if(!empty($product['prod_pic3'])) {
                            echo '<img src="'.$product['prod_pic3'].'" alt="" class="other-img">';
                        }
                        if(!empty($product['prod_pic4'])) {
                            echo '<img src="'.$product['prod_pic4'].'" alt="" class="other-img">';
                        }
                        ?>
                    </div>
                </div>
                <div class="col">
                    <h4 class="indiv-product-title"><?php echo $product['prod_name'] ?></h4>
                    <?php if ($product_shop): ?>
                        <?php
                            $shop_logo = !empty($product_shop['store_logo_path']) ? $product_shop['store_logo_path'] : 'Images/placeholder.png';
                            $shop_name = $product_shop['store_name'] ?? 'Visit Shop';
                            $shop_page_id = (int)($product_shop['approved_shop_id'] ?? 0);
                        ?>
                        <a href="shop.php?shop_id=<?php echo $shop_page_id; ?>" class="d-inline-flex align-items-center text-decoration-none mb-2">
                            <img src="<?php echo htmlspecialchars($shop_logo, ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($shop_name, ENT_QUOTES); ?>" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #ececec;">
                            <span class="ms-2 text-dark fw-semibold"><?php echo htmlspecialchars($shop_name); ?></span>
                            <i class="bi bi-box-arrow-up-right ms-2 text-secondary" style="font-size: 0.9rem;"></i>
                        </a>
                    <?php endif; ?>
                    <div class="product-rating mb-3">
                    <?php
                    // Calculate the average rating
                    $rating_query = "SELECT AVG(rating) as avg_rating FROM mrb_comments WHERE product_id = '$prod_id'";
                    $rating_result = mysqli_query($conn, $rating_query);
                    $avg_rating = 0;
                    if($rating_result && mysqli_num_rows($rating_result) > 0){
                        $rating_data = mysqli_fetch_assoc($rating_result);
                        $avg_rating = round($rating_data['avg_rating'], 1);
                    }
                    // Display the rating stars
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
                    $empty_stars = 5 - $full_stars - $half_star;

                    for($i = 0; $i < $full_stars; $i++){
                        echo '<i class="bi bi-star-fill text-primary indiv-product-rating real-rating"></i>';
                    }
                    for($i = 0; $i < $half_star; $i++){
                        echo '<i class="bi bi-star-half text-primary indiv-product-rating real-rating"></i>';
                    }
                    for($i = 0; $i < $empty_stars; $i++){
                        echo '<i class="bi bi-star-fill indiv-product-rating invalid-rating"></i>';
                    }

                    if($avg_rating > 0){
                        echo '<span class="indiv-product-rating-text">('.$avg_rating.')</span>';
                    } else {
                        echo '<span class="indiv-product-rating-text">(No ratings yet)</span>';
                    }


                    ?>
                    </div>
                    <div class="product-indiv-infos px-3">
                        <div class="row">
                            <p class="col-3">Description:</p>
                            <p class="col product-indiv-desc"><?php echo $product['prod_desc'] ?></p>
                        </div>
                        <div class="row">
                            <p class="col-3">Price:</p>
                            <div class="col d-flex flex-row align-items-center justify-content-around">
                                <?php
                                    $old_price_value = isset($product['prod_oldprice']) ? (float)$product['prod_oldprice'] : 0;
                                    $new_price_value = isset($product['prod_newprice']) ? (float)$product['prod_newprice'] : 0;
                                ?>
                                <span class="product-indiv-old-price text-center"><?php
                                    if ($old_price_value > 0 && $old_price_value > $new_price_value) {
                                        echo "₱" . number_format($old_price_value, 2);
                                    }
                                ?></span>
                                <span class="product-indiv-price text-start"><?php echo "₱" . number_format($new_price_value, 2); ?></span>
                            </div>
                        </div>

    <!-- Toast Container for Order Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="orderToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
            <div class="toast-header">
                <i class='bx bx-check-circle me-2 text-success' id="orderToastIcon"></i>
                <strong class="me-auto" id="orderToastTitle">Order Success</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <div id="orderToastMessage">Order placed successfully!</div>
                <div class="mt-3">
                    <button class="btn btn-sm btn-primary w-100" id="orderToastBtn" onclick="dismissAndGoToOrders()">Dismiss and go to orders</button>
                </div>
            </div>
        </div>
    </div>

                        <div class="row">
                            <p class="col-3">Quantity:</p>
                            <div class="col quantity-controls d-flex flex-row align-items-center justify-content-center">
                                <button onclick="sub_quantity()">-</button>
                                <span class="product-indiv-quantity" id="quantity">1</span>
                                <button onclick="add_quantity()">+</button>
                            </div>    
                        </div>
                        <div class="row">
                            <div class="col-3"></div>
                            <?php
                                if (isset($_SESSION["user_id"])){
                                    echo"<div class='col d-flex flex-row align-items-center justify-content-center'>
                                            <button class='BuyNow-btn' data-bs-toggle='modal' data-bs-target='#buynowmodel'>Buy Now</button>
                                        </div>";
                                }else{
                                    echo"<div class='col d-flex flex-row align-items-center justify-content-center'>
                                            <a href='mrbloginpage.php' class='Login-btn'>
                                                <i class='bi bi-box-arrow-in-right me-2'></i>Login to Purchase
                                            </a>
                                        </div>";
                                }
                            ?>
                              
                            
                        </div>
                        
                    </div>
                   
                </div>
            </div>
            <div class="product-reviews-comments w-100 pt-5 pb-3 d-flex flex-column px-4" id="reviews-section">
                <p class="text-start ms-3">REVIEWS & COMMENTS</p>
                <div class="container d-flex flex-row align-items-center my-3 px-5">
                    <span class="me-3" style="font-size: 1rem; font-family: 'DM Sans', sans-serif;">Sort By:</span>
                    <div class="dropdown-hover">
                        <a href="#" class="dropdown-toggle" style="font-size: 1rem; font-family: 'DM Sans', sans-serif;">Newest First</a>
                        <ul class="dropdown-menu dropdown-hover-menu">
                            <li><a class="dropdown-item" href="indiv.html#reviews-section" style="font-size: 0.999rem; font-family: 'DM Sans', sans-serif;">Highest Ratings First</a></li>
                            <li><a class="dropdown-item" href="indiv.html#reviews-section" style="font-size: 0.999rem; font-family: 'DM Sans', sans-serif;">Lowest Ratings First</a></li>
                            <li><a class="dropdown-item" href="indiv.html#reviews-section" style="font-size: 0.999rem; font-family: 'DM Sans', sans-serif;">Oldest First</a></li>
                        </ul>
                    </div>
                </div>
                    <div class="comments-container px-5 mt-3 mb-2">
                    <?php
                        
                            $query = "SELECT * FROM mrb_comments WHERE product_id = '$prod_id' ORDER BY comment_dateadded DESC LIMIT 5";
                            $result = mysqli_query($conn, $query);
                            if($result && mysqli_num_rows($result) > 0){
                                while($comment = mysqli_fetch_assoc($result)){
                                    $commenter_query = "SELECT user_name FROM mrb_users WHERE user_id = '{$comment['user_id']}'";
                                    $commenter_result = mysqli_query($conn, $commenter_query);
                                    $commenter = mysqli_fetch_assoc($commenter_result);
                                    
                                    echo '<div class="comment w-100 mb-4">';
                                    echo '<div class="row mb-4">';
                                    echo '<div class="col">';
                                    echo '<p class="commenter-name mb-0">' . htmlspecialchars($commenter['user_name']) . '</p>';
                                    echo '<div class="comment-star-container d-flex">';
                                    
                                    for($i = 0; $i < 5; $i++){
                                        if($i < $comment['rating']){
                                            echo '<i class="bi bi-star-fill text-warning comment-product-rating"></i>';
                                        } else {
                                            echo '<i class="bi bi-star-fill comment-product-rating text-muted"></i>';
                                        }
                                    }
                                    
                                    echo '</div></div>';
                                    echo '<div class="col"><p class="comment-date text-end">' . date('m/d/y', strtotime($comment['comment_dateadded'])) . '</p></div>';
                                    echo '</div>';
                                    echo '<p class="comment-text">' . '"'.htmlspecialchars($comment['user_comment']) . '"</p>';
                                    
                                    // Display review photo if exists
                                    if(!empty($comment['comments_pic']) && file_exists($comment['comments_pic'])){
                                        echo '<div class="review-photo-container mt-2">';
                                        echo '<img src="' . htmlspecialchars($comment['comments_pic']) . '" ';
                                        echo 'alt="Review photo" ';
                                        echo 'class="review-photo-thumbnail" ';
                                        echo 'style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #ddd;" ';
                                        echo 'onclick="openPhotoModal(\'' . htmlspecialchars($comment['comments_pic']) . '\')">'; 
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>No reviews yet. Be the first to review this product!</p>';
                            }
                        ?>
                    </div>
                    
                    <button class="seeall-btn mb-4" data-bs-target="#comrevmodal" data-bs-toggle="modal" style="font-size: 1rem;">See all>></button>

            </div>
        </div>

        <!-- SIDEBARRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRRR -->
         
        <div class="col-lg p-0 indiv-products-sidebar w-100 mx-0 pt-3 mb-0 mb-lg-5">
            <p class="sidebar-title px-3">MOST POPULAR PRODUCTS</p>
            <div class="container-fluid w-100 px-3 sidebar-products-scroll d-flex flex-row flex-lg-column">
                
                <?php
                // Query to get top 5 most popular products based on order frequency
                $popular_query = "SELECT 
                    mrb_fireex.prod_id,
                    mrb_fireex.prod_name,
                    mrb_fireex.prod_mainpic,
                    mrb_fireex.prod_newprice,
                    COUNT(mrb_orders.product_id) as total_orders,
                    SUM(mrb_orders.order_quantity) as total_quantity_sold,
                    COALESCE(AVG(mrb_comments.rating), 0) as avg_rating
                FROM mrb_fireex 
                LEFT JOIN mrb_orders ON mrb_fireex.prod_id = mrb_orders.product_id 
                    AND mrb_orders.order_status IN ('confirmed', 'delivered')
                LEFT JOIN mrb_comments ON mrb_fireex.prod_id = mrb_comments.product_id
                WHERE mrb_fireex.prod_type != 'deleted' AND (mrb_fireex.is_hidden IS NULL OR mrb_fireex.is_hidden != 'true')
                GROUP BY mrb_fireex.prod_id, mrb_fireex.prod_name, mrb_fireex.prod_mainpic, mrb_fireex.prod_newprice
                ORDER BY total_orders DESC, total_quantity_sold DESC
                LIMIT 4";
                
                $popular_result = mysqli_query($conn, $popular_query);
                
                if ($popular_result && mysqli_num_rows($popular_result) > 0) {
                    $rank = 1;
                    while ($popular_product = mysqli_fetch_assoc($popular_result)) {
                        $product_id = $popular_product['prod_id'];
                        $product_name = htmlspecialchars($popular_product['prod_name']);
                        $product_image = $popular_product['prod_mainpic'];
                        $product_price = number_format($popular_product['prod_newprice'], 2);
                        $total_orders = $popular_product['total_orders'];
                        $avg_rating = round($popular_product['avg_rating'], 1);
                        
                        // Truncate product name if too long
                        $display_name = strlen($product_name) > 40 ? substr($product_name, 0, 40) . '...' : $product_name;
                        
                        echo "
                        <div class='indiv-product-card mb-sm-0 mb-lg-3 position-relative' data-rank='{$rank}'>
                            <div class='rank-badge position-absolute top-0 start-0 bg-danger text-white px-2 py-1 rounded-end' style='font-size: 0.8rem; z-index: 10;'>
                                {$rank}
                            </div>
                            <a href='indiv.php?prod_id={$product_id}' class='text-decoration-none text-dark'>
                                <img src='{$product_image}' class='sidebar-card-img img-fluid' alt='{$product_name}' style='height: 120px; object-fit: cover;'>
                                <p class='sidebar-product-title mb-1' title='{$product_name}'>{$display_name}</p>
                                <div class='row'>
                                    <div class='col-6 d-flex justify-content-start align-items-center'>
                                        <i class='bi bi-star-fill text-warning sidebar-star'></i>
                                        <span class='sidebar-rating'>{$avg_rating}</span>
                                    </div>
                                    <div class='col-6 d-flex justify-content-end sidebar-price align-items-center'>
                                        ₱{$product_price}
                                    </div>
                                </div>
                                <div class='row mt-1'>
                                    <div class='col-12'>
                                        <small class='text-muted'>
                                            <i class='bi bi-cart-check'></i> {$total_orders} orders
                                        </small>
                                    </div>
                                </div>
                            </a>
                        </div>";
                        
                        $rank++;
                    }
                } else {
                    // Fallback to show all products if no orders exist
                    echo "
                    <div class='text-center p-3'>
                        <p class='text-muted'>No sales data available yet.</p>
                        <small>Showing featured products instead:</small>
                    </div>";
                    
                    // Show regular products as fallback
                    $fallback_query = "SELECT prod_id, prod_name, prod_mainpic, prod_newprice 
                                       FROM mrb_fireex 
                                       ORDER BY prod_id DESC 
                                       LIMIT 5";
                    $fallback_result = mysqli_query($conn, $fallback_query);
                    
                    if ($fallback_result && mysqli_num_rows($fallback_result) > 0) {
                        while ($product = mysqli_fetch_assoc($fallback_result)) {
                            $product_id = $product['prod_id'];
                            $product_name = htmlspecialchars($product['prod_name']);
                            $product_image = $product['prod_mainpic'];
                            $product_price = number_format($product['prod_newprice'], 2);
                            $display_name = strlen($product_name) > 40 ? substr($product_name, 0, 40) . '...' : $product_name;
                            
                            echo "
                            <div class='indiv-product-card mb-sm-0 mb-lg-3'>
                                <a href='indiv.php?prod_id={$product_id}' class='text-decoration-none text-dark'>
                                    <img src='{$product_image}' class='sidebar-card-img img-fluid' alt='{$product_name}' style='height: 120px; object-fit: cover;'>
                                    <p class='sidebar-product-title mb-1' title='{$product_name}'>{$display_name}</p>
                                    <div class='row'>
                                        <div class='col-6 d-flex justify-content-start align-items-center'>
                                            <i class='bi bi-star sidebar-star text-muted'></i>
                                            <span class='sidebar-rating text-muted'>New</span>
                                        </div>
                                        <div class='col-6 d-flex justify-content-end sidebar-price align-items-center'>
                                            ₱{$product_price}
                                        </div>
                                    </div>
                                </a>
                            </div>";
                        }
                    }
                }
                ?>
                
            </div>
        </div>



    </div>

<!-- buy now button modal -->
    <div class="modal fade" id="buynowmodel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-body py-5 px-4">
                <div class="row modal-product-header pb-3">
                    <div class="col d-flex justify-content-center align-items-start">
                       <img src="<?php echo $product['prod_mainpic'] ?>" alt="">
                    </div>
                    <div class="col-7">
                        <h3><?php echo $product['prod_name'] ?></h3>
                        <p class="modal-product-address">From : Blk. 2 Lot 1 Joy St. Cityland Subdivision
                            Brgy Mabuhay Carmona, Cavite </p>
                    </div>
                </div>
                <div class="container mt-5 px-4">

                    <div class="row mb-5">
                        <span class="col">Name of Recipient:</span>
                        <p class="col-7 text-center"><?php echo $user['user_name']; ?></p>
                    </div>
                    <div class="row mb-5">
                        <span class="col">Location:
                            <a class="loc-icon-link text-secondary" href="#" data-bs-toggle="modal" data-bs-target="#locationModal">
                                <i class="p-0 bi bi-geo-alt-fill select-loc-icon"></i>
                              </a>
                        </span>
                        <p class="col-7 text-center" id="location_modal"><?php echo $user['user_location2']; ?></p>
                    </div>
                    <div class="row mb-5">
                        <span class="col">Quantity (kg):</span>
                        <p class="col-7 text-center" id="quantity_modal">1</p>
                    </div>
                    <!-- New Meat-Specific Fields -->
                    <div class="row mb-4">
                        <span class="col">Cut Preference:</span>
                        <div class="col-7">
                            <select class="form-select" id="cut_preference" required>
                                <option value="" selected disabled>Select cut preference</option>
                                <option value="thick">Thick Cut (1.5-2 inches)</option>
                                <option value="medium">Medium Cut (1 inch)</option>
                                <option value="thin">Thin Cut (0.5 inch)</option>
                                <option value="whole">Whole/Uncut</option>
                                <option value="diced">Diced/Cubed</option>
                                <option value="ground">Ground/Minced</option>
                                <option value="other">Other (Specify in notes)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <span class="col">Meat Processing:</span>
                        <div class="col-7">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="deboned" value="deboned">
                                <label class="form-check-label" for="deboned">
                                    Remove Bones (Deboned)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="trimmed" value="trimmed">
                                <label class="form-check-label" for="trimmed">
                                    Trim Excess Fat
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="skinless" value="skinless">
                                <label class="form-check-label" for="skinless">
                                    Remove Skin (if applicable)
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <span class="col">Preferred Weight:</span>
                        <div class="col-7">
                            <input type="number" class="form-control" id="preferred_weight" 
                                   min="0.5" step="0.5" placeholder="Enter weight in kg">
                            <small class="text-muted">Optional: Specify exact weight needed</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <span class="col">Delivery Date:</span>
                        <div class="col-7">
                            <input type="date" class="form-control" id="delivery_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            <small class="text-muted">Minimum 1 day in advance</small>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <span class="col">Special Instructions:</span>
                        <div class="col-7">
                            <textarea class="form-control" id="special_instructions" rows="3" 
                                      placeholder="Any special requests? (e.g., marinated, butterfly cut, portion sizes)"></textarea>
                        </div>
                    </div>
                    
                    <div class="row mb-5">
                        <span class="col">Payment Method:</span>
                        <div class="col-7 d-flex flex-row justify-content-center align-items-start justify-content-center">
                            <div class="col d-flex justify-content-center">
                                <input type="radio" class="me-1" name="payment-method" id="gcash" value="gcash">
                                <label for="gcash" class="text-center">GCash (PayMongo)</label>
                            </div>
                            <div class="col d-flex align-items-start justify-content-center">
                                <input type="radio" class="me-1 mt-2" name="payment-method" id="cash" value="cash" checked>
                                <label for="cash" class="text-center">Cash on<br> Delivery</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-5">
                        <span class="col">Total Price:</span>
                        <p class="col-7 text-center" style="font-weight: bold;" id="total_price_display">₱2,600.00</p>
                    </div>
                </div>
                <div class="container d-flex flex-column pt-5">
                    <button type="button" class="btn btn-primary mb-4 BuyNow-btn" style="background-color:#C42F01;" id="confirmOrderBtn" data-product-id="<?php echo (int)$product['prod_id']; ?>" data-inline-order-handler="1">Confirm</button>
                    <button type="button" class="Cancelbuy-btn" data-bs-dismiss="modal">Cancel Order</button>

                </div>
                
            </div>
          </div>
        </div>
      </div>  



<!-- comments and reviews modal -->
      <div class="modal fade" id="comrevmodal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-body py-5 px-4">
                <h3 class="px-2 mb-3">REVIEWS & COMMENTS</h3>
                <div class="row px-2 d-flex flex-row align-items-center">
                    <span>Sort By:</span>
                    <div class="dropdown-hover">
                        <a href="#" class="dropdown-toggle">Newest First</a>
                        <ul class="dropdown-menu dropdown-hover-menu">
                            <li><a class="dropdown-item" href="indiv.html#reviews-section">Highest Ratings First</a></li>
                            <li><a class="dropdown-item" href="indiv.html#reviews-section">Lowest Ratings First</a></li>
                            <li><a class="dropdown-item" href="indiv.html#reviews-section">Oldest First</a></li>
                        </ul>
                    </div>

                </div>
                <div class="comments-container px-2 mt-3 mb-2">
                <?php
                        
                            $query = "SELECT * FROM mrb_comments WHERE product_id = '$prod_id' ORDER BY comment_dateadded DESC LIMIT 5";
                            $result = mysqli_query($conn, $query);
                            if($result && mysqli_num_rows($result) > 0){
                                while($comment = mysqli_fetch_assoc($result)){
                                    $commenter_query = "SELECT user_name FROM mrb_users WHERE user_id = '{$comment['user_id']}'";
                                    $commenter_result = mysqli_query($conn, $commenter_query);
                                    $commenter = mysqli_fetch_assoc($commenter_result);
                                    
                                    echo '<div class="comment w-100 mb-4">';
                                    echo '<div class="row mb-4">';
                                    echo '<div class="col">';
                                    echo '<p class="commenter-name mb-0">' . htmlspecialchars($commenter['user_name']) . '</p>';
                                    echo '<div class="comment-star-container d-flex">';
                                    
                                    for($i = 0; $i < 5; $i++){
                                        if($i < $comment['rating']){
                                            echo '<i class="bi bi-star-fill text-warning comment-product-rating"></i>';
                                        } else {
                                            echo '<i class="bi bi-star-fill comment-product-rating text-muted"></i>';
                                        }
                                    }
                                    
                                    echo '</div></div>';
                                    echo '<div class="col"><p class="comment-date text-end">' . date('m/d/y', strtotime($comment['comment_dateadded'])) . '</p></div>';
                                    echo '</div>';
                                    echo '<p class="comment-text">' . '"'.htmlspecialchars($comment['user_comment']) . '"</p>';
                                    
                                    // Display review photo if exists
                                    if(!empty($comment['comments_pic']) && file_exists($comment['comments_pic'])){
                                        echo '<div class="review-photo-container mt-2">';
                                        echo '<img src="' . htmlspecialchars($comment['comments_pic']) . '" ';
                                        echo 'alt="Review photo" ';
                                        echo 'class="review-photo-thumbnail" ';
                                        echo 'style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #ddd;" ';
                                        echo 'onclick="openPhotoModal(\'' . htmlspecialchars($comment['comments_pic']) . '\')">'; 
                                        echo '</div>';
                                    }
                                    
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>No reviews yet. Be the first to review this product!</p>';
                            }
                        ?>
                </div>
            </div>
          </div>
        </div>
      </div> 


<!-- Location Selection Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Select Delivery Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">Choose your preferred delivery location:</p>
                <div class="list-group">
                    <?php
                    $hasLocations = false;
                    
                    // Location 1
                    if (!empty($user['user_location'])) {
                        $hasLocations = true;
                        echo "
                        <button type='button' class='list-group-item list-group-item-action location-option' data-location='" . htmlspecialchars($user['user_location'], ENT_QUOTES) . "'>
                            <div class='d-flex w-100 justify-content-between'>
                                <h6 class='mb-1'>" . htmlspecialchars($user['user_location']) . "</h6>
                                <small class='text-success'>Available</small>
                            </div>
                            <small class='text-muted'>1-2 business days</small>
                        </button>
                        ";
                    }
                    
                    // Location 2
                    if (!empty($user['user_location2'])) {
                        $hasLocations = true;
                        echo "
                        <button type='button' class='list-group-item list-group-item-action location-option' data-location='" . htmlspecialchars($user['user_location2'], ENT_QUOTES) . "'>
                            <div class='d-flex w-100 justify-content-between'>
                                <h6 class='mb-1'>" . htmlspecialchars($user['user_location2']) . "</h6>
                                <small class='text-success'>Available</small>
                            </div>
                            <small class='text-muted'>1-2 business days</small>
                        </button>
                        ";
                    }
                    
                    // Location 3
                    if (!empty($user['user_location3'])) {
                        $hasLocations = true;
                        echo "
                        <button type='button' class='list-group-item list-group-item-action location-option' data-location='" . htmlspecialchars($user['user_location3'], ENT_QUOTES) . "'>
                            <div class='d-flex w-100 justify-content-between'>
                                <h6 class='mb-1'>" . htmlspecialchars($user['user_location3']) . "</h6>
                                <small class='text-success'>Available</small>
                            </div>
                            <small class='text-muted'>1-2 business days</small>
                        </button>
                        ";
                    }
                    
                    if (!$hasLocations) {
                        echo "<p class='text-muted'>No saved locations available. Please add a location in your profile settings.</p>";
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLocationBtn" disabled>Confirm Location</button>
            </div>
        </div>
    </div>
</div>

<!-- GCash payments now use PayMongo redirect checkout (no manual QR/transaction input modal). -->
    
    <!--INDIVIDUAL PRODUCTSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->
    <!-- 2 TYPES OF FIRE EXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX -->
   <section class="types-of-fireex-section py-5" id="info-section">
        <p class="bruh text-center" style="color: #fff;">At Meat Shop, we're committed to providing you with the best quality meats and helping you make the most of them. Here's your complete guide to understanding meat cuts and cooking methods: <hr style="color: #fff;"></p>

        <div class="container mt-5">
            <div class="row g-4">
              
              <table>
                  <tr>
                    <th></th>
                    <th>Fresh Cuts</th>
                    <th>Frozen Cuts</th>
                  </tr>
                  <tr>
                    <td>Storage:</td>
                    <td>Keep refrigerated at 32-40°F (0-4°C). Use within 1-2 days for ground meat, 3-5 days for steaks and roasts.</td>
                    <td>Store at 0°F (-18°C) or below. Keeps for 4-12 months depending on the cut.</td>
                  </tr>
                  <tr>
                    <td>Best for:</td>
                    <td>Immediate cooking, maximum tenderness, best flavor profile for grilling and quick meals.</td>
                    <td>Meal planning, bulk buying, long-term storage, and slow-cooking methods.</td>
                  </tr>
                  <tr>
                    <td>Pros:</td>
                    <td>Superior texture, no thawing needed, better for marinating, immediate use.</td>
                    <td>Longer shelf life, convenient, economical for bulk purchases, reduces food waste.</td>
                  </tr>
                  <tr>
                    <td>Cons:</td>
                    <td>Shorter storage time, requires immediate planning for meals.</td>
                    <td>Requires thawing time (24hrs in fridge), slight moisture loss if not properly wrapped.</td>
                  </tr>
              </table>

          
    <div class="row container-fluid p-5 mt-3">
        <section id="alt-features" class="alt-features section">

            <div class="container">
          
              <div class="row gy-5">
          
                <div class="col-xl-7 d-flex order-2 order-xl-1" data-aos="fade-up" data-aos-delay="200">
          
                  <div class="row align-self-center gy-5">
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-thermometer-half"></i>
                      <div>
                        <h4 class="bruh">Beef</h4>
                        <p class="bruh1">Internal temp: 145°F (medium-rare) to 160°F (medium). Rest for 3 minutes before cutting.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-heart-fill"></i>
                      <div>
                        <h4 class="bruh">Pork</h4>
                        <p class="bruh1">Cook to 145°F with 3-minute rest. Ground pork should reach 160°F for food safety.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-egg-fried"></i>
                      <div>
                        <h4 class="bruh">Chicken</h4>
                        <p class="bruh1">Must reach 165°F throughout. Check thickest part of breast and thigh for doneness.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-snow"></i>
                      <div>
                        <h4 class="bruh">Frozen Storage</h4>
                        <p class="bruh1">Wrap tightly in freezer paper or vacuum-seal. Label with date. Use oldest first.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-clock-history"></i>
                      <div>
                        <h4 class="bruh">Marinating</h4>
                        <p class="bruh1">Marinate in refrigerator 30min-24hrs. Tougher cuts benefit from longer marinating time.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-fire"></i>
                      <div>
                        <h4 class="bruh">Cooking Methods</h4>
                        <p class="bruh1">Tender cuts: grill or pan-fry. Tough cuts: slow-cook, braise, or stew for best results.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                  </div>
          
                </div>
          
                <div class="col-xl-5 d-flex align-items-center order-1 order-xl-2" data-aos="fade-up" data-aos-delay="100">
                  <img src="img/shock.png" class="img-fluid w-100" alt="Meat Temperature Guide">
                </div>
          
              </div>
          
            </div>
          
          </section>
          
          
    </div>
    </section>
    <!-- MEAT GUIDE SECTION -->
    <!-- CONTACTITTTTTTTTTTTTTTTTTTTTTTTTTTTSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->

    <?php include 'user-footer.php'; ?>
    
    <!-- CONTACTITTTTTTTTTTTTTTTTTTTTTTTTTTTSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->
     
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.min.js" integrity="sha384-VQqxDN0EQCkWoxt/0vsQvZswzTHUVOImccYmSyhJTp7kGtPed0Qcx8rK9h9YEgx+" crossorigin="anonymous"></script>
    <script src="mrbscript.js"></script>
    <script>
        const paymongoReturnState = <?php echo json_encode($paymongo_return, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const paymongoPendingOrder = <?php echo json_encode($paymongo_pending_order, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Get the Buy Now button (use data-bs-target to find the right button)
            const buyNowBtns = document.querySelectorAll('[data-bs-target="#buynowmodel"]');
            
            // Add click event handler to all buy now buttons
            buyNowBtns.forEach(function(buyNowBtn) {
                buyNowBtn.addEventListener('click', function() {
                    // Get the current quantity value from the product page
                    const productQuantity = document.getElementById('quantity') ? 
                                          document.getElementById('quantity').innerHTML : '1';
                    
                    // Update the quantity in the modal
                    const quantityModal = document.getElementById('quantity_modal');
                    if (quantityModal) {
                        quantityModal.innerHTML = productQuantity;
                    }
                    
                    // Reset preferred weight
                    const preferredWeightInput = document.getElementById('preferred_weight');
                    if (preferredWeightInput) {
                        preferredWeightInput.value = '';
                    }
                    
                    // Update the total price
                    updateTotalPrice(productQuantity);
                });
            });
        });

        // Function to update total price based on quantity and weight
        function updateTotalPrice(quantity) {
            // Get product price (remove ₱ sign and convert to number)
            const priceElement = document.querySelector('.product-indiv-price');
            
            if (priceElement) {
                const priceText = priceElement.innerHTML;
                const pricePerKg = parseFloat(priceText.replace('₱', '').replace(',', ''));
                
                // Check if preferred weight is specified
                const preferredWeightInput = document.getElementById('preferred_weight');
                const preferredWeight = preferredWeightInput ? parseFloat(preferredWeightInput.value) || 0 : 0;
                
                // Use preferred weight if specified, otherwise use quantity
                const weight = preferredWeight > 0 ? preferredWeight : parseInt(quantity);
                
                // Calculate total
                const total = pricePerKg * weight;
                
                // Format with thousand separator and currency
                const formattedTotal = '₱' + total.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                
                // Update the total price in modal
                const totalPriceElement = document.getElementById('total_price_display');
                if (totalPriceElement) {
                    totalPriceElement.innerHTML = formattedTotal;
                }
            }
        }
        
        // Add event listener for preferred weight changes
        document.addEventListener('DOMContentLoaded', function() {
            const preferredWeightInput = document.getElementById('preferred_weight');
            if (preferredWeightInput) {
                preferredWeightInput.addEventListener('input', function() {
                    const quantity = document.getElementById('quantity_modal') ? 
                                   document.getElementById('quantity_modal').textContent : '1';
                    updateTotalPrice(quantity);
                });
            }
        });

        // Functions for quantity controls
        function add_quantity() {
            const quantityElement = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityElement.innerHTML);
            
            // Get available stock from PHP (you may need to add this)
            const maxStock = <?php echo $product['prod_quantity']; ?>;
            
            if (currentQuantity < maxStock) {
                quantityElement.innerHTML = currentQuantity + 1;
            } else {
                showOrderToast('Stock Limit', 'Maximum stock reached!', 'error');
            }
        }

        function sub_quantity() {
            const quantityElement = document.getElementById('quantity');
            let currentQuantity = parseInt(quantityElement.innerHTML);
            
            if (currentQuantity > 1) {
                quantityElement.innerHTML = currentQuantity - 1;
            }
        }

        // Location selection script
        document.addEventListener('DOMContentLoaded', function() {
            const locationOptions = document.querySelectorAll('.location-option');
            const confirmLocationBtn = document.getElementById('confirmLocationBtn');
            const locationModal = document.getElementById('location_modal');

            // Enable or disable the confirm button based on selection
            locationOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove active class from all options
                    locationOptions.forEach(opt => opt.classList.remove('active'));

                    // Add active class to the selected option
                    this.classList.add('active');

                    // Enable the confirm button
                    confirmLocationBtn.removeAttribute('disabled');
                });
            });

            // Confirm location button click
            confirmLocationBtn.addEventListener('click', function() {
                // Get the selected location
                const selectedLocation = document.querySelector('.location-option.active');

                if (selectedLocation) {
                    const newLocation = selectedLocation.getAttribute('data-location');
                    
                    // Update the location in the modal display
                    locationModal.innerHTML = newLocation;

                    // Send AJAX request to update session variable
                    fetch('update_location.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'location=' + encodeURIComponent(newLocation)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Location updated successfully');
                        } else {
                            console.error('Failed to update location');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });

                    // Close the modal and reopen Buy Now modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('locationModal'));
                    modal.hide();
                    
                    // Wait for location modal to fully close, then reopen Buy Now modal
                    document.getElementById('locationModal').addEventListener('hidden.bs.modal', function reopenBuyNow() {
                        // Remove the event listener to prevent multiple executions
                        this.removeEventListener('hidden.bs.modal', reopenBuyNow);
                        
                        // Reopen the Buy Now modal
                        const buyNowModalElement = document.getElementById('buynowmodel');
                        const buyNowModal = new bootstrap.Modal(buyNowModalElement);
                        buyNowModal.show();
                    }, { once: true });
                }
            });

            // Order confirmation handler
            const confirmOrderBtn = document.getElementById('confirmOrderBtn');
            if (confirmOrderBtn) {
                confirmOrderBtn.addEventListener('click', function() {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        showOrderToast('Login Required', 'Please log in to place an order.', 'error');
                        return;
                    <?php endif; ?>

                    // Get order data
                    const productId = <?php echo $product['prod_id']; ?>;
                    const quantity = parseInt(document.getElementById('quantity_modal').textContent);
                    const location = document.getElementById('location_modal').textContent;
                    const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
                    
                    // Get meat-specific fields
                    const cutPreference = document.getElementById('cut_preference').value;
                    const deliveryDate = document.getElementById('delivery_date').value;
                    const preferredWeight = document.getElementById('preferred_weight').value;
                    const specialInstructions = document.getElementById('special_instructions').value;
                    
                    // Get processing options
                    const deboned = document.getElementById('deboned').checked;
                    const trimmed = document.getElementById('trimmed').checked;
                    const skinless = document.getElementById('skinless').checked;
                    
                    // Build processing options string
                    let processingOptions = [];
                    if (deboned) processingOptions.push('Deboned');
                    if (trimmed) processingOptions.push('Trimmed');
                    if (skinless) processingOptions.push('Skinless');
                    const processing = processingOptions.join(', ');

                    // Validate required fields
                    if (!location || location.trim() === '') {
                        showOrderToast('Missing Information', 'Please select a delivery location.', 'error');
                        return;
                    }

                    if (quantity <= 0) {
                        showOrderToast('Invalid Quantity', 'Please select a valid quantity.', 'error');
                        return;
                    }
                    
                    if (!cutPreference) {
                        showOrderToast('Missing Information', 'Please select a cut preference.', 'error');
                        return;
                    }
                    
                    if (!deliveryDate) {
                        showOrderToast('Missing Information', 'Please select a delivery date.', 'error');
                        return;
                    }

                    // Check if GCash is selected
                    if (paymentMethod === 'gcash') {
                        initiatePayMongoCheckout(productId, quantity, location, cutPreference, deliveryDate, preferredWeight, processing, specialInstructions);
                    } else {
                        // Process order directly for Cash on Delivery
                        processOrder(productId, quantity, location, paymentMethod, cutPreference, deliveryDate, preferredWeight, processing, specialInstructions);
                    }
                });
            }

            if (paymongoReturnState && paymongoReturnState.message) {
                const wasSuccess = paymongoReturnState.status === 'success';
                showOrderToast(wasSuccess ? 'Payment Update' : 'Payment Error', paymongoReturnState.message, wasSuccess ? 'success' : 'error');
            }

            if (paymongoReturnState && paymongoReturnState.status === 'success' && paymongoPendingOrder) {
                processOrder(
                    parseInt(paymongoPendingOrder.productId, 10),
                    parseInt(paymongoPendingOrder.quantity, 10),
                    paymongoPendingOrder.location || '',
                    'gcash',
                    paymongoPendingOrder.cutPreference || '',
                    paymongoPendingOrder.deliveryDate || '',
                    paymongoPendingOrder.preferredWeight || '',
                    paymongoPendingOrder.processing || '',
                    paymongoPendingOrder.specialInstructions || ''
                );
            }
        });

        function initiatePayMongoCheckout(productId, quantity, location, cutPreference, deliveryDate, preferredWeight, processing, specialInstructions) {
            const confirmOrderBtn = document.getElementById('confirmOrderBtn');
            if (confirmOrderBtn) {
                confirmOrderBtn.disabled = true;
                confirmOrderBtn.innerHTML = 'Redirecting to PayMongo...';
            }

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('location', location);
            formData.append('cut_preference', cutPreference);
            formData.append('delivery_date', deliveryDate);
            formData.append('preferred_weight', preferredWeight || '');
            formData.append('processing_options', processing || '');
            formData.append('special_instructions', specialInstructions || '');

            fetch('create_paymongo_checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => parseJsonResponse(response, 'PayMongo checkout'))
            .then(data => {
                if (!data.success || !data.checkout_url) {
                    throw new Error(data.message || 'Unable to start PayMongo checkout.');
                }

                window.location.href = data.checkout_url;
            })
            .catch(error => {
                console.error('PayMongo checkout error:', error);
                showOrderToast('Payment Error', error.message || 'Unable to start PayMongo checkout. Please try again.', 'error');
            })
            .finally(() => {
                if (confirmOrderBtn) {
                    confirmOrderBtn.disabled = false;
                    confirmOrderBtn.innerHTML = 'Confirm';
                }
            });
        }

        // Parse JSON safely so empty/non-JSON responses surface a readable error.
        function parseJsonResponse(response, contextLabel) {
            return response.text().then(text => {
                let data = null;

                if (text && text.trim() !== '') {
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        const preview = text.substring(0, 180).replace(/\s+/g, ' ').trim();
                        throw new Error((contextLabel || 'Request') + ' returned invalid server response.' + (preview ? ' Response preview: ' + preview : ''));
                    }
                }

                if (!response.ok) {
                    const serverMsg = data && data.message ? data.message : ('HTTP ' + response.status);
                    throw new Error((contextLabel || 'Request') + ' failed: ' + serverMsg);
                }

                if (!data) {
                    throw new Error((contextLabel || 'Request') + ' returned an empty response from server.');
                }

                return data;
            });
        }

        // Function to process order
        function processOrder(productId, quantity, location, paymentMethod, cutPreference, deliveryDate, preferredWeight, processing, specialInstructions) {
            const currentOrderShopId = <?php echo isset($product['shop_id']) ? (int)$product['shop_id'] : 0; ?>;
            // Disable the button to prevent double submission
            const confirmOrderBtn = document.getElementById('confirmOrderBtn');
            confirmOrderBtn.disabled = true;
            confirmOrderBtn.innerHTML = 'Processing...';

            // Prepare form data
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('location', location);
            formData.append('payment_method', paymentMethod);
            formData.append('cut_preference', cutPreference);
            formData.append('delivery_date', deliveryDate);
            formData.append('preferred_weight', preferredWeight || '');
            formData.append('processing_options', processing);
            formData.append('special_instructions', specialInstructions);
            if (currentOrderShopId > 0) {
                formData.append('shop_id', currentOrderShopId);
            }

            // Send order to server
            fetch('process_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => parseJsonResponse(response, 'Order request'))
            .then(data => {
                if (data.success) {
                    // Order successful
                    showOrderToast('Order Success', `${data.message}<br><br>Product: ${data.product_name}<br>Quantity: ${data.quantity}<br>Delivery to: ${data.location}`, 'success');
                    
                    // Close any open modals
                    const buyNowModal = bootstrap.Modal.getInstance(document.getElementById('buynowmodel'));
                    if (buyNowModal) buyNowModal.hide();
                } else {
                    // Order failed
                    showOrderToast('Order Failed', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showOrderToast('Error', 'An error occurred while processing your order. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable the button
                if (confirmOrderBtn) {
                    confirmOrderBtn.disabled = false;
                    confirmOrderBtn.innerHTML = 'Confirm';
                }
            });
        }

        // Function to show order toast notifications
        function showOrderToast(title, message, type) {
            const toastEl = document.getElementById('orderToast');
            const toastTitle = document.getElementById('orderToastTitle');
            const toastMessage = document.getElementById('orderToastMessage');
            const toastIcon = document.getElementById('orderToastIcon');
            const toastHeader = toastEl.querySelector('.toast-header');
            
            // Set title and message
            toastTitle.textContent = title;
            toastMessage.innerHTML = message.replace(/\\n/g, '<br>');
            
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
            
            // Show toast without auto-hide
            const toast = new bootstrap.Toast(toastEl, { autohide: false });
            toast.show();
        }

        // Function to dismiss toast and redirect to orders page
        function dismissAndGoToOrders() {
            const toastEl = document.getElementById('orderToast');
            const toast = bootstrap.Toast.getInstance(toastEl);
            if (toast) {
                toast.hide();
            }
            window.location.href = 'userorders.php?order_sort=All';
        }
    </script>
    
    <!-- Review Photo Modal -->
    <div class="modal fade" id="reviewPhotoModal" tabindex="-1" aria-labelledby="reviewPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewPhotoModalLabel">Review Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="reviewPhotoFull" src="" alt="Review photo" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Function to open photo in full-size modal
        function openPhotoModal(photoPath) {
            document.getElementById('reviewPhotoFull').src = photoPath;
            const modal = new bootstrap.Modal(document.getElementById('reviewPhotoModal'));
            modal.show();
        }
    </script>
    
    <script src="chat-message.js"></script>
</body>
</html>
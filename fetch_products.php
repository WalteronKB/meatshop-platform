<?php
include 'connection.php';

// Get sorting order from POST
$orderBy = isset($_POST['order']) ? $_POST['order'] : 'prod_dateadded DESC';

// Get product type from POST
$productType = isset($_POST['type']) ? $_POST['type'] : 'all';

// Map product types to database values
$productTypeMap = [
    'pork' => 'Pork Products',
    'chicken' => 'Chicken Products',
    'beef' => 'Beef Products',
    'all' => null
];

// Sanitize the orderBy to prevent SQL injection
$allowedOrders = [
    'prod_dateadded DESC', 'prod_dateadded ASC', 
    'prod_name ASC', 'prod_name DESC',
    'prod_newprice ASC', 'prod_newprice DESC'
];

if (!in_array($orderBy, $allowedOrders)) {
    $orderBy = 'prod_dateadded DESC'; // Default if invalid order
}

// Validate product type
if (!array_key_exists($productType, $productTypeMap)) {
    $productType = 'all';
}

// Build WHERE clause based on product type
$whereClause = '';
if ($productTypeMap[$productType]) {
    $whereClause = "WHERE prod_type = '{$productTypeMap[$productType]}'";
} else {
    $whereClause = "WHERE prod_type != 'deleted'";
}

// Count total products
$countQuery = "SELECT COUNT(*) as total FROM mrb_fireex $whereClause";
$countResult = mysqli_query($conn, $countQuery);
$totalItems = $countResult->fetch_assoc()['total'];

// Fetch products from the database
$sql = "SELECT * FROM mrb_fireex $whereClause ORDER BY $orderBy";
$result = mysqli_query($conn, $sql);
$output = '';

if (mysqli_num_rows($result) > 0) {
    $counter = 0;
    while($row = mysqli_fetch_assoc($result)) {
        $output .= '<div class="card product-card" onclick="window.location.href=\'indiv.php?prod_id='.$row['prod_id'].'\'">';
        $output .= '<img src="'.$row['prod_mainpic'].'" class="product-img"/>';
        
        // Only add the "New" tag to the first product
        if ($counter == 0) {
            $output .= '<div class="new-product">New</div>';
        }
        
        $output .= '<div class="card-body p-0">';
        $output .= '<div class="row d-flex flex-row align-items-center my-2 px-2">';
        $output .= '<p class="col product-title m-0 ">'.$row['prod_name'].'</p>';
        $output .= '<a class="help-icon-link col-1" href="#info-section" onclick="event.stopPropagation();"><i class="p-0 bi bi-question-circle help-icon"></i></a>';
        $output .= '</div>';
        $output .= '<div class="row d-flex flex-row align-items-center mb-2">';
        if (isset($row['prod_oldprice']) && (float)$row['prod_oldprice'] > 0) {
            $output .= '<span class="old-price col text-center">₱'.$row['prod_oldprice'].'</span>';
        }
        $output .= '<span class="new-price col-7">₱'.$row['prod_newprice'].'</span>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $counter++;
    }
} else {
    $output = "No products found.";
}

// Return both the HTML and the count in JSON format
$response = [
    'html' => $output,
    'count' => $totalItems . ' items'
];

header('Content-Type: application/json');
echo json_encode($response);
?>
<?php
include 'connection.php';

// Check if prod_id is provided
if (!isset($_POST['prod_id']) || empty($_POST['prod_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => '<div class="alert alert-warning">Product ID is required to load comments.</div>',
        'error' => 'Missing product ID'
    ]);
    exit;
}

$orderBy = isset($_POST['order']) ? $_POST['order'] : 'comment_dateadded DESC';
$prod_id = mysqli_real_escape_string($conn, $_POST['prod_id']);

$allowedOrders = [
    'comment_dateadded DESC', 'comment_dateadded ASC', 
    'rating DESC', 'rating ASC'
];

if (!in_array($orderBy, $allowedOrders)) {
    $orderBy = 'comment_dateadded DESC';
}

$sql = "SELECT c.*, u.user_name 
        FROM mrb_comments c 
        JOIN mrb_users u ON c.user_id = u.user_id 
        WHERE c.product_id = '$prod_id' 
        ORDER BY $orderBy";
$result = mysqli_query($conn, $sql);

if (!$result) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => '<div class="alert alert-danger">Error loading comments. Please try again.</div>',
        'error' => 'Database query failed'
    ]);
    exit;
}

$output = '';

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $output .= '<div class="comment w-100 mb-4">';
        $output .= '<div class="row mb-4">';
        $output .= '<div class="col">';
        $output .= '<p class="commenter-name mb-0">' . htmlspecialchars($row['user_name']) . '</p>';
        $output .= '<div class="comment-star-container d-flex">';
        
        for($i = 0; $i < 5; $i++){
            if($i < $row['rating']){
                $output .= '<i class="bi bi-star-fill text-warning comment-product-rating"></i>';
            } else {
                $output .= '<i class="bi bi-star-fill comment-product-rating text-muted"></i>';
            }
        }
        $output .= '</div></div>';
        $output .= '<div class="col"><p class="comment-date text-end">' . date('m/d/y', strtotime($row['comment_dateadded'])) . '</p></div>';
        $output .='</div>';
        $output .= '<p class="comment-text">' . '"'.htmlspecialchars($row['user_comment']) . '"</p>';
        $output .= '</div>';
    }
} else {
    $output = '<div class="text-center text-muted py-4">No comments found for this product yet. Be the first to leave a review!</div>';
}

$response = [
    'html' => $output,
    'count' => mysqli_num_rows($result)
];

header('Content-Type: application/json');
echo json_encode($response);
?>
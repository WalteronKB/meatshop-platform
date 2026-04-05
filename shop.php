<?php
session_start();
include 'connection.php';

date_default_timezone_set('Asia/Manila');

$shop_id = isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : 0;
$shop = null;

if ($shop_id > 0) {
    $shop_sql = "SELECT approved_shop_id, store_name, store_description, store_address, address_iframe,
                        business_email, business_phone, operating_hours, delivery_areas, store_logo_path
                 FROM approved_shops
                 WHERE approved_shop_id = ? AND shop_status = 'active'
                 LIMIT 1";
    $shop_stmt = mysqli_prepare($conn, $shop_sql);
    mysqli_stmt_bind_param($shop_stmt, 'i', $shop_id);
    mysqli_stmt_execute($shop_stmt);
    $shop_result = mysqli_stmt_get_result($shop_stmt);
    if ($shop_result && mysqli_num_rows($shop_result) > 0) {
        $shop = mysqli_fetch_assoc($shop_result);
    }
    mysqli_stmt_close($shop_stmt);
}

$products = [];
if ($shop) {
    $products_sql = "SELECT prod_id, prod_name, prod_mainpic, prod_newprice, prod_oldprice, prod_desc
                     FROM mrb_fireex
                     WHERE shop_id = ?
                       AND prod_type != 'deleted'
                       AND (is_hidden IS NULL OR is_hidden != 'true')
                     ORDER BY prod_dateadded DESC";
    $products_stmt = mysqli_prepare($conn, $products_sql);
    mysqli_stmt_bind_param($products_stmt, 'i', $shop_id);
    mysqli_stmt_execute($products_stmt);
    $products_result = mysqli_stmt_get_result($products_stmt);
    if ($products_result) {
        while ($row = mysqli_fetch_assoc($products_result)) {
            $products[] = $row;
        }
    }
    mysqli_stmt_close($products_stmt);
}

$shop_avg_rating = 0.0;
$shop_rating_count = 0;
if ($shop) {
    $rating_sql = "SELECT COALESCE(AVG(mc.rating), 0) AS avg_rating,
                          COUNT(mc.comment_id) AS rating_count
                   FROM mrb_fireex mf
                   LEFT JOIN mrb_comments mc ON mc.product_id = mf.prod_id
                   WHERE mf.shop_id = ?
                     AND mf.prod_type != 'deleted'
                     AND (mf.is_hidden IS NULL OR mf.is_hidden != 'true')
                     AND mc.rating > 0";
    $rating_stmt = mysqli_prepare($conn, $rating_sql);
    mysqli_stmt_bind_param($rating_stmt, 'i', $shop_id);
    mysqli_stmt_execute($rating_stmt);
    $rating_result = mysqli_stmt_get_result($rating_stmt);
    if ($rating_result && mysqli_num_rows($rating_result) > 0) {
        $rating_row = mysqli_fetch_assoc($rating_result);
        $shop_avg_rating = isset($rating_row['avg_rating']) ? round((float)$rating_row['avg_rating'], 1) : 0.0;
        $shop_rating_count = isset($rating_row['rating_count']) ? (int)$rating_row['rating_count'] : 0;
    }
    mysqli_stmt_close($rating_stmt);
}

$dynamic_footer_iframe_src = '';
$current_chat_shop_id = 0;
if ($shop && !empty($shop['address_iframe'])) {
    $current_chat_shop_id = (int)($shop['approved_shop_id'] ?? 0);
    $dynamic_footer_iframe_src = trim((string) $shop['address_iframe']);
    if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/i', $dynamic_footer_iframe_src, $iframe_match)) {
        $dynamic_footer_iframe_src = $iframe_match[1];
    }
}

$_SESSION['chat_context_shop_id'] = $current_chat_shop_id;
$_SESSION['chat_context_product_id'] = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $shop ? htmlspecialchars($shop['store_name']) : 'Shop'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="main.css">
    <style>
        .shop-hero {
            background: linear-gradient(135deg, #fff8f5 0%, #fff 100%);
            border: 1px solid #f0e0d8;
            border-radius: 16px;
            padding: 1.25rem;
        }

        .shop-logo {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
        }

        .shop-rating i {
            color: #f2b01e;
            margin-right: 2px;
        }

        .shop-product-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform .2s ease, box-shadow .2s ease;
            overflow: hidden;
            cursor: pointer;
            height: 100%;
        }

        .shop-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.14);
        }

        .shop-product-img {
            width: 100%;
            height: 210px;
            object-fit: cover;
            background: #f3f3f3;
        }

        .chat-wrap {
            border-radius: 12px;
            background: #3f3f3f;
            padding: 14px;
        }

        .chats {
            max-height: 280px;
            overflow-y: auto;
        }

        .chat-message .message,
        .chat-reply .message {
            margin: 0;
            padding: 8px 12px;
            border-radius: 10px;
            display: inline-block;
            max-width: 90%;
        }

        .chat-message {
            text-align: right;
            margin-bottom: 3px;
        }

        .chat-message .message {
            background: #d44a1f;
            color: #fff;
        }

        .chat-reply {
            text-align: left;
            margin-bottom: 3px;
        }

        .chat-reply .message {
            background: #e9ecef;
            color: #222;
        }

        .chat-time {
            font-size: 0.78rem;
            color: #e7e7e7;
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body class="d-flex flex-column">
<?php
if (isset($_SESSION['user_id'])) {
    include 'headerinned.php';
} else {
    include 'headerout.php';
}
?>

<div class="container mt-3">
    <div class="row align-items-center">
        <?php include 'mininavbar.php'; ?>
    </div>
</div>

<div class="container py-4">
    <?php if (!$shop): ?>
        <div class="alert alert-warning">Shop not found or no longer active.</div>
        <a href="landpage.php" class="btn btn-outline-dark">Back to Home</a>
    <?php else: ?>
        <?php $logo_path = !empty($shop['store_logo_path']) ? $shop['store_logo_path'] : 'Images/placeholder.png'; ?>
        <div class="shop-hero mb-4">
            <div class="d-flex gap-3 align-items-center flex-wrap">
                <img src="<?php echo htmlspecialchars($logo_path); ?>" class="shop-logo" alt="<?php echo htmlspecialchars($shop['store_name']); ?>">
                <div>
                    <h2 class="mb-1"><?php echo htmlspecialchars($shop['store_name']); ?></h2>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($shop['store_description'] ?: 'Trusted local shop'); ?></p>
                    <p class="mb-0"><strong>Products:</strong> <?php echo number_format(count($products)); ?></p>
                    <p class="mb-0 shop-rating">
                        <strong>Overall Rating:</strong>
                        <?php if ($shop_rating_count > 0): ?>
                            <?php
                                $full_stars = (int) floor($shop_avg_rating);
                                $half_star = (($shop_avg_rating - $full_stars) >= 0.5) ? 1 : 0;
                                $empty_stars = 5 - $full_stars - $half_star;
                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<i class="bi bi-star-fill"></i>';
                                }
                                if ($half_star) {
                                    echo '<i class="bi bi-star-half"></i>';
                                }
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    echo '<i class="bi bi-star"></i>';
                                }
                            ?>
                            <span><?php echo number_format($shop_avg_rating, 1); ?>/5 (<?php echo number_format($shop_rating_count); ?> reviews)</span>
                        <?php else: ?>
                            <span>No ratings yet</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <h4 class="mb-3">Products</h4>
        <div class="row g-4 mb-5">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="shop-product-card" onclick="window.location.href='indiv.php?prod_id=<?php echo (int)$product['prod_id']; ?>'">
                            <img src="<?php echo htmlspecialchars($product['prod_mainpic']); ?>" class="shop-product-img" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            <div class="p-3">
                                <h6 class="mb-1"><?php echo htmlspecialchars($product['prod_name']); ?></h6>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (isset($product['prod_oldprice']) && (float)$product['prod_oldprice'] > 0): ?>
                                    <span class="text-muted text-decoration-line-through small">PHP <?php echo number_format((float)$product['prod_oldprice'], 2); ?></span>
                                    <?php endif; ?>
                                    <span class="fw-semibold">PHP <?php echo number_format((float)$product['prod_newprice'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-secondary">No products available for this shop yet.</div>
                </div>
            <?php endif; ?>
        </div>

        
    <?php endif; ?>
</div>

<?php include 'user-footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="chat-message.js"></script>
</body>
</html>

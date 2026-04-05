<?php
session_start();
include 'connection.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <style>
    .carousel-item {
      justify-content: center;
      padding: 20px;
      transition: transform 0.6s ease-in-out;
    }

    .carousel-inner {
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .carousel .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      transition: transform 0.4s ease, box-shadow 0.4s ease;
      overflow: hidden;
      position: relative;
    }

    .carousel .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #C42F01 0%, #8B0000 100%);
    }

    .carousel .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 25px 60px rgba(196, 47, 1, 0.25);
    }

    .carousel .card-body {
      padding: 40px;
      background: white;
    }

    .carousel .card img {
      border-radius: 15px;
      object-fit: cover;
      max-height: 400px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      transition: transform 0.4s ease;
    }

    .carousel .card:hover img {
      transform: scale(1.05);
    }

    .carousel .card-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 20px;
      text-transform: capitalize;
      letter-spacing: -0.5px;
    }

    .carousel .card-text {
      font-size: 1.05rem;
      color: #666;
      line-height: 1.7;
      margin-bottom: 25px;
    }

    .carousel .btn-primary {
      background: linear-gradient(135deg, #C42F01 0%, #8B0000 100%);
      border: none;
      padding: 14px 40px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1.05rem;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(196, 47, 1, 0.3);
    }

    .carousel .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(196, 47, 1, 0.5);
      background: linear-gradient(135deg, #d43512 0%, #9d0000 100%);
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
      background-color: rgba(196, 47, 1, 0.8);
      border-radius: 50%;
      padding: 25px;
      transition: all 0.3s ease;
    }

    .carousel-control-prev:hover .carousel-control-prev-icon,
    .carousel-control-next:hover .carousel-control-next-icon {
      background-color: rgba(139, 0, 0, 1);
      transform: scale(1.1);
    }

    .carousel-indicators button {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background-color: #C42F01;
      border: 2px solid white;
      transition: all 0.3s ease;
    }

    .carousel-indicators button.active {
      width: 40px;
      border-radius: 10px;
      background-color: #8B0000;
    }

    h2.text-center {
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 40px;
      position: relative;
      display: inline-block;
      width: 100%;
    }

    h2.text-center::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 4px;
      background: linear-gradient(90deg, #C42F01 0%, #8B0000 100%);
      border-radius: 2px;
    }

    @media (max-width: 768px) {
      .carousel .card-body {
        padding: 20px;
      }
      
      .carousel .card-title {
        font-size: 1.5rem;
      }
      
      .carousel .card img {
        max-height: 250px;
      }

      h2.text-center {
        font-size: 1.8rem;
      }

      .carousel .btn-primary {
        padding: 12px 30px;
        font-size: 1rem;
      }
    }

    .shop-card {
      border: none;
      border-radius: 14px;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
      padding: 1.25rem;
      text-align: center;
    }

    .shop-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 30px rgba(0, 0, 0, 0.12);
    }

    .shop-logo {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      background: #f2f2f2;
      border: 4px solid #fff;
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
      margin: 0 auto 0.9rem;
    }

    .shop-meta {
      font-size: 0.92rem;
      color: #5f6368;
      margin-bottom: 0.35rem;
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
      <?php
      include 'mininavbar.php';
      ?>
    </div>
  </div>
  
  <div class="container py-5">
    <h2 class="text-center mb-4">Most popular products today</h2>
    <?php
      $products_to_display = [];

      $_query = " SELECT mrb_orders.product_id, SUM(mrb_orders.order_quantity) AS total_quantity
                  FROM mrb_orders
                  INNER JOIN mrb_fireex ON mrb_orders.product_id = mrb_fireex.prod_id
                  WHERE mrb_fireex.prod_type != 'deleted'
                  AND (mrb_fireex.is_hidden IS NULL OR mrb_fireex.is_hidden != 'true')
                  GROUP BY mrb_orders.product_id
                  ORDER BY total_quantity DESC
                  LIMIT 3 ";

      $popular_result = mysqli_query($conn, $_query);
      if ($popular_result && mysqli_num_rows($popular_result) > 0) {
        while($order = mysqli_fetch_assoc($popular_result)) {
          $product_id = (int) $order['product_id'];
          $product_query = "SELECT prod_id, prod_name, prod_mainpic, prod_newprice, prod_oldprice, prod_desc
                            FROM mrb_fireex
                            WHERE prod_id = '{$product_id}'
                            AND prod_type != 'deleted'
                            AND (is_hidden IS NULL OR is_hidden != 'true')
                            LIMIT 1";
          $product_result = mysqli_query($conn, $product_query);
          if ($product_result && mysqli_num_rows($product_result) > 0) {
            $products_to_display[] = mysqli_fetch_assoc($product_result);
          }
        }
      }

      if (count($products_to_display) === 0) {
        $fallback_query = "SELECT prod_id, prod_name, prod_mainpic, prod_newprice, prod_oldprice, prod_desc
                           FROM mrb_fireex
                           WHERE prod_type != 'deleted'
                           AND (is_hidden IS NULL OR is_hidden != 'true')
                           ORDER BY prod_id DESC
                           LIMIT 3";
        $fallback_result = mysqli_query($conn, $fallback_query);

        if ($fallback_result && mysqli_num_rows($fallback_result) > 0) {
          while ($fallback = mysqli_fetch_assoc($fallback_result)) {
            $products_to_display[] = $fallback;
          }
        }
      }
    ?>

    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
      <div class="carousel-indicators">
        <?php if (count($products_to_display) > 0): ?>
          <?php foreach ($products_to_display as $index => $unused_product): ?>
            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>" aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $index + 1; ?>"></button>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="carousel-inner">
      <?php
        if (count($products_to_display) > 0) {
          foreach ($products_to_display as $item_count => $product) {
            $product_id = (int) ($product['prod_id'] ?? 0);
            $product_name = $product['prod_name'] ?? 'Product';
            $product_image = $product['prod_mainpic'] ?? 'Images/placeholder.png';
            $product_desc = $product['prod_desc'] ?? 'No description available.';

            if (strlen($product_desc) > 150) {
              $product_desc = substr($product_desc, 0, 150) . '...';
            }

            if ($item_count === 0){
              echo "<div class='carousel-item active'>";
            }
            else{
              echo "<div class='carousel-item'>";
            }
            echo "<div class='card'>
                      <div class='card-body pb-0' style='min-height: 50vh;'>
                        <div class='row'>
                          <div class='col-6' style>
                            <img src='{$product_image}' class='img-fluid card-img-top' alt='" . htmlspecialchars($product_name, ENT_QUOTES) . "'>
                          </div>
                          <div class='col-6 py-5'>
                            <h5 class='card-title'>{$product_name}</h5>
                            <p class='card-text'>{$product_desc}</p>
                            <a href='indiv.php?prod_id={$product_id}' class='btn btn-primary'>View Product</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>";
          }
        }
            
      ?>
      
        
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>
  </div>

  <div class="container pb-5">
    <h2 class="text-center mb-4">Visit these shops</h2>
    <div class="row g-4">
      <?php
        $shops_query = "SELECT s.approved_shop_id, s.store_name, s.store_logo_path,
                               COALESCE(sales.total_sold, 0) AS total_sold
                        FROM approved_shops s
                        LEFT JOIN (
                          SELECT shop_id, SUM(order_quantity) AS total_sold
                          FROM mrb_orders
                          GROUP BY shop_id
                        ) sales ON sales.shop_id = s.approved_shop_id
                        WHERE s.shop_status = 'active'
                        ORDER BY s.approved_at DESC
                        LIMIT 8";
        $shops_result = mysqli_query($conn, $shops_query);

        if ($shops_result && mysqli_num_rows($shops_result) > 0) {
          while ($shop = mysqli_fetch_assoc($shops_result)) {
            $shop_id = (int) ($shop['approved_shop_id'] ?? 0);
            $shop_name = $shop['store_name'] ?? 'Shop';
            $shop_logo = !empty($shop['store_logo_path']) ? $shop['store_logo_path'] : 'Images/placeholder.png';
            $total_sold = (int) ($shop['total_sold'] ?? 0);

            echo "<div class='col-12 col-sm-6 col-lg-3'>
                    <a href='shop.php?shop_id={$shop_id}' class='text-decoration-none text-reset'>
                    <div class='card shop-card'>
                      <img src='" . htmlspecialchars($shop_logo, ENT_QUOTES) . "' class='shop-logo' alt='" . htmlspecialchars($shop_name, ENT_QUOTES) . "'>
                      <div class='card-body d-flex flex-column'>
                        <h5 class='card-title mb-2'>" . htmlspecialchars($shop_name, ENT_QUOTES) . "</h5>
                        <p class='shop-meta mb-0'><strong>Products sold:</strong> " . number_format($total_sold) . "</p>
                      </div>
                    </div>
                    </a>
                  </div>";
          }
        } else {
          echo "<div class='col-12'>
                  <div class='alert alert-secondary text-center mb-0'>No active shops available right now.</div>
                </div>";
        }
      ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
<script src="chat-message.js"></script>
</body>

</html>
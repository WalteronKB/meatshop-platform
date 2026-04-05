<?php
    
    session_start();
    include 'connection.php';
    
    // Get product type from URL parameter, default to 'all'
    $productType = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    // Map product types to database values and page titles
    $productTypeMap = [
        'pork' => ['db' => 'Pork Products', 'title' => 'PORK PRODUCTS'],
        'chicken' => ['db' => 'Chicken Products', 'title' => 'CHICKEN PRODUCTS'],
        'fish' => ['db' => 'Fish Products', 'title' => 'FISH PRODUCTS'],
        'beef' => ['db' => 'Beef Products', 'title' => 'BEEF PRODUCTS'],
        'all' => ['db' => null, 'title' => 'ALL PRODUCTS']
    ];
    
    // Validate product type
    if (!array_key_exists($productType, $productTypeMap)) {
        $productType = 'all';
    }
    
    $currentType = $productTypeMap[$productType];
    
    if(isset($_GET['search-submit'])){
      if(!empty($_GET['search-input'])) {
        $searchInput = mysqli_real_escape_string($conn, $_GET['search-input']);
        header("Location: mrbproducts.php?type=" . $productType . "&query=" . urlencode($searchInput));
        exit();
      } else {
        $searchError = "Please enter a search term.";
      }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-...your-key..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="mrbstyle.css">
</head>
<body class="d-flex flex-column">
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



    <div class="products-content container">
        <h3 class="h2 product-title-1 mt-5"><?php echo $currentType['title']; ?> <hr></h3>
        <div class="row">
            <div class="col d-flex align-items-center">
                <span class="red-text">Sort by:</span>
                <div class="dropdown">
                <button class="btn dropdown-toggle ms-2 clean-dropdown d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span id="dropdownLabel" class="red-text">Newest First</span> 
                </button>
                <ul class="dropdown-menu clean-dropdown-menu">
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_dateadded DESC', 'Newest First')">Newest First</a></li>
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_dateadded ASC', 'Oldest First')">Oldest First</a></li>
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_name ASC', 'A to Z')">A to Z</a></li>
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_name DESC', 'Z to A')">Z to A</a></li>
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_newprice ASC', 'Lowest Price First')">Lowest Price First</a></li>
                  <li><a class="dropdown-item red-text" href="#" onclick="updateSort('prod_newprice DESC', 'Highest Price First')">Highest Price First</a></li>
                </ul>
                  </div>
                  
            </div>
            <div class="col">
                <p class="red-text text-end" id="product-count">
                    <?php
                      // Build WHERE clause based on product type
                      $whereClause = $currentType['db'] ? "WHERE prod_type = '{$currentType['db']}'" : "WHERE prod_type != 'deleted'";
                      
                      if(isset($_GET['query']) && !empty($_GET['query'])) {
                        $searchTerm = mysqli_real_escape_string($conn, $_GET['query']);
                        if($whereClause) {
                          $whereClause .= " AND (prod_name LIKE '%$searchTerm%')";
                        } else {
                          $whereClause = "WHERE (prod_name LIKE '%$searchTerm%')";
                        }
                      } else {
                        // If no product type specified and no search, exclude deleted
                        if(!$currentType['db']) {
                          $whereClause = "WHERE prod_type != 'deleted'";
                        }
                      }
                      
                      $query = "SELECT COUNT(*) as total FROM mrb_fireex $whereClause";
                      $result = mysqli_query($conn, $query);
                      echo $result->fetch_assoc()['total'] . " items";
                    ?>
                </p>
            </div>
        </div>
        <div class="container p-3 product-grid">
        <?php
          // Build WHERE clause for products query
          $whereClause = $currentType['db'] ? "WHERE prod_type = '{$currentType['db']}'" : "WHERE prod_type != 'deleted'";
          
          // Check if we have a search query
          if(isset($_GET['query']) && !empty($_GET['query'])) {
            $searchTerm = mysqli_real_escape_string($conn, $_GET['query']);
            if($whereClause) {
              $whereClause .= " AND (prod_name LIKE '%$searchTerm%')";
            } else {
              $whereClause = "WHERE (prod_name LIKE '%$searchTerm%')";
            }
          } else {
            // If no product type specified and no search, exclude deleted
            if(!$currentType['db']) {
              $whereClause = "WHERE prod_type != 'deleted'";
            }
          }
          
          $sql = "SELECT * FROM mrb_fireex $whereClause ORDER BY prod_dateadded DESC";
          $result = mysqli_query($conn, $sql);

          if (mysqli_num_rows($result) > 0) {
                $counter = 0;
                while($row = mysqli_fetch_assoc($result)) {
                    echo '<div class="card product-card" onclick="window.location.href=\'indiv.php?prod_id='.$row['prod_id'].'\'">';
                    echo "<img src='{$row['prod_mainpic']}' style='object-fit: cover;' class='product-img'/>";
                    if ($counter == 0) {
                        echo '<div class="new-product">New</div>';
                    }
                    
                    echo '<div class="card-body p-0">';
                    echo '<div class="row d-flex flex-row align-items-center my-2 px-2">';
                    echo '<p class="col product-title m-0 ">'.$row['prod_name'].'</p>';
                    echo '<a class="help-icon-link col-1" href="#info-section" onclick="event.stopPropagation();"><i class="p-0 bi bi-question-circle help-icon"></i></a>';
                    echo '</div>';
                    echo '<div class="row d-flex flex-row align-items-center mb-2">';
                    if (isset($row['prod_oldprice']) && (float)$row['prod_oldprice'] > 0) {
                      echo '<span class="old-price col text-center">₱'.$row['prod_oldprice'].'</span>';
                    }
                    echo '<span class="new-price col-7">₱'.$row['prod_newprice'].'</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    
                    $counter++;
                }
            } else {
                echo '<div class="alert alert-warning">No products found matching your search criteria.</div>';
            }
          ?>

        </div>
        <h3 class="product-title-1 quote" style="font-size: 2.3rem;">"Premium Quality Meats at Competitive Prices!"</h3>
          
    </div>

    <!-- PRODUCTSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->
    <!-- MEAT EDUCATION SECTION -->
   
      <section class="types-of-fireex-section py-5" id="info-section">
        <p class="bruh text-center" style="color: #fff;">At Meat Shop, we're committed to providing you with the highest quality meat products. Understanding the different cuts and preparation methods will help you make the best choice for your meals: <hr style="color: #fff;"></p>

        <div class="container mt-5">
            <div class="row g-4">
              
              <table>
                  <tr>
                    <th></th>
                    <th>Fresh Meat Cuts</th>
                    <th>Processed Meat Products</th>
                  </tr>
                  <tr>
                    <td>Description:</td>
                    <td>Raw, unprocessed meat cuts including chops, tenderloin, ribs, belly, and shoulder.</td>
                    <td>Cured, smoked, or seasoned products like bacon, ham, sausages, and tocino.</td>
                  </tr>
                  <tr>
                    <td>Best for:</td>
                    <td>Grilling, roasting, braising, and stir-frying. Perfect for homemade marinades and recipes.</td>
                    <td>Quick meals, breakfast dishes, and ready-to-cook convenience. Great for busy families.</td>
                  </tr>
                  <tr>
                    <td>Pros:</td>
                    <td>Maximum freshness, versatile cooking options, control over seasoning and preparation.</td>
                    <td>Time-saving, pre-seasoned flavors, extended shelf life, consistent taste.</td>
                  </tr>
                  <tr>
                    <td>Storage:</td>
                    <td>Refrigerate for 3-5 days or freeze for up to 6 months.</td>
                    <td>Follow package instructions; typically longer shelf life when properly stored.</td>
                  </tr>
              </table>
            </div>
          </div>
          
    <div class="row container-fluid p-5 mt-3">
        <section id="alt-features" class="alt-features section">

            <div class="container">
          
              <div class="row gy-5">
          
                <div class="col-xl-7 d-flex order-2 order-xl-1" data-aos="fade-up" data-aos-delay="200">
          
                  <div class="row align-self-center gy-5">
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-star-fill"></i>
                      <div>
                        <h4 class="bruh">Premium Cuts</h4>
                        <p class="bruh1">Tenderloin, chops, and prime cuts for special occasions and fine dining.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-fire"></i>
                      <div>
                        <h4 class="bruh">BBQ Favorites</h4>
                        <p class="bruh1">Ribs, belly, and shoulder cuts perfect for grilling and smoking.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-egg-fried"></i>
                      <div>
                        <h4 class="bruh">Breakfast Meats</h4>
                        <p class="bruh1">Bacon, sausages, and tocino for delicious morning meals.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-cart-check"></i>
                      <div>
                        <h4 class="bruh">Ground Meat</h4>
                        <p class="bruh1">Versatile ground meat for meatballs, dumplings, and various recipes.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-trophy"></i>
                      <div>
                        <h4 class="bruh">Specialty Items</h4>
                        <p class="bruh1">Marinated meats, ready-to-cook products, and house specialties.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                    <div class="col-md-6 icon-box">
                      <i class="bi bi-snow"></i>
                      <div>
                        <h4 class="bruh">Frozen Options</h4>
                        <p class="bruh1">Flash-frozen cuts maintaining freshness and quality for extended storage.</p>
                      </div>
                    </div><!-- End Feature Item -->
          
                  </div>
          
                </div>
          
                <div class="col-xl-5 d-flex align-items-center order-1 order-xl-2" data-aos="fade-up" data-aos-delay="100">
                  <img src="img/shock.png" class="img-fluid w-100" alt="Meat Products Guide">
                </div>
          
              </div>
          
            </div>
          
          </section>
          
          
    </div>
    </section>
    
    <!-- MEAT EDUCATION SECTION -->
    <!-- CONTACTITTTTTTTTTTTTTTTTTTTTTTTTTTTSSSSSSSSSSSSSSSSSSSSSSSSSSSSSS -->

    


    
    <script>
      function setDropdownLabel(text) {
        document.getElementById('dropdownLabel').textContent = text;
      }
    </script>
    <script>
      // Store the current product type from PHP
      const currentProductType = '<?php echo $productType; ?>';
      
      function updateSort(orderBy, label) {
        // Update dropdown label
        $('#dropdownLabel').text(label);
        
        // Show loading indicator
        $('.product-grid').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        
        // Make AJAX request with product type
        $.post('fetch_products.php', { 
          order: orderBy,
          type: currentProductType
        }, function(data) {
          // Update the product grid with new data
          $('.product-grid').html(data.html);
          
          // Update the product count
          $('#product-count').text(data.count);
        }, 'json')
        .fail(function(xhr, status, error) {
          // Handle errors
          $('.product-grid').html('<div class="alert alert-danger">Error loading products. Please try again.</div>');
          console.error("AJAX Error: " + status + " - " + error);
        });
      }
      </script>
      <script>
        document.getElementById('searchInput').addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault(); // prevent default form submission
            document.getElementById('hiddenSubmit').click(); // trigger hidden button
          }
        });
      </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.min.js" integrity="sha384-VQqxDN0EQCkWoxt/0vsQvZswzTHUVOImccYmSyhJTp7kGtPed0Qcx8rK9h9YEgx+" crossorigin="anonymous"></script>
    <script src="mrbscript.js"></script>
    <script src="chat-message.js"></script>
</body>
</html>

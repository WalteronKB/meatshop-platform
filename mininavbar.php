
<link rel="stylesheet" href="mrbstyle.css">

<div class="col-12 col-md-4 mb-3 mb-md-0">
  <form method="GET" action="mrbproducts.php" class="d-flex">
    <div class="input-group">
      <input type="text" class="form-control search-bar" name="search-input" id="searchInput" style="font-family: 'Inter', sans-serif; font-size: 0.875rem; border-radius: 20px;" placeholder="Search products..."/>
      <button class="btn btn-outline-secondary d-none" id="hiddenSubmit" type="submit" name="search-submit">
      </button>
    </div>
  </form>
  <?php if(isset($searchError)): ?>
    <script>alert('<?php echo $searchError; ?>');</script>
  <?php endif; ?>
</div>
<div class="col-12 col-md-8 d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-3 ps-3 ps-sm-4 ps-md-0 text-center text-md-start">
  <a href="landpage.php" class="nav-text-2 text-decoration-none fw-bold">Home</a>

  <div class="dropdown d-flex align-items-center">
    <a href="#" class="nav-text-2 text-decoration-none fw-bold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
      Products
    </a>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item nav-text-2 fw-bold" href="mrbproducts.php">All Products</a></li>
      <li><a class="dropdown-item nav-text-2 fw-bold" href="mrbproducts.php?type=pork">Pork Products</a></li>
      <li><a class="dropdown-item nav-text-2 fw-bold" href="mrbproducts.php?type=chicken">Chicken Products</a></li>
      <li><a class="dropdown-item nav-text-2 fw-bold" href="mrbproducts.php?type=beef">Beef Products</a></li>
    </ul>
  </div>

  <a href="household.php" class="nav-text-2 text-decoration-none fw-bold">Recipes & Guides</a>
  <a href="maintenance.php" class="nav-text-2 text-decoration-none fw-bold">Quality Assurance</a>
</div>
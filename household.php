<?php
    
    session_start();
    include 'connection.php';
    
    if(isset($_GET['search-submit'])){
      if(!empty($_GET['search-input'])) {
        $searchInput = mysqli_real_escape_string($conn, $_GET['search-input']);
        header("Location: household.php?query=" . urlencode($searchInput));
        exit();
      } else {
        $searchError = "Please enter a search term.";
      }
    }
    if(isset($_GET['query']) && !empty($_GET['query'])) {
        $searchTerm = mysqli_real_escape_string($conn, $_GET['query']);
        // Count for search results
        $countQuery = "SELECT COUNT(*) as total FROM mrb_fireex WHERE prod_type = 'Other Products' AND (prod_name LIKE '%$searchTerm%')";
    } else {
        // Count for regular view
        $countQuery = "SELECT COUNT(*) as total FROM mrb_fireex WHERE prod_type = 'Other Products'";
    }
    
    // Execute the appropriate count query
    $countResult = mysqli_query($conn, $countQuery);
    $displayCount = $countResult->fetch_assoc()['total'] . " items";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-...your-key..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="swiper-bundle.min.css">
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="household.css">
  <style>
    .hover-shadow {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-shadow:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(139, 0, 0, 0.2) !important;
    }
    .card-title i {
      font-size: 0.9rem;
    }
  </style>
  
</head>

<body class="d-flex flex-column" style="padding: 0;">

  <?php
  if (isset($_SESSION['user_id'])) {
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
    </div>
  </div>


<!-- Recipes Section -->
<section class="container my-5">
  <h3 class="h2 product-title-1 text-center mb-4">DELICIOUS MEAT RECIPES<hr></h3>
  <p class="text-center mb-5">Try these amazing recipes with our premium quality meats!</p>
  
  <div class="row g-4">
    <!-- Recipe 1: Classic Beef Steak -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Classic Beef Steak
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 30 minutes | <i class="bi bi-people"></i> 2 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>2 beef steaks (200g each)</li>
            <li>2 tbsp olive oil</li>
            <li>2 cloves garlic, minced</li>
            <li>Salt and pepper to taste</li>
            <li>1 tbsp butter</li>
            <li>Fresh rosemary sprigs</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Season steaks with salt and pepper</li>
            <li>Heat oil in a pan over high heat</li>
            <li>Sear steaks 4-5 minutes per side</li>
            <li>Add butter, garlic, and rosemary</li>
            <li>Baste and rest for 5 minutes</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Recipe 2: Chicken Adobo -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Filipino Chicken Adobo
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 45 minutes | <i class="bi bi-people"></i> 4 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>1 kg chicken pieces</li>
            <li>1/2 cup soy sauce</li>
            <li>1/4 cup vinegar</li>
            <li>1 head garlic, crushed</li>
            <li>1 tsp peppercorns</li>
            <li>3 bay leaves</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Combine all ingredients in a pot</li>
            <li>Marinate chicken for 30 minutes</li>
            <li>Bring to boil, then simmer 30 minutes</li>
            <li>Remove chicken, reduce sauce</li>
            <li>Serve hot with rice</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Recipe 3: Pork Chops -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Garlic Herb Pork Chops
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 25 minutes | <i class="bi bi-people"></i> 3 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>3 pork chops (1 inch thick)</li>
            <li>3 cloves garlic, minced</li>
            <li>2 tbsp mixed herbs</li>
            <li>2 tbsp olive oil</li>
            <li>Salt and pepper</li>
            <li>Lemon wedges</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Mix garlic, herbs, oil, salt, pepper</li>
            <li>Coat pork chops with mixture</li>
            <li>Let marinate 15 minutes</li>
            <li>Pan-fry 6-7 minutes per side</li>
            <li>Serve with lemon wedges</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Recipe 4: Ground Beef Tacos -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Easy Beef Tacos
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 20 minutes | <i class="bi bi-people"></i> 4 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>500g ground beef</li>
            <li>1 onion, diced</li>
            <li>2 tbsp taco seasoning</li>
            <li>Taco shells</li>
            <li>Lettuce, cheese, tomatoes</li>
            <li>Sour cream & salsa</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Brown beef with onions</li>
            <li>Add taco seasoning and water</li>
            <li>Simmer until thickened</li>
            <li>Warm taco shells</li>
            <li>Assemble with toppings</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Recipe 5: BBQ Ribs -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Tender BBQ Ribs
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 2 hours | <i class="bi bi-people"></i> 4 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>1 rack pork ribs</li>
            <li>1 cup BBQ sauce</li>
            <li>2 tbsp brown sugar</li>
            <li>1 tbsp paprika</li>
            <li>Salt and pepper</li>
            <li>1 tsp garlic powder</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Remove membrane from ribs</li>
            <li>Mix dry rub ingredients</li>
            <li>Coat ribs, wrap in foil</li>
            <li>Bake at 300°F for 90 minutes</li>
            <li>Brush with BBQ sauce, grill briefly</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Recipe 6: Grilled Chicken Breast -->
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 shadow-sm hover-shadow">
        <div class="card-body">
          <h5 class="card-title" style="color: #8B0000; font-weight: bold;">
            <i class="bi bi-star-fill"></i> Lemon Herb Chicken
          </h5>
          <p class="text-muted mb-3"><i class="bi bi-clock"></i> 35 minutes | <i class="bi bi-people"></i> 4 servings</p>
          
          <h6 class="fw-bold">Ingredients:</h6>
          <ul class="small">
            <li>4 chicken breasts</li>
            <li>Juice of 2 lemons</li>
            <li>3 tbsp olive oil</li>
            <li>Fresh thyme & oregano</li>
            <li>3 cloves garlic</li>
            <li>Salt and pepper</li>
          </ul>
          
          <h6 class="fw-bold">Instructions:</h6>
          <ol class="small">
            <li>Mix lemon, oil, herbs, garlic</li>
            <li>Marinate chicken 20 minutes</li>
            <li>Preheat grill to medium-high</li>
            <li>Grill 6-7 minutes per side</li>
            <li>Let rest 5 minutes before serving</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <!-- Cooking Tips Section -->
  <div class="row mt-5">
    <div class="col-12">
      <div class="card shadow-sm" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
        <div class="card-body p-4">
          <h4 class="text-center mb-4" style="color: #8B0000;">
            <i class="bi bi-lightbulb-fill"></i> Cooking Tips from Meat Shop
          </h4>
          <div class="row">
            <div class="col-md-4 mb-3">
              <div class="d-flex align-items-start">
                <i class="bi bi-thermometer-half fs-3 me-3" style="color: #8B0000;"></i>
                <div>
                  <h6 class="fw-bold">Temperature Matters</h6>
                  <p class="small mb-0">Let meat reach room temperature before cooking for even results. Use a meat thermometer for perfect doneness.</p>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="d-flex align-items-start">
                <i class="bi bi-clock-history fs-3 me-3" style="color: #8B0000;"></i>
                <div>
                  <h6 class="fw-bold">Rest Your Meat</h6>
                  <p class="small mb-0">Always let cooked meat rest 5-10 minutes before cutting. This keeps the juices inside for maximum flavor.</p>
                </div>
              </div>
            </div>
            <div class="col-md-4 mb-3">
              <div class="d-flex align-items-start">
                <i class="bi bi-droplet-fill fs-3 me-3" style="color: #8B0000;"></i>
                <div>
                  <h6 class="fw-bold">Pat It Dry</h6>
                  <p class="small mb-0">Pat meat dry with paper towels before seasoning and cooking for better browning and crispy edges.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // drag functionality, constrain inside screen
    let offsetX, offsetY, dragTarget = null;

    function startDrag(e, bar) {
      dragTarget = bar.parentElement;
      const screen = document.getElementById('screen');
      offsetX = e.clientX - dragTarget.offsetLeft - screen.offsetLeft;
      offsetY = e.clientY - dragTarget.offsetTop - screen.offsetTop;
      document.onmousemove = dragWindow;
      document.onmouseup = () => {
        dragTarget = null;
        document.onmousemove = null;
      };
    }

    function dragWindow(e) {
      if (!dragTarget) return;
      const screen = document.getElementById('screen');
      let newX = e.clientX - offsetX - screen.offsetLeft;
      let newY = e.clientY - offsetY - screen.offsetTop;

      // boundaries so window stays inside screen
      newX = Math.max(0, Math.min(newX, screen.clientWidth - dragTarget.clientWidth));
      newY = Math.max(0, Math.min(newY, screen.clientHeight - dragTarget.clientHeight));

      dragTarget.style.left = newX + 'px';
      dragTarget.style.top = newY + 'px';
    }

    // clock update
    function updateClock() {
      const clock = document.getElementById('clock');
      const now = new Date();
      let hours = now.getHours();
      const minutes = now.getMinutes();
      const ampm = hours >= 12 ? 'PM' : 'AM';
      hours = hours % 12 || 12;
      clock.textContent = `${hours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
    }
    setInterval(updateClock, 1000);
    updateClock();


    // toggle info panel visibility
    document.getElementById('toggleInfo').addEventListener('click', () => {
      const infoPanel = document.getElementById('infoPanel');
      if (infoPanel.style.display === 'block') {
        infoPanel.style.display = 'none';
      } else {
        infoPanel.style.display = 'block';
      }
    });

    function populatePassMethodInfo(items) {
      const infoList = document.getElementById('infoList');
      infoList.innerHTML = ''; // clear existing content

      items.forEach(item => {
        const li = document.createElement('li');

        if (item.title) {
          const title = document.createElement('strong');
          title.textContent = item.title;
          li.appendChild(title);
        }

        if (item.content) {
          const paragraph = document.createElement('p');
          paragraph.style.margin = '5px 0 0 0';
          paragraph.textContent = item.content;
          li.appendChild(paragraph);
        }

        infoList.appendChild(li);
      });
    }

    const passMethodDetails = [{
        title: "What is the PASS Method?",
        content: "PASS is an acronym that helps you remember the correct technique to operate a fire extinguisher quickly and safely. It stands for Pull, Aim, Squeeze, and Sweep."
      },
      {
        title: "Why is the PASS Method Important?",
        content: "Using a fire extinguisher correctly is crucial in effectively putting out small fires before they spread. The PASS method ensures you apply the extinguishing agent properly to control the fire while maintaining safety."
      },
      {
        title: "Step 1: Pull",
        content: "Pull the pin to unlock the operating lever. This pin prevents accidental discharge and must be removed before use."
      },
      {
        title: "Step 2: Aim",
        content: "Aim the nozzle at the base of the fire, not the flames. Targeting the fuel source is key to stopping the fire from spreading."
      },
      {
        title: "Step 3: Squeeze",
        content: "Squeeze the handle firmly to release the extinguishing agent. Make sure to control the flow and maintain steady pressure."
      },
      {
        title: "Step 4: Sweep",
        content: "Sweep the nozzle side to side across the fire’s base, covering the entire area evenly until the fire is fully extinguished."
      },
      {
        title: "Common Mistakes to Avoid",
        content: "Avoid aiming at the flames, which won’t put out the fire effectively. Don’t stand too close or too far from the fire—maintain a safe distance of about 6-8 feet. Never turn your back on a fire that’s not fully out."
      },
      {
        title: "Safety Tips",
        content: "Always ensure you have a clear exit behind you before attempting to use an extinguisher. If the fire grows or you feel unsafe, evacuate immediately and call emergency services."
      },
      {
        title: "When to Use a Fire Extinguisher",
        content: "Only use a fire extinguisher on small, contained fires such as kitchen grease fires or trash can fires. For larger fires, evacuate immediately and wait for professional firefighters."
      }
    ];

    populatePassMethodInfo(passMethodDetails);

    function updateSort(sortOrder, labelText) {
    // Update the dropdown label
    document.getElementById('dropdownLabel').textContent = labelText;
    
    // Get the current search query if any
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('query');
    
    // Build the new URL with sort parameter
    let url = 'household.php?sort=' + encodeURIComponent(sortOrder);
    
    // Add the search query if it exists
    if (searchQuery) {
        url += '&query=' + encodeURIComponent(searchQuery);
    }
    
    // Redirect to the new URL
    window.location.href = url;
}
  </script>
  <script>
      function setDropdownLabel(text) {
        document.getElementById('dropdownLabel').textContent = text;
      }
    </script>
    <script>
      function updateSort(orderBy, label) {
        // Update dropdown label
        $('#dropdownLabel').text(label);
        
        // Show loading indicator
        $('.product-grid').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        
        // Make AJAX request
        $.post('fetch_products3.php', { order: orderBy }, function(data) {
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
    <script src="chat-message.js"></script>
</body>

</html>
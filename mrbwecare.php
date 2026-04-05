<?php
    
    session_start();
    include 'connection.php';
    
    if(isset($_GET['search-submit'])){
      if(!empty($_GET['search-input'])) {
        $searchInput = mysqli_real_escape_string($conn, $_GET['search-input']);
        header("Location: mrbproducts1.php?query=" . urlencode($searchInput));
        exit();
      } else {
        $searchError = "Please enter a search term.";
      }
    }
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
  <link rel="stylesheet" href="css/magnific-popup.css">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="mrbwecare.css">
  <link rel="stylesheet" href="normalize.css">
  <link rel="stylesheet" href="vendor.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


</head>

<body>
  <?php
  if (isset($_SESSION['user_id'])) {
    include 'headerinned.php';
  } else {
    include 'headerout.php';
  }
  echo "<div class='py-2'>";
      include 'mininavbar-wecare.php';  
  echo  "</div>";
  
  ?>
  <div class="container-fluid header-bg py-5 mb-3 mt-4 wow fadeIn" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-12 col-md-10 text-center text-md-start">
          <h1 class="mrb-title mb-3 animated slideInDown">
            With MRB, You’re Cared For.
          </h1>
        </div>
      </div>
    </div>
  </div>
  <section class="intro py-4">
    <div class="container container px-3 px-md-4">
      <div class="row justify-content-center">
        <div class="col-12 col-md-4 mb-4 mb-md-0">
          <div class="intro-box w-100 d-flex">
            <div class="icon d-flex align-items-center justify-content-center">
              <span class="fa fa-phone"></span>
            </div>
            <div class="text px-4">
              <h4 class="mb-0">Call us: 046-4134962</h4>
              <p class="mt-2" style="color: #525150;">B.2 L.1 Joy St. City Land Subd Brgy. Mabuhay Carmona, Cavite</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4 mb-4 mb-md-0">
          <div class="intro-box w-100 d-flex">
            <div class="icon d-flex align-items-center justify-content-center">
              <span class="fa fa-clock-o"></span>
            </div>
            <div class="text px-4">
              <h4 class="mb-0">Office Hours</h4>
              <p class="mt-2" style="color: #525150;">Monday - Friday 8:00 AM - 5:00 PM</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="intro-box w-100 text-center">
            <p class="mb-0 retro-box1">
              <a href="#" class="retro-btn1" style="text-decoration: none;">Make an Appointment</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
  <br>
  <section data-aos="fade-up">
    <div class="section light-bg">
      <div class="container px-3 px-md-4">
        <div class="section-title mt-2">
          <small>WHO WE ARE</small>
          <div class="d-flex align-items-center">
            <h3 class="mb-0 mr-3 tab-title">Empowering Safer Communities with MRBWeCare</h3>
          </div>
        </div>
        <ul class="nav nav-tabs nav-justified mt-4" role="tablist">
          <li class="nav-item">
            <a class="nav-link tab-title" data-toggle="tab" href="#mission">Mission</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active tab-title" data-toggle="tab" href="#vision">Vision</a>
          </li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="mission">
            <div class="d-flex flex-column flex-lg-row">
              <img src="img/mission.jpg" alt="graphic" class="img-fluid rounded mx-auto d-md-block d-lg-inline-block align-self-start mr-lg-5 mb-5 mb-lg-0">
              <div class="tabpane-text">
                <h2>At Meat Shop,</h2>
                <p class="lead">
                  We are driven by a mission to provide the finest quality meats to our community—sourcing responsibly, ensuring freshness, and delivering exceptional taste.
                </p>
                <p>
                  Our commitment is rooted in delivering cutting-edge fire protection technologies and tailored safety systems that ensure the highest level of reliability and performance. We strive to meet the diverse and evolving needs of our clients by offering solutions that go beyond compliance—providing true peace of mind.
                </p>
                <p>
                  Through continuous innovation, strategic partnerships, and a customer-first approach, we aim to safeguard lives, properties, and the environment worldwide. We hold ourselves to the highest standards of excellence, integrity, and service, ensuring that every engagement reflects our passion for safety and our promise of dependability.
                </p>
                <hr>
              </div>
            </div>
          </div>
          <div class="tab-pane fade" id="vision">
            <div class="d-flex flex-column flex-lg-row">
              <div class="tabpane-text">
                <h2>Our Vision</h2>
                <p class="lead">To be the premier destination for quality meats, known for our commitment to freshness, sustainability, and customer satisfaction.</p>
                <p>
                  At Meat Shop, we envision a community where every meal is made special with premium quality meats. Our goal is to build lasting relationships with our customers through trust, quality, and exceptional service.
                </p>
                <p>
                  We are driven by a future where fire safety is seamlessly integrated into everyday life—smart, responsive, and accessible to all. Through continuous innovation, the MRBWeCare initiative, and a passion for saving lives, we are building safer communities and setting new global standards in fire safety solutions.
                  <hr>
                </p>
              </div>
              <img src="img/vision.jpg" alt="graphic" class="img-fluid rounded mx-auto d-md-block d-lg-inline-block align-self-start mr-lg-5 mb-5 mb-lg-0">
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>


  <br>
  <br>

  <section>
    <div class="container-fluid features mb-2">
      <div class="container my-4">
        <div class="notes-container d-flex flex-wrap justify-content-center gap-3">

          <div class="sticky-note p-3 shadow rounded text-center" style="width: 250px;">
            <div class="note-status fw-bold text-primary">Reminder</div>
            <div class="note-title mt-2">Keep extinguishers near exits for safety.</div>
          </div>

          <div class="sticky-note p-3 shadow rounded text-center" style="width: 250px;">
            <div class="note-status fw-bold text-danger">Alert</div>
            <div class="note-title mt-2">Test smoke alarms monthly to ensure they work.</div>
          </div>

          <div class="sticky-note p-3 shadow rounded text-center" style="width: 250px;">
            <div class="note-status fw-bold text-warning">Notice</div>
            <div class="note-title mt-2">Store flammable items away from heat sources.</div>
          </div>

          <div class="sticky-note p-3 shadow rounded text-center" style="width: 250px;">
            <div class="note-status fw-bold text-info">Heads-up</div>
            <div class="note-title mt-2">Plan and practice a fire escape route with your family.</div>
          </div>

        </div>
      </div>
      <br>
      <br>

      <!-- Main Post Section Start -->
      <div class="container-fluid py-5 border">
        <div class="container py-5">
          <div class="row g-4">
            <div class="col-lg-7 col-xl-8 mt-0">
              <div class="position-relative overflow-hidden rounded">
                <img src="img/landpage2.png" class="img-fluid rounded img-zoomin w-100" alt="">
              </div>

              <div class="border-bottom py-3">
                <a href="#" class="mb-0 link-hover about-company">ABOUT OUR COMPANY</a>
              </div>

              <p class="mt-3 mb-4 about-text-sm" style="font-weight: 370;">
                Meat Shop is a family-owned business specializing in premium quality meats and meat products. Established in 2019, the company is driven by a mission to deliver fresh, high-quality meat products to our valued customers.
              </p>

              <div class="row mt-3">
                <div class="col-md-5 mb-3">
                  <img src="img/landpage2.png" class="img-fluid rounded w-100" style="height: 200px; object-fit: cover;" alt="Additional visual" alt="">
                </div>
                <div class="col-md-7 mb-3">
                  <img src="img/landpage1.png" class="img-fluid rounded w-100" style="height: 200px; object-fit: cover;" alt="Additional visual">
                </div>
              </div>
            </div>

            <div class="col-lg-5 col-xl-4">
              <div class="bg-light rounded p-4 pt-0">
                <div class="row g-4">
                  <div class="col-12">
                    <h4 class="mb-3 about-company" style="font-size: 1.4rem;">ABOUT MEAT SHOP</h4>
                    <p class="mb-2 about-text-sm" style="font-weight: 370;">Meat Shop, founded in 2019, is a family-operated business specializing in premium quality meats and meat products. Led by passionate meat specialists, the company is driven by quality and customer satisfaction.</p>
                    <p class="mb-4 about-text-sm" style="font-weight: 370;">We are committed to excellence in providing fresh, responsibly-sourced meats to our community.</p>
                  </div>
                  <div class="col-12">
                    <div class="container info-2 xp-tab">
                      <div class="xp-tab-header">
                        <span class="xp-tab-title">About Us - Meat Shop</span>
                      </div>
                      <div class="xp-tab-content">
                        <p class="mb-4">
                          Meat Shop is a trusted name in quality meats, dedicated to providing premium cuts and meat products through excellent customer service.
                          We are committed to helping families and businesses enjoy the finest meats with exceptional quality and convenience.
                        </p>
                        <hr>
                        <p class="mb-4">
                          Our shop offers a wide range of quality meats, including beef, pork, chicken, and specialty cuts.
                          With a focus on freshness, quality, and convenience, Meat Shop ensures you get the best products for your needs.
                        </p>
                      </div>
                    </div>
                  </div>
                 
                  <div class="col-12">
                    <br>
                    <div class="rounded overflow-hidden">
                      <img src="img/landpage5.png" class="img-fluid rounded img-zoomin w-100" alt="Fire Extinguisher Product">
                    </div>
                    <small class="d-block mt-2 text-muted"><i class="fa fa-certificate me-1"></i> ISO-Certified Quality | Trusted Nationwide</small>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
      <!-- Main Post Section End -->
  </section>
  
  <?php
  include 'user-footer.php';
  ?>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
  <script src=""></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script src="aos.js"></script>
  <script src="bootstrap.bundle.min.js"></script>
  <script src="chat-message.js"></script>
</body>

</html>
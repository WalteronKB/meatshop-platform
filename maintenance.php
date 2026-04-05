<?php
session_start();
include 'connection.php';

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
    <link rel="stylesheet" href="maintenance.css">
</head>

<body class="d-flex flex-column" style="padding: 0;">

    <?php
    if (isset($_SESSION['user_id'])) {
        include 'headerinned.php';
    } else {
        include 'headerout.php';
    }
    ?>

    <div class="py-2">
       <?php
        include 'mininavbar-wecare.php';
        ?> 
    </div>
    

    <section>
        <div class="container maintenance-header text-center  fs-md-2 fs-lg-3">
            <p class="mt-4">Quality Assurance & Food Safety Standards</p>
            <span class="d-block mx-auto mb-3" style="position: relative;">
                At Meat Shop, we are committed to delivering the highest quality meat products. Our comprehensive quality assurance program ensures that every product meets strict food safety standards, from sourcing to delivery. We follow rigorous inspection protocols, maintain proper storage temperatures, and adhere to all health regulations to guarantee your family's safety.
            </span>
        </div>
    </section>

    <!-- Quality Standards Section -->
    <section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
        <div class="container">
            <h2 class="text-center mb-4" style="color: #8B0000; font-weight: 700;">Our Quality Standards</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-check" style="font-size: 3rem; color: #8B0000;"></i>
                            </div>
                            <h5 class="card-title fw-bold">Certified Sources</h5>
                            <p class="card-text">All our meat comes from certified farms and suppliers that meet stringent animal welfare and quality standards. Regular audits ensure compliance.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-thermometer-snow" style="font-size: 3rem; color: #8B0000;"></i>
                            </div>
                            <h5 class="card-title fw-bold">Cold Chain Management</h5>
                            <p class="card-text">Temperature-controlled storage and transport maintain freshness. Our facilities maintain -18°C for frozen and 0-4°C for fresh products.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-clipboard2-check" style="font-size: 3rem; color: #8B0000;"></i>
                            </div>
                            <h5 class="card-title fw-bold">Daily Inspections</h5>
                            <p class="card-text">Every product undergoes visual inspection for quality, color, texture, and packaging integrity before reaching our customers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Food Safety Checklist -->
    <section>
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-10 col-lg-9 col-xl-8">
                    <div class="receipt1">
                        <h2>Quality Assurance Checklist</h2>
                        <div class="subheader">Daily Food Safety & Quality Control Report</div>

                        <div class="section">
                            <div class="section-title">1. Product Receiving & Inspection</div>
                            <table class="tasks">
                                <tr>
                                    <th>Inspection Point</th>
                                    <th>Standard</th>
                                    <th>Status</th>
                                </tr>
                                <tr>
                                    <td>Temperature upon delivery</td>
                                    <td>Fresh: 0-4°C / Frozen: -18°C or below</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Packaging integrity</td>
                                    <td>No tears, punctures, or contamination</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Color and appearance</td>
                                    <td>Bright red (beef), pink (pork/chicken), no discoloration</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Odor check</td>
                                    <td>Fresh smell, no off odors or sourness</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Expiration dates verified</td>
                                    <td>Minimum 3 days shelf life for fresh products</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="section">
                            <div class="section-title">2. Storage & Handling Protocols</div>
                            <table class="tasks">
                                <tr>
                                    <th>Protocol</th>
                                    <th>Standard</th>
                                    <th>Status</th>
                                </tr>
                                <tr>
                                    <td>Refrigerator temperature</td>
                                    <td>0-4°C maintained consistently</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Freezer temperature</td>
                                    <td>-18°C or below</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>FIFO rotation</td>
                                    <td>First In, First Out system implemented</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Cross-contamination prevention</td>
                                    <td>Raw/cooked products separated, proper wrapping</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Equipment sanitization</td>
                                    <td>Cutting boards, knives cleaned after each use</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="section">
                            <div class="section-title">3. Staff Hygiene & Safety</div>
                            <table class="tasks">
                                <tr>
                                    <th>Requirement</th>
                                    <th>Standard</th>
                                    <th>Status</th>
                                </tr>
                                <tr>
                                    <td>Hand washing compliance</td>
                                    <td>Before handling, after breaks, regularly during shifts</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Protective equipment</td>
                                    <td>Gloves, hairnets, clean aprons worn</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Food handler certification</td>
                                    <td>All staff certified and up-to-date</td>
                                    <td class="status-options">
                                        <label><input type="checkbox" /> ✓</label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="footer1">
                            Meat Shop Quality Assurance • Contact: (555) 123-4567 • quality@meatshop.com
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Customer Safety Tips -->
    <section class="py-5" style="background-color: #fff;">
        <div class="container">
            <h2 class="text-center mb-4" style="color: #8B0000; font-weight: 700;">Safe Handling Tips for Customers</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-thermometer" style="font-size: 2rem; color: #8B0000; margin-right: 15px;"></i>
                                <h5 class="mb-0 fw-bold">Keep Cold</h5>
                            </div>
                            <p class="mb-0">Refrigerate meat within 2 hours of purchase. Use cooler bags for transport during hot weather.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-droplet" style="font-size: 2rem; color: #8B0000; margin-right: 15px;"></i>
                                <h5 class="mb-0 fw-bold">Separate Raw</h5>
                            </div>
                            <p class="mb-0">Store raw meat in sealed containers on bottom shelf to prevent dripping onto other foods.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-fire" style="font-size: 2rem; color: #8B0000; margin-right: 15px;"></i>
                                <h5 class="mb-0 fw-bold">Cook Thoroughly</h5>
                            </div>
                            <p class="mb-0">Use meat thermometer: Poultry 74°C, Ground meat 71°C, Whole cuts 63°C minimum.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-clock-history" style="font-size: 2rem; color: #8B0000; margin-right: 15px;"></i>
                                <h5 class="mb-0 fw-bold">Follow Dates</h5>
                            </div>
                            <p class="mb-0">Respect use-by dates. Freeze if not using within 2-3 days of purchase.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Certifications Section -->
    <section class="py-5" style="background: linear-gradient(135deg, #8B0000 0%, #5C0000 100%);">
        <div class="container">
            <h2 class="text-center mb-4 text-white fw-bold">Our Certifications & Compliance</h2>
            <div class="row text-center text-white g-4">
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="bi bi-award-fill" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0 fw-bold">HACCP Certified</p>
                        <small>Hazard Analysis Critical Control Points</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="bi bi-patch-check-fill" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0 fw-bold">FDA Approved</p>
                        <small>Food & Drug Administration Standards</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="bi bi-clipboard2-pulse-fill" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0 fw-bold">ISO 22000</p>
                        <small>Food Safety Management System</small>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3">
                        <i class="bi bi-heart-pulse-fill" style="font-size: 3rem;"></i>
                        <p class="mt-3 mb-0 fw-bold">Halal Certified</p>
                        <small>Approved Halal Processing</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="chat-message.js"></script>
</body>

</html>
<?php
include 'connection.php';
// Check if the user is logged in

$default_footer_iframe_src = "https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d241.62684092509116!2d121.04167370476465!3d14.30972142281377!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d7a776fe8f03%3A0x9b6dc697fb851ceb!2sMRB%20Estelope%20Trading%20%26%20Services%20Inc.%20(%20LOTUS%20FIRE%20EXTINGUISHER%20)!5e0!3m2!1sen!2sph!4v1746455235142!5m2!1sen!2sph";
$footer_iframe_src = $default_footer_iframe_src;

if (isset($dynamic_footer_iframe_src) && is_string($dynamic_footer_iframe_src) && trim($dynamic_footer_iframe_src) !== '') {
  $footer_iframe_src = trim($dynamic_footer_iframe_src);
}

$current_chat_shop_id = isset($current_chat_shop_id) ? (int)$current_chat_shop_id : 0;
$current_chat_product_id = isset($current_chat_product_id) ? (int)$current_chat_product_id : 0;
$chat_scope_sql = '';
if ($current_chat_shop_id > 0) {
  $chat_scope_sql = " AND shop_id = {$current_chat_shop_id}";
}

$footer_contact_address = 'Blk. 2 Lot 1 Joy St. Cityland Subdivision, Brgy Mabuhay, Carmona, Cavite';
$footer_contact_email = 'admin@meatshop.com';
$footer_contact_phone = '+6346-4134962 / +63917-8011545';

if ($current_chat_shop_id > 0) {
  $shop_contact_sql = "SELECT store_address, business_email, business_phone FROM approved_shops WHERE approved_shop_id = ? LIMIT 1";
  $shop_contact_stmt = mysqli_prepare($conn, $shop_contact_sql);
  if ($shop_contact_stmt) {
    mysqli_stmt_bind_param($shop_contact_stmt, 'i', $current_chat_shop_id);
    mysqli_stmt_execute($shop_contact_stmt);
    $shop_contact_result = mysqli_stmt_get_result($shop_contact_stmt);
    if ($shop_contact_result && mysqli_num_rows($shop_contact_result) > 0) {
      $shop_contact_row = mysqli_fetch_assoc($shop_contact_result);
      if (!empty($shop_contact_row['store_address'])) {
        $footer_contact_address = trim((string)$shop_contact_row['store_address']);
      }
      if (!empty($shop_contact_row['business_email'])) {
        $footer_contact_email = trim((string)$shop_contact_row['business_email']);
      }
      if (!empty($shop_contact_row['business_phone'])) {
        $footer_contact_phone = trim((string)$shop_contact_row['business_phone']);
      }
    }
    mysqli_stmt_close($shop_contact_stmt);
  }
}
?>

<footer id="contact_id">
    <div class="container-fluid">
      <div class="container py-5">
        <div class="bg-light rounded p-sm-0 p-lg-5" style="border-radius: 5px;">
          <div class="row g-4 align-items-stretch">

            <!-- Left Column: MESSAGE US DIRECTLY -->
            <div class="col-lg-6 col-md-12 d-flex justify-content-center">
              <div class="container d-flex flex-column justify-content-between h-100" style="padding: 0;">
                <h2 class="text-center mb-4 contact-info-1">MESSAGE US DIRECTLY</h2>
                <span class="text-center mb-4 contact-info-2" style="transform: translateY(-10px);">
                  Our representative will reply as soon as possible.
                </span>
                <hr>
                <p class="text-center mb-4 contact-info-2">
                  We are committed to providing exceptional customer service and technical support. Feel free to reach out to us with any questions or inquiries, and our team will be happy to assist you.
                </p>

                <div class="chatbox mb-4 d-flex flex-column align-items-center" style="margin: 0 auto;">
                  <img src="img/prof.png" alt="" class="rounded-circle replier-img mb-2" style="width: 70px; height: 70px;">
                  <p class="replier m-0 white-text text-center" style="color: #fff">Admin</p>
                  <p class="replier-job">Manager at Meat Shop</p>
                  <hr class="w-100">
                  <div class="chats w-100">
                    <?php
                      if (isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        $query = "SELECT * FROM mrb_messages WHERE user_id = '$user_id'{$chat_scope_sql} ORDER BY message_datesent ASC LIMIT 100";
                        $result = mysqli_query($conn, $query);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                          echo "<script>var initialMessageIds = [];</script>";
                          
                          while ($row = mysqli_fetch_assoc($result)) {
                            $messageClass = '';
                            if ($row['message_type'] == 'user-chat') {
                              $messageClass = 'chat-message';
                            } else {
                              $messageClass = 'chat-reply';
                            }

                            $message = htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8');
                            $dateSent = date('h:i A', strtotime($row['message_datesent']));
                            $messageId = $row['message_id'];
                            
                            // Add message ID to the JavaScript array
                            echo "<script>initialMessageIds.push('{$messageId}');</script>";
                            
                            echo "<div class='$messageClass'>
                                    <p class='message'>{$message}</p>
                                  </div>
                                  <span class='chat-time message-time white-text' style='color:white;'>{$dateSent}</span>";
                          }
                        } else {
                          echo "<div class='text-center my-4'>
                                  <p class='text-muted'>No messages yet. Start a conversation!</p>
                                </div>";
                          echo "<script>var initialMessageIds = [];</script>";
                        }
                      } else {
                        echo "<div class='text-center my-4'>
                                <p class='text-muted'>Please log in to chat with us.</p>
                              </div>";
                        echo "<script>var initialMessageIds = [];</script>";
                      }
                      ?>
                  </div>
                </div>

                <div class="">
                  <?php
                  // Note: Chat form submission is now handled by JavaScript (chat-message.js)
                  // This PHP fallback code has been commented out to prevent page reloads
                  ?>
                  <form action="#" class="row usermessagebox gx-2 w-100 align-items-center justify-content-center" id="chatForm" method="POST">
                    <?php if ($current_chat_shop_id > 0): ?>
                      <input type="hidden" name="chat-shop-id" value="<?php echo (int)$current_chat_shop_id; ?>">
                    <?php endif; ?>
                    <?php if ($current_chat_product_id > 0): ?>
                      <input type="hidden" name="chat-product-id" value="<?php echo (int)$current_chat_product_id; ?>">
                    <?php endif; ?>
                    <div class="col-10 col-sm-8 col-md-6 col-lg-5">
                      <input type="text" name="chat-text-field" class="form-control chat-textfield" placeholder="Type your message...">
                    </div>
                    <div class="col-2 d-flex justify-content-end">
                      <button type="submit" name="chat-text-button" class="btn btn-primary w-100 send-button">
                        <i class="bi bi-send text-light send-icon-products"></i>
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>


            <div class="col-lg-6 col-md-12 d-flex">
              <div class="container d-flex flex-column justify-content-between h-100">
                <h2 class="mb-4 contact-info-1">General Customer Care & Technical Support</h2>
                <p class="mb-4 contact-info-2" style="text-align: end;">You can find us on the map or use the contact details below for direct communication. We’re happy to help you anytime.</p>

                <!-- Google Map -->
                <div class="rounded mb-4">
                  <iframe class="google-map rounded w-100"
                    src="<?php echo htmlspecialchars($footer_iframe_src, ENT_QUOTES); ?>"
                    width="100%" height="360" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                  </iframe>
                </div>

                <!-- Contact Info Boxes -->
                <div class="pt-2">
                  <div class="row g-3 small">
                    <div class="col-md-6">
                      <div class="d-flex align-items-center p-3 rounded bg-white h-100">
                        <i class="fas fa-map-marker-alt fa-lg text-primary me-3"></i>
                        <div>
                          <h6 class="mb-1 contact-info-2">Address</h6>
                          <p class="mb-0"><?php echo htmlspecialchars($footer_contact_address, ENT_QUOTES); ?></p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex align-items-center p-3 rounded bg-white h-100">
                        <i class="fas fa-envelope fa-lg text-primary me-3"></i>
                        <div>
                          <h6 class="mb-1 contact-info-2">Mail Us</h6>
                          <p class="mb-0"><?php echo htmlspecialchars($footer_contact_email, ENT_QUOTES); ?></p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex align-items-center p-3 rounded bg-white h-100">
                        <i class="fas fa-phone-alt fa-lg text-primary me-3"></i>
                        <div>
                          <h6 class="mb-1 contact-info-2">Telephone</h6>
                          <p class="mb-0"><?php echo htmlspecialchars($footer_contact_phone, ENT_QUOTES); ?></p>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex align-items-center p-3 rounded bg-white h-100">
                        <i class="fas fa-share-alt fa-lg text-primary me-3"></i>
                        <div>
                          <h6 class="mb-2 contact-info-2">Share</h6>
                          <div class="d-flex">
                            <a class="me-2" href="#"><i class="fab fa-twitter text-dark link-hover"></i></a>
                            <a class="me-2" href="#"><i class="fab fa-facebook-f text-dark link-hover"></i></a>
                            <a class="me-2" href="#"><i class="fab fa-youtube text-dark link-hover"></i></a>
                            <a href="#"><i class="fab fa-linkedin-in text-dark link-hover"></i></a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div> <!-- end row -->
                </div> <!-- end contact info -->
              </div>
            </div>

          </div> <!-- end row -->
        </div> <!-- end bg-light -->
      </div> <!-- end container -->

    </div> <!-- end container-fluid -->
  </footer>
  <footer class="text-center mt-2 small text-muted copyright">
    © 2025 Meat Shop. All rights reserved.
  </footer>


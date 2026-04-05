<?php
session_start();
include 'connection.php';

$appToasts = [];

if (isset($_POST['reg-submit'])) {
  $name = trim($_POST['reg_name']);
  $mname = trim($_POST['reg_mname']);
  $lname = trim($_POST['reg_lname']);
  $contact = trim($_POST['reg_contact']);
  $email = trim($_POST['reg_email']);
  $firstpassword = $_POST['first_reg_pass'];
  $password = $_POST['reg_pass'];

  // Validate inputs
  if (empty($name) || empty($contact) || empty($email) || empty($password)) {
    $_SESSION['reg_error'] = "All fields are required.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }

  $email_check_query = "SELECT user_email FROM mrb_users WHERE user_email = ?";
  $stmt = $conn->prepare($email_check_query);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $email_check_result = $stmt->get_result();

  if ($email_check_result->num_rows > 0) {
    $_SESSION['reg_error'] = "Email address already exists. Please use a different email.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }

  // Check if passwords match
  if ($firstpassword !== $password) {
    $_SESSION['reg_error'] = "Passwords do not match. Please try again.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }

  // Get next user ID safely
  $query = "SELECT MAX(user_id) AS max_id FROM mrb_users";
  $result = $conn->query($query);
  $id = $result->fetch_assoc()['max_id'] + 1;

  // Hash password for security
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Set default values
  $profile_pic = 'Images/profile_pics/anonymous.jpg';
  $user_type = 'user';

  // Insert new user using prepared statement
  $insert_query = "INSERT INTO mrb_users (user_id, user_name, user_mname, user_lname, user_contactnum, user_email, user_password, user_pic, user_dateadded, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
  $stmt = $conn->prepare($insert_query);
  $stmt->bind_param("issssssss", $id, $name, $mname, $lname, $contact, $email, $hashed_password, $profile_pic, $user_type);
  
  if ($stmt->execute()) {
    // Log activity for new account registration
    $escaped_name = mysqli_real_escape_string($conn, $name);
    $escaped_email = mysqli_real_escape_string($conn, $email);
    $activity_desc = "New user account '{$escaped_name}' ({$escaped_email}) was registered";
    $activity_desc_escaped = mysqli_real_escape_string($conn, $activity_desc);
    $log_query = "INSERT INTO mrb_activity_log (activity_desc, activity_type, created_at) VALUES ('$activity_desc_escaped', 'accounts', NOW())";
    mysqli_query($conn, $log_query);
    
    $_SESSION['reg_success'] = true;
    header("Location: mrbloginpage.php?registration=success");
    exit();
  } else {
    $_SESSION['reg_error'] = "Registration failed. Please try again.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }
}

if (isset($_POST['log-submit'])) {
  $email = trim($_POST['log-email'] ?? '');
  $password = $_POST['log-pass'] ?? '';

  if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please fill in all fields.';
    header('Location: mrbloginpage.php?purpose=login');
    exit();
  }

  $query = "SELECT * FROM mrb_users WHERE user_email = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();

    if ($user_data['user_type'] == 'deleted') {
      $_SESSION['login_warning'] = 'Your account is inactive. Please contact support.';
      header('Location: mrbloginpage.php?purpose=login');
      exit();
    }

    $password_valid = false;
    if (password_verify($password, $user_data['user_password'])) {
      $password_valid = true;
    } elseif ($password === $user_data['user_password']) {
      $password_valid = true;
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $update_query = "UPDATE mrb_users SET user_password = ? WHERE user_id = ?";
      $update_stmt = $conn->prepare($update_query);
      $update_stmt->bind_param("si", $hashed_password, $user_data['user_id']);
      $update_stmt->execute();
    }

    if ($password_valid) {
      $usertype = $user_data['user_type'];
      $user_id = $user_data['user_id'];
      $resolved_name_parts = array_filter([
        trim((string)($user_data['user_name'] ?? '')),
        trim((string)($user_data['user_mname'] ?? '')),
        trim((string)($user_data['user_lname'] ?? '')),
      ]);
      $resolved_actor_name = trim(implode(' ', $resolved_name_parts));
      if ($resolved_actor_name === '') {
        $resolved_actor_name = trim((string)($user_data['first_name'] ?? ''));
      }
      if ($resolved_actor_name === '') {
        $resolved_actor_name = trim((string)($user_data['last_name'] ?? ''));
      }
      if ($resolved_actor_name === '') {
        $resolved_actor_name = trim((string)($user_data['user_email'] ?? ''));
      }
      if ($resolved_actor_name === '') {
        $resolved_actor_name = 'Admin';
      }
      $_SESSION['user_id'] = $user_id;
      $_SESSION['user_type'] = $usertype;
      $_SESSION['user_name'] = $resolved_actor_name;

      if ($usertype == 'super_admin') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/super_admin.php");
        exit();
      } elseif ($usertype == 'admin') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/analytics-admin.php");
        exit();
      } elseif ($usertype == 'butcher') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/products-admin.php");
        exit();
      } elseif ($usertype == 'cashier') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/orders-admin.php");
        exit();
      } elseif ($usertype == 'rider') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/orders-admin.php");
        exit();
      } elseif ($usertype == 'finance') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin/finances-admin.php");
        exit();
      } else {
        header("Location: landpage.php");
        exit();
      }
    }
  }

  $_SESSION['login_error'] = 'Invalid email or password. Please try again.';
  header('Location: mrbloginpage.php?purpose=login');
  exit();
}

if (isset($_SESSION['reg_success'])) {
  $appToasts[] = ['message' => 'Registration successful! You can now log in.', 'type' => 'success'];
  unset($_SESSION['reg_success']); // Remove the message after displaying
}
if (isset($_SESSION['reg_error'])) {
  $appToasts[] = ['message' => 'Error: ' . $_SESSION['reg_error'], 'type' => 'error'];
  unset($_SESSION['reg_error']); // Remove the message after displaying
}

// Add this in mrbloginpage.php after existing session messages
if (isset($_SESSION['forgot_success'])) {
  $appToasts[] = ['message' => $_SESSION['forgot_success'], 'type' => 'success'];
    if (isset($_SESSION['reset_link'])) {
        $appToasts[] = ['message' => 'For development: ' . $_SESSION['reset_link'], 'type' => 'info'];
        unset($_SESSION['reset_link']);
    }
    unset($_SESSION['forgot_success']);
}
if (isset($_SESSION['forgot_error'])) {
  $appToasts[] = ['message' => 'Error: ' . $_SESSION['forgot_error'], 'type' => 'error'];
    unset($_SESSION['forgot_error']);
}

if (isset($_SESSION['login_warning'])) {
  $appToasts[] = ['message' => $_SESSION['login_warning'], 'type' => 'warning'];
  unset($_SESSION['login_warning']);
}

if (isset($_SESSION['login_error'])) {
  $appToasts[] = ['message' => $_SESSION['login_error'], 'type' => 'error'];
  unset($_SESSION['login_error']);
}

?>
<script>
window.__toastQueue = window.__toastQueue || [];
window.showAppToast = window.showAppToast || function(message, type) {
  window.__toastQueue.push({ message: message, type: type || 'info' });
};
<?php if (!empty($appToasts)): ?>
window.__toastQueue = window.__toastQueue.concat(<?php echo json_encode($appToasts); ?>);
<?php endif; ?>
</script>

<?php if (isset($_SESSION['send_email']) && isset($_SESSION['reset_data'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add delay to ensure EmailJS is fully loaded
    setTimeout(function() {
        console.log('EmailJS init status:', typeof emailjs !== 'undefined');
        
        const resetData = <?php echo $_SESSION['reset_data']; ?>;
        console.log('Reset data:', resetData);
        
        // Show loading message
        showLoadingMessage("Sending password reset email...");
        
        // EmailJS template parameters
        const templateParams = {
          name: resetData.user_name,
          email: resetData.user_email,
          reset_link: resetData.reset_link,
          message: `Hello ${resetData.user_name},

          You requested a password reset for your Meat Shop account.

          Click the link below to reset your password:
          ${resetData.reset_link}

          This link will expire in 24 hours.

          If you didn't request this reset, please ignore this email.

          Best regards,
          Meat Shop Team`
                };
        
        console.log('Template params:', templateParams);
        
        // Check if EmailJS is loaded
        if (typeof emailjs === 'undefined') {
            console.error('EmailJS not loaded');
            hideLoadingMessage();
            showResetLinkFallback(resetData.reset_link);
            return;
        }
        
        // Send email using EmailJS
        emailjs.send('service_tn9ghw1', 'template_fyqd2hz', templateParams)
            .then(function(response) {
                console.log('Email sent successfully:', response);
                hideLoadingMessage();
                showSuccessMessage("Password reset instructions have been sent to your email address!");
            })
            .catch(function(error) {
                console.error('Email sending failed:', error);
                hideLoadingMessage();
                // Show detailed error and fallback
                showEmailErrorWithFallback(error, resetData.reset_link);
            });
    }, 1000); // 1 second delay to ensure EmailJS is loaded
});

function showEmailErrorWithFallback(error, resetLink) {
    let errorMessage = "Unknown error occurred";
    
    if (error && error.text) {
        errorMessage = error.text;
    } else if (error && error.message) {
        errorMessage = error.message;
    } else if (typeof error === 'string') {
        errorMessage = error;
    }
    
    const errorHTML = `
        <div id="emailErrorModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Email Service Error</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeEmailModal('emailErrorModal')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <h6>Email sending failed</h6>
                            <p><strong>Error:</strong> ${errorMessage}</p>
                            <p class="mb-0">Please use the direct reset link below:</p>
                        </div>
                        <div class="text-center">
                            <a href="${resetLink}" class="btn btn-primary btn-lg">
                                Reset My Password
                            </a>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                Direct link: <br>
                                <code style="word-break: break-all;">${resetLink}</code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    document.body.insertAdjacentHTML('beforeend', errorHTML);
}

function closeEmailModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.remove();
    }
}
</script>

<?php 
unset($_SESSION['send_email']);
unset($_SESSION['reset_data']);
endif; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="mrbstyle.css">
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
  <script type="text/javascript">
    // Initialize EmailJS immediately after loading
    (function(){
        console.log('Initializing EmailJS...');
        try {
            emailjs.init({
                publicKey: "ngVuTALHVeNP-vWuZ"
            });
            console.log('EmailJS initialized successfully');
        } catch (error) {
            console.error('EmailJS initialization failed:', error);
        }
    })();
  </script>
</head>

<body>

  <div class="row login-container d-flex flex-column-reverse flex-sm-row">
    <div class="col-md-8 login-content p-3">
      <div class="login-header px-4">
        <h5 class="h5 white-text mt-5">
          Welcome to Meat Shop Portal
        </h5>
        <p class="white-text mb-5">
          At Meat Shop, we're committed to providing premium quality meats and seamless transactions for all our customers. Access your account, manage your orders, and stay updated—securely and efficiently.
        </p>
        <hr class="mt-5 mb-4 thick-line">
      </div>
      <div class="login-body">
        <button class="btn login-btn w-75" data-bs-toggle="modal" data-bs-target="#LoginModalLabel">
          Login
        </button>
        <!-- LOGIN MODAL -->
        <div class="modal fade" id="LoginModalLabel" tabindex="-1" aria-labelledby="LoginModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content sign-in-modal">
              <div class="modal-header" style="border: none; margin: 0;">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>

              <div class="container w-100 h-100">
                <div class="row d-flex justify-content-center align-items-center h-100">
                  <div class="col-12">
                    <div class="shadow-2-strong" style="border-radius: 1rem;">
                      <div class="p-5 text-center">

                        <h3 class="mb-5 red-text white-text" style="font-weight: 600;">Sign in to MRB LOTUS</h3>

                        <form action="mrbloginpage.php" method="POST">

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input type="email" name="log-email" id="typeEmailX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typeEmailX-2">Email</label>
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input type="password" name="log-pass" id="typePasswordX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typePasswordX-2">Password</label>
                          </div>
                          <button data-mdb-button-init style="min-width: 120px; background-color: #0a9215;" class="white-text btn btn-lg btn-block" name="log-submit" type="submit">Login</button>
                          <div class="mt-3">

                            <a href="#" class="text-light small" data-bs-toggle="modal" data-bs-target="#ForgotPasswordModal" data-bs-dismiss="modal">
                              Forgot your password?
                            </a>
                          </div>


                          

                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- LOGIN MODAL -->

        <!-- FORGOT PASSWORD MODAL -->
        <div class="modal fade" id="ForgotPasswordModal" tabindex="-1" aria-labelledby="ForgotPasswordModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content sign-in-modal">
                              <div class="modal-header" style="border: none; margin: 0;">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="container w-100 px-5 pb-5 h-100">
                                <div class="row d-flex justify-content-center align-items-center h-100">
                                  <div class="col-12">
                                    <div class="shadow-2-strong" style="border-radius: 1rem;">
                                      <div class="px-5 pb-5 text-center">
                                        <h3 class="mb-4 white-text" style="font-weight: 600;">Reset Password</h3>
                                        <p class="white-text mb-4">Enter your email address and we'll send you a link to reset your password.</p>
                                        
                                        <form action="forgot_password.php" method="post">
                                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                                            <input required type="email" name="reset_email" id="resetEmailX" class="form-control form-control-lg" />
                                            <label class="form-label white-text" for="resetEmailX">Email Address</label>
                                          </div>
                                          
                                          <button name="forgot-submit" data-mdb-button-init style="min-width: 120px; background-color: #15ac2e;" class="white-text btn btn-lg btn-block" type="submit">
                                            Send Reset Link
                                          </button>
                                          
                                          <div class="mt-3">
                                            <a href="#" class="text-light small" data-bs-toggle="modal" data-bs-target="#LoginModalLabel" data-bs-dismiss="modal">
                                              Back to Login
                                            </a>
                                          </div>
                                        </form>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
        </div>
        <!-- FORGOT PASSWORD MODAL -->

        <button class="btn reg-btn w-75" data-bs-toggle="modal" data-bs-target="#RegModalLabel">
          Register
        </button>
        <!-- REGISTER MODAL -->
        <div class="modal fade" id="RegModalLabel" tabindex="-1" aria-labelledby="RegModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content sign-in-modal">
              <div class="modal-header" style="border: none; margin: 0;">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="container w-100 px-5 pb-5 h-100">
                <div class="row d-flex justify-content-center align-items-center h-100">
                  <div class="col-12">
                    <div class="shadow-2-strong" style="border-radius: 1rem;">
                      <div class="px-5 pb-5 text-center">

                        <h3 class="mb-5 white-text" style="font-weight: 600;">Sign up in to MRB LOTUS</h3>
                        <form action="mrbloginpage.php" method="post">
                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="text" placeholder="First name" name="reg_name" id="typeNameX-2" class="form-control form-control-lg" />
                          </div>
                          <div data-mdb-input-init class="form-outline mb-4 login-input-group d-flex">
                            <input required type="text" name="reg_mname" placeholder="Middle name" id="typeNameX-2" class="form-control form-control-lg col me-3" />
                            <input required type="text" name="reg_lname" placeholder="Last name" id="typeNameX-2" class="form-control form-control-lg col" />
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="text" name="reg_contact" placeholder="Contact number" maxlength="11" inputmode="numeric" id="typeNameX-2" class="form-control form-control-lg" />
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="email" name="reg_email" placeholder="Email Address" id="typeEmailX-2" class="form-control form-control-lg" />
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="password" placeholder="Enter password" name="first_reg_pass" id="typePasswordX-2" class="form-control form-control-lg" />
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="password" placeholder="Confirm password" name="reg_pass" id="typePasswordX-2" class="form-control form-control-lg" />
                          </div>
                          <button name="reg-submit" data-mdb-button-init style="min-width: 120px; background-color: #15ac2e;" data-mdb-ripple-init class="white-text btn btn-lg btn-block mt-3" type="submit">Register</button>
                           
                        </form>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- LOGIN MODAL -->
      </div>
      <div class="login-footer">
        <a href="landpage.php">Continue to Meat Shop Portal without Signing-in</a>
      </div>
    </div>
    <div class="col d-none icon-container d-lg-flex justify-content-center align-items-center" style="overflow: hidden;">
      <img src="meat-icon.png" alt="" class="img-fluid spinning-image">
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.min.js" integrity="sha384-VQqxDN0EQCkWoxt/0vsQvZswzTHUVOImccYmSyhJTp7kGtPed0Qcx8rK9h9YEgx+" crossorigin="anonymous"></script>
  <script>
    (function () {
      function ensureToastContainer() {
        let container = document.getElementById('appToastContainer');
        if (!container) {
          container = document.createElement('div');
          container.id = 'appToastContainer';
          container.style.position = 'fixed';
          container.style.top = '20px';
          container.style.right = '20px';
          container.style.zIndex = '9999';
          container.style.display = 'flex';
          container.style.flexDirection = 'column';
          container.style.gap = '10px';
          document.body.appendChild(container);
        }
        return container;
      }

      function getToastStyle(type) {
        if (type === 'success') return { bg: '#198754', border: '#157347' };
        if (type === 'error') return { bg: '#dc3545', border: '#b02a37' };
        if (type === 'warning') return { bg: '#fd7e14', border: '#ca6510' };
        return { bg: '#0d6efd', border: '#0a58ca' };
      }

      window.showAppToast = function (message, type) {
        const container = ensureToastContainer();
        const colors = getToastStyle(type || 'info');
        const toast = document.createElement('div');
        toast.textContent = String(message);
        toast.style.minWidth = '260px';
        toast.style.maxWidth = '420px';
        toast.style.color = '#fff';
        toast.style.background = colors.bg;
        toast.style.border = '1px solid ' + colors.border;
        toast.style.borderRadius = '8px';
        toast.style.padding = '12px 14px';
        toast.style.boxShadow = '0 8px 20px rgba(0,0,0,0.2)';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-6px)';
        toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
        container.appendChild(toast);

        requestAnimationFrame(function () {
          toast.style.opacity = '1';
          toast.style.transform = 'translateY(0)';
        });

        setTimeout(function () {
          toast.style.opacity = '0';
          toast.style.transform = 'translateY(-6px)';
          setTimeout(function () {
            if (toast.parentNode) {
              toast.parentNode.removeChild(toast);
            }
          }, 220);
        }, 3500);
      };

      window.alert = function (message) {
        window.showAppToast(message, 'warning');
      };

      if (Array.isArray(window.__toastQueue) && window.__toastQueue.length) {
        window.__toastQueue.forEach(function (item) {
          window.showAppToast(item.message, item.type);
        });
        window.__toastQueue = [];
      }
    })();

    const myModal = document.getElementById('myModal')
    const myInput = document.getElementById('myInput')

    if (myModal && myInput) {
      myModal.addEventListener('shown.bs.modal', () => {
        myInput.focus()
      })
    }

    // EmailJS Helper Functions - These were missing and causing errors
    function showLoadingMessage(message) {
        const loadingHTML = `<div id="emailLoadingModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p class="mb-0">${message}</p>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    }

    function hideLoadingMessage() {
        const modal = document.getElementById('emailLoadingModal');
        if (modal) modal.remove();
    }

    function showSuccessMessage(message) {
        const html = `<div id="emailSuccessModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Email Sent Successfully</h5>
                        <button type="button" class="btn-close btn-close-white" onclick="closeEmailModal('emailSuccessModal')"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="text-success mb-3" style="font-size: 3rem;">✓</div>
                        <p>${message}</p>
                        <small class="text-muted">Please check your inbox and spam folder.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" onclick="closeEmailModal('emailSuccessModal')">OK</button>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
    }

    function showResetLinkFallback(resetLink) {
        const html = `<div id="emailFallbackModal" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Email Service Unavailable</h5>
                        <button type="button" class="btn-close" onclick="closeEmailModal('emailFallbackModal')"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6>Unable to send email</h6>
                            <p>Please use the direct reset link below:</p>
                        </div>
                        <div class="text-center">
                            <a href="${resetLink}" class="btn btn-primary btn-lg">Reset My Password</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
    }

    function closeEmailModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.remove();
    }
  </script>
  
  <script>
    // Auto-open modal based on purpose parameter
    document.addEventListener('DOMContentLoaded', function() {
      <?php
      if (isset($_GET['purpose'])) {
        $purpose = $_GET['purpose'];
        if ($purpose === 'register') {
          echo "const registerModal = new bootstrap.Modal(document.getElementById('RegModalLabel'));\n";
          echo "registerModal.show();\n";
        } else {
          echo "const loginModal = new bootstrap.Modal(document.getElementById('LoginModalLabel'));\n";
          echo "loginModal.show();\n";
        }
      }
      ?>
    });
  </script>
  
  <script src="mrbscript.js"></script>
</body>

</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
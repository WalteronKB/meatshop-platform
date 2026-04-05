<?php
session_start();
$_SESSION['user_id'] = null;
include 'connection.php';


if (isset($_POST['reg-submit'])) {
  $name = trim($_POST['reg_name']);
  $contact = trim($_POST['reg_contact']);
  $email = trim($_POST['reg_email']);
  $password = $_POST['reg_pass'];

  // Validate inputs
  if (empty($name) || empty($contact) || empty($email) || empty($password)) {
    $_SESSION['reg_error'] = "All fields are required.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }

  // Check if email already exists using prepared statement
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

  // Get next user ID safely
  $query = "SELECT MAX(user_id) AS max_id FROM mrb_users";
  $result = $conn->query($query);
  $id = $result->fetch_assoc()['max_id'] + 1;

  // Hash password for security
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);

  // Insert new user using prepared statement
  $insert_query = "INSERT INTO mrb_users (user_id, user_name, user_contactnum, user_email, user_password, user_pic, user_dateadded) VALUES (?, ?, ?, ?, ?, ?, NOW())";
  $stmt = $conn->prepare($insert_query);
  $stmt->bind_param("isssss", $id, $name, $contact, $email, $hashed_password, $profile_pic);
  $profile_pic = 'Images/profile_pics/anonymous.jpg';
  
  if ($stmt->execute()) {
    $_SESSION['reg_success'] = true;
    header("Location: mrbloginpage.php?registration=success");
    exit();
  } else {
    $_SESSION['reg_error'] = "Registration failed. Please try again.";
    header("Location: mrbloginpage.php?registration=error");
    exit();
  }
}

if (isset($_SESSION['reg_success'])) {
  echo "<script>alert('Registration successful! You can now log in.');</script>";
  unset($_SESSION['reg_success']); // Remove the message after displaying
}
if (isset($_SESSION['reg_error'])) {
  echo "<script>alert('Error: " . $_SESSION['reg_error'] . "');</script>";
  unset($_SESSION['reg_error']); // Remove the message after displaying
}
if (isset($_SESSION['forgot_success'])) {
    echo "<script>alert('" . $_SESSION['forgot_success'] . "');</script>";
    if (isset($_SESSION['reset_link'])) {
        echo "<script>
        alert('For development: " . $_SESSION['reset_link'] . "');
        </script>";
        unset($_SESSION['reset_link']);
    }
    unset($_SESSION['forgot_success']);
}
if (isset($_SESSION['forgot_error'])) {
    echo "<script>alert('Error: " . $_SESSION['forgot_error'] . "');</script>";
    unset($_SESSION['forgot_error']);
}

?>

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
          message: `Click this link to reset your password: ${resetData.reset_link}`
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
        emailjs.send('service_y62evzw', 'template_d56wof5', templateParams)
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
                publicKey: "ByfBqlg8U4c7l7Vzi"
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
          Welcome to MRB Estelope Trading & Services Inc. Portal
        </h5>
        <p class="white-text mb-5">
          At MRB Estelope, we’re committed to providing reliable services and seamless transactions for all our clients. Access your dashboard, manage your records, and stay updated—securely and efficiently.
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

                        <?php
                        if (isset($_POST['log-submit'])) {
                          $email = trim($_POST['log-email']);
                          $password = $_POST['log-pass'];

                          if (empty($email) || empty($password)) {
                            echo "<script>alert('Please fill in all fields.');</script>";
                          } else {
                            // Use prepared statement for login
                            $query = "SELECT * FROM mrb_users WHERE user_email = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("s", $email);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                              $user_data = $result->fetch_assoc();
                              
                              // Verify password - check if it's hashed or plain text
                              $password_valid = false;
                              if (password_verify($password, $user_data['user_password'])) {
                                // New hashed password
                                $password_valid = true;
                              } elseif ($password === $user_data['user_password']) {
                                // Legacy plain text password - update to hashed
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
                                $_SESSION['user_id'] = $user_id;

                                if ($usertype == 'admin') {
                                  $_SESSION['user_type'] = 'admin';
                                  $_SESSION['admin_logged_in'] = true;
                                  header("Location: admin/admin.php");
                                  exit();
                                } else {
                                  header("Location: landpage.php");
                                  exit();
                                }
                              } else {
                                echo "<script>alert('Invalid email or password. Please try again.');</script>";
                              }
                            } else {
                              echo "<script>alert('Invalid email or password. Please try again.');</script>";
                            }
                          }
                        }
                        ?>

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
                            <input required type="text" name="reg_name" id="typeNameX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typeEmailX-2">Name</label>
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="text" name="reg_contact" id="typeNameX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typeContact-2">Contact Number</label>
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="email" name="reg_email" id="typeEmailX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typeEmailX-2">Email</label>
                          </div>

                          <div data-mdb-input-init class="form-outline mb-4 login-input-group">
                            <input required type="password" name="reg_pass" id="typePasswordX-2" class="form-control form-control-lg" />
                            <label class="form-label white-text" for="typePasswordX-2">Password</label>
                          </div>
                          <button name="reg-submit" data-mdb-button-init style="min-width: 120px; background-color: #15ac2e;" data-mdb-ripple-init class="white-text btn btn-lg btn-block" type="submit">Register</button>
                           
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
        <a href="landpage.php">Continue to MRB without Signing-in</a>
      </div>
    </div>
    <div class="col icon-container d-flex justify-content-center align-items-center" style="overflow: hidden;">
      <img src="img/logo.png" alt="" class="img-fluid spinning-image">
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.min.js" integrity="sha384-VQqxDN0EQCkWoxt/0vsQvZswzTHUVOImccYmSyhJTp7kGtPed0Qcx8rK9h9YEgx+" crossorigin="anonymous"></script>
  <script>
    const myModal = document.getElementById('myModal')
    const myInput = document.getElementById('myInput')

    myModal.addEventListener('shown.bs.modal', () => {
      myInput.focus()
    })
  </script>
  <script src="mrbscript.js"></script>
</body>

</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
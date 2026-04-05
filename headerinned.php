
<nav class="navbar navbar-expand-lg navbar-radius sticky-top" style="background-color:#8B0000; margin: 0; padding: 0;">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center text-decoration-none text-dark" href="landpage.php">
        <img src="meat-icon.png" alt="Logo" class="img-fluid" style="height: 50px; width: auto;" />
        <div class="ms-3">
          <div class="nav-text-1">Meat Shop</div>
          <div class="nav-text-1">Premium Quality Meats</div>
        </div>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
              aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNavDropdown">
        <div class="ms-lg-auto d-flex flex-column flex-lg-row align-items-center text-center text-lg-start gap-lg-5 w-100 justify-content-lg-end">
          <ul class="navbar-nav flex-column flex-lg-row align-items-center gap-lg-3 mb-3 mb-lg-0">
            
            <li class="nav-item">
              <a class="nav-text-1 nav-link" href="#contact_id">Contact Us</a>
            </li>

            <!-- Order Notification -->
            

            <li class="nav-item">
            <a class="nav-text-1 nav-link" href="usersetting.php" title="User Settings">

              <span class="d-inline d-lg-none">User Settings</span>

              <span class="d-none d-lg-inline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="20" height="20" fill="currentColor">
                  <path d="M12.25 14c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zM12.25 2c-2.757 0-5 2.243-5 5s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5z" />
                  <path d="M18.104 32H4.308A4.062 4.062 0 0 1 .25 27.942c0-6.616 5.383-12 12-12 2.268 0 4.477.638 6.388 1.843a1 1 0 1 1-1.067 1.691 9.96 9.96 0 0 0-5.321-1.534c-5.514 0-10 4.486-10 10A2.06 2.06 0 0 0 4.308 30h13.796a1 1 0 0 1 0 2z" />
                  <path d="M24.75 32c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7zm0-12c-2.757 0-5 2.243-5 5s2.243 5 5 5 5-2.243 5-5-2.243-5-5-5z" />
                  <path d="M26.75 26h-1v1a1 1 0 0 1-2 0v-1h-1a1 1 0 0 1 0-2h1v-1a1 1 0 0 1 2 0v1h1a1 1 0 0 1 0 2z" />
                </svg>
              </span>
            </a>


          </li>
          <li class="nav-item position-relative">
              <a class="nav-text-1 nav-link" href="userorders.php?order_sort=All" title="My Orders">
                <span class="d-inline d-lg-none">My Orders</span>
                <span class="d-none d-lg-inline">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                  </svg>
                </span>
                <!-- Notification Badge -->
                <span id="orderNotificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none; font-size: 0.65rem; padding: 0.25em 0.5em;">
                  0
                </span>
              </a>
            </li>
          </ul>
         
        </div>
      </div>
    </div>
  </nav>
  <script>
    // Check for new order notifications
    function checkNewOrders() {
      fetch('check_new_orders.php')
        .then(response => response.json())
        .then(data => {
          const badge = document.getElementById('orderNotificationBadge');
          if (data.count > 0) {
            badge.textContent = data.count;
            badge.style.display = 'inline-block';
          } else {
            badge.style.display = 'none';
          }
        })
        .catch(error => console.error('Error checking orders:', error));
    }

    // Check immediately on page load
    checkNewOrders();

    // Check every 30 seconds for new order updates
    setInterval(checkNewOrders, 30000);
  </script>
    <script>
      (function () {
        if (window.__appAlertToToastInitialized) {
          return;
        }
        window.__appAlertToToastInitialized = true;

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
      })();
    </script>
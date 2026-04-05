<nav class="navbar navbar-expand-lg navbar-radius sticky-top" style="background-color: #8B0000; margin: 0; padding: 0;">
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
        </ul>

        <ul class="navbar-nav flex-column flex-lg-row align-items-center gap-lg-3">

          <li class="nav-item">
            <a class="nav-text-1 nav-link" href="mrbloginpage.php?purpose=register" title="Register">

              <span class="d-inline d-lg-none">Register</span>

              <span class="d-none d-lg-inline">
                <i class="bi bi-person-plus-fill fs-5"></i>
              </span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-text-1 nav-link" href="mrbloginpage.php?purpose=login" title="Login">
              <!-- Text on xs, sm, md -->
              <span class="d-inline d-lg-none">Login</span>
              <!-- Icon on lg+ -->
              <span class="d-none d-lg-inline">
                <i class="bi bi-box-arrow-right fs-5"></i>
              </span>
            </a>
          </li>
        </ul>



      </div>
    </div>
  </div>
</nav>
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
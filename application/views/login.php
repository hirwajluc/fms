<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>

<html
  lang="en"
  class="light-style customizer-hide"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="<?=base_url();?>assets/"
  data-template="horizontal-menu-template">
  <head>
    <meta charset="utf-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?=$title;?></title>

    <meta name="description" content="" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?=base_url();?>assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
      rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/tabler-icons.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/flag-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.css" />
    <!-- Vendor -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/formvalidation/dist/css/formValidation.min.css" />
		<link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/toastr/toastr.css" />
		<link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/animate-css/animate.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/pages/page-auth.css" />
    <!-- Helpers -->
    <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js.  -->
    <script src="<?=base_url();?>assets/vendor/js/template-customizer.js"></script>
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="<?=base_url();?>assets/js/config.js"></script>
  </head>

  <body>
    <!-- Content -->
		
    <div class="authentication-wrapper authentication-cover authentication-bg">
      <div class="authentication-inner row">
        <!-- /Left Text -->
        <div class="d-none d-lg-flex col-lg-7 p-0">
          <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
            <img
              src="<?=base_url();?>assets/img/illustrations/auth-login-illustration-light.png"
              alt="auth-login-cover"
              class="img-fluid my-5 auth-illustration"
              data-app-light-img="illustrations/auth-login-illustration-light.png"
              data-app-dark-img="illustrations/auth-login-illustration-dark.png" />

            <img
              src="<?=base_url();?>assets/img/illustrations/bg-shape-image-light.png"
              alt="auth-login-cover"
              class="platform-bg"
              data-app-light-img="illustrations/bg-shape-image-light.png"
              data-app-dark-img="illustrations/bg-shape-image-dark.png" />
          </div>
        </div>
        <!-- /Left Text -->
				
        <!-- Login -->
        <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
          <div class="w-px-400 mx-auto">
            <!-- Logo -->
            <div class="app-brand mb-4">
              <a href="<?=base_url();?>" class="app-brand-link gap-2">
                <span class="app-brand-logo w-px-400 h-px-100">
                  <img src="<?=base_url();?>assets/img/branding/GreaterIcon.png">
                </span>
              </a>
            </div>
            <!-- /Logo -->
            <h3 class="mb-1 fw-bold">Welcome to GREATER FMS! 👋</h3>
            <p class="mb-4">Please sign-in to your account</p>

            <form id="formAuthentication" class="mb-3" action="<?=base_url();?>login/login_pro" method="POST">
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input
                  type="text"
                  class="form-control"
                  id="email"
                  name="email"
                  placeholder="Enter your email"
                  autofocus
                  required />
              </div>
              <div class="mb-3 form-password-toggle">
                <div class="d-flex justify-content-between">
                  <label class="form-label" for="password">Password</label>
                  <!--<a href="#">
                    <small>Forgot Password?</small>
                  </a>-->
                </div>
                <div class="input-group input-group-merge">
                  <input
                    type="password"
                    id="password"
                    class="form-control"
                    name="password"
                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                    aria-describedby="password"
                    required />
                  <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                </div>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="remember-me" />
                  <label class="form-check-label" for="remember-me"> Remember Me </label>
                </div>
              </div>
              <button type="submit" class="btn btn-primary d-grid w-100" id="signInBtn">Sign in</button>
            </form>

          </div>
        </div>
        <!-- /Login -->
      </div>
    </div>
		
    <!-- / Content -->

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>

    <script src="<?=base_url();?>assets/vendor/libs/hammer/hammer.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18n/i18n.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.js"></script>

    <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js"></script>
		<script src="<?=base_url();?>assets/vendor/libs/toastr/toastr.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="<?=base_url();?>assets/js/pages-auth.js"></script>
	<script src="<?=base_url();?>assets/js/ui-toasts.js"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.getElementById('formAuthentication');
			const signInBtn = document.getElementById('signInBtn');

			form.addEventListener('submit', function(e) {
				e.preventDefault();

				const email = document.getElementById('email').value.trim();
				const password = document.getElementById('password').value.trim();

				// Validate inputs
				if(!email || !password) {
					alert('Please enter both email and password');
					signInBtn.disabled = false;
					signInBtn.textContent = 'Sign in';
					return;
				}

				// Disable button to prevent multiple submissions
				signInBtn.disabled = true;
				signInBtn.textContent = 'Signing in...';

				// Use XMLHttpRequest for better compatibility and error handling
				var xhr = new XMLHttpRequest();
				var url = form.action;

				// Try POST first
				xhr.open('POST', url, true);
				xhr.withCredentials = true;
				xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

				xhr.onload = function() {
					console.log('POST request completed with status:', xhr.status);
					// If POST succeeds (server redirects), page will load the new URL
					// If POST fails or times out, fallback to GET
					if(xhr.status === 0 || xhr.readyState === 4) {
						// Check if we got a response (POST worked)
						if(xhr.responseText) {
							// Server responded - check if it's a redirect
							window.location.href = url;
							return;
						}
						// No response or error - use GET fallback
						console.warn('POST request failed or no response, using GET fallback');
						submitViaGET(email, password);
					}
				};

				xhr.onerror = function() {
					console.warn('POST request error (CORS or network issue), using GET fallback');
					submitViaGET(email, password);
				};

				xhr.onreadystatechange = function() {
					// Also check for timeout or abort
					if(xhr.readyState === 4 && xhr.status === 0) {
						console.warn('POST request timeout/abort, using GET fallback');
						submitViaGET(email, password);
					}
				};

				// Set a timeout for POST attempt (3 seconds)
				var postTimeout = setTimeout(function() {
					if(xhr.readyState < 4) {
						console.warn('POST request timeout after 3 seconds, using GET fallback');
						xhr.abort();
						submitViaGET(email, password);
					}
				}, 3000);

				// Send POST request with form data
				var formData = new FormData(form);
				xhr.send(formData);

				function submitViaGET(email, password) {
					clearTimeout(postTimeout);
					console.log('Submitting via GET method due to POST failure/restrictions');
					var getUrl = url + '?email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password);
					window.location.href = getUrl;
				}
			});
		});
	</script>
	
	<?php
		if ($this->input->get('status')=='error'): ?>
		<div class="bs-toast toast fade animate__animated toast-ex show position-absolute top-0 end-0" role="alert" aria-live="assertive" aria-atomic="true">
		    <div class="toast-progress"></div>
			<div class="toast-header">
				<i class="ti ti-bell ti-xs text-danger me-2"></i>
				<div class="me-auto fw-medium text-danger">Error</div>
				<button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
			<div class="toast-body bg-danger text-white">
				<?php echo $this->session->flashdata('msg'); ?>
				<br>Please try again 
			</div>
		</div>
		<?php endif;?>
  </body>
</html>
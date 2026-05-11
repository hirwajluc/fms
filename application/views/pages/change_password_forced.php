<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr"
  data-theme="theme-default"
  data-assets-path="<?=base_url();?>assets/"
  data-template="horizontal-menu-template">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title><?=isset($title) ? htmlspecialchars($title) : 'Set New Password – GREATER FMS';?></title>
  <meta name="description" content="" />
  <link rel="icon" type="image/x-icon" href="<?=base_url();?>assets/img/favicon/favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/fontawesome.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/tabler-icons.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" class="template-customizer-core-css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" class="template-customizer-theme-css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/pages/page-auth.css" />
  <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>
  <script src="<?=base_url();?>assets/vendor/js/template-customizer.js"></script>
  <script src="<?=base_url();?>assets/js/config.js"></script>
</head>
<body>
  <div class="authentication-wrapper authentication-cover authentication-bg">
    <div class="authentication-inner row">

      <!-- Left illustration -->
      <div class="d-none d-lg-flex col-lg-7 p-0">
        <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
          <img src="<?=base_url();?>assets/img/illustrations/auth-login-illustration-light.png"
            alt="cover" class="img-fluid my-5 auth-illustration"
            data-app-light-img="illustrations/auth-login-illustration-light.png"
            data-app-dark-img="illustrations/auth-login-illustration-dark.png" />
          <img src="<?=base_url();?>assets/img/illustrations/bg-shape-image-light.png"
            alt="shape" class="platform-bg"
            data-app-light-img="illustrations/bg-shape-image-light.png"
            data-app-dark-img="illustrations/bg-shape-image-dark.png" />
        </div>
      </div>

      <!-- Form -->
      <div class="d-flex col-12 col-lg-5 align-items-center p-sm-5 p-4">
        <div class="w-px-400 mx-auto">

          <!-- Logo -->
          <div class="app-brand mb-4">
            <a href="<?=base_url();?>" class="app-brand-link gap-2">
              <span class="app-brand-logo w-px-400 h-px-100">
                <img src="<?=base_url();?>assets/img/branding/GreaterIcon.png" alt="GREATER FMS" />
              </span>
            </a>
          </div>

          <h3 class="mb-1 fw-bold">Set Your New Password</h3>
          <p class="mb-4 text-muted">
            <i class="ti ti-shield-lock me-1 text-warning"></i>
            For security, please set a permanent password before continuing.
          </p>

          <!-- Alerts -->
          <?php $error = $this->session->flashdata('error'); ?>
          <?php $success = $this->session->flashdata('success'); ?>
          <?php if($error): ?>
          <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
            <i class="ti ti-alert-circle me-2"></i>
            <?=htmlspecialchars($error);?>
          </div>
          <?php endif; ?>
          <?php if($success): ?>
          <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
            <i class="ti ti-circle-check me-2"></i>
            <?=htmlspecialchars($success);?>
          </div>
          <?php endif; ?>

          <form id="formChangePassword" action="<?=base_url('processChangePassword');?>" method="POST">
            <div class="mb-3 form-password-toggle">
              <label class="form-label" for="new_password">New Password</label>
              <div class="input-group input-group-merge">
                <input type="password" id="new_password" name="new_password"
                  class="form-control" placeholder="Min. 6 characters" required autofocus />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
            </div>
            <div class="mb-4 form-password-toggle">
              <label class="form-label" for="confirm_password">Confirm New Password</label>
              <div class="input-group input-group-merge">
                <input type="password" id="confirm_password" name="confirm_password"
                  class="form-control" placeholder="Repeat new password" required />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100" id="btnSubmit">
              <i class="ti ti-lock-check me-1"></i> Set Password &amp; Continue
            </button>
          </form>

          <p class="text-center mt-4 text-muted small">
            <a href="<?=base_url('logout');?>">
              <i class="ti ti-logout me-1"></i>Sign out
            </a>
          </p>

        </div>
      </div>

    </div>
  </div>

  <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
  <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
  <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
  <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
  <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
  <script src="<?=base_url();?>assets/js/main.js"></script>
  <script src="<?=base_url();?>assets/js/pages-auth.js"></script>
  <script>
    document.getElementById('formChangePassword').addEventListener('submit', function(e){
      var p = document.getElementById('new_password').value;
      var c = document.getElementById('confirm_password').value;
      if(p.length < 6){
        e.preventDefault();
        alert('Password must be at least 6 characters.');
        return;
      }
      if(p !== c){
        e.preventDefault();
        alert('Passwords do not match.');
        return;
      }
      document.getElementById('btnSubmit').disabled = true;
      document.getElementById('btnSubmit').textContent = 'Saving...';
    });
  </script>
</body>
</html>

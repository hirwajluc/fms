<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr"
      data-theme="theme-default"
      data-assets-path="<?=base_url();?>assets/"
      data-template="horizontal-menu-template">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
  <title><?=$title;?></title>
  <link rel="icon" type="image/x-icon" href="<?=base_url();?>assets/img/favicon/favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/tabler-icons.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/pages/page-auth.css" />
  <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>
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
              <span class="app-brand-logo">
                <img src="<?=base_url();?>assets/img/branding/GreaterIcon.png" style="max-height:60px;">
              </span>
            </a>
          </div>

          <h3 class="mb-1 fw-bold">Forgot Password? 🔑</h3>
          <p class="mb-4 text-muted">Enter your registered email and we'll send you a new temporary password.</p>

          <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible mb-3" role="alert">
            <i class="ti ti-circle-check me-2"></i><?=$this->session->flashdata('success');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <?php if($this->session->flashdata('msg')): ?>
          <div class="alert alert-danger alert-dismissible mb-3" role="alert">
            <i class="ti ti-alert-circle me-2"></i><?=$this->session->flashdata('msg');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <form action="<?=base_url('processForgotPassword');?>" method="POST">
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email"
                     placeholder="Enter your registered email" required autofocus />
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100">
              <i class="ti ti-send me-1"></i> Send New Password
            </button>
          </form>

          <div class="text-center mt-4">
            <a href="<?=base_url('login');?>" class="d-flex align-items-center justify-content-center gap-1 small">
              <i class="ti ti-arrow-left"></i> Back to Login
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>

  <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
  <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
  <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
</body>
</html>

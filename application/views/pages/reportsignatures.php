<!DOCTYPE html>

<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="<?=base_url();?>assets/"
  data-template="horizontal-menu-template-no-customizer">
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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.css" />

    <!-- Page CSS -->

    <!-- Helpers -->
    <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>

    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
    <!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
    <script src="<?=base_url();?>assets/js/config.js"></script>
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
      <div class="layout-container">
        <!-- Navbar -->
		<?php include("navbar.php");?>
        <!-- / Navbar -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Menu -->
            <?php include("menu.php");?>
            <!-- / Menu -->

            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Settings /</span> Report Signatures</h4>

              <?php if($this->session->flashdata('success')): ?>
              <div class="alert alert-success alert-dismissible" role="alert">
                <?=$this->session->flashdata('success');?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <?php if($this->session->flashdata('error')): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <?=$this->session->flashdata('error');?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">My Report Signature</h5>
                  <p class="text-muted small mb-0">Configure your signature details that will appear on generated expense reports.</p>
                </div>
                <div class="card-body">
                  <form action="<?=base_url('saveSignature');?>" method="post" enctype="multipart/form-data">
                    <div class="row g-3">
                      <!-- Signature Name -->
                      <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="signature_name"
                               placeholder="e.g., John Doe"
                               value="<?=isset($current_signature['signature_name']) ? htmlspecialchars($current_signature['signature_name']) : '';?>"
                               required>
                        <small class="text-muted">Your full name to appear on reports</small>
                      </div>

                      <!-- Position/Title -->
                      <div class="col-md-6">
                        <label class="form-label">Position/Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="position"
                               placeholder="e.g., Project Coordinator"
                               value="<?=isset($current_signature['position']) ? htmlspecialchars($current_signature['position']) : '';?>"
                               required>
                        <small class="text-muted">Your job title or position</small>
                      </div>

                      <!-- Organization (Optional) -->
                      <div class="col-md-6">
                        <label class="form-label">Organization (Optional)</label>
                        <input type="text" class="form-control" name="organization"
                               placeholder="e.g., GREATER Project"
                               value="<?=isset($current_signature['organization']) ? htmlspecialchars($current_signature['organization']) : 'GREATER Project';?>">
                        <small class="text-muted">Organization name</small>
                      </div>

                      <!-- Signature Image Upload -->
                      <div class="col-md-6">
                        <label class="form-label">Signature Image (PNG) <?=!isset($current_signature['signature_file']) ? '<span class="text-danger">*</span>' : '';?></label>
                        <input type="file" class="form-control" name="signature_file" accept=".png,image/png" <?=!isset($current_signature['signature_file']) ? 'required' : '';?>>
                        <small class="text-muted">Upload your signature as PNG image (transparent background recommended)</small>
                      </div>

                      <!-- Current Signature Preview -->
                      <?php if(isset($current_signature['signature_file']) && !empty($current_signature['signature_file'])): ?>
                      <div class="col-12">
                        <label class="form-label">Current Signature Preview</label>
                        <div class="border rounded p-3 bg-light text-center">
                          <img src="<?=base_url('assets/signatures/' . $current_signature['signature_file']);?>"
                               alt="Current Signature"
                               style="max-height: 100px; max-width: 300px;">
                        </div>
                        <small class="text-muted">Upload a new image to replace the current signature</small>
                      </div>
                      <?php endif; ?>

                      <!-- Submit Button -->
                      <div class="col-12">
                        <hr class="my-4">
                        <button type="submit" class="btn btn-primary">
                          <i class="ti ti-device-floppy me-1"></i> Save My Signature
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

            </div>
            <!-- / Content -->

            <div class="content-backdrop fade"></div>
          </div>
          <!-- Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

      <!-- Drag Target Area To SlideIn Menu On Small Screens -->
      <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

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

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

  </body>
</html>

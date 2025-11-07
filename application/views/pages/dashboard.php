<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>

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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/apex-charts/apex-charts.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />

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
              <div class="row">
                <!-- Welcome Card -->
                <div class="col-xl-4 mb-4 col-lg-5 col-12">
                  <div class="card">
                    <div class="d-flex align-items-end row">
                      <div class="col-7">
                        <div class="card-body text-nowrap">
                          <h5 class="card-title mb-0">Welcome <?=$this->session->userdata("fms_fname");?>! 🎉</h5>
                          <p class="mb-2"><?=$this->session->userdata("fms_role_name") ?: $this->session->userdata("fms_role");?></p>
                          <h4 class="text-primary mb-1"><?=$this->session->userdata("fms_partner") ?: 'GREATER';?></h4>
                          <small class="text-muted">Role: <?=ucfirst(str_replace('_', ' ', $role));?></small>
                        </div>
                      </div>
                      <div class="col-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                          <img
                            src="<?=base_url();?>assets/img/illustrations/card-advance-sale.png"
                            height="140"
                            alt="view sales" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Statistics Cards -->
                <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                <!-- Super Admin / Admin Dashboard -->
                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-users ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Total Users</span>
                      <h3 class="card-title mb-2"><?=isset($total_users) ? $total_users : 0;?></h3>
                      <small class="text-success fw-semibold">All Institutions</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-building ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Partners</span>
                      <h3 class="card-title mb-2"><?=isset($total_partners) ? $total_partners : 0;?></h3>
                      <small class="text-info fw-semibold">Active Institutions</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-file-invoice ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Total Expenses</span>
                      <h3 class="card-title mb-2"><?=isset($total_expenses) ? $total_expenses : 0;?></h3>
                      <small class="text-warning fw-semibold">All Partners</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Timesheets</span>
                      <h3 class="card-title mb-2"><?=isset($total_timesheets) ? $total_timesheets : 0;?></h3>
                      <small class="text-primary fw-semibold">All Submissions</small>
                    </div>
                  </div>
                </div>

                <?php elseif($this->auth_manager->is_coordinator()): ?>
                <!-- Coordinator Dashboard -->
                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-users ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Institution Members</span>
                      <h3 class="card-title mb-2"><?=isset($total_users) ? $total_users : 0;?></h3>
                      <small class="text-success fw-semibold">Your Institution</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-file-invoice ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Expenses</span>
                      <h3 class="card-title mb-2"><?=isset($total_expenses) ? $total_expenses : 0;?></h3>
                      <small class="text-warning fw-semibold">Institution Total</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock-check ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Pending Timesheets</span>
                      <h3 class="card-title mb-2"><?=isset($pending_timesheets) ? $pending_timesheets : 0;?></h3>
                      <small class="text-danger fw-semibold">Awaiting Approval</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">All Timesheets</span>
                      <h3 class="card-title mb-2"><?=isset($total_timesheets) ? $total_timesheets : 0;?></h3>
                      <small class="text-info fw-semibold">Institution Total</small>
                    </div>
                  </div>
                </div>

                <?php else: ?>
                <!-- Member Dashboard -->
                <div class="col-md-6 col-lg-4 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">My Timesheets</span>
                      <h3 class="card-title mb-2"><?=isset($total_timesheets) ? $total_timesheets : 0;?></h3>
                      <small class="text-primary fw-semibold">Total Submitted</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock-check ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Approved</span>
                      <h3 class="card-title mb-2"><?=isset($approved_timesheets) ? $approved_timesheets : 0;?></h3>
                      <small class="text-success fw-semibold">Timesheets</small>
                    </div>
                  </div>
                </div>

                <div class="col-md-6 col-lg-4 mb-4">
                  <div class="card">
                    <div class="card-body">
                      <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                          <i class="ti ti-clock-pause ti-lg"></i>
                        </div>
                      </div>
                      <span class="fw-semibold d-block mb-1">Pending</span>
                      <h3 class="card-title mb-2"><?=isset($pending_timesheets) ? $pending_timesheets : 0;?></h3>
                      <small class="text-warning fw-semibold">Awaiting Review</small>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

              </div>
            </div>
            <!--/ Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
                  <div>
                    ©
                    <script>
                      document.write(new Date().getFullYear());
                    </script>
                    , made with ❤️ by <a href="#" target="_blank" class="fw-semibold">ERASMUS+ GREATER</a>
                  </div>
                  
                </div>
              </div>
            </footer>
            <!-- / Footer -->

            <div class="content-backdrop fade"></div>
          </div>
          <!--/ Content wrapper -->
        </div>

        <!--/ Layout container -->
      </div>
    </div>

    <!-- Overlay -->
    <div class="layout-overlay layout-menu-toggle"></div>

    <!-- Drag Target Area To SlideIn Menu On Small Screens -->
    <div class="drag-target"></div>

    <!--/ Layout wrapper -->

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
    <script src="<?=base_url();?>assets/vendor/libs/apex-charts/apexcharts.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="<?=base_url();?>assets/js/dashboards-ecommerce.js"></script>
  </body>
</html>


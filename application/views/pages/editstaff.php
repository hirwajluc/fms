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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.css" />

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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Staff /</span> Edit Staff Member</h4>

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

              <?php if(validation_errors()): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <?=validation_errors();?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <div class="row">
                <!-- Form -->
                <div class="col-12">
                  <div class="card">
                    <h5 class="card-header">Edit Staff Member</h5>
                    <div class="card-body">
                      <form id="editStaffForm" class="row g-3" action="<?=base_url();?>updateStaff/<?=$staff['staff_id'];?>" method="POST">

                        <div class="col-12">
                          <h6 class="fw-semibold">1. Personal Information</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="first_name">First Name *</label>
                          <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="form-control"
                            placeholder="First Name"
                            value="<?=set_value('first_name', $staff['first_name']);?>"
                            required />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="last_name">Last Name *</label>
                          <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control"
                            placeholder="Last Name"
                            value="<?=set_value('last_name', $staff['last_name']);?>"
                            required />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="email">Email Address *</label>
                          <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="staff@example.com"
                            value="<?=set_value('email', $staff['email']);?>"
                            required />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="phone">Phone Number</label>
                          <input
                            type="text"
                            id="phone"
                            name="phone"
                            class="form-control"
                            placeholder="+250 xxx xxx xxx"
                            value="<?=set_value('phone', $staff['phone']);?>" />
                        </div>

                        <div class="col-12">
                          <h6 class="mt-2 fw-semibold">2. Institution & Professional Details</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="partner_id">Partner/Institution *</label>
                          <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                          <select
                            id="partner_id"
                            name="partner_id"
                            class="form-select select2"
                            required>
                            <option value="">Select Institution</option>
                            <?php if(isset($partners) && !empty($partners)): ?>
                              <?php foreach($partners as $partner): ?>
                                <option value="<?=$partner['partner_id'];?>" <?=set_select('partner_id', $partner['partner_id'], ($partner['partner_id'] == $staff['partner_id']));?>><?=$partner['name'];?></option>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </select>
                          <?php else: ?>
                          <input
                            class="form-control"
                            type="text"
                            value="<?=$staff['partner_name'];?>"
                            disabled/>
                          <input type="hidden" name="partner_id" value="<?=$staff['partner_id'];?>" />
                          <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="position">Position/Title *</label>
                          <input
                            type="text"
                            id="position"
                            name="position"
                            class="form-control"
                            placeholder="e.g., Research Assistant, Lecturer"
                            value="<?=set_value('position', $staff['position']);?>"
                            required />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="department">Department</label>
                          <input
                            type="text"
                            id="department"
                            name="department"
                            class="form-control"
                            placeholder="e.g., Computer Science, Engineering"
                            value="<?=set_value('department', $staff['department']);?>" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="greater_role">GREATER Project Role</label>
                          <input
                            type="text"
                            id="greater_role"
                            name="greater_role"
                            class="form-control"
                            placeholder="e.g., Researcher, Developer"
                            value="<?=set_value('greater_role', $staff['greater_role']);?>" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="employee_number">Employee Number</label>
                          <input
                            type="text"
                            id="employee_number"
                            name="employee_number"
                            class="form-control"
                            placeholder="Staff ID/Number"
                            value="<?=set_value('employee_number', $staff['employee_number']);?>" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="hire_date">Hire Date</label>
                          <input
                            type="text"
                            id="hire_date"
                            name="hire_date"
                            class="form-control flatpickr"
                            placeholder="Select date"
                            value="<?=set_value('hire_date', $staff['hire_date']);?>" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="status">Status *</label>
                          <select
                            id="status"
                            name="status"
                            class="form-select"
                            required>
                            <option value="active" <?=set_select('status', 'active', ($staff['status'] == 'active'));?>>Active</option>
                            <option value="inactive" <?=set_select('status', 'inactive', ($staff['status'] == 'inactive'));?>>Inactive</option>
                          </select>
                        </div>

                        <div class="col-12">
                          <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Update Staff Member
                          </button>
                          <a href="<?=base_url();?>users" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i> Cancel
                          </a>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
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
    <script src="<?=base_url();?>assets/vendor/libs/select2/select2.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/moment/moment.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script>
    $(document).ready(function() {
      // Initialize Select2
      $('.select2').select2();

      // Initialize Flatpickr for date picker
      $('.flatpickr').flatpickr({
        dateFormat: 'Y-m-d',
        allowInput: true
      });
    });
    </script>

  </body>
</html>

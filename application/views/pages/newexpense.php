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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/bootstrap-select/bootstrap-select.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/tagify/tagify.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/formvalidation/dist/css/formValidation.min.css" />

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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Expenses /</span> New Report</h4>

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

              <div class="row">
                <!-- FormValidation -->
                <div class="col-12">
                  <div class="card">
                    <h5 class="card-header">New Expense Report</h5>
                    <div class="card-body">
                      <form id="formValidationExamples" class="row g-3" action="<?=base_url();?>saveExpense" method="POST" enctype="multipart/form-data">
                        <!-- Account Details -->
                        
                        <?php
                        $FileName=$this->session->userdata("fms_partner")."-FS-".$uid;
                        ?>

                        <div class="col-12">
                          <h6 class="fw-semibold">1. Expense Details</h6>
                          <hr class="mt-0" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="formFileName">File Name</label>
                          <input
                            type="text"
                            id="formFileName"
                            class="form-control"
                            placeholder="File Name"
							value="<?=$FileName;?>"
                            name="formFileName" disabled/>
                            <input type="hidden" value="<?=$FileName;?>" name="formFN"/>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="formPartner">Partner</label>
                          <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                          <select
                            id="formPartnerId"
                            name="formPartnerId"
                            class="form-select select2"
                            data-allow-clear="true"
                            required>
                            <option value="">Select Partner</option>
                            <?php if(isset($partners) && !empty($partners)): ?>
                              <?php foreach($partners as $partner): ?>
                                <option value="<?=$partner['partner_id'];?>"><?=$partner['name'];?></option>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </select>
                          <?php else: ?>
                          <input
                            class="form-control"
                            type="text"
                            id="formPartner"
                            name="formPartner"
							value="<?=$this->session->userdata("fms_partner");?>"
                            placeholder="Partner Name" disabled/>
                          <?php endif; ?>
                        </div>

						<div class="col-md-6">
                          <label class="form-label" for="formCategory">Category</label>
                          <select
                            id="formCategory"
                            name="formCategory"
                            class="form-select select2"
                            data-allow-clear="true">
                            <option value="">Select</option>
                            <option value="travel">Travel</option>
                            <option value="accommodation">Accommodation</option>
                            <option value="subsistence">Subsistence</option>
							<option value="equipment">Equipment</option>
                            <option value="consumables">Consumables</option>
                            <option value="meetings">Services for Meetings, Seminars</option>
							<option value="communication">Services for communication/promotion/dissemination</option>
							<option value="other">Other</option>
                          </select>
                        </div>
						<div class="col-md-6">
                          <label class="form-label" for="formWorkPackage">Work Package</label>
                          <select
                            id="formWorkPackage"
                            name="formWorkPackage"
                            class="form-select select2"
                            data-allow-clear="true">
                            <option value="">Select</option>
                            <option value="wp1">Management and coordination</option>
                            <option value="wp2">Collaboration design</option>
                            <option value="wp3">Infrastructures</option>
							<option value="wp4">Curricula design</option>
							<option value="wp5">Training and coaching</option>
							<option value="wp6">Transfer methodologies</option>
							<option value="wp7">Impact and dissemination</option>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="formCurrency">Currency</label>
                          <select
                            id="formCurrency"
                            name="formCurrency"
                            class="form-select select2"
                            data-allow-clear="true">
                            <option value="">Select</option>
                            <option value="rwf">Rwandan Francs</option>
                            <option value="euro">Euro</option>
                            <option value="usd">USD</option>
                          </select>
                        </div>
						<div class="col-md-6">
                          <label class="form-label" for="formAmount">Amount</label>
                          <input
                            class="form-control"
                            type="number"
                            id="formAmount"
                            name="formAmount"
                            placeholder="Amount (Price)" />
                        </div>
						<!--<div class="col-md-6">
                          <label class="form-label" for="formRates">Exch. Rate</label>
                          <input
                            class="form-control"
                            type="number"
                            id="formRates"
                            name="formRates"
                            placeholder="Exchange Rate (Based on date of buying)" />
                        </div>-->

						<div class="col-md-6">
                          <label class="form-label" for="formShortDescription">Short Description</label>
                          <textarea
                            class="form-control"
                            id="formShortDescription"
                            name="formShortDescription"
                            rows="3"></textarea>
                        </div>

                        <!-- Personal Info -->

                        <div class="col-12">
                          <h6 class="mt-2 fw-semibold">2. File Details</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-6">
                          <label for="formValidationFile" class="form-label">File</label>
                          <input class="form-control" type="file" id="formValidationFile" name="formValidationFile" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="formValidationDate">Date</label>
                          <input
                            type="text"
                            class="form-control flatpickr-validation"
                            name="formValidationDate"
                            id="formValidationDate"
                            required />
                        </div>
                        
                        <div class="col-12">
                          <button type="submit" name="submitButton" class="btn btn-primary">Save</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <!-- /FormValidation -->
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
    <script src="<?=base_url();?>assets/vendor/libs/bootstrap-select/bootstrap-select.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/moment/moment.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/tagify/tagify.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="<?=base_url();?>assets/js/form-expenses.js"></script>
  </body>
</html>
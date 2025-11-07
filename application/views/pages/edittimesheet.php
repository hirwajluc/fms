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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Timesheets /</span> Edit Timesheet</h4>

              <?php if($timesheet['status'] == 'rejected' && !empty($timesheet['comments'])): ?>
              <div class="alert alert-warning">
                <h6 class="alert-heading"><i class="ti ti-alert-triangle me-1"></i> Rejection Comments:</h6>
                <p class="mb-0"><?=$timesheet['comments'];?></p>
              </div>
              <?php endif; ?>

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
                <!-- Form -->
                <div class="col-12">
                  <div class="card">
                    <h5 class="card-header">GREATER - Timesheet for Project Outputs</h5>
                    <div class="card-body">
                      <form id="editTimesheetForm" class="row g-3" action="<?=base_url();?>updateTimesheet/<?=$timesheet['timesheet_id'];?>" method="POST">

                        <div class="col-12">
                          <h6 class="fw-semibold">Staff Information</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Name</label>
                          <input type="text" class="form-control" value="<?=isset($user) ? $user['first_name'].' '.$user['last_name'] : '';?>" disabled />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Organization</label>
                          <input type="text" class="form-control" value="<?=isset($partner_name) ? $partner_name : '';?>" disabled />
                        </div>

                        <div class="col-md-4">
                          <label class="form-label" for="staff_category">Category of Staff *</label>
                          <select id="staff_category" name="staff_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Senior Lecturer / Professor" <?=$timesheet['staff_category'] == 'Senior Lecturer / Professor' ? 'selected' : '';?>>Senior Lecturer / Professor</option>
                            <option value="Junior Lecturer / Research assistant" <?=$timesheet['staff_category'] == 'Junior Lecturer / Research assistant' ? 'selected' : '';?>>Junior Lecturer / Research assistant</option>
                            <option value="Technician" <?=$timesheet['staff_category'] == 'Technician' ? 'selected' : '';?>>Technician</option>
                            <option value="Administrative" <?=$timesheet['staff_category'] == 'Administrative' ? 'selected' : '';?>>Administrative</option>
                            <option value="Other" <?=$timesheet['staff_category'] == 'Other' ? 'selected' : '';?>>Other</option>
                          </select>
                        </div>

                        <div class="col-md-4">
                          <label class="form-label">Year</label>
                          <input type="text" class="form-control" value="<?php
                            $months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
                            echo $timesheet['year'];
                          ?>" disabled />
                        </div>

                        <div class="col-md-4">
                          <label class="form-label">Month</label>
                          <input type="text" class="form-control" value="<?php
                            $month_name = isset($months[$timesheet['month']]) ? $months[$timesheet['month']] : $timesheet['month'];
                            echo $month_name;
                          ?>" disabled />
                        </div>

                        <div class="col-12">
                          <h6 class="mt-2 fw-semibold">Daily Time Entries</h6>
                          <hr class="mt-2" />
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="col-12">
                          <div class="table-responsive">
                            <table class="table table-bordered" id="entriesTable">
                              <thead>
                                <tr>
                                  <th style="width: 5%;">#</th>
                                  <th style="width: 15%;">Date (dd/mm/yyyy)</th>
                                  <th style="width: 10%;">Total Hours</th>
                                  <th style="width: 25%;">Work Package</th>
                                  <th style="width: 40%;">Comments</th>
                                  <th style="width: 5%;">Action</th>
                                </tr>
                              </thead>
                              <tbody id="entriesBody">
                                <!-- Entries will be added dynamically -->
                              </tbody>
                              <tfoot>
                                <tr>
                                  <td colspan="6">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addEntry()">
                                      <i class="ti ti-plus"></i> Add Entry
                                    </button>
                                  </td>
                                </tr>
                                <tr class="table-info">
                                  <td colspan="2" class="text-end"><strong>Total Hours:</strong></td>
                                  <td><strong id="totalHours">0</strong></td>
                                  <td colspan="3"></td>
                                </tr>
                              </tfoot>
                            </table>
                          </div>
                        </div>

                        <div class="col-12">
                          <button type="submit" class="btn btn-primary">
                            <i class="ti ti-save me-1"></i> Update & Resubmit Timesheet
                          </button>
                          <a href="<?=base_url();?>timesheets" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i> Cancel
                          </a>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <!-- /Form -->
              </div>
            </div>
            <!--/ Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
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
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/hammer/hammer.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18n/i18n.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>

    <!-- Vendors JS -->
    <script src="<?=base_url();?>assets/vendor/libs/select2/select2.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/moment/moment.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script>
    let entryCounter = 0;
    let excelEntries = [];

    $(document).ready(function() {
      $('.select2').select2();

      // Load existing entries
      <?php if(!empty($timesheet_details)): ?>
        <?php foreach($timesheet_details as $detail): ?>
          addEntryWithData(
            '<?=date('Y-m-d', strtotime($detail['date']));?>',
            '<?=$detail['hours'];?>',
            '<?=$detail['work_package'];?>',
            '<?=addslashes($detail['activity_description']);?>'
          );
        <?php endforeach; ?>
      <?php else: ?>
        // Add initial empty entry if no existing data
        addEntry();
      <?php endif; ?>

    });

    function addEntry() {
      entryCounter++;

      const row = `
        <tr id="entry_${entryCounter}">
          <td class="text-center">${entryCounter}</td>
          <td>
            <input type="date" name="entry_date[]" class="form-control form-control-sm" required />
          </td>
          <td>
            <input type="number" name="entry_hours[]" class="form-control form-control-sm entry-hours" min="0" step="0.5" placeholder="0.0" required onchange="calculateTotal()" />
          </td>
          <td>
            <select name="entry_work_package[]" class="form-select form-select-sm" required>
              <option value="">Select WP</option>
              <option value="WP1">WP1 - Management and coordination</option>
              <option value="WP2">WP2 - Collaboration design</option>
              <option value="WP3">WP3 - Infrastructures</option>
              <option value="WP4">WP4 - Curricula design</option>
              <option value="WP5">WP5 - Training and coaching</option>
              <option value="WP6">WP6 - Transfer methodologies</option>
              <option value="WP7">WP7 - Impact and dissemination</option>
            </select>
          </td>
          <td>
            <input type="text" name="entry_description[]" class="form-control form-control-sm" placeholder="Describe work done..." />
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeEntry(${entryCounter})">
              <i class="ti ti-trash"></i>
            </button>
          </td>
        </tr>
      `;

      $('#entriesBody').append(row);
      calculateTotal();
    }

    function addEntryWithData(date, hours, workPackage, description) {
      entryCounter++;

      const row = `
        <tr id="entry_${entryCounter}">
          <td class="text-center">${entryCounter}</td>
          <td>
            <input type="date" name="entry_date[]" class="form-control form-control-sm" value="${date}" required />
          </td>
          <td>
            <input type="number" name="entry_hours[]" class="form-control form-control-sm entry-hours" min="0" step="0.5" value="${hours}" required onchange="calculateTotal()" />
          </td>
          <td>
            <select name="entry_work_package[]" class="form-select form-select-sm" required>
              <option value="">Select WP</option>
              <option value="WP1" ${workPackage === 'WP1' ? 'selected' : ''}>WP1 - Management and coordination</option>
              <option value="WP2" ${workPackage === 'WP2' ? 'selected' : ''}>WP2 - Collaboration design</option>
              <option value="WP3" ${workPackage === 'WP3' ? 'selected' : ''}>WP3 - Infrastructures</option>
              <option value="WP4" ${workPackage === 'WP4' ? 'selected' : ''}>WP4 - Curricula design</option>
              <option value="WP5" ${workPackage === 'WP5' ? 'selected' : ''}>WP5 - Training and coaching</option>
              <option value="WP6" ${workPackage === 'WP6' ? 'selected' : ''}>WP6 - Transfer methodologies</option>
              <option value="WP7" ${workPackage === 'WP7' ? 'selected' : ''}>WP7 - Impact and dissemination</option>
            </select>
          </td>
          <td>
            <input type="text" name="entry_description[]" class="form-control form-control-sm" value="${description}" placeholder="Describe work done..." />
          </td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger" onclick="removeEntry(${entryCounter})">
              <i class="ti ti-trash"></i>
            </button>
          </td>
        </tr>
      `;

      $('#entriesBody').append(row);
      calculateTotal();
    }

    function removeEntry(id) {
      $('#entry_' + id).remove();
      calculateTotal();
      renumberEntries();
    }

    function renumberEntries() {
      $('#entriesBody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
      });
    }

    function calculateTotal() {
      let total = 0;
      $('.entry-hours').each(function() {
        const value = parseFloat($(this).val()) || 0;
        total += value;
      });
      $('#totalHours').text(total.toFixed(1));
    }

    // Form validation before submit
    $('#editTimesheetForm').on('submit', function(e) {
      const entryCount = $('#entriesBody tr').length;
      if (entryCount === 0) {
        e.preventDefault();
        alert('Please add at least one daily entry.');
        return false;
      }

      const totalHours = parseFloat($('#totalHours').text());
      if (totalHours === 0) {
        e.preventDefault();
        alert('Total hours cannot be 0. Please add hours to your entries.');
        return false;
      }

      return true;
    });
    </script>

  </body>
</html>

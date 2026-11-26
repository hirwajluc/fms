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
                          <input type="text" class="form-control" value="<?=htmlspecialchars(trim(($timesheet['first_name'] ?? '').' '.($timesheet['last_name'] ?? '')));?>" disabled />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Organization</label>
                          <input type="text" class="form-control" value="<?=htmlspecialchars($timesheet['partner_name'] ?? '');?>" disabled />
                        </div>

                        <div class="col-md-6">
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

                        <?php $period_label = date('F Y', mktime(0, 0, 0, (int)$timesheet['month'], 1, (int)$timesheet['year'])); ?>
                        <div class="col-md-6">
                          <label class="form-label" for="periodDisplay">Period</label>
                          <input type="text" id="periodDisplay" class="form-control" value="<?=htmlspecialchars($period_label);?>" readonly tabindex="-1" />
                          <div class="form-text">Set by the dates of the entries. A timesheet covers one month.</div>
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

                        <div class="col-12" id="monthNote" style="display: none;">
                          <div class="alert mb-0" role="alert" id="monthNoteText"></div>
                        </div>

                        <div class="col-12">
                          <button type="submit" class="btn btn-primary" id="submitTimesheet">
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

    // A timesheet covers one month; the month follows the entry dates
    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const takenMonths = <?=json_encode((object)($taken_months ?? array()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;
    const baseUrl = <?=json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;
    const originalKey = <?=json_encode(sprintf('%04d-%02d', $timesheet['year'], $timesheet['month']));?>;
    const originalLabel = <?=json_encode($period_label, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;
    const savedEntries = <?=json_encode(array_map(function($d){
      return array(
        'date'  => date('Y-m-d', strtotime($d['date'])),
        'hours' => (string)$d['hours'],
        'wp'    => (string)$d['work_package'],
        'desc'  => (string)$d['activity_description'],
      );
    }, $timesheet_details ?? array()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;

    $(document).ready(function() {
      $('.select2').select2();

      // Load existing entries (an empty row if there are none)
      if (savedEntries.length) {
        savedEntries.forEach(function(e) { addEntryWithData(e.date, e.hours, e.wp, e.desc); });
      } else {
        addEntry();
      }
      $('#entriesBody').on('input change', 'input', refreshPeriod);
      refreshPeriod();
    });

    function joinLabels(labels) {
      return labels.length > 1 ? labels.slice(0, -1).join(', ') + ' and ' + labels[labels.length - 1] : (labels[0] || '');
    }

    function groupByMonth() {
      const hours = {};
      $('#entriesBody tr').each(function() {
        const date = $(this).find('input[name="entry_date[]"]').val() || '';
        const h = parseFloat($(this).find('.entry-hours').val()) || 0;
        if (!(h > 0) || !/^\d{4}-\d{2}-\d{2}$/.test(date)) return;
        hours[date.slice(0, 7)] = (hours[date.slice(0, 7)] || 0) + h;
      });
      return Object.keys(hours).sort().map(function(key) {
        return { key: key, label: MONTH_NAMES[parseInt(key.slice(5), 10) - 1] + ' ' + key.slice(0, 4) };
      });
    }

    // Keeps the Period box in step with the dates and stops a save that would break the one-month rule
    function refreshPeriod() {
      const months = groupByMonth();
      const $note = $('#monthNoteText').removeClass('alert-danger alert-info').empty();
      let problem = false;

      if (!months.length) {
        $('#periodDisplay').val(originalLabel);
      } else if (months.length === 1) {
        const m = months[0];
        const other = takenMonths[m.key];
        $('#periodDisplay').val(m.label);
        if (other) {
          problem = true;
          const editable = other.status === 'draft' || other.status === 'rejected';
          $note.addClass('alert-danger')
            .append(document.createTextNode('There is already another timesheet for ' + m.label + ' ('))
            .append($('<a target="_blank" rel="noopener"></a>')
              .attr('href', baseUrl + (editable ? 'editTimesheet/' : 'viewTimesheet/') + other.id)
              .text('open it'))
            .append(document.createTextNode('). Change that one instead, or keep these dates in ' + originalLabel + '.'));
        } else if (m.key !== originalKey) {
          $note.addClass('alert-info').text('The dates are now in ' + m.label + ', so this timesheet will move from ' + originalLabel + ' to ' + m.label + ' when you save.');
        }
      } else {
        problem = true;
        $('#periodDisplay').val(months[0].label + ' – ' + months[months.length - 1].label + ' (' + months.length + ' months)');
        $note.addClass('alert-danger').text('A timesheet covers one month, but these dates fall in '
          + joinLabels(months.map(function(m) { return m.label; }))
          + '. Keep one month here and add the other dates with New Timesheet.');
      }

      $('#monthNote').toggle($note.text() !== '');
      $('#submitTimesheet').prop('disabled', problem);
    }

    function addEntry() {
      entryCounter++;

      const row = `
        <tr id="entry_${entryCounter}">
          <td class="text-center">${entryCounter}</td>
          <td>
            <input type="date" name="entry_date[]" class="form-control form-control-sm" required />
          </td>
          <td>
            <input type="number" name="entry_hours[]" class="form-control form-control-sm entry-hours" min="0" step="0.01" placeholder="0.0" required onchange="calculateTotal()" />
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

    // Values are set with .val(), so quotes or markup in a description can't break the page
    function addEntryWithData(date, hours, workPackage, description) {
      addEntry();
      const $row = $('#entry_' + entryCounter);
      $row.find('input[name="entry_date[]"]').val(date);
      $row.find('input[name="entry_hours[]"]').val(hours);
      $row.find('select[name="entry_work_package[]"]').val(workPackage);
      $row.find('input[name="entry_description[]"]').val(description);
      calculateTotal();
    }

    function removeEntry(id) {
      $('#entry_' + id).remove();
      calculateTotal();
      renumberEntries();
      refreshPeriod();
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
      $('#totalHours').text(total.toFixed(2));
    }

    // Form validation before submit
    $('#editTimesheetForm').on('submit', function(e) {
      refreshPeriod();
      if ($('#submitTimesheet').prop('disabled')) {
        e.preventDefault();
        alert($('#monthNoteText').text());
        return false;
      }

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

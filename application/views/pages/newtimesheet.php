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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Timesheets /</span> New Timesheet</h4>

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
                      <form id="newTimesheetForm" class="row g-3" action="<?=base_url();?>saveTimesheet" method="POST">

                        <div class="col-12">
                          <h6 class="fw-semibold">Staff Information</h6>
                          <hr class="mt-0" />
                        </div>

                        <?php if(!empty($staff_users)): ?>
                        <!-- Coordinator: pick which staff member this timesheet is for -->
                        <div class="col-12">
                          <label class="form-label fw-semibold" for="for_user_id">
                            <i class="ti ti-user-check me-1 text-primary"></i>Creating timesheet for
                          </label>
                          <select id="for_user_id" name="for_user_id" class="form-select select2" required>
                            <?php foreach($staff_users as $su):
                              $self = ($su['user_id'] == $user['user_id']);
                            ?>
                            <option value="<?=$su['user_id'];?>"
                                    data-name="<?=htmlspecialchars($su['first_name'].' '.$su['last_name']);?>"
                                    <?=$self ? 'selected' : '';?>>
                              <?=htmlspecialchars($su['first_name'].' '.$su['last_name']);?>
                              <?=$self ? '(myself)' : '';?>
                            </option>
                            <?php endforeach; ?>
                          </select>
                          <div class="form-text">Select the staff member you are submitting this timesheet on behalf of.</div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="for_user_id" value="0" />
                        <?php endif; ?>

                        <div class="col-md-6">
                          <label class="form-label">Name</label>
                          <input type="text" id="displayName" class="form-control"
                                 value="<?=isset($user) ? htmlspecialchars($user['first_name'].' '.$user['last_name']) : '';?>" disabled />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Organization</label>
                          <input type="text" class="form-control" value="<?=isset($partner_name) ? htmlspecialchars($partner_name) : '';?>" disabled />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="staff_category">Category of Staff *</label>
                          <select id="staff_category" name="staff_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Senior Lecturer / Professor">Senior Lecturer / Professor</option>
                            <option value="Junior Lecturer / Research assistant">Junior Lecturer / Research assistant</option>
                            <option value="Technician">Technician</option>
                            <option value="Administrative">Administrative</option>
                            <option value="Other">Other</option>
                          </select>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="periodDisplay">Period</label>
                          <input type="text" id="periodDisplay" class="form-control" value="" placeholder="Set by the dates of your entries" readonly tabindex="-1" />
                          <div class="form-text">No need to pick a month: each entry's date decides it. Entries in different months become one timesheet per month.</div>
                        </div>

                        <div class="col-12">
                          <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mt-2 fw-semibold">Daily Time Entries</h6>
                            <div class="btn-group" role="group">
                              <input type="radio" class="btn-check" name="entry_mode" id="manual_mode" value="manual" checked autocomplete="off">
                              <label class="btn btn-outline-primary" for="manual_mode">
                                <i class="ti ti-edit"></i> Manual Entry
                              </label>

                              <input type="radio" class="btn-check" name="entry_mode" id="excel_mode" value="excel" autocomplete="off">
                              <label class="btn btn-outline-primary" for="excel_mode">
                                <i class="ti ti-file-spreadsheet"></i> Upload Excel
                              </label>
                            </div>
                          </div>
                          <hr class="mt-2" />
                        </div>

                        <!-- Excel Upload Section -->
                        <div class="col-12" id="excelUploadSection" style="display: none;">
                          <div class="alert alert-info">
                            <h6 class="alert-heading mb-2">
                              <i class="ti ti-info-circle me-1"></i> Upload GREATER Timesheet Excel Template
                            </h6>
                            <p class="mb-2">Upload the completed GREATER timesheet Excel file (like GREATER - TimeSheet- HIRWAJeanLuc-v1.xlsx) and we'll automatically extract all your daily entries.</p>
                            <small class="text-muted">
                              <strong>Note:</strong> The Excel file should have the daily entries on the "Timesheet" sheet with columns: Total Hours, Date (dd/mm/yyyy), Work Package, and Comments.
                            </small>
                          </div>

                          <div class="mb-3">
                            <label for="excel_file" class="form-label">Select Excel File *</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" />
                            <small class="text-muted">Supported formats: .xlsx, .xls (Max 10MB)</small>
                          </div>

                          <div id="excelPreview" style="display: none;">
                            <h6 class="fw-semibold">Preview of Extracted Entries:</h6>
                            <div class="table-responsive">
                              <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                  <tr>
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Work Package</th>
                                    <th>Comments</th>
                                  </tr>
                                </thead>
                                <tbody id="excelPreviewBody">
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="col-12" id="manualEntrySection">
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

                        <!-- Which monthly timesheet(s) the entries make, from their dates -->
                        <div class="col-12" id="monthSplit" style="display: none;">
                          <div class="border rounded p-3">
                            <div class="fw-semibold mb-2">
                              <i class="ti ti-calendar me-1 text-primary"></i><span id="monthSplitTitle"></span>
                            </div>
                            <div id="monthSplitList" class="d-flex flex-wrap gap-2"></div>
                            <div id="monthSplitWarning" class="text-danger small mt-2" style="display: none;"></div>
                          </div>
                        </div>

                        <div class="col-12">
                          <button type="submit" class="btn btn-primary" id="submitTimesheet">
                            <i class="ti ti-send me-1"></i> Submit Timesheet
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
    <script src="<?=base_url();?>assets/js/main.js?v=<?=filemtime(FCPATH.'assets/js/main.js');?>"></script>

    <!-- Page JS -->
    <script>
    let entryCounter = 0;
    let excelEntries = [];

    // A timesheet covers one month; the month comes from each entry's date
    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const takenMonths = <?=json_encode((object)($taken_months ?? array()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;
    const ownUserId = <?=(int)($own_user_id ?? 0);?>;
    const baseUrl = <?=json_encode(base_url(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);?>;

    function escapeHtml(value) {
      return String(value == null ? '' : value).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    }

    function formatHours(h) {
      return String(Math.round(h * 100) / 100);
    }

    function joinLabels(labels) {
      return labels.length > 1 ? labels.slice(0, -1).join(', ') + ' and ' + labels[labels.length - 1] : (labels[0] || '');
    }

    function currentEntries() {
      if ($('input[name="entry_mode"]:checked').val() === 'excel') {
        return excelEntries.map(function(e) { return { date: e.date_raw, hours: parseFloat(e.hours) || 0 }; });
      }
      const list = [];
      $('#entriesBody tr').each(function() {
        list.push({
          date: $(this).find('input[name="entry_date[]"]').val(),
          hours: parseFloat($(this).find('.entry-hours').val()) || 0
        });
      });
      return list;
    }

    function groupByMonth(entries) {
      const hours = {};
      entries.forEach(function(e) {
        if (!(e.hours > 0) || !/^\d{4}-\d{2}-\d{2}$/.test(e.date || '')) return;
        const key = e.date.slice(0, 7);
        hours[key] = (hours[key] || 0) + e.hours;
      });
      return Object.keys(hours).sort().map(function(key) {
        return { key: key, label: MONTH_NAMES[parseInt(key.slice(5), 10) - 1] + ' ' + key.slice(0, 4), hours: hours[key] };
      });
    }

    function targetUserId() {
      const chosen = parseInt($('#for_user_id').val(), 10);
      return chosen > 0 ? chosen : ownUserId;
    }

    // Shows the period and which monthly timesheet(s) will be created; blocks months that already have one
    function refreshPeriod() {
      const months = groupByMonth(currentEntries());
      const taken = takenMonths[targetUserId()] || {};
      const clashes = [];
      const $list = $('#monthSplitList').empty();

      if (!months.length) {
        $('#periodDisplay').val('');
        $('#monthSplit').hide();
        $('#submitTimesheet').prop('disabled', false);
        return;
      }

      $('#periodDisplay').val(months.length === 1
        ? months[0].label
        : months[0].label + ' – ' + months[months.length - 1].label + ' (' + months.length + ' months)');
      $('#monthSplitTitle').text(months.length === 1
        ? 'These entries make one timesheet'
        : 'These entries make ' + months.length + ' timesheets, one per month');

      months.forEach(function(m) {
        const existing = taken[m.key];
        if (existing) {
          clashes.push(m.label);
          const editable = existing.status === 'draft' || existing.status === 'rejected';
          $list.append($('<a class="badge bg-label-danger text-wrap text-start mw-100" target="_blank" rel="noopener"></a>')
            .attr('href', baseUrl + (editable ? 'editTimesheet/' : 'viewTimesheet/') + existing.id)
            .attr('title', editable ? 'Open it to change it' : 'Open it')
            .text(m.label + ' · already has a timesheet (' + existing.status + ')'));
        } else {
          $list.append($('<span class="badge bg-label-primary text-wrap text-start mw-100"></span>').text(m.label + ' · ' + formatHours(m.hours) + ' h'));
        }
      });

      if (clashes.length) {
        $('#monthSplitWarning').text((clashes.length === 1
          ? clashes[0] + ' already has a timesheet.'
          : joinLabels(clashes) + ' already have timesheets.')
          + ' Change the existing one instead, or remove those dates here.').show();
      } else {
        $('#monthSplitWarning').hide().text('');
      }
      $('#submitTimesheet').prop('disabled', clashes.length > 0);
      $('#monthSplit').show();
    }

    $(document).ready(function() {
      $('.select2').select2();

      // When coordinator changes the "Creating for" staff member, update the displayed name
      $('#for_user_id').on('change', function(){
        var selected = $(this).find('option:selected');
        $('#displayName').val(selected.data('name') || selected.text().replace('(myself)', '').trim());
        refreshPeriod();
      });

      // Add initial entry for manual mode
      addEntry();
      $('#entriesBody').on('input change', 'input', refreshPeriod);

      // Handle mode toggle
      $('input[name="entry_mode"]').on('change', function() {
        if ($(this).val() === 'manual') {
          $('#manualEntrySection').show();
          $('#excelUploadSection').hide();
          $('#excel_file').prop('required', false);
          // Re-enable the manual entry fields
          $('#entriesTable input, #entriesTable select').prop('disabled', false).prop('required', true);
        } else {
          $('#manualEntrySection').hide();
          $('#excelUploadSection').show();
          $('#excel_file').prop('required', true);
          // Manual rows are not sent in Excel mode
          $('#entriesTable input, #entriesTable select').prop('required', false).prop('disabled', true);
        }
        refreshPeriod();
      });

      // Handle Excel file upload and parsing
      $('#excel_file').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('excel_file', file);
        excelEntries = [];
        refreshPeriod();

        // Show loading with progress bar
        let progressHtml = '<div class="text-center p-4">';
        progressHtml += '<div class="mb-3"><i class="ti ti-file-upload" style="font-size: 3rem; color: #696cff;"></i></div>';
        progressHtml += '<h5 class="mb-3">Processing Excel File</h5>';
        progressHtml += '<div class="progress mb-3" style="height: 25px;">';
        progressHtml += '<div id="uploadProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">';
        progressHtml += '<span class="fw-semibold">0%</span>';
        progressHtml += '</div>';
        progressHtml += '</div>';
        progressHtml += '<p class="text-muted mb-0" id="progressStatus">Uploading file...</p>';
        progressHtml += '</div>';
        $('#excelPreview').html(progressHtml).show();

        // Simulate progress stages
        let progress = 0;
        const progressInterval = setInterval(function() {
          if (progress < 30) {
            progress += 5;
            updateProgress(progress, 'Uploading file...');
          } else if (progress < 60) {
            progress += 3;
            updateProgress(progress, 'Reading Excel structure...');
          } else if (progress < 90) {
            progress += 2;
            updateProgress(progress, 'Extracting timesheet entries...');
          }
        }, 150);

        $.ajax({
          url: '<?=base_url();?>parseTimesheetExcel',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
            clearInterval(progressInterval);
            updateProgress(100, 'Processing complete!');

            setTimeout(function() {
              if (response.success) {
                excelEntries = response.entries;
                displayExcelPreview(response.entries, response.staff_info);
                refreshPeriod();

                // Auto-fill staff category if the file's value is one of the choices
                const fileCategory = response.staff_info && response.staff_info.staff_category;
                if (fileCategory && $('#staff_category option').filter(function() { return this.value === fileCategory; }).length) {
                  $('#staff_category').val(fileCategory);
                }
              } else {
                let errorMsg = '<div class="alert alert-danger">' + response.message;

                // Show debug info if available
                if (response.debug) {
                  errorMsg += '<hr class="my-2">';
                  errorMsg += '<small><strong>Debug Information:</strong><br>';
                  if (response.debug.sheet_names) {
                    errorMsg += 'Sheets in file: ' + response.debug.sheet_names.join(', ') + '<br>';
                  }
                  if (response.debug.sheet_name) {
                    errorMsg += 'Using sheet: ' + response.debug.sheet_name + '<br>';
                  }
                  if (response.debug.error_type) {
                    errorMsg += 'Error type: ' + response.debug.error_type + '<br>';
                  }
                  if (response.debug.error_file && response.debug.error_line) {
                    errorMsg += 'Location: ' + response.debug.error_file + ':' + response.debug.error_line;
                  }
                  errorMsg += '</small>';
                }

                errorMsg += '</div>';
                $('#excelPreview').html(errorMsg).show();
              }
            }, 500);
          },
          error: function(xhr, status, error) {
            clearInterval(progressInterval);
            updateProgress(100, 'Error occurred!');

            setTimeout(function() {
              let errorMsg = '<div class="alert alert-danger">Error parsing Excel file. Please try again.';

              if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += '<br><small>' + xhr.responseJSON.message + '</small>';
              }

              errorMsg += '</div>';
              $('#excelPreview').html(errorMsg).show();
            }, 500);
          }
        });
      });
    });

    function updateProgress(percentage, statusText) {
      const progressBar = $('#uploadProgress');
      progressBar.css('width', percentage + '%');
      progressBar.attr('aria-valuenow', percentage);
      progressBar.find('span').text(percentage + '%');
      $('#progressStatus').text(statusText);

      // Change color based on completion
      if (percentage >= 100) {
        progressBar.removeClass('bg-primary').addClass('bg-success');
      }
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

    function displayExcelPreview(entries, staffInfo) {
      let html = '<h6 class="fw-semibold">Preview of Extracted Entries:</h6>';
      html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
      html += '<thead class="table-light"><tr><th>Date</th><th>Hours</th><th>Work Package</th><th>Comments</th></tr></thead>';
      html += '<tbody>';

      let totalHours = 0;
      entries.forEach(function(entry) {
        html += '<tr>';
        html += '<td>' + escapeHtml(entry.date) + '</td>';
        html += '<td>' + escapeHtml(entry.hours) + '</td>';
        html += '<td>' + escapeHtml(entry.work_package) + '</td>';
        html += '<td>' + escapeHtml(entry.comments) + '</td>';
        html += '</tr>';
        totalHours += parseFloat(entry.hours);
      });

      html += '</tbody>';
      html += '<tfoot class="table-info"><tr><th>Total</th><th>' + totalHours.toFixed(2) + ' hours</th><th colspan="2"></th></tr></tfoot>';
      html += '</table></div>';

      if (staffInfo && (staffInfo.name || staffInfo.organization || staffInfo.staff_category)) {
        html += '<div class="alert alert-success mt-3">';
        html += '<strong>Extracted Staff Info:</strong><br>';
        if (staffInfo.name) html += 'Name: ' + escapeHtml(staffInfo.name) + '<br>';
        if (staffInfo.organization) html += 'Organization: ' + escapeHtml(staffInfo.organization) + '<br>';
        if (staffInfo.staff_category) html += 'Category: ' + escapeHtml(staffInfo.staff_category);
        html += '</div>';
      }

      $('#excelPreview').html(html).show();
    }

    // Form validation before submit
    $('#newTimesheetForm').on('submit', function(e) {
      const mode = $('input[name="entry_mode"]:checked').val();

      refreshPeriod();
      if ($('#submitTimesheet').prop('disabled')) {
        e.preventDefault();
        alert($('#monthSplitWarning').text());
        return false;
      }

      if (mode === 'manual') {
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
      } else {
        // Excel mode
        if (excelEntries.length === 0) {
          e.preventDefault();
          alert('Please upload an Excel file with timesheet entries.');
          return false;
        }

        // Add excel entries as hidden inputs (once, even if the form is submitted again)
        $('#newTimesheetForm input.excel-entry').remove();
        excelEntries.forEach(function(entry, index) {
          [['entry_date[]', entry.date_raw], ['entry_hours[]', entry.hours],
           ['entry_work_package[]', entry.work_package_code], ['entry_description[]', entry.comments]
          ].forEach(function(field) {
            $('<input>').attr({ type: 'hidden', name: field[0], 'class': 'excel-entry' })
              .val(field[1] == null ? '' : field[1])
              .appendTo('#newTimesheetForm');
          });
        });
      }

      return true;
    });
    </script>

  </body>
</html>

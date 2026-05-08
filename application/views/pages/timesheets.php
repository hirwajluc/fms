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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Timesheets /</span> Reports</h4>

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

              <!-- Responsive Datatable -->
              <div class="card">
                <!-- Card header: action button -->
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">Timesheet Records</h5>
                  <div>
                    <a href="<?=base_url();?>newTimesheet" class="btn btn-info btn-sm me-2">
                      <span class="tf-icons ti ti-upload me-1"></span> Submit New Timesheet
                    </a>
                    <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#generateTsReportModal">
                      <span class="tf-icons ti ti-file-download me-1"></span> Generate Report
                    </button>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Filter bar -->
                <div class="card-body border-bottom py-3">
                  <div class="row g-2 align-items-end flex-wrap">
                    <!-- Status fixed buttons -->
                    <div class="col-12 col-xl-auto">
                      <label class="form-label small text-muted mb-1 d-block">Status</label>
                      <div class="btn-group btn-group-sm" id="tsStatusBtns">
                        <button class="btn btn-outline-secondary active" data-val="">All</button>
                        <button class="btn btn-outline-warning"   data-val="submitted">Submitted</button>
                        <button class="btn btn-outline-success"   data-val="approved">Approved</button>
                        <button class="btn btn-outline-danger"    data-val="rejected">Rejected</button>
                      </div>
                    </div>
                    <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                    <!-- Partner filter — all partners from DB -->
                    <div class="col-12 col-sm-6 col-xl-3">
                      <label class="form-label small text-muted mb-1">Partner / Institution</label>
                      <select class="form-select form-select-sm" id="tsPartnerFilter">
                        <option value="">All Partners</option>
                        <?php if(!empty($all_partners)): foreach($all_partners as $p): ?>
                        <option value="<?=htmlspecialchars($p['name']);?>"><?=htmlspecialchars($p['name']);?></option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                    <?php endif; ?>
                    <!-- Period range -->
                    <div class="col-6 col-sm-3 col-xl-2">
                      <label class="form-label small text-muted mb-1">Period From</label>
                      <input type="text" class="form-control form-control-sm" id="tsPeriodFrom" placeholder="Select month…" readonly />
                    </div>
                    <div class="col-6 col-sm-3 col-xl-2">
                      <label class="form-label small text-muted mb-1">Period To</label>
                      <input type="text" class="form-control form-control-sm" id="tsPeriodTo" placeholder="Select month…" readonly />
                    </div>
                    <!-- Reset -->
                    <div class="col-auto ms-auto">
                      <button class="btn btn-sm btn-outline-secondary" id="tsResetFilters">
                        <i class="ti ti-refresh me-1"></i> Reset
                      </button>
                    </div>
                  </div>
                </div>

                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table" id="timesheetsTable">
                    <thead>
                      <tr>
                        <th></th>
                        <th>Staff Member</th>
						<th>Partner/Institution</th>
						<th>Period</th>
						<th>Staff Category</th>
						<th>Total Hours</th>
						<th>Submitted Date</th>
						<th>Status</th>
						<th>Actions</th>
                      </tr>
                    </thead>
					<tbody>
						<?php foreach ($timesheets as $timesheet) {
									// Status badge color
									$status_badge = '';
									switch($timesheet['status']){
										case 'approved':
											$status_badge = 'bg-success';
											break;
										case 'rejected':
											$status_badge = 'bg-danger';
											break;
										case 'submitted':
											$status_badge = 'bg-warning';
											break;
										case 'draft':
										default:
											$status_badge = 'bg-secondary';
											break;
									}

									// Month name
									$months = array(
										1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
										5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
										9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
									);
									$month_name = isset($months[$timesheet['month']]) ? $months[$timesheet['month']] : $timesheet['month'];
						?>
						<tr>
							<td></td>
							<td>
								<strong><?=$timesheet['first_name'].' '.$timesheet['last_name'];?></strong>
							</td>
							<td><?=isset($timesheet['partner_name']) ? $timesheet['partner_name'] : 'N/A';?></td>
							<td><?=$month_name.' '.$timesheet['year'];?></td>
							<td><?=isset($timesheet['staff_category']) ? $timesheet['staff_category'] : 'N/A';?></td>
							<td><?=number_format($timesheet['total_hours'], 1);?> hrs</td>
							<td><?=!empty($timesheet['submitted_at']) ? date('M d, Y', strtotime($timesheet['submitted_at'])) : 'Not submitted';?></td>
							<td>
								<span class="badge <?=$status_badge;?>"><?=ucfirst($timesheet['status']);?></span>
							</td>
							<td>
								<a href="<?=base_url();?>viewTimesheet/<?=$timesheet['timesheet_id'];?>" class="btn btn-sm btn-info">
									<i class="ti ti-eye"></i> View
								</a>
								<?php
									// Allow user to edit their own draft/rejected timesheets
									$user_id = $this->session->userdata('fms_user_id');
									if($timesheet['user_id'] == $user_id && ($timesheet['status'] == 'draft' || $timesheet['status'] == 'rejected')):
								?>
									<a href="<?=base_url();?>editTimesheet/<?=$timesheet['timesheet_id'];?>" class="btn btn-sm btn-warning">
										<i class="ti ti-edit"></i> Edit
									</a>
								<?php endif; ?>

								<?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
									<?php if($timesheet['status'] == 'submitted'): ?>
									<button type="button" class="btn btn-sm btn-success" onclick="approveTimesheet(<?=$timesheet['timesheet_id'];?>)">
										<i class="ti ti-check"></i>
									</button>
									<button type="button" class="btn btn-sm btn-danger" onclick="rejectTimesheet(<?=$timesheet['timesheet_id'];?>)">
										<i class="ti ti-x"></i>
									</button>
									<?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
						<?php } ?>
					</tbody>
                  </table>
                </div>
              </div>
              <!--/ Responsive Datatable -->
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

    <!-- Generate Timesheet Report Modal -->
    <div class="modal fade" id="generateTsReportModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Timesheet Report</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="generateTsReportForm">
            <div class="modal-body">
              <input type="hidden" name="from_period" id="tsRptFromHidden" />
              <input type="hidden" name="to_period"   id="tsRptToHidden" />
              <div class="mb-3">
                <label class="form-label">From</label>
                <div class="row g-2">
                  <div class="col-7">
                    <select class="form-select" id="tsRptFromMonth">
                      <option value="">Month</option>
                      <option value="01">January</option><option value="02">February</option>
                      <option value="03">March</option><option value="04">April</option>
                      <option value="05">May</option><option value="06">June</option>
                      <option value="07">July</option><option value="08">August</option>
                      <option value="09">September</option><option value="10">October</option>
                      <option value="11">November</option><option value="12">December</option>
                    </select>
                  </div>
                  <div class="col-5">
                    <select class="form-select" id="tsRptFromYear">
                      <option value="">Year</option>
                      <option value="2023">2023</option><option value="2024">2024</option>
                      <option value="2025">2025</option><option value="2026">2026</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">To</label>
                <div class="row g-2">
                  <div class="col-7">
                    <select class="form-select" id="tsRptToMonth">
                      <option value="">Month</option>
                      <option value="01">January</option><option value="02">February</option>
                      <option value="03">March</option><option value="04">April</option>
                      <option value="05">May</option><option value="06">June</option>
                      <option value="07">July</option><option value="08">August</option>
                      <option value="09">September</option><option value="10">October</option>
                      <option value="11">November</option><option value="12">December</option>
                    </select>
                  </div>
                  <div class="col-5">
                    <select class="form-select" id="tsRptToYear">
                      <option value="">Year</option>
                      <option value="2023">2023</option><option value="2024">2024</option>
                      <option value="2025">2025</option><option value="2026">2026</option>
                    </select>
                  </div>
                </div>
              </div>
              <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
              <div class="mb-3">
                <label class="form-label">Partner (Optional)</label>
                <select class="form-control" name="partner_id">
                  <option value="">All Partners</option>
                  <?php foreach($all_partners as $p): ?>
                  <option value="<?=$p['partner_id'];?>"><?=htmlspecialchars($p['name']);?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="mb-3">
                <label class="form-label">Status Filter</label>
                <select class="form-control" name="status">
                  <option value="">All Status</option>
                  <option value="submitted">Submitted</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
              <div id="tsRptStatus" class="alert alert-info d-none">
                <span id="tsRptStatusText">Generating report…</span>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success" id="generateTsRptBtn">
                <i class="ti ti-file-download me-1"></i> Generate Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PDF Viewer Modal (reused for timesheet report) -->
    <div class="modal fade" id="tsRptPdfModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="tsRptPdfTitle">Timesheet Report</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <iframe id="tsRptPdfFrame" style="width:100%;height:75vh;border:none;"></iframe>
          </div>
          <div class="modal-footer">
            <a id="tsRptPdfDownload" href="#" class="btn btn-primary" target="_blank">
              <i class="ti ti-download me-1"></i> Download
            </a>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

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
    <script src="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
    <!-- Flat Picker -->
    <script src="<?=base_url();?>assets/vendor/libs/moment/moment.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->
    <script>
    // ── Timesheet filters ────────────────────────────────────────────

    // Period range custom filter (registered once, before DataTable init)
    var monthNames = ['january','february','march','april','may','june','july',
                      'august','september','october','november','december'];

    function periodToNum(text) {
      var parts = text.trim().toLowerCase().split(' ');
      var m = monthNames.indexOf(parts[0]) + 1;
      var y = parseInt(parts[1]);
      return (m < 1 || isNaN(y)) ? 0 : y * 100 + m;
    }

    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      if (settings.nTable.id !== 'timesheetsTable') return true;
      var COL_PERIOD = 3;
      var periodNum = periodToNum(data[COL_PERIOD]);
      // flatpickr stores YYYY-MM-DD in the input; take first 7 chars → "YYYY-MM"
      var fromRaw = $('#tsPeriodFrom').val();
      var toRaw   = $('#tsPeriodTo').val();
      if (!fromRaw && !toRaw) return true;
      var fromNum = fromRaw ? parseInt(fromRaw.substring(0, 7).replace('-', '')) : 0;
      var toNum   = toRaw   ? parseInt(toRaw.substring(0, 7).replace('-', ''))   : 999999;
      return periodNum >= fromNum && periodNum <= toNum;
    });

    $(document).ready(function() {
      var table = $.fn.dataTable.isDataTable('#timesheetsTable')
                    ? $('#timesheetsTable').DataTable()
                    : $('#timesheetsTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        language: {
                          emptyTable: 'No timesheets found.',
                          zeroRecords: 'No timesheets match your filters.'
                        }
                      });

      // Column indices
      var COL_PARTNER = 2;
      var COL_STATUS  = 7;

      // Status quick-filter buttons (fixed — All / Submitted / Approved / Rejected)
      $('#tsStatusBtns .btn').on('click', function() {
        $('#tsStatusBtns .btn').removeClass('active');
        $(this).addClass('active');
        var val = $(this).data('val');
        table.column(COL_STATUS).search(val ? '^' + val + '$' : '', true, false).draw();
      });

      // Partner select
      $('#tsPartnerFilter').on('change', function() {
        table.column(COL_PARTNER).search($(this).val(), false, false).draw();
      });

      // Period From / To — flatpickr calendars
      var fpFrom = flatpickr('#tsPeriodFrom', {
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   'F Y',
        onChange: function() { table.draw(); }
      });
      var fpTo = flatpickr('#tsPeriodTo', {
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   'F Y',
        onChange: function() { table.draw(); }
      });

      // Reset
      $('#tsResetFilters').on('click', function() {
        $('#tsStatusBtns .btn').removeClass('active').first().addClass('active');
        $('#tsPartnerFilter').val('');
        fpFrom.clear();
        fpTo.clear();
        table.columns().search('', true, false).draw();
      });
    });
    // ────────────────────────────────────────────────────────────────

    function approveTimesheet(timesheetId) {
        var comments = prompt('Add approval comments (optional):');
        if(comments !== null) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?=base_url();?>approveTimesheet/' + timesheetId;

            var commentsInput = document.createElement('input');
            commentsInput.type = 'hidden';
            commentsInput.name = 'comments';
            commentsInput.value = comments;

            form.appendChild(commentsInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function rejectTimesheet(timesheetId) {
        var comments = prompt('Please provide a reason for rejection (required):');
        if(comments && comments.trim() !== '') {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?=base_url();?>rejectTimesheet/' + timesheetId;

            var commentsInput = document.createElement('input');
            commentsInput.type = 'hidden';
            commentsInput.name = 'comments';
            commentsInput.value = comments;

            form.appendChild(commentsInput);
            document.body.appendChild(form);
            form.submit();
        } else if(comments !== null) {
            alert('Comments are required when rejecting a timesheet.');
        }
    }
    </script>

    <script>
    // ── Generate Timesheet Report modal ─────────────────────────────
    (function($){
      $(document).on('submit', '#generateTsReportForm', function(e){
        e.preventDefault();

        // Build YYYY-MM values from selects
        var fromM = $('#tsRptFromMonth').val();
        var fromY = $('#tsRptFromYear').val();
        var toM   = $('#tsRptToMonth').val();
        var toY   = $('#tsRptToYear').val();

        if(!fromM || !fromY || !toM || !toY){
          alert('Please select both a month and year for From and To.');
          return;
        }

        $('#tsRptFromHidden').val(fromY + '-' + fromM);
        $('#tsRptToHidden').val(toY   + '-' + toM);

        var statusDiv  = $('#tsRptStatus');
        var statusText = $('#tsRptStatusText');
        var btn        = $('#generateTsRptBtn');

        statusDiv.removeClass('d-none alert-danger alert-success').addClass('alert-info');
        statusText.html('<i class="ti ti-loader ti-spin me-1"></i> Generating report…');
        btn.prop('disabled', true);

        $.ajax({
          url: '<?=base_url('generateTimesheetReport');?>',
          method: 'POST',
          data: $(this).serialize(),
          dataType: 'json',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function(res){
          if(res.success){
            statusDiv.removeClass('alert-info').addClass('alert-success');
            statusText.html('<i class="ti ti-check me-1"></i> ' + res.message);
            setTimeout(function(){
              bootstrap.Modal.getInstance(document.getElementById('generateTsReportModal')).hide();
              $('#tsRptPdfFrame').attr('src', res.file_url);
              $('#tsRptPdfTitle').text(res.file_name);
              $('#tsRptPdfDownload').attr('href', res.file_url).attr('download', res.file_name);
              new bootstrap.Modal(document.getElementById('tsRptPdfModal')).show();
              statusDiv.addClass('d-none');
              btn.prop('disabled', false);
              document.getElementById('generateTsReportForm').reset();
            }, 800);
          } else {
            statusDiv.removeClass('alert-info').addClass('alert-danger');
            statusText.html('<i class="ti ti-alert-circle me-1"></i> ' + res.message);
            btn.prop('disabled', false);
          }
        }).fail(function(){
          statusDiv.removeClass('alert-info').addClass('alert-danger');
          statusText.html('<i class="ti ti-alert-circle me-1"></i> Failed to generate report. Please try again.');
          btn.prop('disabled', false);
        });
      });
    })(jQuery);
    // ────────────────────────────────────────────────────────────────
    </script>
  </body>
</html>

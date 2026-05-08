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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Financial /</span> Expenses & Reports</h4>

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
			  <div class="card-header d-flex justify-content-between align-items-center">
				<h5 class="mb-0">Expense Records</h5>
				<div>
				  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_coordinator()): ?>
				  <a href="newExpense" class="btn btn-primary me-2">
					<span class="tf-icons ti ti-plus me-1"></span> Add Expense
				  </a>
				  <?php endif; ?>
				  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
				  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateReportModal">
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
                      <div class="btn-group btn-group-sm" id="expStatusBtns">
                        <button class="btn btn-outline-secondary active" data-val="">All</button>
                        <button class="btn btn-outline-warning"   data-val="pending">Pending</button>
                        <button class="btn btn-outline-success"   data-val="approved">Approved</button>
                        <button class="btn btn-outline-danger"    data-val="rejected">Rejected</button>
                      </div>
                    </div>
                    <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                    <!-- Partner filter — all partners from DB -->
                    <div class="col-12 col-sm-6 col-xl-3">
                      <label class="form-label small text-muted mb-1">Partner / Institution</label>
                      <select class="form-select form-select-sm" id="expPartnerFilter">
                        <option value="">All Partners</option>
                        <?php if(!empty($all_partners)): foreach($all_partners as $p): ?>
                        <option value="<?=htmlspecialchars($p['name']);?>"><?=htmlspecialchars($p['name']);?></option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                    <?php endif; ?>
                    <!-- Work Package filter -->
                    <div class="col-6 col-sm-3 col-xl-2">
                      <label class="form-label small text-muted mb-1">Work Package</label>
                      <select class="form-select form-select-sm" id="expWpFilter">
                        <option value="">All WPs</option>
                        <option value="WP1">WP1</option>
                        <option value="WP2">WP2</option>
                        <option value="WP3">WP3</option>
                        <option value="WP4">WP4</option>
                        <option value="WP5">WP5</option>
                        <option value="WP6">WP6</option>
                        <option value="WP7">WP7</option>
                      </select>
                    </div>
                    <!-- Period range -->
                    <div class="col-6 col-sm-3 col-xl-2">
                      <label class="form-label small text-muted mb-1">Date From</label>
                      <input type="text" class="form-control form-control-sm" id="expDateFrom" placeholder="Select date…" readonly />
                    </div>
                    <div class="col-6 col-sm-3 col-xl-2">
                      <label class="form-label small text-muted mb-1">Date To</label>
                      <input type="text" class="form-control form-control-sm" id="expDateTo" placeholder="Select date…" readonly />
                    </div>
                    <!-- Reset -->
                    <div class="col-auto ms-auto">
                      <button class="btn btn-sm btn-outline-secondary" id="expResetFilters">
                        <i class="ti ti-refresh me-1"></i> Reset
                      </button>
                    </div>
                  </div>
                </div>

                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table" id="expensesTable">
                    <thead>
                      <tr>
                        <th></th>
                        <th>File Name</th>
						<th>Partner</th>
						<th>Date</th>
						<th>Amount</th>
						<th>EUR Equiv.</th>
						<th>Currency</th>
						<th>Category</th>
						<th>Work Package</th>
						<th>Upload Date</th>
						<th>Description</th>
                        <th>Status</th>
						<th>Actions</th>
                      </tr>
                    </thead>
					<tbody>
						<?php foreach ($expenses as $expense) {
									// Determine file extension and path
									$file_path = base_url().'assets/uploads/'.$expense['FileName'];
									$file_extension = pathinfo($expense['FileName'], PATHINFO_EXTENSION);

									// Map work package to full name
									$wp_names = [
										'WP1' => 'WP1 - Management and Coordination',
										'WP2' => 'WP2 - Collaboration Design',
										'WP3' => 'WP3 - Infrastructures',
										'WP4' => 'WP4 - Curricula Design',
										'WP5' => 'WP5 - Training and Coaching',
										'WP6' => 'WP6 - Transfer Methodologies',
										'WP7' => 'WP7 - Impact and Dissemination'
									];
									$wp_display = isset($wp_names[$expense['WorkPackage']]) ? $wp_names[$expense['WorkPackage']] : $expense['WorkPackage'];

									// Status badge color
									$status_badge = '';
									switch($expense['status']){
										case 'approved':
											$status_badge = 'bg-success';
											break;
										case 'rejected':
											$status_badge = 'bg-danger';
											break;
										case 'pending':
										default:
											$status_badge = 'bg-warning';
											break;
									}
						?>
						<tr>
							<td></td>
							<td>
								<?php if(strtolower($file_extension) == 'pdf'): ?>
								<a href="javascript:void(0)" onclick="viewPDF('<?=$file_path;?>', '<?=$expense['FileName'];?>')">
									<i class="ti ti-file-text me-1"></i><?=$expense['FileName'];?>
								</a>
								<?php else: ?>
								<a href="<?=$file_path;?>" target="_blank">
									<i class="ti ti-file me-1"></i><?=$expense['FileName'];?>
								</a>
								<?php endif; ?>
							</td>
							<td><?=isset($expense['partner_name']) ? $expense['partner_name'] : $expense['Partner'];?></td>
							<td><?=date('M d, Y', strtotime($expense['Date']));?></td>
							<td><?=number_format($expense['Amount'], 2);?></td>
							<td>
								<?php
								$cur = strtoupper($expense['Currency']);
								if($cur === 'RWF' && !empty($expense['forex_rate']) && $expense['forex_rate'] > 0):
									$eur_equiv = $expense['Amount'] / $expense['forex_rate'];
								?>
								<span class="text-success fw-semibold"><?=number_format($eur_equiv, 2);?> €</span>
								<br><small class="text-muted"><?=number_format($expense['forex_rate'], 2);?> RWF = 1 €</small>
								<?php elseif($cur === 'EUR'): ?>
								<span class="text-success fw-semibold"><?=number_format($expense['Amount'], 2);?> €</span>
								<?php else: ?>
								<span class="text-muted small">—</span>
								<?php endif; ?>
							</td>
							<td><?=strtoupper($expense['Currency']);?></td>
							<td><?=ucfirst($expense['Category']);?></td>
							<td><?=$wp_display;?></td>
							<td><?=date('M d, Y', strtotime($expense['created_at']));?></td>
							<td><small><?=$expense['ShortDescription'];?></small></td>
							<td><span class="badge <?=$status_badge;?>"><?=ucfirst($expense['status']);?></span></td>
							<td>
								<div class="btn-group" role="group">
									<?php if(strtolower($file_extension) == 'pdf'): ?>
									<button type="button" class="btn btn-sm btn-info" onclick="viewPDF('<?=$file_path;?>', '<?=htmlspecialchars($expense['FileName'], ENT_QUOTES);?>')">
										<i class="ti ti-eye"></i> View
									</button>
									<?php else: ?>
									<a href="<?=$file_path;?>" target="_blank" class="btn btn-sm btn-info">
										<i class="ti ti-eye"></i> View
									</a>
									<?php endif; ?>
									<?php if(($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()) && $expense['status'] == 'pending'): ?>
									<button type="button" class="btn btn-sm btn-success" onclick="approveExpense(<?=$expense['expense_id'];?>)">
										<i class="ti ti-check"></i> Approve
									</button>
									<button type="button" class="btn btn-sm btn-danger" onclick="rejectExpense(<?=$expense['expense_id'];?>)">
										<i class="ti ti-x"></i> Reject
									</button>
									<?php endif; ?>
								</div>
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

    <!-- Generate Report Modal -->
    <div class="modal fade" id="generateReportModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Generate Expense Report</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="generateReportForm" action="<?=base_url('generateReport');?>" method="post">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Select Date Range</label>
                <div class="row">
                  <div class="col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="from_date" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="to_date" required>
                  </div>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Partner (Optional)</label>
                <select class="form-control" name="partner_id">
                  <option value="">All Partners</option>
                  <?php
                  $partners = $this->fmsm_enhanced->get_all_partners();
                  foreach($partners as $partner):
                  ?>
                  <option value="<?=$partner['partner_id'];?>"><?=$partner['name'];?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Status Filter</label>
                <select class="form-control" name="status">
                  <option value="">All Status</option>
                  <option value="approved">Approved Only</option>
                  <option value="pending">Pending Only</option>
                  <option value="rejected">Rejected Only</option>
                </select>
              </div>
              <div id="reportGenerationStatus" class="alert alert-info d-none">
                <i class="ti ti-loader me-1"></i> <span id="reportStatusText">Generating report...</span>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success" id="generateReportBtn">
                <i class="ti ti-file-download me-1"></i> Generate Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="pdfViewerTitle">View Document</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <iframe id="pdfViewerFrame" style="width: 100%; height: 75vh; border: none;"></iframe>
          </div>
          <div class="modal-footer">
            <a id="pdfDownloadLink" href="#" download class="btn btn-primary" target="_blank">
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
    // ── Expense filters ──────────────────────────────────────────────

    // Date range custom filter (registered before DataTable init)
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
      if (settings.nTable.id !== 'expensesTable') return true;
      var COL_DATE = 3; // "Apr 14, 2025"
      var fromVal = $('#expDateFrom').val(); // YYYY-MM-DD from flatpickr
      var toVal   = $('#expDateTo').val();
      if (!fromVal && !toVal) return true;

      // Parse the displayed date cell (e.g. "Apr 14, 2025") to a Date object
      var cellDate = new Date(data[COL_DATE]);
      if (isNaN(cellDate)) return true;

      var fromDate = fromVal ? new Date(fromVal) : null;
      var toDate   = toVal   ? new Date(toVal)   : null;
      // Set toDate to end of day so the selected day is inclusive
      if (toDate) toDate.setHours(23, 59, 59, 999);

      if (fromDate && cellDate < fromDate) return false;
      if (toDate   && cellDate > toDate)   return false;
      return true;
    });

    $(document).ready(function() {
      var table = $.fn.dataTable.isDataTable('#expensesTable')
                    ? $('#expensesTable').DataTable()
                    : $('#expensesTable').DataTable({
                        responsive: true,
                        pageLength: 25,
                        language: {
                          emptyTable: 'No expenses found.',
                          zeroRecords: 'No expenses match your filters.'
                        }
                      });

      // Column indices
      var COL_PARTNER = 2;
      var COL_WP      = 8;
      var COL_STATUS  = 11;

      // Status quick-filter buttons (fixed — All / Pending / Approved / Rejected)
      $('#expStatusBtns .btn').on('click', function() {
        $('#expStatusBtns .btn').removeClass('active');
        $(this).addClass('active');
        var val = $(this).data('val');
        table.column(COL_STATUS).search(val ? '^' + val + '$' : '', true, false).draw();
      });

      // Partner select
      $('#expPartnerFilter').on('change', function() {
        table.column(COL_PARTNER).search($(this).val(), false, false).draw();
      });

      // Work Package select
      $('#expWpFilter').on('change', function() {
        table.column(COL_WP).search($(this).val(), false, false).draw();
      });

      // Date From / To — flatpickr calendars
      var fpExpFrom = flatpickr('#expDateFrom', {
        dateFormat: 'Y-m-d',
        altInput:   true,
        altFormat:  'M d, Y',
        onChange: function() { table.draw(); }
      });
      var fpExpTo = flatpickr('#expDateTo', {
        dateFormat: 'Y-m-d',
        altInput:   true,
        altFormat:  'M d, Y',
        onChange: function() { table.draw(); }
      });

      // Reset
      $('#expResetFilters').on('click', function() {
        $('#expStatusBtns .btn').removeClass('active').first().addClass('active');
        $('#expPartnerFilter, #expWpFilter').val('');
        fpExpFrom.clear();
        fpExpTo.clear();
        table.columns().search('', true, false).draw();
      });
    });
    // ────────────────────────────────────────────────────────────────

    function approveExpense(expenseId) {
        if(confirm('Are you sure you want to approve this expense?')) {
            const comments = prompt('Add approval comments (optional):');
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?=base_url();?>approveExpense/' + expenseId;

            if(comments) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'comments';
                input.value = comments;
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        }
    }

    function rejectExpense(expenseId) {
        const comments = prompt('Please provide a reason for rejection:');
        if(comments) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?=base_url();?>rejectExpense/' + expenseId;

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'comments';
            input.value = comments;
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        } else {
            alert('Comments are required for rejection.');
        }
    }

    function viewPDF(fileUrl, fileName) {
        // Set the PDF source in the iframe
        document.getElementById('pdfViewerFrame').src = fileUrl;
        // Set the modal title
        document.getElementById('pdfViewerTitle').textContent = fileName;
        // Set the download link
        document.getElementById('pdfDownloadLink').href = fileUrl;
        document.getElementById('pdfDownloadLink').download = fileName;
        // Show the modal
        var pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
        pdfModal.show();
    }

    // Handle Generate Report Form Submission with AJAX
    document.getElementById('generateReportForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading status
        const statusDiv = document.getElementById('reportGenerationStatus');
        const statusText = document.getElementById('reportStatusText');
        const generateBtn = document.getElementById('generateReportBtn');

        statusDiv.classList.remove('d-none', 'alert-danger', 'alert-success');
        statusDiv.classList.add('alert-info');
        statusText.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Generating report...';
        generateBtn.disabled = true;

        // Get form data
        const formData = new FormData(this);

        // Send AJAX request
        fetch('<?=base_url('generateReport');?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Show success message
                statusDiv.classList.remove('alert-info');
                statusDiv.classList.add('alert-success');
                statusText.innerHTML = '<i class="ti ti-check me-1"></i> ' + data.message;

                // Close the generate report modal
                const generateModal = bootstrap.Modal.getInstance(document.getElementById('generateReportModal'));
                setTimeout(function() {
                    generateModal.hide();

                    // Open the PDF viewer modal with the generated report
                    viewPDF(data.file_url, data.file_name);

                    // Reset form and status
                    document.getElementById('generateReportForm').reset();
                    statusDiv.classList.add('d-none');
                    generateBtn.disabled = false;
                }, 1000);
            } else {
                // Show error message
                statusDiv.classList.remove('alert-info');
                statusDiv.classList.add('alert-danger');
                statusText.innerHTML = '<i class="ti ti-alert-circle me-1"></i> ' + data.message;
                generateBtn.disabled = false;
            }
        })
        .catch(error => {
            // Show error message
            statusDiv.classList.remove('alert-info');
            statusDiv.classList.add('alert-danger');
            statusText.innerHTML = '<i class="ti ti-alert-circle me-1"></i> Failed to generate report. Please try again.';
            generateBtn.disabled = false;
            console.error('Error:', error);
        });
    });
    </script>

  </body>
</html>
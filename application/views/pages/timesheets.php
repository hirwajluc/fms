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
			  <div class="col-md-4 m-4">
				<a href="<?=base_url();?>newTimesheet" class="btn rounded-pill me-2 btn-info"><span class="tf-icons ti-xs ti ti-upload me-1"></span> Submit New Timesheet</a>
			  </div>
                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table">
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
						<?php
							if(!empty($timesheets)){
								foreach ($timesheets as $timesheet) {
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
								<?php if(!empty($timesheet['comments'])): ?>
								<br><small class="text-muted"><i class="ti ti-message-circle"></i> <?=$timesheet['comments'];?></small>
								<?php endif; ?>
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
						<?php
								}
							} else {
								echo '<tr><td colspan="9" class="text-center">No timesheets found</td></tr>';
							}
						?>
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

  </body>
</html>

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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Expenses /</span> Reports</h4>

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
			  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
			  <div class="col-md-4 m-4">
				<a href="newExpense" class="btn rounded-pill me-2 btn-info"><span class="tf-icons ti-xs ti ti-upload me-1"></span> Upload New Report</a>
			  </div>
			  <?php endif; ?>
                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table">
                    <thead>
                      <tr>
                        <th></th>
                        <th>File Name</th>
						<th>Partner</th>
						<th>Date</th>
						<th>Amount</th>
						<th>Currency</th>
						<th>Category</th>
						<th>Work Package</th>
						<th>Uploaded By</th>
						<th>Upload Date</th>
						<th>Description</th>
                        <th>Status</th>
						<?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
						<th>Actions</th>
						<?php endif; ?>
                      </tr>
                    </thead>
					<tbody>
						<?php
							if(!empty($expenses)){
								foreach ($expenses as $expense) {
									// Determine file extension
									$file_path = base_url().'assets/uploads/'.$expense['FileName'];

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
								<a href="<?=$file_path;?>" target="_blank">
									<i class="ti ti-file-text me-1"></i><?=$expense['FileName'];?>
								</a>
							</td>
							<td><?=isset($expense['partner_name']) ? $expense['partner_name'] : $expense['Partner'];?></td>
							<td><?=date('M d, Y', strtotime($expense['Date']));?></td>
							<td><?=number_format($expense['Amount'], 2);?></td>
							<td><?=strtoupper($expense['Currency']);?></td>
							<td><?=ucfirst($expense['Category']);?></td>
							<td><?=strtoupper($expense['WorkPackage']);?></td>
							<td><?=isset($expense['uploader_name']) ? $expense['uploader_name'] : 'N/A';?></td>
							<td><?=date('M d, Y', strtotime($expense['created_at']));?></td>
							<td><small><?=$expense['ShortDescription'];?></small></td>
							<td><span class="badge <?=$status_badge;?>"><?=ucfirst($expense['status']);?></span></td>
							<?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
							<td>
								<?php if($expense['status'] == 'pending'): ?>
								<div class="btn-group" role="group">
									<button type="button" class="btn btn-sm btn-success" onclick="approveExpense(<?=$expense['expense_id'];?>)">
										<i class="ti ti-check"></i> Approve
									</button>
									<button type="button" class="btn btn-sm btn-danger" onclick="rejectExpense(<?=$expense['expense_id'];?>)">
										<i class="ti ti-x"></i> Reject
									</button>
								</div>
								<?php else: ?>
								<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
							<?php endif; ?>
						</tr>
						<?php
								}
							} else {
								echo '<tr><td colspan="13" class="text-center">No expenses found</td></tr>';
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
    </script>

  </body>
</html>
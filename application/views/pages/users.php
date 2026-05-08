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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Administration /</span> Users & Staff</h4>

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

              <!-- Nav tabs -->
              <ul class="nav nav-tabs mb-3" role="tablist">
                <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                <li class="nav-item">
                  <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#users-tab" aria-controls="users-tab" aria-selected="true">
                    <i class="tf-icons ti ti-users ti-xs me-1"></i> User Accounts
                  </button>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                  <button type="button" class="nav-link <?=(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()) ? 'active' : '';?>" role="tab" data-bs-toggle="tab" data-bs-target="#staff-tab" aria-controls="staff-tab" aria-selected="<?=(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()) ? 'true' : 'false';?>">
                    <i class="tf-icons ti ti-user-check ti-xs me-1"></i> Staff Members
                  </button>
                </li>
              </ul>

              <!-- Tab content -->
              <div class="tab-content">
                <!-- User Accounts Tab -->
                <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                <div class="tab-pane fade show active" id="users-tab" role="tabpanel">
                  <div class="card">
                    <div class="col-md-4 m-4">
                      <a href="newUser" class="btn rounded-pill me-2 btn-info"><span class="tf-icons ti-xs ti ti-user-plus me-1"></span> New User Account</a>
                    </div>
                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table">
                    <thead>
                      <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Email</th>
						<th>Partner/Institution</th>
						<th>Position</th>
						<th>Role</th>
						<th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
					<tbody>
						<?php
							if(!empty($users)){
								foreach ($users as $user) {
									// Status badge
									$status_badge = $user['status'] == 'active' ? 'bg-success' : 'bg-danger';

									// Role badge
									$role_badge = '';
									switch($user['role_id']){
										case 1: $role_badge = 'bg-danger'; break; // Super Admin
										case 2: $role_badge = 'bg-warning'; break; // Admin
										case 3: $role_badge = 'bg-info'; break; // Coordinator
										case 4: $role_badge = 'bg-primary'; break; // Member
									}
						?>
						<tr>
							<td></td>
							<td>
								<strong><?=$user['first_name']." ".$user['last_name'];?></strong>
							</td>
							<td><?=$user['email'];?></td>
							<td><?=isset($user['partner_name']) ? $user['partner_name'] : 'N/A';?></td>
							<td><?=$user['position'];?></td>
							<td><span class="badge <?=$role_badge;?>"><?=ucwords(str_replace('_', ' ', $user['role_name']));?></span></td>
							<td><span class="badge <?=$status_badge;?>"><?=ucfirst($user['status']);?></span></td>
							<td>
								<div class="btn-group" role="group">
									<a href="<?=base_url();?>editUser/<?=$user['user_id'];?>" class="btn btn-sm btn-primary">
										<i class="ti ti-edit"></i> Edit
									</a>
									<?php if($this->auth_manager->is_super_admin() && $user['user_id'] != $this->session->userdata('fms_user_id')): ?>
									<button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?=$user['user_id'];?>, '<?=$user['first_name']." ".$user['last_name'];?>')">
										<i class="ti ti-trash"></i> Delete
									</button>
									<?php endif; ?>
								</div>
							</td>
						</tr>
						<?php
								}
							} else {
								echo '<tr><td colspan="8" class="text-center">No users found</td></tr>';
							}
						?>
					</tbody>
                  </table>
                </div>
              </div>
            </div>
            <?php endif; ?>
            <!-- End User Accounts Tab -->

            <!-- Staff Members Tab -->
            <div class="tab-pane fade <?=(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()) ? 'show active' : '';?>" id="staff-tab" role="tabpanel">
              <div class="card">
                <div class="col-md-4 m-4">
                  <a href="newStaff" class="btn rounded-pill me-2 btn-success"><span class="tf-icons ti-xs ti ti-user-plus me-1"></span> New Staff Member</a>
                </div>
                <div class="card-datatable table-responsive">
                  <table class="dt-responsive table" id="staff-table">
                    <thead>
                      <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Partner/Institution</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>GREATER Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if(!empty($staff)){
                          foreach ($staff as $member) {
                            // Status badge
                            $status_badge = $member['status'] == 'active' ? 'bg-success' : 'bg-danger';
                      ?>
                      <tr>
                        <td></td>
                        <td>
                          <strong><?=$member['first_name']." ".$member['last_name'];?></strong>
                        </td>
                        <td><?=$member['email'];?></td>
                        <td><?=isset($member['partner_name']) ? $member['partner_name'] : 'N/A';?></td>
                        <td><?=$member['position'] ? $member['position'] : 'N/A';?></td>
                        <td><?=$member['department'] ? $member['department'] : 'N/A';?></td>
                        <td><?=$member['greater_role'] ? $member['greater_role'] : 'N/A';?></td>
                        <td><span class="badge <?=$status_badge;?>"><?=ucfirst($member['status']);?></span></td>
                        <td>
                          <div class="btn-group" role="group">
                            <a href="<?=base_url();?>editStaff/<?=$member['staff_id'];?>" class="btn btn-sm btn-primary">
                              <i class="ti ti-edit"></i> Edit
                            </a>
                            <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteStaff(<?=$member['staff_id'];?>, '<?=$member['first_name']." ".$member['last_name'];?>')">
                              <i class="ti ti-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                      <?php
                          }
                        } else {
                          echo '<tr><td colspan="9" class="text-center">No staff members found</td></tr>';
                        }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!-- End Staff Members Tab -->

          </div>
          <!-- End Tab Content -->
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
    function deleteUser(userId, userName) {
        if(confirm('Are you sure you want to delete user "' + userName + '"?\n\nThis action cannot be undone.')) {
            window.location.href = '<?=base_url();?>deleteUser/' + userId;
        }
    }

    function deleteStaff(staffId, staffName) {
        if(confirm('Are you sure you want to delete staff member "' + staffName + '"?\n\nThis action cannot be undone.')) {
            window.location.href = '<?=base_url();?>deleteStaff/' + staffId;
        }
    }
    </script>

  </body>
</html>
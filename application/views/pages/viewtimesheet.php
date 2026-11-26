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
            <?php
              // Month names
              $months = array(
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
              );
              $month_name = isset($months[$timesheet['month']]) ? $months[$timesheet['month']] : $timesheet['month'];

              // Status badge
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
            ?>
            <div class="container-xxl flex-grow-1 container-p-y">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">
                  <span class="text-muted fw-light">Timesheets /</span>
                  <?=$month_name.' '.$timesheet['year'];?> - <?=$timesheet['first_name'].' '.$timesheet['last_name'];?>
                </h4>
                <div class="btn-group" role="group">
                  <?php if($timesheet['status'] == 'approved'): ?>
                    <a href="<?=base_url();?>downloadTimesheetPDF/<?=$timesheet['timesheet_id'];?>" class="btn btn-info">
                      <i class="ti ti-download me-1"></i> Download PDF
                    </a>
                  <?php endif; ?>
                  <a href="<?=base_url();?>timesheets" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to List
                  </a>
                </div>
              </div>

              <!-- Timesheet Header Info -->
              <div class="row mb-4">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="mb-0">GREATER - Timesheet for Project Outputs</h5>
                      <span class="badge <?=$status_badge;?>"><?=ucfirst($timesheet['status']);?></span>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Project Name:</strong></p>
                          <p>GREATER – Growing Rwanda Energy Awareness Through highER education</p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Project ID:</strong></p>
                          <p>101083081 ERASMUS-EDU-2022-CBHE</p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Employee:</strong></p>
                          <p><?=$timesheet['first_name'].' '.$timesheet['last_name'];?></p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Organization:</strong></p>
                          <p><?=isset($timesheet['partner_name']) ? $timesheet['partner_name'] : 'N/A';?></p>
                        </div>
                      </div>
                      <div class="row mt-3">
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Staff Category:</strong></p>
                          <p><?=$timesheet['staff_category'];?></p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Period:</strong></p>
                          <p><?=$month_name.' '.$timesheet['year'];?></p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Total Hours:</strong></p>
                          <p class="text-primary fw-bold"><?=number_format($timesheet['total_hours'], 2);?> hours</p>
                        </div>
                        <div class="col-md-3">
                          <p class="mb-1"><strong>Submitted:</strong></p>
                          <p><?=!empty($timesheet['submitted_at']) ? date('M d, Y', strtotime($timesheet['submitted_at'])) : 'Not submitted';?></p>
                        </div>
                      </div>
                      <?php if(!empty($timesheet['comments'])): ?>
                      <div class="row mt-3">
                        <div class="col-12">
                          <div class="alert alert-info">
                            <strong><i class="ti ti-message-circle"></i> Comments:</strong>
                            <p class="mb-0 mt-2"><?=$timesheet['comments'];?></p>
                          </div>
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Work Package Summary -->
              <div class="row mb-4">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">Summary by Work Package</h5>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-bordered">
                          <thead class="table-light">
                            <tr>
                              <th>Work Package</th>
                              <th>Hours</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $wp_labels = array(
                                'WP1' => 'WP1 - Management and coordination',
                                'WP2' => 'WP2 - Collaboration design',
                                'WP3' => 'WP3 - Infrastructures',
                                'WP4' => 'WP4 - Curricula design',
                                'WP5' => 'WP5 - Training and coaching',
                                'WP6' => 'WP6 - Transfer methodologies',
                                'WP7' => 'WP7 - Impact and dissemination'
                              );

                              if(!empty($work_package_summary)){
                                foreach($work_package_summary as $wp){
                                  $wp_label = isset($wp_labels[$wp['work_package']]) ? $wp_labels[$wp['work_package']] : $wp['work_package'];
                                  echo '<tr>';
                                  echo '<td>'.$wp_label.'</td>';
                                  echo '<td>'.number_format($wp['total_hours'], 2).'</td>';
                                  echo '</tr>';
                                }
                              } else {
                                echo '<tr><td colspan="2" class="text-center">No work package data available</td></tr>';
                              }
                            ?>
                          </tbody>
                          <tfoot class="table-info">
                            <tr>
                              <th>Total</th>
                              <th><?=number_format($timesheet['total_hours'], 2);?> hours</th>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Daily Entries -->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0">Daily Time Entries</h5>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead class="table-light">
                            <tr>
                              <th style="width: 10%;">Date</th>
                              <th style="width: 10%;">Hours</th>
                              <th style="width: 25%;">Work Package</th>
                              <th style="width: 55%;">Comments</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if(!empty($timesheet_details)){
                                foreach($timesheet_details as $detail){
                                  $wp_label = isset($wp_labels[$detail['work_package']]) ? $wp_labels[$detail['work_package']] : $detail['work_package'];
                                  echo '<tr>';
                                  echo '<td>'.date('d/m/Y', strtotime($detail['date'])).'</td>';
                                  echo '<td>'.number_format($detail['hours'], 2).'</td>';
                                  echo '<td>'.$wp_label.'</td>';
                                  echo '<td>'.$detail['activity_description'].'</td>';
                                  echo '</tr>';
                                }
                              } else {
                                echo '<tr><td colspan="4" class="text-center">No daily entries found</td></tr>';
                              }
                            ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Staff signature (set by each user in Settings) -->
              <div class="row mt-4">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">
                      <h5 class="mb-0"><i class="ti ti-signature me-1"></i> Staff Signature</h5>
                    </div>
                    <div class="card-body">
                      <?php if($has_staff_signature): ?>
                        <img src="<?=base_url('timesheetSignature/' . (int)$timesheet['timesheet_id']);?>"
                             alt="Signature of <?=htmlspecialchars($timesheet['first_name'].' '.$timesheet['last_name'], ENT_QUOTES);?>"
                             style="max-height:100px;max-width:100%;" />
                        <p class="mb-0 mt-2 small text-muted">
                          Signed by <?=htmlspecialchars(trim((isset($timesheet['title']) ? $timesheet['title'].' ' : '').$timesheet['first_name'].' '.$timesheet['last_name']));?><?php
                          if(!empty($timesheet['signature_date'])) echo ' on ' . date('F j, Y', strtotime($timesheet['signature_date'])); ?>
                        </p>
                      <?php elseif($is_own_timesheet): ?>
                        <p class="mb-2 text-muted">This timesheet is not signed yet. Add your signature in Settings and it will appear here and on the PDF.</p>
                        <a href="<?=base_url('settings');?>#signature" class="btn btn-sm btn-primary">
                          <i class="ti ti-signature me-1"></i> Add my signature
                        </a>
                      <?php else: ?>
                        <p class="mb-0 text-muted">Not signed: this staff member has not added a signature yet.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
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

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>


  </body>
</html>

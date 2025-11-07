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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title><?=$title;?></title>
    <meta name="description" content="" />
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?=base_url();?>assets/img/favicon/favicon.ico" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
    <!-- Icons -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/tabler-icons.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/flag-icons.css" />
    <!-- Core CSS -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css" />
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/select2/select2.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/sweetalert2/sweetalert2.css" />
    <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>
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
                <h4 class="py-3 mb-4"><span class="text-muted fw-light">Financial Management /</span> Monthly Reports</h4>

                <!-- Flash Messages -->
                <?php if($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $this->session->flashdata('success'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $this->session->flashdata('error'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Filters and Actions Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5 class="mb-3">Generate New Monthly Report</h5>
                                <form id="generateReportForm" method="POST" action="<?php echo base_url('generateMonthlyReport'); ?>">
                                    <div class="row g-3">
                                        <?php if(!$this->auth_manager->is_coordinator()): ?>
                                        <div class="col-md-3">
                                            <label class="form-label">Partner Institution</label>
                                            <select class="form-select" name="partner_id" id="partner_id" required>
                                                <option value="">Select Partner</option>
                                                <?php
                                                $partners = $this->db->get('partners')->result_array();
                                                foreach($partners as $partner):
                                                ?>
                                                <option value="<?php echo $partner['partner_id']; ?>"
                                                    <?php echo ($partner_id == $partner['partner_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $partner['name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>

                                        <div class="col-md-3">
                                            <label class="form-label">Month</label>
                                            <select class="form-select" name="month" id="month" required>
                                                <option value="">Select Month</option>
                                                <?php
                                                $months = array(1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
                                                               7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December');
                                                foreach($months as $num => $name):
                                                ?>
                                                <option value="<?php echo $num; ?>" <?php echo ($selected_month == $num) ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Year</label>
                                            <select class="form-select" name="year" id="year" required>
                                                <option value="">Select Year</option>
                                                <?php
                                                $current_year = date('Y');
                                                for($y = $current_year - 2; $y <= $current_year + 1; $y++):
                                                ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bx bx-plus"></i> Generate Report
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <hr>

                        <!-- Filter Existing Reports -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5 class="mb-3">Filter Reports</h5>
                                <form id="filterForm" method="GET" action="<?php echo base_url('monthlyReports'); ?>">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="">All Statuses</option>
                                                <option value="draft" <?php echo ($selected_status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                                <option value="submitted" <?php echo ($selected_status == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                                                <option value="approved" <?php echo ($selected_status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo ($selected_status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Month</label>
                                            <select class="form-select" name="month">
                                                <option value="">All Months</option>
                                                <?php foreach($months as $num => $name): ?>
                                                <option value="<?php echo $num; ?>" <?php echo ($selected_month == $num) ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label">Year</label>
                                            <select class="form-select" name="year">
                                                <option value="">All Years</option>
                                                <?php for($y = $current_year - 2; $y <= $current_year + 1; $y++): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-3 d-flex align-items-end gap-2">
                                            <button type="submit" class="btn btn-outline-primary flex-grow-1">
                                                <i class="bx bx-filter"></i> Filter
                                            </button>
                                            <a href="<?php echo base_url('monthlyReports'); ?>" class="btn btn-outline-secondary">
                                                <i class="bx bx-reset"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="card">
                    <h5 class="card-header">Monthly Financial Reports</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Report</th>
                                    <th>Partner</th>
                                    <th>Period</th>
                                    <th>Expenses</th>
                                    <th>RWF</th>
                                    <th>EUR</th>
                                    <th>USD</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($reports)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        No monthly reports found. Generate a new report to get started.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($reports as $report): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $report['report_name']; ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $partner = $this->db->select('name')->where('partner_id', $report['partner_id'])->get('partners')->row_array();
                                            echo $partner ? $partner['name'] : 'N/A';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            $months_arr = array(1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun',
                                                               7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec');
                                            echo $months_arr[$report['report_month']] . ' ' . $report['report_year'];
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><?php echo $report['total_expenses_count']; ?></span>
                                        </td>
                                        <td><?php echo number_format($report['total_amount_rwf'], 0); ?></td>
                                        <td><?php echo number_format($report['total_amount_eur'], 2); ?></td>
                                        <td><?php echo number_format($report['total_amount_usd'], 2); ?></td>
                                        <td>
                                            <?php
                                            $status_colors = array('draft' => 'light', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger');
                                            $status_color = isset($status_colors[$report['status']]) ? $status_colors[$report['status']] : 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $creator = $this->db->select('s.first_name, s.last_name')
                                                ->from('users u')
                                                ->join('staff s', 's.staff_id = u.staff_id', 'left')
                                                ->where('u.user_id', $report['created_by'])
                                                ->get()
                                                ->row_array();
                                            echo $creator ? ($creator['first_name'] . ' ' . $creator['last_name']) : 'Unknown';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill"
                                                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="<?php echo base_url('viewMonthlyReport/' . $report['report_id']); ?>">
                                                        <i class="bx bx-show me-2"></i> View
                                                    </a>
                                                    <?php if($report['status'] == 'draft' || $report['status'] == 'rejected'): ?>
                                                    <a class="dropdown-item" href="<?php echo base_url('submitMonthlyReport/' . $report['report_id']); ?>"
                                                       onclick="return confirm('Submit this report for approval?');">
                                                        <i class="bx bx-send me-2"></i> Submit
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if($this->auth_manager->is_admin() && $report['status'] == 'submitted'): ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="approveReport(<?php echo $report['report_id']; ?>)">
                                                        <i class="bx bx-check me-2 text-success"></i> Approve
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectReport(<?php echo $report['report_id']; ?>)">
                                                        <i class="bx bx-x me-2 text-danger"></i> Reject
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- / Content -->

          </div>
          <!-- / Content wrapper -->
        </div>
        <!-- / Layout page -->
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="approveForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Approval Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rejection Comments <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="rejection_comments" rows="4" placeholder="Explain why this report is being rejected..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Core JS -->
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/hammer/hammer.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18next/i18next.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18next-http-backend/i18next-http-backend.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18next-browser-languagedetector/i18next-browser-languagedetector.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/swiper/swiper.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/select2/select2.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/sweetalert2/sweetalert2.js"></script>
    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>
    <script src="<?=base_url();?>assets/js/pages-auth.js"></script>

    <script>
    let currentReportId = null;

    function approveReport(reportId) {
        currentReportId = reportId;
        document.getElementById('approveForm').action = '<?php echo base_url('approveMonthlyReport/'); ?>' + reportId;
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }

    function rejectReport(reportId) {
        currentReportId = reportId;
        document.getElementById('rejectForm').action = '<?php echo base_url('rejectMonthlyReport/'); ?>' + reportId;
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    }
    </script>
  </body>
</html>

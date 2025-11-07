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
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h4 class="py-3 mb-0">
                            <span class="text-muted fw-light">Financial Management /</span>
                            <?php echo $report['report_name']; ?>
                        </h4>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?php echo base_url('monthlyReports'); ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left"></i> Back to Reports
                        </a>
                    </div>
                </div>

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

                <!-- Report Header Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Report Information</h5>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-semibold">Report ID:</td>
                                        <td><?php echo $report['report_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Partner:</td>
                                        <td><?php echo $partner ? $partner['name'] : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Period:</td>
                                        <td>
                                            <?php
                                            $months = array(1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
                                                           7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December');
                                            echo $months[$report['report_month']] . ' ' . $report['report_year'];
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Status:</td>
                                        <td>
                                            <?php
                                            $status_colors = array('draft' => 'light', 'submitted' => 'info', 'approved' => 'success', 'rejected' => 'danger');
                                            $status_color = isset($status_colors[$report['status']]) ? $status_colors[$report['status']] : 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $status_color; ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Audit Trail</h5>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-semibold">Created:</td>
                                        <td>
                                            <?php echo $created_by ? ($created_by['first_name'] . ' ' . $created_by['last_name']) : 'Unknown'; ?><br>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($report['created_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php if($report['submitted_at']): ?>
                                    <tr>
                                        <td class="fw-semibold">Submitted:</td>
                                        <td>
                                            <?php echo $submitted_by ? ($submitted_by['first_name'] . ' ' . $submitted_by['last_name']) : 'Unknown'; ?><br>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($report['submitted_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if($report['approved_at']): ?>
                                    <tr>
                                        <td class="fw-semibold">Approved:</td>
                                        <td>
                                            <?php echo $approved_by ? ($approved_by['first_name'] . ' ' . $approved_by['last_name']) : 'Unknown'; ?><br>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($report['approved_at'])); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Executive Summary -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total Expenses</h6>
                                <h3 class="mb-0"><?php echo $report['total_expenses_count']; ?></h3>
                                <small class="text-muted">Approved items in this report</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total RWF</h6>
                                <h3 class="mb-0">₨ <?php echo number_format($report['total_amount_rwf'], 0); ?></h3>
                                <small class="text-muted">Rwandan Francs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total EUR</h6>
                                <h3 class="mb-0">€ <?php echo number_format($report['total_amount_eur'], 2); ?></h3>
                                <small class="text-muted">Euros</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Total USD</h6>
                                <h3 class="mb-0">$ <?php echo number_format($report['total_amount_usd'], 2); ?></h3>
                                <small class="text-muted">US Dollars</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary by Work Package -->
                <div class="card mb-4">
                    <h5 class="card-header">Summary by Work Package</h5>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Work Package</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($report['wp_summary'])): ?>
                                    <?php foreach($report['wp_summary'] as $wp): ?>
                                    <tr>
                                        <td><?php echo $wp['work_package']; ?></td>
                                        <td class="text-end"><?php echo $wp['expense_count']; ?></td>
                                        <td class="text-end"><?php echo number_format($wp['total_amount'], 2); ?></td>
                                        <td class="text-end">
                                            <?php
                                            $total_all = $report['total_amount_rwf'] + $report['total_amount_eur'] + $report['total_amount_usd'];
                                            $percentage = $total_all > 0 ? ($wp['total_amount'] / $total_all) * 100 : 0;
                                            ?>
                                            <?php echo number_format($percentage, 1); ?>%
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No work package summary data</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary by Category -->
                <div class="card mb-4">
                    <h5 class="card-header">Summary by Category</h5>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Count</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($report['category_summary'])): ?>
                                    <?php foreach($report['category_summary'] as $cat): ?>
                                    <tr>
                                        <td><?php echo $cat['category']; ?></td>
                                        <td class="text-end"><?php echo $cat['expense_count']; ?></td>
                                        <td class="text-end"><?php echo number_format($cat['total_amount'], 2); ?></td>
                                        <td class="text-end">
                                            <?php
                                            $percentage = $total_all > 0 ? ($cat['total_amount'] / $total_all) * 100 : 0;
                                            ?>
                                            <?php echo number_format($percentage, 1); ?>%
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No category summary data</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary by Currency -->
                <div class="card mb-4">
                    <h5 class="card-header">Summary by Currency</h5>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Currency</th>
                                    <th class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($report['currency_summary'])): ?>
                                    <?php foreach($report['currency_summary'] as $curr): ?>
                                    <tr>
                                        <td><?php echo $curr['currency']; ?></td>
                                        <td class="text-end"><?php echo number_format($curr['total_amount'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No currency summary data</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detailed Expenses -->
                <div class="card mb-4">
                    <h5 class="card-header">Detailed Expenses</h5>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Currency</th>
                                    <th>Category</th>
                                    <th>Work Package</th>
                                    <th>Description</th>
                                    <th>Uploaded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($report['expenses'])): ?>
                                    <?php foreach($report['expenses'] as $expense): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                        <td class="text-end"><?php echo number_format($expense['amount'], 2); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo $expense['currency']; ?></span></td>
                                        <td><?php echo $expense['category']; ?></td>
                                        <td><span class="badge bg-info text-white"><?php echo $expense['work_package']; ?></span></td>
                                        <td>
                                            <small><?php echo substr($expense['description'], 0, 50); ?>...</small>
                                        </td>
                                        <td>
                                            <?php
                                            $uploader = $this->db->select('s.first_name, s.last_name')
                                                ->from('users u')
                                                ->join('staff s', 's.staff_id = u.staff_id', 'left')
                                                ->where('u.user_id', $expense['uploaded_by'])
                                                ->get()
                                                ->row_array();
                                            echo $uploader ? ($uploader['first_name'] . ' ' . $uploader['last_name']) : 'Unknown';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">No expenses in this report</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Approval Section (Admin Only) -->
                <?php if($this->auth_manager->is_admin() || $this->auth_manager->is_super_admin()): ?>
                <div class="card mb-4">
                    <h5 class="card-header bg-light">Approval Workflow</h5>
                    <div class="card-body">
                        <?php if($report['status'] == 'draft'): ?>
                        <div class="alert alert-info">
                            This report is in draft status. The user must submit it for approval.
                        </div>
                        <?php elseif($report['status'] == 'submitted'): ?>
                        <div class="alert alert-warning">
                            This report is awaiting approval. You can approve or reject it below.
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button class="btn btn-success w-100" onclick="showApproveForm()">
                                    <i class="ti ti-check"></i> Approve Report
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-danger w-100" onclick="showRejectForm()">
                                    <i class="ti ti-x"></i> Reject Report
                                </button>
                            </div>
                        </div>

                        <!-- Approve Form (Hidden) -->
                        <div id="approveForm" style="display:none;" class="mt-3 p-3 bg-light border rounded">
                            <h6>Approval Notes (Optional)</h6>
                            <form method="POST" action="<?php echo base_url('approveMonthlyReport/' . $report['report_id']); ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" name="notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-success">Confirm Approval</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="hideApproveForm()">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <!-- Reject Form (Hidden) -->
                        <div id="rejectForm" style="display:none;" class="mt-3 p-3 bg-light border rounded">
                            <h6>Rejection Comments <span class="text-danger">*</span></h6>
                            <form method="POST" action="<?php echo base_url('rejectMonthlyReport/' . $report['report_id']); ?>">
                                <div class="mb-3">
                                    <textarea class="form-control" name="rejection_comments" rows="4" placeholder="Explain why this report is being rejected..." required></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="hideRejectForm()">Cancel</button>
                                </div>
                            </form>
                        </div>
                        <?php elseif($report['status'] == 'approved'): ?>
                        <div class="alert alert-success">
                            This report has been approved on <?php echo date('M d, Y H:i', strtotime($report['approved_at'])); ?>.
                        </div>
                        <?php if($report['notes']): ?>
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Approval Notes:</h6>
                                <p><?php echo $report['notes']; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php elseif($report['status'] == 'rejected'): ?>
                        <div class="alert alert-danger">
                            This report was rejected and can be edited and resubmitted by the user.
                        </div>
                        <?php if($report['rejection_comments']): ?>
                        <div class="card bg-light border-danger">
                            <div class="card-body">
                                <h6 class="text-danger">Rejection Reason:</h6>
                                <p><?php echo $report['rejection_comments']; ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- User Actions -->
                <?php if($report['status'] == 'draft' || $report['status'] == 'rejected'): ?>
                <div class="card mb-4">
                    <h5 class="card-header bg-light">Report Actions</h5>
                    <div class="card-body">
                        <p>This report is ready to be submitted for approval.</p>
                        <a href="<?php echo base_url('submitMonthlyReport/' . $report['report_id']); ?>"
                           class="btn btn-primary" onclick="return confirm('Submit this report for approval?');">
                            <i class="ti ti-send"></i> Submit for Approval
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <!-- / Content -->

          </div>
        </div>
      </div>
    </div>

    <!-- Core JS -->
    <!-- build:js assets/vendor/js/core.js -->
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="<?=base_url();?>assets/vendor/libs/masonry/masonry.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/cleavejs/cleave.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/cleavejs/cleave-phone.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <!-- Page JS -->

    <script>
    function showApproveForm() {
        document.getElementById('approveForm').style.display = 'block';
        document.getElementById('rejectForm').style.display = 'none';
    }

    function hideApproveForm() {
        document.getElementById('approveForm').style.display = 'none';
    }

    function showRejectForm() {
        document.getElementById('rejectForm').style.display = 'block';
        document.getElementById('approveForm').style.display = 'none';
    }

    function hideRejectForm() {
        document.getElementById('rejectForm').style.display = 'none';
    }
    </script>

  </body>
</html>

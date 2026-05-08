<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr"
      data-theme="theme-default" data-assets-path="<?=base_url();?>assets/"
      data-template="horizontal-menu-template-no-customizer">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
  <title><?=$title;?></title>
  <link rel="icon" type="image/x-icon" href="<?=base_url();?>assets/img/favicon/favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/fontawesome.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/tabler-icons.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/fonts/flag-icons.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/core.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/css/rtl/theme-default.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/css/demo.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/apex-charts/apex-charts.css" />
  <script src="<?=base_url();?>assets/vendor/js/helpers.js"></script>
  <script src="<?=base_url();?>assets/js/config.js"></script>
</head>
<body>
<div class="layout-wrapper layout-navbar-full layout-horizontal layout-without-menu">
  <div class="layout-container">
    <?php include("navbar.php"); ?>
    <div class="layout-page">
      <div class="content-wrapper">
        <?php include("menu.php"); ?>

        <div class="container-xxl flex-grow-1 container-p-y">

          <?php
          // ── Helpers ──────────────────────────────────────────────────
          $fname      = $this->session->userdata('fms_fname');
          $partner    = $this->session->userdata('fms_partner') ?: 'GREATER';
          $role_label = isset($role_label) ? $role_label : ucwords(str_replace('_',' ',$role));

          // Role badge colour
          $role_color = 'secondary';
          $role_icon  = 'ti-user';
          if($this->auth_manager->is_super_admin()){ $role_color='danger';  $role_icon='ti-shield-check'; }
          elseif($this->auth_manager->is_admin())  { $role_color='warning'; $role_icon='ti-settings';     }
          elseif($this->auth_manager->is_coordinator()){ $role_color='info'; $role_icon='ti-user-check';  }
          ?>

          <!-- ═══════════════════════════════════════════════════════
               WELCOME BANNER
          ════════════════════════════════════════════════════════════ -->
          <div class="card mb-4 border-0" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
            <div class="card-body py-4">
              <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="text-white">
                  <h4 class="mb-1 fw-bold text-white">Welcome back, <?=htmlspecialchars($fname);?>! 👋</h4>
                  <p class="mb-2 opacity-75"><?=htmlspecialchars($partner);?> · ERASMUS+ GREATER Project</p>
                  <span class="badge bg-white text-dark px-3 py-1 rounded-pill">
                    <i class="ti <?=$role_icon;?> me-1"></i><?=htmlspecialchars($role_label);?>
                  </span>
                </div>
                <div class="text-center d-none d-md-block">
                  <img src="<?=base_url();?>assets/img/illustrations/card-advance-sale.png"
                       height="110" alt="dashboard" style="opacity:.9" />
                </div>
              </div>
            </div>
          </div>

          <!-- ═══════════════════════════════════════════════════════
               STAT CARDS
          ════════════════════════════════════════════════════════════ -->
          <div class="row g-4 mb-4">

          <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>

            <?php
            $stats = [
              ['label'=>'Total Users',    'value'=>$total_users    ?? 0, 'icon'=>'ti-users',        'color'=>'primary',   'sub'=>'Across all institutions'],
              ['label'=>'Partners',        'value'=>$total_partners ?? 0, 'icon'=>'ti-building',     'color'=>'success',   'sub'=>'Active institutions'],
              ['label'=>'Expenses',        'value'=>$total_expenses ?? 0, 'icon'=>'ti-receipt',      'color'=>'warning',   'sub'=>'All submissions'],
              ['label'=>'Timesheets',      'value'=>$total_timesheets??0, 'icon'=>'ti-clock',        'color'=>'info',      'sub'=>'All submissions'],
              ['label'=>'Other Files',     'value'=>$total_other_files??0,'icon'=>'ti-folder-open',  'color'=>'danger',    'sub'=>'Uploaded files'],
            ];
            ?>
            <?php foreach($stats as $s): ?>
            <div class="col-6 col-xl">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-label-<?=$s['color'];?>">
                        <i class="ti <?=$s['icon'];?> fs-5"></i>
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 small text-muted"><?=$s['label'];?></p>
                      <h4 class="mb-0 fw-bold"><?=$s['value'];?></h4>
                    </div>
                  </div>
                  <p class="mt-2 mb-0 small text-muted"><?=$s['sub'];?></p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>

          <?php elseif($this->auth_manager->is_coordinator()): ?>

            <?php
            $stats = [
              ['label'=>'Institution Members','value'=>$total_users      ??0,'icon'=>'ti-users',       'color'=>'primary', 'sub'=>'Your institution'],
              ['label'=>'Expenses',           'value'=>$total_expenses   ??0,'icon'=>'ti-receipt',     'color'=>'warning', 'sub'=>'Institution total'],
              ['label'=>'Timesheets',         'value'=>$total_timesheets ??0,'icon'=>'ti-clock',       'color'=>'info',    'sub'=>'Institution total'],
              ['label'=>'Pending Approval',   'value'=>$pending_timesheets??0,'icon'=>'ti-clock-pause','color'=>'danger',  'sub'=>'Awaiting review'],
              ['label'=>'Other Files',        'value'=>$total_other_files??0,'icon'=>'ti-folder-open', 'color'=>'success', 'sub'=>'Your uploads'],
            ];
            ?>
            <?php foreach($stats as $s): ?>
            <div class="col-6 col-xl">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-label-<?=$s['color'];?>">
                        <i class="ti <?=$s['icon'];?> fs-5"></i>
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 small text-muted"><?=$s['label'];?></p>
                      <h4 class="mb-0 fw-bold"><?=$s['value'];?></h4>
                    </div>
                  </div>
                  <p class="mt-2 mb-0 small text-muted"><?=$s['sub'];?></p>
                </div>
              </div>
            </div>
            <?php endforeach; ?>

          <?php else: ?>

            <?php
            $total_ts    = $total_timesheets    ?? 0;
            $approved_ts = $approved_timesheets ?? 0;
            $pending_ts  = $pending_timesheets  ?? 0;
            $pct = $total_ts > 0 ? round($approved_ts / $total_ts * 100) : 0;
            ?>
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-label-primary">
                        <i class="ti ti-clock fs-5"></i>
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 small text-muted">My Timesheets</p>
                      <h4 class="mb-0 fw-bold"><?=$total_ts;?></h4>
                    </div>
                  </div>
                  <p class="mt-2 mb-0 small text-muted">Total submitted</p>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-label-success">
                        <i class="ti ti-clock-check fs-5"></i>
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 small text-muted">Approved</p>
                      <h4 class="mb-0 fw-bold"><?=$approved_ts;?></h4>
                    </div>
                  </div>
                  <div class="mt-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                      <span>Approval rate</span><span><?=$pct;?>%</span>
                    </div>
                    <div class="progress" style="height:5px;">
                      <div class="progress-bar bg-success" style="width:<?=$pct;?>%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                      <span class="avatar-initial rounded-circle bg-label-warning">
                        <i class="ti ti-clock-pause fs-5"></i>
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 small text-muted">Pending</p>
                      <h4 class="mb-0 fw-bold"><?=$pending_ts;?></h4>
                    </div>
                  </div>
                  <p class="mt-2 mb-0 small text-muted">Awaiting review</p>
                </div>
              </div>
            </div>

          <?php endif; ?>
          </div>
          <!-- /Stat cards -->

          <!-- ═══════════════════════════════════════════════════════
               CHARTS  (Super Admin / Admin)
          ════════════════════════════════════════════════════════════ -->
          <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
          <?php
          // Prepare chart data as JSON for ApexCharts
          $exp_status   = $chart_expense_status   ?? ['pending'=>0,'approved'=>0,'rejected'=>0];
          $ts_status    = $chart_timesheet_status ?? ['draft'=>0,'submitted'=>0,'approved'=>0,'rejected'=>0];
          $ep_data      = $chart_expense_by_partner ?? [];
          $fw_data      = $chart_files_by_wp       ?? [];

          $ep_labels = json_encode(array_column($ep_data, 'short_name'));
          $ep_values = json_encode(array_map('intval', array_column($ep_data, 'cnt')));
          $fw_labels = json_encode(array_column($fw_data, 'code'));
          $fw_values = json_encode(array_map('intval', array_column($fw_data, 'cnt')));
          ?>

          <div class="row g-4 mb-4">
            <!-- Expense Status donut -->
            <div class="col-md-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-receipt me-2 text-warning"></i>Expense Status</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartExpenseStatus"></div>
                  <div class="d-flex justify-content-around mt-2 small">
                    <span><span class="badge bg-warning me-1">&nbsp;</span>Pending: <?=$exp_status['pending'];?></span>
                    <span><span class="badge bg-success me-1">&nbsp;</span>Approved: <?=$exp_status['approved'];?></span>
                    <span><span class="badge bg-danger me-1">&nbsp;</span>Rejected: <?=$exp_status['rejected'];?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Timesheet Status donut -->
            <div class="col-md-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-clock me-2 text-info"></i>Timesheet Status</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartTimesheetStatus"></div>
                  <div class="d-flex justify-content-around mt-2 small flex-wrap gap-1">
                    <span><span class="badge bg-secondary me-1">&nbsp;</span>Draft: <?=$ts_status['draft'];?></span>
                    <span><span class="badge bg-primary me-1">&nbsp;</span>Submitted: <?=$ts_status['submitted'];?></span>
                    <span><span class="badge bg-success me-1">&nbsp;</span>Approved: <?=$ts_status['approved'];?></span>
                    <span><span class="badge bg-danger me-1">&nbsp;</span>Rejected: <?=$ts_status['rejected'];?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Expenses by Partner bar -->
            <div class="col-md-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-building me-2 text-primary"></i>Expenses by Partner</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartExpenseByPartner"></div>
                </div>
              </div>
            </div>

            <!-- Files by WP bar -->
            <div class="col-md-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-folder-open me-2 text-danger"></i>Other Files by WP</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartFilesByWP"></div>
                </div>
              </div>
            </div>
          </div>

          <?php elseif($this->auth_manager->is_coordinator()): ?>
          <?php
          $ts_status  = $chart_timesheet_status ?? ['draft'=>0,'submitted'=>0,'approved'=>0,'rejected'=>0];
          $ew_data    = $chart_expense_by_wp    ?? [];
          $fw_data    = $chart_files_by_wp      ?? [];
          $ew_labels  = json_encode(array_column($ew_data, 'WorkPackage'));
          $ew_values  = json_encode(array_map('intval', array_column($ew_data, 'cnt')));
          $fw_labels  = json_encode(array_column($fw_data, 'code'));
          $fw_values  = json_encode(array_map('intval', array_column($fw_data, 'cnt')));
          ?>

          <div class="row g-4 mb-4">
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-clock me-2 text-info"></i>Timesheet Status</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartTimesheetStatus"></div>
                  <div class="d-flex justify-content-around mt-2 small flex-wrap gap-1">
                    <span><span class="badge bg-primary me-1">&nbsp;</span>Submitted: <?=$ts_status['submitted'];?></span>
                    <span><span class="badge bg-success me-1">&nbsp;</span>Approved: <?=$ts_status['approved'];?></span>
                    <span><span class="badge bg-danger me-1">&nbsp;</span>Rejected: <?=$ts_status['rejected'];?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-receipt me-2 text-warning"></i>Expenses by Work Package</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartExpenseByWP"></div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-folder-open me-2 text-success"></i>Files by Work Package</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartFilesByWP"></div>
                </div>
              </div>
            </div>
          </div>

          <?php else: ?>
          <?php
          // Member: simple timesheet donut
          $ts_status = $chart_timesheet_status ?? ['draft'=>0,'submitted'=>0,'approved'=>0,'rejected'=>0];
          ?>
          <div class="row g-4 mb-4">
            <div class="col-md-5">
              <div class="card border-0 shadow-sm">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-clock me-2 text-primary"></i>My Timesheet Status</h6>
                </div>
                <div class="card-body pt-2">
                  <div id="chartTimesheetStatus"></div>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 pb-0">
                  <h6 class="mb-0 fw-semibold"><i class="ti ti-info-circle me-2 text-info"></i>Quick Guide</h6>
                </div>
                <div class="card-body">
                  <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-2"></i>Submit your timesheet each month via <strong>Timesheets</strong></li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-2"></i>Your coordinator will review and approve it</li>
                    <li class="mb-2"><i class="ti ti-circle-check text-success me-2"></i>Check your approval status in the Timesheets list</li>
                    <li><i class="ti ti-circle-check text-success me-2"></i>Contact your coordinator for any queries</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div><!-- /container -->

        <!-- Footer -->
        <footer class="content-footer footer bg-footer-theme">
          <div class="container-xxl">
            <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
              <div>
                © <script>document.write(new Date().getFullYear());</script>,
                made with ❤️ by <a href="#" target="_blank" class="fw-semibold">ERASMUS+ GREATER</a>
              </div>
            </div>
          </div>
        </footer>

        <div class="content-backdrop fade"></div>
      </div>
    </div>
  </div>
</div>
<div class="layout-overlay layout-menu-toggle"></div>
<div class="drag-target"></div>

<!-- JS -->
<script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
<script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="<?=base_url();?>assets/js/main.js"></script>

<script>
// ── shared colours ──────────────────────────────────────────────
var cardColor   = '#fff';
var labelColor  = '#a1acb8';
var borderColor = '#eceef1';

// ── donut helper ────────────────────────────────────────────────
function donut(el, labels, series, colors){
  new ApexCharts(document.querySelector(el), {
    chart:   { type:'donut', height: 180, sparkline:{enabled:true} },
    labels:  labels,
    series:  series,
    colors:  colors,
    legend:  { show: false },
    dataLabels: { enabled: true, formatter: function(val){ return Math.round(val)+'%'; } },
    plotOptions: { pie:{ donut:{ size:'72%' } } },
    tooltip: { y:{ formatter: function(v){ return v + ' records'; } } }
  }).render();
}

// ── bar helper ──────────────────────────────────────────────────
function bar(el, categories, data, color, horizontal){
  new ApexCharts(document.querySelector(el), {
    chart:   { type:'bar', height: 180, toolbar:{show:false}, sparkline:{enabled:false} },
    plotOptions: { bar:{ borderRadius:4, horizontal: !!horizontal, columnWidth:'55%' } },
    dataLabels: { enabled: false },
    series: [{ name:'Count', data: data }],
    xaxis:  { categories: categories, labels:{ style:{ colors: labelColor, fontSize:'11px' } } },
    yaxis:  { labels:{ style:{ colors: labelColor } }, tickAmount: Math.max(...data) > 4 ? 4 : Math.max(...data) },
    colors: [color || '#696cff'],
    grid:   { borderColor: borderColor, strokeDashArray: 4 },
    tooltip: { y:{ formatter: function(v){ return v + ' items'; } } }
  }).render();
}

<?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
donut('#chartExpenseStatus',
  ['Pending','Approved','Rejected'],
  [<?=$exp_status['pending'];?>, <?=$exp_status['approved'];?>, <?=$exp_status['rejected'];?>],
  ['#fd7e14','#28a745','#dc3545']
);
donut('#chartTimesheetStatus',
  ['Draft','Submitted','Approved','Rejected'],
  [<?=$ts_status['draft'];?>, <?=$ts_status['submitted'];?>, <?=$ts_status['approved'];?>, <?=$ts_status['rejected'];?>],
  ['#a8b1c5','#696cff','#28a745','#dc3545']
);
bar('#chartExpenseByPartner', <?=$ep_labels;?>, <?=$ep_values;?>, '#696cff', false);
bar('#chartFilesByWP',        <?=$fw_labels;?>, <?=$fw_values;?>, '#e83e8c', false);

<?php elseif($this->auth_manager->is_coordinator()): ?>
donut('#chartTimesheetStatus',
  ['Draft','Submitted','Approved','Rejected'],
  [<?=$ts_status['draft'];?>, <?=$ts_status['submitted'];?>, <?=$ts_status['approved'];?>, <?=$ts_status['rejected'];?>],
  ['#a8b1c5','#696cff','#28a745','#dc3545']
);
bar('#chartExpenseByWP', <?=$ew_labels;?>, <?=$ew_values;?>, '#fd7e14', true);
bar('#chartFilesByWP',   <?=$fw_labels;?>, <?=$fw_values;?>, '#28a745', false);

<?php else: ?>
donut('#chartTimesheetStatus',
  ['Draft','Submitted','Approved','Rejected'],
  [<?=$ts_status['draft'];?>, <?=$ts_status['submitted'];?>, <?=$ts_status['approved'];?>, <?=$ts_status['rejected'];?>],
  ['#a8b1c5','#696cff','#28a745','#dc3545']
);
<?php endif; ?>
</script>
</body>
</html>

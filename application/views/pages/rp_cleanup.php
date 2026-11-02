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

          <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Admin /</span> RP Data Cleanup
          </h4>

          <?php
          $e = function($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
          $person = function($a) use ($e){ return $e(trim($a['first_name'] . ' ' . $a['last_name'])); };
          $role = function($r) use ($e){ return $e(ucwords(str_replace('_', ' ', (string)$r))); };
          $lists = $plan['lists'];
          $movable = array_filter($plan['files'], function($f){ return $f['exists'] && !$f['in_use']; });
          $labels = array(
            'users' => 'Accounts', 'staff' => 'Staff records', 'timesheets' => 'Timesheets', 'timesheet_details' => 'Timesheet daily entries',
            'timesheet_entries' => 'Timesheet entries (old format)', 'expenses' => 'Expenses', 'other_files' => 'Work package files',
            'other_file_versions' => 'Work package file versions', 'other_file_comments' => 'Comments on work package files',
            'monthly_financial_reports' => 'Monthly reports', 'forex_rates' => 'RP Forex rates', 'notifications' => 'Notifications',
            'audit_log' => 'Activity log entries', 'system_settings' => 'Signature setups of removed accounts',
            'monthly_report_items' => 'Monthly report lines', 'monthly_report_attachments' => 'Monthly report attachments',
            'monthly_report_summary' => 'Monthly report summary',
            'monthly_report_summary_category' => 'Monthly report summary by category', 'monthly_report_category_summary' => 'Monthly report summary by category',
            'monthly_report_summary_currency' => 'Monthly report summary by currency', 'monthly_report_currency_summary' => 'Monthly report summary by currency',
            'monthly_report_summary_wp' => 'Monthly report summary by work package', 'monthly_report_wp_summary' => 'Monthly report summary by work package',
          );
          ?>

          <?php if(!empty($error)): ?>
          <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle me-1"></i><?=$e($error);?></div>
          <?php endif; ?>

          <?php if($result !== NULL): ?>
            <?php if($result['ok']): ?>
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body">
                <h5 class="text-success"><i class="ti ti-circle-check me-1"></i>RP data removed</h5>
                <p class="mb-2">Removed <strong><?=array_sum($result['deleted']);?></strong> rows and moved <strong><?=count($result['moved']);?></strong> uploaded files into the backup.</p>
                <ul class="mb-3">
                  <?php foreach($result['deleted'] as $table => $n): if($n > 0): ?>
                  <li><?=$e($labels[$table] ?? $table);?>: <?=$n;?></li>
                  <?php endif; endforeach; ?>
                </ul>
                <?php if(!empty($result['not_moved'])): ?>
                <p class="mb-1">Files not moved:</p>
                <ul class="small text-muted mb-3">
                  <?php foreach($result['not_moved'] as $f): ?><li><?=$e($f);?></li><?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <p class="mb-1"><i class="ti ti-archive me-1"></i>Backup on the server: <code><?=$e($result['backup']['path']);?></code></p>
                <p class="small text-muted">It holds <code>rows.sql</code> (import it to put the rows back) and <code>files/</code> (copy back into the fms folder). The web server does not serve this folder.</p>
                <a href="<?=base_url('rpCleanup/backup/' . $result['backup']['folder']);?>" class="btn btn-outline-primary mb-3">
                  <i class="ti ti-download me-1"></i>Download rows.sql
                </a>
                <div class="alert alert-warning mb-0" role="alert">
                  <i class="ti ti-alert-triangle me-1"></i>Now delete <code>application/controllers/Rp_cleanup.php</code> and <code>application/views/pages/rp_cleanup.php</code> from the server.
                </div>
              </div>
            </div>
            <?php else: ?>
            <div class="alert alert-danger" role="alert"><i class="ti ti-alert-circle me-1"></i><?=$e($result['error']);?></div>
            <?php endif; ?>
          <?php else: ?>

          <div class="alert alert-danger" role="alert">
            <i class="ti ti-alert-triangle me-1"></i>
            This permanently removes Rwanda Polytechnic data from FMS. Nothing has been changed yet: check everything below first.
            A backup is written before anything is removed.
          </div>

          <?php if(!empty($plan['blockers'])): ?>
          <div class="alert alert-warning" role="alert">
            <p class="fw-semibold mb-1"><i class="ti ti-ban me-1"></i>The cleanup cannot run:</p>
            <ul class="mb-0">
              <?php foreach($plan['blockers'] as $b): ?><li><?=$e($b);?></li><?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <div class="row g-4">
            <div class="col-lg-4">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-shield-check me-1"></i>Kept</h5></div>
                <div class="card-body">
                  <?php if($plan['partner']): ?>
                  <p class="mb-3">Partner record: <strong><?=$e($plan['partner']['name']);?></strong></p>
                  <?php endif; ?>
                  <?php foreach($plan['keep'] as $a): ?>
                  <div class="mb-3">
                    <div class="fw-semibold"><?=$person($a);?></div>
                    <div class="small text-muted"><?=$role($a['role_name']);?> &middot; <?=$e($a['email']);?></div>
                  </div>
                  <?php endforeach; ?>
                  <p class="small text-muted mb-0">These accounts keep their login and the coordinator keeps the approval signature setup. Their timesheets, expenses, files and activity history are removed.</p>
                </div>
              </div>
            </div>

            <div class="col-lg-8">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-trash me-1"></i>To be removed (<?=$plan['total'];?> rows)</h5></div>
                <div class="card-body">
                  <div class="table-responsive mb-3">
                    <table class="table table-sm mb-0">
                      <tbody>
                        <?php foreach($plan['delete'] as $table => $ids): if(!empty($ids)): ?>
                        <tr><td><?=$e($labels[$table] ?? $table);?></td><td class="text-end fw-semibold"><?=count($ids);?></td></tr>
                        <?php endif; endforeach; ?>
                        <tr><td>Uploaded files moved into the backup</td><td class="text-end fw-semibold"><?=count($movable);?></td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card border-0 shadow-sm mt-4">
            <div class="card-header"><h5 class="mb-0"><i class="ti ti-list-details me-1"></i>Details</h5></div>
            <div class="card-body">

              <h6 class="fw-semibold">Accounts removed</h6>
              <?php if(empty($lists['accounts']) && empty($lists['staff_only'])): ?><p class="text-muted small">None</p><?php endif; ?>
              <ul class="small">
                <?php foreach($lists['accounts'] as $a): ?>
                <li><?=$person($a);?> &middot; <?=$role($a['role_name']);?> &middot; <?=$e($a['email']);?> (<?=$e($a['status']);?>)</li>
                <?php endforeach; ?>
                <?php foreach($lists['staff_only'] as $s): ?>
                <li><?=$person($s);?> &middot; staff record without an account</li>
                <?php endforeach; ?>
              </ul>

              <div class="table-responsive">
                <h6 class="fw-semibold">Timesheets</h6>
                <?php if(empty($lists['timesheets'])): ?><p class="text-muted small">None</p><?php else: ?>
                <table class="table table-sm small text-nowrap mb-4">
                  <thead><tr><th>#</th><th>Owner</th><th>Period</th><th>Status</th><th>Daily entries</th></tr></thead>
                  <tbody>
                    <?php foreach($lists['timesheets'] as $t): ?>
                    <tr><td><?=(int)$t['id'];?></td><td><?=$e($t['owner']);?></td><td><?=$e($t['period']);?></td><td><?=$e($t['status']);?></td><td><?=(int)$t['entries'];?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>

                <h6 class="fw-semibold">Expenses</h6>
                <?php if(empty($lists['expenses'])): ?><p class="text-muted small">None</p><?php else: ?>
                <table class="table table-sm small text-nowrap mb-4">
                  <thead><tr><th>#</th><th>Date</th><th class="text-end">Amount</th><th>Status</th><th>File</th></tr></thead>
                  <tbody>
                    <?php foreach($lists['expenses'] as $x): ?>
                    <tr><td><?=(int)$x['expense_id'];?></td><td><?=$e($x['Date']);?></td><td class="text-end"><?=number_format((float)$x['Amount'], 2);?> <?=$e($x['Currency']);?></td><td><?=$e($x['status']);?></td><td><?=$e($x['FileName']);?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>

                <h6 class="fw-semibold">Work package files</h6>
                <?php if(empty($lists['files'])): ?><p class="text-muted small">None</p><?php else: ?>
                <table class="table table-sm small text-nowrap mb-4">
                  <thead><tr><th>#</th><th>Name</th><th>WP</th><th>Versions</th></tr></thead>
                  <tbody>
                    <?php foreach($lists['files'] as $f): ?>
                    <tr><td><?=(int)$f['id'];?></td><td><?=$e($f['name']);?></td><td>WP<?=(int)$f['wp_id'];?></td><td><?=(int)$f['versions'];?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>

                <h6 class="fw-semibold">Monthly reports</h6>
                <?php if(empty($lists['reports'])): ?><p class="text-muted small">None</p><?php else: ?>
                <table class="table table-sm small text-nowrap mb-4">
                  <thead><tr><th>#</th><th>Partner</th><th>Period</th><th>Status</th><th>Lines</th><th>Why</th></tr></thead>
                  <tbody>
                    <?php foreach($lists['reports'] as $r): ?>
                    <tr><td><?=(int)$r['id'];?></td><td><?=$e($r['partner']);?></td><td><?=$e($r['period']);?></td><td><?=$e($r['status']);?></td><td><?=(int)$r['lines'];?></td><td><?=$e($r['note']);?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>

                <h6 class="fw-semibold">Uploaded files</h6>
                <?php if(empty($plan['files'])): ?><p class="text-muted small">None</p><?php else: ?>
                <table class="table table-sm small text-nowrap mb-4">
                  <thead><tr><th>File</th><th>Belongs to</th><th>What happens</th></tr></thead>
                  <tbody>
                    <?php foreach($plan['files'] as $path => $f): ?>
                    <tr>
                      <td><code><?=$e($path);?></code></td>
                      <td><?=$e($f['owner']);?></td>
                      <td><?=!$f['exists'] ? '<span class="text-muted">Already missing</span>' : ($f['in_use'] ? '<span class="text-muted">Still used elsewhere, left in place</span>' : 'Moved into the backup');?></td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if(empty($plan['blockers']) && $plan['total'] > 0): ?>
          <div class="card border-danger shadow-sm mt-4">
            <div class="card-body">
              <form action="<?=base_url('rpCleanup/execute');?>" method="POST" autocomplete="off">
                <input type="hidden" name="token" value="<?=$e($token);?>" />
                <input type="hidden" name="fingerprint" value="<?=$e($plan['fingerprint']);?>" />
                <label class="form-label" for="confirm_phrase">To back up and remove everything listed above, type <code><?=$e($phrase);?></code></label>
                <div class="d-flex flex-wrap gap-2">
                  <input type="text" class="form-control" id="confirm_phrase" name="confirm_phrase" style="max-width:280px;" spellcheck="false" required />
                  <button type="submit" class="btn btn-danger" id="confirmCleanup" disabled>
                    <i class="ti ti-trash me-1"></i>Back up and remove RP data
                  </button>
                </div>
              </form>
            </div>
          </div>
          <?php elseif(empty($plan['blockers'])): ?>
          <div class="alert alert-success mt-4" role="alert"><i class="ti ti-circle-check me-1"></i>There is no RP data left to remove.</div>
          <?php endif; ?>

          <?php endif; ?>

        </div><!-- /container -->

        <footer class="content-footer footer bg-footer-theme">
          <div class="container-xxl">
            <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
              <div>© <script>document.write(new Date().getFullYear());</script>, made with ❤️ by <a href="#" class="fw-semibold">ERASMUS+ GREATER</a></div>
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

<script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
<script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
<script src="<?=base_url();?>assets/js/main.js"></script>
<script>
$(function(){
  // The button only works once the phrase is typed exactly; the server checks it again
  $('#confirm_phrase').on('input', function(){
    $('#confirmCleanup').prop('disabled', $(this).val().trim() !== '<?=$e($phrase);?>');
  });
  $('#confirmCleanup').closest('form').on('submit', function(){
    $('#confirmCleanup').prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>Backing up and removing...');
  });
});
</script>
</body>
</html>

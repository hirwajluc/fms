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
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
  <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/flatpickr/flatpickr.css" />
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
            <span class="text-muted fw-light">Settings /</span> Forex Exchange
            <small class="text-muted fs-6 fw-normal ms-2">RWF → EUR daily rates</small>
          </h4>

          <?php if($this->session->flashdata('success')): ?>
          <div class="alert alert-success alert-dismissible" role="alert">
            <i class="ti ti-circle-check me-1"></i><?=$this->session->flashdata('success');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>
          <?php if($this->session->flashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible" role="alert">
            <i class="ti ti-alert-circle me-1"></i><?=$this->session->flashdata('error');?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php endif; ?>

          <?php
          $stats      = $stats ?? [];
          $total      = (int)($stats['total'] ?? 0);
          $earliest   = $stats['earliest'] ?? null;
          $latest_date= $stats['latest']   ?? null;
          $last_rate  = $stats['last_rate'] ?? null;
          ?>

          <!-- Stats row -->
          <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="avatar avatar-md bg-label-primary rounded">
                    <i class="ti ti-calendar-stats fs-4"></i>
                  </div>
                  <div>
                    <div class="fw-bold fs-4"><?=$total;?></div>
                    <div class="text-muted small">Days recorded</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="avatar avatar-md bg-label-success rounded">
                    <i class="ti ti-currency-euro fs-4"></i>
                  </div>
                  <div>
                    <div class="fw-bold fs-4"><?=$last_rate ? number_format($last_rate, 2) : '—';?> RWF</div>
                    <div class="text-muted small">Latest rate (= 1 EUR)</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="avatar avatar-md bg-label-info rounded">
                    <i class="ti ti-calendar-event fs-4"></i>
                  </div>
                  <div>
                    <div class="fw-bold"><?=$earliest ? date('d M Y', strtotime($earliest)) : '—';?></div>
                    <div class="text-muted small">Earliest entry</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-xl-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                  <div class="avatar avatar-md bg-label-warning rounded">
                    <i class="ti ti-calendar-due fs-4"></i>
                  </div>
                  <div>
                    <div class="fw-bold"><?=$latest_date ? date('d M Y', strtotime($latest_date)) : '—';?></div>
                    <div class="text-muted small">Latest entry</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Table card -->
          <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
              <h5 class="mb-0"><i class="ti ti-table me-1"></i> Exchange Rate Records</h5>
              <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRateModal">
                  <i class="ti ti-plus me-1"></i> Add Rate
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadExcelModal">
                  <i class="ti ti-upload me-1"></i> Upload Excel
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table id="forexTable" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>RWF → EUR Rate</th>
                    <th>Example conversion</th>
                    <th>Added By</th>
                    <th>Added On</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if(!empty($rates)): ?>
                  <?php foreach($rates as $i => $r): ?>
                  <tr>
                    <td class="text-muted small"><?=($i + 1);?></td>
                    <td><strong><?=date('d M Y', strtotime($r['rate_date']));?></strong></td>
                    <td class="text-muted small"><?=date('l', strtotime($r['rate_date']));?></td>
                    <td>
                      <span class="fw-semibold text-success"><?=number_format($r['rwf_per_eur'], 2);?> RWF</span>
                      <small class="text-muted d-block">= 1 EUR</small>
                    </td>
                    <td>
                      <small class="text-muted">10,000 RWF = <strong><?=number_format(10000 / $r['rwf_per_eur'], 2);?> EUR</strong></small>
                    </td>
                    <td><?=htmlspecialchars($r['added_by_name'] ?? '—');?></td>
                    <td class="text-muted small"><?=date('d M Y H:i', strtotime($r['created_at']));?></td>
                    <td>
                      <button type="button" class="btn btn-sm btn-outline-danger"
                              onclick="confirmDelete(<?=$r['id'];?>, '<?=date('d M Y', strtotime($r['rate_date']));?>')">
                        <i class="ti ti-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

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

<!-- ── Add Single Rate Modal ──────────────────────────────────────── -->
<div class="modal fade" id="addRateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-currency-euro me-1"></i> Add / Update Rate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?=base_url('saveForexRate');?>" method="POST">
        <div class="modal-body">
          <div class="alert alert-info py-2 small mb-3">
            <i class="ti ti-info-circle me-1"></i>
            Enter how many <strong>RWF = 1 EUR</strong> for that day (from BNR or your source).
            Used to convert RWF expenses → EUR. If a rate already exists for the date it will be overwritten.
          </div>
          <div class="mb-3">
            <label class="form-label" for="rate_date">Date <span class="text-danger">*</span></label>
            <input type="date" id="rate_date" name="rate_date" class="form-control"
                   max="<?=date('Y-m-d');?>" required />
          </div>
          <div class="mb-3">
            <label class="form-label" for="rwf_per_eur">
              RWF → EUR rate <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <input type="number" id="rwf_per_eur" name="rwf_per_eur" class="form-control"
                     step="0.0001" min="1" placeholder="e.g. 1210.50" required />
              <span class="input-group-text">RWF = 1 EUR</span>
            </div>
            <div class="form-text">Example: enter <strong>1210.50</strong> if 1,210.50 RWF converts to 1 EUR that day.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i> Save Rate</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Upload Excel Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="uploadExcelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ti ti-upload me-1"></i> Upload Excel File</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 small mb-3">
          <i class="ti ti-info-circle me-1"></i>
          The file must have <strong>Column A = Date</strong> and <strong>Column B = RWF → EUR rate</strong>
          (how many RWF equal 1 EUR, e.g. 1210.50).
          Row 1 is the header and is skipped automatically.
        </div>
        <div class="mb-3 text-center">
          <a href="<?=base_url('downloadForexTemplate');?>" class="btn btn-outline-success btn-sm">
            <i class="ti ti-download me-1"></i> Download Template
          </a>
        </div>
        <div class="mb-3">
          <label class="form-label" for="forex_file">Excel File (.xlsx / .xls) <span class="text-danger">*</span></label>
          <input type="file" id="forex_file" class="form-control" accept=".xlsx,.xls" required />
        </div>
        <div id="uploadStatus" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="btnUploadExcel">
          <i class="ti ti-upload me-1"></i> Upload & Import
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Delete confirmation modal ──────────────────────────────────── -->
<div class="modal fade" id="deleteRateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger"><i class="ti ti-trash me-1"></i> Delete Rate</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Delete the rate for <strong id="deleteDateLabel"></strong>? This cannot be undone.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <a href="#" id="confirmDeleteLink" class="btn btn-danger btn-sm">
          <i class="ti ti-trash me-1"></i> Delete
        </a>
      </div>
    </div>
  </div>
</div>

<!-- JS -->
<script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
<script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
<script src="<?=base_url();?>assets/js/main.js"></script>

<script>
$(function(){

  // DataTable
  $('#forexTable').DataTable({
    order: [[1, 'desc']],
    pageLength: 25,
    columnDefs: [{ targets: [0, 7], orderable: false }],
    language: { emptyTable: 'No exchange rates recorded yet.' }
  });

  // Upload Excel
  $('#btnUploadExcel').on('click', function(){
    var file = $('#forex_file')[0].files[0];
    if(!file){ alert('Please select an Excel file first.'); return; }

    var ext = file.name.split('.').pop().toLowerCase();
    if(ext !== 'xlsx' && ext !== 'xls'){
      alert('Only .xlsx and .xls files are accepted.');
      return;
    }

    var $btn    = $(this).prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> Importing...');
    var $status = $('#uploadStatus');
    $status.removeClass('d-none alert-danger alert-success alert-info').addClass('alert alert-info').html('<i class="ti ti-loader ti-spin me-1"></i> Processing file...');

    var fd = new FormData();
    fd.append('forex_file', file);

    $.ajax({
      url:         '<?=base_url('uploadForexExcel');?>',
      type:        'POST',
      data:        fd,
      processData: false,
      contentType: false,
      headers:     {'X-Requested-With': 'XMLHttpRequest'},
      success: function(res){
        $btn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Upload & Import');
        if(res.success){
          $status.removeClass('alert-info').addClass('alert-success').html('<i class="ti ti-circle-check me-1"></i> ' + res.message + ' Reloading...');
          setTimeout(function(){ location.reload(); }, 1500);
        } else {
          $status.removeClass('alert-info').addClass('alert-danger').html('<i class="ti ti-alert-circle me-1"></i> ' + res.message);
        }
      },
      error: function(){
        $btn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Upload & Import');
        $status.removeClass('alert-info').addClass('alert-danger').html('<i class="ti ti-alert-circle me-1"></i> Request failed. Please try again.');
      }
    });
  });

});

function confirmDelete(id, dateLabel){
  document.getElementById('deleteDateLabel').textContent  = dateLabel;
  document.getElementById('confirmDeleteLink').href = '<?=base_url('deleteForexRate');?>/' + id;
  new bootstrap.Modal(document.getElementById('deleteRateModal')).show();
}
</script>
</body>
</html>

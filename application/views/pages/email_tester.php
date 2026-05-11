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
            <span class="text-muted fw-light">Settings /</span> Email Notification Tester
            <small class="text-muted fs-6 fw-normal ms-2">Send test emails to verify templates</small>
          </h4>

          <div class="row">
            <!-- Left: Send form -->
            <div class="col-lg-5 col-12 mb-4">
              <div class="card shadow-sm h-100">
                <div class="card-header border-bottom">
                  <h5 class="card-title mb-0">
                    <i class="ti ti-send me-2 text-primary"></i>Send Test Email
                  </h5>
                </div>
                <div class="card-body">

                  <div id="resultArea" class="mb-3" style="display:none;"></div>

                  <div class="mb-3">
                    <label for="testEmail" class="form-label fw-semibold">Recipient Email</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="ti ti-mail"></i></span>
                      <input type="email" id="testEmail" class="form-control"
                        placeholder="someone@example.com" value="<?=htmlspecialchars($this->session->userdata('fms_email') ?? '');?>" />
                    </div>
                    <div class="form-text">The test email will be delivered to this address using mock data.</div>
                  </div>

                  <div class="mb-4">
                    <label for="emailType" class="form-label fw-semibold">Notification Type</label>
                    <select id="emailType" class="form-select">
                      <option value="">— Select a notification type —</option>
                      <optgroup label="Account">
                        <option value="account_created">Account Created (welcome + temp password)</option>
                        <option value="password_reset">Password Reset (forgot password)</option>
                        <option value="password_changed">Password Changed (confirmation)</option>
                      </optgroup>
                      <optgroup label="Timesheets">
                        <option value="timesheet_submitted">Timesheet Submitted (admin notification)</option>
                        <option value="timesheet_approved">Timesheet Approved (staff notification)</option>
                        <option value="timesheet_rejected">Timesheet Rejected (staff notification)</option>
                      </optgroup>
                      <optgroup label="Expenses">
                        <option value="expense_approved">Expense Approved (staff notification)</option>
                        <option value="expense_rejected">Expense Rejected (staff notification)</option>
                      </optgroup>
                      <optgroup label="Monthly Reports">
                        <option value="monthly_report_submitted">Monthly Report Submitted (admin notification)</option>
                        <option value="monthly_report_approved">Monthly Report Approved (coordinator notification)</option>
                        <option value="monthly_report_rejected">Monthly Report Rejected (coordinator notification)</option>
                      </optgroup>
                    </select>
                  </div>

                  <button id="btnSendTest" class="btn btn-primary w-100" disabled>
                    <i class="ti ti-send me-1"></i> Send Test Email
                  </button>
                </div>
              </div>
            </div>

            <!-- Right: Quick reference -->
            <div class="col-lg-7 col-12 mb-4">
              <div class="card shadow-sm h-100">
                <div class="card-header border-bottom">
                  <h5 class="card-title mb-0">
                    <i class="ti ti-list-check me-2 text-success"></i>Notification Templates Reference
                  </h5>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0">
                      <thead class="table-light">
                        <tr>
                          <th>Type</th>
                          <th>Trigger</th>
                          <th>Recipient</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td><span class="badge bg-label-primary">account_created</span></td>
                          <td>New user account created by admin</td>
                          <td>New user</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-warning">password_reset</span></td>
                          <td>User requests forgot password</td>
                          <td>Requesting user</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-info">password_changed</span></td>
                          <td>User changes their password</td>
                          <td>User</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-secondary">timesheet_submitted</span></td>
                          <td>Staff submits a timesheet</td>
                          <td>Admins &amp; coordinators</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-success">timesheet_approved</span></td>
                          <td>Admin/coordinator approves timesheet</td>
                          <td>Staff member</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-danger">timesheet_rejected</span></td>
                          <td>Admin/coordinator rejects timesheet</td>
                          <td>Staff member</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-success">expense_approved</span></td>
                          <td>Coordinator approves an expense</td>
                          <td>Staff member</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-danger">expense_rejected</span></td>
                          <td>Coordinator rejects an expense</td>
                          <td>Staff member</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-secondary">report_submitted</span></td>
                          <td>Coordinator submits monthly report</td>
                          <td>Admins</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-success">report_approved</span></td>
                          <td>Admin approves monthly report</td>
                          <td>Coordinator</td>
                        </tr>
                        <tr>
                          <td><span class="badge bg-label-danger">report_rejected</span></td>
                          <td>Admin rejects monthly report</td>
                          <td>Coordinator</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-footer text-muted small border-top">
                  <i class="ti ti-info-circle me-1"></i>
                  All test emails use mock data. SMTP: <code>ssl://mail.greaterproject.eu:465</code>
                </div>
              </div>
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

<script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
<script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
<script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
<script src="<?=base_url();?>assets/js/main.js"></script>
<script>
$(function(){
  // Enable button only when both fields are filled
  function checkReady(){
    var email = $('#testEmail').val().trim();
    var type  = $('#emailType').val();
    $('#btnSendTest').prop('disabled', !email || !type);
  }
  $('#testEmail, #emailType').on('input change', checkReady);

  $('#btnSendTest').on('click', function(){
    var $btn = $(this);
    var email = $('#testEmail').val().trim();
    var type  = $('#emailType').val();

    if(!email || !type){ return; }

    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending...');
    $('#resultArea').hide();

    $.ajax({
      url: '<?=base_url('sendTestEmail');?>',
      method: 'POST',
      dataType: 'json',
      data: { email: email, type: type },
      success: function(res){
        var cls = res.success ? 'alert-success' : 'alert-danger';
        var ico = res.success ? 'ti-circle-check' : 'ti-alert-circle';
        $('#resultArea')
          .removeClass('alert-success alert-danger')
          .addClass('alert ' + cls)
          .html('<i class="ti ' + ico + ' me-1"></i>' + res.message)
          .show();
      },
      error: function(){
        $('#resultArea')
          .removeClass('alert-success').addClass('alert alert-danger')
          .html('<i class="ti ti-alert-circle me-1"></i> Request failed. Check network or server logs.')
          .show();
      },
      complete: function(){
        $btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Send Test Email');
        checkReady();
      }
    });
  });
});
</script>
</body>
</html>

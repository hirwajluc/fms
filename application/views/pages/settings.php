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
            <span class="text-muted fw-light">Account /</span> Settings
          </h4>

          <div class="row">
            <div class="col-xl-8 col-lg-10">
              <div class="card border-0 shadow-sm">
                <div class="card-header">
                  <h5 class="mb-0"><i class="ti ti-mail me-1"></i> Email Notifications</h5>
                </div>
                <div class="card-body">

                  <div id="notificationFeedback" role="status" aria-live="polite"></div>

                  <?php if(!$notifications_supported): ?>
                  <div class="alert alert-warning mb-4" role="alert">
                    <i class="ti ti-alert-triangle me-2"></i>This setting is not available yet. You will keep receiving email notifications.
                  </div>
                  <?php endif; ?>

                  <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                      <label class="form-label fw-semibold mb-1" for="emailNotificationsToggle">Receive email notifications</label>
                      <div class="text-muted small">
                        Emails about timesheets, expenses, monthly reports and work package file uploads.
                      </div>
                      <div class="mt-2">
                        <span class="small text-muted me-1">Status:</span>
                        <span id="notificationStatus" class="badge <?=$notifications_enabled ? 'bg-label-success' : 'bg-label-secondary';?>">
                          <?=$notifications_enabled ? 'Enabled' : 'Disabled';?>
                        </span>
                      </div>
                    </div>
                    <div class="form-check form-switch mb-0 flex-shrink-0">
                      <input class="form-check-input" type="checkbox" role="switch" id="emailNotificationsToggle"
                             style="width:3em;height:1.5em;cursor:pointer;"
                             <?=$notifications_enabled ? 'checked' : '';?> <?=$notifications_supported ? '' : 'disabled';?> />
                    </div>
                  </div>

                  <hr class="my-4" />

                  <p class="text-muted small mb-0">
                    <i class="ti ti-shield-lock me-1"></i>
                    Security emails are always sent, whatever this setting: new account details, password resets and password change alerts.
                  </p>

                </div>
              </div>
            </div>
          </div>

        </div><!-- /container -->

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
<script src="<?=base_url();?>assets/js/main.js"></script>

<script>
$(function(){

  function showFeedback(type, icon, message){
    var $alert = $('<div class="alert alert-dismissible mb-4" role="alert"></div>').addClass('alert-' + type);
    $alert.append($('<i class="ti me-2"></i>').addClass(icon));
    $alert.append(document.createTextNode(message));
    $alert.append('<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    $('#notificationFeedback').empty().append($alert);
  }

  function showStatus(enabled){
    $('#notificationStatus')
      .toggleClass('bg-label-success', enabled)
      .toggleClass('bg-label-secondary', !enabled)
      .text(enabled ? 'Enabled' : 'Disabled');
  }

  $('#emailNotificationsToggle').on('change', function(){
    var $toggle = $(this);
    var enabled = $toggle.is(':checked');

    $toggle.prop('disabled', true);

    $.ajax({
      url:      '<?=base_url('updateNotificationPreference');?>',
      type:     'POST',
      dataType: 'json',
      data:     {enabled: enabled ? 1 : 0},
      headers:  {'X-Requested-With': 'XMLHttpRequest'}
    }).done(function(res){
      showStatus(res.enabled);
      $toggle.prop('checked', res.enabled);
      showFeedback('success', 'ti-circle-check', res.message);
    }).fail(function(xhr){
      // Not saved: put the switch back so it always shows the stored value
      $toggle.prop('checked', !enabled);
      var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Could not save your preference. Please try again.';
      showFeedback('danger', 'ti-alert-circle', message);
    }).always(function(){
      $toggle.prop('disabled', false);
    });
  });

});
</script>
</body>
</html>

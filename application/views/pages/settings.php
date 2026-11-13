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

              <!-- Signature -->
              <div class="card border-0 shadow-sm mt-4" id="signature">
                <div class="card-header">
                  <h5 class="mb-0"><i class="ti ti-signature me-1"></i> My Signature</h5>
                </div>
                <div class="card-body">

                  <div id="signatureFeedback" role="status" aria-live="polite"></div>

                  <p class="text-muted small">
                    Your signature is added to the timesheets you submit<?=$signs_approvals ? ', and to the timesheets and expenses you approve' : '';?>.
                    Timesheets you submitted before adding a signature show your current one.
                  </p>

                  <div class="border rounded p-3 bg-light text-center mb-3">
                    <img id="signatureImage" src="<?=htmlspecialchars($signature_url, ENT_QUOTES);?>" alt="Your signature"
                         style="max-height:100px;max-width:100%;" <?=$signature_url ? '' : 'hidden';?> />
                    <div id="signatureEmpty" class="text-muted small py-3" <?=$signature_url ? 'hidden' : '';?>>
                      <i class="ti ti-signature-off d-block mb-1" style="font-size:1.5rem;"></i>No signature yet
                    </div>
                  </div>

                  <label class="form-label" for="signatureInput">Upload a signature</label>
                  <input type="file" class="form-control" id="signatureInput" accept="image/png,image/jpeg" />
                  <div class="form-text">PNG or JPG, up to 2 MB. Sign on white paper and take a clear photo, or use a PNG with a transparent background.</div>

                  <div class="d-flex flex-wrap gap-2 mt-3">
                    <button type="button" class="btn btn-primary" id="signatureSave" disabled>
                      <i class="ti ti-device-floppy me-1"></i> Save signature
                    </button>
                    <button type="button" class="btn btn-label-danger" id="signatureRemove" <?=$signature_url ? '' : 'hidden';?>>
                      <i class="ti ti-trash me-1"></i> Remove
                    </button>
                  </div>

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

  function showFeedback(type, icon, message, target){
    var $alert = $('<div class="alert alert-dismissible mb-4" role="alert"></div>').addClass('alert-' + type);
    $alert.append($('<i class="ti me-2"></i>').addClass(icon));
    $alert.append(document.createTextNode(message));
    $alert.append('<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
    $(target || '#notificationFeedback').empty().append($alert);
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

  // ── Signature ──
  var savedSignature = $('#signatureImage').attr('hidden') ? '' : $('#signatureImage').attr('src');
  var previewUrl = null;

  function showSignature(url){
    $('#signatureImage').attr('src', url || '').prop('hidden', !url);
    $('#signatureEmpty').prop('hidden', !!url);
  }

  function clearPreview(){
    if(previewUrl){ URL.revokeObjectURL(previewUrl); previewUrl = null; }
  }

  // Show the chosen image before it is saved
  $('#signatureInput').on('change', function(){
    clearPreview();
    var file = this.files[0];
    if(!file){ showSignature(savedSignature); $('#signatureSave').prop('disabled', true); return; }
    if(!/^image\/(png|jpeg)$/.test(file.type)){
      showFeedback('danger', 'ti-alert-circle', 'Only PNG or JPG images can be used as a signature.', '#signatureFeedback');
      this.value = ''; showSignature(savedSignature); $('#signatureSave').prop('disabled', true); return;
    }
    if(file.size > 2 * 1024 * 1024){
      showFeedback('danger', 'ti-alert-circle', 'The image is too large (max 2 MB).', '#signatureFeedback');
      this.value = ''; showSignature(savedSignature); $('#signatureSave').prop('disabled', true); return;
    }
    previewUrl = URL.createObjectURL(file);
    showSignature(previewUrl);
    $('#signatureFeedback').empty();
    $('#signatureSave').prop('disabled', false);
  });

  function sendSignature(formData, $button){
    $button.prop('disabled', true);
    $.ajax({
      url:         '<?=base_url('updateMySignature');?>',
      type:        'POST',
      data:        formData,
      processData: false,
      contentType: false,
      dataType:    'json',
      headers:     {'X-Requested-With': 'XMLHttpRequest'}
    }).done(function(res){
      if(!res.success){
        showFeedback('danger', 'ti-alert-circle', res.message, '#signatureFeedback');
        return;
      }
      clearPreview();
      $('#signatureInput').val('');
      $('#signatureSave').prop('disabled', true);
      savedSignature = res.removed ? '' : res.preview_url;
      showSignature(savedSignature);
      $('#signatureRemove').prop('hidden', !savedSignature);
      showFeedback('success', 'ti-circle-check', res.message, '#signatureFeedback');
    }).fail(function(){
      showFeedback('danger', 'ti-alert-circle', 'Could not save your signature. Please try again.', '#signatureFeedback');
    }).always(function(){
      $button.prop('disabled', $button.is('#signatureSave') && !$('#signatureInput').val());
    });
  }

  $('#signatureSave').on('click', function(){
    var file = $('#signatureInput')[0].files[0];
    if(!file){ return; }
    var formData = new FormData();
    formData.append('signature', file);
    sendSignature(formData, $(this));
  });

  $('#signatureRemove').on('click', function(){
    if(!confirm('Remove your signature? Timesheets you submit from now on will not be signed.')){ return; }
    var formData = new FormData();
    formData.append('remove', '1');
    sendSignature(formData, $(this));
  });

});
</script>
</body>
</html>

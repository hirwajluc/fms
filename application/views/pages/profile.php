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
            <span class="text-muted fw-light">Account /</span> My Profile
          </h4>

          <?php
          $u = $profile_user ?? [];
          $role_label = ucwords(str_replace('_', ' ', $u['role_name'] ?? ''));
          $initials   = strtoupper(substr($u['first_name'] ?? 'U', 0, 1) . substr($u['last_name'] ?? '', 0, 1));

          // Role colour
          $role_color = 'secondary';
          switch($u['role_id'] ?? 0){
            case 1: $role_color = 'danger';  break;
            case 2: $role_color = 'warning'; break;
            case 3: $role_color = 'info';    break;
            case 4: $role_color = 'primary'; break;
          }
          ?>

          <div class="row g-4">

            <!-- ── Left column: avatar card ───────────────────────── -->
            <div class="col-xl-3 col-lg-4">
              <div class="card text-center border-0 shadow-sm">
                <div class="card-body pt-4">
                  <div class="mx-auto mb-3 avatar avatar-xl"
                       style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;">
                    <span class="text-white fw-bold fs-3"><?=$initials;?></span>
                  </div>
                  <h5 class="mb-1 fw-semibold">
                    <?=htmlspecialchars(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));?>
                  </h5>
                  <span class="badge bg-label-<?=$role_color;?> mb-2"><?=$role_label;?></span>
                  <p class="text-muted small mb-1">
                    <i class="ti ti-building me-1"></i><?=htmlspecialchars($u['partner_name'] ?? '—');?>
                  </p>
                  <p class="text-muted small mb-1">
                    <i class="ti ti-mail me-1"></i><?=htmlspecialchars($u['email'] ?? '—');?>
                  </p>
                  <?php if(!empty($u['phone'])): ?>
                  <p class="text-muted small mb-0">
                    <i class="ti ti-phone me-1"></i><?=htmlspecialchars($u['phone']);?>
                  </p>
                  <?php endif; ?>
                </div>
                <div class="card-footer border-0 py-3 bg-light">
                  <small class="text-muted">
                    <i class="ti ti-clock me-1"></i>
                    Member since <?=date('M Y', strtotime($u['created_at'] ?? 'now'));?>
                  </small>
                </div>
              </div>
            </div>

            <!-- ── Right column: tabs ───────────────────���─────────── -->
            <div class="col-xl-9 col-lg-8">

              <!-- Nav tabs -->
              <ul class="nav nav-tabs mb-0 border-0" id="profileTabs" role="tablist">
                <li class="nav-item">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">
                    <i class="ti ti-user me-1"></i> Personal Info
                  </button>
                </li>
                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-password" id="pwdTabBtn" type="button">
                    <i class="ti ti-lock me-1"></i> Change Password
                  </button>
                </li>
              </ul>

              <div class="tab-content">

                  <!-- ── Personal Info tab ────────────────────── -->
                  <div class="tab-pane fade show active" id="tab-info">
                  <div class="card border-0 shadow-sm p-4" style="border-top-left-radius:0;">

                    <?php if($this->session->flashdata('profile_success')): ?>
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                      <i class="ti ti-circle-check me-2"></i><?=$this->session->flashdata('profile_success');?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('profile_error')): ?>
                    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                      <i class="ti ti-alert-circle me-2"></i><?=$this->session->flashdata('profile_error');?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="<?=base_url('updateProfile');?>" method="POST">

                      <h6 class="fw-semibold mb-3 text-muted text-uppercase small">
                        <i class="ti ti-edit me-1"></i>Editable Information
                      </h6>

                      <div class="row g-3 mb-4">
                        <div class="col-md-6">
                          <label class="form-label" for="first_name">First Name <span class="text-danger">*</span></label>
                          <input type="text" id="first_name" name="first_name" class="form-control"
                                 value="<?=htmlspecialchars($u['first_name'] ?? '');?>" required />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="last_name">Last Name <span class="text-danger">*</span></label>
                          <input type="text" id="last_name" name="last_name" class="form-control"
                                 value="<?=htmlspecialchars($u['last_name'] ?? '');?>" required />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="phone">Phone Number</label>
                          <input type="text" id="phone" name="phone" class="form-control"
                                 value="<?=htmlspecialchars($u['phone'] ?? '');?>"
                                 placeholder="+250 7XX XXX XXX" />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label" for="department">Department</label>
                          <input type="text" id="department" name="department" class="form-control"
                                 value="<?=htmlspecialchars($u['department'] ?? '');?>"
                                 placeholder="e.g. Research & Innovation" />
                        </div>
                      </div>

                      <hr class="my-3" />

                      <h6 class="fw-semibold mb-3 text-muted text-uppercase small">
                        <i class="ti ti-lock me-1"></i>Read-only Information
                      </h6>

                      <div class="row g-3 mb-4">
                        <div class="col-md-6">
                          <label class="form-label">Login Email</label>
                          <input type="text" class="form-control bg-light" value="<?=htmlspecialchars($u['email'] ?? '');?>" disabled />
                          <div class="form-text">Contact an admin to change your login email.</div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Position</label>
                          <input type="text" class="form-control bg-light" value="<?=htmlspecialchars($u['position'] ?? '—');?>" disabled />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Institution</label>
                          <input type="text" class="form-control bg-light" value="<?=htmlspecialchars($u['partner_name'] ?? '—');?>" disabled />
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Role</label>
                          <input type="text" class="form-control bg-light" value="<?=$role_label;?>" disabled />
                        </div>
                      </div>

                      <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Changes
                      </button>

                    </form>
                  </div><!-- /card -->
                  </div>
                  <!-- /Personal Info tab -->

                  <!-- ── Change Password tab ──────────────────── -->
                  <div class="tab-pane fade" id="tab-password">
                  <div class="card border-0 shadow-sm p-4">

                    <?php if($this->session->flashdata('password_success')): ?>
                    <div class="alert alert-success alert-dismissible mb-4" role="alert">
                      <i class="ti ti-circle-check me-2"></i><?=$this->session->flashdata('password_success');?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if($this->session->flashdata('password_error')): ?>
                    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                      <i class="ti ti-alert-circle me-2"></i><?=$this->session->flashdata('password_error');?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="<?=base_url('updatePassword');?>" method="POST">

                      <h6 class="fw-semibold mb-3 text-muted text-uppercase small">
                        <i class="ti ti-key me-1"></i>Update your password
                      </h6>

                      <div class="row g-3 mb-4" style="max-width:480px;">
                        <div class="col-12">
                          <label class="form-label" for="current_password">
                            Current Password <span class="text-danger">*</span>
                          </label>
                          <div class="input-group">
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control" placeholder="Your current password" required />
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="current_password">
                              <i class="ti ti-eye"></i>
                            </button>
                          </div>
                        </div>
                        <div class="col-12">
                          <label class="form-label" for="new_password">
                            New Password <span class="text-danger">*</span>
                          </label>
                          <div class="input-group">
                            <input type="password" id="new_password" name="new_password"
                                   class="form-control" placeholder="At least 6 characters" required minlength="6" />
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="new_password">
                              <i class="ti ti-eye"></i>
                            </button>
                          </div>
                          <!-- Strength bar -->
                          <div class="mt-2">
                            <div class="progress" style="height:4px;">
                              <div id="pwStrengthBar" class="progress-bar" style="width:0%;transition:width .3s;"></div>
                            </div>
                            <small id="pwStrengthLabel" class="text-muted"></small>
                          </div>
                        </div>
                        <div class="col-12">
                          <label class="form-label" for="confirm_password">
                            Confirm New Password <span class="text-danger">*</span>
                          </label>
                          <div class="input-group">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-control" placeholder="Repeat new password" required />
                            <button class="btn btn-outline-secondary toggle-pw" type="button" data-target="confirm_password">
                              <i class="ti ti-eye"></i>
                            </button>
                          </div>
                          <div id="confirmMatchMsg" class="form-text"></div>
                        </div>
                      </div>

                      <button type="submit" class="btn btn-warning" id="btnChangePw">
                        <i class="ti ti-lock-open me-1"></i>Change Password
                      </button>

                    </form>
                  </div><!-- /card -->
                  </div>
                  <!-- /Change Password tab -->

              </div>
            </div>
            <!-- /Right column -->

          </div>
          <!-- /row -->

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

  // ── Auto-open password tab if redirected with #change-password ──
  if(window.location.hash === '#change-password'){
    $('#pwdTabBtn').tab('show');
  }

  // ── Toggle password visibility ──────────────────────────────────
  $(document).on('click', '.toggle-pw', function(){
    var targetId = $(this).data('target');
    var $inp = $('#' + targetId);
    var isText = $inp.attr('type') === 'text';
    $inp.attr('type', isText ? 'password' : 'text');
    $(this).find('i').toggleClass('ti-eye ti-eye-off');
  });

  // ── Password strength meter ──────────────────────────────────────
  $('#new_password').on('input', function(){
    var pw  = $(this).val();
    var score = 0;
    if(pw.length >= 6)  score++;
    if(pw.length >= 10) score++;
    if(/[A-Z]/.test(pw)) score++;
    if(/[0-9]/.test(pw)) score++;
    if(/[^A-Za-z0-9]/.test(pw)) score++;

    var configs = [
      { label: '',         color: '',          width: '0%'   },
      { label: 'Weak',     color: 'bg-danger',  width: '25%'  },
      { label: 'Fair',     color: 'bg-warning', width: '50%'  },
      { label: 'Good',     color: 'bg-info',    width: '75%'  },
      { label: 'Strong',   color: 'bg-success', width: '100%' },
    ];
    var c = configs[Math.min(score, 4)];
    $('#pwStrengthBar').attr('class', 'progress-bar ' + c.color).css('width', c.width);
    $('#pwStrengthLabel').text(c.label ? 'Strength: ' + c.label : '');
  });

  // ── Confirm password match ───────────────────────────────────────
  $('#confirm_password, #new_password').on('input', function(){
    var nw  = $('#new_password').val();
    var cnf = $('#confirm_password').val();
    if(!cnf) { $('#confirmMatchMsg').text('').removeClass('text-success text-danger'); return; }
    if(nw === cnf){
      $('#confirmMatchMsg').text('✓ Passwords match').removeClass('text-danger').addClass('text-success');
      $('#btnChangePw').prop('disabled', false);
    } else {
      $('#confirmMatchMsg').text('✗ Passwords do not match').removeClass('text-success').addClass('text-danger');
      $('#btnChangePw').prop('disabled', true);
    }
  });

});
</script>
</body>
</html>

<?php
/*
 * One form for New User, New Staff Member, Edit User and Edit Staff Member.
 * $mode: new_user | new_staff | edit_user | edit_staff; $person: the record being edited.
 */
$person  = isset($person) && is_array($person) ? $person : array();
$is_new  = ($mode === 'new_user' || $mode === 'new_staff');
$admin   = $this->auth_manager->is_super_admin() || $this->auth_manager->is_admin();
$me      = (int)$this->session->userdata('fms_user_id');
$account = $mode === 'edit_user' ? $person : (isset($person['account']) ? $person['account'] : NULL);
$self    = !$is_new && $account && (int)$account['user_id'] === $me;
$val     = function($field) use ($person){ return set_value($field, isset($person[$field]) ? (string)$person[$field] : ''); };
$name    = trim(preg_replace('/\s+/', ' ', (isset($person['title']) ? $person['title'] : '') . ' ' . (isset($person['first_name']) ? $person['first_name'] : '') . ' ' . (isset($person['last_name']) ? $person['last_name'] : '')));
$pages   = array(
  'new_user'   => array('Users', 'New User', 'Create New User Account', 'saveUser', 'Create User', 'ti-user-plus'),
  'new_staff'  => array('Staff', 'New Staff Member', 'Create New Staff Member', 'saveStaff', 'Create Staff Member', 'ti-user-plus'),
  'edit_user'  => array('Users', 'Edit User', 'Edit User: ' . $name, 'updateUser/' . (isset($person['user_id']) ? (int)$person['user_id'] : 0), 'Update User', 'ti-device-floppy'),
  'edit_staff' => array('Staff', 'Edit Staff Member', 'Edit Staff Member: ' . $name, 'updateStaff/' . (isset($person['staff_id']) ? (int)$person['staff_id'] : 0), 'Update Staff Member', 'ti-device-floppy'),
);
list($crumb, $page_name, $card_title, $action, $button, $icon) = $pages[$mode];
$current_role   = $account ? (int)$account['role_id'] : 4;
$current_status = isset($person['status']) ? $person['status'] : 'active';
?>
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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/select2/select2.css" />

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
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light"><?=$crumb;?> /</span> <?=$page_name;?></h4>

              <?php if($this->session->flashdata('success')): ?>
              <div class="alert alert-success alert-dismissible" role="alert">
                <?=$this->session->flashdata('success');?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <?php if($this->session->flashdata('error')): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <?=$this->session->flashdata('error');?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <?php if(validation_errors() || !empty($form_error)): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <?=validation_errors();?>
                <?php if(!empty($form_error)): ?><p class="mb-0"><?=htmlspecialchars($form_error);?></p><?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
              <?php endif; ?>

              <div class="row">
                <div class="col-12">
                  <div class="card">
                    <h5 class="card-header"><?=htmlspecialchars($card_title);?></h5>
                    <div class="card-body">
                      <form id="personForm" class="row g-3" action="<?=base_url($action);?>" method="POST" novalidate>

                        <div class="col-12">
                          <h6 class="fw-semibold">1. Personal Information</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-4">
                          <label class="form-label" for="title">Title</label>
                          <input type="text" id="title" name="title" class="form-control" maxlength="50"
                                 placeholder="e.g. Prof. Dr.-Ing." autocomplete="honorific-prefix"
                                 value="<?=$val('title');?>" <?=$titles_supported ? '' : 'disabled';?> />
                          <div class="d-flex flex-wrap gap-1 mt-2" role="group" aria-label="Title parts">
                            <?php foreach(array('Prof.', 'Dr.', 'Dr.-Ing.', 'Eng.') as $part): ?>
                            <button type="button" class="btn btn-xs btn-outline-primary" data-title-part="<?=$part;?>" aria-pressed="false" <?=$titles_supported ? '' : 'disabled';?>><?=$part;?></button>
                            <?php endforeach; ?>
                          </div>
                          <div class="form-text">
                            <?php if($titles_supported): ?>
                            Pick one or more (e.g. Prof. + Dr.-Ing.), or type your own.
                            <?php else: ?>
                            Titles can be saved after the next system update.
                            <?php endif; ?>
                          </div>
                        </div>

                        <div class="col-md-4">
                          <label class="form-label" for="first_name">First Name *</label>
                          <input type="text" id="first_name" name="first_name" class="form-control" maxlength="100"
                                 placeholder="First Name" value="<?=$val('first_name');?>" required />
                        </div>

                        <div class="col-md-4">
                          <label class="form-label" for="last_name">Last Name *</label>
                          <input type="text" id="last_name" name="last_name" class="form-control" maxlength="100"
                                 placeholder="Last Name" value="<?=$val('last_name');?>" required />
                        </div>

                        <div class="col-12 mt-1">
                          <span class="text-muted small">Shown as:</span>
                          <strong id="namePreview" class="small"><?=htmlspecialchars($name !== '' ? $name : '—');?></strong>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="email">Email Address *</label>
                          <input type="email" id="email" name="email" class="form-control" maxlength="255"
                                 placeholder="name@example.com" value="<?=$val('email');?>" required />
                          <?php if($is_new): ?>
                          <div class="form-text">Their login. A temporary password is emailed here.</div>
                          <?php elseif($account): ?>
                          <div class="form-text">Also their login email.</div>
                          <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="phone">Phone Number</label>
                          <input type="text" id="phone" name="phone" class="form-control" maxlength="50"
                                 placeholder="+250 xxx xxx xxx" value="<?=$val('phone');?>" />
                        </div>

                        <div class="col-12">
                          <h6 class="mt-2 fw-semibold">2. Institution</h6>
                          <hr class="mt-0" />
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="partner_id">Partner/Institution *</label>
                          <?php if($admin): ?>
                          <select id="partner_id" name="partner_id" class="form-select select2" required>
                            <option value="">Select Institution</option>
                            <?php foreach($partners as $partner): ?>
                            <option value="<?=(int)$partner['partner_id'];?>" <?=set_select('partner_id', $partner['partner_id'], isset($person['partner_id']) && (int)$person['partner_id'] === (int)$partner['partner_id']);?>><?=htmlspecialchars($partner['name']);?></option>
                            <?php endforeach; ?>
                          </select>
                          <?php else: ?>
                          <input class="form-control" type="text" value="<?=htmlspecialchars((string)$this->session->userdata('fms_partner'));?>" disabled />
                          <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label" for="department">Department</label>
                          <input type="text" id="department" name="department" class="form-control" maxlength="100"
                                 placeholder="e.g. Computer Science, Engineering" value="<?=$val('department');?>" />
                        </div>

                        <div class="col-12">
                          <h6 class="mt-2 fw-semibold">3. Account</h6>
                          <hr class="mt-0" />
                        </div>

                        <?php if($is_new || $mode === 'edit_user'): ?>
                        <div class="col-md-6">
                          <label class="form-label" for="role_id">User Role *</label>
                          <select id="role_id" name="role_id" class="form-select" required <?=$self ? 'disabled' : '';?>>
                            <?php if($self): ?>
                            <option selected><?=htmlspecialchars(ucwords(str_replace('_', ' ', isset($person['role_name']) ? $person['role_name'] : '')));?></option>
                            <?php else: ?>
                            <?php foreach($roles as $role): ?>
                            <option value="<?=(int)$role['role_id'];?>" <?=set_select('role_id', $role['role_id'], (int)$role['role_id'] === $current_role);?>><?=htmlspecialchars(ucwords(str_replace('_', ' ', $role['role_name'])));?></option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                          </select>
                          <div class="form-text">
                            <?php if($self): ?>You cannot change your own role.
                            <?php elseif($this->auth_manager->is_coordinator() && !$admin): ?>Coordinators can give the Member role only.
                            <?php else: ?>The role says what they do in GREATER FMS: Member, Institution Coordinator, Admin.
                            <?php endif; ?>
                          </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                          <label class="form-label" for="status"><?=$mode === 'edit_user' ? 'Account Status' : 'Status';?> *</label>
                          <select id="status" name="status" class="form-select" required <?=$self ? 'disabled' : '';?>>
                            <?php foreach($mode === 'edit_user' ? array('active', 'inactive', 'suspended') : array('active', 'inactive') as $status): ?>
                            <option value="<?=$status;?>" <?=set_select('status', $status, $status === $current_status);?>><?=ucfirst($status);?></option>
                            <?php endforeach; ?>
                          </select>
                          <?php if($self): ?><div class="form-text">You cannot change your own status.</div><?php endif; ?>
                        </div>

                        <?php if($mode === 'edit_user'): ?>
                        <div class="col-md-6">
                          <label class="form-label" for="password">New Password (Optional)</label>
                          <input type="password" id="password" name="password" class="form-control" minlength="6"
                                 placeholder="Leave blank to keep the current password" autocomplete="new-password" />
                        </div>
                        <?php endif; ?>

                        <?php if($mode === 'edit_staff'): ?>
                        <div class="col-md-6 d-flex align-items-end">
                          <?php if($account): ?>
                          <p class="mb-2 small">
                            <i class="ti ti-user-check me-1 text-success"></i>Has a login account.
                            <a href="<?=base_url('editUser/' . (int)$account['user_id']);?>">Edit role, status or password</a>
                          </p>
                          <?php else: ?>
                          <p class="mb-2 small text-muted"><i class="ti ti-user-off me-1"></i>No login account.</p>
                          <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($is_new): ?>
                        <div class="col-12">
                          <div class="alert alert-info mb-0" role="alert">
                            <i class="ti ti-info-circle me-2"></i>
                            A temporary password is emailed to them; they choose their own at first login.
                            <ul class="mb-0 mt-2">
                              <li><strong>Super Admin:</strong> full system access (Central Coordinator at UNIPR)</li>
                              <li><strong>Admin:</strong> system configuration, sees all reports</li>
                              <li><strong>Institution Coordinator:</strong> manages staff and approves timesheets for their institution</li>
                              <li><strong>Member:</strong> submits timesheets and sees their own data</li>
                            </ul>
                          </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12">
                          <button type="submit" class="btn btn-primary">
                            <i class="ti <?=$icon;?> me-1"></i> <?=$button;?>
                          </button>
                          <a href="<?=base_url('users');?>" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i> Cancel
                          </a>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!--/ Content -->

            <!-- Footer -->
            <footer class="content-footer footer bg-footer-theme">
              <div class="container-xxl">
                <div
                  class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
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
    <!-- build:js assets/vendor/js/core.js -->
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>

    <script src="<?=base_url();?>assets/vendor/libs/hammer/hammer.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/i18n/i18n.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/typeahead-js/typeahead.js"></script>

    <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="<?=base_url();?>assets/vendor/libs/select2/select2.js"></script>

    <!-- Main JS -->
    <script src="<?=base_url();?>assets/js/main.js?v=<?=filemtime(FCPATH.'assets/js/main.js');?>"></script>

    <!-- Page JS -->
    <script>
    $(document).ready(function() {
      $('.select2').select2();
    });

    // Title: the buttons add or remove a part in the usual order (Prof. Dr.-Ing.); anything typed is kept
    (function () {
      var input = document.getElementById('title');
      var first = document.getElementById('first_name');
      var last = document.getElementById('last_name');
      var preview = document.getElementById('namePreview');
      var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-title-part]'));
      var order = ['Prof.', 'Dr.', 'Dr.-Ing.', 'Eng.'];
      var exclusive = { 'Dr.': 'Dr.-Ing.', 'Dr.-Ing.': 'Dr.' };

      function parts() { return input.value.split(/\s+/).filter(Boolean); }

      function render() {
        var current = parts();
        buttons.forEach(function (b) {
          var on = current.indexOf(b.getAttribute('data-title-part')) >= 0;
          b.classList.toggle('btn-primary', on);
          b.classList.toggle('btn-outline-primary', !on);
          b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        var name = [input.disabled ? '' : input.value, first.value, last.value]
          .map(function (v) { return v.trim(); }).filter(Boolean).join(' ');
        preview.textContent = name || '—';
      }

      buttons.forEach(function (b) {
        b.addEventListener('click', function () {
          var part = b.getAttribute('data-title-part');
          var current = parts();
          if (current.indexOf(part) >= 0) {
            current = current.filter(function (p) { return p !== part; });
          } else {
            current.push(part);
            if (exclusive[part]) { current = current.filter(function (p) { return p !== exclusive[part]; }); }
          }
          var own = current.filter(function (p) { return order.indexOf(p) < 0; });
          var known = order.filter(function (p) { return current.indexOf(p) >= 0; });
          input.value = own.concat(known).join(' ');
          render();
        });
      });
      [input, first, last].forEach(function (el) { el.addEventListener('input', render); });
      render();
    })();
    </script>

  </body>
</html>

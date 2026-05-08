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
    <!-- Helpers -->
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

              <!-- Breadcrumb -->
              <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Files /</span> Other Files
              </h4>

              <!-- Flash messages -->
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

              <!-- WP Folder Cards -->
              <div class="row g-4">
                <?php
                $wp_icons  = ['ti-chart-line','ti-users','ti-building','ti-school','ti-chalkboard','ti-speakerphone','ti-leaf'];
                $wp_colors = ['primary','success','info','warning','danger','secondary','dark'];
                foreach($work_packages as $i => $wp):
                  $icon  = $wp_icons[$i % count($wp_icons)];
                  $color = $wp_colors[$i % count($wp_colors)];
                ?>
                <div class="col-sm-6 col-xl-3">
                  <a href="<?=base_url('otherFilesWP/'.$wp['wp_id']);?>" class="text-decoration-none">
                    <div class="card h-100 border-0 shadow-sm wp-folder-card">
                      <div class="card-body d-flex flex-column align-items-center text-center p-4">
                        <div class="avatar avatar-xl mb-3">
                          <span class="avatar-initial rounded-circle bg-label-<?=$color;?>">
                            <i class="ti <?=$icon;?> fs-3"></i>
                          </span>
                        </div>
                        <h6 class="fw-semibold mb-1"><?=$wp['code'];?></h6>
                        <p class="small text-muted mb-3"><?=$wp['name'];?></p>
                        <span class="badge bg-<?=$color;?> rounded-pill">
                          <?=$wp['file_count'];?> file<?=($wp['file_count'] != 1) ? 's' : '';?>
                        </span>
                      </div>
                    </div>
                  </a>
                </div>
                <?php endforeach; ?>
              </div>

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
    <!-- Drag Target -->
    <div class="drag-target"></div>

    <!-- Vendor JS -->
    <script src="<?=base_url();?>assets/vendor/libs/jquery/jquery.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/popper/popper.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/bootstrap.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?=base_url();?>assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="<?=base_url();?>assets/vendor/js/menu.js"></script>
    <script src="<?=base_url();?>assets/js/main.js"></script>

    <style>
    .wp-folder-card { transition: transform .15s, box-shadow .15s; }
    .wp-folder-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    </style>
  </body>
</html>

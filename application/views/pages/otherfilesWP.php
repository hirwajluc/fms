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
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="<?=base_url();?>assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
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
                <span class="text-muted fw-light">
                  <a href="<?=base_url('otherFiles');?>" class="text-muted">Other Files</a> /
                </span>
                <?=$wp['code'];?> – <?=$wp['name'];?>
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

              <!-- Header card: WP info + Upload button -->
              <div class="card mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                  <div>
                    <h5 class="mb-1 fw-semibold"><?=$wp['code'];?> – <?=$wp['name'];?></h5>
                    <p class="text-muted mb-0 small"><?=$wp['description'];?></p>
                  </div>
                  <button type="button" class="btn btn-primary flex-shrink-0"
                          data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="ti ti-upload me-1"></i> Upload New File
                  </button>
                </div>
              </div>

              <!-- Files table -->
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="ti ti-files me-1"></i> Files in <?=$wp['code'];?>
                  </h5>
                  <span class="badge bg-primary rounded-pill"><?=count($files);?> file<?=(count($files) != 1)?'s':'';?></span>
                </div>
                <div class="table-responsive">
                  <table id="filesTable" class="table table-hover">
                    <thead class="table-light">
                      <tr>
                        <th style="width:40px">#</th>
                        <th>File Name / Description</th>
                        <?php if($this->auth_manager->is_super_admin()): ?>
                        <th>Partner</th>
                        <th>Uploaded By</th>
                        <?php endif; ?>
                        <th>Latest</th>
                        <th>Stored Filename</th>
                        <th>Last Updated</th>
                        <th style="width:160px">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach($files as $i => $f): ?>
                      <tr>
                        <td class="text-muted small"><?=($i+1);?></td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-file-description text-primary fs-5"></i>
                            <span class="fw-medium"><?=htmlspecialchars($f['display_name']);?></span>
                          </div>
                          <?php if(!empty($f['description'])): ?>
                          <div class="text-muted small mt-1"><?=htmlspecialchars($f['description']);?></div>
                          <?php endif; ?>
                        </td>
                        <?php if($this->auth_manager->is_super_admin()): ?>
                        <td><span class="badge bg-label-info"><?=htmlspecialchars($f['partner_short_name']);?></span></td>
                        <td class="small"><?=htmlspecialchars($f['uploader_name'] ?? '—');?></td>
                        <?php endif; ?>
                        <td><span class="badge bg-success">v<?=$f['latest_version'];?></span></td>
                        <td class="small text-muted font-monospace"><?=htmlspecialchars($f['latest_stored_name'] ?? '—');?></td>
                        <td class="small text-muted"><?=date('d M Y H:i', strtotime($f['latest_upload_at']));?></td>
                        <td>
                          <div class="btn-group" role="group">
                            <?php if(!empty($f['latest_version_id'])): ?>
                            <a href="<?=base_url('downloadOtherFileVersion/'.$f['latest_version_id']);?>"
                               class="btn btn-sm btn-primary" title="Download latest">
                              <i class="ti ti-download"></i>
                            </a>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-sm btn-info btn-versions"
                                    data-file-id="<?=$f['file_id'];?>"
                                    data-file-name="<?=htmlspecialchars($f['display_name'], ENT_QUOTES);?>"
                                    title="Version history">
                              <i class="ti ti-history"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-warning btn-add-version"
                                    data-file-id="<?=$f['file_id'];?>"
                                    data-file-name="<?=htmlspecialchars($f['display_name'], ENT_QUOTES);?>"
                                    title="Upload new version">
                              <i class="ti ti-versions"></i>
                            </button>
                            <!-- Comments -->
                            <button type="button"
                                    class="btn btn-sm btn-secondary btn-comments"
                                    data-file-id="<?=$f['file_id'];?>"
                                    data-file-name="<?=htmlspecialchars($f['display_name'], ENT_QUOTES);?>"
                                    title="Comments">
                              <i class="ti ti-message-circle"></i>
                            </button>
                            <?php if($this->auth_manager->is_super_admin()): ?>
                            <button type="button"
                                    class="btn btn-sm btn-danger btn-delete-file"
                                    data-file-id="<?=$f['file_id'];?>"
                                    data-file-name="<?=htmlspecialchars($f['display_name'], ENT_QUOTES);?>"
                                    title="Delete file">
                              <i class="ti ti-trash"></i>
                            </button>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- /Files table -->

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

    <!-- =====================================================
         MODAL: Upload New File
    ====================================================== -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-upload me-2 text-primary"></i>Upload File to <?=$wp['code'];?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="uploadForm" enctype="multipart/form-data">
              <input type="hidden" name="wp_id"   value="<?=$wp['wp_id'];?>">
              <input type="hidden" name="file_id" value="0">

              <div class="mb-3">
                <label class="form-label" for="display_name">File Label <span class="text-danger">*</span></label>
                <input type="text" id="display_name" name="display_name"
                       class="form-control" placeholder="e.g. RP Progress Report Q1" required />
                <div class="form-text">A short human-readable label for this file.</div>
              </div>

              <div class="mb-3">
                <label class="form-label" for="upload_description">Description <span class="text-danger">*</span></label>
                <textarea id="upload_description" name="description" rows="3"
                          class="form-control" placeholder="Brief description of the file content…" required></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label" for="upload_file">File <span class="text-danger">*</span></label>
                <input type="file" id="upload_file" name="upload_file" class="form-control" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.png,.jpg,.jpeg" />
                <div class="form-text">Allowed: PDF, Word, Excel, PowerPoint, Text, ZIP, Images. Max 20 MB.</div>
              </div>

              <div id="uploadProgress" class="d-none mb-2">
                <div class="progress" style="height:22px; border-radius:4px;">
                  <div id="uploadProgressBar" class="progress-bar progress-bar-striped bg-primary"
                       role="progressbar" style="width:0%; transition:width .2s ease;"
                       aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <small id="uploadProgressLabel" class="text-muted">Uploading…</small>
              </div>
              <div id="uploadResult"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="btnUpload">
              <i class="ti ti-upload me-1"></i> Upload
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: Add New Version
    ====================================================== -->
    <div class="modal fade" id="addVersionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-versions me-2 text-warning"></i>Upload New Version
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2" role="alert">
              <i class="ti ti-info-circle"></i>
              <span>Adding a new version for: <strong id="versionFileName"></strong></span>
            </div>
            <form id="addVersionForm" enctype="multipart/form-data">
              <input type="hidden" name="wp_id"   value="<?=$wp['wp_id'];?>">
              <input type="hidden" name="file_id" id="versionFileId" value="">

              <div class="mb-3">
                <label class="form-label" for="version_description">Change Notes <span class="text-danger">*</span></label>
                <textarea id="version_description" name="description" rows="3"
                          class="form-control" placeholder="What changed in this version?" required></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label" for="version_file">Revised File <span class="text-danger">*</span></label>
                <input type="file" id="version_file" name="upload_file" class="form-control" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.png,.jpg,.jpeg" />
              </div>

              <div id="versionProgress" class="d-none mb-2">
                <div class="progress" style="height:22px; border-radius:4px;">
                  <div id="versionProgressBar" class="progress-bar progress-bar-striped bg-warning"
                       role="progressbar" style="width:0%; transition:width .2s ease;"
                       aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                </div>
                <small id="versionProgressLabel" class="text-muted">Uploading…</small>
              </div>
              <div id="versionResult"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-warning" id="btnAddVersion">
              <i class="ti ti-versions me-1"></i> Upload Version
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: Version History
    ====================================================== -->
    <div class="modal fade" id="versionsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width:fit-content;min-width:900px;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-history me-2 text-info"></i>Version History – <span id="versionsFileName"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
            <div id="versionsLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="mt-2 text-muted small">Loading versions…</p>
            </div>
            <div id="versionsContent" class="d-none">
              <table class="table table-hover mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Version</th>
                    <th>Stored Filename</th>
                    <th>Size</th>
                    <th>Change Notes</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Download</th>
                  </tr>
                </thead>
                <tbody id="versionsList"></tbody>
              </table>
            </div>
            <div id="versionsError" class="d-none alert alert-danger m-3"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: Comments
    ====================================================== -->
    <div class="modal fade" id="commentsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-message-circle me-2 text-secondary"></i>Comments – <span id="commentsFileName"></span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">

            <!-- Comment thread -->
            <div id="commentsLoading" class="text-center py-4">
              <div class="spinner-border text-secondary spinner-border-sm" role="status"></div>
              <span class="ms-2 text-muted small">Loading comments…</span>
            </div>
            <div id="commentsList" class="mb-3" style="max-height:340px;overflow-y:auto;"></div>
            <div id="commentsEmpty" class="d-none text-center text-muted py-3 small">
              <i class="ti ti-message-off fs-4 d-block mb-1 opacity-50"></i>No comments yet.
            </div>

            <!-- Add comment (Super Admin only) -->
            <?php if($this->auth_manager->is_super_admin()): ?>
            <hr class="my-3">
            <div>
              <label class="form-label fw-medium small">Add a comment</label>
              <div class="d-flex gap-2">
                <textarea id="newCommentText" class="form-control form-control-sm" rows="2"
                          placeholder="Write your comment to the coordinator…"></textarea>
                <button class="btn btn-primary btn-sm align-self-end flex-shrink-0" id="btnPostComment">
                  <i class="ti ti-send me-1"></i>Post
                </button>
              </div>
              <div id="commentPostResult" class="mt-2"></div>
            </div>
            <?php else: ?>
            <div class="alert alert-light border small mb-0 mt-2">
              <i class="ti ti-info-circle me-1"></i>Comments are added by the Super Admin as feedback on your uploaded files.
            </div>
            <?php endif; ?>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- =====================================================
         MODAL: Delete confirmation
    ====================================================== -->
    <div class="modal fade" id="deleteFileModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <i class="ti ti-trash text-danger fs-1 mb-2 d-block"></i>
            <h5 class="mb-1">Delete File?</h5>
            <p class="text-muted small mb-0">
              Permanently delete <strong id="deleteFileName"></strong>
              and <span class="text-danger fw-semibold">ALL its versions</span>. This cannot be undone.
            </p>
          </div>
          <div class="modal-footer d-flex justify-content-center border-0 pt-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <a id="deleteFileBtn" href="#" class="btn btn-danger">Delete All Versions</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Vendor JS -->
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

      // DataTable – manual single init, no auto-init classes used
      $('#filesTable').DataTable({
        pageLength: 25,
        order: [],
        columnDefs: [{ orderable: false, targets: [-1] }],
        language: {
          emptyTable:
            '<div class="text-center py-4 text-muted">' +
            '<i class="ti ti-folder-open fs-1 d-block mb-2 opacity-50"></i>' +
            'No files uploaded yet in this Work Package.<br>' +
            '<button type="button" class="btn btn-sm btn-primary mt-3" ' +
            'data-bs-toggle="modal" data-bs-target="#uploadModal">' +
            '<i class="ti ti-upload me-1"></i> Upload the first file</button></div>',
          zeroRecords: 'No files match your search.'
        }
      });

      // -------------------------------------------------------
      // Helper: inline result
      // -------------------------------------------------------
      function showResult($el, type, msg){
        $el.removeClass('alert-success alert-danger')
           .addClass('alert alert-' + type)
           .html(msg)
           .show();
      }

      // -------------------------------------------------------
      // Upload new file
      // -------------------------------------------------------
      $('#btnUpload').on('click', function(){
        var form = document.getElementById('uploadForm');
        if(!form.checkValidity()){ form.reportValidity(); return; }
        var fd = new FormData(form);
        $('#uploadProgressBar').css('width','0%').attr('aria-valuenow',0).text('0%').removeClass('progress-bar-animated');
        $('#uploadProgressLabel').text('Uploading…');
        $('#uploadProgress').removeClass('d-none');
        $('#uploadResult').html('');
        $('#btnUpload').prop('disabled', true);

        $.ajax({
          url: '<?=base_url('uploadOtherFile');?>',
          type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
          xhr: function(){
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e){
              if(e.lengthComputable){
                var pct = Math.round(e.loaded / e.total * 100);
                $('#uploadProgressBar').css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');
                if(pct === 100){
                  $('#uploadProgressBar').addClass('progress-bar-animated');
                  $('#uploadProgressLabel').text('Processing…');
                }
              }
            }, false);
            return xhr;
          },
          success: function(res){
            $('#uploadProgress').addClass('d-none');
            $('#btnUpload').prop('disabled', false);
            if(res.success){
              showResult($('#uploadResult'), 'success', '<i class="ti ti-check me-1"></i>' + res.message);
              setTimeout(function(){ location.reload(); }, 1500);
            } else {
              showResult($('#uploadResult'), 'danger', '<i class="ti ti-alert-circle me-1"></i>' + res.message);
            }
          },
          error: function(){
            $('#uploadProgress').addClass('d-none');
            $('#btnUpload').prop('disabled', false);
            showResult($('#uploadResult'), 'danger', 'Server error. Please try again.');
          }
        });
      });

      $('#uploadModal').on('hidden.bs.modal', function(){
        document.getElementById('uploadForm').reset();
        $('#uploadResult').html('');
        $('#uploadProgress').addClass('d-none');
        $('#btnUpload').prop('disabled', false);
      });

      // -------------------------------------------------------
      // Add new version
      // -------------------------------------------------------
      $(document).on('click', '.btn-add-version', function(){
        var fileId   = $(this).data('file-id');
        var fileName = $(this).data('file-name');
        $('#versionFileId').val(fileId);
        $('#versionFileName').text(fileName);
        document.getElementById('addVersionForm').reset();
        $('#versionFileId').val(fileId);
        $('#versionResult').html('');
        var modal = new bootstrap.Modal(document.getElementById('addVersionModal'));
        modal.show();
      });

      $('#btnAddVersion').on('click', function(){
        var form = document.getElementById('addVersionForm');
        if(!form.checkValidity()){ form.reportValidity(); return; }
        var fd = new FormData(form);
        fd.set('file_id', $('#versionFileId').val());
        $('#versionProgressBar').css('width','0%').attr('aria-valuenow',0).text('0%').removeClass('progress-bar-animated');
        $('#versionProgressLabel').text('Uploading…');
        $('#versionProgress').removeClass('d-none');
        $('#versionResult').html('');
        $('#btnAddVersion').prop('disabled', true);

        $.ajax({
          url: '<?=base_url('uploadOtherFile');?>',
          type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
          xhr: function(){
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e){
              if(e.lengthComputable){
                var pct = Math.round(e.loaded / e.total * 100);
                $('#versionProgressBar').css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');
                if(pct === 100){
                  $('#versionProgressBar').addClass('progress-bar-animated');
                  $('#versionProgressLabel').text('Processing…');
                }
              }
            }, false);
            return xhr;
          },
          success: function(res){
            $('#versionProgress').addClass('d-none');
            $('#btnAddVersion').prop('disabled', false);
            if(res.success){
              showResult($('#versionResult'), 'success', '<i class="ti ti-check me-1"></i>' + res.message);
              setTimeout(function(){ location.reload(); }, 1500);
            } else {
              showResult($('#versionResult'), 'danger', '<i class="ti ti-alert-circle me-1"></i>' + res.message);
            }
          },
          error: function(){
            $('#versionProgress').addClass('d-none');
            $('#btnAddVersion').prop('disabled', false);
            showResult($('#versionResult'), 'danger', 'Server error. Please try again.');
          }
        });
      });

      // -------------------------------------------------------
      // Version history
      // -------------------------------------------------------
      $(document).on('click', '.btn-versions', function(){
        var fileId   = $(this).data('file-id');
        var fileName = $(this).data('file-name');

        $('#versionsFileName').text(fileName);
        $('#versionsLoading').removeClass('d-none');
        $('#versionsContent').addClass('d-none');
        $('#versionsError').addClass('d-none');

        var modal = new bootstrap.Modal(document.getElementById('versionsModal'));
        modal.show();

        $.getJSON('<?=base_url('getFileVersions/');?>' + fileId, function(res){
          $('#versionsLoading').addClass('d-none');
          if(res.success){
            var html = '';
            if(res.versions.length === 0){
              html = '<tr><td colspan="7" class="text-center text-muted py-4">No versions found.</td></tr>';
            } else {
              $.each(res.versions, function(i, v){
                var isLatest = (i === 0);
                var sizeKB   = v.file_size ? (v.file_size / 1024).toFixed(1) + ' KB' : '—';
                var badge    = isLatest ? '<span class="badge bg-success ms-1">latest</span>' : '';
                html += '<tr' + (isLatest ? ' class="table-success"' : '') + '>'
                  + '<td><strong>v' + v.version_number + '</strong>' + badge + '</td>'
                  + '<td class="font-monospace small">' + esc(v.stored_name) + '</td>'
                  + '<td class="small">' + sizeKB + '</td>'
                  + '<td class="small">' + esc(v.description || '—') + '</td>'
                  + '<td class="small">' + esc(v.uploader_name || '—') + '</td>'
                  + '<td class="small text-nowrap">' + fmtDate(v.created_at) + '</td>'
                  + '<td><a href="<?=base_url('downloadOtherFileVersion/');?>' + v.version_id
                  + '" class="btn btn-sm btn-primary" title="Download v' + v.version_number + '">'
                  + '<i class="ti ti-download"></i></a></td>'
                  + '</tr>';
              });
            }
            $('#versionsList').html(html);
            $('#versionsContent').removeClass('d-none');
          } else {
            $('#versionsError').text(res.message || 'Failed to load versions.').removeClass('d-none');
          }
        }).fail(function(){
          $('#versionsLoading').addClass('d-none');
          $('#versionsError').text('Server error loading versions.').removeClass('d-none');
        });
      });

      // -------------------------------------------------------
      // Comments
      // -------------------------------------------------------
      var currentCommentFileId = null;

      $(document).on('click', '.btn-comments', function(){
        currentCommentFileId = $(this).data('file-id');
        var fileName = $(this).data('file-name');
        $('#commentsFileName').text(fileName);
        $('#commentsList').html('');
        $('#commentsEmpty').addClass('d-none');
        $('#commentsLoading').removeClass('d-none');
        $('#newCommentText').val('');
        $('#commentPostResult').html('');
        var modal = new bootstrap.Modal(document.getElementById('commentsModal'));
        modal.show();
        loadComments(currentCommentFileId);
      });

      function loadComments(fileId){
        $.getJSON('<?=base_url('getFileComments/');?>' + fileId, function(res){
          $('#commentsLoading').addClass('d-none');
          if(!res.success){ $('#commentsEmpty').removeClass('d-none'); return; }
          if(res.comments.length === 0){
            $('#commentsEmpty').removeClass('d-none');
          } else {
            var html = '';
            $.each(res.comments, function(i, c){
              var roleLabel = c.role_name ? c.role_name.replace(/_/g,' ').replace(/\b\w/g,function(l){return l.toUpperCase();}) : '';
              var isSuperAdmin = (c.role_name === 'super_admin');
              html += '<div class="d-flex gap-3 mb-3">'
                + '<div class="flex-shrink-0">'
                +   '<span class="avatar avatar-sm rounded-circle bg-label-' + (isSuperAdmin ? 'danger' : 'primary') + '">'
                +     '<i class="ti ' + (isSuperAdmin ? 'ti-shield-check' : 'ti-user') + ' fs-6"></i>'
                +   '</span>'
                + '</div>'
                + '<div class="flex-grow-1">'
                +   '<div class="d-flex align-items-center gap-2 mb-1">'
                +     '<span class="fw-semibold small">' + esc(c.commenter_name) + '</span>'
                +     '<span class="badge bg-label-' + (isSuperAdmin ? 'danger' : 'secondary') + ' small">' + esc(roleLabel) + '</span>'
                +     '<span class="text-muted small ms-auto">' + fmtDate(c.created_at) + '</span>'
                +     <?php if($this->auth_manager->is_super_admin()): ?>
                      (isSuperAdmin
                        ? '<button class="btn btn-xs btn-link text-danger p-0 ms-2 btn-del-comment" data-comment-id="' + c.comment_id + '" title="Delete"><i class="ti ti-x small"></i></button>'
                        : '')
                      <?php else: ?> ''
                      <?php endif; ?>
                +   '</div>'
                +   '<div class="p-2 rounded" style="background:#f8f9fa;font-size:.875rem;">' + esc(c.comment) + '</div>'
                + '</div>'
                + '</div>';
            });
            $('#commentsList').html(html);
          }
        }).fail(function(){
          $('#commentsLoading').addClass('d-none');
          $('#commentsEmpty').removeClass('d-none');
        });
      }

      <?php if($this->auth_manager->is_super_admin()): ?>
      // Post comment
      $('#btnPostComment').on('click', function(){
        var text = $.trim($('#newCommentText').val());
        if(!text) return;
        $('#btnPostComment').prop('disabled', true);
        $.post('<?=base_url('addFileComment');?>', { file_id: currentCommentFileId, comment: text }, function(res){
          $('#btnPostComment').prop('disabled', false);
          if(res.success){
            $('#newCommentText').val('');
            $('#commentPostResult').html('');
            $('#commentsLoading').addClass('d-none');
            $('#commentsEmpty').addClass('d-none');
            // Re-render comments from response
            var html = '';
            $.each(res.comments, function(i, c){
              var roleLabel = c.role_name ? c.role_name.replace(/_/g,' ').replace(/\b\w/g,function(l){return l.toUpperCase();}) : '';
              var isSuperAdmin = (c.role_name === 'super_admin');
              html += '<div class="d-flex gap-3 mb-3">'
                + '<div class="flex-shrink-0"><span class="avatar avatar-sm rounded-circle bg-label-' + (isSuperAdmin?'danger':'primary') + '"><i class="ti ' + (isSuperAdmin?'ti-shield-check':'ti-user') + ' fs-6"></i></span></div>'
                + '<div class="flex-grow-1">'
                +   '<div class="d-flex align-items-center gap-2 mb-1">'
                +     '<span class="fw-semibold small">' + esc(c.commenter_name) + '</span>'
                +     '<span class="badge bg-label-' + (isSuperAdmin?'danger':'secondary') + ' small">' + esc(roleLabel) + '</span>'
                +     '<span class="text-muted small ms-auto">' + fmtDate(c.created_at) + '</span>'
                +     (isSuperAdmin ? '<button class="btn btn-xs btn-link text-danger p-0 ms-2 btn-del-comment" data-comment-id="'+c.comment_id+'" title="Delete"><i class="ti ti-x small"></i></button>' : '')
                +   '</div>'
                +   '<div class="p-2 rounded" style="background:#f8f9fa;font-size:.875rem;">' + esc(c.comment) + '</div>'
                + '</div></div>';
            });
            $('#commentsList').html(html);
            if(res.comments.length === 0) $('#commentsEmpty').removeClass('d-none');
          } else {
            $('#commentPostResult').html('<div class="alert alert-danger py-1 small">' + esc(res.message) + '</div>');
          }
        }, 'json').fail(function(){
          $('#btnPostComment').prop('disabled', false);
          $('#commentPostResult').html('<div class="alert alert-danger py-1 small">Server error.</div>');
        });
      });

      // Delete comment
      $(document).on('click', '.btn-del-comment', function(){
        var cid = $(this).data('comment-id');
        if(!confirm('Delete this comment?')) return;
        var $row = $(this).closest('.d-flex.gap-3');
        $.post('<?=base_url('deleteFileComment/');?>' + cid, {}, function(res){
          if(res.success){
            $row.remove();
            if($('#commentsList .d-flex.gap-3').length === 0) $('#commentsEmpty').removeClass('d-none');
          }
        }, 'json');
      });
      <?php endif; ?>

      // -------------------------------------------------------
      // Delete file
      // -------------------------------------------------------
      $(document).on('click', '.btn-delete-file', function(){
        var fileId   = $(this).data('file-id');
        var fileName = $(this).data('file-name');
        $('#deleteFileName').text('"' + fileName + '"');
        $('#deleteFileBtn').attr('href', '<?=base_url('deleteOtherFile/');?>' + fileId);
        var modal = new bootstrap.Modal(document.getElementById('deleteFileModal'));
        modal.show();
      });

      // -------------------------------------------------------
      // Utils
      // -------------------------------------------------------
      function esc(str){ return $('<div>').text(str).html(); }

      function fmtDate(str){
        if(!str) return '—';
        var d = new Date(str.replace(' ','T'));
        var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getDate()+' '+m[d.getMonth()]+' '+d.getFullYear()+' '
          +String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');
      }
    });
    </script>
  </body>
</html>

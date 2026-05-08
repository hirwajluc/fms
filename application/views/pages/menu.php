<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal menu bg-menu-theme flex-grow-0">
              <div class="container-xxl d-flex h-100">
                <ul class="menu-inner">
                  <!-- ==================== HOME ==================== -->
                  <!-- Available to: All Roles -->
                  <li class="menu-item <?=($this->router->fetch_method()=='index')?'active':'';?>">
                    <a href="<?=base_url();?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-smart-home"></i>
                      <div data-i18n="Home">Home</div>
                    </a>
                  </li>

                  <!-- ==================== TIMESHEETS ==================== -->
                  <!-- Available to: All Roles (Staff, Coordinator, Admin, Super Admin) -->
                  <li class="menu-item <?=($this->router->fetch_method()=='timesheets' || $this->router->fetch_method()=='newTimesheet' || $this->router->fetch_method()=='viewTimesheet' || $this->router->fetch_method()=='editTimesheet')?'active':'';?>">
                    <a href="<?=base_url('timesheets');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-clock"></i>
                      <div data-i18n="Timesheets">Timesheets</div>
                    </a>
                  </li>

                  <!-- ==================== EXPENSES & REPORTS ==================== -->
                  <!-- Coordinators upload, Super Admin approves -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-item <?=($this->router->fetch_method()=='expenses' || $this->router->fetch_method()=='newExpense' || $this->router->fetch_method()=='saveExpense' || $this->router->fetch_method()=='generateReport')?'active':'';?>">
                    <a href="<?=base_url('expenses');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-receipt"></i>
                      <div data-i18n="Expenses">Expenses</div>
                    </a>
                  </li>
                  <?php endif; ?>


                  <!-- ==================== OTHER FILES ==================== -->
                  <!-- Available to: Super Admin and Local Coordinators -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ti ti-folder-open"></i>
                      <div data-i18n="OtherFiles">Other Files</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item <?=($this->router->fetch_method()=='otherFiles')?'active':'';?>">
                        <a href="<?=base_url('otherFiles');?>" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-layout-grid"></i>
                          <div data-i18n="AllWPs">All Work Packages</div>
                        </a>
                      </li>
                      <?php
                      $wps = [
                        1 => 'WP1 - Management and coordination',
                        2 => 'WP2 - Collaboration design',
                        3 => 'WP3 - Infrastructures',
                        4 => 'WP4 - Curricula design',
                        5 => 'WP5 - Training and coaching',
                        6 => 'WP6 - Transfer methodologies',
                        7 => 'WP7 - Impact and dissemination',
                      ];
                      foreach($wps as $wpid => $wplabel): ?>
                      <li class="menu-item">
                        <a href="<?=base_url('otherFilesWP/'.$wpid);?>" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-folder"></i>
                          <div><?=$wplabel;?></div>
                        </a>
                      </li>
                      <?php endforeach; ?>
                    </ul>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== ADMINISTRATION SECTION ==================== -->
                  <!-- Divider for organization -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Administration</span>
                  </li>

                  <!-- Users Management - Available to Coordinators, Admin, Super Admin -->
                  <li class="menu-item <?=($this->router->fetch_method()=='users' || $this->router->fetch_method()=='newUser' || $this->router->fetch_method()=='editUser' || $this->router->fetch_method()=='staff' || $this->router->fetch_method()=='newStaff')?'active':'';?>">
                    <a href="<?=base_url('users');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-users"></i>
                      <div data-i18n="Users">Users</div>
                    </a>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== SETTINGS SECTION ==================== -->
                  <!-- Settings menu visible to Coordinators, Admins, and Super Admins -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-header small text-uppercase">
                    <span class="menu-header-text"><?=($this->auth_manager->is_coordinator() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_super_admin()) ? 'Settings' : 'Admin';?></span>
                  </li>

                  <!-- Settings/Configuration -->
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ti ti-settings"></i>
                      <div data-i18n="Settings">Settings</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item <?=($this->router->fetch_method()=='reportSignatures')?'active':'';?>">
                        <a href="<?=base_url('reportSignatures');?>" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="ReportSignatures">Report Signatures</div>
                        </a>
                      </li>
                      <?php if($this->auth_manager->is_super_admin()): ?>
                      <li class="menu-item <?=($this->router->fetch_method()=='forexExchange')?'active':'';?>">
                        <a href="<?=base_url('forexExchange');?>" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-currency-euro"></i>
                          <div data-i18n="ForexExchange">Forex Exchange</div>
                        </a>
                      </li>
                      <?php endif; ?>
                    </ul>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>
            </aside>
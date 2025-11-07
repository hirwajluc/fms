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

                  <!-- ==================== EXPENSES ==================== -->
                  <!-- Available to: Coordinator, Admin, Super Admin -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-item <?=($this->router->fetch_method()=='expenses' || $this->router->fetch_method()=='newExpense' || $this->router->fetch_method()=='saveExpense')?'active':'';?>">
                    <a href="<?=base_url('expenses');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-receipt"></i>
                      <div data-i18n="Expenses">Expenses</div>
                    </a>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== MONTHLY REPORTS ==================== -->
                  <!-- Available to: Coordinator, Admin, Super Admin (V2 with file attachments) -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-item <?=($this->router->fetch_method()=='monthlyReports' || $this->router->fetch_method()=='viewMonthlyReport' || $this->router->fetch_method()=='generateMonthlyReport')?'active':'';?>">
                    <a href="<?=base_url('monthlyReports');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-file-text"></i>
                      <div data-i18n="MonthlyReports">Monthly Reports</div>
                    </a>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== WORK PACKAGES / EVENTS ==================== -->
                  <!-- Available to: Coordinator, Admin, Super Admin -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ti ti-folder"></i>
                      <div data-i18n="WorkPackages">Work Packages</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP1">WP 1 - Management</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP2">WP 2 - Collaboration</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP3">WP 3 - Implementation</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP4">WP 4 - Support</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP5">WP 5 - Training</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP6">WP 6 - Monitoring</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="WP7">WP 7 - Evaluation</div>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== ADMINISTRATION SECTION ==================== -->
                  <!-- Divider for organization -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin() || $this->auth_manager->is_coordinator()): ?>
                  <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Administration</span>
                  </li>

                  <!-- Users Management -->
                  <li class="menu-item <?=($this->router->fetch_method()=='users' || $this->router->fetch_method()=='newUser' || $this->router->fetch_method()=='editUser')?'active':'';?>">
                    <a href="<?=base_url('users');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-users"></i>
                      <div data-i18n="Users">Users</div>
                    </a>
                  </li>

                  <!-- Staff Management -->
                  <li class="menu-item <?=($this->router->fetch_method()=='staff' || $this->router->fetch_method()=='newStaff')?'active':'';?>">
                    <a href="<?=base_url('staff');?>" class="menu-link">
                      <i class="menu-icon tf-icons ti ti-user-check"></i>
                      <div data-i18n="Staff">Staff</div>
                    </a>
                  </li>
                  <?php endif; ?>

                  <!-- ==================== ADMIN ONLY SECTION ==================== -->
                  <!-- Only for Super Admin and Admin -->
                  <?php if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()): ?>
                  <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Admin</span>
                  </li>

                  <!-- Settings/Configuration (placeholder for future use) -->
                  <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                      <i class="menu-icon tf-icons ti ti-settings"></i>
                      <div data-i18n="Settings">Settings</div>
                    </a>
                    <ul class="menu-sub">
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="SystemSettings">System Settings</div>
                        </a>
                      </li>
                      <li class="menu-item">
                        <a href="javascript:void(0);" class="menu-link">
                          <i class="menu-icon tf-icons ti ti-arrow-right"></i>
                          <div data-i18n="Reports">Reports & Analytics</div>
                        </a>
                      </li>
                    </ul>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>
            </aside>
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fms extends CI_Controller {

	/**
	 * Index Page for this controller.
	 */
	protected $data = array();

	public function __construct(){
		parent::__construct();
		// Use Auth Manager for authentication
		$this->auth_manager->require_login();
	}

	public function index(){
		$this->data['title'] = 'FMS - Dashboard';

		$user = $this->auth_manager->get_current_user();
		$this->data['user'] = $user;
		$this->data['role'] = $user['role'];

		// Human-readable role label (no underscores)
		$raw_role = $this->session->userdata('fms_role_name') ?: $user['role'];
		$this->data['role_label'] = ucwords(str_replace('_', ' ', $raw_role));

		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['total_users']      = count($this->fmsm_enhanced->get_all_users());
			$this->data['total_partners']   = count($this->fmsm_enhanced->get_all_partners());
			$this->data['total_expenses']   = count($this->fmsm_enhanced->get_all_expenses());
			$this->data['total_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets());
			$this->data['total_other_files']= $this->fmsm_enhanced->count_other_files();

			// Chart data
			$this->data['chart_expense_status']    = $this->fmsm_enhanced->get_expenses_by_status();
			$this->data['chart_expense_by_partner']= $this->fmsm_enhanced->get_expenses_by_partner();
			$this->data['chart_files_by_wp']       = $this->fmsm_enhanced->get_other_files_by_wp();
			$this->data['chart_timesheet_status']  = $this->fmsm_enhanced->get_timesheets_by_status();

		} elseif($this->auth_manager->is_coordinator()){
			$partner_id = $this->session->userdata('fms_partner_id');
			$user_id    = $this->session->userdata('fms_user_id');
			$this->data['total_users']        = count($this->fmsm_enhanced->get_all_users($partner_id));
			$this->data['total_expenses']     = count($this->fmsm_enhanced->get_all_expenses($partner_id));
			$this->data['total_timesheets']   = count($this->fmsm_enhanced->get_all_timesheets(NULL, $partner_id));
			$this->data['pending_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets(NULL, $partner_id, 'submitted'));
			$this->data['total_other_files']  = $this->fmsm_enhanced->count_other_files($partner_id, $user_id);

			// Chart data
			$this->data['chart_timesheet_status'] = $this->fmsm_enhanced->get_timesheets_by_status($partner_id);
			$this->data['chart_expense_by_wp']    = $this->fmsm_enhanced->get_expenses_by_wp($partner_id);
			$this->data['chart_files_by_wp']      = $this->fmsm_enhanced->get_other_files_by_wp($partner_id, $user_id);

		} else {
			$user_id = $this->session->userdata('fms_user_id');
			$this->data['total_timesheets']   = count($this->fmsm_enhanced->get_all_timesheets($user_id));
			$this->data['pending_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets($user_id, NULL, 'submitted'));
			$this->data['approved_timesheets']= count($this->fmsm_enhanced->get_all_timesheets($user_id, NULL, 'approved'));
			$this->data['chart_timesheet_status'] = $this->fmsm_enhanced->get_timesheets_by_status(NULL, $user_id);
		}

		$this->load->view('pages/dashboard', $this->data);
	}

	public function expenses(){
		// Only coordinators and admins can view expenses
		if(!$this->auth_manager->can_upload_expenses() && !$this->auth_manager->is_admin()){
			show_error('Access Denied: You do not have permission to view expenses.', 403);
		}

		$this->data["title"] = "FMS - Expenses";

		// Get expenses based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['expenses'] = $this->fmsm_enhanced->get_all_expenses();
		} else {
			$partner_id = $this->session->userdata('fms_partner_id');
			$this->data['expenses'] = $this->fmsm_enhanced->get_all_expenses($partner_id);
		}

		$this->data['all_partners'] = $this->fmsm_enhanced->get_all_partners();
		$this->load->view('pages/expenses', $this->data);
	}

	public function timesheets(){
		$this->data["title"] = "FMS - TimeSheets";

		// Get timesheets based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['timesheets'] = $this->fmsm_enhanced->get_all_timesheets();
		} else if($this->auth_manager->is_coordinator()){
			$partner_id = $this->session->userdata('fms_partner_id');
			$this->data['timesheets'] = $this->fmsm_enhanced->get_all_timesheets(NULL, $partner_id);
		} else {
			$user_id = $this->session->userdata('fms_user_id');
			$this->data['timesheets'] = $this->fmsm_enhanced->get_all_timesheets($user_id);
		}

		$this->data['all_partners'] = $this->fmsm_enhanced->get_all_partners();
		$this->load->view('pages/timesheets', $this->data);
	}

	public function newTimesheet(){
		$this->data["title"] = "FMS - New Timesheet";

		// Get current user's partner information
		$user_id = $this->session->userdata('fms_user_id');
		$user = $this->fmsm_enhanced->get_user_by_id($user_id);

		if($user){
			$this->data['user'] = $user;
			$this->data['partner_id'] = $user['partner_id'];
			$this->data['partner_name'] = $user['partner_name'];
		}

		$this->load->view('pages/newtimesheet', $this->data);
	}

	public function parseTimesheetExcel(){
		// Only accept AJAX requests
		if(!$this->input->is_ajax_request()){
			show_error('Invalid request', 403);
		}

		// Check if file was uploaded
		if(empty($_FILES['excel_file']['name'])){
			echo json_encode(array('success' => false, 'message' => 'No file uploaded'));
			return;
		}

		// Validate file upload
		if($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK){
			log_message('error', 'Excel file upload error: ' . $_FILES['excel_file']['error']);
			echo json_encode(array(
				'success' => false,
				'message' => 'File upload failed. Please try again.',
				'debug' => 'Upload error code: ' . $_FILES['excel_file']['error']
			));
			return;
		}

		// Load PhpSpreadsheet
		require_once FCPATH . 'vendor/autoload.php';

		try {
			$file_path = $_FILES['excel_file']['tmp_name'];

			// Validate file exists
			if(!file_exists($file_path)){
				throw new Exception('Uploaded file not found');
			}

			log_message('info', 'Parsing Excel file: ' . $_FILES['excel_file']['name']);
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);

			// Find the "Timesheet Erasmus+" sheet or any sheet with "timesheet" in the name (not summary)
			$worksheet = null;
			$sheetCount = $spreadsheet->getSheetCount();
			$sheetNames = array();

			log_message('info', 'Excel file has ' . $sheetCount . ' sheets');

			for($i = 0; $i < $sheetCount; $i++){
				$sheet = $spreadsheet->getSheet($i);
				$sheetName = $sheet->getTitle();
				$sheetNames[] = $sheetName;
				$sheetNameLower = strtolower($sheetName);

				log_message('info', 'Sheet ' . $i . ': ' . $sheetName);

				// Look for sheet with "timesheet" in the name but NOT "summary"
				if(strpos($sheetNameLower, 'timesheet') !== false && strpos($sheetNameLower, 'summary') === false){
					$worksheet = $sheet;
					log_message('info', 'Found timesheet sheet: ' . $sheetName);
					break;
				}
			}

			// If not found, try to use a specific sheet name
			if(!$worksheet){
				log_message('info', 'No sheet with "timesheet" found, trying fallback methods');
				try {
					$worksheet = $spreadsheet->getSheetByName('Timesheet Erasmus+');
					log_message('info', 'Using sheet: Timesheet Erasmus+');
				} catch(Exception $e) {
					// If still not found, use the third sheet (index 2) as fallback
					if($sheetCount > 2){
						$worksheet = $spreadsheet->getSheet(2);
						log_message('info', 'Using sheet index 2: ' . $worksheet->getTitle());
					} else {
						$worksheet = $spreadsheet->getSheet(0);
						log_message('info', 'Using sheet index 0: ' . $worksheet->getTitle());
					}
				}
			}

			$entries = array();
			$staff_info = array();

			log_message('info', 'Using worksheet: ' . $worksheet->getTitle());

			try {
				// Extract staff information from the sheet
				// Based on the GREATER template structure
				$staff_info['name'] = $worksheet->getCell('B4')->getValue();
				$staff_info['organization'] = $worksheet->getCell('B5')->getValue();
				$staff_info['staff_category'] = $worksheet->getCell('B6')->getValue();

				log_message('info', 'Staff info extracted - Name: ' . $staff_info['name'] . ', Org: ' . $staff_info['organization'] . ', Category: ' . $staff_info['staff_category']);
			} catch(Exception $e) {
				log_message('error', 'Error extracting staff info: ' . $e->getMessage());
				// Continue anyway - staff info is optional
			}

			// Check the header row to verify column structure
			log_message('info', 'Header check - A12=' . $worksheet->getCell('A12')->getValue() . ', B12=' . $worksheet->getCell('B12')->getValue() . ', C12=' . $worksheet->getCell('C12')->getValue() . ', D12=' . $worksheet->getCell('D12')->getValue());

			// Daily entries in the template start from row 13 (after the headers)
			// Correct column mapping based on actual Excel structure:
			// A=Total hours, B=Date (dd/mm/yyyy), C=Work Package, D=Comments
			$row = 13;
			$max_rows = 200; // Safety limit

			log_message('info', 'Starting to parse daily entries from row ' . $row);

			while($row < $max_rows){
				try {
					$total_hours = $worksheet->getCell('A' . $row)->getValue();
					$date_cell = $worksheet->getCell('B' . $row)->getValue();
					$work_package = $worksheet->getCell('C' . $row)->getValue();
					$comments = $worksheet->getCell('D' . $row)->getValue();

					// Log raw values for debugging
					$comments_preview = is_string($comments) ? substr($comments, 0, 50) : (string)$comments;
					log_message('info', 'Row ' . $row . ' - Raw values: Hours=' . $total_hours . ', Date=' . $date_cell . ', WP=' . $work_package . ', Comments=' . $comments_preview);

					// Stop if we hit an empty row
					if(empty($total_hours) && empty($date_cell)){
						log_message('info', 'Reached end of entries at row ' . $row);
						break;
					}

					// Skip if no hours or invalid data
					if(empty($total_hours) || $total_hours == 0 || empty($work_package)){
						$row++;
						continue;
					}

					// Parse the date
					$date_formatted = '';
					$date_raw = '';
					if(!empty($date_cell)){
						try {
							if(is_numeric($date_cell)){
								// Excel date serial number - validate it's reasonable (after 1900, before 2100)
								if($date_cell > 0 && $date_cell < 73050){
									$date_obj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date_cell);
									$parsed_year = $date_obj->format('Y');
									// Validate the year is reasonable (1900-2099)
									if($parsed_year >= 1900 && $parsed_year <= 2099){
										$date_formatted = $date_obj->format('d/m/Y');
										$date_raw = $date_obj->format('Y-m-d');
									} else {
										log_message('info', 'Row ' . $row . ' - Invalid year parsed: ' . $parsed_year);
										$date_formatted = '';
										$date_raw = '';
									}
								} else {
									log_message('info', 'Row ' . $row . ' - Invalid date serial number: ' . $date_cell);
									$date_formatted = '';
									$date_raw = '';
								}
							} else {
								// Try to parse as string date - handle various formats
								try {
									// Try common date formats
									$date_formats = array('d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'm-d-Y');
									$date_obj = null;

									foreach($date_formats as $format) {
										$parsed = DateTime::createFromFormat($format, $date_cell);
										if($parsed !== false) {
											$date_obj = $parsed;
											break;
										}
									}

									// If no format matched, try generic parsing
									if($date_obj === null) {
										$date_obj = new DateTime($date_cell);
									}

									$parsed_year = $date_obj->format('Y');
									if($parsed_year >= 1900 && $parsed_year <= 2099){
										$date_formatted = $date_obj->format('d/m/Y');
										$date_raw = $date_obj->format('Y-m-d');
									} else {
										log_message('info', 'Row ' . $row . ' - Invalid year in string date: ' . $parsed_year);
										$date_formatted = '';
										$date_raw = '';
									}
								} catch(Exception $e) {
									log_message('info', 'Row ' . $row . ' - String date parsing failed: ' . $e->getMessage());
									$date_formatted = '';
									$date_raw = '';
								}
							}
						} catch(Exception $e) {
							log_message('info', 'Date parsing error at row ' . $row . ': ' . $e->getMessage());
							$date_formatted = '';
							$date_raw = '';
						}
					}

					// Skip if date is empty or invalid
					if(empty($date_formatted) || empty($date_raw)){
						log_message('info', 'Row ' . $row . ' - Skipping due to invalid date');
						$row++;
						continue;
					}

					// Extract work package code (e.g., "WP1" from "WP1 - Management and coordination")
					$wp_code = '';
					if(!empty($work_package)){
						if(preg_match('/^(WP\d+)/', $work_package, $matches)){
							$wp_code = $matches[1];
						} else {
							$wp_code = $work_package;
						}
					}

					$entries[] = array(
						'date' => $date_formatted,
						'date_raw' => $date_raw,
						'hours' => floatval($total_hours),
						'work_package' => $work_package,
						'work_package_code' => $wp_code,
						'comments' => $comments ? $comments : ''
					);

				} catch(Exception $e) {
					log_message('error', 'Error parsing row ' . $row . ': ' . $e->getMessage());
				}

				$row++;
			}

			log_message('info', 'Parsed ' . count($entries) . ' entries from Excel file');

			if(empty($entries)){
				log_message('error', 'No valid entries found in Excel file');
				echo json_encode(array(
					'success' => false,
					'message' => 'No valid entries found in the Excel file. Please make sure the file follows the GREATER template format.',
					'debug' => array(
						'sheet_name' => $worksheet->getTitle(),
						'sheet_count' => $sheetCount,
						'sheet_names' => $sheetNames
					)
				));
				return;
			}

			echo json_encode(array(
				'success' => true,
				'entries' => $entries,
				'staff_info' => $staff_info,
				'message' => 'Successfully parsed ' . count($entries) . ' entries from Excel file'
			));

		} catch(Exception $e) {
			// Log comprehensive error details
			log_message('error', 'Excel parsing exception: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile());
			log_message('error', 'Stack trace: ' . $e->getTraceAsString());

			echo json_encode(array(
				'success' => false,
				'message' => 'Error reading Excel file: ' . $e->getMessage(),
				'debug' => array(
					'error_line' => $e->getLine(),
					'error_file' => basename($e->getFile()),
					'error_type' => get_class($e)
				)
			));
		}
	}

	public function saveTimesheet(){
		// Validate inputs
		$this->form_validation->set_rules('year', 'Year', 'required');
		$this->form_validation->set_rules('month', 'Month', 'required');
		$this->form_validation->set_rules('staff_category', 'Staff Category', 'required');

		if($this->form_validation->run() == FALSE){
			$this->session->set_flashdata('error', validation_errors());
			redirect('newTimesheet');
			return;
		}

		$user_id = $this->session->userdata('fms_user_id');
		$user = $this->fmsm_enhanced->get_user_by_id($user_id);

		// Check if timesheet already exists for this user/month/year
		$existing = $this->fmsm_enhanced->get_timesheet_by_month($user_id, $this->input->post('year'), $this->input->post('month'));

		if($existing){
			$this->session->set_flashdata('error', 'A timesheet for this month already exists. Please edit the existing timesheet instead.');
			redirect('newTimesheet');
			return;
		}

		// Get daily entries from POST data
		$dates = $this->input->post('entry_date');
		$hours = $this->input->post('entry_hours');
		$work_packages = $this->input->post('entry_work_package');
		$descriptions = $this->input->post('entry_description');

		// Validate that we have at least one entry
		if(empty($dates) || !is_array($dates) || count($dates) == 0){
			$this->session->set_flashdata('error', 'Please add at least one daily entry.');
			redirect('newTimesheet');
			return;
		}

		// Start transaction
		$this->db->trans_start();

		// Create timesheet record
		$timesheet_data = array(
			'user_id' => $user_id,
			'partner_id' => $user['partner_id'],
			'year' => $this->input->post('year'),
			'month' => $this->input->post('month'),
			'staff_category' => $this->input->post('staff_category'),
			'total_hours' => 0, // Will be calculated from details
			'status' => 'submitted',
			'submitted_at' => date('Y-m-d H:i:s')
		);

		$timesheet_id = $this->fmsm_enhanced->create_timesheet($timesheet_data);

		if($timesheet_id){
			// Save daily entries
			$total_hours = 0;
			foreach($dates as $index => $date){
				if(!empty($date) && !empty($hours[$index]) && $hours[$index] > 0){
					$detail_data = array(
						'timesheet_id' => $timesheet_id,
						'date' => $date,
						'hours' => $hours[$index],
						'work_package' => $work_packages[$index],
						'activity_description' => $descriptions[$index]
					);
					$this->fmsm_enhanced->save_timesheet_detail($detail_data);
					$total_hours += $hours[$index];
				}
			}

			// Update timesheet with calculated total hours
			$this->fmsm_enhanced->update_timesheet($timesheet_id, array('total_hours' => $total_hours));
		}

		// Complete transaction
		$this->db->trans_complete();

		if($this->db->trans_status() === FALSE){
			$this->session->set_flashdata('error', 'Failed to save timesheet to database.');
			redirect('newTimesheet');
		} else {
			// Notify coordinators and admins of new submission
			$timesheet_row = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);
			$submitter_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
			$admin_emails = $this->fmsm_enhanced->get_admin_emails();
			$coord_emails = $this->fmsm_enhanced->get_coordinator_emails($user['partner_id'] ?? 0);
			$recipients   = array_unique(array_merge($admin_emails, $coord_emails));
			if($timesheet_row) $this->fms_mailer->timesheet_submitted($timesheet_row, $submitter_name, $recipients);

			$this->session->set_flashdata('success', 'Timesheet submitted successfully and pending approval.');
			redirect('timesheets');
		}
	}

	public function approveTimesheet($timesheet_id){
		// Only coordinators and admins can approve timesheets
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: Only coordinators and administrators can approve timesheets.', 403);
		}

		$approver_id = $this->session->userdata('fms_user_id');
		$comments = $this->input->post('comments');

		// Get approver's signature configuration
		$signature = $this->fmsm_enhanced->get_signature_by_user_id($approver_id);

		if($this->fmsm_enhanced->approve_timesheet($timesheet_id, $approver_id, $comments, $signature)){
			// Notify the staff member
			$ts   = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);
			$owner = $ts ? $this->fmsm_enhanced->get_user_by_id($ts['user_id']) : null;
			if($owner && !empty($owner['email'])){
				$name = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
				$this->fms_mailer->timesheet_approved($ts, $owner['email'], $name);
			}
			$this->session->set_flashdata('success', 'Timesheet approved successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to approve timesheet.');
		}

		redirect('timesheets');
	}

	public function rejectTimesheet($timesheet_id){
		// Only coordinators and admins can reject timesheets
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: Only coordinators and administrators can reject timesheets.', 403);
		}

		$comments = $this->input->post('comments');

		if(empty($comments)){
			$this->session->set_flashdata('error', 'Comments are required when rejecting a timesheet.');
			redirect('timesheets');
			return;
		}

		if($this->fmsm_enhanced->reject_timesheet($timesheet_id, $comments)){
			// Notify the staff member
			$ts   = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);
			$owner = $ts ? $this->fmsm_enhanced->get_user_by_id($ts['user_id']) : null;
			if($owner && !empty($owner['email'])){
				$name = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
				$this->fms_mailer->timesheet_rejected($ts, $owner['email'], $name, $comments);
			}
			$this->session->set_flashdata('success', 'Timesheet rejected.');
		} else {
			$this->session->set_flashdata('error', 'Failed to reject timesheet.');
		}

		redirect('timesheets');
	}

	public function viewTimesheet($timesheet_id){
		// Get the timesheet
		$timesheet = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);

		if(!$timesheet){
			show_error('Timesheet not found.', 404);
		}

		// Check access - users can view their own, coordinators can view their institution's, admins can view all
		$user_id = $this->session->userdata('fms_user_id');
		$partner_id = $this->session->userdata('fms_partner_id');

		if($timesheet['user_id'] != $user_id &&
		   !$this->auth_manager->is_super_admin() &&
		   !$this->auth_manager->is_admin()){
			if($this->auth_manager->is_coordinator() && $timesheet['partner_id'] != $partner_id){
				show_error('Access Denied: You can only view timesheets from your institution.', 403);
			} else if(!$this->auth_manager->is_coordinator()){
				show_error('Access Denied: You can only view your own timesheets.', 403);
			}
		}

		$this->data["title"] = "FMS - View Timesheet";
		$this->data['timesheet'] = $timesheet;
		$this->data['timesheet_details'] = $this->fmsm_enhanced->get_timesheet_details($timesheet_id);
		$this->data['work_package_summary'] = $this->fmsm_enhanced->get_timesheet_work_package_summary($timesheet_id);

		$this->load->view('pages/viewtimesheet', $this->data);
	}

	public function downloadTimesheetPDF($timesheet_id){
		// Get the timesheet
		$timesheet = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);

		if(!$timesheet){
			show_error('Timesheet not found.', 404);
		}

		// Check access
		$user_id = $this->session->userdata('fms_user_id');
		if($timesheet['user_id'] != $user_id && !$this->auth_manager->is_admin()){
			show_error('Access Denied', 403);
		}

		// Get timesheet details
		$timesheet_details = $this->fmsm_enhanced->get_timesheet_details($timesheet_id);
		$work_package_summary = $this->fmsm_enhanced->get_timesheet_work_package_summary($timesheet_id);

		// Load dompdf library
		require_once APPPATH . '../vendor/autoload.php';

		try {
			$dompdf = new \Dompdf\Dompdf();
			$dompdf->set_option('enable_remote', true);
		} catch(Exception $e) {
			show_error('PDF Library Error: ' . $e->getMessage(), 500);
			return;
		}

		// Build HTML content
		try {
			$html = $this->generateTimesheetPDF($timesheet, $timesheet_details, $work_package_summary);

			if(empty($html)){
				show_error('Failed to generate PDF content', 500);
				return;
			}

			$dompdf->load_html($html);
			$dompdf->render();

			// Generate filename
			$filename = 'Timesheet-' . $timesheet['first_name'] . '-' . $timesheet['last_name'] . '-' . $timesheet['year'] . '-' . $timesheet['month'] . '.pdf';

			// Output PDF
			$dompdf->stream($filename, array('Attachment' => 0));
		} catch(Exception $e) {
			show_error('Error generating PDF: ' . $e->getMessage(), 500);
		}
	}

	/** Returns an <img> tag with the GREATER logo embedded as base64 */
	private function get_pdf_logo_html($max_height = '55px'){
		$logo_path = FCPATH . 'assets/img/greater_logo.png';
		if(file_exists($logo_path)){
			$b64 = base64_encode(file_get_contents($logo_path));
			return '<img src="data:image/png;base64,'.$b64.'" style="max-height:'.$max_height.';width:auto;" alt="GREATER" />';
		}
		return '<span style="font-weight:bold;font-size:16px;color:#696cff;">GREATER</span>';
	}

	private function generateTimesheetPDF($timesheet, $details, $summary){
		$months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
		                7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
		$month_name = isset($months[$timesheet['month']]) ? $months[$timesheet['month']] : $timesheet['month'];

		// Work package labels
		$wp_labels = array(
			'WP1' => 'WP1 - Management and coordination',
			'WP2' => 'WP2 - Collaboration design',
			'WP3' => 'WP3 - Infrastructures',
			'WP4' => 'WP4 - Curricula design',
			'WP5' => 'WP5 - Training and coaching',
			'WP6' => 'WP6 - Transfer methodologies',
			'WP7' => 'WP7 - Impact and dissemination'
		);

		$logo_html = $this->get_pdf_logo_html('52px');

		$html = '<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				* { margin: 0; padding: 0; box-sizing: border-box; }
				body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
				.container { max-width: 100%; margin: 0 auto; padding: 20px; }
				h4 { font-size: 13px; font-weight: 600; margin-top: 20px; margin-bottom: 12px; color: #212529; }
				h5 { font-size: 12px; font-weight: 600; margin-bottom: 10px; color: #212529; }
				.pdf-logo-bar { display:table; width:100%; border-bottom:3px solid #696cff; padding-bottom:10px; margin-bottom:18px; }
				.pdf-logo-bar-left { display:table-cell; vertical-align:middle; width:180px; }
				.pdf-logo-bar-right { display:table-cell; vertical-align:middle; text-align:right; }
				.pdf-logo-bar-right h2 { font-size:15px; color:#333; margin:0; font-weight:700; }
				.pdf-logo-bar-right p  { font-size:9px; color:#696cff; margin:2px 0 0 0; letter-spacing:.5px; }

				.card { border: 1px solid #e3e6f0; border-radius: 4px; margin-bottom: 20px; }
				.card-header { background-color: #f8f9fa; border-bottom: 1px solid #e3e6f0; padding: 12px 15px; }
				.card-header h5 { margin: 0; font-size: 12px; font-weight: 600; }
				.card-body { padding: 15px; }

				.row { display: table; width: 100%; margin-bottom: 10px; }
				.col-md-3 { display: table-cell; width: 25%; padding: 8px; vertical-align: top; }
				.col-md-12 { display: table-cell; width: 100%; padding: 8px; }

				.info-text { margin-bottom: 8px; }
				.info-text p { margin: 0 0 3px 0; font-size: 11px; }
				.info-text strong { font-weight: 600; }

				table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
				thead { background-color: #f8f9fa; display: table-header-group; }
				th { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: 600; font-size: 10px; }
				td { border: 1px solid #dee2e6; padding: 8px; }
				tbody tr:nth-child(even) { background-color: #f8f9fa; }
				tbody tr { page-break-inside: avoid; }
				tfoot { background-color: #f1f3f5; font-weight: 600; display: table-footer-group; }
				tfoot th { background-color: #f1f3f5; }

				/* Add margin to all pages - this creates space at top of each page */
				@page { margin: 60px 25px 30px 25px; }

				.badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; }
				.badge-success { background-color: #d4edda; color: #155724; }
				.badge-danger { background-color: #f8d7da; color: #721c24; }
				.badge-warning { background-color: #fff3cd; color: #856404; }
				.badge-secondary { background-color: #e2e3e5; color: #383d41; }

				.flex-between { display: flex; justify-content: space-between; align-items: center; }
				.mb-0 { margin-bottom: 0; }
				.mb-1 { margin-bottom: 4px; }
				.mt-3 { margin-top: 15px; }

				.signature-section { margin-top: 30px; float: right; text-align: left; width: 300px; }
				.signature-image { max-width: 200px; max-height: 100px; margin: 15px 0; }
				.signature-line { border-top: 1px solid #333; width: 250px; margin-top: 30px; margin-bottom: 5px; }
				.signature-label { font-size: 9px; color: #666; }

				.alert { padding: 12px 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid; }
				.alert-info { background-color: #d1ecf1; border-left-color: #0c5460; color: #0c5460; }

				/* Approved Stamp */
				.approved-stamp {
					position: absolute;
					top: 15px;
					right: 20px;
					border: 3px solid #28a745;
					color: #28a745;
					font-size: 18px;
					font-weight: bold;
					padding: 8px 20px;
					text-transform: uppercase;
					border-radius: 8px;
					opacity: 0.7;
					transform: rotate(30deg);
					letter-spacing: 2px;
				}

				/* Page break before Daily Entries */
				.page-break-before {
					page-break-before: always;
					margin-top: 30px;
				}

				/* Position relative for stamp positioning */
				.position-relative {
					position: relative;
				}

				/* Add top margin to sections after page break */
				.section-spacing {
					margin-top: 30px;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<!-- Logo banner -->
				<div class="pdf-logo-bar">
					<div class="pdf-logo-bar-left">'.$logo_html.'</div>
					<div class="pdf-logo-bar-right">
						<h2>Timesheet for Project Outputs</h2>
						<p>ERASMUS+ GREATER — Growing Rwanda Energy Awareness Through highER education</p>
					</div>
				</div>
				<!-- Timesheet Header Info -->
				<div class="card position-relative">
					<div class="card-header">
						<h5 class="mb-0">Staff Information</h5>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Project Name:</strong></p>
									<p>GREATER – Growing Rwanda Energy Awareness Through highER education</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Project ID:</strong></p>
									<p>101083081 ERASMUS-EDU-2022-CBHE</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Employee:</strong></p>
									<p>' . htmlspecialchars($timesheet['first_name'] . ' ' . $timesheet['last_name']) . '</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Organization:</strong></p>
									<p>' . htmlspecialchars($timesheet['partner_name']) . '</p>
								</div>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Staff Category:</strong></p>
									<p>' . htmlspecialchars($timesheet['staff_category']) . '</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Period:</strong></p>
									<p>' . $month_name . ' ' . $timesheet['year'] . '</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Total Hours:</strong></p>
									<p style="color: #0066cc; font-weight: 600;">' . number_format($timesheet['total_hours'], 1) . ' hours</p>
								</div>
							</div>
							<div class="col-md-3">
								<div class="info-text">
									<p class="mb-1"><strong>Submitted:</strong></p>
									<p>' . (!empty($timesheet['submitted_at']) ? date('M d, Y', strtotime($timesheet['submitted_at'])) : 'Not submitted') . '</p>
								</div>
							</div>
						</div>' . (!empty($timesheet['comments']) ? '
						<div class="row mt-3">
							<div class="col-md-12">
								<div class="alert alert-info">
									<strong>Comments:</strong>
									<p style="margin-top: 8px; margin-bottom: 0;">' . htmlspecialchars($timesheet['comments']) . '</p>
								</div>
							</div>
						</div>' : '') . '
					</div>
					' . ($timesheet['status'] == 'approved' ? '<div class="approved-stamp">Approved</div>' : '') . '
				</div>

				<!-- Work Package Summary -->
				<div class="card section-spacing">
					<div class="card-header">
						<h5 class="mb-0">Summary by Work Package</h5>
					</div>
					<div class="card-body">
						<table>
							<thead>
								<tr>
									<th style="width: 75%;">Work Package</th>
									<th style="width: 25%;">Hours</th>
								</tr>
							</thead>
							<tbody>';

		if(!empty($summary)){
			foreach($summary as $wp){
				$wp_label = isset($wp_labels[$wp['work_package']]) ? $wp_labels[$wp['work_package']] : $wp['work_package'];
				$html .= '<tr>
					<td>' . htmlspecialchars($wp_label) . '</td>
					<td>' . number_format($wp['total_hours'], 1) . '</td>
				</tr>';
			}
		} else {
			$html .= '<tr><td colspan="2" style="text-align: center;">No work package data available</td></tr>';
		}

		$html .= '</tbody>
							<tfoot>
								<tr>
									<th>Total</th>
									<th>' . number_format($timesheet['total_hours'], 1) . ' hours</th>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>

				<!-- Daily Time Entries -->
				<div class="card page-break-before">
					<div class="card-header">
						<h5 class="mb-0">Daily Time Entries</h5>
					</div>
					<div class="card-body">
						<table>
							<thead>
								<tr>
									<th style="width: 12%;">Date</th>
									<th style="width: 10%;">Hours</th>
									<th style="width: 28%;">Work Package</th>
									<th style="width: 50%;">Comments</th>
								</tr>
							</thead>
							<tbody>';

		if(!empty($details)){
			foreach($details as $detail){
				$wp_label = isset($wp_labels[$detail['work_package']]) ? $wp_labels[$detail['work_package']] : $detail['work_package'];
				$html .= '<tr>
					<td>' . date('d/m/Y', strtotime($detail['date'])) . '</td>
					<td>' . number_format($detail['hours'], 1) . '</td>
					<td>' . htmlspecialchars($wp_label) . '</td>
					<td>' . htmlspecialchars($detail['activity_description']) . '</td>
				</tr>';
			}
		} else {
			$html .= '<tr><td colspan="4" style="text-align: center;">No daily entries found</td></tr>';
		}

		$html .= '</tbody>
						</table>
					</div>
				</div>';

		// Add approver signature section from settings
		// Try to get signature from timesheet record, if not available, fetch from approver's current settings
		$signature_data = null;

		if(!empty($timesheet['approver_signature_file'])){
			// Use signature data saved with the timesheet
			$signature_data = array(
				'signature_name' => $timesheet['approver_signature_name'],
				'position' => $timesheet['approver_signature_position'],
				'organization' => $timesheet['approver_signature_organization'],
				'signature_file' => $timesheet['approver_signature_file']
			);
		} else if(!empty($timesheet['approved_by'])){
			// Fetch approver's current signature from settings
			$signature_data = $this->fmsm_enhanced->get_signature_by_user_id($timesheet['approved_by']);
		}

		if($signature_data && !empty($signature_data['signature_file'])){
			// Convert approver signature image to base64 for PDF embedding
			$approver_sig_path = FCPATH . 'assets/signatures/' . $signature_data['signature_file'];
			$approver_sig_base64 = '';

			if(file_exists($approver_sig_path)){
				$image_data = base64_encode(file_get_contents($approver_sig_path));
				$approver_sig_base64 = 'data:image/png;base64,' . $image_data;
			}

			if(!empty($approver_sig_base64)){
				$html .= '
					<div class="signature-section">
						<h5>Approved By:</h5>
						<img src="' . $approver_sig_base64 . '" class="signature-image" alt="Approver Signature" />
						<p style="margin:5px 0;font-size:11px;"><strong>Name:</strong> ' . htmlspecialchars($signature_data['signature_name']) . '</p>
						<p style="margin:5px 0;font-size:11px;"><strong>Position:</strong> ' . htmlspecialchars($signature_data['position']) . '</p>';

				if(!empty($signature_data['organization'])){
					$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Organization:</strong> ' . htmlspecialchars($signature_data['organization']) . '</p>';
				}

				$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Date Approved:</strong> ' . (!empty($timesheet['approved_at']) ? date('F j, Y', strtotime($timesheet['approved_at'])) : 'N/A') . '</p>
					</div>';
			}
		}

		$html .= '
			</div>

		</body>
		</html>';

		return $html;
	}

	public function uploadTimesheetSignature(){
		// Validate request
		if(!$this->input->is_ajax_request() || $this->input->method() != 'post'){
			echo json_encode(array('success' => false, 'message' => 'Invalid request'));
			return;
		}

		$timesheet_id = $this->input->post('timesheet_id');
		$timesheet = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);

		if(!$timesheet){
			echo json_encode(array('success' => false, 'message' => 'Timesheet not found'));
			return;
		}

		// Check access
		$user_id = $this->session->userdata('fms_user_id');
		if($timesheet['user_id'] != $user_id && !$this->auth_manager->is_admin()){
			echo json_encode(array('success' => false, 'message' => 'Access denied'));
			return;
		}

		// Handle file upload
		if(empty($_FILES['signature_image']['name'])){
			echo json_encode(array('success' => false, 'message' => 'No file uploaded'));
			return;
		}

		$config['upload_path'] = './assets/uploads/signatures/';
		$config['allowed_types'] = 'gif|jpg|jpeg|png';
		$config['max_size'] = 5120; // 5MB
		$config['file_name'] = 'signature-' . $timesheet_id . '-' . time() . '.png';

		// Create directory if it doesn't exist
		if(!is_dir($config['upload_path'])){
			mkdir($config['upload_path'], 0755, true);
		}

		$this->upload->initialize($config);

		if(!$this->upload->do_upload('signature_image')){
			echo json_encode(array('success' => false, 'message' => $this->upload->display_errors()));
			return;
		}

		$upload_data = $this->upload->data();
		$signature_path = 'assets/uploads/signatures/' . $upload_data['file_name'];

		// Update timesheet with signature
		$update_data = array(
			'signature_image' => $signature_path,
			'signature_date' => date('Y-m-d H:i:s')
		);

		if($this->fmsm_enhanced->update_timesheet($timesheet_id, $update_data)){
			echo json_encode(array('success' => true, 'message' => 'Signature uploaded successfully'));
		} else {
			echo json_encode(array('success' => false, 'message' => 'Failed to save signature'));
		}
	}

	public function editTimesheet($timesheet_id){
		// Get the timesheet
		$timesheet = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);

		if(!$timesheet){
			show_error('Timesheet not found.', 404);
		}

		// Check access - user can only edit their own timesheets
		$user_id = $this->session->userdata('fms_user_id');
		if($timesheet['user_id'] != $user_id && !$this->auth_manager->is_admin()){
			show_error('Access Denied: You can only edit your own timesheets.', 403);
		}

		// Can only edit if status is draft or rejected
		if($timesheet['status'] != 'draft' && $timesheet['status'] != 'rejected'){
			$this->session->set_flashdata('error', 'You can only edit timesheets that are in draft or rejected status.');
			redirect('timesheets');
			return;
		}

		// Get timesheet details (daily entries)
		$timesheet_details = $this->fmsm_enhanced->get_timesheet_details($timesheet_id);

		$this->data["title"] = "FMS - Edit Timesheet";
		$this->data['timesheet'] = $timesheet;
		$this->data['timesheet_details'] = $timesheet_details;

		$this->load->view('pages/edittimesheet', $this->data);
	}

	public function updateTimesheet($timesheet_id){
		// Get the timesheet
		$timesheet = $this->fmsm_enhanced->get_timesheet_by_id($timesheet_id);

		if(!$timesheet){
			show_error('Timesheet not found.', 404);
		}

		// Check access - user can only update their own timesheets
		$user_id = $this->session->userdata('fms_user_id');
		if($timesheet['user_id'] != $user_id && !$this->auth_manager->is_admin()){
			show_error('Access Denied: You can only update your own timesheets.', 403);
		}

		// Get entry data
		$entry_dates = $this->input->post('entry_date');
		$entry_hours = $this->input->post('entry_hours');
		$entry_work_packages = $this->input->post('entry_work_package');
		$entry_descriptions = $this->input->post('entry_description');

		// Validate entries
		if(empty($entry_dates) || !is_array($entry_dates)){
			$this->session->set_flashdata('error', 'Please add at least one daily entry.');
			redirect('editTimesheet/' . $timesheet_id);
			return;
		}

		// Calculate total hours
		$total_hours = 0;
		foreach($entry_hours as $hours){
			$total_hours += floatval($hours);
		}

		if($total_hours == 0){
			$this->session->set_flashdata('error', 'Total hours cannot be 0.');
			redirect('editTimesheet/' . $timesheet_id);
			return;
		}

		// Update timesheet header
		$timesheet_data = array(
			'staff_category' => $this->input->post('staff_category'),
			'total_hours' => $total_hours,
			'status' => 'submitted',
			'submitted_at' => date('Y-m-d H:i:s')
		);

		if($this->fmsm_enhanced->update_timesheet($timesheet_id, $timesheet_data)){
			// Delete existing details
			$this->fmsm_enhanced->delete_timesheet_details($timesheet_id);

			// Insert updated details
			for($i = 0; $i < count($entry_dates); $i++){
				if(empty($entry_dates[$i]) || empty($entry_hours[$i]) || empty($entry_work_packages[$i])){
					continue;
				}

				$detail_data = array(
					'timesheet_id' => $timesheet_id,
					'date' => date('Y-m-d', strtotime($entry_dates[$i])),
					'hours' => floatval($entry_hours[$i]),
					'work_package' => $entry_work_packages[$i],
					'activity_description' => $entry_descriptions[$i]
				);

				$this->fmsm_enhanced->save_timesheet_detail($detail_data);
			}

			$this->session->set_flashdata('success', 'Timesheet updated and resubmitted successfully.');
			redirect('timesheets');
		} else {
			$this->session->set_flashdata('error', 'Failed to update timesheet.');
			redirect('editTimesheet/' . $timesheet_id);
		}
	}

	public function newExpense(){
		// Only coordinators and admins can upload expenses
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to upload expenses.', 403);
		}

		$this->data["title"] = "FMS - New Expense";
		$this->data["uid"] = $this->generateUID();

		// Get all partners for super admin/admin
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['partners'] = $this->fmsm_enhanced->get_all_partners();
		}

		$this->load->view('pages/newexpense', $this->data);
	}

	public function saveExpense() {
		// Only coordinators and admins can upload expenses
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to upload expenses.', 403);
		}

		// Get and validate input
		$file_name = $this->input->post('formFN', TRUE);
		$category = $this->input->post('formCategory', TRUE);
		$work_package = $this->input->post('formWorkPackage', TRUE);
		$currency = $this->input->post('formCurrency', TRUE);
		$amount = $this->input->post('formAmount', TRUE);
		$description = $this->input->post('formShortDescription', TRUE);
		$date = $this->input->post('formValidationDate', TRUE);

		// Validate required fields
		if(empty($file_name) || empty($category) || empty($work_package) || empty($currency) ||
		   empty($amount) || empty($description) || empty($date)){
			$this->session->set_flashdata('error', 'All fields are required.');
			redirect('newExpense?status=error');
			return;
		}

		// Validate file name length
		if(strlen($file_name) < 6 || strlen($file_name) > 30){
			$this->session->set_flashdata('error', 'File name must be between 6 and 30 characters.');
			redirect('newExpense?status=error');
			return;
		}

		// Validate description length
		if(strlen($description) < 50 || strlen($description) > 500){
			$this->session->set_flashdata('error', 'Description must be between 50 and 500 characters.');
			redirect('newExpense?status=error');
			return;
		}

		// Validate amount is numeric and positive
		if(!is_numeric($amount) || floatval($amount) <= 0){
			$this->session->set_flashdata('error', 'Amount must be a positive number.');
			redirect('newExpense?status=error');
			return;
		}

		// Validate date format (YYYY/MM/DD from flatpickr)
		// Try both formats: YYYY/MM/DD and YYYY-MM-DD for flexibility
		$date_obj = DateTime::createFromFormat('Y/m/d', $date);
		if(!$date_obj){
			// Try alternate format
			$date_obj = DateTime::createFromFormat('Y-m-d', $date);
		}

		if(!$date_obj){
			$this->session->set_flashdata('error', 'Invalid date format. Use YYYY/MM/DD.');
			redirect('newExpense?status=error');
			return;
		}

		// Convert to standard format for database storage (YYYY-MM-DD)
		$date = $date_obj->format('Y-m-d');

		// Validate date is not in the future
		if(strtotime($date) > time()){
			$this->session->set_flashdata('error', 'Expense date cannot be in the future.');
			redirect('newExpense?status=error');
			return;
		}

		// Valid categories (lowercase to match form values)
		$valid_categories = array('travel', 'accommodation', 'subsistence', 'equipment', 'consumables',
								  'meetings', 'communication', 'other');
		if(!in_array(strtolower($category), $valid_categories)){
			$this->session->set_flashdata('error', 'Invalid category selected.');
			redirect('newExpense?status=error');
			return;
		}

		// Map short form values to display values for storage
		$category_map = array(
			'travel' => 'Travel',
			'accommodation' => 'Accommodation',
			'subsistence' => 'Subsistence',
			'equipment' => 'Equipment',
			'consumables' => 'Consumables',
			'meetings' => 'Services for Meetings',
			'communication' => 'Services for communication/promotion/dissemination',
			'other' => 'Other'
		);
		$category = $category_map[strtolower($category)];

		// Valid work packages (convert to uppercase for storage)
		$valid_wps = array('wp1', 'wp2', 'wp3', 'wp4', 'wp5', 'wp6', 'wp7');
		if(!in_array(strtolower($work_package), $valid_wps)){
			$this->session->set_flashdata('error', 'Invalid work package selected.');
			redirect('newExpense?status=error');
			return;
		}

		// Convert to uppercase for storage
		$work_package = strtoupper($work_package);

		// Map currency values to codes and validate
		$currency_map = array(
			'rwf' => 'RWF',
			'euro' => 'EUR',
			'eur' => 'EUR',
			'usd' => 'USD'
		);

		if(!isset($currency_map[strtolower($currency)])){
			$this->session->set_flashdata('error', 'Invalid currency selected.');
			redirect('newExpense?status=error');
			return;
		}

		// Convert to currency code
		$currency = $currency_map[strtolower($currency)];

		// Validate file upload
		if(!isset($_FILES['formValidationFile']) || $_FILES['formValidationFile']['error'] !== UPLOAD_ERR_OK){
			$this->session->set_flashdata('error', 'File upload error. Please try again.');
			redirect('newExpense?status=error');
			return;
		}

		// Get partner_id based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$partner_id = $this->input->post('formPartnerId');
		} else {
			$partner_id = $this->session->userdata("fms_partner_id");
		}

        $data = array(
			'partner_id' => intval($partner_id),
            'FileName' => $file_name,
			'Category' => $category,
            'WorkPackage' => $work_package,
            'Currency' => $currency,  // Already mapped to proper code (RWF, EUR, USD)
            'Amount' => floatval($amount),
            'ShortDescription' => $description,
            'Date' => $date,
			'uploaded_by' => $this->session->userdata('fms_user_id'),
			'status' => 'pending',
        );

        // Handle file upload
        $config['upload_path'] = './assets/uploads/';
        $config['allowed_types'] = 'pdf|xlsx|xls|doc|docx';
        $config['max_size'] = 10240; // 10MB

		$file_name_input = $this->input->post('formFN');
		$file_extension = pathinfo($_FILES['formValidationFile']['name'], PATHINFO_EXTENSION);
        $final_file_name = $file_name_input . '.' . $file_extension;
        $the_file_path = './assets/uploads/' . $final_file_name;

        // Update FileName in data with extension
        $data['FileName'] = $final_file_name;

        if (move_uploaded_file($_FILES['formValidationFile']['tmp_name'], $the_file_path)) {
            // Save expense using enhanced model
			if($this->fmsm_enhanced->create_expense($data)){
				$this->session->set_flashdata('success', 'Expense uploaded successfully and pending approval.');
				redirect('expenses?status=success');
			} else {
				$this->session->set_flashdata('error', 'Failed to save expense to database.');
				redirect('newExpense?status=error');
			}
        } else {
            $error_msg = 'File upload failed. ';
            if(isset($_FILES['formValidationFile']['error'])){
                switch($_FILES['formValidationFile']['error']){
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_msg .= 'File too large (max 2MB).';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_msg .= 'No file selected.';
                        break;
                    default:
                        $error_msg .= 'Error code: ' . $_FILES['formValidationFile']['error'];
                }
            }
            $this->session->set_flashdata('error', $error_msg);
            redirect('newExpense?status=error');
        }
    }

	public function approveExpense($expense_id){
		// Only admins can approve expenses
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()){
			show_error('Access Denied: Only administrators can approve expenses.', 403);
		}

		$approver_id = $this->session->userdata('fms_user_id');
		$comments = $this->input->post('comments');

		// Get approver's signature configuration
		$signature = $this->fmsm_enhanced->get_signature_by_user_id($approver_id);

		if($this->fmsm_enhanced->approve_expense($expense_id, $approver_id, $comments, $signature)){
			// Notify the submitter
			$exp   = $this->fmsm_enhanced->get_expense_by_id($expense_id);
			if($exp && !empty($exp['uploaded_by'])){
				$owner = $this->fmsm_enhanced->get_user_by_id($exp['uploaded_by']);
				if($owner && !empty($owner['email'])){
					$name = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
					$this->fms_mailer->expense_approved($exp, $owner['email'], $name);
				}
			}
			$this->session->set_flashdata('success', 'Expense approved successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to approve expense.');
		}

		redirect('expenses');
	}

	public function rejectExpense($expense_id){
		// Only admins can reject expenses
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()){
			show_error('Access Denied: Only administrators can reject expenses.', 403);
		}

		$comments = $this->input->post('comments');

		if($this->fmsm_enhanced->reject_expense($expense_id, $comments)){
			// Notify the submitter
			$exp   = $this->fmsm_enhanced->get_expense_by_id($expense_id);
			if($exp && !empty($exp['uploaded_by'])){
				$owner = $this->fmsm_enhanced->get_user_by_id($exp['uploaded_by']);
				if($owner && !empty($owner['email'])){
					$name = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
					$this->fms_mailer->expense_rejected($exp, $owner['email'], $name, $comments ?? '');
				}
			}
			$this->session->set_flashdata('success', 'Expense rejected.');
		} else {
			$this->session->set_flashdata('error', 'Failed to reject expense.');
		}

		redirect('expenses');
	}

	public function generateReport(){
		// Only admins can generate reports
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()){
			show_error('Access Denied: You do not have permission to generate reports.', 403);
		}

		// Get form inputs
		$from_date = $this->input->post('from_date');
		$to_date = $this->input->post('to_date');
		$partner_id = $this->input->post('partner_id');
		$status = $this->input->post('status');

		// Validate dates
		if(empty($from_date) || empty($to_date)){
			if($this->input->is_ajax_request()){
				echo json_encode(['success' => false, 'message' => 'Please select both from and to dates']);
				return;
			}
			$this->session->set_flashdata('error', 'Please select both from and to dates');
			redirect('expenses');
			return;
		}

		// Get expenses based on filters
		$expenses = $this->fmsm_enhanced->get_expenses_for_report($from_date, $to_date, $partner_id, $status);

		if(empty($expenses)){
			if($this->input->is_ajax_request()){
				echo json_encode(['success' => false, 'message' => 'No expenses found for the selected criteria']);
				return;
			}
			$this->session->set_flashdata('error', 'No expenses found for the selected criteria');
			redirect('expenses');
			return;
		}

		// Load PDF library
		require_once FCPATH . 'vendor/autoload.php';
		$dompdf = new \Dompdf\Dompdf();

		// Calculate totals by currency
		$totals = [
			'RWF'         => 0,
			'RWF_in_EUR'  => 0,   // RWF total converted to EUR via daily forex rate
			'EUR'         => 0,
			'USD'         => 0,
			'count'       => count($expenses),
			'rwf_no_rate' => 0,   // RWF rows where no forex rate was found
		];

		foreach($expenses as $expense){
			$cur = strtoupper($expense['Currency']);
			if($cur === 'RWF'){
				$totals['RWF'] += $expense['Amount'];
				if(!empty($expense['forex_rate']) && $expense['forex_rate'] > 0){
					$totals['RWF_in_EUR'] += $expense['Amount'] / $expense['forex_rate'];
				} else {
					$totals['rwf_no_rate']++;
				}
			} elseif(isset($totals[$cur])){
				$totals[$cur] += $expense['Amount'];
			}
		}

		// Get partner name if filtered
		$partner_name = 'All Partners';
		if(!empty($partner_id)){
			$partner = $this->fmsm_enhanced->get_partner_by_id($partner_id);
			if($partner){
				$partner_name = $partner['name'];
			}
		}

		// Generate HTML for PDF
		$html = $this->generate_report_html($expenses, $totals, $from_date, $to_date, $partner_name, $status);

		$dompdf->loadHtml($html);
		$dompdf->setPaper('A4', 'landscape');
		$dompdf->render();

		// Generate filename
		$filename = 'Expense_Report_' . date('Y-m-d', strtotime($from_date)) . '_to_' . date('Y-m-d', strtotime($to_date)) . '.pdf';

		// Save PDF to temporary directory
		$reports_dir = FCPATH . 'assets/reports/';
		if(!is_dir($reports_dir)){
			mkdir($reports_dir, 0777, true);
		}

		$file_path = $reports_dir . $filename;
		file_put_contents($file_path, $dompdf->output());

		// Return JSON response for AJAX request or stream for direct access
		if($this->input->is_ajax_request()){
			echo json_encode([
				'success' => true,
				'message' => 'Report generated successfully',
				'file_url' => base_url('assets/reports/' . $filename),
				'file_name' => $filename
			]);
		} else {
			// For direct access, stream the PDF
			$dompdf->stream($filename, array('Attachment' => 0));
		}
	}

	public function generateTimesheetReport(){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied', 403);
		}

		$from_period = $this->input->post('from_period'); // YYYY-MM
		$to_period   = $this->input->post('to_period');
		$partner_id  = $this->input->post('partner_id');
		$status      = $this->input->post('status');

		if(empty($from_period) || empty($to_period)){
			echo json_encode(['success' => false, 'message' => 'Please select both From and To periods']);
			return;
		}

		$from_parts = explode('-', $from_period);
		$to_parts   = explode('-', $to_period);
		$from_year  = $from_parts[0];  $from_month = $from_parts[1];
		$to_year    = $to_parts[0];    $to_month   = $to_parts[1];

		// Coordinators can only pull their own partner
		if($this->auth_manager->is_coordinator() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_super_admin()){
			$partner_id = $this->session->userdata('fms_partner_id');
		}

		$timesheets = $this->fmsm_enhanced->get_timesheets_for_report($from_year, $from_month, $to_year, $to_month, $partner_id, $status);

		if(empty($timesheets)){
			echo json_encode(['success' => false, 'message' => 'No timesheets found for the selected criteria']);
			return;
		}

		$partner_name = 'All Partners';
		if(!empty($partner_id)){
			$partner = $this->fmsm_enhanced->get_partner_by_id($partner_id);
			if($partner) $partner_name = $partner['name'];
		}

		$months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
		           7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
		$from_label = $months[(int)$from_month].' '.$from_year;
		$to_label   = $months[(int)$to_month].' '.$to_year;

		require_once FCPATH . 'vendor/autoload.php';
		$dompdf = new \Dompdf\Dompdf();
		$html = $this->generate_timesheet_report_html($timesheets, $from_label, $to_label, $partner_name, $status);
		$dompdf->loadHtml($html);
		$dompdf->setPaper('A4', 'landscape');
		$dompdf->render();

		$filename = 'Timesheet_Report_'.str_replace(' ','_',$from_label).'_to_'.str_replace(' ','_',$to_label).'.pdf';
		$reports_dir = FCPATH . 'assets/reports/';
		if(!is_dir($reports_dir)) mkdir($reports_dir, 0777, true);
		file_put_contents($reports_dir . $filename, $dompdf->output());

		echo json_encode([
			'success'  => true,
			'message'  => 'Report generated successfully',
			'file_url' => base_url('assets/reports/' . $filename) . '?v=' . time(),
			'file_name'=> $filename
		]);
	}

	private function generate_timesheet_report_html($timesheets, $from_label, $to_label, $partner_name, $status){
		$months      = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
		                7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
		$status_text = empty($status) ? 'All Status' : ucfirst($status);
		$logo_html   = $this->get_pdf_logo_html('48px');

		// ── Aggregate ────────────────────────────────────────────────
		$total_hours      = 0;
		$hours_by_partner = [];
		$status_counts    = [];
		$hours_by_category= [];

		foreach($timesheets as $ts){
			$h     = (float)$ts['total_hours'];
			$total_hours += $h;
			$pname = $ts['partner_name'] ?: 'Unknown';
			$hours_by_partner[$pname] = ($hours_by_partner[$pname] ?? 0) + $h;
			$s = ucfirst($ts['status']);
			$status_counts[$s] = ($status_counts[$s] ?? 0) + 1;
			$cat = $ts['staff_category'] ?: 'N/A';
			$hours_by_category[$cat] = ($hours_by_category[$cat] ?? 0) + $h;
		}
		arsort($hours_by_partner);

		// ── HTML chart helpers (pure HTML — reliable in Dompdf) ─────────

		// Horizontal bar chart
		$barChart = function($data, $title) {
			if(empty($data)) return '';
			$maxV    = max($data) ?: 1;
			$total   = array_sum($data) ?: 1;
			$barPxW  = 180; // fixed pixel width of the bar track
			$colors  = ['#696cff','#7c7fff','#9092ff','#a4a6ff','#b8baff','#ccceff'];

			$o  = '<table style="width:100%;border-collapse:collapse;">';
			// Title header
			$o .= '<tr><td colspan="3" style="background:#696cff;color:#fff;padding:9px 14px;'
			    . 'font-weight:bold;font-size:10.5px;border-radius:5px 5px 0 0;">'
			    . htmlspecialchars($title).'</td></tr>';
			$ci = 0;
			foreach($data as $lbl => $val){
				$bw    = max(4, (int)round(($val / $maxV) * $barPxW));
				$pct   = round($val / $total * 100);
				$clr   = $colors[min($ci, count($colors)-1)];
				$rowBg = ($ci % 2 === 0) ? '#f8f8ff' : '#ffffff';
				$short = mb_strlen($lbl) > 24 ? mb_substr($lbl,0,22).'…' : $lbl;
				$o .= '<tr style="background:'.$rowBg.'">';
				// Label cell
				$o .= '<td style="padding:7px 10px 7px 12px;font-size:9px;color:#444;'
				    . 'width:145px;border-bottom:1px solid #eeeefc;">'
				    . htmlspecialchars($short).'</td>';
				// Bar cell
				$o .= '<td style="padding:7px 6px;border-bottom:1px solid #eeeefc;">';
				$o .= '<div style="background:#ececf8;border-radius:4px;width:'.$barPxW.'px;height:18px;">';
				$o .= '<div style="background:'.$clr.';border-radius:4px;width:'.$bw.'px;height:18px;"></div>';
				$o .= '</div></td>';
				// Value + pct cell
				$o .= '<td style="padding:7px 12px 7px 8px;font-size:9px;text-align:right;'
				    . 'border-bottom:1px solid #eeeefc;white-space:nowrap;">'
				    . '<strong style="color:#1565c0;">'.number_format($val,1).'h</strong>'
				    . '&nbsp;<span style="color:#aaa;font-size:8px;">'.$pct.'%</span>'
				    . '</td>';
				$o .= '</tr>';
				$ci++;
			}
			$o .= '</table>';
			return $o;
		};

		// Status distribution — coloured stat boxes + mini legend rows
		$htmlBar = $barChart($hours_by_partner, 'Hours by Institution');

		// ── Counts for stat cards ────────────────────────────────────
		$cnt_approved  = $status_counts['Approved']  ?? 0;
		$cnt_submitted = $status_counts['Submitted'] ?? 0;
		$cnt_rejected  = $status_counts['Rejected']  ?? 0;

		// ── Signature ────────────────────────────────────────────────
		$user_id   = $this->session->userdata('fms_user_id');
		$signature = $this->fmsm_enhanced->get_signature_by_user_id($user_id);

		// ── HTML ─────────────────────────────────────────────────────
		$html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Timesheet Report</title>';
		$html .= '<style>
			*{margin:0;padding:0;box-sizing:border-box}
			body{font-family:Arial,Helvetica,sans-serif;font-size:10.5px;color:#333;background:#fff;
			     line-height:1.45}

			/* ── Page margins handled by wrapper div ── */
			@page{margin:0}

			/* ── Header bar ── */
			.hdr{display:table;width:100%;border-bottom:3px solid #696cff;
			     padding-bottom:14px;margin-bottom:20px}
			.hdr-l{display:table-cell;vertical-align:middle;width:175px}
			.hdr-r{display:table-cell;vertical-align:middle;text-align:right}
			.hdr-r h1{font-size:22px;color:#1a1a2e;font-weight:800;letter-spacing:-.5px;margin:0}
			.hdr-r .sub{font-size:8.5px;color:#696cff;margin:5px 0 0;letter-spacing:.5px;
			            text-transform:uppercase}
			.hdr-r .gen{font-size:8px;color:#bbb;margin:4px 0 0}

			/* ── Meta strip ── */
			.meta-strip{background:#f5f5ff;border:1px solid #e2e2f5;border-radius:6px;
			            padding:10px 14px;margin-bottom:24px}
			.meta-strip table{width:100%}
			.meta-strip td{padding:3px 12px 3px 0;font-size:9px;color:#555;white-space:nowrap}
			.meta-strip strong{color:#333}

			/* ── Stat cards ── */
			.cards{display:table;width:100%;border-collapse:separate;border-spacing:0;
			       margin-bottom:26px}
			.card{display:table-cell;border-radius:8px;padding:16px 10px;text-align:center;
			      vertical-align:middle}
			.card .n{font-size:24px;font-weight:800;line-height:1}
			.card .l{font-size:7.5px;text-transform:uppercase;letter-spacing:.5px;
			         margin-top:6px;color:#999;font-weight:600}
			.c-purple{background:#f0edff;border:1px solid #c5bbff}.c-purple .n{color:#696cff}
			.c-blue  {background:#e8f4fd;border:1px solid #90caf9}.c-blue   .n{color:#1565c0}
			.c-green {background:#e8f5e9;border:1px solid #a5d6a7}.c-green  .n{color:#2e7d32}
			.c-orange{background:#fff8e1;border:1px solid #ffcc80}.c-orange .n{color:#e65100}
			.c-red   {background:#fce4ec;border:1px solid #f48fb1}.c-red    .n{color:#c62828}
			.card-gap{display:table-cell;width:14px}

			/* ── Section heading ── */
			.sec{font-size:11.5px;font-weight:700;color:#696cff;
			     margin:28px 0 12px;padding-bottom:6px;
			     border-bottom:2px solid #e4e4ff;letter-spacing:.2px}

			/* ── Data table ── */
			table.dt{width:100%;border-collapse:collapse;font-size:9.5px}
			table.dt thead tr{background:#696cff}
			table.dt th{color:#fff;padding:10px 11px;font-size:9px;font-weight:600;
			            letter-spacing:.25px;text-align:left}
			table.dt td{padding:8px 11px;border-bottom:1px solid #eeeefc;vertical-align:middle}
			table.dt tbody tr:nth-child(even){background:#f7f7ff}
			table.dt tfoot td{background:#ededff;font-weight:700;
			                  border-top:2px solid #696cff;padding:9px 11px;font-size:10.5px}

			/* ── Badges ── */
			.b{display:inline-block;padding:3px 10px;border-radius:10px;
			   font-size:7.5px;font-weight:700;letter-spacing:.2px}
			.b-approved {background:#c8e6c9;color:#1b5e20}
			.b-submitted{background:#fff3cd;color:#856404}
			.b-rejected {background:#ffcdd2;color:#b71c1c}
			.b-draft    {background:#e0e0e0;color:#424242}

			/* ── Signature ── */
			.sig-wrap{display:table;width:100%;margin-top:40px;page-break-inside:avoid}
			.sig-l{display:table-cell;vertical-align:bottom}
			.sig-r{display:table-cell;vertical-align:bottom;text-align:right;width:270px}
			.sig-box{display:inline-block;border-top:1.5px solid #ccc;
			         padding-top:10px;min-width:230px}
			.sig-img{max-width:175px;max-height:70px;margin-bottom:6px}
			.sig-name{font-size:11.5px;font-weight:700;color:#1a1a2e}
			.sig-role{font-size:9px;color:#777;margin-top:3px}
			.sig-date{font-size:8.5px;color:#bbb;margin-top:2px}

			/* ── Footer ── */
			.foot{margin-top:24px;text-align:center;font-size:8px;color:#ccc;
			      border-top:1px solid #eee;padding-top:10px}
		</style></head><body>';

		// ── Outer padding wrapper (controls left/right/top/bottom margins) ──
		$html .= '<div style="padding:38px 52px 36px 52px;">';

		// ── Header ──
		$html .= '<div class="hdr">';
		$html .= '<div class="hdr-l">'.$logo_html.'</div>';
		$html .= '<div class="hdr-r"><h1>Timesheet Report</h1>';
		$html .= '<p class="sub">ERASMUS+ GREATER — Growing Rwanda Energy Awareness Through highER education</p>';
		$html .= '<p class="gen">Generated: '.date('F j, Y \a\t g:i A').'</p></div></div>';

		// ── Meta strip ──
		$html .= '<div class="meta-strip"><table><tr>';
		$html .= '<td><strong>Period:</strong> '.$from_label.' — '.$to_label.'</td>';
		$html .= '<td><strong>Institution:</strong> '.htmlspecialchars($partner_name).'</td>';
		$html .= '<td><strong>Status:</strong> '.$status_text.'</td>';
		$html .= '<td><strong>Records:</strong> '.count($timesheets).'</td>';
		$html .= '</tr></table></div>';

		// ── Stat cards ──
		$html .= '<table class="cards"><tr>';
		$html .= '<td class="card c-purple"><div class="n">'.count($timesheets).'</div><div class="l">Timesheets</div></td><td class="card-gap"></td>';
		$html .= '<td class="card c-blue"><div class="n">'.number_format($total_hours,1).'h</div><div class="l">Total Hours</div></td><td class="card-gap"></td>';
		$html .= '<td class="card c-green"><div class="n">'.$cnt_approved.'</div><div class="l">Approved</div></td><td class="card-gap"></td>';
		$html .= '<td class="card c-orange"><div class="n">'.$cnt_submitted.'</div><div class="l">Submitted</div></td><td class="card-gap"></td>';
		$html .= '<td class="card c-red"><div class="n">'.$cnt_rejected.'</div><div class="l">Rejected</div></td>';
		$html .= '</tr></table>';

		// ── Charts ──
		if(!empty($hours_by_partner)){
			$html .= '<div class="sec">Analytics Overview</div>';
			$boxStyle = 'border:1px solid #e0e0f5;border-radius:8px;padding:14px 12px;background:#fafafe;';
			$html .= '<div style="'.$boxStyle.'">'.$htmlBar.'</div>';
		}

		// ── Detailed table ──
		$html .= '<div class="sec">Timesheet Details</div>';
		$html .= '<table class="dt"><thead><tr>';
		$html .= '<th>#</th><th>Staff Member</th><th>Partner / Institution</th>';
		$html .= '<th>Period</th><th>Staff Category</th><th style="text-align:right">Total Hours</th>';
		$html .= '<th>Submitted</th><th>Status</th></tr></thead><tbody>';

		$i = 1;
		foreach($timesheets as $ts){
			$period    = $months[(int)$ts['month']].' '.$ts['year'];
			$submitted = !empty($ts['submitted_at']) ? date('d/m/Y', strtotime($ts['submitted_at'])) : '—';
			$bc        = 'b-'.strtolower($ts['status']);
			$html .= '<tr>';
			$html .= '<td style="color:#bbb">'.$i++.'</td>';
			$html .= '<td><strong>'.htmlspecialchars($ts['first_name'].' '.$ts['last_name']).'</strong></td>';
			$html .= '<td>'.htmlspecialchars($ts['partner_name'] ?: '—').'</td>';
			$html .= '<td>'.$period.'</td>';
			$html .= '<td>'.htmlspecialchars($ts['staff_category'] ?: '—').'</td>';
			$html .= '<td style="text-align:right;font-weight:700;color:#1976d2;">'.number_format($ts['total_hours'],1).'h</td>';
			$html .= '<td>'.$submitted.'</td>';
			$html .= '<td><span class="b '.$bc.'">'.ucfirst($ts['status']).'</span></td>';
			$html .= '</tr>';
		}
		$html .= '</tbody><tfoot><tr>';
		$html .= '<td colspan="5" style="text-align:right;color:#696cff;">GRAND TOTAL</td>';
		$html .= '<td style="text-align:right;color:#1976d2;font-size:11px;">'.number_format($total_hours,1).'h</td>';
		$html .= '<td colspan="2"></td></tr></tfoot></table>';

		// ── Hours by Institution breakdown (if multi-partner) ──
		if(count($hours_by_partner) > 1){
			$html .= '<div class="sec">Hours Breakdown by Institution</div>';
			$thStyle  = 'padding:9px 14px;text-align:left;font-size:9px;font-weight:600;letter-spacing:.2px;background:#696cff;color:#fff;border:1px solid #5a5ce8;';
			$thStyleR = $thStyle.'text-align:right;';
			$tdBase   = 'padding:8px 14px;border:1px solid #e4e4f5;vertical-align:middle;font-size:9.5px;';
			$tdR      = $tdBase.'text-align:right;';
			$html .= '<table style="width:65%;border-collapse:collapse;margin-top:6px;">';
			$html .= '<thead><tr>';
			$html .= '<th style="'.$thStyle.'">Institution</th>';
			$html .= '<th style="'.$thStyleR.'">Total Hours</th>';
			$html .= '<th style="'.$thStyleR.'">Share</th>';
			$html .= '</tr></thead><tbody>';
			$ri = 0;
			foreach($hours_by_partner as $pn => $ph){
				$pct = round($ph / max($total_hours,1) * 100, 1);
				$rowBg = ($ri++ % 2 === 0) ? '#ffffff' : '#f7f7ff';
				$html .= '<tr style="background:'.$rowBg.'">';
				$html .= '<td style="'.$tdBase.'">'.htmlspecialchars($pn).'</td>';
				$html .= '<td style="'.$tdR.'font-weight:700;color:#1565c0;">'.number_format($ph,1).' h</td>';
				$html .= '<td style="'.$tdR.'color:#696cff;">'.$pct.' %</td>';
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			$tfStyle = 'padding:9px 14px;border-top:2px solid #696cff;background:#ededff;font-weight:700;font-size:10px;';
			$html .= '<tfoot><tr>';
			$html .= '<td style="'.$tfStyle.'">Grand Total</td>';
			$html .= '<td style="'.$tfStyle.'text-align:right;color:#1565c0;">'.number_format($total_hours,1).' h</td>';
			$html .= '<td style="'.$tfStyle.'text-align:right;color:#696cff;">100 %</td>';
			$html .= '</tr></tfoot></table>';
		}

		// ── Signature ──
		if($signature && !empty($signature['signature_file'])){
			$sig_path = FCPATH . 'assets/signatures/' . $signature['signature_file'];
			$sig_img  = '';
			if(file_exists($sig_path)){
				$sig_img = 'data:image/png;base64,'.base64_encode(file_get_contents($sig_path));
			}
			$html .= '<div class="sig-wrap"><div class="sig-l"></div><div class="sig-r"><div class="sig-box">';
			if($sig_img) $html .= '<img src="'.$sig_img.'" class="sig-img" alt="Signature"><br>';
			$html .= '<div class="sig-name">'.htmlspecialchars($signature['signature_name']).'</div>';
			$html .= '<div class="sig-role">'.htmlspecialchars($signature['position']).'</div>';
			if(!empty($signature['organization']))
				$html .= '<div class="sig-role">'.htmlspecialchars($signature['organization']).'</div>';
			$html .= '<div class="sig-date">Date: '.date('F j, Y').'</div>';
			$html .= '</div></div></div>';
		}

		$html .= '<div class="foot">© '.date('Y').' GREATER — Erasmus+ Programme Project 101083081'
		       . ' &nbsp;·&nbsp; Generated automatically by the Financial Management System</div>';

		$html .= '</div>'; // end padding wrapper
		$html .= '</body></html>';
		return $html;
	}

	private function generate_report_html($expenses, $totals, $from_date, $to_date, $partner_name, $status){
		$status_text = empty($status) ? 'All Status' : ucfirst($status);

		// Determine which signature to use
		// Priority: 1) Common approver signature from approved expenses, 2) Current user's signature
		$signature = null;

		// Check if all approved expenses have the same approver signature
		$approver_signatures = array();
		foreach($expenses as $expense){
			if($expense['status'] == 'approved' && !empty($expense['approver_signature_name'])){
				$sig_key = $expense['approver_signature_name'] . '_' . $expense['approver_signature_file'];
				if(!isset($approver_signatures[$sig_key])){
					$approver_signatures[$sig_key] = array(
						'signature_name' => $expense['approver_signature_name'],
						'position' => $expense['approver_signature_position'],
						'organization' => $expense['approver_signature_organization'],
						'signature_file' => $expense['approver_signature_file']
					);
				}
			}
		}

		// If there's exactly one unique approver signature, use it
		if(count($approver_signatures) == 1){
			$signature = reset($approver_signatures);
		} else {
			// Otherwise, use current user's signature
			$user_id = $this->session->userdata('fms_user_id');
			$signature = $this->fmsm_enhanced->get_signature_by_user_id($user_id);
		}

		$logo_html = $this->get_pdf_logo_html('52px');

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Expense Report</title>';
		$html .= '<style>
			body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;font-size:10px;color:#333}
			.pdf-logo-bar{display:table;width:100%;border-bottom:3px solid #696cff;padding-bottom:10px;margin-bottom:18px;}
			.pdf-logo-bar-left{display:table-cell;vertical-align:middle;width:180px;}
			.pdf-logo-bar-right{display:table-cell;vertical-align:middle;text-align:right;}
			.pdf-logo-bar-right h2{font-size:16px;color:#333;margin:0;font-weight:700;}
			.pdf-logo-bar-right p{font-size:9px;color:#696cff;margin:2px 0 0 0;letter-spacing:.5px;}
			.info{margin-bottom:15px}.info table{width:100%}.info td{padding:3px 6px}
			table.expenses{width:100%;border-collapse:collapse;margin-top:10px}
			table.expenses th{background:#696cff;color:white;padding:7px 6px;text-align:left;font-size:9px}
			table.expenses td{border:1px solid #ddd;padding:5px 6px;font-size:9px}
			table.expenses tr:nth-child(even){background:#f5f5f5}
			.summary{margin-top:20px;padding:12px 15px;background:#f0f4ff;border-left:4px solid #696cff;border-radius:4px;}
			.summary h3{margin:0 0 10px 0;font-size:12px;color:#696cff;}
			.signature-section{margin-top:40px;page-break-inside:avoid}
			.signature-image{max-width:200px;max-height:80px;margin:15px 0}
			.footer{margin-top:30px;text-align:center;font-size:8px;color:#999;border-top:1px solid #eee;padding-top:10px;}
		</style>';
		$html .= '</head><body>';
		$html .= '<div class="pdf-logo-bar">';
		$html .= '<div class="pdf-logo-bar-left">'.$logo_html.'</div>';
		$html .= '<div class="pdf-logo-bar-right"><h2>Expense Report</h2><p>ERASMUS+ GREATER — Growing Rwanda Energy Awareness Through highER education</p></div>';
		$html .= '</div>';
		$html .= '<div class="info"><table><tr><td><strong>Report Period:</strong> '.date('F j, Y',strtotime($from_date)).' to '.date('F j, Y',strtotime($to_date)).'</td>';
		$html .= '<td><strong>Institution:</strong> '.htmlspecialchars($partner_name).'</td></tr><tr><td><strong>Status Filter:</strong> '.$status_text.'</td>';
		$html .= '<td><strong>Generated:</strong> '.date('F j, Y g:i A').'</td></tr></table></div>';
		$html .= '<table class="expenses"><thead><tr><th>#</th><th>Date</th><th>Partner</th><th>Description</th><th>Category</th>';
		$html .= '<th>Work Package</th><th>Amount (RWF/EUR/USD)</th><th>EUR Equiv.</th><th>Currency</th><th>Status</th></tr></thead><tbody>';

		$counter = 1;
		foreach($expenses as $expense){
			$status_badge = $expense['status'] == 'approved' ? 'Approved' : ($expense['status'] == 'rejected' ? 'Rejected' : 'Pending');
			$cur = strtoupper($expense['Currency']);

			// EUR equivalent cell
			if($cur === 'RWF' && !empty($expense['forex_rate']) && $expense['forex_rate'] > 0){
				$eur_equiv = $expense['Amount'] / $expense['forex_rate'];
				$eur_cell  = '<span style="color:#2e7d32;font-weight:600;">'.number_format($eur_equiv,2).' EUR</span>'
				           . '<br><span style="font-size:8px;color:#888;">'.number_format($expense['forex_rate'],2).' RWF=1€</span>';
			} elseif($cur === 'EUR'){
				$eur_cell = '<span style="color:#2e7d32;font-weight:600;">'.number_format($expense['Amount'],2).' EUR</span>';
			} else {
				$eur_cell = '<span style="color:#aaa;">—</span>';
			}

			$html .= '<tr><td>'.$counter++.'</td><td>'.date('d/m/Y',strtotime($expense['Date'])).'</td><td>'.htmlspecialchars($expense['Partner']).'</td>';
			$html .= '<td>'.htmlspecialchars($expense['ShortDescription']).'</td><td>'.htmlspecialchars($expense['Category']).'</td>';
			$html .= '<td>'.htmlspecialchars($expense['WorkPackage']).'</td>';
			$html .= '<td style="text-align:right;font-weight:600;">'.number_format($expense['Amount'],2).'</td>';
			$html .= '<td style="text-align:right;">'.$eur_cell.'</td>';
			$html .= '<td>'.$cur.'</td><td>'.$status_badge.'</td></tr>';
		}

		$html .= '</tbody></table>';

		// Summary section
		$html .= '<div class="summary"><h3>Summary</h3>';
		$html .= '<table style="width:100%;border-collapse:collapse;">';
		$html .= '<tr><td style="padding:4px 0;"><strong>Total Expenses:</strong></td><td>'.$totals['count'].' records</td></tr>';

		if($totals['RWF'] > 0){
			$html .= '<tr><td style="padding:4px 0;"><strong>Total Amount (RWF):</strong></td>';
			$html .= '<td><span style="font-size:12px;">'.number_format($totals['RWF'],2).' RWF</span>';
			if($totals['RWF_in_EUR'] > 0){
				$html .= ' &nbsp;=&nbsp; <span style="color:#2e7d32;font-size:13px;font-weight:bold;">'.number_format($totals['RWF_in_EUR'],2).' EUR</span>';
				if($totals['rwf_no_rate'] > 0){
					$html .= ' <span style="color:#c62828;font-size:8px;">('.$totals['rwf_no_rate'].' row(s) excluded — no rate found)</span>';
				}
			}
			$html .= '</td></tr>';
		}

		if($totals['EUR'] > 0){
			$combined_eur = $totals['EUR'] + $totals['RWF_in_EUR'];
			$html .= '<tr><td style="padding:4px 0;"><strong>Total Amount (EUR):</strong></td>';
			$html .= '<td><span style="font-size:12px;">'.number_format($totals['EUR'],2).' EUR</span></td></tr>';
			if($totals['RWF'] > 0){
				$html .= '<tr><td style="padding:4px 0;"><strong>Grand Total (EUR incl. converted RWF):</strong></td>';
				$html .= '<td><span style="color:#1a237e;font-size:14px;font-weight:bold;">'.number_format($combined_eur,2).' EUR</span></td></tr>';
			}
		}

		if($totals['USD'] > 0){
			$html .= '<tr><td style="padding:4px 0;"><strong>Total Amount (USD):</strong></td>';
			$html .= '<td>'.number_format($totals['USD'],2).' USD</td></tr>';
		}

		$html .= '</table></div>';

		// Add signature section if configured
		if($signature && isset($signature['signature_file'])){
			$signature_path = FCPATH . 'assets/signatures/' . $signature['signature_file'];

			// Convert image to base64 for embedding in PDF
			$signature_image = '';
			if(file_exists($signature_path)){
				$image_data = base64_encode(file_get_contents($signature_path));
				$signature_image = 'data:image/png;base64,' . $image_data;
			}

			$html .= '<div class="signature-section">';
			$html .= '<h5 style="font-size:12px;font-weight:600;margin-bottom:10px;">Approved By:</h5>';

			// Add signature image if available
			if(!empty($signature_image)){
				$html .= '<img src="'.$signature_image.'" class="signature-image" alt="Signature" />';
			}

			$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Name:</strong> '.htmlspecialchars($signature['signature_name']).'</p>';
			$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Position:</strong> '.htmlspecialchars($signature['position']).'</p>';

			if(!empty($signature['organization'])){
				$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Organization:</strong> '.htmlspecialchars($signature['organization']).'</p>';
			}

			$html .= '<p style="margin:5px 0;font-size:11px;"><strong>Date Signed:</strong> '.date('F j, Y').'</p>';
			$html .= '</div>';
		}

		$html .= '<div class="footer"><p>© '.date('Y').' GREATER - Erasmus+ Programme Project 101083081</p>';
		$html .= '<p>This report was generated automatically by the Financial Management System</p></div></body></html>';

		return $html;
	}

	public function users() {
		// Only admins and coordinators can view users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to view users.', 403);
		}

		$this->data['title'] = "FMS - Users & Staff";

		// Get users and staff based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['users'] = $this->fmsm_enhanced->get_all_users();
			$this->data['staff'] = $this->fmsm_enhanced->get_all_staff();
		} else {
			// Coordinators see only their institution's data
			$partner_id = $this->session->userdata('fms_partner_id');
			$this->data['users'] = $this->fmsm_enhanced->get_all_users($partner_id);
			$this->data['staff'] = $this->fmsm_enhanced->get_all_staff($partner_id);
		}

		$this->load->view('pages/users', $this->data);
	}

	public function newUser(){
		// Only admins and coordinators can create users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to create users.', 403);
		}

		$this->data["title"] = "FMS - New User";

		// Get all partners for super admin/admin
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['partners'] = $this->fmsm_enhanced->get_all_partners();
		}

		// Get all roles
		$this->data['roles'] = $this->fmsm_enhanced->get_all_roles();

		$this->load->view('pages/newuser', $this->data);
	}

	public function saveUser() {
		// Only admins and coordinators can create users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to create users.', 403);
		}

		// Validate inputs
		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
		$this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
		$this->form_validation->set_rules('role_id', 'Role', 'required');
		$this->form_validation->set_rules('position', 'Position', 'required|trim');

		if($this->form_validation->run() == FALSE){
			$this->session->set_flashdata('error', validation_errors());
			redirect('newUser');
			return;
		}

		// Get partner_id based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$partner_id = $this->input->post('partner_id');
		} else {
			$partner_id = $this->session->userdata("fms_partner_id");
		}

		// Role restrictions - coordinators can only create members
		$role_id = $this->input->post('role_id');
		if($this->auth_manager->is_coordinator() && $role_id != 4){
			$this->session->set_flashdata('error', 'Coordinators can only create Member accounts.');
			redirect('newUser');
			return;
		}

		// Create staff first
		$staff_data = array(
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'partner_id' => $partner_id,
			'position' => $this->input->post('position'),
			'status' => 'active'
		);

		$staff_id = $this->fmsm_enhanced->create_staff($staff_data);

		if($staff_id){
			// Create user account
			$user_data = array(
				'staff_id' => $staff_id,
				'email' => $this->input->post('email'),
				'password' => sha1($this->input->post('password')),
				'role_id' => $role_id,
				'status' => 'active'
			);

			if($this->fmsm_enhanced->create_user($user_data)){
				// Welcome email
				$role_names = [1=>'Super Admin',2=>'Admin',3=>'Institution Coordinator',4=>'Member'];
				$this->fms_mailer->account_created(
					$this->input->post('email'),
					$this->input->post('first_name') . ' ' . $this->input->post('last_name'),
					$this->input->post('password'),
					$role_names[$role_id] ?? 'Member'
				);
				$this->session->set_flashdata('success', 'User created successfully.');
				redirect('users');
			} else {
				$this->session->set_flashdata('error', 'Failed to create user account.');
				redirect('newUser');
			}
		} else {
			$this->session->set_flashdata('error', 'Failed to create staff record.');
			redirect('newUser');
		}
	}

	public function editUser($user_id){
		// Only admins and coordinators can edit users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to edit users.', 403);
		}

		$this->data["title"] = "FMS - Edit User";

		// Get user details
		$this->data['user'] = $this->fmsm_enhanced->get_user_by_id($user_id);

		if(!$this->data['user']){
			show_error('User not found.', 404);
		}

		// Coordinators can only edit users from their institution
		if($this->auth_manager->is_coordinator()){
			$partner_id = $this->session->userdata('fms_partner_id');
			if($this->data['user']['partner_id'] != $partner_id){
				show_error('Access Denied: You can only edit users from your institution.', 403);
			}
		}

		// Get all partners for super admin/admin
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['partners'] = $this->fmsm_enhanced->get_all_partners();
		}

		// Get all roles
		$this->data['roles'] = $this->fmsm_enhanced->get_all_roles();

		$this->load->view('pages/edituser', $this->data);
	}

	public function updateUser($user_id) {
		// Only admins and coordinators can update users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to update users.', 403);
		}

		// Validate inputs
		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
		$this->form_validation->set_rules('role_id', 'Role', 'required');
		$this->form_validation->set_rules('position', 'Position', 'required|trim');

		if($this->form_validation->run() == FALSE){
			$this->session->set_flashdata('error', validation_errors());
			redirect('editUser/'.$user_id);
			return;
		}

		$user = $this->fmsm_enhanced->get_user_by_id($user_id);

		if(!$user){
			show_error('User not found.', 404);
		}

		// Get partner_id based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$partner_id = $this->input->post('partner_id');
		} else {
			$partner_id = $this->session->userdata("fms_partner_id");
		}

		// Update staff
		$staff_data = array(
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'partner_id' => $partner_id,
			'position' => $this->input->post('position'),
			'status' => $this->input->post('status', TRUE) ?: 'active'
		);

		$this->fmsm_enhanced->update_staff($user['staff_id'], $staff_data);

		// Update user account
		$user_data = array(
			'email' => $this->input->post('email'),
			'role_id' => $this->input->post('role_id'),
			'level' => $this->input->post('level', TRUE) ?: 1,
			'status' => $this->input->post('status', TRUE) ?: 'active'
		);

		// Update password if provided
		$password = $this->input->post('password');
		if(!empty($password)){
			$user_data['password'] = sha1($password);
		}

		if($this->fmsm_enhanced->update_user($user_id, $user_data)){
			$this->session->set_flashdata('success', 'User updated successfully.');
			redirect('users');
		} else {
			$this->session->set_flashdata('error', 'Failed to update user.');
			redirect('editUser/'.$user_id);
		}
	}

	public function deleteUser($user_id){
		// Only super admin can delete users
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied: Only Super Admin can delete users.', 403);
		}

		// Prevent self-deletion
		if($user_id == $this->session->userdata('fms_user_id')){
			$this->session->set_flashdata('error', 'You cannot delete your own account.');
			redirect('users');
			return;
		}

		if($this->fmsm_enhanced->delete_user($user_id)){
			$this->session->set_flashdata('success', 'User deleted successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to delete user.');
		}

		redirect('users');
	}

	// ==================== STAFF MANAGEMENT METHODS ====================

	public function newStaff(){
		// Only admins and coordinators can create staff
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to create staff.', 403);
		}

		$this->data["title"] = "FMS - New Staff Member";

		// Get all partners for super admin/admin
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['partners'] = $this->fmsm_enhanced->get_all_partners();
		}

		$this->load->view('pages/newstaff', $this->data);
	}

	public function saveStaff(){
		// Only admins and coordinators can create staff
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to create staff.', 403);
		}

		// Validate inputs
		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|is_unique[staff.email]');
		$this->form_validation->set_rules('position', 'Position', 'required|trim');
		$this->form_validation->set_rules('partner_id', 'Partner/Institution', 'required|numeric');

		if($this->form_validation->run() === FALSE){
			$this->newStaff();
			return;
		}

		// Prepare staff data
		$staff_data = array(
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'phone' => $this->input->post('phone'),
			'position' => $this->input->post('position'),
			'department' => $this->input->post('department'),
			'partner_id' => $this->input->post('partner_id'),
			'greater_role' => $this->input->post('greater_role'),
			'employee_number' => $this->input->post('employee_number'),
			'hire_date' => $this->input->post('hire_date') ? $this->input->post('hire_date') : NULL,
			'status' => $this->input->post('status') ? $this->input->post('status') : 'active'
		);

		// Create staff member
		if($this->fmsm_enhanced->create_staff($staff_data)){
			$this->session->set_flashdata('success', 'Staff member created successfully.');
			redirect('users');
		} else {
			$this->session->set_flashdata('error', 'Failed to create staff member. Please try again.');
			$this->newStaff();
		}
	}

	public function editStaff($staff_id){
		// Only admins and coordinators can edit staff
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to edit staff.', 403);
		}

		$this->data["title"] = "FMS - Edit Staff Member";

		// Get staff data
		$this->data['staff'] = $this->fmsm_enhanced->get_staff_by_id($staff_id);

		if(empty($this->data['staff'])){
			show_404();
		}

		// Coordinators can only edit staff from their institution
		if($this->auth_manager->is_coordinator() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_super_admin()){
			$partner_id = $this->session->userdata('fms_partner_id');
			if($this->data['staff']['partner_id'] != $partner_id){
				show_error('Access Denied: You can only edit staff from your institution.', 403);
			}
		}

		// Get all partners for super admin/admin
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['partners'] = $this->fmsm_enhanced->get_all_partners();
		}

		$this->load->view('pages/editstaff', $this->data);
	}

	public function updateStaff($staff_id){
		// Only admins and coordinators can update staff
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to update staff.', 403);
		}

		// Get staff data to verify permissions
		$staff = $this->fmsm_enhanced->get_staff_by_id($staff_id);
		if(empty($staff)){
			show_404();
		}

		// Coordinators can only edit staff from their institution
		if($this->auth_manager->is_coordinator() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_super_admin()){
			$partner_id = $this->session->userdata('fms_partner_id');
			if($staff['partner_id'] != $partner_id){
				show_error('Access Denied: You can only edit staff from your institution.', 403);
			}
		}

		// Validate inputs
		$this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim');
		$this->form_validation->set_rules('position', 'Position', 'required|trim');
		$this->form_validation->set_rules('partner_id', 'Partner/Institution', 'required|numeric');

		// Check email uniqueness (exclude current staff)
		$email = $this->input->post('email');
		if($email != $staff['email']){
			$this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|is_unique[staff.email]');
		}

		if($this->form_validation->run() === FALSE){
			$this->editStaff($staff_id);
			return;
		}

		// Prepare staff data
		$staff_data = array(
			'first_name' => $this->input->post('first_name'),
			'last_name' => $this->input->post('last_name'),
			'email' => $this->input->post('email'),
			'phone' => $this->input->post('phone'),
			'position' => $this->input->post('position'),
			'department' => $this->input->post('department'),
			'partner_id' => $this->input->post('partner_id'),
			'greater_role' => $this->input->post('greater_role'),
			'employee_number' => $this->input->post('employee_number'),
			'hire_date' => $this->input->post('hire_date') ? $this->input->post('hire_date') : NULL,
			'status' => $this->input->post('status')
		);

		// Update staff member
		if($this->fmsm_enhanced->update_staff($staff_id, $staff_data)){
			$this->session->set_flashdata('success', 'Staff member updated successfully.');
			redirect('users');
		} else {
			$this->session->set_flashdata('error', 'Failed to update staff member. Please try again.');
			$this->editStaff($staff_id);
		}
	}

	public function deleteStaff($staff_id){
		// Only super admin and admin can delete staff
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin()){
			show_error('Access Denied: Only Super Admin and Admin can delete staff.', 403);
		}

		if($this->fmsm_enhanced->delete_staff($staff_id)){
			$this->session->set_flashdata('success', 'Staff member deleted successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to delete staff member.');
		}

		redirect('users');
	}

	// ==================== END STAFF MANAGEMENT METHODS ====================

	function generateUID() {
		// Generate a unique 5-digit ID
		$uniqueId = mt_rand(10000, 99999);
		
		return $uniqueId;
	}
	

	public function upload_picture(){
		$this->data["title"] = "STCard - Upload Picture";
		$this->data["regno"] = $this->uri->segment(3);
		$this->load->view('pages/upload_picture', $this->data);
	}

	public function picture_uploading(){
		$config = array(
			'upload_path' => FCPATH."assets/images/profile/",
			'allowed_types' => "jpg",
			'overwrite' => TRUE,
			'file_name' => $this->input->post("picture")
		);
		$this->load->library('upload', $config);
		if($this->upload->do_upload()) {
			$data = $this->upload->data();
			$this->phpAlert("Success!\\n\\nPicture have been uploaded successfully for ". $this->input->post("picture"));
			redirect('card/students', 'refresh');
		}
		else {
			$error = $this->upload->display_errors();
			$this->phpAlert("Error!\\n\\nPicture have been failed to upload for ". $this->input->post("picture")."\\n\\n".$error);
			redirect('card/students', 'refresh');
		}
	}


	public function phpAlert($msg){
		echo '<script type="text/javascript">alert("' . $msg . '")</script>';
	}


	function numberTowords($num)
	{

		$ones = array(
			0 =>"ZERO",
			1 => "ONE",
			2 => "TWO",
			3 => "THREE",
			4 => "FOUR",
			5 => "FIVE",
			6 => "SIX",
			7 => "SEVEN",
			8 => "EIGHT",
			9 => "NINE",
			10 => "TEN",
			11 => "ELEVEN",
			12 => "TWELVE",
			13 => "THIRTEEN",
			14 => "FOURTEEN",
			15 => "FIFTEEN",
			16 => "SIXTEEN",
			17 => "SEVENTEEN",
			18 => "EIGHTEEN",
			19 => "NINETEEN",
			"014" => "FOURTEEN"
		);
		$tens = array(
			0 => "ZERO",
			1 => "TEN",
			2 => "TWENTY",
			3 => "THIRTY",
			4 => "FORTY",
			5 => "FIFTY",
			6 => "SIXTY",
			7 => "SEVENTY",
			8 => "EIGHTY",
			9 => "NINETY"
		);
		$hundreds = array(
			"HUNDRED",
			"THOUSAND",
			"MILLION",
			"BILLION",
			"TRILLION",
			"QUARDRILLION"
		); /*limit t quadrillion */
		$num = number_format($num,2,".",",");
		$num_arr = explode(".",$num);
		$wholenum = $num_arr[0];
		$decnum = $num_arr[1];
		$whole_arr = array_reverse(explode(",",$wholenum));
		krsort($whole_arr,1);
		$rettxt = "";
		foreach($whole_arr as $key => $i){

			while(substr($i,0,1)=="0")
				$i=substr($i,1,5);
			if($i < 20){
				/* echo "getting:".$i; */
				$rettxt .= $ones[$i];
			}elseif($i < 100){
				if(substr($i,0,1)!="0")  $rettxt .= $tens[substr($i,0,1)];
				if(substr($i,1,1)!="0") $rettxt .= " ".$ones[substr($i,1,1)];
			}else{
				if(substr($i,0,1)!="0") $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0];
				if(substr($i,1,1)!="0")$rettxt .= " ".$tens[substr($i,1,1)];
				if(substr($i,2,1)!="0")$rettxt .= " ".$ones[substr($i,2,1)];
			}
			if($key > 0){
				$rettxt .= " ".$hundreds[$key]." ";
			}
		}
		if($decnum > 0){
			$rettxt .= " and ";
			if($decnum < 20){
				$rettxt .= $ones[$decnum];
			}elseif($decnum < 100){
				$rettxt .= $tens[substr($decnum,0,1)];
				$rettxt .= " ".$ones[substr($decnum,1,1)];
			}
		}
		return $rettxt;
	}

	// ==================== MONTHLY FINANCIAL REPORTS ====================

	/**
	 * List all monthly financial reports
	 * Access: Coordinators (own partner), Admins (all partners)
	 */
	public function monthlyReports() {
		// Check access
		if(!$this->auth_manager->is_super_admin() &&
		   !$this->auth_manager->is_admin() &&
		   !$this->auth_manager->is_coordinator()) {
			show_error('Access Denied', 403);
		}

		// Get partner ID
		if($this->auth_manager->is_coordinator()) {
			$partner_id = $this->session->userdata('fms_partner_id');
		} else {
			$partner_id = $this->input->get('partner_id');
		}

		// Get filters
		$status = $this->input->get('status');
		$year = $this->input->get('year');
		$month = $this->input->get('month');

		// Get reports
		if($partner_id) {
			$reports = $this->fmsm_enhanced->get_partner_monthly_reports($partner_id, $status);
		} else {
			$reports = array();
		}

		// Filter by year and month if specified
		if($year || $month) {
			$reports = array_filter($reports, function($report) use ($year, $month) {
				if($year && $report['report_year'] != $year) return false;
				if($month && $report['report_month'] != $month) return false;
				return true;
			});
		}

		$this->data['reports'] = $reports;
		$this->data['partner_id'] = $partner_id;
		$this->data['selected_status'] = $status;
		$this->data['selected_year'] = $year;
		$this->data['selected_month'] = $month;
		$this->data['title'] = 'Monthly Financial Reports';
		$this->data['page'] = 'monthly_reports';
		$this->load->view('pages/monthly_reports', $this->data);
	}

	/**
	 * View monthly report details
	 * Access: Coordinators (own partner), Admins (all partners)
	 */
	public function viewMonthlyReport($report_id) {
		$report = $this->fmsm_enhanced->get_monthly_report($report_id);

		if(!$report) {
			show_error('Report not found', 404);
		}

		// Check access
		if(!$this->can_access_report($report)) {
			show_error('Access Denied', 403);
		}

		// Get partner info
		$partner = $this->db->select('name')->where('partner_id', $report['partner_id'])->get('partners')->row_array();

		// Get user info for created_by, submitted_by, approved_by
		$created_by = $this->db->select('s.first_name, s.last_name, u.email')
			->from('users u')
			->join('staff s', 's.staff_id = u.staff_id', 'left')
			->where('u.user_id', $report['created_by'])
			->get()
			->row_array();
		$submitted_by = null;
		$approved_by = null;

		if($report['submitted_by']) {
			$submitted_by = $this->db->select('s.first_name, s.last_name, u.email')
				->from('users u')
				->join('staff s', 's.staff_id = u.staff_id', 'left')
				->where('u.user_id', $report['submitted_by'])
				->get()
				->row_array();
		}
		if($report['approved_by']) {
			$approved_by = $this->db->select('s.first_name, s.last_name, u.email')
				->from('users u')
				->join('staff s', 's.staff_id = u.staff_id', 'left')
				->where('u.user_id', $report['approved_by'])
				->get()
				->row_array();
		}

		$this->data['report'] = $report;
		$this->data['partner'] = $partner;
		$this->data['created_by'] = $created_by;
		$this->data['submitted_by'] = $submitted_by;
		$this->data['approved_by'] = $approved_by;
		$this->data['title'] = 'Financial Report - ' . $report['report_name'];
		$this->data['page'] = 'monthly_report_detail';
		$this->load->view('pages/monthly_report_detail', $this->data);
	}

	/**
	 * Generate monthly report for specified month/year
	 * Access: Coordinators, Admins
	 */
	public function generateMonthlyReport($partner_id = null, $year = null, $month = null) {
		// Check access
		if(!$this->auth_manager->is_coordinator() &&
		   !$this->auth_manager->is_admin() &&
		   !$this->auth_manager->is_super_admin()) {
			show_error('Access Denied', 403);
		}

		// Get from POST if not in URL
		if($this->input->post()) {
			$partner_id = $this->input->post('partner_id');
			$year = $this->input->post('year');
			$month = $this->input->post('month');
		}

		// Validate inputs
		if(!$partner_id || !$year || !$month) {
			$this->session->set_flashdata('error', 'Partner, year, and month are required');
			redirect('monthlyReports');
		}

		// Check coordinator access
		if($this->auth_manager->is_coordinator()) {
			$user_partner = $this->session->userdata('fms_partner_id');
			if($partner_id != $user_partner) {
				show_error('Access Denied', 403);
			}
		}

		// Create report
		$report_id = $this->fmsm_enhanced->create_monthly_report(
			$partner_id,
			$year,
			$month,
			$this->session->userdata('fms_user_id')
		);

		if($report_id) {
			$this->session->set_flashdata('success', 'Monthly report generated successfully');
			redirect('viewMonthlyReport/' . $report_id);
		} else {
			$this->session->set_flashdata('error', 'No approved expenses found for this month/partner');
			redirect('monthlyReports');
		}
	}

	/**
	 * Submit monthly report for approval
	 * Access: Coordinators (own reports), Admins
	 */
	public function submitMonthlyReport($report_id) {
		// Check access
		if(!$this->auth_manager->is_coordinator() &&
		   !$this->auth_manager->is_admin() &&
		   !$this->auth_manager->is_super_admin()) {
			show_error('Access Denied', 403);
		}

		$report = $this->fmsm_enhanced->get_monthly_report($report_id);
		if(!$report) {
			show_error('Report not found', 404);
		}

		// Check coordinator access
		if($this->auth_manager->is_coordinator()) {
			$user_partner = $this->session->userdata('fms_partner_id');
			if($report['partner_id'] != $user_partner) {
				show_error('Access Denied', 403);
			}
		}

		// Check report is in draft status
		if($report['status'] != 'draft' && $report['status'] != 'rejected') {
			$this->session->set_flashdata('error', 'Only draft or rejected reports can be submitted');
			redirect('viewMonthlyReport/' . $report_id);
		}

		// Submit report
		$this->fmsm_enhanced->submit_monthly_report(
			$report_id,
			$this->session->userdata('fms_user_id')
		);

		// Notify admins
		$submitter = $this->fmsm_enhanced->get_user_by_id($this->session->userdata('fms_user_id'));
		$submitter_name = $submitter ? trim(($submitter['first_name'] ?? '') . ' ' . ($submitter['last_name'] ?? '')) : 'Coordinator';
		$fresh_report = $this->fmsm_enhanced->get_monthly_report($report_id);
		$admin_emails = $this->fmsm_enhanced->get_admin_emails();
		if($fresh_report) $this->fms_mailer->monthly_report_submitted($fresh_report, $submitter_name, $admin_emails);

		$this->session->set_flashdata('success', 'Report submitted for approval');
		redirect('viewMonthlyReport/' . $report_id);
	}

	/**
	 * Approve monthly report (Admin only)
	 * Access: Admins
	 */
	public function approveMonthlyReport($report_id) {
		// Check admin access
		if(!$this->auth_manager->is_admin() &&
		   !$this->auth_manager->is_super_admin()) {
			show_error('Access Denied', 403);
		}

		$report = $this->fmsm_enhanced->get_monthly_report($report_id);
		if(!$report) {
			show_error('Report not found', 404);
		}

		// Check report is submitted
		if($report['status'] != 'submitted') {
			$this->session->set_flashdata('error', 'Only submitted reports can be approved');
			redirect('viewMonthlyReport/' . $report_id);
		}

		$notes = $this->input->post('notes', true);

		// Approve report
		$this->fmsm_enhanced->approve_monthly_report(
			$report_id,
			$this->session->userdata('fms_user_id'),
			$notes
		);

		// Notify coordinator
		$fresh_report = $this->fmsm_enhanced->get_monthly_report($report_id);
		if($fresh_report && !empty($fresh_report['partner_id'])){
			$coord_emails = $this->fmsm_enhanced->get_coordinator_emails($fresh_report['partner_id']);
			foreach($coord_emails as $cemail){
				$coord = $this->fmsm_enhanced->get_user_by_email($cemail);
				$cname = $coord ? trim(($coord['first_name'] ?? '') . ' ' . ($coord['last_name'] ?? '')) : '';
				$this->fms_mailer->monthly_report_approved($fresh_report, $cemail, $cname);
			}
		}
		$this->session->set_flashdata('success', 'Report approved successfully');
		redirect('viewMonthlyReport/' . $report_id);
	}

	/**
	 * Reject monthly report (Admin only)
	 * Access: Admins
	 */
	public function rejectMonthlyReport($report_id) {
		// Check admin access
		if(!$this->auth_manager->is_admin() &&
		   !$this->auth_manager->is_super_admin()) {
			show_error('Access Denied', 403);
		}

		$report = $this->fmsm_enhanced->get_monthly_report($report_id);
		if(!$report) {
			show_error('Report not found', 404);
		}

		// Check report is submitted
		if($report['status'] != 'submitted') {
			$this->session->set_flashdata('error', 'Only submitted reports can be rejected');
			redirect('viewMonthlyReport/' . $report_id);
		}

		$rejection_comments = $this->input->post('rejection_comments', true);

		if(!$rejection_comments) {
			$this->session->set_flashdata('error', 'Rejection comments are required');
			redirect('viewMonthlyReport/' . $report_id);
		}

		// Reject report
		$this->fmsm_enhanced->reject_monthly_report($report_id, $rejection_comments);

		// Notify coordinator
		$fresh_report = $this->fmsm_enhanced->get_monthly_report($report_id);
		if($fresh_report && !empty($fresh_report['partner_id'])){
			$coord_emails = $this->fmsm_enhanced->get_coordinator_emails($fresh_report['partner_id']);
			foreach($coord_emails as $cemail){
				$coord = $this->fmsm_enhanced->get_user_by_email($cemail);
				$cname = $coord ? trim(($coord['first_name'] ?? '') . ' ' . ($coord['last_name'] ?? '')) : '';
				$this->fms_mailer->monthly_report_rejected($fresh_report, $cemail, $cname, $rejection_comments);
			}
		}
		$this->session->set_flashdata('success', 'Report rejected. User can edit and resubmit');
		redirect('viewMonthlyReport/' . $report_id);
	}

	/**
	 * Helper method: Check if user can access this report
	 */
	private function can_access_report($report) {
		if($this->auth_manager->is_admin() || $this->auth_manager->is_super_admin()) {
			return true;
		}

		if($this->auth_manager->is_coordinator()) {
			$user_partner = $this->session->userdata('fms_partner_id');
			return $report['partner_id'] == $user_partner;
		}

		return false;
	}

	// ==================== END MONTHLY FINANCIAL REPORTS ====================

	// ==================== REPORT SIGNATURES MANAGEMENT ====================

	public function reportSignatures(){
		// Coordinators, admins, and super admins can manage signatures
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to manage report signatures.', 403);
		}

		$this->data['title'] = "FMS - Report Signatures";

		// Get current user's signature
		$user_id = $this->session->userdata('fms_user_id');
		$this->data['current_signature'] = $this->fmsm_enhanced->get_signature_by_user_id($user_id);

		$this->load->view('pages/reportsignatures', $this->data);
	}

	public function saveSignature(){
		// Coordinators, admins, and super admins can manage signatures
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to manage report signatures.', 403);
		}

		// Get current user ID
		$user_id = $this->session->userdata('fms_user_id');

		// Validate inputs
		$signature_name = $this->input->post('signature_name');
		$position = $this->input->post('position');
		$organization = $this->input->post('organization');

		if(empty($signature_name) || empty($position)){
			$this->session->set_flashdata('error', 'Name and position are required.');
			redirect('reportSignatures');
			return;
		}

		// Get existing signature to check if file exists
		$existing_signature = $this->fmsm_enhanced->get_signature_by_user_id($user_id);
		$signature_file = isset($existing_signature['signature_file']) ? $existing_signature['signature_file'] : '';

		// Handle file upload if provided
		if(isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] == 0){
			// Validate file type
			$file_type = $_FILES['signature_file']['type'];
			$file_extension = strtolower(pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION));

			if($file_extension != 'png' || $file_type != 'image/png'){
				$this->session->set_flashdata('error', 'Only PNG images are allowed for signatures.');
				redirect('reportSignatures');
				return;
			}

			// Create signatures directory if it doesn't exist
			$signatures_dir = FCPATH . 'assets/signatures/';
			if(!is_dir($signatures_dir)){
				mkdir($signatures_dir, 0777, true);
			}

			// Delete old signature file if exists
			if(!empty($signature_file) && file_exists($signatures_dir . $signature_file)){
				unlink($signatures_dir . $signature_file);
			}

			// Generate unique filename
			$signature_file = 'signature_' . $user_id . '_' . time() . '.png';
			$file_path = $signatures_dir . $signature_file;

			// Move uploaded file
			if(!move_uploaded_file($_FILES['signature_file']['tmp_name'], $file_path)){
				$this->session->set_flashdata('error', 'Failed to upload signature file.');
				redirect('reportSignatures');
				return;
			}
		} else if(empty($signature_file)){
			// No file uploaded and no existing file
			$this->session->set_flashdata('error', 'Signature image is required.');
			redirect('reportSignatures');
			return;
		}

		// Save signature configuration in system_settings table
		$signature_key = 'report_signature_' . $user_id;
		$signature_value = json_encode([
			'user_id' => $user_id,
			'signature_name' => $signature_name,
			'position' => $position,
			'organization' => $organization,
			'signature_file' => $signature_file
		]);

		if($this->fmsm_enhanced->save_setting($signature_key, $signature_value, 'json', 'Report signature configuration for user ' . $user_id)){
			$this->session->set_flashdata('success', 'Signature saved successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to save signature configuration.');
		}

		redirect('reportSignatures');
	}

	// ==================== END REPORT SIGNATURES MANAGEMENT ====================

	// ============================================================
	// OTHER FILES – WORK PACKAGES, FILES & VERSIONS
	// ============================================================

	/**
	 * Folder view – 7 Work Package cards.
	 * Accessible by Super Admin and Local Coordinators only.
	 */
	public function otherFiles(){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to view Other Files.', 403);
		}

		$this->data['title'] = 'FMS - Other Files';

		$user_id    = $this->session->userdata('fms_user_id');
		$partner_id = $this->session->userdata('fms_partner_id');

		if($this->auth_manager->is_super_admin()){
			$this->data['work_packages'] = $this->fmsm_enhanced->get_work_packages();
		} else {
			// Coordinator sees counts for their own partner only
			$this->data['work_packages'] = $this->fmsm_enhanced->get_work_packages($partner_id);
		}

		$this->load->view('pages/otherfiles', $this->data);
	}

	/**
	 * File list view for a specific Work Package.
	 */
	public function otherFilesWP($wp_id){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to view Other Files.', 403);
		}

		$wp = $this->fmsm_enhanced->get_work_package_by_id($wp_id);
		if(!$wp){
			show_error('Work Package not found.', 404);
		}

		$partner_id = $this->session->userdata('fms_partner_id');
		$user_id    = $this->session->userdata('fms_user_id');

		if($this->auth_manager->is_super_admin()){
			$files = $this->fmsm_enhanced->get_files_by_wp($wp_id);
		} else {
			// Coordinators see only files they uploaded
			$files = $this->fmsm_enhanced->get_files_by_wp($wp_id, $partner_id, $user_id);
		}

		$this->data['title']        = 'FMS - Other Files / ' . $wp['code'];
		$this->data['wp']           = $wp;
		$this->data['files']        = $files;
		$this->data['partner_id']   = $partner_id;

		$this->load->view('pages/otherfilesWP', $this->data);
	}

	/**
	 * Handle file upload (AJAX POST → JSON).
	 * - If file_id is provided, creates a new version of an existing file.
	 * - Otherwise creates a new file record + v1.
	 */
	public function uploadOtherFile(){
		header('Content-Type: application/json');
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			echo json_encode(['success' => false, 'message' => 'Access denied.']);
			return;
		}

		$wp_id       = (int)$this->input->post('wp_id');
		$file_id     = (int)$this->input->post('file_id');   // 0 → new file
		$description = $this->input->post('description');
		$display_name = trim($this->input->post('display_name'));
		$user_id     = $this->session->userdata('fms_user_id');
		$partner_id  = $this->session->userdata('fms_partner_id');

		// Validate WP
		if(!$this->fmsm_enhanced->get_work_package_by_id($wp_id)){
			echo json_encode(['success' => false, 'message' => 'Invalid work package.']);
			return;
		}

		// Validate file was uploaded
		if(empty($_FILES['upload_file']['name'])){
			echo json_encode(['success' => false, 'message' => 'No file selected.']);
			return;
		}

		$allowed_ext = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar','png','jpg','jpeg'];
		$file_tmp    = $_FILES['upload_file']['tmp_name'];
		$file_orig   = $_FILES['upload_file']['name'];
		$file_size   = $_FILES['upload_file']['size'];
		$file_mime   = $_FILES['upload_file']['type'];
		$ext         = strtolower(pathinfo($file_orig, PATHINFO_EXTENSION));

		if(!in_array($ext, $allowed_ext)){
			echo json_encode(['success' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_ext)]);
			return;
		}
		if($file_size > 20 * 1024 * 1024){
			echo json_encode(['success' => false, 'message' => 'File too large (max 20 MB).']);
			return;
		}

		// Get partner short name for filename
		$partner_info = $this->fmsm_enhanced->get_all_partners();
		$partner_short = 'UNK';
		foreach($partner_info as $p){
			if($p['partner_id'] == $partner_id){
				$partner_short = strtoupper($p['short_name'] ?? 'UNK');
				break;
			}
		}
		// Super admin uploads keep partner from existing file or use SADMIN
		if($this->auth_manager->is_super_admin() && $file_id > 0){
			$existing = $this->fmsm_enhanced->get_other_file_by_id($file_id);
			$partner_short = $existing ? strtoupper($existing['partner_short_name'] ?? 'SADMIN') : 'SADMIN';
			$partner_id    = $existing ? $existing['partner_id'] : $partner_id;
		} elseif($this->auth_manager->is_super_admin() && $file_id == 0){
			$partner_short = 'SADMIN';
		}

		// Determine version number
		if($file_id > 0){
			$version_num = $this->fmsm_enhanced->get_next_version_number($file_id);
		} else {
			$version_num = 1;
		}

		// Build stored filename: PARTNER_YYYYMMDD_HHMMSS_vN.ext
		$datetime    = date('Ymd_His');
		$stored_name = $partner_short . '_' . $datetime . '_v' . $version_num . '.' . $ext;
		$dest_path   = FCPATH . 'assets/otherfiles/' . $stored_name;

		if(!move_uploaded_file($file_tmp, $dest_path)){
			echo json_encode(['success' => false, 'message' => 'Failed to save file. Check server permissions.']);
			return;
		}

		// Create file group record if new upload
		if($file_id == 0){
			if(empty($display_name)){
				$display_name = pathinfo($file_orig, PATHINFO_FILENAME);
			}
			$file_id = $this->fmsm_enhanced->create_other_file([
				'wp_id'        => $wp_id,
				'partner_id'   => $partner_id,
				'display_name' => $display_name,
				'description'  => $description,
				'uploaded_by'  => $user_id,
			]);
		}

		// Create version record
		$this->fmsm_enhanced->create_file_version([
			'file_id'        => $file_id,
			'version_number' => $version_num,
			'stored_name'    => $stored_name,
			'original_ext'   => $ext,
			'file_size'      => $file_size,
			'mime_type'      => $file_mime,
			'description'    => $description,
			'uploaded_by'    => $user_id,
		]);

		echo json_encode([
			'success'      => true,
			'message'      => 'File uploaded successfully as version v' . $version_num . '.',
			'stored_name'  => $stored_name,
			'version'      => $version_num,
			'file_id'      => $file_id,
		]);
	}

	/**
	 * Return JSON list of all versions for a file (AJAX).
	 */
	public function getFileVersions($file_id){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			echo json_encode(['success' => false, 'message' => 'Access denied.']);
			return;
		}

		$file     = $this->fmsm_enhanced->get_other_file_by_id($file_id);
		$versions = $this->fmsm_enhanced->get_file_versions($file_id);

		echo json_encode([
			'success'  => true,
			'file'     => $file,
			'versions' => $versions,
		]);
	}

	/**
	 * Serve a file for download.
	 */
	public function downloadOtherFileVersion($version_id){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied.', 403);
		}

		$version = $this->fmsm_enhanced->get_version_by_id($version_id);
		if(!$version){
			show_error('Version not found.', 404);
		}

		$file_path = FCPATH . 'assets/otherfiles/' . $version['stored_name'];
		if(!file_exists($file_path)){
			show_error('File not found on server.', 404);
		}

		$this->load->helper('download');
		force_download($file_path, NULL);
	}

	/**
	 * Delete a file group and all its versions (Super Admin only).
	 */
	public function deleteOtherFile($file_id){
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied: Only Super Admins can delete files.', 403);
		}

		// Delete physical files from disk
		$versions = $this->fmsm_enhanced->get_file_versions($file_id);
		foreach($versions as $v){
			$path = FCPATH . 'assets/otherfiles/' . $v['stored_name'];
			if(file_exists($path)){
				unlink($path);
			}
		}

		if($this->fmsm_enhanced->delete_other_file($file_id)){
			$this->session->set_flashdata('success', 'File and all its versions deleted successfully.');
		} else {
			$this->session->set_flashdata('error', 'Failed to delete file.');
		}

		// Return to referrer or generic WP list
		$ref = $this->input->server('HTTP_REFERER');
		redirect($ref ? $ref : 'otherFiles');
	}

	// ==================== END OTHER FILES ====================

	// ============================================================
	// OTHER FILE COMMENTS
	// ============================================================

	/** Return JSON comments for a file */
	public function getFileComments($file_id){
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_coordinator()){
			echo json_encode(['success' => false, 'message' => 'Access denied.']); return;
		}
		$comments = $this->fmsm_enhanced->get_comments_by_file($file_id);
		echo json_encode(['success' => true, 'comments' => $comments]);
	}

	/** Add a comment (Super Admin only) */
	public function addFileComment(){
		if(!$this->auth_manager->is_super_admin()){
			echo json_encode(['success' => false, 'message' => 'Only Super Admins can add comments.']); return;
		}
		$file_id = (int)$this->input->post('file_id');
		$comment = trim($this->input->post('comment'));
		if(!$file_id || empty($comment)){
			echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']); return;
		}
		$id = $this->fmsm_enhanced->add_file_comment([
			'file_id'  => $file_id,
			'user_id'  => $this->session->userdata('fms_user_id'),
			'comment'  => $comment,
		]);
		if($id){
			$comments = $this->fmsm_enhanced->get_comments_by_file($file_id);
			echo json_encode(['success' => true, 'message' => 'Comment added.', 'comments' => $comments]);
		} else {
			echo json_encode(['success' => false, 'message' => 'Failed to save comment.']);
		}
	}

	/** Delete a comment (Super Admin only) */
	public function deleteFileComment($comment_id){
		if(!$this->auth_manager->is_super_admin()){
			echo json_encode(['success' => false, 'message' => 'Access denied.']); return;
		}
		$ok = $this->fmsm_enhanced->delete_file_comment($comment_id);
		echo json_encode(['success' => $ok]);
	}

	// ==================== END COMMENTS ====================

	// ============================================================
	// PROFILE
	// ============================================================

	public function profile(){
		$this->data['title'] = 'FMS - My Profile';

		$user_id  = $this->session->userdata('fms_user_id');
		$staff_id = $this->session->userdata('fms_staff_id');

		$this->data['profile_user']  = $this->fmsm_enhanced->get_user_by_id($user_id);
		$this->data['profile_staff'] = $this->fmsm_enhanced->get_staff_by_id($staff_id);

		$this->load->view('pages/profile', $this->data);
	}

	/** Update personal info (name, phone, position) */
	public function updateProfile(){
		$user_id  = $this->session->userdata('fms_user_id');
		$staff_id = $this->session->userdata('fms_staff_id');

		$first_name = trim($this->input->post('first_name'));
		$last_name  = trim($this->input->post('last_name'));
		$phone      = trim($this->input->post('phone'));
		$department = trim($this->input->post('department'));

		if(empty($first_name) || empty($last_name)){
			$this->session->set_flashdata('profile_error', 'First name and last name are required.');
			redirect('profile');
		}

		$this->db->where('staff_id', $staff_id)->update('staff', [
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'phone'      => $phone,
			'department' => $department,
		]);

		// Refresh session name fields
		$this->session->set_userdata([
			'fms_fname' => $first_name,
			'fms_lname' => $last_name,
			'fms_name'  => $first_name . ' ' . $last_name,
		]);

		$this->session->set_flashdata('profile_success', 'Profile updated successfully.');
		redirect('profile');
	}

	/** Change password */
	public function updatePassword(){
		$user_id      = $this->session->userdata('fms_user_id');
		$current_pass = $this->input->post('current_password');
		$new_pass     = $this->input->post('new_password');
		$confirm_pass = $this->input->post('confirm_password');

		if(empty($current_pass) || empty($new_pass) || empty($confirm_pass)){
			$this->session->set_flashdata('password_error', 'All password fields are required.');
			redirect('profile#change-password');
		}

		if($new_pass !== $confirm_pass){
			$this->session->set_flashdata('password_error', 'New password and confirmation do not match.');
			redirect('profile#change-password');
		}

		if(strlen($new_pass) < 6){
			$this->session->set_flashdata('password_error', 'New password must be at least 6 characters.');
			redirect('profile#change-password');
		}

		// Verify current password
		$row = $this->db->where('user_id', $user_id)
		               ->where('password', sha1($current_pass))
		               ->get('users')->row_array();

		if(!$row){
			$this->session->set_flashdata('password_error', 'Current password is incorrect.');
			redirect('profile#change-password');
		}

		$this->db->where('user_id', $user_id)->update('users', ['password' => sha1($new_pass)]);

		$this->session->set_flashdata('password_success', 'Password changed successfully.');
		redirect('profile#change-password');
	}

	// ==================== END PROFILE ====================

	// ============================================
	// FOREX EXCHANGE
	// ============================================

	public function forexExchange(){
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied', 403);
		}
		$this->data['title']  = 'FMS - Forex Exchange';
		$this->data['rates']  = $this->fmsm_enhanced->get_all_forex_rates();
		$this->data['stats']  = $this->fmsm_enhanced->get_forex_stats();
		$this->load->view('pages/forexexchange', $this->data);
	}

	public function saveForexRate(){
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied', 403);
		}
		$date    = $this->input->post('rate_date', TRUE);
		$rate    = $this->input->post('rwf_per_eur', TRUE);
		$user_id = $this->session->userdata('fms_user_id');

		if(empty($date) || empty($rate)){
			$this->session->set_flashdata('error', 'Date and rate are required.');
			redirect('forexExchange');
			return;
		}
		if(!is_numeric($rate) || floatval($rate) <= 0){
			$this->session->set_flashdata('error', 'Rate must be a positive number.');
			redirect('forexExchange');
			return;
		}
		$date_obj = DateTime::createFromFormat('Y-m-d', $date);
		if(!$date_obj){
			$this->session->set_flashdata('error', 'Invalid date format.');
			redirect('forexExchange');
			return;
		}

		$this->fmsm_enhanced->save_forex_rate($date, floatval($rate), $user_id);
		$this->session->set_flashdata('success', 'Rate for ' . date('d M Y', strtotime($date)) . ' saved successfully.');
		redirect('forexExchange');
	}

	public function uploadForexExcel(){
		if(!$this->auth_manager->is_super_admin()){
			echo json_encode(['success' => false, 'message' => 'Access denied.']); return;
		}
		if(!$this->input->is_ajax_request()){
			show_error('Invalid request', 403);
		}
		if(empty($_FILES['forex_file']['name'])){
			echo json_encode(['success' => false, 'message' => 'No file uploaded.']); return;
		}
		if($_FILES['forex_file']['error'] !== UPLOAD_ERR_OK){
			echo json_encode(['success' => false, 'message' => 'Upload error.']); return;
		}

		require_once FCPATH . 'vendor/autoload.php';

		try {
			$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES['forex_file']['tmp_name']);
			$sheet       = $spreadsheet->getActiveSheet();
			$rows        = [];

			foreach($sheet->getRowIterator(2) as $row){ // skip header row 1
				$cells = $row->getCellIterator();
				$cells->setIterateOnlyExistingCells(false);
				$cols = [];
				foreach($cells as $cell){ $cols[] = $cell->getValue(); }

				$raw_date = $cols[0] ?? '';
				$raw_rate = $cols[1] ?? '';

				if(empty($raw_date) && empty($raw_rate)) continue; // blank row

				// Parse date — could be Excel serial or string
				$date_str = '';
				if(is_numeric($raw_date) && $raw_date > 0){
					$dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw_date);
					$date_str = $dt->format('Y-m-d');
				} elseif(!empty($raw_date)){
					// Try common string formats
					$dt = date_create($raw_date);
					$date_str = $dt ? $dt->format('Y-m-d') : '';
				}

				if(empty($date_str) || !is_numeric($raw_rate) || floatval($raw_rate) <= 0) continue;

				$rows[] = ['date' => $date_str, 'rate' => floatval($raw_rate)];
			}

			if(empty($rows)){
				echo json_encode(['success' => false, 'message' => 'No valid rows found in the file. Make sure column A = Date and column B = Rate (RWF per EUR).']);
				return;
			}

			$user_id = $this->session->userdata('fms_user_id');
			$result  = $this->fmsm_enhanced->bulk_save_forex_rates($rows, $user_id);

			$msg = $result['inserted'] . ' rate(s) added, ' . $result['updated'] . ' updated.';
			if(!empty($result['errors'])){
				$msg .= ' Skipped ' . count($result['errors']) . ' invalid row(s).';
			}
			echo json_encode(['success' => true, 'message' => $msg]);

		} catch(Exception $e){
			echo json_encode(['success' => false, 'message' => 'Could not read file: ' . $e->getMessage()]);
		}
	}

	public function deleteForexRate($id){
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied', 403);
		}
		$this->fmsm_enhanced->delete_forex_rate($id);
		$this->session->set_flashdata('success', 'Rate deleted.');
		redirect('forexExchange');
	}

	public function downloadForexTemplate(){
		if(!$this->auth_manager->is_super_admin()){
			show_error('Access Denied', 403);
		}
		require_once FCPATH . 'vendor/autoload.php';

		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle('Forex Rates');

		// Header row
		$sheet->setCellValue('A1', 'Date (YYYY-MM-DD)');
		$sheet->setCellValue('B1', 'Rate (1 EUR = X RWF)');

		// Style header
		$headerStyle = [
			'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
			'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '696cff']],
		];
		$sheet->getStyle('A1:B1')->applyFromArray($headerStyle);
		$sheet->getColumnDimension('A')->setWidth(22);
		$sheet->getColumnDimension('B')->setWidth(24);

		// Sample rows
		$samples = [
			['2023-01-01', 1120.50],
			['2023-01-02', 1121.00],
			['2023-01-03', 1119.75],
		];
		$r = 2;
		foreach($samples as $s){
			$sheet->setCellValue('A'.$r, $s[0]);
			$sheet->setCellValue('B'.$r, $s[1]);
			$r++;
		}

		// Freeze header
		$sheet->freezePane('A2');

		$writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
		$filename = 'forex_rates_template.xlsx';

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
		exit;
	}

	public function getForexRate(){
		$date = $this->input->get('date', TRUE);
		if(empty($date)){
			echo json_encode(['success' => false, 'message' => 'No date provided.']);
			return;
		}
		$row = $this->db->query(
			"SELECT rwf_per_eur, rate_date FROM forex_rates WHERE rate_date <= ? ORDER BY rate_date DESC LIMIT 1",
			[$date]
		)->row_array();

		if($row){
			echo json_encode(['success' => true, 'rate' => (float)$row['rwf_per_eur'], 'rate_date' => $row['rate_date']]);
		} else {
			echo json_encode(['success' => false, 'message' => 'No exchange rate found for this date or earlier.']);
		}
	}

	// ==================== END FOREX ====================

	public function logout(){
		$this->session->sess_destroy();
		redirect('login');
	}

}
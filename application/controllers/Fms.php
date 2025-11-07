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
		$this->data["title"] = "FMS - Dashboard";

		// Get current user info
		$user = $this->auth_manager->get_current_user();
		$this->data['user'] = $user;
		$this->data['role'] = $user['role'];

		// Get dashboard statistics based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			// Admin sees all data
			$this->data['total_expenses'] = count($this->fmsm_enhanced->get_all_expenses());
			$this->data['total_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets());
			$this->data['total_users'] = count($this->fmsm_enhanced->get_all_users());
			$this->data['total_partners'] = count($this->fmsm_enhanced->get_all_partners());
		} else if($this->auth_manager->is_coordinator()){
			// Coordinator sees institution data
			$partner_id = $this->session->userdata('fms_partner_id');
			$this->data['total_expenses'] = count($this->fmsm_enhanced->get_all_expenses($partner_id));
			$this->data['total_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets(NULL, $partner_id));
			$this->data['total_users'] = count($this->fmsm_enhanced->get_all_users($partner_id));
			$this->data['pending_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets(NULL, $partner_id, 'submitted'));
		} else {
			// Member sees own data
			$user_id = $this->session->userdata('fms_user_id');
			$this->data['total_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets($user_id));
			$this->data['pending_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets($user_id, NULL, 'submitted'));
			$this->data['approved_timesheets'] = count($this->fmsm_enhanced->get_all_timesheets($user_id, NULL, 'approved'));
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

		if($this->fmsm_enhanced->approve_timesheet($timesheet_id, $approver_id, $comments)){
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

				.card { border: 1px solid #e3e6f0; border-radius: 4px; margin-bottom: 20px; }
				.card-header { background-color: #f8f9fa; border-bottom: 1px solid #e3e6f0; padding: 12px 15px; }
				.card-header h5 { margin: 0; font-size: 12px; font-weight: 600; }
				.card-body { padding: 15px; }

				.row { display: flex; flex-wrap: wrap; margin: -8px; }
				.col-md-3 { flex: 0 0 25%; padding: 8px; }
				.col-md-12 { flex: 0 0 100%; padding: 8px; }

				.info-text { margin-bottom: 8px; }
				.info-text p { margin: 0 0 3px 0; font-size: 11px; }
				.info-text strong { font-weight: 600; }

				table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 10px; }
				thead { background-color: #f8f9fa; }
				th { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: 600; font-size: 10px; }
				td { border: 1px solid #dee2e6; padding: 8px; }
				tbody tr:nth-child(even) { background-color: #f8f9fa; }
				tfoot { background-color: #f1f3f5; font-weight: 600; }
				tfoot th { background-color: #f1f3f5; }

				.badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 10px; font-weight: 600; }
				.badge-success { background-color: #d4edda; color: #155724; }
				.badge-danger { background-color: #f8d7da; color: #721c24; }
				.badge-warning { background-color: #fff3cd; color: #856404; }
				.badge-secondary { background-color: #e2e3e5; color: #383d41; }

				.flex-between { display: flex; justify-content: space-between; align-items: center; }
				.mb-0 { margin-bottom: 0; }
				.mb-1 { margin-bottom: 4px; }
				.mt-3 { margin-top: 15px; }

				.signature-section { margin-top: 30px; }
				.signature-image { max-width: 200px; max-height: 100px; margin: 15px 0; }
				.signature-line { border-top: 1px solid #333; width: 250px; margin-top: 30px; margin-bottom: 5px; }
				.signature-label { font-size: 9px; color: #666; }

				.alert { padding: 12px 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid; }
				.alert-info { background-color: #d1ecf1; border-left-color: #0c5460; color: #0c5460; }
			</style>
		</head>
		<body>
			<div class="container">
				<!-- Timesheet Header Info -->
				<div class="card">
					<div class="card-header flex-between">
						<h5 class="mb-0">GREATER - Timesheet for Project Outputs</h5>
						<span class="badge badge-success">Approved</span>
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
				</div>

				<!-- Work Package Summary -->
				<div class="card">
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
				<div class="card">
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

		// Add signature section if signature exists
		if(!empty($timesheet['signature_image'])){
			$html .= '
				<div class="signature-section">
					<h5>Signature</h5>
					<img src="' . $timesheet['signature_image'] . '" class="signature-image" alt="Signature" />
					<p><strong>Date Signed:</strong> ' . (!empty($timesheet['signature_date']) ? date('d/m/Y', strtotime($timesheet['signature_date'])) : 'N/A') . '</p>
				</div>';
		}

		$html .= '
				<div class="signature-section">
					<p style="margin-bottom: 30px;"><strong>Employee Signature:</strong></p>
					<div class="signature-line"></div>
					<div class="signature-label">Name and Date</div>
				</div>
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

				$this->fmsm_enhanced->insert_timesheet_detail($detail_data);
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

		$file_name = $this->input->post('formFN');
		$file_extension = pathinfo($_FILES['formValidationFile']['name'], PATHINFO_EXTENSION);
        $config['file_name'] = $file_name . '.' . $file_extension;
        $this->upload->initialize($config);
        $the_file_name = './assets/uploads/'.$file_name.'.'.$file_extension;

        if (move_uploaded_file($_FILES['formValidationFile']['tmp_name'], $the_file_name)) {
            // Save expense using enhanced model
			if($this->fmsm_enhanced->create_expense($data)){
				$this->session->set_flashdata('success', 'Expense uploaded successfully and pending approval.');
				redirect('expenses?status=success');
			} else {
				$this->session->set_flashdata('error', 'Failed to save expense to database.');
				redirect('newExpense?status=error');
			}
        } else {
            $this->session->set_flashdata('error', 'File upload failed: ' . $this->upload->display_errors());
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

		if($this->fmsm_enhanced->approve_expense($expense_id, $approver_id, $comments)){
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
			$this->session->set_flashdata('success', 'Expense rejected.');
		} else {
			$this->session->set_flashdata('error', 'Failed to reject expense.');
		}

		redirect('expenses');
	}
	
	public function users() {
		// Only admins and coordinators can view users
		if(!$this->auth_manager->is_super_admin() && !$this->auth_manager->is_admin() && !$this->auth_manager->is_coordinator()){
			show_error('Access Denied: You do not have permission to view users.', 403);
		}

		$this->data['title'] = "FMS - Users";

		// Get users based on role
		if($this->auth_manager->is_super_admin() || $this->auth_manager->is_admin()){
			$this->data['users'] = $this->fmsm_enhanced->get_all_users();
		} else {
			// Coordinators see only their institution's users
			$partner_id = $this->session->userdata('fms_partner_id');
			$this->data['users'] = $this->fmsm_enhanced->get_all_users($partner_id);
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
				'level' => $this->input->post('level', TRUE) ?: 1,
				'status' => 'active'
			);

			if($this->fmsm_enhanced->create_user($user_data)){
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

	public function logout(){
		$this->session->sess_destroy();
		redirect('login');
	}

}
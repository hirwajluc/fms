<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-off cleanup of Rwanda Polytechnic (RP) data.
 *
 * Keeps the RP partner record and two accounts, with nothing attached to them: the
 * RP Institution Coordinator and the RP staff member named Antoine. Removes the other
 * RP accounts and staff records, and every timesheet, expense, work package file,
 * monthly report, RP Forex rate, comment, notification and activity log entry that
 * belongs to RP or to an RP account. A draft report of another partner is removed
 * too when every line in it is an RP expense.
 *
 * Safety:
 *  - Super Admin only in the browser; on the server's command line for whoever has a shell.
 *  - Shows everything it will remove first, and removes nothing until the confirmation
 *    phrase is typed. It refuses to run if the data changed since that preview.
 *  - Before removing anything, writes every row to a SQL file that restores it and moves
 *    (does not delete) the uploaded files into the same backup folder, under
 *    application/backups/, which the web server refuses to serve.
 *  - All rows are removed in one transaction and checked table by table; anything
 *    unexpected rolls everything back.
 *  - Refuses to run if something belonging to another partner would have to change.
 *
 * Browser:       /fms/rpCleanup
 * Command line:  php index.php rp_cleanup                          (preview)
 *                php index.php rp_cleanup execute DELETE-RP-DATA   (back up and remove)
 *
 * Delete this file and application/views/pages/rp_cleanup.php once the cleanup is done.
 */
class Rp_cleanup extends CI_Controller {

	const PARTNER_SHORT_NAME = 'RP';
	const KEEP_NAME          = 'antoine';
	const CONFIRM_PHRASE     = 'DELETE-RP-DATA';

	/** table => primary key, in delete order: rows that point at others go first */
	private $tables = array(
		'other_file_comments'             => 'comment_id',
		'other_file_versions'             => 'version_id',
		'other_files'                     => 'file_id',
		'monthly_report_attachments'      => 'attachment_id',
		'monthly_report_items'            => 'item_id',
		'monthly_report_summary'          => 'summary_id',
		'monthly_report_summary_category' => 'summary_id',
		'monthly_report_summary_currency' => 'summary_id',
		'monthly_report_summary_wp'       => 'summary_id',
		'monthly_report_category_summary' => 'summary_id',
		'monthly_report_currency_summary' => 'summary_id',
		'monthly_report_wp_summary'       => 'summary_id',
		'monthly_financial_reports'       => 'report_id',
		'timesheet_details'               => 'detail_id',
		'timesheet_entries'               => 'entry_id',
		'timesheets'                      => 'timesheet_id',
		'expenses'                        => 'expense_id',
		'forex_rates'                     => 'id',
		'notifications'                   => 'notification_id',
		'audit_log'                       => 'log_id',
		'system_settings'                 => 'setting_id',
		'users'                           => 'user_id',
		'staff'                           => 'staff_id',
	);

	/** Columns that stop an account from being removed while any row still points at it */
	private $account_references = array(
		array('other_files', 'uploaded_by'),
		array('other_file_versions', 'uploaded_by'),
		array('other_file_comments', 'user_id'),
		array('monthly_financial_reports', 'created_by'),
		array('monthly_financial_reports', 'submitted_by'),
		array('monthly_financial_reports', 'approved_by'),
		array('monthly_report_attachments', 'uploaded_by'),
		array('monthly_report_items', 'uploaded_by'),
	);

	private $exists = array();

	public function __construct(){
		parent::__construct();
		if(!is_cli()){
			$this->auth_manager->require_login();
			if(!$this->auth_manager->is_super_admin()){
				show_error('Access Denied: Only a Super Admin can run the RP data cleanup.', 403, 'Access Denied');
			}
		}
		foreach($this->tables as $table => $pk){
			$this->exists[$table] = $this->db->table_exists($table);
		}
	}

	// ============================================================
	// PAGES
	// ============================================================

	/** Preview: what is kept, what will be removed, and anything that blocks it */
	public function index(){
		$plan = $this->build_plan();

		if(is_cli()){
			$this->print_plan($plan);
			if(empty($plan['blockers']) && $plan['total'] > 0){
				echo "\nTo back up and remove all of the above, run:\n  php index.php rp_cleanup execute " . self::CONFIRM_PHRASE . "\n";
			}
			return;
		}

		$token = bin2hex(random_bytes(16));
		$this->session->set_userdata('rp_cleanup_token', $token);

		$this->load->view('pages/rp_cleanup', array(
			'title'  => 'FMS - RP Data Cleanup',
			'plan'   => $plan,
			'token'  => $token,
			'phrase' => self::CONFIRM_PHRASE,
			'result' => NULL,
			'error'  => $this->session->flashdata('rp_cleanup_error'),
		));
	}

	/** Back up and remove. Browser: POST from the preview page. Command line: needs the phrase. */
	public function execute($cli_phrase = NULL){
		if(is_cli()){
			if($cli_phrase !== self::CONFIRM_PHRASE){
				echo "Nothing was changed. To confirm, run:\n  php index.php rp_cleanup execute " . self::CONFIRM_PHRASE . "\n";
				return;
			}
			$plan = $this->build_plan();
		} else {
			if($this->input->method() !== 'post'){
				redirect('rpCleanup');
				return;
			}
			$token = (string)$this->session->userdata('rp_cleanup_token');
			$this->session->unset_userdata('rp_cleanup_token');
			if($token === '' || !hash_equals($token, (string)$this->input->post('token'))){
				return $this->refuse('The form expired. Nothing was changed. Review the preview and try again.');
			}
			if(trim((string)$this->input->post('confirm_phrase')) !== self::CONFIRM_PHRASE){
				return $this->refuse('The confirmation phrase did not match. Nothing was changed.');
			}
			$plan = $this->build_plan();
			if(!hash_equals($plan['fingerprint'], (string)$this->input->post('fingerprint'))){
				return $this->refuse('The data changed after the preview was shown. Nothing was changed. Review the new preview before confirming again.');
			}
		}

		if(!empty($plan['blockers'])){
			return $this->refuse('Nothing was changed: ' . implode(' ', $plan['blockers']));
		}
		if($plan['total'] === 0){
			return $this->refuse('There is no RP data left to remove. Nothing was changed.');
		}

		$result = $this->run($plan);

		if(is_cli()){
			$this->print_result($result);
			return;
		}
		$this->load->view('pages/rp_cleanup', array(
			'title'  => 'FMS - RP Data Cleanup',
			'plan'   => $plan,
			'token'  => NULL,
			'phrase' => self::CONFIRM_PHRASE,
			'result' => $result,
			'error'  => NULL,
		));
	}

	/** Download the SQL that restores the removed rows */
	public function backup($folder = NULL){
		if(is_cli() || !preg_match('/^rp_cleanup_\d{8}_\d{6}$/', (string)$folder)){
			show_404();
		}
		$file = NULL;
		foreach($this->backup_bases() as $base){
			if(is_file($base . $folder . '/rows.sql')){
				$file = $base . $folder . '/rows.sql';
				break;
			}
		}
		if(!$file){
			show_404();
		}
		header('Content-Type: application/sql; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $folder . '_rows.sql"');
		header('Content-Length: ' . filesize($file));
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: no-store');
		readfile($file);
		exit;
	}

	// ============================================================
	// PLAN: decided entirely from the live data, so preview and run agree
	// ============================================================

	private function build_plan(){
		$plan = array(
			'partner'  => NULL,
			'keep'     => array(),
			'delete'   => array_fill_keys(array_keys($this->tables), array()),
			'lists'    => array('accounts' => array(), 'staff_only' => array(), 'timesheets' => array(), 'expenses' => array(), 'reports' => array(), 'files' => array()),
			'files'    => array(),
			'blockers' => array(),
			'total'    => 0,
		);

		foreach(array('partners', 'staff', 'users', 'roles') as $table){
			if(!$this->db->table_exists($table)){
				$plan['blockers'][] = 'The `' . $table . '` table is missing.';
				return $this->finish_plan($plan);
			}
		}

		// The partner, and the two accounts to keep
		$partners = $this->db->where('short_name', self::PARTNER_SHORT_NAME)->get('partners')->result_array();
		if(count($partners) !== 1){
			$plan['blockers'][] = 'Expected exactly one partner with short name "' . self::PARTNER_SHORT_NAME . '", found ' . count($partners) . '.';
			return $this->finish_plan($plan);
		}
		$partner = $partners[0];
		$pid     = (int)$partner['partner_id'];
		$plan['partner'] = $partner;

		$staff    = $this->db->where('partner_id', $pid)->order_by('staff_id')->get('staff')->result_array();
		$accounts = $this->db->query(
			"SELECT u.user_id, u.staff_id, u.email, u.status, r.role_name, s.first_name, s.last_name
			 FROM users u
			 JOIN staff s ON s.staff_id = u.staff_id
			 LEFT JOIN roles r ON r.role_id = u.role_id
			 WHERE s.partner_id = ?
			 ORDER BY u.user_id", array($pid)
		)->result_array();

		$coordinators = array_values(array_filter($accounts, function($a){ return $a['role_name'] === 'institution_coordinator'; }));
		if(count($coordinators) !== 1){
			$plan['blockers'][] = 'Expected exactly one RP Institution Coordinator account, found ' . count($coordinators) . ': ' . $this->names($coordinators) . '.';
		}
		$named = array_values(array_filter($staff, function($s){
			$words = preg_split('/\s+/', $this->lower(trim($s['first_name'] . ' ' . $s['last_name'])));
			return in_array(self::KEEP_NAME, $words, TRUE);
		}));
		$named_accounts = array();
		if(count($named) !== 1){
			$plan['blockers'][] = 'Expected exactly one RP staff member named Antoine, found ' . count($named) . ': ' . $this->names($named) . '.';
		} else {
			$named_accounts = array_values(array_filter($accounts, function($a) use ($named){ return (int)$a['staff_id'] === (int)$named[0]['staff_id']; }));
			if(count($named_accounts) !== 1){
				$plan['blockers'][] = 'Expected Antoine (' . $this->names($named) . ') to have exactly one account, found ' . count($named_accounts) . '.';
			}
		}
		if(!empty($plan['blockers'])){
			return $this->finish_plan($plan);
		}
		if((int)$coordinators[0]['user_id'] === (int)$named_accounts[0]['user_id']){
			$plan['blockers'][] = 'The RP coordinator and Antoine are the same account; expected two different accounts.';
			return $this->finish_plan($plan);
		}

		$plan['keep'] = array($coordinators[0], $named_accounts[0]);
		$keep_ids     = array((int)$coordinators[0]['user_id'], (int)$named_accounts[0]['user_id']);
		$keep_staff   = array((int)$coordinators[0]['staff_id'], (int)$named_accounts[0]['staff_id']);

		$all_ids   = $this->ints(array_column($accounts, 'user_id'));
		$del_ids   = array_values(array_diff($all_ids, $keep_ids));
		$del_staff = array_values(array_diff($this->ints(array_column($staff, 'staff_id')), $keep_staff));
		$by_user   = array();
		foreach($accounts as $a){
			$by_user[(int)$a['user_id']] = $a;
		}

		foreach($accounts as $a){
			if(in_array((int)$a['user_id'], $del_ids, TRUE) && in_array($a['role_name'], array('super_admin', 'admin'), TRUE)){
				$plan['blockers'][] = 'Refusing to remove the administrator account of ' . $this->names(array($a)) . '.';
			}
		}
		$plan['delete']['users'] = $del_ids;
		$plan['delete']['staff'] = $del_staff;
		$plan['lists']['accounts'] = array_values(array_filter($accounts, function($a) use ($del_ids){ return in_array((int)$a['user_id'], $del_ids, TRUE); }));
		$with_account = $this->ints(array_column($accounts, 'staff_id'));
		$plan['lists']['staff_only'] = array_values(array_filter($staff, function($s) use ($del_staff, $with_account){
			return in_array((int)$s['staff_id'], $del_staff, TRUE) && !in_array((int)$s['staff_id'], $with_account, TRUE);
		}));
		$owner = function($user_id) use ($by_user){
			return isset($by_user[(int)$user_id]) ? trim($by_user[(int)$user_id]['first_name'] . ' ' . $by_user[(int)$user_id]['last_name']) : 'account #' . (int)$user_id;
		};
		$partner_names = array_column($this->db->select('partner_id, short_name')->get('partners')->result_array(), 'short_name', 'partner_id');
		$other_partner = function($row_partner) use ($pid){
			return $row_partner !== NULL && (int)$row_partner !== $pid;
		};
		$pname = function($id) use ($partner_names){
			return $partner_names[$id] ?? 'partner #' . (int)$id;
		};
		$in_all = $this->in_list($all_ids);

		// Timesheets
		$timesheets = $this->db->query(
			"SELECT timesheet_id, user_id, partner_id, month, year, status, signature_image, file_path, approval_stamp_path
			 FROM timesheets WHERE partner_id = ? OR user_id IN $in_all ORDER BY timesheet_id", array($pid)
		)->result_array();
		foreach($timesheets as $t){
			if($other_partner($t['partner_id'])){
				$plan['blockers'][] = 'Timesheet #' . $t['timesheet_id'] . ' of ' . $owner($t['user_id']) . ' is filed under another partner (' . $pname($t['partner_id']) . ').';
			}
		}
		$plan['delete']['timesheets'] = $this->ints(array_column($timesheets, 'timesheet_id'));
		$plan['delete']['timesheet_details'] = $this->child_ids('timesheet_details', 'timesheet_id', $plan['delete']['timesheets']);
		$plan['delete']['timesheet_entries'] = $this->child_ids('timesheet_entries', 'timesheet_id', $plan['delete']['timesheets']);
		foreach($timesheets as $t){
			$plan['lists']['timesheets'][] = array(
				'id'      => $t['timesheet_id'],
				'owner'   => $owner($t['user_id']),
				'period'  => sprintf('%02d/%d', $t['month'], $t['year']),
				'status'  => $t['status'],
				'entries' => count($this->child_ids('timesheet_details', 'timesheet_id', array((int)$t['timesheet_id']))),
			);
		}

		// Expenses
		$expenses = $this->db->query(
			"SELECT expense_id, partner_id, uploaded_by, FileName, Amount, Currency, Date, status
			 FROM expenses WHERE partner_id = ? OR uploaded_by IN $in_all ORDER BY expense_id", array($pid)
		)->result_array();
		foreach($expenses as $e){
			if($other_partner($e['partner_id'])){
				$plan['blockers'][] = 'Expense #' . $e['expense_id'] . ' was uploaded by ' . $owner($e['uploaded_by']) . ' for another partner (' . $pname($e['partner_id']) . ').';
			}
		}
		$plan['delete']['expenses'] = $this->ints(array_column($expenses, 'expense_id'));
		$plan['lists']['expenses']  = $expenses;

		// Monthly reports: RP's own, plus another partner's draft made only of RP expenses
		if($this->exists['monthly_financial_reports']){
			$reports = $this->db->query("SELECT report_id, partner_id, report_month, report_year, status FROM monthly_financial_reports ORDER BY report_id")->result_array();
			foreach($reports as $r){
				$items = $this->exists['monthly_report_items']
					? $this->db->query("SELECT expense_id FROM monthly_report_items WHERE report_id = ?", array((int)$r['report_id']))->result_array()
					: array();
				$item_expenses = $this->ints(array_column($items, 'expense_id'));
				$rp_items      = array_intersect($item_expenses, $plan['delete']['expenses']);
				$note = '';
				if((int)$r['partner_id'] === $pid){
					$note = 'RP report';
				} elseif(!empty($rp_items)){
					if($r['status'] === 'draft' && count($rp_items) === count($item_expenses)){
						$note = 'Draft of ' . $pname($r['partner_id']) . '; every line in it is an RP expense';
					} else {
						$plan['blockers'][] = 'Monthly report #' . $r['report_id'] . ' of ' . $pname($r['partner_id']) . ' (' . $r['status'] . ') contains RP expenses alongside other data.';
						continue;
					}
				} else {
					continue;
				}
				$plan['delete']['monthly_financial_reports'][] = (int)$r['report_id'];
				$plan['lists']['reports'][] = array(
					'id'      => $r['report_id'],
					'partner' => $pname($r['partner_id']),
					'period'  => sprintf('%02d/%d', $r['report_month'], $r['report_year']),
					'status'  => $r['status'],
					'lines'   => count($item_expenses),
					'note'    => $note,
				);
			}
			foreach($this->tables as $table => $pk){
				if(strpos($table, 'monthly_report_') === 0){
					$plan['delete'][$table] = $this->child_ids($table, 'report_id', $plan['delete']['monthly_financial_reports']);
				}
			}
		}

		// Work package files
		$versions = array();
		if($this->exists['other_files']){
			$files = $this->db->query(
				"SELECT file_id, partner_id, uploaded_by, display_name, wp_id FROM other_files
				 WHERE partner_id = ? OR uploaded_by IN $in_all ORDER BY file_id", array($pid)
			)->result_array();
			foreach($files as $f){
				if($other_partner($f['partner_id'])){
					$plan['blockers'][] = 'Work package file #' . $f['file_id'] . ' was uploaded by ' . $owner($f['uploaded_by']) . ' into another partner\'s folder (' . $pname($f['partner_id']) . ').';
				}
			}
			$plan['delete']['other_files'] = $this->ints(array_column($files, 'file_id'));
			if($this->exists['other_file_versions']){
				$versions = $this->db->query(
					"SELECT version_id, file_id, stored_name FROM other_file_versions WHERE file_id IN " . $this->in_list($plan['delete']['other_files']) . " ORDER BY version_id"
				)->result_array();
				$plan['delete']['other_file_versions'] = $this->ints(array_column($versions, 'version_id'));
				$foreign = $this->db->query(
					"SELECT COUNT(*) AS n FROM other_file_versions WHERE uploaded_by IN $in_all AND file_id NOT IN " . $this->in_list($plan['delete']['other_files'])
				)->row()->n;
				if($foreign > 0){
					$plan['blockers'][] = 'RP accounts uploaded ' . $foreign . ' version(s) to other partners\' work package files.';
				}
			}
			if($this->exists['other_file_comments']){
				$plan['delete']['other_file_comments'] = $this->ints(array_column($this->db->query(
					"SELECT comment_id FROM other_file_comments WHERE file_id IN " . $this->in_list($plan['delete']['other_files']) . " OR user_id IN $in_all"
				)->result_array(), 'comment_id'));
			}
			foreach($files as $f){
				$plan['lists']['files'][] = array(
					'id'       => $f['file_id'],
					'name'     => $f['display_name'],
					'wp_id'    => $f['wp_id'],
					'versions' => count(array_filter($versions, function($v) use ($f){ return (int)$v['file_id'] === (int)$f['file_id']; })),
				);
			}
		}

		// RP's own Forex rates (exists once db/database_update.sql has been applied)
		if($this->exists['forex_rates'] && $this->db->field_exists('partner_id', 'forex_rates')){
			$plan['delete']['forex_rates'] = $this->ints(array_column($this->db->query("SELECT id FROM forex_rates WHERE partner_id = ?", array($pid))->result_array(), 'id'));
		}

		// Notifications and activity log of every RP account, signature setup of removed accounts
		foreach(array('notifications' => 'notification_id', 'audit_log' => 'log_id') as $table => $pk){
			if($this->exists[$table]){
				$plan['delete'][$table] = $this->ints(array_column($this->db->query("SELECT $pk FROM $table WHERE user_id IN $in_all")->result_array(), $pk));
			}
		}
		$signatures = array();
		if($this->exists['system_settings'] && !empty($del_ids)){
			$keys = array_map(function($id){ return 'report_signature_' . $id; }, $del_ids);
			$signatures = $this->db->select('setting_id, setting_value')->where_in('setting_key', $keys)->get('system_settings')->result_array();
			$plan['delete']['system_settings'] = $this->ints(array_column($signatures, 'setting_id'));
		}

		// Nothing left behind may still point at a removed account or expense
		$in_del = $this->in_list($del_ids);
		foreach($this->account_references as $ref){
			list($table, $column) = $ref;
			if(!$this->exists[$table] || empty($del_ids)){
				continue;
			}
			$pk   = $this->tables[$table];
			$left = array_diff($this->ints(array_column($this->db->query("SELECT $pk FROM $table WHERE $column IN $in_del")->result_array(), $pk)), $plan['delete'][$table]);
			if(!empty($left)){
				$plan['blockers'][] = count($left) . ' row(s) in ' . $table . ' outside RP still point at an account that would be removed.';
			}
		}
		if($this->exists['monthly_report_items'] && !empty($plan['delete']['expenses'])){
			$left = array_diff($this->ints(array_column($this->db->query(
				"SELECT item_id FROM monthly_report_items WHERE expense_id IN " . $this->in_list($plan['delete']['expenses'])
			)->result_array(), 'item_id')), $plan['delete']['monthly_report_items']);
			if(!empty($left)){
				$plan['blockers'][] = count($left) . ' monthly report line(s) outside the planned reports still use an RP expense.';
			}
		}
		if(!empty($del_staff)){
			$left = array_diff($this->ints(array_column($this->db->query("SELECT user_id FROM users WHERE staff_id IN " . $this->in_list($del_staff))->result_array(), 'user_id')), $del_ids);
			if(!empty($left)){
				$plan['blockers'][] = 'A staff record that would be removed still has an account that is not being removed.';
			}
		}

		// Uploaded files of the removed rows
		$files = array();
		foreach($expenses as $e){
			$this->add_file($files, 'assets/uploads/', $e['FileName'], 'Expense #' . $e['expense_id']);
		}
		foreach($timesheets as $t){
			foreach(array('signature_image', 'file_path', 'approval_stamp_path') as $column){
				$this->add_file($files, '', $t[$column], 'Timesheet #' . $t['timesheet_id']);
			}
		}
		foreach($versions as $v){
			$this->add_file($files, 'assets/otherfiles/', $v['stored_name'], 'Work package file #' . $v['file_id']);
		}
		foreach(array('monthly_financial_reports', 'monthly_report_attachments') as $table){
			if($this->exists[$table] && !empty($plan['delete'][$table])){
				$pk = $this->tables[$table];
				foreach($this->db->query("SELECT $pk, file_path FROM $table WHERE $pk IN " . $this->in_list($plan['delete'][$table]))->result_array() as $row){
					$this->add_file($files, '', $row['file_path'], 'Monthly report file');
				}
			}
		}
		foreach($signatures as $s){
			$value = json_decode($s['setting_value'], TRUE);
			if(!empty($value['signature_file'])){
				$this->add_file($files, 'assets/signatures/', $value['signature_file'], 'Signature setup');
			}
		}
		foreach($files as $path => &$file){
			$file['in_use'] = $this->file_in_use(basename($path), $plan['delete']);
		}
		unset($file);
		$plan['files'] = $files;

		return $this->finish_plan($plan);
	}

	private function finish_plan($plan){
		$total = 0;
		foreach($plan['delete'] as $table => $ids){
			$plan['delete'][$table] = $this->ints($ids);
			$total += count($ids);
		}
		$plan['total'] = $total;
		$plan['fingerprint'] = sha1(json_encode(array(
			$plan['partner']['partner_id'] ?? NULL,
			array_column($plan['keep'], 'user_id'),
			$plan['delete'],
			array_keys($plan['files']),
			$plan['blockers'],
		)));
		return $plan;
	}

	// ============================================================
	// RUN
	// ============================================================

	private function run($plan){
		$result = array('ok' => FALSE, 'error' => NULL, 'deleted' => array(), 'backup' => NULL, 'moved' => array(), 'not_moved' => array());

		// Every table involved must be transactional, or a failure could leave half the rows removed
		$involved = array_keys(array_filter($plan['delete']));
		$engines  = $this->db->query(
			"SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ?", array($involved)
		)->result_array();
		foreach($engines as $e){
			if(strtolower((string)$e['ENGINE']) !== 'innodb'){
				$result['error'] = 'Table ' . $e['TABLE_NAME'] . ' uses ' . $e['ENGINE'] . ', which cannot be rolled back. Nothing was changed.';
				return $result;
			}
		}

		// 1. Backup folder, protected from the web
		$folder = 'rp_cleanup_' . date('Ymd_His');
		$dir    = NULL;
		foreach($this->backup_bases() as $base){
			if((is_dir($base) || @mkdir($base, 0700, TRUE)) && is_really_writable($base)){
				$this->protect_dir($base);
				if(@mkdir($base . $folder, 0700, TRUE)){
					$dir = $base . $folder;
					break;
				}
			}
		}
		if(!$dir){
			$result['error'] = 'Could not create a backup folder in application/backups/ or application/logs/. Nothing was changed.';
			return $result;
		}
		$this->protect_dir($dir . '/');
		$result['backup'] = array('folder' => $folder, 'path' => $this->relative($dir));

		// 2. Every row to be removed, as SQL that puts it back
		$error = $this->write_backup($dir . '/rows.sql', $plan);
		if($error){
			$result['error'] = $error . ' Nothing was changed.';
			return $result;
		}

		// 3. Remove the rows in one transaction, checking each table
		$debug = $this->db->db_debug;
		$this->db->db_debug = FALSE;
		$watched = array_merge(array_keys(array_filter($this->exists)), array('partners', 'roles'));
		if($this->db->table_exists('work_packages')){
			$watched[] = 'work_packages';
		}

		$this->db->trans_begin();
		$before = $this->count_rows($watched);
		foreach($this->tables as $table => $pk){
			$removed = 0;
			foreach(array_chunk($plan['delete'][$table], 500) as $chunk){
				if(!$this->db->query("DELETE FROM `$table` WHERE `$pk` IN " . $this->in_list($chunk))){
					$err = $this->db->error();
					$this->db->trans_rollback();
					$this->db->db_debug = $debug;
					$result['error'] = 'Removing rows from ' . $table . ' failed (' . $err['message'] . '). Everything was rolled back; nothing was changed.';
					return $result;
				}
				$removed += $this->db->affected_rows();
			}
			$result['deleted'][$table] = $removed;
		}
		$after = $this->count_rows($watched);
		foreach($watched as $table){
			$expected = isset($plan['delete'][$table]) ? count($plan['delete'][$table]) : 0;
			$actual   = $before[$table] - $after[$table];
			if($actual !== $expected || (isset($result['deleted'][$table]) && $result['deleted'][$table] !== $expected)){
				$this->db->trans_rollback();
				$this->db->db_debug = $debug;
				$result['error'] = $table . ': expected to remove ' . $expected . ' row(s) but ' . $actual . ' would have gone. Everything was rolled back; nothing was changed.';
				return $result;
			}
		}
		if(!$this->db->trans_commit()){
			$this->db->trans_rollback();
			$this->db->db_debug = $debug;
			$result['error'] = 'The database did not accept the changes. Everything was rolled back; nothing was changed.';
			return $result;
		}
		$this->db->db_debug = $debug;
		$result['ok'] = TRUE;

		// 4. Move the uploaded files into the backup folder (rows are gone, so check again that nothing uses them)
		foreach($plan['files'] as $path => $file){
			if(!$file['exists']){
				$result['not_moved'][] = $path . ' (was already missing)';
				continue;
			}
			if($this->file_in_use(basename($path), array())){
				$result['not_moved'][] = $path . ' (still used by other data, left in place)';
				continue;
			}
			$target = $dir . '/files/' . $path;
			if(!is_dir(dirname($target))){
				@mkdir(dirname($target), 0700, TRUE);
			}
			if(@rename(FCPATH . $path, $target) || (@copy(FCPATH . $path, $target) && @unlink(FCPATH . $path))){
				$result['moved'][] = $path;
			} else {
				$result['not_moved'][] = $path . ' (could not be moved, left in place)';
			}
		}

		file_put_contents($dir . '/result.json', json_encode(array(
			'ran_at'    => date('Y-m-d H:i:s'),
			'ran_by'    => is_cli() ? 'command line' : $this->session->userdata('fms_email'),
			'partner'   => $plan['partner']['name'],
			'kept'      => array_map(function($a){ return trim($a['first_name'] . ' ' . $a['last_name']) . ' <' . $a['email'] . '>'; }, $plan['keep']),
			'deleted'   => $result['deleted'],
			'moved'     => $result['moved'],
			'not_moved' => $result['not_moved'],
		), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		$this->fmsm_enhanced->log_activity(
			is_cli() ? NULL : $this->session->userdata('fms_user_id'),
			'rp_cleanup', 'partner', (int)$plan['partner']['partner_id'],
			'RP data cleanup: removed ' . array_sum($result['deleted']) . ' rows and moved ' . count($result['moved']) . ' files. Backup: ' . $result['backup']['path']
		);

		return $result;
	}

	private function write_backup($path, $plan){
		$fh = @fopen($path, 'xb');
		if(!$fh){
			return 'Could not write the backup file.';
		}
		$ok = fwrite($fh,
			"-- GREATER FMS: rows removed by the RP data cleanup\n" .
			"-- Created " . date('Y-m-d H:i:s') . " for partner " . $plan['partner']['name'] . "\n" .
			"--\n" .
			"-- TO RESTORE: import this file into the same database (phpMyAdmin > Import),\n" .
			"-- then copy the files/ folder next to it back into the fms folder,\n" .
			"-- keeping the same paths (files/assets/... goes to fms/assets/...).\n\n" .
			// Same mode as phpMyAdmin exports: the data holds values such as 0000-00-00 dates
			"SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSET time_zone = '+00:00';\n" .
			"SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSTART TRANSACTION;\n\n"
		);
		// Read timestamps in UTC, matching the time_zone line above
		$time_zone = $this->db->query('SELECT @@session.time_zone AS tz')->row()->tz;
		$this->db->query("SET time_zone = '+00:00'");
		foreach(array_reverse($this->tables, TRUE) as $table => $pk){
			$ids = $plan['delete'][$table];
			if(empty($ids)){
				continue;
			}
			$written = 0;
			foreach(array_chunk($ids, 200) as $chunk){
				$rows = $this->db->query("SELECT * FROM `$table` WHERE `$pk` IN " . $this->in_list($chunk) . " ORDER BY `$pk`")->result_array();
				if(empty($rows)){
					continue;
				}
				$values = array();
				foreach($rows as $row){
					$values[] = '(' . implode(', ', array_map(function($v){ return $v === NULL ? 'NULL' : $this->db->escape((string)$v); }, $row)) . ')';
				}
				$ok = $ok && fwrite($fh, "INSERT INTO `$table` (`" . implode('`, `', array_keys($rows[0])) . "`) VALUES\n" . implode(",\n", $values) . ";\n\n");
				$written += count($rows);
			}
			if($written !== count($ids)){
				fclose($fh);
				$this->db->query('SET time_zone = ' . $this->db->escape($time_zone));
				return 'The backup of ' . $table . ' has ' . $written . ' of ' . count($ids) . ' rows; the data changed during the backup.';
			}
		}
		$this->db->query('SET time_zone = ' . $this->db->escape($time_zone));
		$ok = $ok && fwrite($fh, "COMMIT;\nSET FOREIGN_KEY_CHECKS = 1;\n");
		$ok = fclose($fh) && $ok;
		return $ok ? NULL : 'Writing the backup file failed.';
	}

	// ============================================================
	// HELPERS
	// ============================================================

	private function refuse($message){
		if(is_cli()){
			echo $message . "\n";
			return;
		}
		$this->session->set_flashdata('rp_cleanup_error', $message);
		redirect('rpCleanup');
	}

	private function child_ids($table, $column, $parent_ids){
		if(!$this->exists[$table] || empty($parent_ids)){
			return array();
		}
		$pk = $this->tables[$table];
		return $this->ints(array_column($this->db->query("SELECT $pk FROM $table WHERE $column IN " . $this->in_list($parent_ids))->result_array(), $pk));
	}

	/** Records an uploaded file, only if its path stays inside assets/ */
	private function add_file(&$files, $dir, $value, $owner){
		$value = str_replace('\\', '/', trim((string)$value));
		if($value === '' || strpos($value, '..') !== FALSE || strpos($value, "\0") !== FALSE){
			return;
		}
		$path = ltrim(preg_replace('#^\./#', '', strpos($value, '/') === FALSE ? $dir . $value : $value), '/');
		if($dir === '' && strpos($value, '/') === FALSE){
			return; // a bare name with no known folder
		}
		if(strpos($path, 'assets/') !== 0){
			return;
		}
		$real   = realpath(FCPATH . $path);
		$assets = realpath(FCPATH . 'assets');
		$inside = $real !== FALSE && $assets !== FALSE && strpos($real, $assets . DIRECTORY_SEPARATOR) === 0 && is_file($real);
		if(!isset($files[$path])){
			$files[$path] = array('owner' => $owner, 'exists' => $inside);
		}
	}

	/** Is a file name still used by any row that is not being removed? */
	private function file_in_use($name, $deleting){
		$like = '%' . $this->db->escape_like_str($name);
		$checks = array(
			array('expenses', array('FileName' => '=', 'approver_signature_file' => '=')),
			array('timesheets', array('signature_image' => 'like', 'file_path' => 'like', 'approval_stamp_path' => 'like', 'approver_signature_file' => '=')),
			array('other_file_versions', array('stored_name' => '=')),
			array('monthly_financial_reports', array('file_path' => 'like')),
			array('monthly_report_attachments', array('file_path' => 'like')),
			array('system_settings', array('setting_value' => 'contains')),
		);
		foreach($checks as $check){
			list($table, $columns) = $check;
			if(!$this->db->table_exists($table)){
				continue;
			}
			$pk = $this->tables[$table];
			$where = array();
			foreach($columns as $column => $how){
				if(!$this->db->field_exists($column, $table)){
					continue;
				}
				if($how === '='){
					$where[] = "`$column` = " . $this->db->escape($name);
				} elseif($how === 'like'){
					$where[] = "`$column` LIKE '" . $like . "' ESCAPE '!'";
				} else {
					$where[] = "`$column` LIKE '" . $like . "%' ESCAPE '!'";
				}
			}
			if(empty($where)){
				continue;
			}
			$sql = "SELECT COUNT(*) AS n FROM `$table` WHERE (" . implode(' OR ', $where) . ")";
			if(!empty($deleting[$table])){
				$sql .= " AND `$pk` NOT IN " . $this->in_list($deleting[$table]);
			}
			if($this->db->query($sql)->row()->n > 0){
				return TRUE;
			}
		}
		return FALSE;
	}

	private function count_rows($tables){
		$counts = array();
		foreach($tables as $table){
			$counts[$table] = (int)$this->db->query("SELECT COUNT(*) AS n FROM `$table`")->row()->n;
		}
		return $counts;
	}

	private function backup_bases(){
		return array(APPPATH . 'backups/', APPPATH . 'logs/');
	}

	private function protect_dir($dir){
		if(!is_file($dir . '.htaccess')){
			@file_put_contents($dir . '.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
		}
		if(!is_file($dir . 'index.html')){
			@file_put_contents($dir . 'index.html', '');
		}
	}

	private function relative($path){
		return str_replace(rtrim(FCPATH, '/') . '/', '', $path);
	}

	private function in_list($ids){
		$ids = $this->ints($ids);
		return empty($ids) ? '(NULL)' : '(' . implode(',', $ids) . ')';
	}

	private function ints($values){
		$ids = array_values(array_unique(array_map('intval', $values)));
		sort($ids);
		return $ids;
	}

	private function lower($text){
		return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
	}

	private function names($rows){
		if(empty($rows)){
			return 'none';
		}
		return implode(', ', array_map(function($r){ return trim($r['first_name'] . ' ' . $r['last_name']); }, $rows));
	}

	private function print_plan($plan){
		echo "RP DATA CLEANUP - PREVIEW (nothing has been changed)\n\n";
		if($plan['partner']){
			echo 'Partner: ' . $plan['partner']['name'] . ' (kept)' . "\n";
		}
		foreach($plan['keep'] as $a){
			echo '  Keep account: ' . trim($a['first_name'] . ' ' . $a['last_name']) . ' <' . $a['email'] . '> (' . $a['role_name'] . ")\n";
		}
		echo "\nTo be removed:\n";
		foreach($plan['delete'] as $table => $ids){
			if(!empty($ids)){
				echo sprintf("  %-32s %d\n", $table, count($ids));
			}
		}
		foreach($plan['lists']['accounts'] ?? array() as $a){
			echo '  Account: ' . trim($a['first_name'] . ' ' . $a['last_name']) . ' <' . $a['email'] . '> (' . $a['role_name'] . ")\n";
		}
		foreach($plan['lists']['reports'] ?? array() as $r){
			echo '  Report #' . $r['id'] . ': ' . $r['partner'] . ' ' . $r['period'] . ' ' . $r['status'] . ' (' . $r['note'] . ")\n";
		}
		echo '  Uploaded files to move into the backup: ' . count(array_filter($plan['files'], function($f){ return $f['exists'] && !$f['in_use']; })) . "\n";
		echo "  Total rows: " . $plan['total'] . "\n";
		if(!empty($plan['blockers'])){
			echo "\nCANNOT RUN:\n  - " . implode("\n  - ", $plan['blockers']) . "\n";
		}
	}

	private function print_result($result){
		if(!$result['ok']){
			echo 'FAILED: ' . $result['error'] . "\n";
			return;
		}
		echo "RP DATA CLEANUP - DONE\n\nRemoved rows:\n";
		foreach($result['deleted'] as $table => $n){
			if($n > 0){
				echo sprintf("  %-32s %d\n", $table, $n);
			}
		}
		echo 'Files moved into the backup: ' . count($result['moved']) . "\n";
		foreach($result['not_moved'] as $f){
			echo '  Not moved: ' . $f . "\n";
		}
		echo 'Backup folder: ' . $result['backup']['path'] . "\n";
		echo "\nNow delete application/controllers/Rp_cleanup.php and application/views/pages/rp_cleanup.php from the server.\n";
	}
}

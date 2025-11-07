<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'fms';

// Expense routes
$route['expenses'] = 'fms/expenses';
$route['newExpense'] = 'fms/newExpense';
$route['saveExpense'] = 'fms/saveExpense';
$route['approveExpense/(:num)'] = 'fms/approveExpense/$1';
$route['rejectExpense/(:num)'] = 'fms/rejectExpense/$1';

// Timesheet routes
$route['timesheets'] = 'fms/timesheets';
$route['newTimesheet'] = 'fms/newTimesheet';
$route['saveTimesheet'] = 'fms/saveTimesheet';
$route['parseTimesheetExcel'] = 'fms/parseTimesheetExcel';
$route['viewTimesheet/(:num)'] = 'fms/viewTimesheet/$1';
$route['editTimesheet/(:num)'] = 'fms/editTimesheet/$1';
$route['updateTimesheet/(:num)'] = 'fms/updateTimesheet/$1';
$route['downloadTimesheetPDF/(:num)'] = 'fms/downloadTimesheetPDF/$1';
$route['uploadTimesheetSignature'] = 'fms/uploadTimesheetSignature';
$route['approveTimesheet/(:num)'] = 'fms/approveTimesheet/$1';
$route['rejectTimesheet/(:num)'] = 'fms/rejectTimesheet/$1';

// Monthly Reports routes
$route['monthlyReports'] = 'fms/monthlyReports';
$route['viewMonthlyReport/(:num)'] = 'fms/viewMonthlyReport/$1';
$route['generateMonthlyReport'] = 'fms/generateMonthlyReport';
$route['generateMonthlyReport/(:num)/(:num)/(:num)'] = 'fms/generateMonthlyReport/$1/$2/$3';
$route['submitMonthlyReport/(:num)'] = 'fms/submitMonthlyReport/$1';
$route['approveMonthlyReport/(:num)'] = 'fms/approveMonthlyReport/$1';
$route['rejectMonthlyReport/(:num)'] = 'fms/rejectMonthlyReport/$1';

// User management routes
$route['users'] = 'fms/users';
$route['newUser'] = 'fms/newUser';
$route['saveUser'] = 'fms/saveUser';
$route['editUser/(:num)'] = 'fms/editUser/$1';
$route['updateUser/(:num)'] = 'fms/updateUser/$1';
$route['deleteUser/(:num)'] = 'fms/deleteUser/$1';

// Logout
$route['logout'] = 'fms/logout';

$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
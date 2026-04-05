<?php
session_start();
include '../../connection.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json');

$payroll_id = intval($_GET['payroll_id'] ?? 0);

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
$current_admin_shop_id = null;
if ($current_user_id > 0) {
    $shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
    $shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
    if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
        $shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
        $current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
    }
}

$payroll_scope_condition = "";
$payroll_details_scope_condition = "";
if (!$is_super_admin) {
    if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
        $payroll_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        $payroll_details_scope_condition = " AND pd.shop_id = {$current_admin_shop_id}";
    } else {
        echo json_encode(['success' => false, 'message' => 'No shop assigned to this admin']);
        exit;
    }
}

if($payroll_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payroll ID']);
    exit;
}

// Get payroll record
$payroll_query = "SELECT * FROM mrb_payroll WHERE payroll_id = $payroll_id{$payroll_scope_condition}";
$payroll_result = mysqli_query($conn, $payroll_query);

if(mysqli_num_rows($payroll_result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Payroll not found']);
    exit;
}

$payroll = mysqli_fetch_assoc($payroll_result);

// Get payroll details with employee information
$details_query = "SELECT 
                    pd.*,
                    e.emp_number,
                    e.emp_first_name,
                    e.emp_last_name,
                    e.position,
                    e.department,
                    e.sss_number,
                    e.pagibig_number,
                    e.philhealth_number,
                    e.tin_number
                  FROM mrb_payroll_details pd
                  JOIN mrb_employees e ON pd.emp_id = e.emp_id
                  WHERE pd.payroll_id = $payroll_id{$payroll_details_scope_condition}
                  ORDER BY e.emp_last_name, e.emp_first_name";

$details_result = mysqli_query($conn, $details_query);

$employees = [];
while($detail = mysqli_fetch_assoc($details_result)) {
    $employees[] = [
        'emp_number' => $detail['emp_number'],
        'name' => $detail['emp_first_name'] . ' ' . $detail['emp_last_name'],
        'position' => $detail['position'],
        'department' => $detail['department'],
        'days_worked' => $detail['days_worked'],
        'days_late' => $detail['days_late'],
        'gross_salary' => number_format($detail['gross_salary'], 2),
        'deductions' => number_format($detail['deductions'], 2),
        'net_salary' => number_format($detail['net_salary'], 2),
        'late_deductions' => number_format($detail['total_late_deductions'], 2),
        'sss_number' => $detail['sss_number'] ?? 'N/A',
        'pagibig_number' => $detail['pagibig_number'] ?? 'N/A',
        'philhealth_number' => $detail['philhealth_number'] ?? 'N/A',
        'tin_number' => $detail['tin_number'] ?? 'N/A'
    ];
}

// Handle both old and new column names for backwards compatibility
$employee_count = $payroll['employee_count'] ?? $payroll['total_employees'] ?? 0;
$deductions_amount = $payroll['deductions_amount'] ?? $payroll['total_deductions'] ?? 0;

$response = [
    'success' => true,
    'payroll' => [
        'period_start' => date('M j, Y', strtotime($payroll['payroll_period_start'])),
        'period_end' => date('M j, Y', strtotime($payroll['payroll_period_end'])),
        'employee_count' => $employee_count,
        'gross_amount' => number_format($payroll['gross_amount'], 2),
        'deductions_amount' => number_format($deductions_amount, 2),
        'net_amount' => number_format($payroll['net_amount'], 2),
        'status' => $payroll['status'],
        'processed_date' => date('M j, Y h:i A', strtotime($payroll['processed_date']))
    ],
    'employees' => $employees
];

echo json_encode($response);
?>

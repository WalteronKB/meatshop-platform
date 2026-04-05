<?php
// Database Connection Verification Script
// Run this to verify all tables exist and are properly configured

session_start();
include('../connection.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../mrbloginpage.php');
    exit;
}

$tables_needed = [
    'mrb_employees' => ['emp_id', 'emp_number', 'emp_first_name', 'emp_last_name', 'position', 'salary'],
    'mrb_payroll' => ['payroll_id', 'payroll_period_start', 'payroll_period_end', 'gross_amount'],
    'mrb_suppliers' => ['supplier_id', 'supplier_number', 'company_name', 'contact_person'],
    'mrb_purchase_orders' => ['po_id', 'po_number', 'supplier_id', 'total_amount'],
    'mrb_bank_accounts' => ['account_id', 'bank_name', 'account_number', 'balance'],
    'mrb_income' => ['income_id', 'income_source', 'amount', 'income_date'],
    'mrb_expenses' => ['expense_id', 'category', 'amount', 'expense_date']
];

$results = [];

foreach ($tables_needed as $table => $required_columns) {
    // Check if table exists
    $table_check = mysqli_query($conn, "SELECT 1 FROM $table LIMIT 1");
    
    if ($table_check === false) {
        $results[$table] = [
            'status' => 'ERROR',
            'message' => 'Table does not exist',
            'error' => mysqli_error($conn)
        ];
    } else {
        // Check columns
        $columns_query = mysqli_query($conn, "DESCRIBE $table");
        $existing_columns = [];
        
        while ($col = mysqli_fetch_assoc($columns_query)) {
            $existing_columns[] = $col['Field'];
        }
        
        $missing_columns = array_diff($required_columns, $existing_columns);
        
        if (empty($missing_columns)) {
            // Get row count
            $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM $table");
            $count = mysqli_fetch_assoc($count_query)['total'];
            
            $results[$table] = [
                'status' => 'OK',
                'message' => "Table exists with " . $count . " records",
                'columns' => count($existing_columns)
            ];
        } else {
            $results[$table] = [
                'status' => 'WARNING',
                'message' => 'Missing columns: ' . implode(', ', $missing_columns)
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Verification</title>
    <link rel="stylesheet" href="../bootstrap.min.css">
    <style>
        .container { margin-top: 40px; }
        .status-ok { background-color: #d4edda; border-color: #28a745; }
        .status-error { background-color: #f8d7da; border-color: #dc3545; }
        .status-warning { background-color: #fff3cd; border-color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Integration Verification</h1>
        <p class="text-muted">Checking all required tables and columns...</p>
        
        <div class="row mt-4">
            <div class="col-md-12">
                <?php foreach ($results as $table => $result): ?>
                    <div class="card mb-3 status-<?php echo strtolower($result['status']); ?>">
                        <div class="card-header">
                            <strong><?php echo $table; ?></strong>
                            <span class="badge badge-<?php echo ($result['status'] == 'OK') ? 'success' : 'danger'; ?>" style="float: right;">
                                <?php echo $result['status']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p><?php echo $result['message']; ?></p>
                            <?php if (isset($result['columns'])): ?>
                                <small class="text-muted">Columns: <?php echo $result['columns']; ?></small>
                            <?php endif; ?>
                            <?php if (isset($result['error'])): ?>
                                <pre class="text-danger"><?php echo $result['error']; ?></pre>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Summary</h3>
                <?php
                    $ok_count = count(array_filter($results, fn($r) => $r['status'] == 'OK'));
                    $error_count = count(array_filter($results, fn($r) => $r['status'] == 'ERROR'));
                    $warning_count = count(array_filter($results, fn($r) => $r['status'] == 'WARNING'));
                ?>
                <p>
                    <strong class="text-success">✓ OK: <?php echo $ok_count; ?></strong> | 
                    <strong class="text-danger">✗ Errors: <?php echo $error_count; ?></strong> | 
                    <strong class="text-warning">⚠ Warnings: <?php echo $warning_count; ?></strong>
                </p>
                
                <?php if ($error_count == 0 && $warning_count == 0): ?>
                    <div class="alert alert-success">
                        ✓ All tables are properly configured! You're ready to use the admin modules.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        ✗ Please run the database_schema.sql file to create/fix the tables.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <a href="payroll-admin.php" class="btn btn-primary">HR & Payroll</a>
                <a href="suppliers-admin.php" class="btn btn-primary">Suppliers</a>
                <a href="finances-admin.php" class="btn btn-primary">Finances</a>
                <a href="admin.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

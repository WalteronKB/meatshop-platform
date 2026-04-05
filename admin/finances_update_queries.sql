-- ========================================
-- FINANCE ADMIN DATABASE UPDATE QUERIES
-- Execute these queries in your MySQL database
-- ========================================

-- 1. Add finance approval columns to payroll table
ALTER TABLE mrb_payroll 
ADD COLUMN finance_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending' AFTER status,
ADD COLUMN finance_rejection_reason TEXT NULL AFTER finance_status,
ADD COLUMN finance_reviewed_by INT NULL AFTER finance_rejection_reason,
ADD COLUMN finance_reviewed_date DATETIME NULL AFTER finance_reviewed_by;

-- 2. Add finance approval columns to purchase orders table
ALTER TABLE mrb_purchase_orders 
ADD COLUMN finance_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending' AFTER status,
ADD COLUMN finance_rejection_reason TEXT NULL AFTER finance_status,
ADD COLUMN finance_reviewed_by INT NULL AFTER finance_rejection_reason,
ADD COLUMN finance_reviewed_date DATETIME NULL AFTER finance_reviewed_by;

-- 3. Create product income issues table
CREATE TABLE IF NOT EXISTS mrb_income_issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    income_source VARCHAR(150) NOT NULL,
    reported_amount DECIMAL(12, 2) NOT NULL,
    expected_amount DECIMAL(12, 2) NOT NULL,
    issue_description TEXT NOT NULL,
    reported_by INT NOT NULL,
    reported_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Open', 'Investigating', 'Resolved', 'Closed') DEFAULT 'Open',
    resolution_notes TEXT NULL,
    resolved_date DATETIME NULL,
    FOREIGN KEY (reported_by) REFERENCES mrb_users(user_id)
);

-- 4. Add index for better performance
CREATE INDEX idx_payroll_finance_status ON mrb_payroll(finance_status);
CREATE INDEX idx_po_finance_status ON mrb_purchase_orders(finance_status);
CREATE INDEX idx_income_issues_status ON mrb_income_issues(status);

-- 5. Create notifications table for finance admin (optional but recommended)
CREATE TABLE IF NOT EXISTS mrb_finance_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('Payroll', 'Purchase Order', 'Income Issue') NOT NULL,
    reference_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- End of queries

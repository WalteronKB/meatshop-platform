-- Attendance Table with Late Tracking
CREATE TABLE IF NOT EXISTS mrb_attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    check_in_time DATETIME NOT NULL,
    check_out_time DATETIME NULL,
    work_date DATE NOT NULL,
    is_late TINYINT(1) DEFAULT 0,
    minutes_late INT DEFAULT 0,
    late_deduction DECIMAL(10, 2) DEFAULT 0.00,
    daily_pay DECIMAL(10, 2) DEFAULT 0.00,
    net_pay DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('Present', 'Late', 'Absent') DEFAULT 'Present',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES mrb_employees(emp_id) ON DELETE CASCADE,
    INDEX idx_work_date (work_date),
    INDEX idx_emp_date (emp_id, work_date)
);

-- Attendance Settings Table (for late policy configuration)
CREATE TABLE IF NOT EXISTS mrb_attendance_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    work_start_time TIME NOT NULL DEFAULT '08:00:00',
    late_threshold_minutes INT NOT NULL DEFAULT 15,
    deduction_per_minute DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    fixed_late_deduction DECIMAL(10, 2) NOT NULL DEFAULT 50.00,
    deduction_type ENUM('per_minute', 'fixed') DEFAULT 'fixed',
    daily_rate_calculation ENUM('salary_per_day', 'custom') DEFAULT 'salary_per_day',
    working_days_per_month INT DEFAULT 26,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default attendance settings
INSERT INTO mrb_attendance_settings 
    (work_start_time, late_threshold_minutes, deduction_per_minute, fixed_late_deduction, deduction_type, working_days_per_month) 
VALUES 
    ('08:00:00', 15, 10.00, 50.00, 'fixed', 26)
ON DUPLICATE KEY UPDATE setting_id = setting_id;

-- Add daily_rate column to employees table if not exists
ALTER TABLE mrb_employees 
ADD COLUMN daily_rate DECIMAL(10, 2) NULL 
AFTER salary;

-- Update daily_rate based on monthly salary for existing employees
UPDATE mrb_employees 
SET daily_rate = salary / 26 
WHERE daily_rate IS NULL OR daily_rate = 0;

-- Payroll Details Enhancement - Add attendance-based fields
ALTER TABLE mrb_payroll_details 
ADD COLUMN days_worked INT DEFAULT 0 AFTER net_salary,
ADD COLUMN days_late INT DEFAULT 0 AFTER days_worked,
ADD COLUMN total_late_deductions DECIMAL(10, 2) DEFAULT 0.00 AFTER days_late,
ADD COLUMN attendance_pay DECIMAL(10, 2) DEFAULT 0.00 AFTER total_late_deductions;

-- Create index for faster queries
CREATE INDEX idx_attendance_emp_month ON mrb_attendance(emp_id, work_date);
CREATE INDEX idx_payroll_period ON mrb_payroll(payroll_period_start, payroll_period_end, status);

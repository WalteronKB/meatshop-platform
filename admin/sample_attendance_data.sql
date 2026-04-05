-- Sample Test Data for Attendance-Based Payroll System
-- Run this AFTER importing attendance_payroll_schema.sql
-- This creates sample attendance records for testing

-- Note: This assumes you already have employees in mrb_employees table
-- Adjust emp_id values to match your actual employee IDs

-- Sample attendance for last 15 days for testing payroll
-- Replace '1', '2', '3' with actual employee IDs from your database

-- Get current date for calculations
SET @today = CURDATE();
SET @yesterday = DATE_SUB(@today, INTERVAL 1 DAY);
SET @two_days_ago = DATE_SUB(@today, INTERVAL 2 DAYS);

-- Sample: Employee 1 - Perfect attendance, no late
INSERT INTO mrb_attendance (emp_id, check_in_time, check_out_time, work_date, is_late, minutes_late, late_deduction, daily_pay, net_pay, status) VALUES
(1, CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 07:55:00'), CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 14 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(1, CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 07:58:00'), CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 17:05:00'), DATE_SUB(@today, INTERVAL 13 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(1, CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 08:00:00'), CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 12 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(1, CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 07:50:00'), CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 11 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(1, CONCAT(DATE_SUB(@today, INTERVAL 10 DAYS), ' 08:05:00'), CONCAT(DATE_SUB(@today, INTERVAL 10 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 10 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present');

-- Sample: Employee 2 - Good attendance, 2 late days
INSERT INTO mrb_attendance (emp_id, check_in_time, check_out_time, work_date, is_late, minutes_late, late_deduction, daily_pay, net_pay, status) VALUES
(2, CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 08:20:00'), CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 14 DAYS), 1, 20, 50.00, 1000.00, 950.00, 'Late'),
(2, CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 07:55:00'), CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 13 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(2, CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 08:25:00'), CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 12 DAYS), 1, 25, 50.00, 1000.00, 950.00, 'Late'),
(2, CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 08:00:00'), CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 11 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(2, CONCAT(DATE_SUB(@today, INTERVAL 10 DAYS), ' 07:58:00'), CONCAT(DATE_SUB(@today, INTERVAL 10 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 10 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present');

-- Sample: Employee 3 - Mixed attendance, 3 late days
INSERT INTO mrb_attendance (emp_id, check_in_time, check_out_time, work_date, is_late, minutes_late, late_deduction, daily_pay, net_pay, status) VALUES
(3, CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 08:30:00'), CONCAT(DATE_SUB(@today, INTERVAL 14 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 14 DAYS), 1, 30, 50.00, 1000.00, 950.00, 'Late'),
(3, CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 08:18:00'), CONCAT(DATE_SUB(@today, INTERVAL 13 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 13 DAYS), 1, 18, 50.00, 1000.00, 950.00, 'Late'),
(3, CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 08:00:00'), CONCAT(DATE_SUB(@today, INTERVAL 12 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 12 DAYS), 0, 0, 0.00, 1000.00, 1000.00, 'Present'),
(3, CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 08:22:00'), CONCAT(DATE_SUB(@today, INTERVAL 11 DAYS), ' 17:00:00'), DATE_SUB(@today, INTERVAL 11 DAYS), 1, 22, 50.00, 1000.00, 950.00, 'Late');
-- Employee 3 absent on day 10 (no record)

-- Sample: Today's attendance (incomplete - no checkout yet)
INSERT INTO mrb_attendance (emp_id, check_in_time, check_out_time, work_date, is_late, minutes_late, late_deduction, daily_pay, net_pay, status) VALUES
(1, CONCAT(@today, ' 07:55:00'), NULL, @today, 0, 0, 0.00, 1000.00, 0.00, 'Present'),
(2, CONCAT(@today, ' 08:20:00'), NULL, @today, 1, 20, 50.00, 1000.00, 0.00, 'Late'),
(3, CONCAT(@today, ' 08:00:00'), NULL, @today, 0, 0, 0.00, 1000.00, 0.00, 'Present');

-- Verify the test data
SELECT 
    e.emp_number,
    e.emp_first_name,
    e.emp_last_name,
    COUNT(*) as days_attended,
    SUM(CASE WHEN a.is_late = 1 THEN 1 ELSE 0 END) as days_late,
    SUM(a.late_deduction) as total_deductions
FROM mrb_attendance a
JOIN mrb_employees e ON a.emp_id = e.emp_id
WHERE a.work_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAYS)
GROUP BY e.emp_id
ORDER BY e.emp_number;

-- Expected Output:
-- Employee 1: 6 days, 0 late, ₱0 deductions
-- Employee 2: 6 days, 2 late, ₱100 deductions  
-- Employee 3: 5 days, 3 late, ₱150 deductions

-- Test payroll calculation manually
SELECT 
    e.emp_number,
    e.emp_first_name,
    e.emp_last_name,
    COUNT(CASE WHEN a.check_out_time IS NOT NULL THEN 1 END) as completed_days,
    SUM(CASE WHEN a.is_late = 1 AND a.check_out_time IS NOT NULL THEN 1 ELSE 0 END) as late_days,
    COUNT(CASE WHEN a.check_out_time IS NOT NULL THEN 1 END) * e.daily_rate as gross_pay,
    SUM(CASE WHEN a.check_out_time IS NOT NULL THEN a.late_deduction ELSE 0 END) as deductions,
    (COUNT(CASE WHEN a.check_out_time IS NOT NULL THEN 1 END) * e.daily_rate) - 
    SUM(CASE WHEN a.check_out_time IS NOT NULL THEN a.late_deduction ELSE 0 END) as net_pay
FROM mrb_attendance a
JOIN mrb_employees e ON a.emp_id = e.emp_id
WHERE a.work_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAYS) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
GROUP BY e.emp_id
ORDER BY e.emp_number;

-- Notes for testing:
-- 1. Today's records have no check-out, so won't be counted in payroll
-- 2. Process payroll for date range: 14 days ago to yesterday
-- 3. Expected results:
--    Employee 1: 5 days × ₱1000 - ₱0 = ₱5,000
--    Employee 2: 5 days × ₱1000 - ₱100 = ₱4,900
--    Employee 3: 4 days × ₱1000 - ₱150 = ₱3,850
--    Total: ₱13,750

-- Clean up test data (run this if you want to remove test data)
-- DELETE FROM mrb_attendance WHERE emp_id IN (1, 2, 3) AND work_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAYS);

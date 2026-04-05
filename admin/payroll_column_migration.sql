-- ========================================
-- PAYROLL TABLE COLUMN MIGRATION
-- Run this ONLY if you want to standardize column names
-- Date: March 1, 2026
-- ========================================

-- IMPORTANT: The current database uses: total_employees, total_deductions
-- This migration is OPTIONAL - the code supports both old and new column names

-- Check current structure first (run this to see what columns you have):
-- DESCRIBE mrb_payroll;

-- Option 1: Keep using old column names (total_employees, total_deductions)
-- No action needed - the code already supports these column names

-- Option 2: Rename to new standard names (employee_count, deductions_amount)
-- Uncomment the lines below to perform the migration:

-- ALTER TABLE mrb_payroll 
-- CHANGE COLUMN total_employees employee_count INT;

-- ALTER TABLE mrb_payroll 
-- CHANGE COLUMN total_deductions deductions_amount DECIMAL(12, 2);

-- After migration, update process_payroll.php line 130-131 to use:
-- employee_count and deductions_amount instead of total_employees and total_deductions

-- Verify the changes:
-- DESCRIBE mrb_payroll;

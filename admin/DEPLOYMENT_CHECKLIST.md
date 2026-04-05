# Deployment Checklist: Attendance-Based Payroll System

## Pre-Deployment Steps

### 1. Database Backup ⚠️
```bash
# Backup your database FIRST!
mysqldump -u root -p your_database_name > backup_before_attendance_$(date +%Y%m%d).sql
```

### 2. Verify Files
Check that all files exist:
```
✓ admin/attendance_payroll_schema.sql
✓ admin/handlers/process_payroll.php
✓ admin/handlers/update_attendance_settings.php
✓ admin/handlers/get_payroll_details.php
✓ admin/handlers/process_attendance.php (modified)
✓ admin/payroll-admin.php (modified)
```

---

## Deployment Steps (Run in Order)

### Step 1: Import Database Schema
**Time**: 2 minutes

**Option A - Command Line:**
```bash
cd C:\xampp\mysql\bin
.\mysql.exe -u root your_database_name < C:\xampp\htdocs\Copycat\admin\attendance_payroll_schema.sql
```

**Option B - phpMyAdmin:**
1. Open http://localhost/phpmyadmin
2. Select your database
3. Click "Import"
4. Choose `attendance_payroll_schema.sql`
5. Click "Go"

**Verify:**
```sql
-- Run these queries to verify
SHOW TABLES LIKE 'mrb_attendance%';
-- Should show: mrb_attendance, mrb_attendance_settings

DESCRIBE mrb_employees;
-- Should include: daily_rate column

DESCRIBE mrb_payroll_details;
-- Should include: days_worked, days_late, total_late_deductions, attendance_pay
```

### Step 2: Verify Default Settings
**Time**: 1 minute

```sql
SELECT * FROM mrb_attendance_settings WHERE is_active = 1;
```

**Expected Result:**
```
work_start_time: 08:00:00
late_threshold_minutes: 15
deduction_type: fixed
fixed_late_deduction: 50.00
working_days_per_month: 26
is_active: 1
```

### Step 3: Update Employee Daily Rates
**Time**: 1 minute

```sql
-- This should already be done by schema, but verify:
SELECT emp_id, salary, daily_rate FROM mrb_employees LIMIT 5;

-- If daily_rate is NULL, run:
UPDATE mrb_employees SET daily_rate = salary / 26 WHERE daily_rate IS NULL;
```

### Step 4: Test Attendance
**Time**: 3 minutes

1. Login to admin panel
2. Go to **HR & Payroll**
3. Click **Settings** (should open modal)
4. Verify default settings are displayed
5. Close settings
6. Click **Scan Attendance**
7. View QR code for an employee
8. Scan the QR code
9. Verify check-in recorded
10. Check today's attendance table shows the record

### Step 5: Test Late Detection
**Time**: 2 minutes

**Manual Test:**
```sql
-- Simulate a late check-in for testing
-- (Replace X with actual emp_id)
INSERT INTO mrb_attendance 
(emp_id, check_in_time, work_date, is_late, minutes_late, late_deduction, daily_pay, status)
VALUES
(X, '2026-02-03 08:30:00', '2026-02-03', 1, 30, 50.00, 1000.00, 'Late');
```

**Verify:**
- Go to payroll-admin.php
- Check today's attendance table
- Should show "Late" status with red deduction amount

### Step 6: Test Payroll Processing
**Time**: 3 minutes

1. Ensure you have attendance records with check-outs
2. Click **Process Payroll**
3. Enter date range (use dates with attendance records)
4. Click **Process Payroll**
5. Verify success message
6. Check payroll table shows new record
7. Click **View** on the payroll record
8. Verify detailed breakdown appears

---

## Post-Deployment Verification

### 1. Database Integrity
```sql
-- Check foreign keys
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME IN ('mrb_attendance', 'mrb_payroll_details');

-- Check indexes
SHOW INDEX FROM mrb_attendance;
SHOW INDEX FROM mrb_payroll_details;
```

### 2. Functionality Tests

#### ✅ Attendance Features
- [ ] Can open attendance settings
- [ ] Can save new settings
- [ ] QR scanner works
- [ ] Check-in creates record
- [ ] Late detection works correctly
- [ ] Check-out updates record
- [ ] Today's attendance displays correctly

#### ✅ Payroll Features
- [ ] Can process new payroll
- [ ] Calculations are correct
- [ ] Details modal opens
- [ ] Employee breakdown displays
- [ ] Totals match individual records

#### ✅ Settings Features
- [ ] Can change work start time
- [ ] Can adjust grace period
- [ ] Can switch deduction types
- [ ] Can modify deduction amounts
- [ ] Can change working days
- [ ] Daily rates update after save

### 3. User Experience
- [ ] No JavaScript errors in console
- [ ] Modals open/close smoothly
- [ ] Forms validate properly
- [ ] Success/error messages display
- [ ] Responsive on mobile
- [ ] Print-friendly payroll view

---

## Rollback Plan (If Needed)

### If Something Goes Wrong:

1. **Restore Database Backup**
```bash
mysql -u root -p your_database_name < backup_before_attendance_YYYYMMDD.sql
```

2. **Restore Original Files**
```bash
# Restore payroll-admin.php from backup
# Restore process_attendance.php from backup
```

3. **Remove New Files**
```bash
# Delete if causing issues:
rm admin/handlers/process_payroll.php
rm admin/handlers/update_attendance_settings.php
rm admin/handlers/get_payroll_details.php
```

---

## Common Issues & Solutions

### Issue: Tables not created
**Solution**: 
```sql
-- Check if user has CREATE TABLE permission
SHOW GRANTS FOR 'root'@'localhost';

-- Manually create if needed (check schema file)
```

### Issue: "Column 'daily_rate' doesn't exist"
**Solution**:
```sql
-- Add column manually
ALTER TABLE mrb_employees 
ADD COLUMN daily_rate DECIMAL(10, 2) NULL AFTER salary;

-- Update values
UPDATE mrb_employees SET daily_rate = salary / 26;
```

### Issue: Settings modal doesn't open
**Solution**:
- Check browser console for JavaScript errors
- Verify Bootstrap JS is loaded
- Clear browser cache

### Issue: Late detection not working
**Solution**:
```sql
-- Verify settings exist
SELECT * FROM mrb_attendance_settings WHERE is_active = 1;

-- If empty, insert default
INSERT INTO mrb_attendance_settings 
(work_start_time, late_threshold_minutes, fixed_late_deduction, deduction_type, is_active)
VALUES ('08:00:00', 15, 50.00, 'fixed', 1);
```

### Issue: Payroll processing fails
**Solution**:
- Check attendance records have check_out_time
- Verify employee has daily_rate set
- Check MySQL error log

---

## Configuration Recommendations

### For Small Business (< 20 employees)
```
Work Start: 08:00 AM
Grace Period: 20 minutes
Deduction Type: Fixed
Fixed Amount: ₱50
Working Days: 26
```

### For Medium Business (20-100 employees)
```
Work Start: 08:00 AM
Grace Period: 15 minutes
Deduction Type: Fixed
Fixed Amount: ₱75
Working Days: 26
```

### For Strict Punctuality
```
Work Start: 08:00 AM
Grace Period: 5 minutes
Deduction Type: Per Minute
Per Minute Rate: ₱10
Working Days: 26
```

---

## Performance Tuning (For Large Deployments)

### If you have 100+ employees:

1. **Add Additional Indexes**
```sql
CREATE INDEX idx_attendance_checkout ON mrb_attendance(check_out_time);
CREATE INDEX idx_payroll_processed ON mrb_payroll(processed_date);
```

2. **Archive Old Records**
```sql
-- Archive attendance older than 1 year
CREATE TABLE mrb_attendance_archive LIKE mrb_attendance;
INSERT INTO mrb_attendance_archive 
SELECT * FROM mrb_attendance 
WHERE work_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM mrb_attendance 
WHERE work_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

3. **Optimize Tables Monthly**
```sql
OPTIMIZE TABLE mrb_attendance;
OPTIMIZE TABLE mrb_payroll;
OPTIMIZE TABLE mrb_payroll_details;
```

---

## Training Users

### For Employees:
1. Show how to check-in with QR code
2. Explain late threshold and deductions
3. Demonstrate check-out process
4. Show where to view attendance history

### For Administrators:
1. How to configure settings
2. How to process payroll
3. How to view detailed reports
4. Where to check for issues

---

## Maintenance Schedule

### Daily:
- Monitor attendance dashboard
- Verify check-ins/outs are recorded

### Weekly:
- Review late arrivals
- Check system performance
- Backup attendance data

### Monthly:
- Process payroll
- Review settings effectiveness
- Archive old records (if needed)
- Run database optimization

### Quarterly:
- Review and adjust policies
- Update deduction amounts if needed
- Train new staff

---

## Final Checklist Before Going Live

- [ ] Database backup completed
- [ ] All files uploaded/in place
- [ ] Database schema imported successfully
- [ ] Default settings configured
- [ ] Employee daily rates calculated
- [ ] Attendance test successful
- [ ] Late detection test successful
- [ ] Payroll processing test successful
- [ ] All modals working correctly
- [ ] No JavaScript console errors
- [ ] Mobile responsive verified
- [ ] Users trained on new system
- [ ] Rollback plan documented
- [ ] Support contact ready

---

## Go-Live Announcement Template

```
Subject: New Attendance-Based Payroll System - Effective [Date]

Dear Team,

We are implementing a new attendance system effective [Date].

KEY CHANGES:
• Daily attendance via QR code scanning
• Payroll based on actual days worked
• Late arrivals (after 8:15 AM) will incur ₱50 deduction
• Check-in and check-out required daily

WHAT YOU NEED TO DO:
1. Scan your QR code when you arrive
2. Scan again when you leave
3. Your daily pay will be calculated automatically

BENEFITS:
• Fair pay based on attendance
• Transparent deduction policy
• Accurate payroll records

Questions? Contact [Admin Name] at [Contact Info]

Thank you for your cooperation!
```

---

## Success Metrics

After 1 week, verify:
- [ ] 95%+ employees using QR system
- [ ] Attendance records complete
- [ ] No major technical issues
- [ ] Users comfortable with system

After 1 month, verify:
- [ ] Payroll processed smoothly
- [ ] Deductions calculated correctly
- [ ] Reports are accurate
- [ ] Users satisfied with system

---

**Status**: Ready for Deployment ✅  
**Estimated Deployment Time**: 15-20 minutes  
**Risk Level**: Low (with backup)  
**User Impact**: Minimal (improved process)

🚀 **Ready to deploy!**

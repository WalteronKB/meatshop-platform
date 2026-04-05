# Attendance-Based Payroll System with Late Deductions

## Overview
This system implements a complete attendance-based payroll solution that:
- Tracks employee daily attendance via QR code scanning
- Automatically detects and records late arrivals
- Applies configurable deductions for late check-ins
- Calculates payroll based on actual days worked
- Generates detailed payroll reports with attendance breakdown

## Database Setup

### Step 1: Run the Schema
Execute the SQL file to create required tables and add necessary columns:

```bash
mysql -u root -p your_database < admin/attendance_payroll_schema.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Go to Import tab
4. Choose `admin/attendance_payroll_schema.sql`
5. Click Go

### Tables Created/Modified:

#### 1. `mrb_attendance`
Stores daily attendance records with late tracking:
- `attendance_id` - Primary key
- `emp_id` - Foreign key to mrb_employees
- `check_in_time` - When employee checked in
- `check_out_time` - When employee checked out
- `work_date` - Date of attendance
- `is_late` - Boolean flag if employee was late
- `minutes_late` - How many minutes late
- `late_deduction` - Amount deducted for being late
- `daily_pay` - Base daily pay amount
- `net_pay` - Daily pay minus deductions
- `status` - Present, Late, or Absent

#### 2. `mrb_attendance_settings`
Configurable attendance policies:
- `work_start_time` - Official start time (default: 08:00:00)
- `late_threshold_minutes` - Grace period before marked late (default: 15)
- `deduction_per_minute` - Amount per minute if using per-minute deduction
- `fixed_late_deduction` - Fixed amount if using fixed deduction (default: ₱50.00)
- `deduction_type` - 'fixed' or 'per_minute'
- `working_days_per_month` - Used to calculate daily rate (default: 26)

#### 3. `mrb_employees` (Modified)
Added:
- `daily_rate` - Calculated as salary / working_days_per_month

#### 4. `mrb_payroll_details` (Modified)
Enhanced with attendance fields:
- `days_worked` - Total days attended
- `days_late` - Total days late
- `total_late_deductions` - Sum of all late deductions
- `attendance_pay` - Total pay based on attendance

## How It Works

### Attendance Process

1. **Check-In**
   - Employee scans their QR code
   - System records check-in time
   - Compares check-in time against work start time + grace period
   - If late: Calculates and records deduction
   - Employee receives confirmation with late status

2. **Late Calculation**
   ```
   Official Start: 08:00 AM
   Grace Period: 15 minutes
   Late Threshold: 08:15 AM
   
   If employee checks in at 08:20 AM:
   - Minutes Late: 20 minutes
   - Is Late: Yes
   - Deduction (Fixed): ₱50.00
   - OR Deduction (Per Minute): 20 × ₱10 = ₱200.00
   ```

3. **Check-Out**
   - Employee scans QR code again
   - System records check-out time
   - Finalizes net pay for the day: `Net Pay = Daily Rate - Late Deduction`

### Payroll Processing

1. **Navigate to HR & Payroll**
2. **Click "Process Payroll"**
3. **Select Period** (e.g., Dec 1 - Dec 15)
4. **System Automatically:**
   - Counts days worked for each employee
   - Counts days late
   - Sums late deductions
   - Calculates: `Gross = Days Worked × Daily Rate`
   - Calculates: `Net = Gross - Total Late Deductions`
   - Creates payroll record with complete breakdown

### Viewing Payroll Details

Click "View" on any payroll record to see:
- Period and processing date
- Summary totals (Gross, Deductions, Net)
- Per-employee breakdown showing:
  - Days worked
  - Days late
  - Late deductions
  - Net pay

## Configuration

### Attendance Settings

Access via: **HR & Payroll → Settings button (next to Scan Attendance)**

Configure:
1. **Work Start Time** - When work officially begins
2. **Late Threshold** - Grace period in minutes
3. **Deduction Type**:
   - **Fixed Amount** - Same deduction regardless of how late
   - **Per Minute** - Multiply minutes late by rate
4. **Deduction Amounts** - Set your rates
5. **Working Days** - For daily rate calculation (typically 26)

### Example Configurations

**Lenient Policy:**
```
Start Time: 08:00 AM
Grace Period: 30 minutes
Deduction Type: Fixed
Fixed Deduction: ₱25.00
```

**Strict Policy:**
```
Start Time: 08:00 AM
Grace Period: 5 minutes
Deduction Type: Per Minute
Per Minute Rate: ₱15.00
```

## Files Created/Modified

### New Files:
1. `admin/attendance_payroll_schema.sql` - Database schema
2. `admin/handlers/process_payroll.php` - Payroll processing logic
3. `admin/handlers/update_attendance_settings.php` - Settings management
4. `admin/handlers/get_payroll_details.php` - Payroll details API

### Modified Files:
1. `admin/handlers/process_attendance.php` - Enhanced with late tracking
2. `admin/payroll-admin.php` - Added settings UI and payroll details view

## Usage Examples

### Daily Operations
1. Employees check in using QR codes
2. System automatically tracks late arrivals
3. View today's attendance on the dashboard
4. Late employees show red deduction amounts

### Monthly Payroll
1. At end of period, click "Process Payroll"
2. Enter date range (e.g., Dec 1-31)
3. System generates complete payroll with attendance-based calculations
4. Review employee breakdown
5. Export or process payments

## Features

✅ QR Code-based attendance tracking  
✅ Automatic late detection with grace period  
✅ Configurable deduction policies (fixed or per-minute)  
✅ Daily rate calculation from monthly salary  
✅ Attendance-based payroll generation  
✅ Detailed payroll reports with employee breakdown  
✅ Real-time attendance dashboard  
✅ Late deduction indicators in attendance records  
✅ Transaction-safe payroll processing  

## Benefits

- **Accurate Payroll**: Pay based on actual attendance
- **Fair Late Policy**: Configurable thresholds and deductions
- **Transparency**: Employees see deductions at check-in
- **Automation**: Reduces manual payroll calculations
- **Audit Trail**: Complete attendance and deduction records
- **Flexibility**: Adjust policies without code changes

## Troubleshooting

### Issue: Late deductions not showing
**Solution**: Ensure `mrb_attendance_settings` table has active settings

### Issue: Payroll processing fails
**Solution**: Check that employees have attendance records with completed check-outs

### Issue: Daily rates are zero
**Solution**: Run: `UPDATE mrb_employees SET daily_rate = salary / 26 WHERE daily_rate IS NULL`

### Issue: Can't update settings
**Solution**: Verify `mrb_attendance_settings` table exists and is accessible

## Support

For issues or questions:
1. Check database schema is properly created
2. Verify all handler files exist in `admin/handlers/`
3. Ensure attendance settings are configured
4. Check PHP error logs for detailed errors

---

**Version**: 1.0  
**Created**: February 2026  
**Database**: MySQL/MariaDB  
**Framework**: PHP + Bootstrap 5

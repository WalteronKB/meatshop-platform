# Quick Start Guide: Attendance-Based Payroll

## Step-by-Step Setup (5 minutes)

### 1. Import Database Schema
Open your terminal in the XAMPP directory:

```bash
# Windows (PowerShell)
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p your_database_name < C:\xampp\htdocs\Copycat\admin\attendance_payroll_schema.sql
```

Or use phpMyAdmin:
- Visit: http://localhost/phpmyadmin
- Select your database
- Click "Import"
- Choose: `admin/attendance_payroll_schema.sql`
- Click "Go"

### 2. Configure Attendance Settings
1. Login to admin panel
2. Go to **HR & Payroll**
3. Click **Settings** button (next to "Scan Attendance")
4. Configure:
   - Work Start Time: `08:00 AM`
   - Late Threshold: `15 minutes`
   - Deduction Type: `Fixed Amount`
   - Fixed Deduction: `₱50.00`
   - Working Days: `26`
5. Click **Save Settings**

### 3. Test Attendance
1. Click **Scan Attendance**
2. Scan an employee QR code (or click "View QR" on an employee)
3. System will record check-in and show if late
4. Scan again to check-out

### 4. Process First Payroll
1. Click **Process Payroll**
2. Enter period dates (e.g., today's date for both start and end)
3. Click **Process Payroll**
4. View the generated payroll with attendance breakdown

## Key Features

### Attendance Dashboard
- See who's present today
- View late arrivals with deduction amounts
- Track check-in/check-out times

### Settings Panel
```
Work Schedule
├─ Start Time: When work begins
├─ Grace Period: Minutes before marked late
└─ Late Policy: How to calculate deductions

Deduction Types
├─ Fixed: Same amount every time (₱50)
└─ Per Minute: Multiply by minutes late (₱10 × 20 min = ₱200)
```

### Payroll Processing
```
Input: Date Range (Dec 1 - Dec 15)
↓
Calculate for each employee:
├─ Days Worked × Daily Rate = Gross Pay
├─ Sum of Late Deductions = Total Deductions
└─ Gross - Deductions = Net Pay
↓
Output: Complete Payroll Report
```

## Daily Workflow

### Morning
1. Employees arrive and scan QR codes
2. System shows "On time ✓" or "Late ⚠️ -₱50"
3. Monitor attendance dashboard

### Evening
1. Employees scan QR codes to check out
2. System finalizes daily pay calculations

### End of Period
1. Process payroll for the date range
2. Review employee breakdown
3. Export or proceed with payments

## Example Scenario

**Employee: Juan Dela Cruz**  
**Monthly Salary: ₱26,000**  
**Daily Rate: ₱26,000 ÷ 26 = ₱1,000**

**Attendance (Dec 1-15):**
- Days Worked: 12 days
- Days Late: 2 days
- Late Deduction: ₱50 × 2 = ₱100

**Payroll Calculation:**
```
Gross Pay = 12 days × ₱1,000 = ₱12,000
Deductions = ₱100
Net Pay = ₱12,000 - ₱100 = ₱11,900
```

## Tips

💡 **Set grace period wisely** - 15 minutes is common for traffic delays  
💡 **Use fixed deduction** for simplicity - easier to explain to employees  
💡 **Process payroll regularly** - Weekly or bi-weekly for better tracking  
💡 **Review settings monthly** - Adjust based on company policy changes  

## Common Questions

**Q: Can I change the deduction amount later?**  
A: Yes, in Settings. Only affects future attendance.

**Q: What if employee forgets to check out?**  
A: Admin can manually process, or it won't count as a complete day.

**Q: Can I have different rates for different employees?**  
A: Currently uses same policy for all. Customize `process_attendance.php` for individual rates.

**Q: How do I export payroll data?**  
A: View payroll details modal and copy/print, or add export feature to code.

## Need Help?

Check the full documentation: `ATTENDANCE_PAYROLL_README.md`

---

🎉 **You're all set!** Start scanning and tracking attendance today.

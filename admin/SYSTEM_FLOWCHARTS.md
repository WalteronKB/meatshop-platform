# System Flow Diagrams - Attendance-Based Payroll

## 1. Daily Attendance Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    EMPLOYEE ARRIVES AT WORK                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
                  ┌──────────────────────┐
                  │  Scan QR Code Badge  │
                  └──────────┬───────────┘
                             │
                             ▼
              ┌──────────────────────────────┐
              │  System Records Check-In     │
              │  - emp_id                    │
              │  - check_in_time             │
              │  - work_date                 │
              └──────────┬───────────────────┘
                         │
                         ▼
        ┌────────────────────────────────────┐
        │  Check: Is Time > Start + Grace?  │
        │  (e.g., > 08:15 AM)                │
        └────┬──────────────────────┬────────┘
             │ YES (LATE)           │ NO (ON TIME)
             ▼                      ▼
    ┌─────────────────┐    ┌────────────────┐
    │  Calculate       │    │  No Deduction  │
    │  Deduction:      │    │  is_late = 0   │
    │  - Fixed: ₱50    │    │  deduction = 0 │
    │  - OR Per Min    │    └────────┬───────┘
    │  is_late = 1     │             │
    └────────┬─────────┘             │
             │                       │
             └───────────┬───────────┘
                         │
                         ▼
            ┌────────────────────────┐
            │  Save to Database:     │
            │  - All check-in data   │
            │  - Late status         │
            │  - Deduction amount    │
            │  - Daily rate          │
            └────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │  Show Confirmation Message:    │
        │  "Welcome Juan Dela Cruz!"     │
        │  "8:20 AM - Late ⚠️ -₱50"     │
        │  OR "8:00 AM - On time ✓"     │
        └────────────────────────────────┘
                     │
          ... EMPLOYEE WORKS ...
                     │
                     ▼
        ┌────────────────────────────┐
        │  END OF DAY: Scan Again    │
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  System Records Check-Out  │
        │  - check_out_time          │
        │  Calculate:                │
        │  net_pay = daily_pay -     │
        │            late_deduction  │
        └────────────────────────────┘
```

---

## 2. Late Detection Logic

```
START CHECK-IN
      │
      ▼
┌─────────────────────────┐
│ Get Current Time        │
│ e.g., 08:20:00         │
└───────┬─────────────────┘
        │
        ▼
┌─────────────────────────┐
│ Get Settings:           │
│ - start_time: 08:00:00  │
│ - grace_period: 15 min  │
│ - late_threshold:       │
│   08:00:00 + 15 min     │
│   = 08:15:00            │
└───────┬─────────────────┘
        │
        ▼
┌─────────────────────────────┐
│ Compare:                    │
│ Current (08:20:00) >        │
│ Threshold (08:15:00) ?      │
└───┬──────────────────┬──────┘
    │ YES              │ NO
    ▼                  ▼
┌────────────┐    ┌──────────┐
│ LATE       │    │ ON TIME  │
│ is_late=1  │    │ is_late=0│
└─────┬──────┘    └─────┬────┘
      │                 │
      ▼                 │
┌──────────────────┐    │
│ Calculate Late   │    │
│ Minutes:         │    │
│ 08:20 - 08:00    │    │
│ = 20 minutes     │    │
└─────┬────────────┘    │
      │                 │
      ▼                 │
┌──────────────────┐    │
│ Deduction Type?  │    │
└┬────────────┬────┘    │
 │FIXED       │PER MIN  │
 ▼            ▼         │
┌──────┐  ┌─────────┐  │
│ ₱50  │  │20×₱10   │  │
│      │  │= ₱200   │  │
└──┬───┘  └────┬────┘  │
   │           │       │
   └─────┬─────┘       │
         │             │
         └──────┬──────┘
                │
                ▼
        ┌───────────────┐
        │ SAVE RECORD   │
        └───────────────┘
```

---

## 3. Payroll Processing Flow

```
ADMIN CLICKS "PROCESS PAYROLL"
            │
            ▼
┌────────────────────────────┐
│ Enter Date Range:          │
│ Start: 2026-02-01         │
│ End: 2026-02-15           │
└─────────┬──────────────────┘
          │
          ▼
┌───────────────────────────────┐
│ Query All Active Employees    │
└─────────┬─────────────────────┘
          │
          ▼
   FOR EACH EMPLOYEE:
          │
          ▼
┌──────────────────────────────────┐
│ Query Attendance Records         │
│ WHERE:                           │
│ - emp_id = current employee      │
│ - work_date BETWEEN start & end  │
│ - check_out_time IS NOT NULL    │
│   (completed days only)          │
└─────────┬────────────────────────┘
          │
          ▼
┌──────────────────────────────────┐
│ Calculate:                       │
│                                  │
│ days_worked = COUNT(records)     │
│ days_late = COUNT(is_late=1)     │
│ total_late_deductions =          │
│   SUM(late_deduction)            │
│                                  │
│ gross = days_worked × daily_rate │
│ net = gross - total_deductions   │
└─────────┬────────────────────────┘
          │
          ▼
┌──────────────────────────────────┐
│ Example Calculation:             │
│                                  │
│ Juan Dela Cruz:                  │
│ Daily Rate: ₱1,000               │
│ Days Worked: 12                  │
│ Days Late: 2                     │
│                                  │
│ Gross = 12 × ₱1,000 = ₱12,000   │
│ Deductions = 2 × ₱50 = ₱100     │
│ Net = ₱12,000 - ₱100 = ₱11,900  │
└─────────┬────────────────────────┘
          │
          ▼
┌──────────────────────────────────┐
│ Store in payroll_details:       │
│ - payroll_id (FK)                │
│ - emp_id                         │
│ - gross_salary: ₱12,000          │
│ - deductions: ₱100               │
│ - net_salary: ₱11,900            │
│ - days_worked: 12                │
│ - days_late: 2                   │
└─────────┬────────────────────────┘
          │
          ▼
   REPEAT FOR ALL EMPLOYEES
          │
          ▼
┌──────────────────────────────────┐
│ Sum All Employees:               │
│                                  │
│ Total Employees: 15              │
│ Total Gross: ₱180,000            │
│ Total Deductions: ₱1,500         │
│ Total Net: ₱178,500              │
└─────────┬────────────────────────┘
          │
          ▼
┌──────────────────────────────────┐
│ Create Payroll Record:           │
│ - period_start                   │
│ - period_end                     │
│ - employee_count: 15             │
│ - gross_amount: ₱180,000         │
│ - deductions_amount: ₱1,500      │
│ - net_amount: ₱178,500           │
│ - status: 'Processed'            │
│ - processed_date: NOW()          │
└─────────┬────────────────────────┘
          │
          ▼
┌──────────────────────────────────┐
│ Show Success Message:            │
│ "Payroll processed successfully!"│
│ "Total: ₱178,500 for 15 employees"│
└──────────────────────────────────┘
```

---

## 4. Settings Configuration Flow

```
ADMIN OPENS SETTINGS
         │
         ▼
┌─────────────────────────┐
│ Load Current Settings:  │
│ - work_start_time       │
│ - late_threshold        │
│ - deduction_type        │
│ - deduction_amounts     │
│ - working_days          │
└──────┬──────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Display in Form:         │
│                          │
│ Start Time: [08:00]     │
│ Grace Period: [15] min   │
│ Type: [Fixed] ▼         │
│ Fixed Amount: [₱50.00]  │
│ Working Days: [26]       │
└──────┬───────────────────┘
       │
       ▼
  ADMIN MODIFIES
       │
       ▼
┌──────────────────────────┐
│ Validate Input:          │
│ - Times are valid        │
│ - Numbers are positive   │
│ - Required fields filled │
└──────┬───────────────────┘
       │ VALID
       ▼
┌──────────────────────────┐
│ Deactivate Old Settings: │
│ UPDATE SET is_active=0   │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Insert New Settings:     │
│ - New configuration      │
│ - is_active = 1          │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Recalculate Daily Rates: │
│ UPDATE mrb_employees     │
│ SET daily_rate =         │
│     salary /             │
│     new_working_days     │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│ Show Success:            │
│ "Settings Updated!"      │
│                          │
│ Future attendance will   │
│ use new settings         │
└──────────────────────────┘
```

---

## 5. Data Relationships

```
┌─────────────────┐
│ mrb_employees   │
│ ├─ emp_id (PK)  │◄─────┐
│ ├─ emp_number   │      │
│ ├─ salary       │      │
│ └─ daily_rate   │      │
└─────────────────┘      │
                         │ REFERENCES
                         │
┌─────────────────────────┴───────┐
│ mrb_attendance                  │
│ ├─ attendance_id (PK)           │
│ ├─ emp_id (FK) ─────────────────┤
│ ├─ check_in_time                │
│ ├─ check_out_time               │
│ ├─ work_date                    │
│ ├─ is_late                      │
│ ├─ minutes_late                 │
│ ├─ late_deduction               │
│ ├─ daily_pay                    │
│ └─ net_pay                      │
└─────────────────────────────────┘
                         │
                         │ AGGREGATED INTO
                         │
                         ▼
┌──────────────────────────────────┐
│ mrb_payroll                      │
│ ├─ payroll_id (PK)              │
│ ├─ payroll_period_start         │
│ ├─ payroll_period_end           │
│ ├─ employee_count               │
│ ├─ gross_amount                 │
│ ├─ deductions_amount            │
│ └─ net_amount                   │
└───────┬──────────────────────────┘
        │ HAS MANY
        │
        ▼
┌──────────────────────────────────┐
│ mrb_payroll_details              │
│ ├─ detail_id (PK)               │
│ ├─ payroll_id (FK) ──────────────┤
│ ├─ emp_id (FK) ──────────────────┤
│ ├─ gross_salary                 │
│ ├─ deductions                   │
│ ├─ net_salary                   │
│ ├─ days_worked                  │
│ ├─ days_late                    │
│ └─ total_late_deductions        │
└──────────────────────────────────┘

┌─────────────────────────────┐
│ mrb_attendance_settings     │
│ ├─ setting_id (PK)          │
│ ├─ work_start_time          │
│ ├─ late_threshold_minutes   │
│ ├─ deduction_per_minute     │
│ ├─ fixed_late_deduction     │
│ ├─ deduction_type           │
│ ├─ working_days_per_month   │
│ └─ is_active                │
└─────────────────────────────┘
        │
        │ USED BY
        ▼
   ATTENDANCE PROCESS
```

---

## 6. User Interface Flow

```
┌─────────────────────────────────────────┐
│         PAYROLL ADMIN PAGE              │
└─┬────────┬────────┬────────┬────────────┘
  │        │        │        │
  ▼        ▼        ▼        ▼
┌────┐  ┌────┐  ┌────┐  ┌──────┐
│Stats│  │Scan│  │Set │  │Process│
│Cards│  │QR  │  │tings│ │Payroll│
└────┘  └─┬──┘  └─┬──┘  └───┬───┘
          │        │         │
          ▼        ▼         ▼
     ┌────────┐ ┌────────┐ ┌────────┐
     │Scanner │ │Settings│ │Period  │
     │Modal   │ │Modal   │ │Form    │
     └───┬────┘ └───┬────┘ └───┬────┘
         │          │          │
         ▼          ▼          ▼
     ┌────────┐ ┌────────┐ ┌────────┐
     │Process │ │Save &  │ │Calculate│
     │Attend. │ │Update  │ │&Generate│
     └───┬────┘ └───┬────┘ └───┬────┘
         │          │          │
         └──────────┴──────────┘
                    │
                    ▼
            ┌───────────────┐
            │ Toast Message │
            │ Success/Error │
            └───────────────┘
                    │
                    ▼
            ┌───────────────┐
            │  Page Reload  │
            │ Show Updated  │
            │    Data       │
            └───────────────┘
```

---

## Summary

These flows demonstrate:
1. **Daily Operations**: How attendance is recorded and late fees calculated
2. **Policy Enforcement**: How settings control late detection
3. **Payroll Generation**: How attendance aggregates into payroll
4. **Data Integrity**: How tables relate and data flows
5. **User Experience**: How admins interact with the system

Each component works together to create a complete, automated payroll system based on actual attendance with fair late policies.

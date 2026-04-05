# Quick Start Guide - Database Integration

## 🚀 Get Started in 3 Steps

### Step 1: Run Database Setup (2 minutes)
```bash
# In phpMyAdmin or MySQL command line, paste and execute:
# File: admin/database_schema.sql
```
✅ This creates all 9 tables needed for the system

### Step 2: Verify Installation (1 minute)
```
Go to: http://your-site/admin/verify_database.php
```
✅ Should show all 7 tables with "OK" status

### Step 3: Start Adding Data (Immediate)
```
Open any of these pages:
- http://your-site/admin/payroll-admin.php
- http://your-site/admin/suppliers-admin.php
- http://your-site/admin/finances-admin.php
```
✅ Click the "Add" buttons to insert your first records

---

## 📊 Available Actions

### Payroll Module (`payroll-admin.php`)
| Action | Button | Result |
|--------|--------|--------|
| Add Employee | Add Employee | Opens form to add new employee |
| View Employees | Employee List | Shows all employees from database |
| View Payroll | Payroll Summary | Shows payroll history |
| Employee Count | Summary Card | Auto-updates with real count |

**Sample Data to Add:**
```
Name: Juan dela Cruz
Position: Meat Cutter
Department: Production
Salary: 35000
Email: juan@example.com
Phone: 09XX-XXX-XXXX
```

### Suppliers Module (`suppliers-admin.php`)
| Action | Button | Result |
|--------|--------|--------|
| Add Supplier | Add Supplier | Opens form to add vendor |
| View Suppliers | Supplier List | Shows all suppliers |
| Create PO | New Order | Opens purchase order form |
| Pending Orders | Summary Card | Auto-counts pending orders |

**Sample Data to Add:**
```
Company: Prime Meat Suppliers
Contact: Carlos Gutierrez
Category: Fresh Beef
Email: carlos@prime.com
Phone: 09XX-XXX-XXXX
Address: Manila, Philippines
```

### Finances Module (`finances-admin.php`)
| Action | Button | Result |
|--------|--------|--------|
| Record Income | + (Income) | Opens income form |
| Record Expense | + (Expenses) | Opens expense form |
| Add Bank Account | Add Account | Opens account form |
| Financial Summary | Summary Cards | Auto-calculates totals |

**Sample Data to Add:**
```
INCOME:
Source: Sales Revenue
Amount: 250000
Date: 2025-12-01

EXPENSE:
Category: Cost of Goods
Amount: 125000
Date: 2025-12-01

BANK ACCOUNT:
Bank: BDO
Account Number: 123456789012
Name: Meat Shop Operations
Balance: 500000
```

---

## 🔍 Where Are My Records?

| What You Added | Where It Appears |
|---|---|
| Employee | payroll-admin.php → Employee List table |
| Supplier | suppliers-admin.php → Supplier List table |
| Income | finances-admin.php → Income Sources section |
| Expense | finances-admin.php → Expenses section |
| Bank Account | finances-admin.php → Bank Accounts table |

**Summary Cards Update Automatically:**
- Total Employees: ✓ Updates when you add employees
- Monthly Payroll: ✓ Sums all employee salaries
- Active Suppliers: ✓ Counts suppliers with "Active" status
- Total Income: ✓ Sums all income records for the year

---

## 📝 Form Field Reference

### Employee Form Fields
- **First Name** - Employee's first name
- **Last Name** - Employee's last name
- **Position** - Job title (e.g., "Meat Cutter", "Manager")
- **Department** - Select: Production, Sales, Logistics, Administration
- **Monthly Salary** - Numeric amount (e.g., 35000)
- **Email** - Valid email address
- **Contact Number** - Phone number (e.g., 09XX-XXX-XXXX)

### Supplier Form Fields
- **Company Name** - Full company name
- **Contact Person** - Name of main contact
- **Product Category** - Select from dropdown
- **Email** - Company email address
- **Phone** - Company phone number
- **Address** - Full address with city/province

### Income Form Fields
- **Source** - Where income came from (e.g., "Sales Revenue")
- **Amount** - Numeric value (e.g., 250000)
- **Date** - Transaction date
- **Notes** - Optional notes/comments

### Expense Form Fields
- **Category** - Select: Cost of Goods, Salaries, Utilities, Rent, Marketing, Other
- **Amount** - Numeric value
- **Date** - Transaction date
- **Notes** - Optional details

### Bank Account Form Fields
- **Bank Name** - Name of bank (e.g., "BDO", "PNB")
- **Account Number** - Full account number
- **Account Name** - Account holder name
- **Balance** - Current balance amount

---

## ✅ Quality Checks

After adding records, verify:
- [ ] Employee appears in Employee List table
- [ ] Employee Count summary card increased by 1
- [ ] Supplier appears in Supplier List table
- [ ] Active Suppliers count updated
- [ ] Income appears in Income Sources section
- [ ] Total Income summary card updated
- [ ] Expense appears in Expenses section
- [ ] Bank account appears in Bank Accounts table
- [ ] Monthly Report shows YTD totals

---

## 🆘 Troubleshooting

### "Table doesn't exist" Error
**Solution:** Run the database_schema.sql file
```
Location: admin/database_schema.sql
Run in phpMyAdmin SQL tab or MySQL command line
```

### Summary Cards Show "0"
**Solution:** Check if records are in database
```
Go to: admin/verify_database.php
Check that table shows record count > 0
```

### Form Won't Submit
**Solution:** Check handler file exists
```
Verify /admin/handlers/ folder contains all 5 PHP files:
- add_employee.php
- add_supplier.php
- add_income.php
- add_expense.php
- add_account.php
```

### Page Takes Long to Load
**Solution:** Check database indexes
```
This is normal on first load
If persists, verify database connection in connection.php
```

---

## 📞 Support Resources

| Issue | File to Check |
|-------|---|
| Database setup | database_schema.sql |
| Forms not working | handlers/*.php files |
| Database connection | ../connection.php |
| Page layout issues | admin.css |
| Full documentation | DATABASE_INTEGRATION_GUIDE.md |

---

## 🎓 Learning Resources

### Understanding the System
1. **Database Schema** → admin/database_schema.sql
2. **Form Handlers** → admin/handlers/add_*.php
3. **Display Logic** → payroll-admin.php, suppliers-admin.php, finances-admin.php
4. **Complete Guide** → DATABASE_INTEGRATION_GUIDE.md

### Customization Tips
- Change button colors in admin.css
- Modify salary ranges in forms
- Add new product categories in dropdown lists
- Adjust date ranges in reports
- Change profit margin calculations in finances-admin.php

---

## 📊 Example Workflow

### Complete HR Workflow
1. Open payroll-admin.php
2. Click "Add Employee" button
3. Fill form with employee details
4. Submit form
5. See employee in Employee List
6. Summary cards auto-update

### Complete Supplier Workflow
1. Open suppliers-admin.php
2. Click "Add Supplier" button
3. Enter supplier information
4. Submit form
5. Click "New Order" to create purchase order
6. Track delivery status

### Complete Financial Workflow
1. Open finances-admin.php
2. Record income transactions (click + button)
3. Record expense transactions
4. Add bank accounts
5. View monthly financial report
6. Profit margin auto-calculates

---

**Time to First Working System:** ~5 minutes
**Next Steps:** Add your company data and start using the system!

---
*Last Updated: December 2025*

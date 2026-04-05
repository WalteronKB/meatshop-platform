# Copycat Admin Database Integration - Setup Guide

## Overview
This document provides comprehensive instructions for implementing the HR/Payroll, Suppliers, and Financial Management modules with full database integration.

## Database Setup

### 1. Create Database Tables
Execute the SQL script to create all required tables:

**File:** `/admin/database_schema.sql`

Run this script in your MySQL database using phpMyAdmin or MySQL command line:
```sql
mysql -u your_username -p your_database < database_schema.sql
```

### 2. Tables Created
The schema creates 9 tables across 3 modules:

#### HR & Payroll Module (3 tables)
- **mrb_employees** - Employee master records with personal/employment details
- **mrb_payroll** - Monthly payroll records and summaries
- **mrb_payroll_details** - Individual salary breakdowns per employee per payroll period

#### Suppliers Module (2 tables)
- **mrb_suppliers** - Supplier/vendor master data
- **mrb_purchase_orders** - Purchase order tracking with delivery status

#### Finance Module (4 tables)
- **mrb_bank_accounts** - Bank account tracking
- **mrb_income** - Income source records
- **mrb_expenses** - Expense records
- **mrb_financial_summary** - Monthly financial summaries (optional for reporting)

## Admin Pages Integration

### HR & Payroll Management
**File:** `/admin/payroll-admin.php`

**Features:**
- Summary cards showing total employees, monthly payroll total, pending approvals
- Employee list table with add/edit/delete buttons
- Payroll summary showing processed and pending payroll records
- Modal forms for adding and editing employees
- Process Payroll modal for batch payroll processing

**Database Queries:**
- Fetches employee count and payroll totals from database
- Displays actual employee records from `mrb_employees` table
- Shows payroll history from `mrb_payroll` table

### Supplier Management
**File:** `/admin/suppliers-admin.php`

**Features:**
- Summary cards showing active suppliers, pending orders, total supplies value
- Supplier list table with CRUD operations
- Recent purchase orders with delivery tracking
- Modal forms for adding suppliers and creating purchase orders

**Database Queries:**
- Counts active suppliers and pending orders
- Displays supplier information from `mrb_suppliers` table
- Shows purchase orders with supplier names (JOIN query)

### Financial Management
**File:** `/admin/finances-admin.php`

**Features:**
- Financial summary cards (income, expenses, profit YTD)
- Income sources table with recent transactions
- Expenses table with categories
- Bank accounts list with balance tracking
- Monthly financial report with profit margin calculations

**Database Queries:**
- Calculates YTD totals from `mrb_income` and `mrb_expenses` tables
- Displays bank accounts with masked account numbers
- Generates 12-month financial report with dynamic calculations

## Form Handlers

### Handler Directory
All form submission handlers are located in: `/admin/handlers/`

### Available Handlers

#### 1. Add Employee
**File:** `handlers/add_employee.php`
- **Method:** POST
- **Form Fields:**
  - emp_first_name (required)
  - emp_last_name (required)
  - position (required)
  - department (required)
  - salary (required, decimal)
  - email (required)
  - contact_number (required)
- **Auto-Generated:** emp_number (EMP001, EMP002, etc.)
- **Default Status:** Active
- **Redirect:** Back to payroll-admin.php with success/error message

#### 2. Add Supplier
**File:** `handlers/add_supplier.php`
- **Method:** POST
- **Form Fields:**
  - company_name (required)
  - contact_person (required)
  - product_category (required)
  - email (required)
  - contact_number (required)
  - address (required)
- **Auto-Generated:** supplier_number (SUP001, SUP002, etc.)
- **Default Status:** Active
- **Redirect:** Back to suppliers-admin.php with message

#### 3. Record Income
**File:** `handlers/add_income.php`
- **Method:** POST
- **Form Fields:**
  - income_source (required)
  - amount (required, decimal)
  - income_date (required)
  - notes (optional)
- **Redirect:** Back to finances-admin.php

#### 4. Record Expense
**File:** `handlers/add_expense.php`
- **Method:** POST
- **Form Fields:**
  - category (required)
  - amount (required, decimal)
  - expense_date (required)
  - notes (optional)
- **Redirect:** Back to finances-admin.php

#### 5. Add Bank Account
**File:** `handlers/add_account.php`
- **Method:** POST
- **Form Fields:**
  - bank_name (required)
  - account_number (required)
  - account_name (required)
  - balance (required, decimal)
- **Default Status:** Active
- **Redirect:** Back to finances-admin.php

## Features Implemented

### Dynamic Data Display
✅ Summary cards pull real data from database
✅ All tables display live records from database
✅ Automatic numbering (EMP001, SUP001, etc.)
✅ Date formatting for readability
✅ Currency formatting for amounts
✅ Status badges with appropriate styling

### Form Integration
✅ Modal forms connected to handler files
✅ Proper field naming for POST parameters
✅ Form validation (required fields)
✅ Session-based success/error messages
✅ Automatic redirection after submission

### Database Features
✅ Foreign key relationships
✅ Timestamps for created_at/updated_at
✅ Status enums for data consistency
✅ Indexed fields for better query performance
✅ Secure SQL with real_escape_string()

## Testing Checklist

### Before Going Live
- [ ] Run database_schema.sql to create tables
- [ ] Test adding an employee via payroll-admin.php modal
- [ ] Verify employee appears in table immediately
- [ ] Test adding a supplier
- [ ] Test recording income and expense
- [ ] Test adding a bank account
- [ ] Verify summary cards update with real data
- [ ] Check that all modal forms submit successfully
- [ ] Test filtering and sorting if implemented
- [ ] Verify security with unauthorized access attempts

### Common Issues & Solutions

**Issue:** "Table doesn't exist" error
- **Solution:** Ensure database_schema.sql has been run completely

**Issue:** Forms not submitting
- **Solution:** Check that handler files exist in `/admin/handlers/` directory

**Issue:** Summary cards show 0 or blank values
- **Solution:** Verify database connection in connection.php and check table names match schema

**Issue:** Dates not formatting correctly
- **Solution:** Ensure date fields use date type in HTML forms

## Next Steps

### Features to Add (Optional)
1. **Edit/Delete Handlers** - Create handlers for updating and deleting records
2. **Search & Filter** - Add search functionality to tables
3. **Pagination** - Implement pagination for large datasets
4. **Export to Excel** - Add export functionality for reports
5. **Approval Workflow** - Implement approval status for payroll
6. **Audit Trail** - Track who created/modified records

### Performance Optimization
1. Add database indexes (already included in schema)
2. Implement query caching for frequently used data
3. Optimize images in bank account forms
4. Use prepared statements for all queries

## File Structure

```
admin/
├── payroll-admin.php          (HR & Payroll page)
├── suppliers-admin.php        (Suppliers page)
├── finances-admin.php         (Finances page)
├── database_schema.sql        (Database creation script)
├── handlers/
│   ├── add_employee.php       (Add employee handler)
│   ├── add_supplier.php       (Add supplier handler)
│   ├── add_income.php         (Record income handler)
│   ├── add_expense.php        (Record expense handler)
│   └── add_account.php        (Add bank account handler)
└── [other existing admin files]
```

## Database Connection
All pages include the connection via: `include('../connection.php')`

Make sure your connection.php file has:
- Correct database credentials
- Proper character encoding
- Error handling

## Security Notes
- All user input is sanitized with `mysqli_real_escape_string()`
- Session validation on all handler files
- Admin authentication check before processing
- Account numbers masked in display (****1234 format)

## Support
For issues or questions about the implementation, refer to the database schema documentation or check handler files for specific field requirements.

---
**Last Updated:** December 2025
**Version:** 1.0

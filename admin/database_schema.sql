-- HR & Payroll Tables

-- Employees Table
CREATE TABLE IF NOT EXISTS mrb_employees (
    emp_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_number VARCHAR(20) UNIQUE NOT NULL,
    emp_first_name VARCHAR(100) NOT NULL,
    emp_last_name VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    email VARCHAR(100),
    contact_number VARCHAR(20),
    status ENUM('Active', 'On Leave', 'Inactive') DEFAULT 'Active',
    hire_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payroll Records Table
CREATE TABLE IF NOT EXISTS mrb_payroll (
    payroll_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_start DATE NOT NULL,
    payroll_period_end DATE NOT NULL,
    employee_count INT,
    gross_amount DECIMAL(12, 2),
    deductions_amount DECIMAL(12, 2),
    net_amount DECIMAL(12, 2),
    status ENUM('Pending', 'Processed', 'Cancelled') DEFAULT 'Pending',
    processed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payroll Details Table (breakdown per employee)
CREATE TABLE IF NOT EXISTS mrb_payroll_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_id INT NOT NULL,
    emp_id INT NOT NULL,
    gross_salary DECIMAL(10, 2),
    deductions DECIMAL(10, 2),
    net_salary DECIMAL(10, 2),
    FOREIGN KEY (payroll_id) REFERENCES mrb_payroll(payroll_id),
    FOREIGN KEY (emp_id) REFERENCES mrb_employees(emp_id)
);

-- Supplier Tables

-- Suppliers Table
CREATE TABLE IF NOT EXISTS mrb_suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_number VARCHAR(20) UNIQUE NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    product_category VARCHAR(100),
    email VARCHAR(100),
    contact_number VARCHAR(20),
    address TEXT,
    status ENUM('Active', 'Inactive', 'Blacklisted', 'Archived') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Purchase Orders Table
CREATE TABLE IF NOT EXISTS mrb_purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(30) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    item_description TEXT NOT NULL,
    quantity VARCHAR(50),
    unit_price DECIMAL(10, 2),
    total_amount DECIMAL(12, 2),
    delivery_date DATE,
    status ENUM('Pending', 'Processing', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES mrb_suppliers(supplier_id)
);

-- Finance Tables

-- Bank Accounts Table
CREATE TABLE IF NOT EXISTS mrb_bank_accounts (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    balance DECIMAL(15, 2),
    status ENUM('Active', 'Inactive', 'Closed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Income Table
CREATE TABLE IF NOT EXISTS mrb_income (
    income_id INT AUTO_INCREMENT PRIMARY KEY,
    income_source VARCHAR(150) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    income_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Expenses Table
CREATE TABLE IF NOT EXISTS mrb_expenses (
    expense_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    expense_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Financial Summary Table (for monthly/annual reports)
CREATE TABLE IF NOT EXISTS mrb_financial_summary (
    summary_id INT AUTO_INCREMENT PRIMARY KEY,
    month_year DATE NOT NULL,
    total_income DECIMAL(15, 2),
    total_expenses DECIMAL(15, 2),
    net_profit DECIMAL(15, 2),
    profit_margin DECIMAL(5, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create indexes for better query performance
CREATE INDEX idx_emp_status ON mrb_employees(status);
CREATE INDEX idx_emp_department ON mrb_employees(department);
CREATE INDEX idx_payroll_period ON mrb_payroll(payroll_period_start, payroll_period_end);
CREATE INDEX idx_payroll_status ON mrb_payroll(status);
CREATE INDEX idx_sup_status ON mrb_suppliers(status);
CREATE INDEX idx_po_status ON mrb_purchase_orders(status);
CREATE INDEX idx_income_date ON mrb_income(income_date);
CREATE INDEX idx_expense_date ON mrb_expenses(expense_date);
CREATE INDEX idx_bank_status ON mrb_bank_accounts(status);

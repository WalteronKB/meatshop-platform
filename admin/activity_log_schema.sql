-- Activity Log Table
-- This table stores all activity logs for different modules

CREATE TABLE IF NOT EXISTS `mrb_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_desc` TEXT NOT NULL,
  `activity_type` ENUM('products', 'accounts', 'payroll', 'suppliers', 'finance') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activity_type` (`activity_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data for testing (optional - remove if not needed)
INSERT INTO `mrb_activity_log` (`activity_desc`, `activity_type`) VALUES
('Admin added new product: Premium Beef Steak - ₱450.00 per kg', 'products'),
('Admin updated price for Pork Chops: ₱250.00 → ₱280.00', 'products'),
('Admin restocked Chicken Wings - Added 50 units', 'products'),
('New user registered: juan.delacruz@email.com', 'accounts'),
('Admin verified account: maria.santos@email.com', 'accounts'),
('User updated profile: pedro.reyes@email.com', 'accounts'),
('HR processed payroll #145 for Jan 16-31, 2026 - 12 employees, ₱125,000.00', 'payroll'),
('HR recorded attendance for 5 employees - Feb 20, 2026', 'payroll'),
('HR added new employee: Carlos Mendoza - Position: Butcher', 'payroll'),
('Purchase Order PO-2026-045 created for ABC Meat Suppliers - ₱85,000.00', 'suppliers'),
('Received delivery for PO-2026-044 from XYZ Fresh Meats', 'suppliers'),
('New supplier registered: Premium Poultry Inc.', 'suppliers'),
('Finance approved payroll #145 - ₱125,000.00 for 12 employees', 'finance'),
('Finance approved PO-2026-045 for ABC Meat Suppliers - ₱85,000.00', 'finance'),
('Finance reported income discrepancy: Product Sales - January 2026', 'finance');

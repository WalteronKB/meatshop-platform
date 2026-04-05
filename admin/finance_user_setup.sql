-- Finance User Setup
-- This script helps create or update a user to have finance access

-- Option 1: Update an existing user to be a finance user
-- Replace 'finance@email.com' with the actual finance user's email
UPDATE `mrb_users` 
SET `user_type` = 'finance' 
WHERE `user_email` = 'finance@email.com'
LIMIT 1;

-- Option 2: Update by user_id
-- Uncomment and replace the user_id number
-- UPDATE `mrb_users` SET `user_type` = 'finance' WHERE `user_id` = 2;

-- Option 3: Create a new finance user account
-- Uncomment the lines below to create a new finance user
/*
INSERT INTO `mrb_users` 
(`user_name`, `user_mname`, `user_lname`, `user_contactnum`, `user_email`, `user_password`, `user_pic`, `user_dateadded`, `user_type`) 
VALUES 
('Finance', '', 'Manager', '09123456789', 'finance@meatshop.com', '$2y$10$YourHashedPasswordHere', 'Images/profile_pics/anonymous.jpg', NOW(), 'finance');
*/

-- USER TYPE VALUES:
-- 'super_admin': Full access to all modules including activity logs (Super Admin page)
-- 'admin': Regular admin access to standard admin modules
-- 'finance': Finance admin access (Finances page only)
-- 'user': Regular customer/user access
-- 'deleted': Soft-deleted account

-- After running this query, the finance user can log in at mrbloginpage.php
-- They will be automatically redirected to finances-admin.php

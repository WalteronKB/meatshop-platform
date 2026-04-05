-- Super Admin Setup
-- This sets up super admin access by updating user_type

-- Set a super admin (replace 'admin@email.com' with your actual admin email)
-- This example sets an admin user as super_admin
UPDATE `mrb_users` 
SET `user_type` = 'super_admin' 
WHERE `user_email` = 'admin@email.com' AND `user_type` = 'admin'
LIMIT 1;

-- Alternative: If you want to set by user_id
-- UPDATE `mrb_users` SET `user_type` = 'super_admin' WHERE `user_id` = 1;

-- USER TYPE VALUES:
-- 'super_admin': Full access to all modules including activity logs (Super Admin page)
-- 'admin': Regular admin access to standard admin modules
-- 'user': Regular customer/user access

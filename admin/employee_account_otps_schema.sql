-- Employee account creation OTP persistence table
-- Stores OTP requests so verification works even across page refresh/session changes.

CREATE TABLE IF NOT EXISTS `employee_account_otps` (
  `otp_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `creator_user_id` INT NOT NULL,
  `target_email` VARCHAR(255) NOT NULL,
  `employee_role` ENUM('admin','finance') NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `payload_json` LONGTEXT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `is_used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`otp_id`),
  KEY `idx_creator_active` (`creator_user_id`, `is_used`, `expires_at`),
  KEY `idx_target_email` (`target_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

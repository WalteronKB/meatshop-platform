-- Shop Onboarding Tables

-- Applications submitted by users who want to open a shop
CREATE TABLE IF NOT EXISTS shop_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    store_description TEXT NOT NULL,
    store_address VARCHAR(255) NOT NULL,
    address_iframe TEXT NOT NULL,
    business_email VARCHAR(120) NOT NULL,
    business_phone VARCHAR(40) NOT NULL,
    business_permit_no VARCHAR(80) NOT NULL,
    tin_no VARCHAR(80) DEFAULT NULL,
    operating_hours VARCHAR(120) NOT NULL,
    delivery_areas TEXT DEFAULT NULL,
    store_logo_path VARCHAR(255) NOT NULL,
    business_permit_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_shop_applications_user_id (user_id),
    INDEX idx_shop_applications_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Final list of approved shops (ready for seller operations)
CREATE TABLE IF NOT EXISTS approved_shops (
    approved_shop_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    store_name VARCHAR(150) NOT NULL,
    store_description TEXT NOT NULL,
    store_address VARCHAR(255) NOT NULL,
    address_iframe TEXT NOT NULL,
    business_email VARCHAR(120) NOT NULL,
    business_phone VARCHAR(40) NOT NULL,
    business_permit_no VARCHAR(80) NOT NULL,
    tin_no VARCHAR(80) DEFAULT NULL,
    operating_hours VARCHAR(120) NOT NULL,
    delivery_areas TEXT DEFAULT NULL,
    store_logo_path VARCHAR(255) NOT NULL,
    business_permit_path VARCHAR(255) NOT NULL,
    shop_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_approved_shops_user_id (user_id),
    UNIQUE KEY uniq_approved_shops_application_id (application_id),
    INDEX idx_approved_shops_shop_status (shop_status),
    CONSTRAINT fk_approved_shops_application_id
        FOREIGN KEY (application_id) REFERENCES shop_applications(application_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

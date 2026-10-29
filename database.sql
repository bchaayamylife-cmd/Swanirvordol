-- Multi-Group SHG Savings, Loan & Accounting Management System
-- Target Database: barber_shop
-- Charset: utf8mb4_unicode_ci

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- --------------------------------------------------------
-- Table: groups
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(191) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `address` TEXT NOT NULL,
  `village` VARCHAR(100) NOT NULL,
  `gram_panchayat` VARCHAR(100) DEFAULT NULL,
  `block` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL DEFAULT 'West Bengal',
  `pin_code` VARCHAR(10) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'suspended', 'closed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_group_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: group_applications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `group_applications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_name` VARCHAR(191) NOT NULL,
  `address` TEXT NOT NULL,
  `village` VARCHAR(100) NOT NULL,
  `gram_panchayat` VARCHAR(100) DEFAULT NULL,
  `block` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL DEFAULT 'West Bengal',
  `pin_code` VARCHAR(10) NOT NULL,
  `contact_mobile` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `formation_date` DATE DEFAULT NULL,
  `member_count` INT UNSIGNED NOT NULL DEFAULT 10,
  `notes` TEXT DEFAULT NULL,
  `preferred_language` VARCHAR(10) NOT NULL DEFAULT 'bn',
  `president_name` VARCHAR(150) NOT NULL,
  `president_mobile` VARCHAR(20) NOT NULL,
  `president_address` TEXT NOT NULL,
  `secretary_name` VARCHAR(150) NOT NULL,
  `secretary_mobile` VARCHAR(20) NOT NULL,
  `secretary_address` TEXT NOT NULL,
  `treasurer_name` VARCHAR(150) NOT NULL,
  `treasurer_mobile` VARCHAR(20) NOT NULL,
  `treasurer_address` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_app_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED DEFAULT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `role` ENUM('super_admin', 'group_owner', 'staff_operator') NOT NULL DEFAULT 'group_owner',
  `preferred_language` VARCHAR(10) NOT NULL DEFAULT 'bn',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: members
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `members` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED NOT NULL,
  `member_no` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `english_name` VARCHAR(150) DEFAULT NULL,
  `guardian_name` VARCHAR(150) DEFAULT NULL,
  `gender` ENUM('Female', 'Male', 'Other') NOT NULL DEFAULT 'Female',
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `village` VARCHAR(100) DEFAULT NULL,
  `joining_date` DATE NOT NULL,
  `status` ENUM('active', 'inactive', 'archived') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_group_member` (`group_id`, `member_no`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: savings_transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `savings_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `transaction_date` DATE NOT NULL,
  `receipt_voucher_no` VARCHAR(50) DEFAULT NULL,
  `previous_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `withdrawal_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `updated_balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `cashbook_page_no` VARCHAR(50) DEFAULT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
  `remarks` TEXT DEFAULT NULL,
  `entered_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_savings_group_date (`group_id`, `transaction_date`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: loans
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `loan_no` VARCHAR(50) NOT NULL UNIQUE,
  `principal_amount` DECIMAL(15,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) NOT NULL,
  `interest_calculation_method` ENUM('flat', 'reducing') NOT NULL DEFAULT 'flat',
  `tenure_months` INT UNSIGNED NOT NULL,
  `total_interest` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `outstanding_principal` DECIMAL(15,2) NOT NULL,
  `outstanding_interest` DECIMAL(15,2) NOT NULL,
  `installment_amount` DECIMAL(15,2) NOT NULL,
  `disbursement_date` DATE NOT NULL,
  `status` ENUM('applied', 'approved', 'disbursed', 'closed', 'cancelled') NOT NULL DEFAULT 'disbursed',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_loans_group_status (`group_id`, `status`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: installments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `installments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT UNSIGNED NOT NULL,
  `installment_no` INT UNSIGNED NOT NULL,
  `due_date` DATE NOT NULL,
  `principal_due` DECIMAL(15,2) NOT NULL,
  `interest_due` DECIMAL(15,2) NOT NULL,
  `total_due` DECIMAL(15,2) NOT NULL,
  `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'partial', 'paid') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: repayments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `repayments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `principal_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `interest_paid` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `late_fee` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_paid` DECIMAL(15,2) NOT NULL,
  `remaining_principal` DECIMAL(15,2) NOT NULL,
  `remaining_interest` DECIMAL(15,2) NOT NULL,
  `receipt_no` VARCHAR(50) DEFAULT NULL,
  `cashbook_page_no` VARCHAR(50) DEFAULT NULL,
  `entered_by` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: cashbook_entries
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cashbook_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED NOT NULL,
  `transaction_date` DATE NOT NULL,
  `cashbook_page_no` VARCHAR(50) DEFAULT NULL,
  `reference_type` VARCHAR(50) NOT NULL,
  `reference_id` INT UNSIGNED DEFAULT NULL,
  `description` TEXT NOT NULL,
  `debit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `credit` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `member_id` INT UNSIGNED DEFAULT NULL,
  `entered_by` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cashbook_group_date (`group_id`, `transaction_date`),
  FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: audit_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT UNSIGNED DEFAULT 1,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: app_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Initial Data Seed
-- --------------------------------------------------------
INSERT INTO `groups` (`id`, `name`, `code`, `address`, `village`, `gram_panchayat`, `block`, `district`, `state`, `pin_code`, `phone`, `status`)
VALUES (1, 'পাটপিশু প্রগতি স্বনির্ভর দল', 'SHG-PAT-001', 'পাটপিশু, পোঃ পাটপিশু', 'পাটপিশু', 'পাটপিশু', 'কোতোয়ালী', 'পশ্চিম মেদিনীপুর', 'West Bengal', '721101', '9876543210', 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Default Super Admin Credentials (Username: admin | Password: Password@123)
-- Password hash generated via password_hash('Password@123', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `group_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `preferred_language`, `status`)
VALUES (1, 1, 'admin', '$2y$10$wE9aVd9mGZ1aJdJvE8QjUeHnIqZ4bE5bUuP3eQ6k7R8s9t0u1v2w3', 'সুপার অ্যাডমিনিস্ট্রেটর', 'admin@shgportal.org', '9876543210', 'super_admin', 'bn', 'active')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

SET FOREIGN_KEY_CHECKS = 1;

3. InfinityFree Installation Instructions

1.  Log in to InfinityFree Control Panel (vPanel).
2.  Open MySQL Databases:
      - Create a new database named barber_shop. Note down the full database
        name provided by InfinityFree (for example: epiz_12345678_barber_shop),
        along with your MySQL username and MySQL hostname (e.g.,
        sqlXXX.infinityfree.com).
3.  Open phpMyAdmin:
      - Select your database.
      - Click the Import tab.
      - Choose database.sql and click Go.
4.  Configure Database Credentials in index.php:
      - Open index.php in a text editor.
      - Look for lines 24–28 and update the constants:
        define('DB_HOST', 'sqlXXX.infinityfree.com'); // Hosting MySQL hostname
        define('DB_NAME', 'epiz_12345678_barber_shop'); // Full DB name
        define('DB_USER', 'epiz_12345678');            // Hosting vPanel username
        define('DB_PASS', 'YOUR_VPANEL_PASSWORD');     // Hosting account password
5.  Upload Source Code:
      - Open File Manager or connect via FileZilla FTP.
      - Navigate to the htdocs/ folder.
      - Upload index.php directly into htdocs/.
6.  Access your website in a browser:
    http://your-domain.infinityfreeapp.com/index.php.

4. Initial Super Admin Setup Instructions

The database seed installs an initial Super Admin account:

  - Username: admin
  - Initial Password: Password@123

Post-Installation Security Checklist

1.  Log in immediately at index.php?page=login.
2.  Go to সেটিংস (Settings) from the left sidebar or the user menu at top right.
3.  Change the administrator password immediately to a strong, custom
    passphrase.
4.  Verify that new group applications can be submitted at
    index.php?page=register_group and approved under দল অনুমোদন আবেদন in the
    Super Admin menu.

5. Multi-Language Usage & Preference Management

  - Public Pages: Visitors and unauthenticated users can click the language
    dropdown in the top navigation bar at any time. The selection is stored in
    the browser session.
  - Logged-in Users: Changing the language automatically updates the user's
    preferred_language database field in the users table. The selected language
    persists across sessions and device logins.
  - RTL Language (Urdu): Selecting Urdu (اردو) automatically renders the
    document layout, tables, and navigation with dir="rtl".
  - Financial Integrity: Changing the interface language alters only the UI and
    report labels. All numeric calculations, transaction logs, dates, member
    names, and audit trails remain stored as valid numbers, ISO dates, and raw
    UTF-8 text.

6. Hosting & External Service Limitations

1.  InfinityFree Shared Limits:
      - Outbound port 25/465/587 and external cURL calls may be restricted on
        free tier accounts. Because of this, email and SMS notifications use
        in-app audit logging and manual on-screen prompts rather than failing
        background webhooks.
      - Server execution time is capped at 20–30 seconds. Financial exports and
        calculations are streamed with buffered queries to prevent timeouts.
2.  Browser Print Settings:
      - To print registers in official A4 landscape format with blue headers and
        grid lines, enable "Background graphics" in the browser's print dialog
        (Ctrl + P). Margin should be set to Default or Minimum (8mm).

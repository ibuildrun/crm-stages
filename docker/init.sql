-- CRM Stages: Database Schema
-- Prefix: #__ replaced with empty for standalone, use jos_ for Joomla

CREATE TABLE IF NOT EXISTS `crmstages_companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `stage_code` VARCHAR(32) NOT NULL DEFAULT 'Ice',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT NOT NULL,
    INDEX `idx_stage_code` (`stage_code`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crmstages_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `manager_id` INT NOT NULL,
    `event_type` VARCHAR(64) NOT NULL,
    `payload` JSON,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_company_id` (`company_id`),
    INDEX `idx_company_type` (`company_id`, `event_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_company_created` (`company_id`, `created_at` DESC),
    CONSTRAINT `fk_events_company` FOREIGN KEY (`company_id`)
        REFERENCES `crmstages_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crmstages_discovery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `needs` TEXT,
    `budget` VARCHAR(128),
    `timeline` VARCHAR(128),
    `decision_makers` TEXT,
    `filled_at` DATETIME,
    `filled_by` INT,
    UNIQUE INDEX `idx_company_id` (`company_id`),
    CONSTRAINT `fk_discovery_company` FOREIGN KEY (`company_id`)
        REFERENCES `crmstages_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crmstages_demos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `scheduled_at` DATETIME NOT NULL,
    `conducted_at` DATETIME,
    `demo_link` VARCHAR(512),
    `created_by` INT NOT NULL,
    INDEX `idx_company_id` (`company_id`),
    CONSTRAINT `fk_demos_company` FOREIGN KEY (`company_id`)
        REFERENCES `crmstages_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crmstages_invoices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `amount` DECIMAL(12,2),
    `status` VARCHAR(32) NOT NULL DEFAULT 'created',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at` DATETIME,
    `created_by` INT NOT NULL,
    INDEX `idx_company_status` (`company_id`, `status`),
    CONSTRAINT `fk_invoices_company` FOREIGN KEY (`company_id`)
        REFERENCES `crmstages_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crmstages_certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `certificate_number` VARCHAR(128) NOT NULL,
    `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `issued_by` INT NOT NULL,
    INDEX `idx_company_id` (`company_id`),
    CONSTRAINT `fk_certificates_company` FOREIGN KEY (`company_id`)
        REFERENCES `crmstages_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

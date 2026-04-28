-- Run this SQL in your database to create the verification_codes table
-- Execute in phpMyAdmin or MySQL CLI

CREATE TABLE IF NOT EXISTS `verification_codes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(20) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `attempts` INT(11) DEFAULT 0,
  `status` ENUM('pending', 'verified', 'expired') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_phone_status` (`phone_number`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

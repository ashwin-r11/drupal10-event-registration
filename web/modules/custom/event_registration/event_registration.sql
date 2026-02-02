-- Event Registration Module Database Schema
-- Drupal 10 Custom Module

-- =====================================================
-- Table: event_configurations
-- Stores event definitions created by administrators
-- =====================================================

CREATE TABLE IF NOT EXISTS `event_configurations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    `registration_start_date` DATE NOT NULL COMMENT 'Date when registration opens',
    `registration_end_date` DATE NOT NULL COMMENT 'Date when registration closes',
    `event_date` DATE NOT NULL COMMENT 'Actual date of the event',
    `event_name` VARCHAR(255) NOT NULL COMMENT 'Display name of the event',
    `category` VARCHAR(64) NOT NULL COMMENT 'Event category (online_workshop, hackathon, conference, one_day_workshop)',
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category`),
    INDEX `idx_event_date` (`event_date`),
    INDEX `idx_registration_dates` (`registration_start_date`, `registration_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- Table: event_registrations
-- Stores participant registration records
-- =====================================================

CREATE TABLE IF NOT EXISTS `event_registrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    `full_name` VARCHAR(255) NOT NULL COMMENT 'Participant full name',
    `email` VARCHAR(255) NOT NULL COMMENT 'Participant email address',
    `college` VARCHAR(255) NOT NULL COMMENT 'Participant college/institution',
    `department` VARCHAR(255) NOT NULL COMMENT 'Participant department',
    `event_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to event_configurations',
    `created` INT NOT NULL COMMENT 'Unix timestamp of registration',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_registration` (`email`, `event_id`) COMMENT 'Prevents duplicate registrations',
    INDEX `idx_event_id` (`event_id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_created` (`created`),
    CONSTRAINT `fk_event_id` FOREIGN KEY (`event_id`) 
        REFERENCES `event_configurations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- Category Reference (for documentation purposes)
-- =====================================================
-- 
-- Valid category values:
--   - online_workshop  : Online Workshop
--   - hackathon        : Hackathon
--   - conference       : Conference
--   - one_day_workshop : One-day Workshop
--
-- =====================================================

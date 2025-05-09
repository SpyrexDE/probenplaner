-- Initial schema setup for Probenplaner
-- First, create the migrations tracking table
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create orchestras table
CREATE TABLE IF NOT EXISTS `orchestras` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `token` VARCHAR(50) NOT NULL,
    `leader_pw` VARCHAR(255) NOT NULL,
    `conductor_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_orchestras_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `orchestra_id` INT NOT NULL,
    `role` ENUM('member','leader','conductor') NOT NULL DEFAULT 'member',
    `is_small_group` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_users_username` (`username`),
    KEY `idx_users_type` (`type`),
    KEY `idx_users_orchestra` (`orchestra_id`),
    KEY `idx_is_small_group` (`is_small_group`),
    CONSTRAINT `users_ibfk_1` FOREIGN KEY (`orchestra_id`) REFERENCES `orchestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create rehearsals table
CREATE TABLE IF NOT EXISTS `rehearsals` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `location` VARCHAR(255) NOT NULL,
    `groups_data` JSON,
    `orchestra_id` INT NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rehearsals_date` (`date`),
    KEY `idx_rehearsals_orchestra` (`orchestra_id`),
    CONSTRAINT `rehearsals_ibfk_1` FOREIGN KEY (`orchestra_id`) REFERENCES `orchestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create rehearsal_groups table
CREATE TABLE IF NOT EXISTS `rehearsal_groups` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `rehearsal_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_groups_rehearsal` (`rehearsal_id`),
    CONSTRAINT `rehearsal_groups_ibfk_1` FOREIGN KEY (`rehearsal_id`) REFERENCES `rehearsals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_promises table
CREATE TABLE IF NOT EXISTS `user_promises` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `rehearsal_id` INT NOT NULL,
    `status` ENUM('yes','no','maybe') NOT NULL DEFAULT 'maybe',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_rehearsal` (`user_id`, `rehearsal_id`),
    CONSTRAINT `user_promises_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_promises_ibfk_2` FOREIGN KEY (`rehearsal_id`) REFERENCES `rehearsals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 
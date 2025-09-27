-- Multi-Orchestra Support Schema Migration
-- This migration prepares the database schema for multi-orchestra support
-- Data migration is handled in a separate migration (20250926_151000_migrate_existing_data.sql)

-- Create the user_orchestras junction table
-- This table will store the many-to-many relationships between users and orchestras
CREATE TABLE IF NOT EXISTS `user_orchestras` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `orchestra_id` INT NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'Instrument/section (moved from users table)',
    `role` ENUM('member','leader','conductor') NOT NULL DEFAULT 'member' COMMENT 'Role (moved from users table)',
    `joined_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Soft delete/leave functionality',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_orchestra_active` (`user_id`, `orchestra_id`, `is_active`),
    KEY `idx_user_orchestras_user` (`user_id`),
    KEY `idx_user_orchestras_orchestra` (`orchestra_id`),
    KEY `idx_user_orchestras_type` (`type`),
    KEY `idx_user_orchestras_role` (`role`),
    KEY `idx_user_orchestras_active` (`is_active`),
    CONSTRAINT `user_orchestras_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `user_orchestras_ibfk_2` FOREIGN KEY (`orchestra_id`) REFERENCES `orchestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: The actual data migration and old column cleanup is handled in subsequent migrations
-- 20250926_151000_migrate_existing_data.sql - Migrates existing user-orchestra relationships
-- 20250926_152000_cleanup_old_structure.sql - Removes old columns after data verification

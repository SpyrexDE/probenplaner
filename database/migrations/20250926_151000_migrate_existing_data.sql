-- Data Migration for Multi-Orchestra Support
-- This migration safely transfers all existing data to the new multi-orchestra structure
-- and handles potential conflicts like duplicate usernames across orchestras

-- Step 1: Create the user_orchestras junction table if not exists
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

-- Step 2: Handle username conflicts BEFORE migrating data
-- Find and resolve duplicate usernames across different orchestras

-- Create a temporary table to track username conflicts
CREATE TEMPORARY TABLE username_conflicts AS
SELECT 
    username, 
    COUNT(*) as conflict_count,
    GROUP_CONCAT(id ORDER BY id ASC) as user_ids,
    GROUP_CONCAT(orchestra_id ORDER BY id ASC) as orchestra_ids
FROM users 
WHERE username IN (
    SELECT username 
    FROM users 
    GROUP BY username 
    HAVING COUNT(*) > 1
)
GROUP BY username;

-- Resolve username conflicts by appending orchestra name
UPDATE users u
JOIN orchestras o ON u.orchestra_id = o.id
SET u.username = CONCAT(u.username, '_', REPLACE(LOWER(o.name), ' ', '_'))
WHERE u.username IN (SELECT username FROM username_conflicts);

-- Step 3: Migrate existing user-orchestra relationships
-- Insert all existing users into the user_orchestras junction table
INSERT IGNORE INTO `user_orchestras` (`user_id`, `orchestra_id`, `type`, `role`, `joined_at`, `is_active`)
SELECT 
    u.id as user_id,
    u.orchestra_id,
    COALESCE(u.type, 'Unknown') as type, -- Handle NULL types
    COALESCE(u.role, 'member') as role, -- Handle NULL roles  
    COALESCE(u.created_at, NOW()) as joined_at,
    TRUE as is_active
FROM users u 
WHERE u.orchestra_id IS NOT NULL;

-- Step 4: Verify data integrity
-- Check that all users with orchestra_id have been migrated
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM users WHERE orchestra_id IS NOT NULL) = 
             (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE)
        THEN 'SUCCESS: All users migrated successfully'
        ELSE 'ERROR: Data migration incomplete'
    END as migration_status;

-- Step 5: Handle users without orchestras (if any)
-- These users will simply not have entries in user_orchestras table
-- They can join orchestras later through the new flow

-- Step 6: Update conductor references in orchestras table
-- Make sure conductor_id still points to valid users
UPDATE orchestras o
LEFT JOIN users u ON o.conductor_id = u.id
SET o.conductor_id = NULL
WHERE u.id IS NULL AND o.conductor_id IS NOT NULL;

-- Step 7: Create backup information for rollback if needed
CREATE TABLE IF NOT EXISTS migration_backup_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255),
    users_migrated INT,
    orchestras_count INT,
    username_conflicts_resolved INT,
    migration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO migration_backup_info (migration_name, users_migrated, orchestras_count, username_conflicts_resolved)
SELECT 
    '20250926_151000_migrate_existing_data',
    (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE),
    (SELECT COUNT(*) FROM orchestras),
    (SELECT COUNT(*) FROM username_conflicts);

-- Cleanup temporary table
DROP TEMPORARY TABLE IF EXISTS username_conflicts;

-- Log completion
SELECT 
    'Multi-orchestra data migration completed successfully.' as status,
    (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE) as users_migrated,
    (SELECT COUNT(*) FROM orchestras) as orchestras_preserved,
    NOW() as completed_at;

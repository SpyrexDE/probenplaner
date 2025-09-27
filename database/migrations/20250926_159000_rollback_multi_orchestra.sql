-- ROLLBACK Multi-Orchestra Migration
-- This migration can be used to rollback the multi-orchestra changes if needed
-- WARNING: This will revert to single-orchestra per user system

-- Step 1: Recreate old structure in users table
-- Check and add columns only if they don't exist
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'orchestra_id') = 0,
    'ALTER TABLE users ADD COLUMN orchestra_id INT DEFAULT NULL', 
    'SELECT "orchestra_id column already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'type') = 0,
    'ALTER TABLE users ADD COLUMN type VARCHAR(50) DEFAULT NULL', 
    'SELECT "type column already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'role') = 0,
    'ALTER TABLE users ADD COLUMN role ENUM("member","leader","conductor") DEFAULT "member"', 
    'SELECT "role column already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'is_small_group') = 0,
    'ALTER TABLE users ADD COLUMN is_small_group BOOLEAN DEFAULT FALSE', 
    'SELECT "is_small_group column already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Restore data from user_orchestras to users table
-- NOTE: This will only keep the FIRST orchestra relationship for each user
UPDATE users u
JOIN (
    SELECT 
        user_id, 
        orchestra_id, 
        type, 
        role,
        ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY joined_at ASC) as rn
    FROM user_orchestras 
    WHERE is_active = TRUE
) uo ON u.id = uo.user_id AND uo.rn = 1
SET 
    u.orchestra_id = uo.orchestra_id,
    u.type = uo.type,
    u.role = uo.role;

-- Step 3: Recreate foreign key constraint
ALTER TABLE `users` 
ADD CONSTRAINT `users_ibfk_1` 
FOREIGN KEY (`orchestra_id`) REFERENCES `orchestras` (`id`) ON DELETE CASCADE;

-- Step 4: Recreate indexes
ALTER TABLE `users` ADD INDEX `idx_users_orchestra` (`orchestra_id`);
ALTER TABLE `users` ADD INDEX `idx_users_type` (`type`);
ALTER TABLE `users` ADD INDEX `idx_is_small_group` (`is_small_group`);

-- Step 5: Remove global username unique constraint
ALTER TABLE `users` DROP INDEX IF EXISTS `idx_users_username_unique`;

-- Step 6: Drop user_orchestras table
DROP TABLE IF EXISTS `user_orchestras`;

-- Step 7: Log rollback
INSERT INTO migration_backup_info (migration_name, users_migrated, orchestras_count, username_conflicts_resolved)
SELECT 
    '20250926_159000_rollback_multi_orchestra',
    (SELECT COUNT(*) FROM users WHERE orchestra_id IS NOT NULL),
    (SELECT COUNT(*) FROM orchestras),
    0;

SELECT 
    'Multi-orchestra rollback completed.' as status,
    'System reverted to single-orchestra per user.' as change,
    'WARNING: Users who belonged to multiple orchestras now only have their first orchestra.' as warning,
    NOW() as completed_at;

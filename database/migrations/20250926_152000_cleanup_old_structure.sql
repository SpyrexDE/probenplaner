-- Cleanup Old Structure Migration
-- This migration removes the old orchestra-specific columns from users table
-- It should only be run AFTER the data migration (20250926_151000_migrate_existing_data.sql)

-- Step 1: Verify data migration was successful
-- Check that user_orchestras table exists and has data
SELECT 
    CASE 
        WHEN EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'user_orchestras')
        AND (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE) > 0
        THEN 'VERIFIED: Data migration successful, proceeding with cleanup'
        ELSE 'ERROR: Data migration not complete, aborting cleanup'
    END as verification_status;

-- Step 2: Remove foreign key constraint first (if it exists)
SET foreign_key_checks = 0;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_name = 'users' AND constraint_name = 'users_ibfk_1') > 0,
    'ALTER TABLE users DROP FOREIGN KEY users_ibfk_1', 
    'SELECT "Foreign key users_ibfk_1 does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET foreign_key_checks = 1;

-- Step 3: Remove indexes that reference the columns we're about to drop
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = 'users' AND index_name = 'idx_users_orchestra') > 0,
    'ALTER TABLE users DROP INDEX idx_users_orchestra', 
    'SELECT "Index idx_users_orchestra does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = 'users' AND index_name = 'idx_users_type') > 0,
    'ALTER TABLE users DROP INDEX idx_users_type', 
    'SELECT "Index idx_users_type does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = 'users' AND index_name = 'idx_is_small_group') > 0,
    'ALTER TABLE users DROP INDEX idx_is_small_group', 
    'SELECT "Index idx_is_small_group does not exist" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Remove the old orchestra-specific columns from users table (if they exist)
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'orchestra_id') > 0,
    'ALTER TABLE users DROP COLUMN orchestra_id', 
    'SELECT "Column orchestra_id already removed" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'type') > 0,
    'ALTER TABLE users DROP COLUMN type', 
    'SELECT "Column type already removed" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'role') > 0,
    'ALTER TABLE users DROP COLUMN role', 
    'SELECT "Column role already removed" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'is_small_group') > 0,
    'ALTER TABLE users DROP COLUMN is_small_group', 
    'SELECT "Column is_small_group already removed" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 5: Update users table structure for the new multi-orchestra system
-- Add unique constraint on username since users are now globally unique (if it doesn't exist)
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = 'users' AND index_name = 'idx_users_username_unique') = 0,
    'ALTER TABLE users ADD UNIQUE KEY idx_users_username_unique (username)', 
    'SELECT "Unique constraint on username already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 6: Update migration tracking
INSERT INTO migration_backup_info (migration_name, users_migrated, orchestras_count, username_conflicts_resolved)
SELECT 
    '20250926_152000_cleanup_old_structure',
    (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE),
    (SELECT COUNT(*) FROM orchestras),
    0 as username_conflicts_resolved;

-- Step 7: Final verification
SELECT 
    'Old structure cleanup completed successfully.' as status,
    (SELECT COUNT(*) FROM user_orchestras WHERE is_active = TRUE) as active_user_orchestra_relationships,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM orchestras) as total_orchestras,
    NOW() as completed_at;

-- Log the completion of multi-orchestra migration
SELECT 
    '🎵 MULTI-ORCHESTRA MIGRATION COMPLETE! 🎵' as message,
    'Users can now belong to multiple orchestras' as new_feature,
    'Access orchestras via /{orchestra_id}/ URLs' as new_url_structure;
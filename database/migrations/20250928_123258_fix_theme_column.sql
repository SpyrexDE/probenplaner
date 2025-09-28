-- Fix theme column migration
-- This migration adds the theme column to users table that was missing due to multi-orchestra migration conflicts
-- The original theme migration (20250912_135624_add_theme_to_users.sql) tried to add theme AFTER is_small_group
-- but the multi-orchestra cleanup removed is_small_group, causing the theme column to not be added properly

-- Add theme preference column to users table (if it doesn't exist)
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'users' AND column_name = 'theme') = 0,
    'ALTER TABLE users ADD COLUMN theme VARCHAR(50) NOT NULL DEFAULT "default" AFTER password', 
    'SELECT "theme column already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for theme column for better performance (if it doesn't exist)
SET @sql = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = 'users' AND index_name = 'idx_users_theme') = 0,
    'CREATE INDEX idx_users_theme ON users (theme)', 
    'SELECT "theme index already exists" as notice');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update any existing users that might have empty theme values
UPDATE users SET theme = 'default' WHERE theme = '' OR theme IS NULL;

-- Log the completion
SELECT 
    'Theme column fix completed successfully.' as status,
    (SELECT COUNT(*) FROM users WHERE theme = 'default') as users_with_default_theme,
    NOW() as completed_at;

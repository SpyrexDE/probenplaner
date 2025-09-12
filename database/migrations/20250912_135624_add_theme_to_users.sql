-- Add theme preference column to users table
-- This allows users to select their preferred theme

ALTER TABLE `users` 
ADD COLUMN `theme` VARCHAR(50) NOT NULL DEFAULT 'default' 
AFTER `is_small_group`;

-- Add index for theme column for better performance
CREATE INDEX `idx_users_theme` ON `users` (`theme`);

-- Update existing users to use default theme
UPDATE `users` SET `theme` = 'default' WHERE `theme` = '';
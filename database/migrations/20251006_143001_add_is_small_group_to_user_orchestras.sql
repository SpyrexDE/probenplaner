-- Add is_small_group field to user_orchestras table
-- This field stores whether a user is in a small group for a specific orchestra
-- This replaces the old is_small_group field that was removed from the users table during multi-orchestra migration

ALTER TABLE `user_orchestras` 
ADD COLUMN `is_small_group` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Whether user is in small group for this orchestra';

-- Add index for performance
ALTER TABLE `user_orchestras` 
ADD INDEX `idx_is_small_group` (`is_small_group`);

-- Add keycloak_only flag to invite_links table (idempotent)
SET @dbname = DATABASE();
SET @col = 'keycloak_only';
SET @tbl = 'invite_links';
SET @sql = IF(
    NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col
    ),
    'ALTER TABLE invite_links ADD COLUMN keycloak_only TINYINT(1) NOT NULL DEFAULT 0 AFTER created_by',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
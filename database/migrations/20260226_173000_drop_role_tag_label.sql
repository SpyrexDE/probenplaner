-- Drop tag_label column from roles table — name is the only label field (idempotent)
SET @dbname = DATABASE();
SET @sql = IF(
    EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'tag_label'
    ),
    'ALTER TABLE roles DROP COLUMN tag_label',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
-- [Description] Replace username with email as primary identifier, add orchestra-specific display names
-- Backfill display_name from username where missing
UPDATE users
SET display_name = username
WHERE display_name IS NULL
    OR display_name = '';
-- Backfill email from username where missing
UPDATE users
SET email = username
WHERE email IS NULL
    OR email = '';
-- Drop username index and column
DROP INDEX idx_users_username ON users;
ALTER TABLE users DROP COLUMN username;
-- Unique email constraint
ALTER TABLE users
ADD UNIQUE INDEX idx_users_email (email);
-- Orchestra-specific display name
ALTER TABLE user_orchestras
ADD COLUMN display_name VARCHAR(100) NULL
AFTER type;
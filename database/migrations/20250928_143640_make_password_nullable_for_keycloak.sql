-- Make password nullable for Keycloak users
-- Description: Allows password to be NULL for Keycloak-authenticated users
-- Date: 2025-01-01

-- Make password column nullable
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;

-- Add comment to document the change
ALTER TABLE users COMMENT = 'Users table with nullable password for Keycloak authentication';
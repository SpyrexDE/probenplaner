-- Add Keycloak Support
-- Description: Adds Keycloak authentication fields to users table
-- Date: 2025-01-01

-- Add Keycloak fields to users table
ALTER TABLE users ADD COLUMN keycloak_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN auth_provider ENUM('local', 'keycloak') DEFAULT 'local';

-- Add indexes for performance
ALTER TABLE users ADD UNIQUE KEY idx_keycloak_id (keycloak_id);
ALTER TABLE users ADD KEY idx_email (email);

-- Add comment to document the changes
ALTER TABLE users COMMENT = 'Users table with Keycloak authentication support';

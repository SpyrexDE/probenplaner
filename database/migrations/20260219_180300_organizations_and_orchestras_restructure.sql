-- [Description] Create organizations table, migrate all orchestras into default org, add slug, remove legacy fields
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Seed default organization
INSERT IGNORE INTO organizations (name, slug)
VALUES ('Default', 'default');
-- Add organization_id and slug to orchestras
ALTER TABLE orchestras
ADD COLUMN organization_id INT NULL
AFTER id,
    ADD COLUMN slug VARCHAR(100) NULL
AFTER name;
-- Migrate: all orchestras into the default org, copy token as slug
UPDATE orchestras
SET organization_id = (
        SELECT id
        FROM organizations
        LIMIT 1
    );
UPDATE orchestras
SET slug = token;
-- Enforce constraints
ALTER TABLE orchestras
MODIFY organization_id INT NOT NULL,
    MODIFY slug VARCHAR(100) NOT NULL,
    ADD CONSTRAINT fk_orchestras_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    ADD UNIQUE INDEX idx_org_slug (organization_id, slug);
-- Remove legacy fields
ALTER TABLE orchestras DROP COLUMN token,
    DROP COLUMN leader_pw,
    DROP COLUMN conductor_id;
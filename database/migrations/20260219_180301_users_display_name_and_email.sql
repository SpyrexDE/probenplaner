-- [Description] Add display_name, org admin flag, org link. Set email = username as fallback
ALTER TABLE users
ADD COLUMN display_name VARCHAR(100) NULL
AFTER username,
    ADD COLUMN is_org_admin TINYINT(1) NOT NULL DEFAULT 0
AFTER display_name,
    ADD COLUMN organization_id INT NULL
AFTER is_org_admin,
    ADD CONSTRAINT fk_users_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE
SET NULL;
UPDATE users
SET display_name = username
WHERE display_name IS NULL;
UPDATE users
SET email = username
WHERE email IS NULL
    OR email = '';
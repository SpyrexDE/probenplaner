-- [Description] Add keycloak_only flag to invite_links table
ALTER TABLE invite_links
ADD COLUMN keycloak_only TINYINT(1) NOT NULL DEFAULT 0
AFTER created_by;
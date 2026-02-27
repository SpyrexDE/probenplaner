-- [Description] Create roles table, migrate per-user permissions to role-based system, drop legacy tables
-- ── Step 1: Create roles table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orchestra_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    tag_label VARCHAR(50) NOT NULL,
    tag_color VARCHAR(7) NOT NULL DEFAULT '#478cf4',
    permissions JSON NOT NULL,
    is_system BOOLEAN NOT NULL DEFAULT 0,
    is_default BOOLEAN NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (orchestra_id) REFERENCES orchestras(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_name (orchestra_id, name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ── Step 2: Add role_id FK to user_orchestras ───────────────────────
ALTER TABLE user_orchestras
ADD COLUMN role_id INT NULL
AFTER is_active;
ALTER TABLE user_orchestras
ADD CONSTRAINT fk_user_orchestras_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE
SET NULL;
-- ── Step 3: Create system roles per orchestra ───────────────────────
INSERT INTO roles (
        orchestra_id,
        name,
        tag_label,
        tag_color,
        permissions,
        is_system,
        is_default,
        sort_order
    )
SELECT o.id,
    'Leitung',
    'Leitung',
    '#478cf4',
    JSON_ARRAY(
        'can_attend_rehearsals',
        'can_view_own_section_stats',
        'can_view_all_section_stats',
        'can_view_members',
        'can_manage_rehearsals',
        'can_manage_members',
        'can_manage_permissions',
        'can_manage_ensemble'
    ),
    1,
    0,
    0
FROM orchestras o;
INSERT INTO roles (
        orchestra_id,
        name,
        tag_label,
        tag_color,
        permissions,
        is_system,
        is_default,
        sort_order
    )
SELECT o.id,
    'Mitglied',
    'Mitglied',
    '#10b981',
    JSON_ARRAY('can_attend_rehearsals'),
    1,
    1,
    100
FROM orchestras o;
-- ── Step 4: Assign roles based on existing permissions ──────────────
-- Users with can_manage_ensemble → Leitung
UPDATE user_orchestras uo
    JOIN user_ensemble_permissions uep ON uep.user_orchestra_id = uo.id
    JOIN permissions p ON p.id = uep.permission_id
    AND p.name = 'can_manage_ensemble'
SET uo.role_id = (
        SELECT r.id
        FROM roles r
        WHERE r.orchestra_id = uo.orchestra_id
            AND r.name = 'Leitung'
        LIMIT 1
    );
-- Remaining users without a role → Mitglied
UPDATE user_orchestras uo
SET uo.role_id = (
        SELECT r.id
        FROM roles r
        WHERE r.orchestra_id = uo.orchestra_id
            AND r.name = 'Mitglied'
        LIMIT 1
    )
WHERE uo.role_id IS NULL;
-- ── Step 5: Add default_role_id to invite_links ─────────────────────
ALTER TABLE invite_links
ADD COLUMN default_role_id INT NULL;
ALTER TABLE invite_links
ADD CONSTRAINT fk_invite_links_role FOREIGN KEY (default_role_id) REFERENCES roles(id) ON DELETE
SET NULL;
-- Migrate conductor invite links to use Leitung role
UPDATE invite_links il
SET il.default_role_id = (
        SELECT r.id
        FROM roles r
        WHERE r.orchestra_id = il.orchestra_id
            AND r.name = 'Leitung'
        LIMIT 1
    )
WHERE il.default_permissions IS NOT NULL;
ALTER TABLE invite_links DROP COLUMN default_permissions;
-- ── Step 6: Drop legacy tables ──────────────────────────────────────
DROP TABLE IF EXISTS user_ensemble_permissions;
DROP TABLE IF EXISTS permissions;
-- [Description] Create permissions + user_ensemble_permissions tables, migrate role enum to permission rows
-- Permissions definition table
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    scope ENUM('ensemble', 'organization') NOT NULL DEFAULT 'ensemble',
    description VARCHAR(255) NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Seed ensemble-level permissions (can_ prefix matches existing session keys)
INSERT IGNORE INTO permissions (name, scope, description)
VALUES (
        'can_view_own_section_stats',
        'ensemble',
        'View stats for own section'
    ),
    (
        'can_view_all_section_stats',
        'ensemble',
        'View stats for all sections'
    ),
    (
        'can_view_members',
        'ensemble',
        'View member list'
    ),
    (
        'can_manage_rehearsals',
        'ensemble',
        'Create, edit, delete rehearsals'
    ),
    (
        'can_manage_members',
        'ensemble',
        'Invite and remove members'
    ),
    (
        'can_manage_permissions',
        'ensemble',
        'Change permissions of other members'
    ),
    (
        'can_manage_ensemble',
        'ensemble',
        'Edit ensemble settings'
    );
-- Junction table: user membership <-> permissions
CREATE TABLE IF NOT EXISTS user_ensemble_permissions (
    user_orchestra_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (user_orchestra_id, permission_id),
    FOREIGN KEY (user_orchestra_id) REFERENCES user_orchestras(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Migrate conductor role -> all permissions
INSERT IGNORE INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uo.id,
    p.id
FROM user_orchestras uo
    CROSS JOIN permissions p
WHERE uo.role = 'conductor'
    AND p.scope = 'ensemble';
-- Migrate leader role -> section stat permissions
INSERT IGNORE INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uo.id,
    p.id
FROM user_orchestras uo
    CROSS JOIN permissions p
WHERE uo.role = 'leader'
    AND p.name IN (
        'can_view_own_section_stats',
        'can_view_all_section_stats'
    );
-- Drop the old role column
ALTER TABLE user_orchestras DROP COLUMN role;
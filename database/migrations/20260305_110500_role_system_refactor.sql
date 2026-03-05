-- Role System Refactor: multi-role support, editable Mitglied, Kleingruppe→role migration
-- 1. Add is_self_assignable to roles
ALTER TABLE roles
ADD COLUMN is_self_assignable TINYINT(1) NOT NULL DEFAULT 0;
-- 2. Make Mitglied editable (keep Leitung as sole system role)
UPDATE roles
SET is_system = 0
WHERE name = 'Mitglied';
-- 3. Junction table for multi-role assignment
CREATE TABLE user_orchestra_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_orchestra_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_orch_role (user_orchestra_id, role_id),
    FOREIGN KEY (user_orchestra_id) REFERENCES user_orchestras(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
-- 4. Migrate existing single-role assignments
INSERT INTO user_orchestra_roles (user_orchestra_id, role_id)
SELECT id,
    role_id
FROM user_orchestras
WHERE role_id IS NOT NULL;
-- 5. Create "Kleingruppe" role per orchestra where is_small_group was used
INSERT INTO roles (
        orchestra_id,
        name,
        tag_color,
        permissions,
        is_system,
        is_default,
        is_self_assignable,
        sort_order
    )
SELECT DISTINCT uo.orchestra_id,
    'Kleingruppe',
    '#8b5cf6',
    '["can_attend_rehearsals"]',
    0,
    0,
    1,
    75
FROM user_orchestras uo
WHERE uo.is_small_group = 1
    AND NOT EXISTS (
        SELECT 1
        FROM roles r
        WHERE r.orchestra_id = uo.orchestra_id
            AND r.name = 'Kleingruppe'
    );
-- 6. Assign Kleingruppe role to users who had is_small_group=1
INSERT INTO user_orchestra_roles (user_orchestra_id, role_id)
SELECT uo.id,
    r.id
FROM user_orchestras uo
    JOIN roles r ON r.orchestra_id = uo.orchestra_id
    AND r.name = 'Kleingruppe'
WHERE uo.is_small_group = 1;
-- 7. Junction table for rehearsal→role scoping
CREATE TABLE rehearsal_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rehearsal_id INT NOT NULL,
    role_id INT NOT NULL,
    UNIQUE KEY uq_rehearsal_role (rehearsal_id, role_id),
    FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
-- 8. Migrate is_small_group rehearsals → rehearsal_roles
INSERT INTO rehearsal_roles (rehearsal_id, role_id)
SELECT rh.id,
    ro.id
FROM rehearsals rh
    JOIN roles ro ON ro.orchestra_id = rh.orchestra_id
    AND ro.name = 'Kleingruppe'
WHERE rh.is_small_group = 1;
-- 9. Drop old columns
ALTER TABLE user_orchestras DROP COLUMN role_id;
ALTER TABLE user_orchestras DROP COLUMN is_small_group;
ALTER TABLE rehearsals DROP COLUMN is_small_group;
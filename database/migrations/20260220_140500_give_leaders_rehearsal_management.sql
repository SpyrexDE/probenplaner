-- Migration: Give existing section leaders the 'can_manage_rehearsals' permission
INSERT IGNORE INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uo.id,
    p.id
FROM user_orchestras uo
    CROSS JOIN permissions p
WHERE uo.type = 'section_leader'
    AND p.name = 'can_manage_rehearsals'
    AND NOT EXISTS (
        SELECT 1
        FROM user_ensemble_permissions uep2
        WHERE uep2.user_orchestra_id = uo.id
            AND uep2.permission_id = p.id
    );
-- Also give them can_view_members just in case they need to see member lists for their section
INSERT IGNORE INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uo.id,
    p.id
FROM user_orchestras uo
    CROSS JOIN permissions p
WHERE uo.type = 'section_leader'
    AND p.name = 'can_view_members'
    AND NOT EXISTS (
        SELECT 1
        FROM user_ensemble_permissions uep2
        WHERE uep2.user_orchestra_id = uo.id
            AND uep2.permission_id = p.id
    );
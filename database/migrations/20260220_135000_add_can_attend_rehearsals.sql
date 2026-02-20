-- [Description] Add can_attend_rehearsals permission
-- Insert the new permission
INSERT INTO permissions (name, scope, description)
VALUES (
        'can_attend_rehearsals',
        'ensemble',
        'Kann an Proben teilnehmen und zu/absagen'
    );
-- Assign to everyone who DOES NOT have can_manage_ensemble (the old implicit rule)
-- This ensures backward compatibility: members and leaders get it, conductors don't.
INSERT INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uo.id,
    p.id
FROM user_orchestras uo
    CROSS JOIN permissions p
WHERE p.name = 'can_attend_rehearsals'
    AND NOT EXISTS (
        SELECT 1
        FROM user_ensemble_permissions uep
            JOIN permissions p2 ON uep.permission_id = p2.id
        WHERE uep.user_orchestra_id = uo.id
            AND p2.name = 'can_manage_ensemble'
    );
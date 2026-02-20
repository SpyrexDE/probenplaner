-- Ensure all conductors (users with can_manage_ensemble) have the full conductor permission set
INSERT IGNORE INTO user_ensemble_permissions (user_orchestra_id, permission_id)
SELECT uep_existing.user_orchestra_id,
    p.id
FROM user_ensemble_permissions uep_existing
    JOIN permissions p_flag ON uep_existing.permission_id = p_flag.id
    AND p_flag.name = 'can_manage_ensemble'
    CROSS JOIN permissions p
WHERE p.name IN (
        'can_view_own_section_stats',
        'can_view_all_section_stats',
        'can_view_members',
        'can_manage_rehearsals',
        'can_manage_members',
        'can_manage_permissions',
        'can_manage_ensemble'
    );
-- Grant can_view_schedule to all roles that have can_attend_rehearsals
UPDATE roles
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'can_view_schedule')
WHERE JSON_CONTAINS(permissions, '"can_attend_rehearsals"')
    AND NOT JSON_CONTAINS(permissions, '"can_view_schedule"');
-- Also grant to conductor/management roles that have can_manage_rehearsals
UPDATE roles
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'can_view_schedule')
WHERE JSON_CONTAINS(permissions, '"can_manage_rehearsals"')
    AND NOT JSON_CONTAINS(permissions, '"can_view_schedule"');
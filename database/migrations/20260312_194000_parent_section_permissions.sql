-- Migration: Add parent_section tier to register-scoped permissions
-- Preserves existing behavior: roles with own_section get parent_section too

-- Add can_view_parent_section_stats where can_view_own_section_stats exists
UPDATE roles 
SET permissions = JSON_ARRAY_APPEND(
    permissions, 
    '$', 
    'can_view_parent_section_stats'
)
WHERE JSON_CONTAINS(permissions, '"can_view_own_section_stats"')
  AND NOT JSON_CONTAINS(permissions, '"can_view_parent_section_stats"');

-- Add can_manage_attendance_parent_section where can_manage_attendance_own_section exists
UPDATE roles 
SET permissions = JSON_ARRAY_APPEND(
    permissions, 
    '$', 
    'can_manage_attendance_parent_section'
)
WHERE JSON_CONTAINS(permissions, '"can_manage_attendance_own_section"')
  AND NOT JSON_CONTAINS(permissions, '"can_manage_attendance_parent_section"');

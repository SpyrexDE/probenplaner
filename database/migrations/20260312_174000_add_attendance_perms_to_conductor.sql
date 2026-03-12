-- Update existing conductor roles to include the new attendance permissions
UPDATE roles
SET permissions = JSON_ARRAY_APPEND(
    JSON_ARRAY_APPEND(permissions, '$', 'can_manage_attendance_own_section'),
    '$', 'can_manage_attendance_all'
)
WHERE is_system = 1 AND name = 'Leitung'
  AND JSON_SEARCH(permissions, 'one', 'can_manage_attendance_all') IS NULL;

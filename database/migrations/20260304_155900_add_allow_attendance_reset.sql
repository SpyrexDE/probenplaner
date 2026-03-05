-- Allow admins to enable/disable long-press attendance reset
ALTER TABLE orchestras
ADD COLUMN allow_attendance_reset TINYINT(1) DEFAULT 1;
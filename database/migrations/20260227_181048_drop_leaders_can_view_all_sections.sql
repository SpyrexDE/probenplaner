-- [Description] Remove redundant leaders_can_view_all_sections column (replaced by role-based can_view_all_section_stats permission)
ALTER TABLE orchestras DROP COLUMN leaders_can_view_all_sections;
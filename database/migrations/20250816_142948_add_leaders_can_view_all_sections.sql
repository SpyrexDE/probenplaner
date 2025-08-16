-- Add a setting to allow leaders to view all sections (sectional view)
ALTER TABLE orchestras
    ADD COLUMN leaders_can_view_all_sections TINYINT(1) NOT NULL DEFAULT 0 AFTER leader_pw;

-- [Description] Add section_config JSON column to orchestras for per-ensemble register customization
ALTER TABLE orchestras
ADD COLUMN section_config JSON DEFAULT NULL
AFTER name;
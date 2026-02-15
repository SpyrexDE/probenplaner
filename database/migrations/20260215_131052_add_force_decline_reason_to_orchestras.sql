-- Add force_decline_reason to orchestras
ALTER TABLE orchestras
ADD COLUMN force_decline_reason TINYINT(1) DEFAULT 0;
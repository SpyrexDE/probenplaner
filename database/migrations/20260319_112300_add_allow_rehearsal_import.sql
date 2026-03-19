-- Add allow_rehearsal_import feature setting to orchestras table
ALTER TABLE orchestras ADD COLUMN allow_rehearsal_import BOOLEAN DEFAULT TRUE;

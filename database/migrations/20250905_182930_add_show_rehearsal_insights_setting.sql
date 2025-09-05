-- Add show_rehearsal_insights setting to orchestras table
ALTER TABLE orchestras ADD COLUMN show_rehearsal_insights BOOLEAN DEFAULT FALSE;
-- Add note column to user_promises table
ALTER TABLE user_promises ADD COLUMN note TEXT DEFAULT NULL;

-- Update existing records to have empty note
UPDATE user_promises SET note = '' WHERE note IS NULL; 
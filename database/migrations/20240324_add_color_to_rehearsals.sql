-- Add color column to rehearsals table
ALTER TABLE rehearsals ADD COLUMN color VARCHAR(50) DEFAULT 'white';

-- Update existing records to have default color
UPDATE rehearsals SET color = 'white' WHERE color IS NULL; 
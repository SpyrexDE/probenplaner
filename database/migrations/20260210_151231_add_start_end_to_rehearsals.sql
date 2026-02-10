-- Add start and end datetime columns to rehearsals table
-- Populate them from existing date/time columns
-- Drop old date/time columns
-- Add new columns allowing NULL initially
ALTER TABLE rehearsals
ADD COLUMN start DATETIME NULL
AFTER id;
ALTER TABLE rehearsals
ADD COLUMN
end DATETIME NULL
AFTER start;
-- Migrate existing data
UPDATE rehearsals
SET start = CONCAT(date, ' ', start_time),
end = CONCAT(date, ' ', end_time)
WHERE date IS NOT NULL
    AND start_time IS NOT NULL
    AND end_time IS NOT NULL;
-- Make columns NOT NULL (validating data consistency)
-- Make columns NOT NULL (this will fail if any rows have NULL start/end)
ALTER TABLE rehearsals
MODIFY COLUMN start DATETIME NOT NULL;
ALTER TABLE rehearsals
MODIFY COLUMN
end DATETIME NOT NULL;
-- Drop old columns
ALTER TABLE rehearsals DROP COLUMN date;
ALTER TABLE rehearsals DROP COLUMN start_time;
ALTER TABLE rehearsals DROP COLUMN end_time;
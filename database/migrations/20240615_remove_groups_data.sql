-- Remove groups_data column from rehearsals table as it's legacy and has been replaced by rehearsal_groups table
-- Run this SQL command in your database management tool to remove the column
ALTER TABLE rehearsals DROP COLUMN groups_data; 
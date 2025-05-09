-- Add is_small_group field to rehearsals table
ALTER TABLE rehearsals ADD COLUMN is_small_group TINYINT(1) NOT NULL DEFAULT 0;

-- Update the rehearsals where any group has an asterisk to be small group rehearsals
UPDATE rehearsals 
SET is_small_group = 1
WHERE groups_data LIKE '%*%'; 
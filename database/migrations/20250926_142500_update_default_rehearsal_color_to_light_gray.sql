-- Update default rehearsal color to light gray and migrate existing white values

-- Ensure color column has a default of light gray (#e5e7eb)
ALTER TABLE rehearsals MODIFY COLUMN color VARCHAR(50) DEFAULT '#e5e7eb';

-- Migrate existing values that are white (various forms) to light gray
UPDATE rehearsals
SET color = '#e5e7eb'
WHERE LOWER(color) IN ('white', '#ffffff', 'ffffff')
   OR color IS NULL
   OR color = '';



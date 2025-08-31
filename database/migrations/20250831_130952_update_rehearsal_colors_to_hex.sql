-- Update rehearsal colors from old string format to new hex format
-- This migration converts the old color values to the new hex color system

-- Update existing color values to hex format
UPDATE rehearsals SET color = '#ffffff' WHERE color = 'white';
UPDATE rehearsals SET color = '#3b82f6' WHERE color = 'blue';
UPDATE rehearsals SET color = '#10b981' WHERE color = 'green';
UPDATE rehearsals SET color = '#f59e0b' WHERE color = 'yellow';
UPDATE rehearsals SET color = '#ef4444' WHERE color = 'red';
UPDATE rehearsals SET color = '#8b5cf6' WHERE color = 'purple';

-- Set default color for any NULL or invalid colors
UPDATE rehearsals SET color = '#ffffff' WHERE color IS NULL OR color NOT LIKE '#%';
-- Add type field to rehearsals table
ALTER TABLE `rehearsals` 
ADD COLUMN `type` VARCHAR(100) NOT NULL DEFAULT 'Probe' AFTER `date`;

-- Update existing records to have the default type
UPDATE `rehearsals` SET `type` = 'Probe' WHERE `type` IS NULL OR `type` = '';

-- Add index for better performance
ALTER TABLE `rehearsals` ADD INDEX `idx_rehearsals_type` (`type`);
-- Add timespans
ALTER TABLE `rehearsals`
ADD COLUMN `start_time` TIME NULL AFTER `date`,
ADD COLUMN `end_time` TIME NULL AFTER `start_time`;

UPDATE `rehearsals`
SET
    `start_time` = `time`,
    `end_time` = ADDTIME(`time`, '01:00:00');

ALTER TABLE `rehearsals`
DROP COLUMN `time`;

ALTER TABLE `rehearsals`
MODIFY COLUMN `start_time` TIME NOT NULL,
MODIFY COLUMN `end_time` TIME NOT NULL;

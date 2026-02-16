-- Add rehearsal_infos table for optional info box
CREATE TABLE IF NOT EXISTS `rehearsal_infos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `rehearsal_id` int(11) NOT NULL,
    `emoji` varchar(16) NOT NULL DEFAULT '❗',
    `text` text NOT NULL,
    `sort_order` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `rehearsal_id` (`rehearsal_id`),
    CONSTRAINT `rehearsal_infos_ibfk_1` FOREIGN KEY (`rehearsal_id`) REFERENCES `rehearsals` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
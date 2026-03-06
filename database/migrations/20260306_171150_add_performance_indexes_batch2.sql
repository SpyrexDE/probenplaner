-- Performance indexes for batch query patterns (batch 2)
-- Use stored procedure to safely add indexes that may already exist as FK indexes
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DELIMITER // CREATE PROCEDURE add_index_if_not_exists(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns VARCHAR(255)
) BEGIN
DECLARE index_exists INT DEFAULT 0;
SELECT COUNT(*) INTO index_exists
FROM information_schema.statistics
WHERE table_schema = DATABASE()
    AND table_name = p_table
    AND index_name = p_index;
IF index_exists = 0 THEN
SET @sql = CONCAT(
        'CREATE INDEX ',
        p_index,
        ' ON ',
        p_table,
        '(',
        p_columns,
        ')'
    );
PREPARE stmt
FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
END IF;
END // DELIMITER;
CALL add_index_if_not_exists(
    'rehearsal_groups',
    'idx_rg_rehearsal',
    'rehearsal_id'
);
CALL add_index_if_not_exists(
    'rehearsal_roles',
    'idx_rr_rehearsal',
    'rehearsal_id'
);
CALL add_index_if_not_exists(
    'rehearsal_schedule_items',
    'idx_rsi_rehearsal',
    'rehearsal_id'
);
CALL add_index_if_not_exists(
    'rehearsal_infos',
    'idx_ri_rehearsal',
    'rehearsal_id'
);
CALL add_index_if_not_exists(
    'user_orchestra_roles',
    'idx_uor_uo',
    'user_orchestra_id'
);
CALL add_index_if_not_exists(
    'user_promises',
    'idx_up_user_rehearsal',
    'user_id, rehearsal_id'
);
CALL add_index_if_not_exists(
    'user_orchestras',
    'idx_uo_orchestra_active',
    'orchestra_id, is_active'
);
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
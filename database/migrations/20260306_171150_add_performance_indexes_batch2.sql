-- Performance indexes for batch query patterns (batch 2)

-- rehearsal_groups.rehearsal_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'rehearsal_groups' AND index_name = 'idx_rg_rehearsal'), 'CREATE INDEX idx_rg_rehearsal ON rehearsal_groups(rehearsal_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rehearsal_roles.rehearsal_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'rehearsal_roles' AND index_name = 'idx_rr_rehearsal'), 'CREATE INDEX idx_rr_rehearsal ON rehearsal_roles(rehearsal_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rehearsal_schedule_items.rehearsal_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'rehearsal_schedule_items' AND index_name = 'idx_rsi_rehearsal'), 'CREATE INDEX idx_rsi_rehearsal ON rehearsal_schedule_items(rehearsal_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- rehearsal_infos.rehearsal_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'rehearsal_infos' AND index_name = 'idx_ri_rehearsal'), 'CREATE INDEX idx_ri_rehearsal ON rehearsal_infos(rehearsal_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- user_orchestra_roles.user_orchestra_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'user_orchestra_roles' AND index_name = 'idx_uor_uo'), 'CREATE INDEX idx_uor_uo ON user_orchestra_roles(user_orchestra_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- user_promises.user_id, rehearsal_id
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'user_promises' AND index_name = 'idx_up_user_rehearsal'), 'CREATE INDEX idx_up_user_rehearsal ON user_promises(user_id, rehearsal_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- user_orchestras.orchestra_id, is_active
SET @sql = IF(NOT EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'user_orchestras' AND index_name = 'idx_uo_orchestra_active'), 'CREATE INDEX idx_uo_orchestra_active ON user_orchestras(orchestra_id, is_active)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
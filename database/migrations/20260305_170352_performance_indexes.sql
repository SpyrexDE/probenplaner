-- Performance indexes for batch query patterns
-- Index on rehearsals.end for upcoming/past filtering
CREATE INDEX idx_rehearsals_end ON rehearsals(orchestra_id, `end`);
-- Index on user_promises.rehearsal_id for JOIN in getAllForOrchestra
CREATE INDEX idx_up_rehearsal ON user_promises(rehearsal_id);
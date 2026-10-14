-- JOinJMD partner API (/api/v1): members and leaders are synced before they ever open the
-- Probenplaner.
-- last_login_at: NULL = never logged in. Reminders only go to people who have opened the
-- Probenplaner at least once. Every account that exists before this migration has logged in
-- (self-registered or created at its first JOinJMD login), so it is backfilled.
-- section_confirmed: 0 = the section (type) was guessed from the JOinJMD instrument without an
-- exact match; the member confirms or changes it on the next visit.
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL;
UPDATE users SET last_login_at = created_at WHERE last_login_at IS NULL;
ALTER TABLE user_orchestras ADD COLUMN section_confirmed TINYINT(1) NOT NULL DEFAULT 1;

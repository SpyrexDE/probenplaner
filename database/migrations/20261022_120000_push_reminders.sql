-- Push reminder before rehearsals (JOinJMD).
-- push_reminder_minutes: lead time the member chose in the profile, NULL = off.
-- push_reminders: one row per member and rehearsal already reminded. The push API is not
-- idempotent, so the job (src/jobs/push-reminders.php) sends each reminder at most once.
ALTER TABLE users ADD COLUMN push_reminder_minutes SMALLINT UNSIGNED NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS push_reminders (
    user_id INT NOT NULL,
    rehearsal_id INT NOT NULL,
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, rehearsal_id),
    CONSTRAINT fk_push_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_push_reminders_rehearsal FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

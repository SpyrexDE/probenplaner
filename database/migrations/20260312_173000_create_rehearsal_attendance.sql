-- Create rehearsal_attendance table for documenting actual attendance
CREATE TABLE IF NOT EXISTS rehearsal_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rehearsal_id INT NOT NULL,
    user_id INT NOT NULL,
    present BOOLEAN NOT NULL,
    comment TEXT DEFAULT NULL,
    recorded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_attendance_unique (rehearsal_id, user_id),
    CONSTRAINT fk_attendance_rehearsal FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_attendance_rehearsal ON rehearsal_attendance(rehearsal_id);
CREATE INDEX idx_attendance_user ON rehearsal_attendance(user_id);

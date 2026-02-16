-- Add rehearsal schedule items table
CREATE TABLE rehearsal_schedule_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rehearsal_id INT NOT NULL,
    time TIME NOT NULL,
    label VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_schedule_rehearsal FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    KEY idx_schedule_rehearsal (rehearsal_id),
    KEY idx_schedule_order (rehearsal_id, sort_order)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
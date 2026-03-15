CREATE TABLE IF NOT EXISTS rehearsal_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rehearsal_id INT NOT NULL,
    orchestra_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    INDEX idx_orchestra (orchestra_id),
    INDEX idx_rehearsal (rehearsal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

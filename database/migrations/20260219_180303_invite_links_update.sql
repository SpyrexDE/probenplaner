-- [Description] Create invite_links table with email targeting, JSON permissions, expiry
CREATE TABLE IF NOT EXISTS invite_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orchestra_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NULL,
    default_permissions JSON NULL,
    expires_at DATETIME NULL,
    used_at DATETIME NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orchestra_id) REFERENCES orchestras(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Probenplaner database schema

-- Drop tables if they exist to ensure clean install
DROP TABLE IF EXISTS user_promises;
DROP TABLE IF EXISTS rehearsal_groups;
DROP TABLE IF EXISTS rehearsals;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS orchestras;

-- Create orchestras table
CREATE TABLE orchestras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    token VARCHAR(50) NOT NULL UNIQUE,
    leader_pw VARCHAR(255) NOT NULL,
    conductor_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    orchestra_id INT NOT NULL,
    role ENUM('member', 'leader', 'conductor') NOT NULL DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (orchestra_id) REFERENCES orchestras(id) ON DELETE CASCADE,
    UNIQUE KEY unique_username_per_orchestra (username, orchestra_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key from orchestras to users for conductor
ALTER TABLE orchestras
ADD CONSTRAINT fk_orchestras_conductor FOREIGN KEY (conductor_id) REFERENCES users(id) ON DELETE SET NULL;

-- Create rehearsals table
CREATE TABLE rehearsals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT,
    color VARCHAR(50) DEFAULT '#ffffff',
    groups_data TEXT,
    orchestra_id INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (orchestra_id) REFERENCES orchestras(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create rehearsal_groups table to store which groups are involved in each rehearsal
CREATE TABLE rehearsal_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rehearsal_id INT NOT NULL,
    group_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rehearsal_group (rehearsal_id, group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_promises table
CREATE TABLE user_promises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    rehearsal_id INT NOT NULL,
    attending BOOLEAN NOT NULL DEFAULT TRUE,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rehearsal_id) REFERENCES rehearsals(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_rehearsal (user_id, rehearsal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_type ON users(type);
CREATE INDEX idx_users_orchestra ON users(orchestra_id);
CREATE INDEX idx_rehearsals_date ON rehearsals(date);
CREATE INDEX idx_rehearsals_orchestra ON rehearsals(orchestra_id);
CREATE INDEX idx_promises_user ON user_promises(user_id);
CREATE INDEX idx_promises_rehearsal ON user_promises(rehearsal_id); 
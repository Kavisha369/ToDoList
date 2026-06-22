-- ============================================
--  ToDoList Application Database Schema
--  Compatible with MySQL via XAMPP
-- ============================================

CREATE DATABASE IF NOT EXISTS todolist_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE todolist_db;

-- -----------------------------------------------
-- Table: users
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)      NOT NULL UNIQUE,
    email       VARCHAR(100)     NOT NULL UNIQUE,
    password    VARCHAR(255)     NOT NULL,
    role        ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Table: tasks  (stores both folders and subtasks)
-- parent_folder_id = NULL  → top-level folder
-- parent_folder_id = <id>  → subtask inside that folder
-- is_folder = 1            → this row is a folder
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id          INT UNSIGNED   NOT NULL,
    title            VARCHAR(255)   NOT NULL,
    description      TEXT                    DEFAULT NULL,
    status           ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    priority         ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    is_folder        TINYINT(1)     NOT NULL DEFAULT 0,
    parent_folder_id INT UNSIGNED            DEFAULT NULL,
    due_date         DATE                    DEFAULT NULL,
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id)          REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_folder_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_user_id          (user_id),
    INDEX idx_parent_folder_id (parent_folder_id),
    INDEX idx_status           (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Default admin account
-- Password: admin123  (bcrypt hash)
-- -----------------------------------------------
INSERT IGNORE INTO users (username, email, password, role)
VALUES (
    'admin',
    'admin@todolist.local',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- admin123
    'admin'
);

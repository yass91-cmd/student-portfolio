-- PortfolioHub Database Schema
-- Run this file to set up the database
-- NOTE: On shared hosts (InfinityFree etc.) the database already exists.
-- Import this file while already inside your database in phpMyAdmin.

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    username    VARCHAR(50)   NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email    (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profiles table (one row per user)
CREATE TABLE IF NOT EXISTS profiles (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT           NOT NULL,
    photo         VARCHAR(300)  DEFAULT '',
    bio           TEXT,
    skills        TEXT,          -- comma-separated list
    projects      LONGTEXT,      -- JSON array of project objects
    education     LONGTEXT,      -- JSON array of education objects
    template      VARCHAR(20)   DEFAULT 'template1',
    github_url    VARCHAR(255)  DEFAULT '',
    linkedin_url  VARCHAR(255)  DEFAULT '',
    website_url   VARCHAR(255)  DEFAULT '',
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo user (password: Demo@1234)
INSERT IGNORE INTO users (name, email, username, password) VALUES
(
    'Alex Johnson',
    'demo@portfoliohub.com',
    'demo',
    '$2y$12$8KzNqX5vLmP3rW7tY9uO4OQz1cV6nM2dA8eF0gH3jI5kL7oN9pR1s'
);

INSERT IGNORE INTO profiles (user_id, bio, skills, projects, education, template, github_url, linkedin_url)
SELECT id,
    'Full-stack developer and Computer Science student passionate about building web applications that solve real-world problems. Open to internship opportunities.',
    'PHP, JavaScript, MySQL, HTML, CSS, React, Node.js, Git, Python',
    '[{"title":"PortfolioHub","description":"A student portfolio platform built with PHP and MySQL. Features authentication, CRUD operations, and PDF export.","tech":"PHP, MySQL, CSS","url":"https://github.com/demo"},{"title":"Weather Dashboard","description":"A real-time weather app that fetches data from an open API and displays forecasts with beautiful charts.","tech":"JavaScript, CSS, REST API","url":"https://github.com/demo"},{"title":"Task Manager CLI","description":"A command-line task manager built in Python with SQLite storage and priority queuing.","tech":"Python, SQLite","url":"https://github.com/demo"}]',
    '[{"degree":"Bachelor of Computer Science","school":"State University","year":"2021 – 2025","description":"GPA 3.8 / 4.0 · Dean''s List · Specialisation in Software Engineering"},{"degree":"High School Diploma","school":"Lincoln High School","year":"2017 – 2021","description":"Valedictorian · AP Computer Science A (5/5)"}]',
    'template1',
    'https://github.com/demo',
    'https://linkedin.com/in/demo'
FROM users WHERE username = 'demo';

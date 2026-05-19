-- ============================================================
--  PAWCONNECT - Complete Database
--  Run this in phpMyAdmin or MySQL Workbench
-- ============================================================

-- Drop database if exists (uncomment if needed)
-- DROP DATABASE IF EXISTS pawconnectDB;

-- Create database
CREATE DATABASE IF NOT EXISTS pawconnectDB
CHARACTER SET  latin1
COLLATE  latin1_swedish_ci;

-- Use the database
USE pawconnectDB;

-- ============================================================
--  USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100)  NOT NULL,
  username      VARCHAR(60)   NOT NULL UNIQUE,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password      VARCHAR(255)  NOT NULL,
  phone         VARCHAR(30),
  facebook      VARCHAR(200),
  address       VARCHAR(200),
  birthdate     DATE          NOT NULL,
  profile_photo VARCHAR(200),
  role          ENUM('user','moderator','admin') DEFAULT 'user',
  points        INT           DEFAULT 5,
  mod_points    INT           DEFAULT 0,
  is_banned     TINYINT       DEFAULT 0,
  ban_reason    TEXT,
  ban_until     DATETIME      NULL,
  created_at    DATETIME      DEFAULT NOW()
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  PETS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS pets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL,
  name        VARCHAR(100) NOT NULL,
  species     VARCHAR(50)  NOT NULL,
  breed               VARCHAR(100),
  age                 VARCHAR(50),
  gender              VARCHAR(20)  DEFAULT 'Unknown',
  description         TEXT,
  health_info         TEXT,
  spayed_neutered     ENUM('Yes','No','Unknown') DEFAULT 'Unknown',
  good_with_children  ENUM('Yes','No','Unknown') DEFAULT 'Unknown',
  photo               VARCHAR(200),
  status              ENUM('available','pending','adopted','removed') DEFAULT 'available',
  removed_by  ENUM('user','moderator','admin') NULL,
  removed_at  DATETIME     NULL,
  created_at  DATETIME     DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  ADOPTION REQUESTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS adoption_requests (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pet_id       INT         NOT NULL,
  requester_id INT         NOT NULL,
  message      TEXT,
  status       ENUM('pending','approved','rejected','deleted') DEFAULT 'pending',
  created_at   DATETIME    DEFAULT NOW(),
  updated_at   DATETIME    NULL ON UPDATE NOW(),
  UNIQUE KEY unique_req (pet_id, requester_id),
  FOREIGN KEY (pet_id)       REFERENCES pets(id),
  FOREIGN KEY (requester_id) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  ADOPTIONS LOG TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS adoptions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  pet_id        INT          NOT NULL,
  pet_name      VARCHAR(100),
  adopter_id    INT          NULL,
  adopter_name  VARCHAR(100),
  adopter_email VARCHAR(150),
  adopter_phone VARCHAR(30),
  owner_id      INT          NULL,
  owner_name    VARCHAR(100),
  owner_email   VARCHAR(150),
  owner_phone   VARCHAR(30),
  adopted_at    DATETIME     DEFAULT NOW()
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  REPORTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  pet_id      INT         NOT NULL,
  reporter_id INT         NOT NULL,
  reason      VARCHAR(100) NOT NULL,
  details     TEXT,
  status      ENUM('pending','removed','dismissed') DEFAULT 'pending',
  reviewed_by INT         NULL,
  reviewed_at DATETIME    NULL,
  created_at  DATETIME    DEFAULT NOW(),
  FOREIGN KEY (pet_id)      REFERENCES pets(id),
  FOREIGN KEY (reporter_id) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  MODERATOR LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS mod_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  mod_id      INT          NOT NULL,
  action      VARCHAR(50)  NOT NULL,
  target_type VARCHAR(20)  NOT NULL,
  target_id   INT          NOT NULL,
  notes       TEXT,
  undone      TINYINT      DEFAULT 0,
  undone_by   INT          NULL,
  undone_at   DATETIME     NULL,
  created_at  DATETIME     DEFAULT NOW(),
  FOREIGN KEY (mod_id) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  POINT LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS point_logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  points     INT          NOT NULL,
  type       ENUM('general','mod') DEFAULT 'general',
  reason     VARCHAR(150) NOT NULL,
  created_at DATETIME     DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  BACKUP LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS backup_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(255) NOT NULL,
  filepath    VARCHAR(500),
  filesize    INT DEFAULT 0,
  action_type ENUM('backup', 'restore') DEFAULT 'backup',
  created_by  INT NOT NULL,
  created_at  DATETIME DEFAULT NOW(),
  deleted_at  DATETIME NULL,
  FOREIGN KEY (created_by) REFERENCES users(id)
) DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- ============================================================
--  DEFAULT ADMIN ACCOUNT
--  Username: admin | Password: admin123
-- ============================================================
INSERT IGNORE INTO users (full_name, username, email, password, birthdate, role, points)
VALUES (
  'System Administrator',
  'admin',
  'admin@pawconnect.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  '2000-01-01',
  'admin',
  999
);
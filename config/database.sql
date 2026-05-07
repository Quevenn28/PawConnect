-- ============================================================
--  PAWCONNECT - Complete Database
--  Run this in phpMyAdmin before using the system
-- ============================================================

CREATE DATABASE IF NOT EXISTS pawconnectDB;
USE pawconnectDB;

-- ============================================================
--  USERS TABLE
-- ============================================================
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100)  NOT NULL,
  username      VARCHAR(60)   NOT NULL UNIQUE,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  password      VARCHAR(255)  NOT NULL,
  phone         VARCHAR(30),
  facebook      VARCHAR(200),
  address       VARCHAR(200),
  profile_photo VARCHAR(200),
  role          ENUM('user','moderator','admin') DEFAULT 'user',
  points        INT           DEFAULT 5,
  mod_points    INT           DEFAULT 0,
  is_banned     TINYINT       DEFAULT 0,
  ban_reason    TEXT,
  ban_until     DATETIME      NULL,             -- NULL = permanent ban
  password_reset_token VARCHAR(255) NULL,       -- For password recovery
  password_reset_expires DATETIME NULL,         -- Token expiration (24 hours)
  created_at    DATETIME      DEFAULT NOW()
);

-- ============================================================
--  PETS TABLE
-- ============================================================
CREATE TABLE pets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL,
  name        VARCHAR(100) NOT NULL,
  species     VARCHAR(50)  NOT NULL,
  breed               VARCHAR(100),
  age                 VARCHAR(50),
  gender              VARCHAR(20)  DEFAULT 'Unknown',
  description         TEXT,
  health_info         TEXT,
  vaccinated          ENUM('Yes','No','Unknown') DEFAULT 'Unknown',
  spayed_neutered     ENUM('Yes','No','Unknown') DEFAULT 'Unknown',
  good_with_children  ENUM('Yes','No','Unknown') DEFAULT 'Unknown',
  photo               VARCHAR(200),
  status              ENUM('available','pending','adopted','removed') DEFAULT 'available',
  removed_by  ENUM('user','moderator','admin') NULL,  -- who removed it
  removed_at  DATETIME     NULL,
  created_at  DATETIME     DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
--  ADOPTION REQUESTS TABLE
-- ============================================================
CREATE TABLE adoption_requests (
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
);

-- ============================================================
--  ADOPTIONS LOG TABLE
-- ============================================================
CREATE TABLE adoptions (
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
);

-- ============================================================
--  REPORTS TABLE
-- ============================================================
CREATE TABLE reports (
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
);

-- ============================================================
--  MODERATOR LOGS TABLE
-- ============================================================
CREATE TABLE mod_logs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  mod_id      INT          NOT NULL,
  action      VARCHAR(50)  NOT NULL,   -- 'removed_post','dismissed_report','banned_user','unbanned_user'
  target_type VARCHAR(20)  NOT NULL,   -- 'pet','user','report'
  target_id   INT          NOT NULL,
  notes       TEXT,
  undone      TINYINT      DEFAULT 0,
  undone_by   INT          NULL,
  undone_at   DATETIME     NULL,
  created_at  DATETIME     DEFAULT NOW(),
  FOREIGN KEY (mod_id) REFERENCES users(id)
);

-- ============================================================
--  POINT LOGS TABLE
-- ============================================================
CREATE TABLE point_logs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  points     INT          NOT NULL,
  type       ENUM('general','mod') DEFAULT 'general',
  reason     VARCHAR(150) NOT NULL,
  created_at DATETIME     DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
--  NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT          NOT NULL,
  message    VARCHAR(255) NOT NULL,
  link       VARCHAR(255) NULL,
  is_read    TINYINT      DEFAULT 0,
  created_at DATETIME     DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ============================================================
--  DEFAULT ADMIN ACCOUNT
--  Username: admin | Password: admin123
--  CHANGE THIS PASSWORD IMMEDIATELY AFTER SETUP
-- ============================================================
INSERT INTO users (full_name, username, email, password, role, points)
VALUES (
  'System Administrator',
  'admin',
  'admin@pawconnect.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
  'admin',
  999
);

ALTER TABLE users
ADD COLUMN is_banned  TINYINT  DEFAULT 0,
ADD COLUMN ban_reason TEXT,
ADD COLUMN ban_until  DATETIME NULL;

-- ============================================================
--  PETS TABLE UPDATES (for existing databases)
-- ============================================================
ALTER TABLE pets ADD COLUMN health_info TEXT;
ALTER TABLE pets ADD COLUMN spayed_neutered ENUM('Yes','No','Unknown') DEFAULT 'Unknown';
ALTER TABLE pets ADD COLUMN good_with_children ENUM('Yes','No','Unknown') DEFAULT 'Unknown';
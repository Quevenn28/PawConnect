-- PAWCONNECT Simple Database
-- Run this in phpMyAdmin before using

CREATE DATABASE IF NOT EXISTS pawconnect;
USE pawconnect;

-- Users table
CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(100) NOT NULL,
  username   VARCHAR(60)  NOT NULL UNIQUE,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(30),
  facebook   VARCHAR(200),
  address    VARCHAR(200),
  created_at DATETIME DEFAULT NOW()
);

-- Pets table
CREATE TABLE pets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  name        VARCHAR(100) NOT NULL,
  species     VARCHAR(50)  NOT NULL,
  breed       VARCHAR(100),
  age         VARCHAR(50),
  gender      VARCHAR(20)  DEFAULT 'Unknown',
  description TEXT,
  photo       VARCHAR(200),
  status      VARCHAR(20)  DEFAULT 'available',
  created_at  DATETIME DEFAULT NOW(),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Adoptions log
CREATE TABLE adoptions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pet_id       INT NOT NULL,
  pet_name     VARCHAR(100),
  adopter_name VARCHAR(100),
  adopter_email VARCHAR(150),
  adopter_phone VARCHAR(30),
  owner_name   VARCHAR(100),
  owner_email  VARCHAR(150),
  owner_phone  VARCHAR(30),
  adopted_at   DATETIME DEFAULT NOW()
);

-- Adoption requests
CREATE TABLE adoption_requests (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  pet_id       INT NOT NULL,
  requester_id INT NOT NULL,
  message      TEXT,
  status       VARCHAR(20) DEFAULT 'pending',
  created_at   DATETIME DEFAULT NOW(),
  UNIQUE KEY unique_req (pet_id, requester_id),
  FOREIGN KEY (pet_id)       REFERENCES pets(id),
  FOREIGN KEY (requester_id) REFERENCES users(id)
);

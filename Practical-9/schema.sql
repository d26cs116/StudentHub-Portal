-- =====================================================================
--  Practical 9 - Secure User Registration (MySQL / MariaDB)
--  Database: user_registration_db     Table: users
--
--  HOW TO USE: phpMyAdmin -> Import tab -> choose this file -> Import.
--  It only creates what is missing, so importing it again will NOT
--  delete the users that are already registered.
--  (To start again from zero, run:  DROP TABLE users;  and import again.)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS user_registration_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE user_registration_db;

-- ---------------------------------------------------------------------
--  TABLE: users
--  id         PRIMARY KEY : identifies each user (auto-numbered).
--  username   UNIQUE      : the database itself refuses a second user
--                           with the same username.
--  email      UNIQUE      : the same for the email address.
--                           (The collation is case-insensitive, so
--                            "Aarav" and "aarav" count as the same.)
--  password   VARCHAR(255): stores ONLY the hash made by PHP's
--                           password_hash(), never the real password.
--                           A bcrypt hash is 60 characters; 255 leaves
--                           room for stronger algorithms in the future.
--  created_at             : filled in automatically at registration.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name  VARCHAR(100) NOT NULL,
    username   VARCHAR(30)  NOT NULL,
    email      VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No test data is inserted on purpose: the accounts in this table must be
-- created by the registration form, so every password is stored as a real hash.

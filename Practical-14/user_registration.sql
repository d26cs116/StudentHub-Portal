-- =====================================================================
--  Practical 10 - Secure Login System with Role-Based Access Control
--  Database: user_registration_db     Table: users
--  (This file replaces the Practical 9 schema.sql. It is the SAME database.)
--
--  HOW TO USE: phpMyAdmin -> Import tab -> choose this file -> Import.
--  It is SAFE TO IMPORT MORE THAN ONCE and safe on your existing Practical 9
--  database: it never drops a table, never deletes a user and never
--  changes an existing password.
--    * New database?        -> the table is created with the role column.
--    * Practical 9 table?   -> only the missing "role" column is added;
--                              every existing user becomes a 'Student'.
--    * Test accounts        -> added only if they do not exist yet.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS user_registration_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE user_registration_db;

-- ---------------------------------------------------------------------
--  TABLE: users
--  id         PRIMARY KEY : identifies each user (auto-numbered).
--  full_name              : the person's name.
--  username   UNIQUE      : two users cannot share a username.
--  email      UNIQUE      : two users cannot share an email (the login name).
--  password   VARCHAR(255): ONLY the hash from PHP password_hash(); never the real password.
--  role       ENUM        : 'Admin' or 'Student'. The database refuses any other value.
--                           New registrations get 'Student' automatically.
--  created_at             : filled in automatically at registration.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name  VARCHAR(100) NOT NULL,
    username   VARCHAR(30)  NOT NULL,
    email      VARCHAR(100) NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('Admin','Student') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  UPGRADE a Practical 9 table: add the "role" column only if it is missing.
--  (Written with information_schema so it works on both MariaDB and MySQL.)
-- ---------------------------------------------------------------------
SET @has_role := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
);
SET @upgrade_sql := IF(@has_role = 0,
    'ALTER TABLE users ADD COLUMN role ENUM(''Admin'',''Student'') NOT NULL DEFAULT ''Student'' AFTER password',
    'SELECT ''role column already exists - nothing to change'' AS info');
PREPARE upgrade_stmt FROM @upgrade_sql;
EXECUTE upgrade_stmt;
DEALLOCATE PREPARE upgrade_stmt;

-- ---------------------------------------------------------------------
--  TEST ACCOUNTS (passwords are stored as real password_hash() bcrypt hashes)
--      Admin   : admin@studenthub.local    password: Admin@12345
--      Student : student@studenthub.local  password: Student@12345
--  INSERT IGNORE = skip a row that already exists, so importing again changes nothing.
--  Delete these two accounts before any real use of the site.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO users (full_name, username, email, password, role) VALUES
('Test Admin',   'test_admin',   'admin@studenthub.local',
 '$2y$10$UMBNTwnrpLxorfVPcabZLeICD/YixgguZHh4ZP.FRcMaDsbfOJKim', 'Admin'),
('Test Student', 'test_student', 'student@studenthub.local',
 '$2y$10$TKG/oDb52vIwdvYhVy8hTOezdcaZXjR4hHpZtDq2CKjc94VygSIE2', 'Student');

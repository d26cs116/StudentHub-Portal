-- =====================================================================
--  Practical 14 - Dashboard, Role-Based Access Control and Audit Logs
--  Database: user_registration_db  (the SAME database as Practical 9-13)
--
--  WHAT THIS FILE DOES
--    * Makes sure the tables the dashboard reads exist (users, students,
--      events). They already exist if you imported Practical 10, 11 and 12,
--      so for you these three statements change nothing.
--    * Adds the NEW table  audit_logs  with a FOREIGN KEY to users.
--
--  HOW TO USE: phpMyAdmin (http://localhost/phpmyadmin) -> Import tab
--              -> choose this file -> Import.
--  SAFE TO IMPORT MORE THAN ONCE: only CREATE TABLE IF NOT EXISTS is used.
--  Nothing is dropped, no row is deleted, no password is touched.
--  (A brand-new database also needs the two test accounts from
--   user_registration.sql - import that file first in that case.)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS user_registration_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE user_registration_db;

-- ---------------------------------------------------------------------
--  users (Practical 10)  - login accounts. role decides what a user may do.
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
--  students (Practical 11)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(20)  NOT NULL,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    phone      VARCHAR(20)  NOT NULL,
    course     VARCHAR(60)  NOT NULL,
    `year`     TINYINT UNSIGNED NOT NULL,
    status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_student_id (student_id),
    UNIQUE KEY uq_students_email (email),
    KEY idx_students_course (course),
    KEY idx_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  events (Practical 12)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_name  VARCHAR(150) NOT NULL,
    description TEXT         NOT NULL,
    event_date  DATE         NOT NULL,
    event_time  TIME         NULL DEFAULT NULL,
    venue       VARCHAR(150) NOT NULL,
    poster      VARCHAR(255) NULL DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  audit_logs (NEW in Practical 14) - one row per successful admin action
--  id           PRIMARY KEY  : number of the log entry
--  user_id      FOREIGN KEY  : the admin who did it -> users.id.
--                              ON DELETE SET NULL keeps the entry even if the
--                              account is deleted later (history is never lost).
--  action       VARCHAR      : e.g. EVENT_CREATE, EVENT_UPDATE, EVENT_DELETE,
--                              STUDENT_CREATE, STUDENT_UPDATE, STUDENT_DEACTIVATE, ADMIN_LOGIN
--  entity_type  VARCHAR      : what was changed ('event', 'student', 'user')
--  entity_id    INT          : id of that record. NO foreign key on purpose:
--                              a deleted event must still be visible in the log.
--  details      VARCHAR      : short text such as the event name (never secrets)
--  ip_address   VARCHAR(45)  : IPv4 or IPv6 address of the request
--  created_at                : filled in by MySQL when the row is inserted
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NULL DEFAULT NULL,
    action      VARCHAR(50)     NOT NULL,
    entity_type VARCHAR(30)     NULL DEFAULT NULL,
    entity_id   INT UNSIGNED    NULL DEFAULT NULL,
    details     VARCHAR(255)    NULL DEFAULT NULL,
    ip_address  VARCHAR(45)     NULL DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_action (action),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- No sample audit rows are inserted: every row must come from a real admin action.

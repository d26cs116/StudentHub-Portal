-- =====================================================================
--  Practical 13 - Fetch API + PHP JSON API (CRUD)
--  Database : user_registration_db  (the SAME database as Practical 9-12)
--  New table: notices
--
--  HOW TO USE: phpMyAdmin (http://localhost/phpmyadmin) -> Import tab
--              -> choose this file -> Import.
--  SAFE TO IMPORT MORE THAN ONCE: it only creates the table if it is
--  missing. It never drops or changes users, students, events or any
--  existing record.
-- =====================================================================

USE user_registration_db;

-- ---------------------------------------------------------------------
--  TABLE: notices   (campus notices managed through the JSON API)
--  id          PRIMARY KEY : internal number, used by PUT / DELETE ?id=
--  title       UNIQUE      : two notices cannot have the same title
--                            (the API answers 409 Conflict if you try)
--  category    ENUM        : Academic, Exam, Event, Holiday or General
--  message     TEXT        : the notice text
--  created_at / updated_at : filled in automatically by MySQL
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notices (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title      VARCHAR(100) NOT NULL,
    category   ENUM('Academic','Exam','Event','Holiday','General') NOT NULL DEFAULT 'General',
    message    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_notices_title (title),
    KEY idx_notices_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  SAMPLE DATA (clearly identified test rows so the first list is not
--  empty). INSERT IGNORE skips a row whose title already exists.
--  You may delete these rows from the web page or phpMyAdmin at any time.
-- ---------------------------------------------------------------------
INSERT IGNORE INTO notices (title, category, message) VALUES
('Internal Exam Schedule',   'Exam',     'The internal examination schedule will be displayed on the notice board soon.'),
('Assignment Submission',    'Academic', 'Students must submit all pending assignments before the due date.'),
('Library Timings Updated',  'General',  'The library is now open from 8 AM to 8 PM on working days.'),
('Annual Tech Fest',         'Event',    'Registrations for the annual tech fest are open until the end of this month.');

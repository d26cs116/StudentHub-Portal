-- =====================================================================
--  Practical 8 - College Event Registration System
--  Database: college_event_db   (MySQL / MariaDB, storage engine InnoDB)
--
--  HOW TO USE: phpMyAdmin -> Import tab -> choose this file -> Import.
--  It creates the database, the 3 tables and the test data.
--  It is safe to import again: the three tables are dropped and rebuilt.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS college_event_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE college_event_db;

-- Drop the tables in this order: "registrations" points to the other two,
-- so it must be removed first.
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;

-- ---------------------------------------------------------------------
--  RELATIONSHIPS (3NF)
--    students 1 ----< registrations >---- 1 events
--    * One student can have many registrations.
--    * One event can have many registrations.
--    * Each registration belongs to exactly ONE student and ONE event.
--    * "registrations" is the junction table of the many-to-many
--      relationship between students and events.
--
--  3NF: every table has a primary key, and every other column depends on
--  that key only. Student details live only in "students", event details
--  only in "events"; "registrations" stores just the two foreign keys and
--  the date of the registration (no repeated names or venues).
-- ---------------------------------------------------------------------

-- ---------------------------------------------------------------------
--  TABLE 1: students
--  PRIMARY KEY student_id : identifies each student (auto-numbered).
--  UNIQUE email           : two students cannot share an email address.
-- ---------------------------------------------------------------------
CREATE TABLE students (
    student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL,
    mobile     CHAR(10)     NOT NULL,
    PRIMARY KEY (student_id),
    UNIQUE KEY uq_students_email (email),
    -- 10-digit Indian mobile number starting with 6, 7, 8 or 9
    CONSTRAINT chk_students_mobile CHECK (mobile REGEXP '^[6-9][0-9]{9}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  TABLE 2: events
--  PRIMARY KEY event_id : identifies each event (auto-numbered).
--  capacity must be greater than 0.
-- ---------------------------------------------------------------------
CREATE TABLE events (
    event_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_name VARCHAR(150) NOT NULL,
    event_date DATE         NOT NULL,
    venue      VARCHAR(100) NOT NULL,
    capacity   INT UNSIGNED NOT NULL,
    PRIMARY KEY (event_id),
    CONSTRAINT chk_events_capacity CHECK (capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  TABLE 3: registrations  (junction table)
--  PRIMARY KEY registration_id : identifies each registration.
--  FOREIGN KEY student_id -> students.student_id
--  FOREIGN KEY event_id   -> events.event_id
--    (a registration can only use a student and an event that exist;
--     ON DELETE CASCADE removes a registration when its student or event
--     is deleted.)
--  UNIQUE (student_id, event_id) : a student cannot register for the
--    same event twice.
-- ---------------------------------------------------------------------
CREATE TABLE registrations (
    registration_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id        INT UNSIGNED NOT NULL,
    event_id          INT UNSIGNED NOT NULL,
    registration_date DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_student_event (student_id, event_id),
    KEY idx_registrations_event (event_id),
    CONSTRAINT fk_registrations_student
        FOREIGN KEY (student_id) REFERENCES students (student_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (event_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  TEST DATA (fictional)
-- ---------------------------------------------------------------------
INSERT INTO students (name, email, mobile) VALUES
    ('Aarav Patel',  'aarav.patel@example.edu',  '9000000101'),
    ('Diya Shah',    'diya.shah@example.edu',    '9000000102'),
    ('Rohan Mehta',  'rohan.mehta@example.edu',  '9000000103'),
    ('Ishita Desai', 'ishita.desai@example.edu', '9000000104'),
    ('Kabir Joshi',  'kabir.joshi@example.edu',  '9000000105'),
    ('Meera Trivedi','meera.trivedi@example.edu','9000000106');

INSERT INTO events (event_name, event_date, venue, capacity) VALUES
    ('Code Sprint Hackathon',        '2026-11-07', 'Computer Lab 3',   40),
    ('Web Development Workshop',     '2026-11-12', 'Computer Lab 1',   30),
    ('Cyber Security Awareness Talk','2026-11-20', 'CSE Seminar Hall', 120),
    ('Rangotsav Cultural Fest',      '2026-12-05', 'Open Air Theatre', 300),
    ('Career Guidance Seminar',      '2026-12-10', 'Main Auditorium',  200),
    ('Annual Sports Day',            '2027-01-15', 'College Ground',   500);

-- Every registration uses a student_id and event_id that exist above.
INSERT INTO registrations (student_id, event_id, registration_date) VALUES
    (1, 1, '2026-10-20 10:15:00'),   -- Aarav  -> Code Sprint Hackathon
    (1, 2, '2026-10-21 11:30:00'),   -- Aarav  -> Web Development Workshop
    (2, 1, '2026-10-20 12:05:00'),   -- Diya   -> Code Sprint Hackathon
    (2, 4, '2026-10-25 09:45:00'),   -- Diya   -> Rangotsav Cultural Fest
    (3, 2, '2026-10-22 14:20:00'),   -- Rohan  -> Web Development Workshop
    (4, 3, '2026-10-23 16:10:00'),   -- Ishita -> Cyber Security Awareness Talk
    (4, 4, '2026-10-26 10:00:00'),   -- Ishita -> Rangotsav Cultural Fest
    (5, 5, '2026-10-27 13:40:00'),   -- Kabir  -> Career Guidance Seminar
    (5, 1, '2026-10-28 15:25:00'),   -- Kabir  -> Code Sprint Hackathon
    (6, 6, '2026-10-29 09:30:00');   -- Meera  -> Annual Sports Day

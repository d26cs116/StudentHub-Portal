-- =====================================================================
--  Practical 11 - Student Management System
--  Database: user_registration_db (the SAME database as Practical 9/10)
--  New table: students
--
--  HOW TO USE: phpMyAdmin -> Import tab -> choose this file -> Import.
--  SAFE TO IMPORT MORE THAN ONCE: it only creates what is missing, never
--  drops or changes the users table or any existing record.
-- =====================================================================

USE user_registration_db;

-- ---------------------------------------------------------------------
--  TABLE: students  (the login "users" table is for accounts; this one holds the people managed)
--  id          PRIMARY KEY  : internal number (used in links and forms)
--  student_id  UNIQUE       : enrollment number shown to people (e.g. D26CS101)
--  email       UNIQUE       : two students cannot share an email
--  course      VARCHAR      : course / programme name
--  year        TINYINT      : academic year 1-4
--  status      ENUM         : 'active' or 'inactive'.
--                             SOFT DELETE = status becomes 'inactive'; the row is NEVER deleted.
--  created_at / updated_at  : filled in automatically by MySQL
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
--  SAMPLE STUDENTS so the list is not empty for your first screenshot.
--  (INSERT IGNORE: rows that already exist are skipped. You may delete
--   these rows in phpMyAdmin whenever you like.)
-- ---------------------------------------------------------------------
INSERT IGNORE INTO students (student_id, name, email, phone, course, `year`, status) VALUES
('D26CS101', 'Aarav Patel',   'aarav.patel@example.edu',   '9876500001', 'Computer Engineering',         2, 'active'),
('D26CS102', 'Diya Shah',     'diya.shah@example.edu',     '9876500002', 'Computer Engineering',         2, 'active'),
('D26IT101', 'Kabir Mehta',   'kabir.mehta@example.edu',   '9876500003', 'Information Technology',       1, 'active'),
('D26EC101', 'Isha Desai',    'isha.desai@example.edu',    '9876500004', 'Electronics & Communication',  3, 'active'),
('D26ME101', 'Rohan Joshi',   'rohan.joshi@example.edu',   '9876500005', 'Mechanical Engineering',       4, 'active'),
('D26CE101', 'Meera Trivedi', 'meera.trivedi@example.edu', '9876500006', 'Civil Engineering',            1, 'active'),
('D26IT102', 'Neel Parmar',   'neel.parmar@example.edu',   '9876500007', 'Information Technology',       3, 'inactive');

-- =====================================================================
--  Practical 12 - Admin Event Management (CRUD + poster upload)
--  Database: user_registration_db (the SAME database as Practical 9-11)
--  New table: events
--
--  HOW TO USE: phpMyAdmin -> Import tab -> choose this file -> Import.
--  SAFE TO IMPORT MORE THAN ONCE: it only creates the table if it is missing.
--  It never touches the users or students tables or any existing record.
-- =====================================================================

USE user_registration_db;

-- ---------------------------------------------------------------------
--  TABLE: events
--  id          PRIMARY KEY : internal number used in links and forms
--  event_name  VARCHAR     : title of the event
--  description TEXT        : details
--  event_date  DATE        : day of the event
--  event_time  TIME NULL   : optional start time (NULL = not set)
--  venue       VARCHAR     : location
--  poster      VARCHAR NULL: ONLY the generated file NAME of the poster image
--                            (for example 3f2a...9c.jpg). The picture itself lives in the
--                            folder uploads/event_posters/, never inside MySQL. NULL = no poster.
--  created_at / updated_at : filled in automatically by MySQL
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

-- No sample events are inserted on purpose: create them in the website so that
-- their posters really exist in the uploads folder.

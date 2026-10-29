<?php
/* =========================================================
   practical13/db.php - the database connection for Practical 13.

   It uses the SAME database and the SAME settings as the main
   project db.php (user_registration_db, root, empty password),
   so no second database is needed.

   Why a separate file? The main db.php stops with a plain-text
   message when MySQL is down. A JSON API must ALWAYS answer in
   JSON, so this file only offers db_connect(); api.php decides
   what to send to the browser if the connection fails.

   Practical 13 uses PDO (like Practical 9). Every query in
   api.php is a prepared statement.
   ========================================================= */

// Never show PHP warnings or errors to visitors; write them to the server log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---- Database settings for a normal local XAMPP ----
// Change these if your XAMPP is different (another MySQL port or a root password).
const DB_HOST = '127.0.0.1';             // this computer
const DB_PORT = 3306;                    // XAMPP MySQL port
const DB_NAME = 'user_registration_db';  // same database as Practical 9-12
const DB_USER = 'root';                  // default XAMPP user
const DB_PASS = '';                      // default XAMPP password is empty

// Returns a ready PDO connection or throws PDOException.
// The exception message may contain technical details, so api.php only logs it.
function db_connect(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // problems become exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
        PDO::ATTR_TIMEOUT            => 5,
    ]);
}

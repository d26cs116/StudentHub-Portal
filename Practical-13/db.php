<?php
/* =========================================================
   db.php - the ONLY place that holds the database settings.
   Every other file just does:  require_once 'db.php';
   and then uses the $conn object (a MySQLi connection).

   Practical 10 uses MySQLi (Practical 9 used PDO). The settings
   below are unchanged, so the same database is reused.
   ========================================================= */

// Never show PHP warnings or errors to visitors; write them to the server log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Make MySQLi THROW exceptions (mysqli_sql_exception) instead of printing warnings,
// so try/catch can handle every database problem in one safe way.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---- Database settings for a normal local XAMPP ----
// Change these if your XAMPP is different (for example another MySQL port or a root password).
$dbHost = "127.0.0.1";             // this computer
$dbPort = 3306;                    // XAMPP MySQL port (see the XAMPP window if you changed it)
$dbName = "user_registration_db";  // the database created by user_registration.sql
$dbUser = "root";                  // default XAMPP user
$dbPass = "";                      // default XAMPP password is empty

try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    $conn->set_charset('utf8mb4');  // full Unicode support (names like "Zoë", emoji, etc.)
} catch (mysqli_sql_exception $e) {
    // The technical reason goes to the log only, never to the visitor
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to connect to the database. Please check the configuration.');
}

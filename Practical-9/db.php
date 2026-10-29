<?php
/* =========================================================
   db.php - the ONLY place that holds the database settings.
   Every other file just does:  require_once 'db.php';
   and then uses the $pdo object.
   ========================================================= */

// Never show PHP warnings or errors to visitors; write them to the server log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---- Database settings for a normal local XAMPP ----
// Change these if your XAMPP is different (for example another MySQL port or a root password).
$host     = "127.0.0.1";             // this computer
$port     = "3306";                  // XAMPP MySQL port (see the XAMPP window if you changed it)
$dbname   = "user_registration_db";  // the database created by schema.sql
$username = "root";                  // default XAMPP user
$password = "";                      // default XAMPP password is empty

try {
    // DSN = "Data Source Name": the driver (mysql), server, database and character set
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Throw an exception when something goes wrong (so try/catch can handle it)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Rows come back as associative arrays: $row['username']
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Real prepared statements: the SQL and the values are sent to MySQL separately
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // The technical reason goes to the log only, never to the visitor
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to connect to the database. Please check the configuration.");
}

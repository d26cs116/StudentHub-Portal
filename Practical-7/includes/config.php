<?php
/* =========================================================
   includes/config.php
   Settings and small helper functions shared by
   contact.php (shows the form) and process.php (handles it).
   ========================================================= */

// Never show PHP warnings, notices or file paths to visitors.
// Problems are written to the server's error log instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// A session lets us keep error messages, old values and the CSRF token
// while the browser jumps from process.php back to contact.php.
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// The CSV file where valid submissions are saved (the storage folder is blocked by .htaccess)
define('STORAGE_FILE', __DIR__ . '/../storage/submissions.csv');

// Message length limits
define('MIN_MESSAGE_LENGTH', 10);
define('MAX_MESSAGE_LENGTH', 500);

// The only values the "Subject / Course" dropdown may send
$allowedSubjects = [
    'Data Structures',
    'Object Oriented Programming',
    'Computer Networks',
    'Web Development',
    'Discrete Mathematics',
    'General Enquiry',
];

// OUTPUT ESCAPING: turns characters like < > " ' & into harmless text,
// so user input is shown on the page instead of being run as HTML/JavaScript.
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Send the browser to another page and stop this script
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// Counts characters (not bytes), so a name like "Zoë" has length 3.
// Uses a regular expression, so it works even if the mbstring extension is switched off.
function text_length(string $value): int
{
    return (int) preg_match_all('/./su', $value);
}

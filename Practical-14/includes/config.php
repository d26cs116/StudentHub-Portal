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
// Practical 10: stricter session cookie.
//  - httponly : JavaScript cannot read the session cookie (limits XSS damage)
//  - samesite : the cookie is not sent on cross-site POST requests (limits CSRF)
//  - secure   : only turned on when the site is opened over HTTPS, so plain
//               http://localhost testing in XAMPP keeps working.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
ini_set('session.use_strict_mode', '1');     // refuse session IDs the server did not create
ini_set('session.use_only_cookies', '1');    // never accept a session ID from the URL
session_set_cookie_params([
    'lifetime' => 0,                         // cookie ends when the browser is closed
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// The JSON file where valid submissions are saved (the storage folder is blocked by .htaccess)
define('STORAGE_FILE', __DIR__ . '/../storage/submissions.json');

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

// Completely end the current session: empty the data, expire the cookie, destroy the server file.
// Used by logout.php and by the session-timeout check (timeout.php).
function destroy_session_completely(): void
{
    $_SESSION = [];                                          // 1. clear the data
    if (ini_get('session.use_cookies')) {                    // 2. expire the browser cookie
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();                                   // 3. delete the server-side session
    }
}

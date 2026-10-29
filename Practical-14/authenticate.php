<?php
/* =========================================================
   authenticate.php - receives the login form (POST), checks the
   credentials with MySQLi and, if they are correct, creates the
   logged-in session. It prints nothing: it always redirects.
   ========================================================= */
require_once __DIR__ . '/includes/config.php';   // session + e() + redirect()
require_once __DIR__ . '/includes/auth.php';     // ALLOWED_ROLES, role_home()   (also loads db.php)

/* ---------- 1. Accept POST only ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

// Helper: go back to the login page with one error message (and the email, never the password)
function login_fail(string $message, string $email = ''): void
{
    $_SESSION['login_error']     = $message;
    $_SESSION['login_old_email'] = $email;
    redirect('login.php');
}

/* ---------- 2. Read the input (arrays are treated as empty) ---------- */
$email    = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$token    = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
// NOTE: a "role" sent by the form or the URL is never read. The role only ever comes from the database.

/* ---------- 3. CSRF check ---------- */
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    login_fail('Your session expired. Please try logging in again.');
}

/* ---------- 4. Validate ---------- */
if ($email === '' || $password === '') {
    login_fail('Please enter both your email and password.', $email);
}
if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    login_fail('Please enter a valid email address.', $email);
}

/* ---------- 5. Find the user (prepared statement) ---------- */
$user = null;
try {
    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);                 // the email is a bound value, never glued into the SQL
    $stmt->execute();
    $stmt->bind_result($id, $fullName, $dbEmail, $hash, $role);
    if ($stmt->fetch()) {
        $user = ['id' => (int) $id, 'name' => $fullName, 'email' => $dbEmail, 'hash' => $hash, 'role' => $role];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Login: lookup failed (' . $ex->getCode() . ')');   // details only in the log
    login_fail('Sorry, we could not log you in right now. Please try again later.', $email);
}

/* ---------- 6. Verify the password ---------- */
// If the email does not exist we still run password_verify() against a dummy hash, so a
// wrong email and a wrong password take about the same time (hides which emails exist).
$dummyHash = '$2y$10$xKa9feAYynxBK7aGCDKpX.7nNdQK69twSYT2vuHkAZT6js2OsScBG';
$passwordOk = password_verify($password, $user['hash'] ?? $dummyHash);
unset($password);

if ($user === null || !$passwordOk) {
    // One generic message for both cases. No authenticated session is created.
    login_fail('Invalid email or password.', $email);
}

/* ---------- 7. Reject unsupported roles ---------- */
if (!in_array($user['role'], ALLOWED_ROLES, true)) {
    error_log('Login: user ' . $user['id'] . ' has an unsupported role');
    login_fail('Your account cannot be used to log in. Please contact the administrator.', $email);
}

/* ---------- 8. SUCCESS: create the authenticated session ---------- */
// A NEW session ID is issued the moment the user logs in (true = delete the old session file).
// This blocks "session fixation": an attacker who knew the old ID gets nothing.
session_regenerate_id(true);

$_SESSION['user_id']       = $user['id'];
$_SESSION['user_name']     = $user['name'];
$_SESSION['user_email']    = $user['email'];
$_SESSION['role']          = $user['role'];       // from the database only
$_SESSION['last_activity'] = time();              // starts the inactivity timer (see timeout.php)
$_SESSION['csrf_token']    = bin2hex(random_bytes(32));   // fresh token for the logout form
unset($_SESSION['login_error'], $_SESSION['login_old_email']);

// Practical 14: record a successful ADMINISTRATOR login in the audit log (a logging problem never blocks the login).
if ($user['role'] === 'Admin') {
    audit_log_safe($conn, 'ADMIN_LOGIN', 'user', $user['id'], 'Administrator logged in');
}

/* ---------- 9. Redirect by the STORED role ---------- */
redirect(role_home($user['role']));

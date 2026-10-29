<?php
/* =========================================================
   process_registration.php - receives the registration form (POST),
   validates it, checks for duplicates, hashes the password and
   inserts the new user into MySQL. It prints nothing: the visitor
   is always sent back to register.php (Post/Redirect/Get pattern).
   ========================================================= */
require __DIR__ . '/includes/config.php';   // starts the session and gives us e(), redirect(), text_length()
require_once __DIR__ . '/db.php';           // gives us the $conn MySQLi connection

/* ---------- 1. Accept POST only ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
}

/* ---------- Helper: read one POST value as text ---------- */
// If someone sends an array (username[]=x) instead of text we treat it as empty.
function posted(string $key, bool $trim = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return $trim ? trim($value) : $value;    // trim() removes spaces at the start and end
}

/* ---------- 2. Read the submitted values ---------- */
$old = [                                      // the values we are allowed to show again
    'full_name' => posted('full_name'),
    'username'  => posted('username'),
    'email'     => posted('email'),
];
$password = posted('password', false);        // passwords are NOT trimmed and are NEVER shown again
$confirm  = posted('confirm_password', false);

$errors = [];                                 // field name => error message

/* ---------- 3. CSRF check ---------- */
// The hidden token proves the form came from OUR page in THIS browser session.
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], posted('csrf_token'))) {
    $errors['general'] = 'Your session has expired. Please check the form and submit it again.';
}

/* ---------- 4. SERVER-SIDE VALIDATION ---------- */
// Full name: required, letters / spaces / apostrophes / hyphens, 2-50 characters
if ($old['full_name'] === '') {
    $errors['full_name'] = 'Full name is required.';
} elseif (!preg_match("/^[\p{L}][\p{L} '\-]*$/u", $old['full_name'])) {
    $errors['full_name'] = "Name can contain only letters, spaces, apostrophes (') and hyphens (-).";
} elseif (text_length($old['full_name']) < 2 || text_length($old['full_name']) > 50) {
    $errors['full_name'] = 'Name must be between 2 and 50 characters.';
}

// Username: required, 3-20 letters, digits or underscores
if ($old['username'] === '') {
    $errors['username'] = 'Username is required.';
} elseif (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $old['username'])) {
    $errors['username'] = 'Username must be 3 to 20 characters: letters, digits and underscore (_) only.';
}

// Email: required and a valid format (PHP's built-in validator)
if ($old['email'] === '') {
    $errors['email'] = 'Email address is required.';
} elseif (strlen($old['email']) > 100 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address, for example name@example.com.';
}

// Password: required, at least 8 characters (72 is the most bcrypt can use)
if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long.';
} elseif (strlen($password) > 72) {
    $errors['password'] = 'Password must be at most 72 characters long.';
}

// Confirm password: required and EXACTLY the same as the password (strict comparison !==)
if ($confirm === '') {
    $errors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

/* ---------- 5. DUPLICATE USERNAME / EMAIL CHECK ---------- */
// Only checked when the field itself is valid. Prepared statement: the value is a bound
// parameter (:username), it is never glued into the SQL text.
try {
    if (!isset($errors['username'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $old['username']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['username'] = 'Username already exists.';
        }
        $stmt->close();
    }
    if (!isset($errors['email'])) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $old['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email address is already registered.';
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $ex) {
    error_log('Registration: duplicate check failed (' . $ex->getCode() . ')');
    $errors['general'] = 'Sorry, we could not complete your registration right now. Please try again later.';
}

/* ---------- If anything is wrong: do NOT insert, go back to the form ---------- */
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $old;          // name, username and email only - never the passwords
    redirect('register.php');
}

/* ---------- 6. HASH THE PASSWORD ---------- */
// Why? If the database is ever leaked or read by someone, plain passwords would let them log in
// as every user. password_hash() turns the password into a one-way hash with a random salt,
// so the real password cannot be read back from the database. Never use MD5 or SHA1 for this.
$passwordHash = password_hash($password, PASSWORD_DEFAULT);
unset($password, $confirm);                   // we no longer need the real passwords

/* ---------- 7. INSERT THE NEW USER ---------- */
// The role is NOT taken from the form: the database default ('Student') is used,
// so nobody can register themselves as an Admin.
try {
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $old['full_name'], $old['username'], $old['email'], $passwordHash);  // only the HASH is stored
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Insert did not add exactly one row.');
    }
    $stmt->close();
} catch (mysqli_sql_exception | RuntimeException $ex) {
    // 1062 = "duplicate entry": the UNIQUE keys in MySQL caught a duplicate that slipped in
    // between our check and the insert (two people registering at the same moment).
    $driverCode = ($ex instanceof mysqli_sql_exception) ? (int) $ex->getCode() : 0;
    if ($driverCode === 1062) {
        if (stripos($ex->getMessage(), 'username') !== false) {
            $errors['username'] = 'Username already exists.';
        } elseif (stripos($ex->getMessage(), 'email') !== false) {
            $errors['email'] = 'Email address is already registered.';
        } else {
            $errors['general'] = 'That username or email address is already registered.';
        }
    } else {
        error_log('Registration: insert failed (' . $ex->getCode() . ')');   // never log passwords
        $errors['general'] = 'Sorry, we could not create your account right now. Please try again later.';
    }
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $old;
    redirect('register.php');
}

/* ---------- 8. Success: Post/Redirect/Get ---------- */
// Redirecting means a browser refresh only reloads register.php (a GET request),
// so the same account can never be inserted twice.
$_SESSION['register_success'] = true;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));   // the old token can no longer be reused
redirect('register.php');

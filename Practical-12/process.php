<?php
/* =========================================================
   process.php – receives the form (POST), validates it,
   saves it to storage/submissions.json and sends the visitor
   back to contact.php (Post/Redirect/Get pattern).
   Nothing is printed here; the visitor only ever sees contact.php.
   ========================================================= */
require __DIR__ . '/includes/config.php';

/* ---------- 1. Accept POST only ---------- */
// If someone opens process.php normally (GET), nothing is processed or stored.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.php');
}

/* ---------- Helper: read one POST value as trimmed text ---------- */
// trim() removes spaces at the start and end.
// If a hacker sends an array (name[]=x) instead of text we treat it as empty.
function posted(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/* ---------- 2. Read the submitted values ($_POST) ---------- */
$old = [
    'name'    => posted('name'),
    'email'   => posted('email'),
    'mobile'  => posted('mobile'),
    'subject' => posted('subject'),
    'message' => str_replace("\r\n", "\n", posted('message')),
];

$errors = [];   // field name => error message

/* ---------- 3. CSRF check ---------- */
// The hidden token proves the form came from OUR page in THIS browser session,
// not from another website that tricks the visitor into submitting it.
$sentToken = posted('csrf_token');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
    $errors['general'] = 'Your session has expired. Please check the form and submit it again.';
}

/* ---------- 4. SERVER-SIDE VALIDATION ---------- */
// Full name: letters (any language), spaces, apostrophes and hyphens, 2-50 characters
if ($old['name'] === '') {
    $errors['name'] = 'Full name is required.';
} elseif (!preg_match("/^[\p{L}][\p{L} '\-]*$/u", $old['name'])) {
    $errors['name'] = "Name can contain only letters, spaces, apostrophes (') and hyphens (-).";
} elseif (text_length($old['name']) < 2 || text_length($old['name']) > 50) {
    $errors['name'] = 'Name must be between 2 and 50 characters.';
}

// Email: PHP's built-in email validator
if ($old['email'] === '') {
    $errors['email'] = 'Email address is required.';
} elseif (strlen($old['email']) > 100 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address, for example name@example.com.';
}

// Mobile: exactly 10 digits, first digit 6-9 (Indian mobile number)
if ($old['mobile'] === '') {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!preg_match('/^[6-9][0-9]{9}$/', $old['mobile'])) {
    $errors['mobile'] = 'Enter a valid 10-digit mobile number starting with 6, 7, 8 or 9 (digits only).';
}

// Subject / Course: must be one of the values we offered in the dropdown
if ($old['subject'] === '') {
    $errors['subject'] = 'Please choose a subject or course.';
} elseif (!in_array($old['subject'], $allowedSubjects, true)) {
    $errors['subject'] = 'That subject is not allowed. Please choose one from the list.';
}

// Message: not empty (trim() already removed whitespace-only text), sensible length, no control characters
$messageLength = text_length($old['message']);
if ($old['message'] === '') {
    $errors['message'] = 'Message is required.';
} elseif (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $old['message'])) {
    $errors['message'] = 'Message contains characters that are not allowed.';
} elseif ($messageLength < MIN_MESSAGE_LENGTH) {
    $errors['message'] = 'Message must be at least ' . MIN_MESSAGE_LENGTH . ' characters long.';
} elseif ($messageLength > MAX_MESSAGE_LENGTH) {
    $errors['message'] = 'Message must be at most ' . MAX_MESSAGE_LENGTH . ' characters long (you typed ' . $messageLength . ').';
}

/* ---------- If anything is wrong: do NOT store, go back to the form ---------- */
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $old;       // so the visitor does not have to type everything again
    redirect('contact.php');
}

/* ---------- 5. Save to the JSON file ---------- */
// The file holds ONE JSON array. Every submission is one object inside that array, like:
// [ { "id": 1, "submitted_at": "...", "name": "...", ... }, { "id": 2, ... } ]
// Adds ONE record to the array. Returns true on success, false on failure.
function save_submission(string $file, array $record): bool
{
    $folder = dirname($file);
    if (!is_dir($folder) && !mkdir($folder, 0750, true)) {
        return false;
    }

    $isNewFile = !file_exists($file);
    $handle = fopen($file, 'c+');                // c+ = read and write, create the file if missing, keep old content
    if ($handle === false) {
        return false;
    }

    $saved = false;
    if (flock($handle, LOCK_EX)) {               // lock so two visitors cannot write at the same moment
        $text = stream_get_contents($handle);    // 1. read everything already in the file

        if (trim($text) === '') {
            $records = [];                       // new or empty file: start with an empty list
        } else {
            $records = json_decode($text, true); // JSON text -> PHP array
        }

        // If the file contains broken JSON we stop and keep it untouched (nothing is lost)
        if (is_array($records)) {
            $record = ['id' => count($records) + 1] + $record;   // 2. give the new record the next id
            $records[] = $record;                // 3. add the new record at the end of the list

            $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false) {
                rewind($handle);                 // 4. go back to the start of the file...
                ftruncate($handle, 0);           //    ...clear it...
                $saved = fwrite($handle, $json . "\n") !== false;   // ...and write the full list again
                fflush($handle);
            }
        }
        flock($handle, LOCK_UN);
    }
    fclose($handle);

    if ($isNewFile) {
        @chmod($file, 0644);                     // owner can write, others can only read
    }
    return $saved;
}

// The new record. JSON can store line breaks and any text safely, so the message is kept as typed.
$record = [
    'submitted_at' => date('Y-m-d H:i:s'),
    'name'         => $old['name'],
    'email'        => $old['email'],
    'mobile'       => $old['mobile'],
    'subject'      => $old['subject'],
    'message'      => $old['message'],
];

if (!save_submission(STORAGE_FILE, $record)) {
    // The visitor gets a friendly message; the technical reason goes to the server log only
    error_log('Student Hub contact form: could not write to the submissions file.');
    $_SESSION['form_errors'] = ['general' => 'Sorry, we could not save your message right now. Please try again later.'];
    $_SESSION['form_old']    = $old;
    redirect('contact.php');
}

/* ---------- 6. Success: Post/Redirect/Get ---------- */
// Redirecting means a browser refresh only reloads contact.php (a GET request),
// so the same submission can never be stored twice.
$_SESSION['form_success'] = true;
$_SESSION['csrf_token']   = bin2hex(random_bytes(32));   // old token can no longer be reused
redirect('contact.php');

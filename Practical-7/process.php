<?php
require __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.php');
}

function posted(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}


$old = [
    'name'    => posted('name'),
    'email'   => posted('email'),
    'mobile'  => posted('mobile'),
    'subject' => posted('subject'),
    'message' => str_replace("\r\n", "\n", posted('message')),
];

$errors = [];   
$sentToken = posted('csrf_token');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
    $errors['general'] = 'Your session has expired. Please check the form and submit it again.';
}


if ($old['name'] === '') {
    $errors['name'] = 'Full name is required.';
} elseif (!preg_match("/^[\p{L}][\p{L} '\-]*$/u", $old['name'])) {
    $errors['name'] = "Name can contain only letters, spaces, apostrophes (') and hyphens (-).";
} elseif (text_length($old['name']) < 2 || text_length($old['name']) > 50) {
    $errors['name'] = 'Name must be between 2 and 50 characters.';
}

if ($old['email'] === '') {
    $errors['email'] = 'Email address is required.';
} elseif (strlen($old['email']) > 100 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address, for example name@example.com.';
}

if ($old['mobile'] === '') {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!preg_match('/^[6-9][0-9]{9}$/', $old['mobile'])) {
    $errors['mobile'] = 'Enter a valid 10-digit mobile number starting with 6, 7, 8 or 9 (digits only).';
}

if ($old['subject'] === '') {
    $errors['subject'] = 'Please choose a subject or course.';
} elseif (!in_array($old['subject'], $allowedSubjects, true)) {
    $errors['subject'] = 'That subject is not allowed. Please choose one from the list.';
}


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


if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $old;       // so the visitor does not have to type everything again
    redirect('contact.php');
}


function csv_safe(string $value): string
{
    return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
}

function save_submission(string $file, array $row): bool
{
    $folder = dirname($file);
    if (!is_dir($folder) && !mkdir($folder, 0750, true)) {
        return false;
    }

    $isNewFile = !file_exists($file);
    $handle = fopen($file, 'a');                 
    if ($handle === false) {
        return false;
    }

    $saved = false;
    if (flock($handle, LOCK_EX)) {               // lock so two visitors cannot write at the same moment
        $fields = ['submitted_at', 'name', 'email', 'mobile', 'subject', 'message'];
        if (fstat($handle)['size'] === 0) {      // empty file: write the header row first
            fputcsv($handle, $fields, ',', '"', '\\');
        }
        $saved = fputcsv($handle, $row, ',', '"', '\\') !== false;   // write the new record
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);

    if ($isNewFile) {
        @chmod($file, 0644);                    
    }
    return $saved;
}

$row = [
    date('Y-m-d H:i:s'),
    csv_safe($old['name']),
    csv_safe($old['email']),
    csv_safe($old['mobile']),
    csv_safe($old['subject']),
    csv_safe(preg_replace('/\s+/', ' ', $old['message'])),
];

if (!save_submission(STORAGE_FILE, $row)) {
    error_log('Student Hub contact form: could not write to the submissions file.');
    $_SESSION['form_errors'] = ['general' => 'Sorry, we could not save your message right now. Please try again later.'];
    $_SESSION['form_old']    = $old;
    redirect('contact.php');
}

$_SESSION['form_success'] = true;
$_SESSION['csrf_token']   = bin2hex(random_bytes(32));   // old token can no longer be reused
redirect('contact.php');

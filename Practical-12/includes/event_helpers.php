<?php
/* =========================================================
   includes/event_helpers.php - helpers for Practical 12 (events + poster upload).
   Flash messages and CSRF helpers are reused from student_helpers.php.
   ========================================================= */
require_once __DIR__ . '/student_helpers.php';     // flash_set(), flash_render(), csrf_field(), csrf_is_valid(), clean_id()

// ---- Poster rules (change the limit here) ----
const POSTER_MAX_BYTES = 2 * 1024 * 1024;           // 2 MB
const POSTER_MAX_LABEL = '2 MB';
const POSTER_PIXEL_LIMIT = 40000000;                // refuse absurdly large pictures (width x height)
const POSTER_WEB_PATH  = 'uploads/event_posters/';  // relative URL used in <img src="...">
// Allowed real content types => the extension WE choose for the saved file
const POSTER_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

const MSG_POSTER_FORMAT = 'Invalid poster format. Please upload a JPEG, PNG, or WebP image.';
const MSG_POSTER_SIZE   = 'Poster file is too large. Maximum allowed size is ' . POSTER_MAX_LABEL . '.';
const MSG_POSTER_FAIL   = 'Unable to upload poster.';

function poster_dir(): string
{
    return dirname(__DIR__) . '/uploads/event_posters';
}

// A poster name is "managed" only if WE generated it: 32 hex characters + .jpg/.png/.webp.
// Anything else in the database is never shown as an image and never deleted from disk.
function is_managed_poster($name): bool
{
    return is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) === 1;
}

/* ---------- Text fields ---------- */
function read_event_input(): array
{
    return [
        'event_name'  => preg_replace('/\s+/', ' ', posted_text('event_name')),
        'description' => posted_text('description'),
        'event_date'  => posted_text('event_date'),
        'event_time'  => posted_text('event_time'),
        'venue'       => preg_replace('/\s+/', ' ', posted_text('venue')),
    ];
}

function text_len(string $s): int
{
    return (int) preg_match_all('/./su', $s);       // characters, not bytes
}

function validate_event(array $in): array
{
    $err = [];
    if ($in['event_name'] === '') {
        $err['event_name'] = 'Event name is required.';
    } elseif (text_len($in['event_name']) < 3 || text_len($in['event_name']) > 150) {
        $err['event_name'] = 'Event name must be 3 to 150 characters.';
    }

    if ($in['description'] === '') {
        $err['description'] = 'Description is required.';
    } elseif (text_len($in['description']) < 10 || text_len($in['description']) > 2000) {
        $err['description'] = 'Description must be 10 to 2000 characters.';
    }

    if ($in['event_date'] === '') {
        $err['event_date'] = 'Event date is required.';
    } else {
        $d = DateTime::createFromFormat('!Y-m-d', $in['event_date']);
        $ok = $d && $d->format('Y-m-d') === $in['event_date'] && (int) $d->format('Y') >= 2000 && (int) $d->format('Y') <= 2100;
        if (!$ok) {
            $err['event_date'] = 'Enter a valid date.';
        }
    }

    if ($in['event_time'] !== '') {                 // the time is optional
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $in['event_time'])) {
            $err['event_time'] = 'Enter a valid time (HH:MM).';
        }
    }

    if ($in['venue'] === '') {
        $err['venue'] = 'Venue is required.';
    } elseif (text_len($in['venue']) < 2 || text_len($in['venue']) > 150) {
        $err['venue'] = 'Venue must be 2 to 150 characters.';
    }
    return $err;
}

// Message at the top of a failed form
function event_error_banner(array $errors, array $in): string
{
    foreach (['event_name', 'description', 'event_date', 'venue'] as $f) {
        if ($in[$f] === '' && isset($errors[$f])) {
            return 'Please fill in all required fields.';
        }
    }
    if (count($errors) === 1 && isset($errors['poster'])) {
        return $errors['poster'];                   // only the poster is wrong: show its message directly
    }
    return 'Please correct the highlighted fields.';
}

/* ---------- Poster upload: validation ---------- */

// True when the whole request was larger than post_max_size: PHP then drops $_POST and $_FILES completely.
function request_exceeds_post_max(): bool
{
    $len = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    return $len > 0 && empty($_POST) && empty($_FILES);
}

// Looks at $_FILES['poster'] and returns one of:
//   ['status' => 'none']                                  no file chosen (allowed)
//   ['status' => 'error', 'message' => '...']             rejected - nothing is saved
//   ['status' => 'ok', 'tmp' => path, 'ext' => 'jpg']     valid, ready to be moved
function validate_poster_upload(): array
{
    if (!isset($_FILES['poster']) || !is_array($_FILES['poster']) || !isset($_FILES['poster']['error']) || is_array($_FILES['poster']['error'])) {
        return ['status' => 'none'];
    }
    $f = $_FILES['poster'];

    // 1. PHP's own upload error code
    switch ($f['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['status' => 'none'];
        case UPLOAD_ERR_INI_SIZE:                   // bigger than upload_max_filesize in php.ini
        case UPLOAD_ERR_FORM_SIZE:
            return ['status' => 'error', 'message' => MSG_POSTER_SIZE];
        default:                                    // partial upload, no temp folder, cannot write, extension stopped it
            error_log('Poster upload error code ' . (int) $f['error']);
            return ['status' => 'error', 'message' => MSG_POSTER_FAIL];
    }

    $tmp = (string) ($f['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {   // must be a file PHP received through an HTTP upload
        return ['status' => 'error', 'message' => MSG_POSTER_FAIL];
    }

    // 2. Size limit (our own limit, checked on the server)
    $size = (int) @filesize($tmp);
    if ($size > POSTER_MAX_BYTES || (int) ($f['size'] ?? 0) > POSTER_MAX_BYTES) {
        return ['status' => 'error', 'message' => MSG_POSTER_SIZE];
    }
    if ($size <= 0) {
        return ['status' => 'error', 'message' => MSG_POSTER_FORMAT];
    }

    // 3. Extension of the original name (first check only: it is never trusted alone)
    $extFromName = strtolower(pathinfo((string) ($f['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extFromName, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['status' => 'error', 'message' => MSG_POSTER_FORMAT];
    }

    // 4. REAL content type, read from the file's own bytes (Fileinfo). $_FILES['poster']['type'] is ignored.
    if (!class_exists('finfo')) {
        error_log('Poster upload: the PHP fileinfo extension is not enabled');
        return ['status' => 'error', 'message' => MSG_POSTER_FAIL];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!is_string($mime) || !isset(POSTER_TYPES[$mime])) {
        return ['status' => 'error', 'message' => MSG_POSTER_FORMAT];
    }
    $ext = POSTER_TYPES[$mime];
    $nameExtOk = ($ext === 'jpg') ? in_array($extFromName, ['jpg', 'jpeg'], true) : ($extFromName === $ext);
    if (!$nameExtOk) {                              // e.g. a PNG renamed to .jpg
        return ['status' => 'error', 'message' => MSG_POSTER_FORMAT];
    }

    // 5. Must really decode as an image of that type (blocks scripts hidden behind an image header)
    $info = @getimagesize($tmp);
    if ($info === false || ($info['mime'] ?? '') !== $mime || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > POSTER_PIXEL_LIMIT) {
        return ['status' => 'error', 'message' => MSG_POSTER_FORMAT];
    }

    return ['status' => 'ok', 'tmp' => $tmp, 'ext' => $ext];
}

/* ---------- Poster upload: saving and deleting files ---------- */

// Moves a validated upload into uploads/event_posters/ under a NEW random name.
// Returns the stored file name, or null if it could not be saved.
function save_poster(array $upload): ?string
{
    $dir = poster_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('Poster upload: cannot create the uploads folder');
        return null;
    }
    if (!is_writable($dir)) {
        error_log('Poster upload: the uploads folder is not writable');
        return null;
    }
    $name = bin2hex(random_bytes(16)) . '.' . $upload['ext'];     // the visitor's file name is never used
    if (!move_uploaded_file($upload['tmp'], $dir . '/' . $name)) {
        error_log('Poster upload: move_uploaded_file failed');
        return null;
    }
    @chmod($dir . '/' . $name, 0644);               // readable, not executable
    return $name;
}

// Deletes a poster file, but only if it is one of OUR generated names inside the poster folder.
function delete_poster_file($name): void
{
    if (!is_managed_poster($name)) {
        return;
    }
    $path = poster_dir() . '/' . $name;
    $real = realpath($path);
    $base = realpath(poster_dir());
    if ($real !== false && $base !== false && strpos($real, $base . DIRECTORY_SEPARATOR) === 0 && is_file($real)) {
        @unlink($real);
    }
}

/* ---------- Database helper ---------- */
function find_event($conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT id, event_name, description, event_date, event_time, venue, poster FROM events WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($rid, $name, $desc, $date, $time, $venue, $poster);
    $row = null;
    if ($stmt->fetch()) {
        $row = ['id' => $rid, 'event_name' => $name, 'description' => $desc, 'event_date' => $date,
                'event_time' => $time === null ? '' : substr((string) $time, 0, 5), 'venue' => $venue, 'poster' => $poster];
    }
    $stmt->close();
    return $row;
}

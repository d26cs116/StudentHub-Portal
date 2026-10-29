<?php
/* =========================================================
   includes/student_helpers.php - small helpers shared by the
   Practical 11 pages (students, add, edit, deactivate, export).
   ========================================================= */

// Courses offered (used by the Add / Edit dropdowns and for validation).
const STUDENT_COURSES = [
    'Computer Engineering',
    'Information Technology',
    'Electronics & Communication',
    'Mechanical Engineering',
    'Civil Engineering',
];
const STUDENT_YEARS    = [1, 2, 3, 4];
const STUDENT_STATUSES = ['active', 'inactive'];

/* ---------- Flash messages (shown once, after a redirect) ---------- */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];   // type: success | error | info
}

// Prints the pending message (if any) in the site's existing .form-status style, then forgets it.
function flash_render(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $type = in_array($f['type'], ['success', 'error', 'info'], true) ? $f['type'] : 'info';
    $icon = ['success' => '✅', 'error' => '❌', 'info' => 'ℹ️'][$type];
    echo '<div class="form-status ' . e($type) . '" role="' . ($type === 'error' ? 'alert' : 'status') . '">'
        . $icon . ' ' . e((string) $f['message']) . '</div>';
}

/* ---------- CSRF (same token as the logout form; created at login) ---------- */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

function csrf_is_valid(): bool
{
    $sent = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/* ---------- Reading + validating the student form ---------- */
function posted_text(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';        // arrays are treated as empty
}

// Reads the form fields from $_POST (cleaned, not yet validated).
function read_student_input(): array
{
    $phone = preg_replace('/[\s\-().]/', '', posted_text('phone'));   // "98765 00001" -> "9876500001"
    return [
        'student_id' => strtoupper(posted_text('student_id')),
        'name'       => preg_replace('/\s+/', ' ', posted_text('name')),
        'email'      => posted_text('email'),
        'phone'      => (string) $phone,
        'course'     => posted_text('course'),
        'year'       => posted_text('year'),
        'status'     => posted_text('status'),
    ];
}

// Returns [field => message]. An empty array means the input is valid.
function validate_student(array $in, bool $checkStatus): array
{
    $err = [];
    if ($in['student_id'] === '') {
        $err['student_id'] = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Z0-9\-]{3,20}$/', $in['student_id'])) {
        $err['student_id'] = 'Student ID must be 3 to 20 letters, digits or hyphens.';
    }

    if ($in['name'] === '') {
        $err['name'] = 'Name is required.';
    } elseif (!preg_match("/^[\p{L}][\p{L} .'\-]{1,99}$/u", $in['name'])) {
        $err['name'] = "Name must be 2 to 100 characters: letters, spaces, . ' and - only.";
    }

    if ($in['email'] === '') {
        $err['email'] = 'Email is required.';
    } elseif (strlen($in['email']) > 100 || !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'Invalid email address.';
    }

    if ($in['phone'] === '') {
        $err['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $in['phone'])) {
        $err['phone'] = 'Phone must have 10 to 15 digits (a leading + is allowed).';
    }

    if ($in['course'] === '') {
        $err['course'] = 'Please choose a course.';
    } elseif (!in_array($in['course'], STUDENT_COURSES, true)) {
        $err['course'] = 'Please choose a course from the list.';
    }

    if ($in['year'] === '') {
        $err['year'] = 'Please choose the academic year.';
    } elseif (!ctype_digit($in['year']) || !in_array((int) $in['year'], STUDENT_YEARS, true)) {
        $err['year'] = 'Academic year must be 1, 2, 3 or 4.';
    }

    if ($checkStatus && !in_array($in['status'], STUDENT_STATUSES, true)) {
        $err['status'] = 'Status must be active or inactive.';
    }
    return $err;
}

// Banner text for a failed form: "required" message if anything is empty, otherwise a general one.
function student_error_banner(array $errors, array $in): string
{
    foreach (['student_id', 'name', 'email', 'phone', 'course', 'year'] as $f) {
        if ($in[$f] === '' && isset($errors[$f])) {
            return 'Please fill in all required fields.';
        }
    }
    return 'Please correct the highlighted fields.';
}

/* ---------- Search + filter (used by the list AND the CSV export) ---------- */
// Reads ?q= &course= &status= from the address bar and cleans them.
function read_student_filters(): array
{
    $q      = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    $course = is_string($_GET['course'] ?? null) ? trim($_GET['course']) : '';
    $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : 'active';
    if (!in_array($status, ['active', 'inactive', 'all'], true)) {
        $status = 'active';
    }
    return ['q' => function_exists('mb_substr') ? mb_substr($q, 0, 100) : substr($q, 0, 100), 'course' => $course, 'status' => $status];
}

// Builds the WHERE part with ? placeholders. Returns [sql, types, values] for bind_param().
function build_student_where(array $f): array
{
    $parts = [];
    $types = '';
    $vals  = [];

    if ($f['q'] !== '') {
        // Escape the LIKE wildcards the user may have typed, so "50%" searches for a literal percent.
        $like    = '%' . addcslashes($f['q'], '\\%_') . '%';
        $parts[] = '(name LIKE ? OR student_id LIKE ? OR email LIKE ?)';
        $types  .= 'sss';
        array_push($vals, $like, $like, $like);
    }
    if ($f['course'] !== '') {
        $parts[] = 'course = ?';
        $types  .= 's';
        $vals[]  = $f['course'];
    }
    if ($f['status'] !== 'all') {
        $parts[] = 'status = ?';
        $types  .= 's';
        $vals[]  = $f['status'];
    }
    return [$parts ? ' WHERE ' . implode(' AND ', $parts) : '', $types, $vals];
}

// Validates an id from the address bar or a form. Returns the integer, or 0 when it is not valid.
function clean_id($raw): int
{
    return (is_string($raw) && ctype_digit($raw) && strlen($raw) <= 10 && (int) $raw > 0) ? (int) $raw : 0;
}

// Loads one student row (or null when it does not exist).
function find_student(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT id, student_id, name, email, phone, course, `year`, status FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($rid, $sid, $name, $email, $phone, $course, $year, $status);
    $row = null;
    if ($stmt->fetch()) {
        $row = ['id' => $rid, 'student_id' => $sid, 'name' => $name, 'email' => $email,
                'phone' => $phone, 'course' => $course, 'year' => (string) $year, 'status' => $status];
    }
    $stmt->close();
    return $row;
}

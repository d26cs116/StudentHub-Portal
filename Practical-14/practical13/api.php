<?php
/* =========================================================
   practical13/api.php - JSON API for the "notices" table

   Endpoints (all answer JSON):
     GET    api.php            -> list all notices                200
     GET    api.php?id=5       -> one notice                      200 / 404
     POST   api.php            -> create (JSON body)              201 / 400 / 409
     PUT    api.php?id=5       -> update (JSON body)              200 / 400 / 404 / 409
     DELETE api.php?id=5       -> delete                          200 / 400 / 404
     anything else             -> 405 Method Not Allowed
   Database problems          -> 500 with a safe message.

   Every response has the same shape:
     { "success": true|false, "message": "...", "data": ... }
   Invalid input also adds  "errors": { "field": "problem" }.
   ========================================================= */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

ob_start();   // so a stray notice or space can never break the JSON

const CATEGORIES   = ['Academic', 'Exam', 'Event', 'Holiday', 'General'];
const TITLE_MIN    = 3;
const TITLE_MAX    = 100;
const MESSAGE_MIN  = 5;
const MESSAGE_MAX  = 500;

/* ---------- helpers ---------- */

// Send ONE JSON response and stop. Used for success AND errors.
function respond(int $status, bool $success, string $message, $data = null, array $extra = []): void
{
    if (ob_get_length() !== false) {
        ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode(
        ['success' => $success, 'message' => $message, 'data' => $data] + $extra,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

// Character count (works even if the mbstring extension is off)
function text_length(string $v): int
{
    return (int) preg_match_all('/./su', $v);
}

// Make sure a row from MySQL has the right types for JSON (id as a number)
function shape(array $row): array
{
    return [
        'id'         => (int) $row['id'],
        'title'      => (string) $row['title'],
        'category'   => (string) $row['category'],
        'message'    => (string) $row['message'],
        'created_at' => (string) $row['created_at'],
        'updated_at' => (string) $row['updated_at'],
    ];
}

function find_notice(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, title, category, message, created_at, updated_at FROM notices WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ? shape($row) : null;
}

// Read ?id= from the URL. Returns null when it is missing; stops with 400 when it is not a positive whole number.
function read_id(): ?int
{
    if (!isset($_GET['id']) || $_GET['id'] === '') {
        return null;
    }
    $raw = $_GET['id'];
    if (!is_string($raw) || !ctype_digit($raw) || strlen($raw) > 10 || (int) $raw < 1) {
        respond(400, false, 'The id must be a positive whole number.');
    }
    return (int) $raw;
}

// Read and decode the JSON request body. Stops with 400 when it is not a JSON object.
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        respond(400, false, 'The request body is empty. Send the notice as JSON.');
    }
    $body = json_decode($raw, true);
    if (!is_array($body) || array_is_list($body)) {
        respond(400, false, 'The request body must be a valid JSON object.');
    }
    return $body;
}

// Validate title / category / message. Returns the CLEANED values or stops with 400 and a list of errors.
function validate_notice(array $body): array
{
    $errors = [];

    $title    = isset($body['title'])    && is_string($body['title'])    ? trim($body['title'])    : '';
    $category = isset($body['category']) && is_string($body['category']) ? trim($body['category']) : '';
    $message  = isset($body['message'])  && is_string($body['message'])  ? trim($body['message'])  : '';

    $len = text_length($title);
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    } elseif ($len < TITLE_MIN || $len > TITLE_MAX) {
        $errors['title'] = 'Title must be ' . TITLE_MIN . '-' . TITLE_MAX . ' characters.';
    }

    if ($category === '') {
        $errors['category'] = 'Category is required.';
    } elseif (!in_array($category, CATEGORIES, true)) {
        $errors['category'] = 'Choose one of: ' . implode(', ', CATEGORIES) . '.';
    }

    $len = text_length($message);
    if ($message === '') {
        $errors['message'] = 'Message is required.';
    } elseif ($len < MESSAGE_MIN || $len > MESSAGE_MAX) {
        $errors['message'] = 'Message must be ' . MESSAGE_MIN . '-' . MESSAGE_MAX . ' characters.';
    }

    if ($errors) {
        respond(400, false, 'Please correct the highlighted fields.', null, ['errors' => $errors]);
    }
    return ['title' => $title, 'category' => $category, 'message' => $message];
}

// SQLSTATE 23000 = integrity constraint violation (here: the UNIQUE title)
function is_duplicate(PDOException $e): bool
{
    return ($e->errorInfo[0] ?? $e->getCode()) === '23000';
}

/* ---------- unexpected problems: log the detail, show a safe message ---------- */
set_exception_handler(function (Throwable $e): void {
    error_log('Practical 13 API error: ' . $e->getMessage());
    respond(500, false, 'Something went wrong on the server. Please try again.');
});

/* ---------- 1. only these methods are allowed ---------- */
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$allowed = ['GET', 'POST', 'PUT', 'DELETE'];
if (!in_array($method, $allowed, true)) {
    header('Allow: ' . implode(', ', $allowed));
    respond(405, false, 'Method ' . preg_replace('/[^A-Za-z]/', '', $method) . ' is not allowed. Use GET, POST, PUT or DELETE.');
}

/* ---------- 2. connect to MySQL ---------- */
try {
    $pdo = db_connect();
} catch (PDOException $e) {
    error_log('Practical 13 database connection failed: ' . $e->getMessage());
    respond(500, false, 'The database is unavailable. Please check that MySQL is running and try again.');
}

/* ---------- 3. handle the request ---------- */
try {
    switch ($method) {

        case 'GET':     // LIST (all) or READ (one)
            $id = read_id();
            if ($id !== null) {
                $notice = find_notice($pdo, $id);
                if ($notice === null) {
                    respond(404, false, 'Notice not found.');
                }
                respond(200, true, 'Notice loaded.', $notice);
            }
            $rows = $pdo->query('SELECT id, title, category, message, created_at, updated_at FROM notices ORDER BY id DESC')->fetchAll();
            $list = array_map('shape', $rows);
            respond(200, true, $list ? count($list) . ' notice(s) loaded.' : 'No notices found.', $list, ['count' => count($list)]);
            break;

        case 'POST':    // CREATE
            $v  = validate_notice(read_json_body());
            $st = $pdo->prepare('INSERT INTO notices (title, category, message) VALUES (?, ?, ?)');
            $st->execute([$v['title'], $v['category'], $v['message']]);
            $notice = find_notice($pdo, (int) $pdo->lastInsertId());
            respond(201, true, 'Notice added successfully.', $notice);
            break;

        case 'PUT':     // UPDATE
            $id = read_id();
            if ($id === null) {
                respond(400, false, 'An id is required, for example api.php?id=5.');
            }
            $v = validate_notice(read_json_body());
            if (find_notice($pdo, $id) === null) {
                respond(404, false, 'Notice not found. It may already have been deleted.');
            }
            $st = $pdo->prepare('UPDATE notices SET title = ?, category = ?, message = ? WHERE id = ?');
            $st->execute([$v['title'], $v['category'], $v['message'], $id]);
            respond(200, true, 'Notice updated successfully.', find_notice($pdo, $id));
            break;

        case 'DELETE':  // DELETE
            $id = read_id();
            if ($id === null) {
                respond(400, false, 'An id is required, for example api.php?id=5.');
            }
            $st = $pdo->prepare('DELETE FROM notices WHERE id = ?');
            $st->execute([$id]);
            if ($st->rowCount() === 0) {
                respond(404, false, 'Notice not found. It may already have been deleted.');
            }
            respond(200, true, 'Notice deleted successfully.', ['id' => $id]);
            break;
    }
} catch (PDOException $e) {
    if (is_duplicate($e)) {
        respond(409, false, 'A notice with this title already exists. Choose a different title.',
            null, ['errors' => ['title' => 'This title is already used.']]);
    }
    error_log('Practical 13 query failed: ' . $e->getMessage());
    respond(500, false, 'The database could not complete the request. Please try again.');
}

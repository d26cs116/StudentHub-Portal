<?php
/* =========================================================
   test_connection.php - shows that PHP can talk to MySQL
   through PDO and lists the rows of the tables.
   Open it with:  http://localhost/<your folders>/Practical-8/test_connection.php
   ========================================================= */
require_once __DIR__ . '/db.php';          // gives us the $pdo connection (stops with a message if it fails)

// Escapes text before it is printed in HTML, so database values can never run as HTML/JavaScript
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$students = $events = $registrations = [];
$problems = [];                            // friendly messages if a query fails

// Optional search box (GET): the typed text is sent to MySQL as a bound value, never added to the SQL text
$search = (isset($_GET['q']) && is_string($_GET['q'])) ? trim($_GET['q']) : '';

/* ---------- 1. STUDENTS ---------- */
try {
    if ($search !== '') {
        // :name_search and :email_search are PLACEHOLDERS - the real value is supplied later in execute()
        $stmt = $pdo->prepare(
            "SELECT student_id, name, email, mobile
             FROM students
             WHERE name LIKE :name_search OR email LIKE :email_search
             ORDER BY student_id"
        );                                                   // prepare()  = MySQL checks and prepares the SQL
        $like = '%' . addcslashes($search, '%_\\') . '%';    // % and _ typed by the visitor are treated as plain characters
        $stmt->execute([':name_search' => $like, ':email_search' => $like]);   // execute() = run it with the values
    } else {
        $stmt = $pdo->prepare("SELECT student_id, name, email, mobile FROM students ORDER BY student_id");
        $stmt->execute();
    }
    $students = $stmt->fetchAll();         // fetchAll() = get ALL rows; FETCH_ASSOC (set in db.php) = $row['name'] style arrays
} catch (PDOException $ex) {
    error_log('test_connection.php students query: ' . $ex->getMessage());
    $problems[] = 'The students could not be loaded. Check that schema.sql has been imported.';
}

/* ---------- 2. EVENTS ---------- */
try {
    $stmt = $pdo->prepare("SELECT event_id, event_name, event_date, venue, capacity FROM events ORDER BY event_date");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('test_connection.php events query: ' . $ex->getMessage());
    $problems[] = 'The events could not be loaded. Check that schema.sql has been imported.';
}

/* ---------- 3. REGISTRATIONS (JOIN of all three tables) ---------- */
// Each registration row stores only ids; JOIN looks up the matching student and event names.
try {
    $stmt = $pdo->prepare(
        "SELECT r.registration_id, s.name AS student_name, ev.event_name, r.registration_date
         FROM registrations AS r
         INNER JOIN students AS s  ON r.student_id = s.student_id
         INNER JOIN events   AS ev ON r.event_id   = ev.event_id
         ORDER BY r.registration_id"
    );
    $stmt->execute();
    $registrations = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('test_connection.php registrations query: ' . $ex->getMessage());
    $problems[] = 'The registrations could not be loaded. Check that schema.sql has been imported.';
}

/* Prepared statements and SQL injection:
   Because the SQL text and the values travel separately, a value such as  ' OR 1=1 --
   is treated as plain text to look for, and can never change the query. */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP PDO Database Connection Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f3f6f4; color: #1b2f30; line-height: 1.6; }
        header { background: #12403f; color: #fff; padding: 1.2rem clamp(1rem, 4vw, 3rem); }
        header small { color: #cfe3dc; }
        main { max-width: 1100px; margin: 0 auto; padding: 1.5rem clamp(1rem, 4vw, 2rem) 3rem; }
        h1 { font-size: clamp(1.5rem, 3.5vw, 2.1rem); }
        h2 { font-size: 1.3rem; margin: 2rem 0 0.4rem; }
        .banner { padding: 0.9rem 1.2rem; border-radius: 12px; font-weight: 600; margin-bottom: 1rem; border: 2px solid; }
        .banner.ok { background: #e6f6ec; color: #14633a; border-color: #14633a; }
        .banner.bad { background: #fdecea; color: #b3261e; border-color: #b3261e; }
        .counts { display: flex; flex-wrap: wrap; gap: 0.7rem; margin-bottom: 0.5rem; }
        .count { background: #fff; border: 1px solid #c9d8d2; border-radius: 12px; padding: 0.5rem 1rem; }
        .count strong { font-size: 1.3rem; color: #12403f; }
        .note { color: #4f6663; font-size: 0.92rem; margin-bottom: 0.6rem; }
        .table-wrap { overflow-x: auto; background: #fff; border: 1px solid #c9d8d2; border-radius: 14px; }
        table { width: 100%; border-collapse: collapse; min-width: 520px; }
        th { text-align: left; background: #dfe9e5; padding: 0.7rem 1rem; font-size: 0.9rem; }
        td { padding: 0.65rem 1rem; border-top: 1px solid #c9d8d2; }
        tbody tr:hover { background: #f3f8f6; }
        .empty { padding: 1rem; color: #4f6663; }
        form { display: flex; flex-wrap: wrap; gap: 0.6rem; margin: 0.6rem 0 0.8rem; }
        input[type="text"] { flex: 1 1 240px; padding: 0.6rem 0.9rem; border: 1.5px solid #c9d8d2; border-radius: 10px; font: inherit; }
        input[type="text"]:focus { outline: 3px solid rgba(242, 182, 50, 0.55); border-color: #12403f; }
        button, .reset { padding: 0.6rem 1.2rem; border-radius: 10px; border: 2px solid #12403f; background: #12403f; color: #fff; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
        .reset { background: transparent; color: #12403f; }
        button:hover { background: #e8604c; border-color: #e8604c; }
    </style>
</head>
<body>
    <header>
        <h1>PHP PDO Database Connection Test</h1>
        <small>Practical 8 &ndash; College Event Registration System</small>
    </header>

    <main>
        <!-- If db.php had failed, the script would have stopped before this line -->
        <div class="banner ok" role="status">✅ Connection established successfully with the MySQL database "<?= e($dbname) ?>" using PHP PDO.</div>

        <?php foreach ($problems as $problem): ?>
            <div class="banner bad" role="alert">❌ <?= e($problem) ?></div>
        <?php endforeach; ?>

        <div class="counts" aria-label="Number of records fetched">
            <div class="count"><strong><?= count($students) ?></strong> students fetched</div>
            <div class="count"><strong><?= count($events) ?></strong> events fetched</div>
            <div class="count"><strong><?= count($registrations) ?></strong> registrations fetched</div>
        </div>

        <h2>Students</h2>
        <form method="get" action="test_connection.php">
            <label for="q" class="note" style="align-self:center">Search name or email:</label>
            <input type="text" id="q" name="q" value="<?= e($search) ?>" maxlength="50" placeholder="e.g. Aarav">
            <button type="submit">Search</button>
            <?php if ($search !== ''): ?><a class="reset" href="test_connection.php">Show all</a><?php endif; ?>
        </form>
        <div class="table-wrap">
            <?php if (count($students) === 0): ?>
                <p class="empty">No students found.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Mobile</th></tr></thead>
                <tbody>
                <?php foreach ($students as $row): ?>
                    <tr>
                        <td><?= e($row['student_id']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['mobile']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <h2>Events</h2>
        <div class="table-wrap">
            <?php if (count($events) === 0): ?>
                <p class="empty">No events found.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>ID</th><th>Event</th><th>Date</th><th>Venue</th><th>Capacity</th></tr></thead>
                <tbody>
                <?php foreach ($events as $row): ?>
                    <tr>
                        <td><?= e($row['event_id']) ?></td>
                        <td><?= e($row['event_name']) ?></td>
                        <td><?= e(date('d M Y', strtotime($row['event_date']))) ?></td>
                        <td><?= e($row['venue']) ?></td>
                        <td><?= e($row['capacity']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <h2>Registrations</h2>
        <p class="note">Joined from the registrations, students and events tables.</p>
        <div class="table-wrap">
            <?php if (count($registrations) === 0): ?>
                <p class="empty">No registrations found.</p>
            <?php else: ?>
            <table>
                <thead><tr><th>Reg. ID</th><th>Student</th><th>Event</th><th>Registered on</th></tr></thead>
                <tbody>
                <?php foreach ($registrations as $row): ?>
                    <tr>
                        <td><?= e($row['registration_id']) ?></td>
                        <td><?= e($row['student_name']) ?></td>
                        <td><?= e($row['event_name']) ?></td>
                        <td><?= e(date('d M Y, h:i A', strtotime($row['registration_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>

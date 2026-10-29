<?php
/* students.php - student list with search, course filter and status view (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/student_helpers.php';
require_role('Admin');

$f = read_student_filters();                 // q, course, status (always cleaned)
$filtersActive = ($f['q'] !== '' || $f['course'] !== '' || $f['status'] !== 'active');

$students = [];
$courses  = [];
$dbError  = false;
try {
    // Course dropdown = the courses that really exist in the table.
    $res = $conn->prepare("SELECT DISTINCT course FROM students ORDER BY course");
    $res->execute();
    $res->bind_result($courseName);
    while ($res->fetch()) {
        $courses[] = $courseName;
    }
    $res->close();

    // The list itself: only the WHERE parts that are needed, every value bound with ?
    [$where, $types, $vals] = build_student_where($f);
    $stmt = $conn->prepare("SELECT id, student_id, name, email, phone, course, `year`, status FROM students" . $where . " ORDER BY name, id");
    if ($types !== '') {
        $stmt->bind_param($types, ...$vals);
    }
    $stmt->execute();
    $stmt->bind_result($rid, $sid, $name, $email, $phone, $course, $year, $status);
    while ($stmt->fetch()) {
        $students[] = ['id' => $rid, 'student_id' => $sid, 'name' => $name, 'email' => $email,
                       'phone' => $phone, 'course' => $course, 'year' => $year, 'status' => $status];
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    error_log('Student list failed: ' . $ex->getMessage());
    $dbError = true;
}

// Link that keeps the current search/filter (used by the Export button)
$exportQuery = http_build_query(['q' => $f['q'], 'course' => $f['course'], 'status' => $f['status']]);

render_header('Students', 'students');
?>
        <section class="students-section" aria-labelledby="stu-list-title">
            <div class="section-head">
                <div>
                    <h2 id="stu-list-title">Student Management</h2>
                    <p class="section-description">Add, edit, search and deactivate students.</p>
                </div>
                <div class="head-actions">
                    <a class="btn-link" href="add_student.php">+ Add Student</a>
                    <a class="btn-link btn-secondary" href="export_students.php?<?= e($exportQuery) ?>">⬇ Export CSV</a>
                </div>
            </div>

            <?php flash_render(); ?>

            <!-- Search + filter: a GET form, so the address bar keeps the search and can be bookmarked -->
            <form class="toolbar row" action="students.php" method="GET" role="search">
                <div class="tool tool-search">
                    <label for="q">Search (name, student ID or email)</label>
                    <input type="text" id="q" name="q" value="<?= e($f['q']) ?>" maxlength="100" placeholder="e.g. Aarav, D26CS101 or @example.edu">
                </div>
                <div class="tool">
                    <label for="course">Course</label>
                    <select id="course" name="course">
                        <option value="">All courses</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= e($c) ?>"<?= $f['course'] === $c ? ' selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="tool">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active"<?= $f['status'] === 'active' ? ' selected' : '' ?>>Active only</option>
                        <option value="inactive"<?= $f['status'] === 'inactive' ? ' selected' : '' ?>>Inactive only</option>
                        <option value="all"<?= $f['status'] === 'all' ? ' selected' : '' ?>>All students</option>
                    </select>
                </div>
                <div class="tool"><button type="submit" class="tool-btn">Search / Filter</button></div>
                <div class="tool"><a class="tool-btn tool-link" href="students.php">Clear</a></div>
            </form>

            <?php if ($dbError): ?>
                <div class="form-status error" role="alert">❌ Unable to complete the operation.</div>
            <?php elseif (!$students): ?>
                <div class="form-status info" role="status">
                    <?= $filtersActive ? 'ℹ️ No students found matching your search.' : 'ℹ️ No students yet. Use “Add Student” to create the first record.' ?>
                </div>
            <?php else: ?>
                <p class="result-count">Showing <strong><?= count($students) ?></strong> student<?= count($students) === 1 ? '' : 's' ?></p>
                <table class="students-table">
                    <thead>
                        <tr><th>Student ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Course</th><th>Year</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr<?= $s['status'] === 'inactive' ? ' class="row-inactive"' : '' ?>>
                            <td><?= e($s['student_id']) ?></td>
                            <td><?= e($s['name']) ?></td>
                            <td><?= e($s['email']) ?></td>
                            <td><?= e($s['phone']) ?></td>
                            <td><?= e($s['course']) ?></td>
                            <td><?= (int) $s['year'] ?></td>
                            <td><span class="status-badge status-<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                            <td class="actions">
                                <a class="act-link" href="edit_student.php?id=<?= (int) $s['id'] ?>">Edit</a>
                                <?php if ($s['status'] === 'active'): ?>
                                    <a class="act-link act-danger" href="delete_student.php?id=<?= (int) $s['id'] ?>">Deactivate</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
<?php render_footer(); ?>

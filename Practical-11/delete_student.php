<?php
/* =========================================================
   delete_student.php - SOFT DELETE (deactivation). Admin only.
   GET  ?id=5 -> shows "Are you sure you want to deactivate this student?"
   POST       -> sets status = 'inactive'. The row is NEVER removed (no SQL DELETE anywhere).
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/student_helpers.php';
require_role('Admin');

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$id     = clean_id($isPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));
if ($id === 0) {
    flash_set('error', 'Student not found.');
    redirect('students.php');
}

try {
    $student = find_student($conn, $id);
} catch (mysqli_sql_exception $ex) {
    error_log('Deactivate: load failed (' . $ex->getCode() . ')');
    flash_set('error', 'Unable to complete the operation.');
    redirect('students.php');
}
if ($student === null) {
    flash_set('error', 'Student not found.');
    redirect('students.php');
}
if ($student['status'] === 'inactive') {                       // do not deactivate twice
    flash_set('error', $student['name'] . ' is already inactive.');
    redirect('students.php');
}

/* ---------- POST: do the deactivation ---------- */
if ($isPost) {
    if (!csrf_is_valid()) {
        flash_set('error', 'Your session expired. Please try again.');
        redirect('students.php');
    }
    try {
        // "AND status = 'active'" makes sure we only change a student who is still active.
        $stmt = $conn->prepare("UPDATE students SET status = 'inactive' WHERE id = ? AND status = 'active'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();
        if ($changed === 1) {
            flash_set('success', 'Student deactivated successfully.');
        } else {
            flash_set('error', 'Unable to complete the operation.');   // nothing changed: never claim success
        }
    } catch (mysqli_sql_exception $ex) {
        error_log('Deactivate failed: ' . $ex->getMessage());
        flash_set('error', 'Unable to complete the operation.');
    }
    redirect('students.php?status=all');
}

/* ---------- GET: ask for confirmation ---------- */
render_header('Deactivate Student', 'students');
?>
        <section class="denied-box" aria-labelledby="del-title">
            <h2 id="del-title">Confirm Deactivation</h2>
            <div class="form-status error" role="alert">⚠️ Are you sure you want to deactivate this student?</div>
            <div class="info-grid">
                <div class="info-tile"><span class="info-label">Student ID</span><strong><?= e($student['student_id']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Name</span><strong><?= e($student['name']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Course</span><strong><?= e($student['course']) ?> · Year <?= e($student['year']) ?></strong></div>
            </div>
            <p class="section-description">The record is not erased. Its status is changed to <strong>inactive</strong> and it stays in the database.</p>
            <form action="delete_student.php" method="POST" class="confirm-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <div class="form-actions">
                    <button type="submit" class="reg-submit btn-danger">Yes, deactivate</button>
                    <a href="students.php" class="reg-reset">Cancel</a>
                </div>
            </form>
        </section>
<?php render_footer(); ?>

<?php
/* edit_student.php - load, validate and update one student (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/student_helpers.php';
require_once __DIR__ . '/includes/student_form.php';
require_role('Admin');

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$id     = clean_id($isPost ? ($_POST['id'] ?? null) : ($_GET['id'] ?? null));
if ($id === 0) {
    flash_set('error', 'Student not found.');          // missing or non-numeric id
    redirect('students.php');
}

try {
    $current = find_student($conn, $id);
} catch (mysqli_sql_exception $ex) {
    error_log('Edit student: load failed (' . $ex->getCode() . ')');
    flash_set('error', 'Unable to complete the operation.');
    redirect('students.php');
}
if ($current === null) {
    flash_set('error', 'Student not found.');
    redirect('students.php');
}

$values = $current;          // the form starts with what is stored in MySQL
$errors = [];
$banner = '';

if ($isPost) {
    $values = read_student_input();
    if (!csrf_is_valid()) {
        $banner = 'Your session expired. Please check the form and submit it again.';
    } else {
        $errors = validate_student($values, true);
        if ($errors) {
            $banner = student_error_banner($errors, $values);
        } else {
            try {
                $year = (int) $values['year'];
                $conn->begin_transaction();           // the update and its audit entry succeed or fail together
                $stmt = $conn->prepare("UPDATE students SET student_id = ?, name = ?, email = ?, phone = ?, course = ?, `year` = ?, status = ? WHERE id = ?");
                $stmt->bind_param('sssssisi', $values['student_id'], $values['name'], $values['email'], $values['phone'], $values['course'], $year, $values['status'], $id);
                $stmt->execute();     // an UPDATE that changes nothing is still a success
                $stmt->close();
                audit_log($conn, 'STUDENT_UPDATE', 'student', $id, 'Updated student ' . $values['student_id']);   // Practical 14
                $conn->commit();
                flash_set('success', 'Student updated successfully.');
                redirect('students.php');
            } catch (mysqli_sql_exception $ex) {
                db_rollback_quietly($conn);
                if ((int) $ex->getCode() === 1062) {
                    $banner = 'A student with this ID or email already exists.';
                } else {
                    error_log('Edit student failed: ' . $ex->getMessage());
                    $banner = 'Unable to complete the operation.';
                }
            }
        }
    }
}

render_header('Edit Student', 'students');
?>
        <section class="registration-section" aria-labelledby="edit-title">
            <h2 id="edit-title">Edit Student</h2>
            <p class="section-description">Editing <strong><?= e($current['name']) ?></strong> (<?= e($current['student_id']) ?>)</p>
            <?php if ($banner !== ''): ?><div class="form-status error" role="alert">❌ <?= e($banner) ?></div><?php endif; ?>
<?php render_student_form('edit_student.php', $values, $errors, true, $id, 'Save Changes'); ?>
        </section>
<?php render_footer(); ?>

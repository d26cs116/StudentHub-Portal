<?php
/* add_student.php - form + processing for adding a student (Admin only). */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/student_helpers.php';
require_once __DIR__ . '/includes/student_form.php';
require_role('Admin');

$values = ['student_id' => '', 'name' => '', 'email' => '', 'phone' => '', 'course' => '', 'year' => '', 'status' => 'active'];
$errors = [];
$banner = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid()) {
        $banner = 'Your session expired. Please check the form and submit it again.';
        $values = read_student_input();
    } else {
        $values = read_student_input();
        $errors = validate_student($values, false);
        if ($errors) {
            $banner = student_error_banner($errors, $values);
        } else {
            try {
                $year = (int) $values['year'];
                $stmt = $conn->prepare("INSERT INTO students (student_id, name, email, phone, course, `year`) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssi', $values['student_id'], $values['name'], $values['email'], $values['phone'], $values['course'], $year);
                $stmt->execute();
                $ok = ($stmt->affected_rows === 1);
                $stmt->close();
                if (!$ok) {
                    throw new RuntimeException('Insert did not add exactly one row.');
                }
                // Post/Redirect/Get: a browser refresh cannot add the same student twice.
                flash_set('success', 'Student added successfully.');
                redirect('students.php');
            } catch (mysqli_sql_exception | RuntimeException $ex) {
                if ($ex instanceof mysqli_sql_exception && (int) $ex->getCode() === 1062) {
                    $banner = 'A student with this ID or email already exists.';
                } else {
                    error_log('Add student failed: ' . $ex->getMessage());
                    $banner = 'Unable to complete the operation.';
                }
            }
        }
    }
}

render_header('Add Student', 'students');
?>
        <section class="registration-section" aria-labelledby="add-title">
            <h2 id="add-title">Add Student</h2>
            <p class="section-description">Fields marked <span aria-hidden="true">*</span> are required.</p>
            <?php if ($banner !== ''): ?><div class="form-status error" role="alert">❌ <?= e($banner) ?></div><?php endif; ?>
<?php render_student_form('add_student.php', $values, $errors, false, null, 'Add Student'); ?>
        </section>
<?php render_footer(); ?>

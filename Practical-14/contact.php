<?php
/* =========================================================
   contact.php – shows the contact / registration form.
   It never saves anything: the form posts to process.php,
   which sends the visitor back here with a message.
   ========================================================= */
require __DIR__ . '/includes/config.php';

// "Flash" messages: read them once from the session, then delete them,
// so they disappear when the page is refreshed.
$errors  = $_SESSION['form_errors'] ?? [];
$old     = $_SESSION['form_old'] ?? [];
$success = $_SESSION['form_success'] ?? false;
unset($_SESSION['form_errors'], $_SESSION['form_old'], $_SESSION['form_success']);

// CSRF token: a random secret stored in the session and sent with the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Small helpers used inside the form below
function field_class(array $errors, string $field): string
{
    return 'form-control' . (isset($errors[$field]) ? ' is-invalid' : '');
}
function field_attrs(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . '-error"' : '';
}
function field_error(array $errors, string $field): void
{
    if (isset($errors[$field])) {
        echo '<div class="invalid-feedback d-block" id="' . $field . '-error">❌ ' . e($errors[$field]) . '</div>';
    }
}
$fieldErrorCount = count(array_diff_key($errors, ['general' => true]));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Contact</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="portal">
    <header>
        <div class="logo">Student Hub</div>
        <nav id="main-nav" aria-label="Main navigation">
            <a href="index.html">Home</a>
            <a href="dashboard.html">Dashboard</a>
            <a href="attendance.html">Attendance</a>
            <a href="assignments.html">Assignments</a>
            <a href="timetable.html">Timetable</a>
            <a href="results.html">Results</a>
            <a href="notices.html">Notices</a>
            <a href="explore.html">Explore</a>
            <a href="profile.html">Profile</a>
            <a href="contact.php" class="active">Contact</a>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        </nav>
        <div class="header-actions">
            <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false"><span aria-hidden="true">🌙</span> Dark</button>
            <button type="button" id="menu-toggle" class="menu-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
        </div>
    </header>
    <main>
        <section>
            <h2>Contact Us</h2>
            <p class="section-description">Send a message to the department using the form. All fields are required.</p>

            <?php if ($success): ?>
                <div class="alert alert-success mt-3" role="status">✅ <strong>Your form has been submitted successfully!</strong> Thank you, we will get back to you soon.</div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger mt-3" role="alert">❌ <?= e($errors['general']) ?></div>
            <?php elseif ($fieldErrorCount > 0): ?>
                <div class="alert alert-danger mt-3" role="alert">❌ Please correct the <?= $fieldErrorCount ?> highlighted field<?= $fieldErrorCount === 1 ? '' : 's' ?> below.</div>
            <?php endif; ?>

            <div class="row g-4 mt-1">
                <div class="col-lg-7">
                    <!-- method="POST": the data travels in the request body (not in the address bar) -->
                    <form action="process.php" method="POST" class="row g-3" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="<?= field_class($errors, 'name') ?>" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" maxlength="50" autocomplete="name" required<?= field_attrs($errors, 'name') ?>>
                            <?php field_error($errors, 'name'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="<?= field_class($errors, 'email') ?>" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="100" autocomplete="email" required<?= field_attrs($errors, 'email') ?>>
                            <?php field_error($errors, 'email'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="<?= field_class($errors, 'mobile') ?>" id="mobile" name="mobile" value="<?= e($old['mobile'] ?? '') ?>" maxlength="10" inputmode="numeric" autocomplete="tel-national" placeholder="10-digit number" required<?= field_attrs($errors, 'mobile') ?>>
                            <?php field_error($errors, 'mobile'); ?>
                        </div>

                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject / Course</label>
                            <select class="<?= str_replace('form-control', 'form-select', field_class($errors, 'subject')) ?>" id="subject" name="subject" required<?= field_attrs($errors, 'subject') ?>>
                                <option value="">Select a subject</option>
                                <?php foreach ($allowedSubjects as $subjectOption): ?>
                                    <option value="<?= e($subjectOption) ?>"<?= (($old['subject'] ?? '') === $subjectOption) ? ' selected' : '' ?>><?= e($subjectOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php field_error($errors, 'subject'); ?>
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="<?= field_class($errors, 'message') ?>" id="message" name="message" rows="5" maxlength="<?= MAX_MESSAGE_LENGTH ?>" required<?= field_attrs($errors, 'message') ?>><?= e($old['message'] ?? '') ?></textarea>
                            <?php field_error($errors, 'message'); ?>
                            <div class="form-text"><?= MIN_MESSAGE_LENGTH ?> to <?= MAX_MESSAGE_LENGTH ?> characters.</div>
                        </div>

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Send Message</button>
                            <a href="contact.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="col-lg-5">
                    <div class="list-group">
                        <div class="list-group-item"><strong>Visit</strong><br>Department office, CSE block</div>
                        <div class="list-group-item"><strong>Office hours</strong><br>Monday to Friday, 9:00 – 3:15</div>
                        <div class="list-group-item"><strong>College</strong><br>CSPIT, Computer Science and Engineering</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer>
        <p>Student Hub Portal</p>
        <p>Developed by Arshit Vaghela</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>

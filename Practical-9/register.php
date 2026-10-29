<?php
/* =========================================================
   register.php - shows the user registration form.
   It never touches the database: the form posts to
   process_registration.php, which sends the visitor back here
   with a success or error message.
   ========================================================= */
require __DIR__ . '/includes/config.php';

// "Flash" messages: read them once from the session, then delete them,
// so they disappear when the page is refreshed.
$errors  = $_SESSION['form_errors'] ?? [];
$old     = $_SESSION['form_old'] ?? [];          // only name, username and email - passwords are never kept
$success = $_SESSION['register_success'] ?? false;
unset($_SESSION['form_errors'], $_SESSION['form_old'], $_SESSION['register_success']);

// CSRF token: a random secret stored in the session and sent with the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Small helpers used inside the form below
function field_class(array $errors, string $field, bool $full): string
{
    return 'form-field' . ($full ? ' full' : '') . (isset($errors[$field]) ? ' error' : '');
}
function field_invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true"' : '';
}
function field_message(array $errors, string $field): string
{
    return isset($errors[$field]) ? '❌ ' . e($errors[$field]) : '';
}
$fieldErrorCount = count(array_diff_key($errors, ['general' => true]));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Create Account</title>
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
            <a href="contact.php">Contact</a>
            <a href="register.php" class="active">Register</a>
            <a href="login.html">Login</a>
        </nav>
        <div class="header-actions">
            <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false"><span aria-hidden="true">🌙</span> Dark</button>
            <button type="button" id="menu-toggle" class="menu-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
        </div>
    </header>
    <main>
        <section class="registration-section" aria-labelledby="reg-title">
            <h2 id="reg-title">Create Your Account</h2>
            <p class="section-description">Register for Student Hub. Fields marked <span aria-hidden="true">*</span> are required.</p>

            <?php if ($success): ?>
                <div class="form-status success" role="status">✅ Registration successful! Your account has been created.</div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="form-status error" role="alert">❌ <?= e($errors['general']) ?></div>
            <?php elseif ($fieldErrorCount > 0): ?>
                <div class="form-status error" role="alert">❌ Please correct the <?= $fieldErrorCount ?> highlighted field<?= $fieldErrorCount === 1 ? '' : 's' ?> below.</div>
            <?php endif; ?>

            <!-- method="POST": the data travels in the request body (not in the address bar) -->
            <form id="user-registration-form" class="reg-form" action="process_registration.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="form-grid">
                    <div class="<?= field_class($errors, 'full_name', false) ?>" id="field-full_name">
                        <label for="full_name">Full Name <span aria-hidden="true">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name'] ?? '') ?>" maxlength="50" autocomplete="name" required aria-describedby="full_name-message"<?= field_invalid($errors, 'full_name') ?>>
                        <p class="field-message" id="full_name-message"><?= field_message($errors, 'full_name') ?></p>
                    </div>
                    <div class="<?= field_class($errors, 'username', false) ?>" id="field-username">
                        <label for="username">Username <span aria-hidden="true">*</span></label>
                        <input type="text" id="username" name="username" value="<?= e($old['username'] ?? '') ?>" maxlength="20" autocomplete="username" autocapitalize="none" spellcheck="false" required aria-describedby="username-hint username-message"<?= field_invalid($errors, 'username') ?>>
                        <p class="field-hint" id="username-hint">3 to 20 letters, digits or underscores.</p>
                        <p class="field-message" id="username-message"><?= field_message($errors, 'username') ?></p>
                    </div>
                    <div class="<?= field_class($errors, 'email', true) ?>" id="field-email">
                        <label for="email">Email Address <span aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" maxlength="100" autocomplete="email" required aria-describedby="email-message"<?= field_invalid($errors, 'email') ?>>
                        <p class="field-message" id="email-message"><?= field_message($errors, 'email') ?></p>
                    </div>
                    <div class="<?= field_class($errors, 'password', false) ?>" id="field-password">
                        <label for="password">Password <span aria-hidden="true">*</span></label>
                        <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="password-hint password-message"<?= field_invalid($errors, 'password') ?>>
                        <p class="field-hint" id="password-hint">At least 8 characters.</p>
                        <p class="field-message" id="password-message"><?= field_message($errors, 'password') ?></p>
                    </div>
                    <div class="<?= field_class($errors, 'confirm_password', false) ?>" id="field-confirm_password">
                        <label for="confirm_password">Confirm Password <span aria-hidden="true">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="confirm_password-message"<?= field_invalid($errors, 'confirm_password') ?>>
                        <p class="field-message" id="confirm_password-message"><?= field_message($errors, 'confirm_password') ?></p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="reg-submit">Register</button>
                    <a href="register.php" class="reg-reset">Reset</a>
                </div>
                <p class="section-description" style="margin-top:1rem">Already registered? <a href="login.html"><strong>Go to Login</strong></a></p>
            </form>
        </section>
    </main>
    <footer>
        <p>Student Hub Portal</p>
        <p>Developed by Arshit Vaghela</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>

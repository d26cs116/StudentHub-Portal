<?php
/* =========================================================
   login.php - shows the login form (it never touches the database).
   The form POSTs to authenticate.php, which checks the credentials.
   ========================================================= */
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Skip the form and go to the right dashboard.
send_no_cache_headers();
enforce_session_timeout();
if (is_logged_in()) {
    redirect(role_home($_SESSION['role']));
}

// Messages sent through the address bar: ONLY these fixed keys are accepted,
// so nobody can inject their own text into the page.
$statusMessages = [
    'logout'         => ['success', '✅ You have been logged out successfully.'],
    'timeout'        => ['warning', '⏰ Your session expired because of inactivity. Please log in again.'],
    'login_required' => ['warning', '🔒 Please log in to view that page.'],
];
$status  = $_GET['status'] ?? '';
$message = (is_string($status) && isset($statusMessages[$status])) ? $statusMessages[$status] : null;

// One-time (flash) messages left by authenticate.php / process_registration.php
$loginError = $_SESSION['login_error'] ?? '';
$oldEmail   = $_SESSION['login_old_email'] ?? '';        // the email only - the password is NEVER sent back
unset($_SESSION['login_error'], $_SESSION['login_old_email']);

// CSRF token for the form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

render_header('Login', 'login', false);
?>
        <section class="auth-card login-card" aria-labelledby="login-title">
            <h2 id="login-title">Login</h2>
            <p class="section-description">Enter your email and password to open your dashboard.</p>

            <!-- Success / warning / error message area -->
            <?php if ($loginError !== ''): ?>
                <div class="form-status error" role="alert">❌ <?= e($loginError) ?></div>
            <?php elseif ($message !== null): ?>
                <div class="form-status <?= e($message[0]) ?>" role="status"><?= e($message[1]) ?></div>
            <?php endif; ?>

            <form action="authenticate.php" method="POST" id="login-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>" maxlength="100" autocomplete="username" required>

                <label for="password">Password</label>
                <div class="password-box">
                    <input type="password" id="password" name="password" maxlength="72" autocomplete="current-password" required>
                    <button type="button" class="eye-btn" id="toggle-password" aria-label="Show password">👁</button>
                </div>

                <input type="submit" value="Login">
            </form>

            <div class="auth-switch">
                Don't have an account? <a href="register.php">Create Account</a>
            </div>
        </section>
        <script>
            // Show / hide the password while typing (a convenience only; security is done on the server)
            document.getElementById("toggle-password").addEventListener("click", function () {
                var input = document.getElementById("password");
                var show = input.type === "password";
                input.type = show ? "text" : "password";
                this.textContent = show ? "🙈" : "👁";
                this.setAttribute("aria-label", show ? "Hide password" : "Show password");
            });
        </script>
<?php render_footer(); ?>

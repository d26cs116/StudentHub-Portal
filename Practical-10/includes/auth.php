<?php
/* =========================================================
   includes/auth.php - reusable authentication + authorization helpers.
   Every protected page starts with ONE of these lines:
       require_login();            // any logged-in user
       require_role('Admin');      // Admin only
       require_role('Student');    // Student only
   AUTHENTICATION = "who are you?"  (login, session)
   AUTHORIZATION  = "what may you do?" (the role check)
   ========================================================= */
require_once __DIR__ . '/config.php';       // session, e(), redirect(), destroy_session_completely()
require_once __DIR__ . '/../timeout.php';   // enforce_session_timeout()
require_once __DIR__ . '/../db.php';        // $conn (MySQLi)
require_once __DIR__ . '/layout.php';       // render_header() / render_footer()

// The only roles this application understands.
const ALLOWED_ROLES = ['Admin', 'Student'];

// The page each role lands on after login.
function role_home(string $role): string
{
    return $role === 'Admin' ? 'admin_dashboard.php' : 'student_dashboard.php';
}

// Tell the browser not to store protected pages, so the Back button
// cannot show a protected page again after logout.
function send_no_cache_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Does the session hold a complete, valid login?
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role'])
        && is_int($_SESSION['user_id'])
        && in_array($_SESSION['role'], ALLOWED_ROLES, true);
}

// Any logged-in user. Order matters: timeout first, then "are you logged in?", then re-check the user in the database.
function require_login(): void
{
    send_no_cache_headers();
    enforce_session_timeout();                         // may redirect with ?status=timeout
    if (!is_logged_in()) {
        redirect('login.php?status=login_required');
    }

    // Re-read the user's CURRENT role from the database on every request.
    // If the account was deleted or its role changed, the old session stops working at once.
    try {
        global $conn;
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($dbRole);
        $found = $stmt->fetch();
        $stmt->close();
    } catch (mysqli_sql_exception $ex) {
        error_log('Auth: role re-check failed (' . $ex->getCode() . ')');
        http_response_code(500);
        exit('Something went wrong. Please try again later.');
    }
    if (!$found || !in_array($dbRole, ALLOWED_ROLES, true)) {
        destroy_session_completely();
        redirect('login.php?status=login_required');
    }
    $_SESSION['role'] = $dbRole;                       // always trust the database, not old session data
}

// Logged in AND holding exactly this role.
function require_role(string $role): void
{
    require_login();
    if ($_SESSION['role'] !== $role) {
        deny_access($role);
    }
}

// Show a clear "403 Forbidden" page and stop. The protected content is never sent.
function deny_access(string $neededRole): void
{
    http_response_code(403);
    render_header('Access Denied', '');
    ?>
        <section class="denied-box" aria-labelledby="denied-title">
            <h2 id="denied-title">Access Denied</h2>
            <div class="form-status error" role="alert">
                ❌ You do not have permission to view this page. It is only for the <strong><?= e($neededRole) ?></strong> role.
            </div>
            <p class="section-description">You are signed in as <strong><?= e($_SESSION['user_name'] ?? '') ?></strong> (<?= e($_SESSION['role'] ?? '') ?>).</p>
            <p><a class="btn-link" href="<?= e(role_home($_SESSION['role'] ?? 'Student')) ?>">Go to my dashboard</a></p>
        </section>
    <?php
    render_footer();
    exit;
}

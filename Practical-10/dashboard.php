<?php
/* dashboard.php - general dashboard: any logged-in user (Admin or Student). */
require_once __DIR__ . '/includes/auth.php';
require_login();      // not logged in -> login.php ; idle too long -> login.php?status=timeout

$role = $_SESSION['role'];
render_header('Dashboard', 'dashboard');
?>
        <section aria-labelledby="dash-title">
            <h2 id="dash-title">Welcome, <span class="user-highlight"><?= e($_SESSION['user_name']) ?></span></h2>
            <p class="section-description">You are logged in to the Student Hub portal.</p>
            <div class="info-grid">
                <div class="info-tile"><span class="info-label">Name</span><strong><?= e($_SESSION['user_name']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Email</span><strong><?= e($_SESSION['user_email']) ?></strong></div>
                <div class="info-tile"><span class="info-label">Role</span><span class="role-badge role-<?= e(strtolower($role)) ?>"><?= e($role) ?></span></div>
                <div class="info-tile"><span class="info-label">Auto logout after</span><strong><?= e(SESSION_TIMEOUT >= 60 ? round(SESSION_TIMEOUT / 60, 1) . ' min' : SESSION_TIMEOUT . ' sec') ?> idle</strong></div>
            </div>
            <p style="margin-top:1.2rem"><a class="btn-link" href="<?= e(role_home($role)) ?>">Open my <?= $role === 'Admin' ? 'Admin Panel' : 'Student Area' ?></a></p>
        </section>
<?php render_footer(); ?>
